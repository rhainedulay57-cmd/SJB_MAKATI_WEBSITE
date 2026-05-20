<?php
/**
 * Check Sacrament Booking Status
 */

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Method Not Allowed"]);
    exit();
}

$conn = new mysqli("localhost", "root", "", "sjb_databases");

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Database connection failed"]);
    exit();
}

$booking_id = $_POST['booking_id'] ?? null;
$email = $_POST['email'] ?? null;

if (!$booking_id || !$email) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Missing required fields"]);
    exit();
}

// Query to find booking
// First, ensure the booking exists
$stmt = $conn->prepare("SELECT booking_id, sacrament_type, status, created_at, admin_notes FROM sacrament_bookings WHERE booking_id = ?");
$stmt->bind_param("s", $booking_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(404);
    echo json_encode(["success" => false, "message" => "Booking not found"]);
    $conn->close();
    exit();
}

$booking = $result->fetch_assoc();
$s_type = $booking['sacrament_type'];

// Determine which child table/email column to check based on sacrament type
$childTable = '';
$emailColumn = '';
if ($s_type === 'wedding') {
    $childTable = 'wedding_bookings';
    $emailColumn = 'groom_email';
} elseif ($s_type === 'baptism') {
    $childTable = 'baptism_bookings';
    $emailColumn = 'parent1_email';
} elseif ($s_type === 'funeral') {
    $childTable = 'funeral_bookings';
    $emailColumn = 'requestor_email';
}

$contactEmail = null;
if ($childTable && $emailColumn) {
    $sql = "SELECT $emailColumn FROM $childTable WHERE booking_id = ? LIMIT 1";
    $stmt2 = $conn->prepare($sql);
    $stmt2->bind_param("s", $booking_id);
    $stmt2->execute();
    $res2 = $stmt2->get_result();
    if ($res2 && $res2->num_rows > 0) {
        $row2 = $res2->fetch_assoc();
        $contactEmail = $row2[$emailColumn] ?? null;
    }
}

// Compare emails (case-insensitive)
$match = false;
if ($contactEmail) {
    if (strtolower(trim($contactEmail)) === strtolower(trim($email))) {
        $match = true;
    }
}

if ($match) {
    // Return booking details plus contact email
    $booking['contact_email'] = $contactEmail;
    echo json_encode(["success" => true, "booking" => $booking]);
} else {
    http_response_code(404);
    echo json_encode(["success" => false, "message" => "Booking not found for provided email"]);
}

$conn->close();
?>
