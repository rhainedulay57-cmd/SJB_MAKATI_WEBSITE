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
    // Try fallback: maybe this is a Mass & Blessing request (numeric ID)
    $possibleId = intval($booking_id);
    if ($possibleId > 0) {
        $stmtm = $conn->prepare("SELECT * FROM mass_blessing WHERE id = ? LIMIT 1");
        if ($stmtm) {
            $stmtm->bind_param("i", $possibleId);
            $stmtm->execute();
            $resm = $stmtm->get_result();
            if ($resm && $resm->num_rows > 0) {
                $mrow = $resm->fetch_assoc();
                $storedEmail = isset($mrow['email']) ? strtolower(trim($mrow['email'])) : '';
                if ($storedEmail && strtolower(trim($email)) === $storedEmail) {
                    // Map mass row to a response compatible with sacrament booking UI
                    $booking = [
                        'booking_id' => $mrow['id'],
                        'sacrament_type' => 'mass',
                        'status' => $mrow['status'] ?? 'pending',
                        'mass_details' => $mrow
                    ];
                    echo json_encode(["success" => true, "booking" => $booking]);
                    $conn->close();
                    exit();
                } else {
                    http_response_code(404);
                    echo json_encode([
                        "success" => false,
                        "message" => "Email address doesn't match the mass request.",
                        "debug_info" => [
                            "provided_email" => $email,
                            "stored_email" => $mrow['email'] ?? null
                        ]
                    ]);
                    $conn->close();
                    exit();
                }
            }
        }
    }

    http_response_code(404);
    echo json_encode(["success" => false, "message" => "Booking not found"]);
    $conn->close();
    exit();
}

$booking = $result->fetch_assoc();
$s_type = strtolower(trim($booking['sacrament_type'] ?? ''));

// Determine child table and email columns to check based on sacrament type
$childTable = '';
$emailColumns = [];
if ($s_type === 'wedding') {
    $childTable = 'wedding_bookings';
    $emailColumns = ['groom_email'];
} elseif ($s_type === 'baptism') {
    $childTable = 'baptism_bookings';
    $emailColumns = ['parent1_email', 'parent2_email'];
} elseif ($s_type === 'funeral') {
    $childTable = 'funeral_bookings';
    $emailColumns = ['requestor_email'];
}

$storedEmails = [];
$rowFound = false;
$detectedType = $s_type;

$tryChildLookup = function($table, $columns) use ($conn, $booking_id, &$storedEmails, &$rowFound) {
    $columnsList = implode(', ', $columns);
    $sql = "SELECT $columnsList FROM $table WHERE booking_id = ? LIMIT 1";
    $stmt2 = $conn->prepare($sql);
    if (!$stmt2) {
        return false;
    }
    $stmt2->bind_param("s", $booking_id);
    $stmt2->execute();
    $res2 = $stmt2->get_result();
    if ($res2 && $res2->num_rows > 0) {
        $rowFound = true;
        $row2 = $res2->fetch_assoc();
        foreach ($columns as $column) {
            if (isset($row2[$column]) && $row2[$column] !== '') {
                $storedEmails[$column] = $row2[$column];
            }
        }
        return true;
    }
    return false;
};

if ($childTable && !empty($emailColumns)) {
    $tryChildLookup($childTable, $emailColumns);
}

// Fallback: if the declared sacrament type is invalid or the record is missing, search all child tables
if (!$rowFound) {
    $fallbackMappings = [
        'wedding' => ['table' => 'wedding_bookings', 'columns' => ['groom_email']],
        'baptism' => ['table' => 'baptism_bookings', 'columns' => ['parent1_email', 'parent2_email']],
        'funeral' => ['table' => 'funeral_bookings', 'columns' => ['requestor_email']],
    ];

    foreach ($fallbackMappings as $type => $info) {
        if ($type === $s_type) {
            continue;
        }
        $storedEmails = [];
        if ($tryChildLookup($info['table'], $info['columns'])) {
            $detectedType = $type;
            break;
        }
    }
}

if (!$rowFound) {
    // Child record not found - booking exists but details are incomplete
    http_response_code(404);
    echo json_encode([
        "success" => false,
        "message" => "Booking found, but the booking details are incomplete. Please contact admin with your Booking ID.",
        "debug_info" => [
            "booking_id" => $booking_id,
            "sacrament_type" => $s_type,
            "child_table" => $childTable,
            "error" => "Child table record not found"
        ]
    ]);
    $conn->close();
    exit();
}

// Compare emails (case-insensitive) against all available stored email fields
$match = false;
$normalizedProvided = strtolower(trim($email));
foreach ($storedEmails as $column => $storedEmail) {
    if (strtolower(trim($storedEmail)) === $normalizedProvided) {
        $match = true;
        break;
    }
}

if ($match) {
    // Return booking details plus matched contact email
    $booking['matched_email'] = $normalizedProvided;
    $booking['contact_emails'] = $storedEmails;
    echo json_encode(["success" => true, "booking" => $booking]);
} else {
    http_response_code(404);
    echo json_encode([
        "success" => false,
        "message" => "Email address doesn't match the booking. Please verify your email and booking ID.",
        "debug_info" => [
            "provided_email" => $email,
            "stored_emails" => $storedEmails,
            "match" => $match
        ]
    ]);
}

$conn->close();
?>
