<?php
/**
 * Booking Diagnostic Tool
 * Use this to identify incomplete booking records
 */

header('Content-Type: application/json');

$conn = new mysqli("localhost", "root", "", "sjb_databases");

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["error" => "Database connection failed: " . $conn->connect_error]);
    exit();
}

$action = $_GET['action'] ?? $_POST['action'] ?? 'list';

if ($action === 'list') {
    // List all bookings with their child record status
    $sql = "SELECT sb.id, sb.booking_id, sb.sacrament_type, sb.status, sb.created_at,
            CASE 
                WHEN sb.sacrament_type = 'wedding' THEN EXISTS(SELECT 1 FROM wedding_bookings WHERE booking_id = sb.booking_id)
                WHEN sb.sacrament_type = 'baptism' THEN EXISTS(SELECT 1 FROM baptism_bookings WHERE booking_id = sb.booking_id)
                WHEN sb.sacrament_type = 'funeral' THEN EXISTS(SELECT 1 FROM funeral_bookings WHERE booking_id = sb.booking_id)
            END as child_exists
            FROM sacrament_bookings sb
            ORDER BY sb.created_at DESC
            LIMIT 50";
    
    $result = $conn->query($sql);
    $bookings = [];
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $bookings[] = [
                'booking_id' => $row['booking_id'],
                'sacrament_type' => $row['sacrament_type'],
                'status' => $row['status'],
                'child_exists' => (bool)$row['child_exists'],
                'created_at' => $row['created_at'],
                'is_incomplete' => !$row['child_exists']
            ];
        }
    }
    
    echo json_encode([
        "success" => true,
        "total" => count($bookings),
        "incomplete_count" => count(array_filter($bookings, fn($b) => !$b['child_exists'])),
        "bookings" => $bookings
    ]);

} elseif ($action === 'detail') {
    // Get detailed info about a specific booking
    $booking_id = $_GET['booking_id'] ?? $_POST['booking_id'] ?? null;
    
    if (!$booking_id) {
        http_response_code(400);
        echo json_encode(["error" => "booking_id parameter required"]);
        exit();
    }
    
    // Get main booking
    $stmt = $conn->prepare("SELECT * FROM sacrament_bookings WHERE booking_id = ?");
    $stmt->bind_param("s", $booking_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        http_response_code(404);
        echo json_encode(["error" => "Booking not found"]);
        exit();
    }
    
    $booking = $result->fetch_assoc();
    $type = $booking['sacrament_type'];
    $response = ['main_booking' => $booking];
    
    // Get child booking
    if ($type === 'wedding') {
        $stmt = $conn->prepare("SELECT * FROM wedding_bookings WHERE booking_id = ?");
        $stmt->bind_param("s", $booking_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $response['child_booking'] = $result->num_rows > 0 ? $result->fetch_assoc() : null;
        $response['child_table'] = 'wedding_bookings';
    } elseif ($type === 'baptism') {
        $stmt = $conn->prepare("SELECT * FROM baptism_bookings WHERE booking_id = ?");
        $stmt->bind_param("s", $booking_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $response['child_booking'] = $result->num_rows > 0 ? $result->fetch_assoc() : null;
        $response['child_table'] = 'baptism_bookings';
    } elseif ($type === 'funeral') {
        $stmt = $conn->prepare("SELECT * FROM funeral_bookings WHERE booking_id = ?");
        $stmt->bind_param("s", $booking_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $response['child_booking'] = $result->num_rows > 0 ? $result->fetch_assoc() : null;
        $response['child_table'] = 'funeral_bookings';
    }
    
    $response['is_complete'] = $response['child_booking'] !== null;
    echo json_encode($response);

} elseif ($action === 'fix') {
    // Attempt to identify and report orphaned records
    $sql = "
    SELECT sb.booking_id, sb.sacrament_type
    FROM sacrament_bookings sb
    WHERE NOT EXISTS (
        CASE 
            WHEN sb.sacrament_type = 'wedding' THEN 
                (SELECT 1 FROM wedding_bookings wb WHERE wb.booking_id = sb.booking_id)
            WHEN sb.sacrament_type = 'baptism' THEN 
                (SELECT 1 FROM baptism_bookings bb WHERE bb.booking_id = sb.booking_id)
            WHEN sb.sacrament_type = 'funeral' THEN 
                (SELECT 1 FROM funeral_bookings fb WHERE fb.booking_id = sb.booking_id)
        END
    )
    ";
    
    $result = $conn->query($sql);
    $orphaned = [];
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $orphaned[] = $row;
        }
    }
    
    echo json_encode([
        "success" => true,
        "orphaned_count" => count($orphaned),
        "orphaned_bookings" => $orphaned,
        "message" => "These bookings exist in main table but are missing child records. They will fail status checks."
    ]);

} else {
    http_response_code(400);
    echo json_encode(["error" => "Unknown action. Use: list, detail, or fix"]);
}

$conn->close();
?>
