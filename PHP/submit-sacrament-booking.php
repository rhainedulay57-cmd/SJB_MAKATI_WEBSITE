<?php
/**
 * Sacrament Booking Submission Handler
 * Processes bookings for weddings, baptisms, and funerals
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

$sacrament_type = isset($_POST['sacrament_type']) ? $_POST['sacrament_type'] : null;

if (!$sacrament_type || !in_array($sacrament_type, ['wedding', 'baptism', 'funeral'])) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Invalid sacrament type"]);
    exit();
}

// Generate unique booking ID
function generateBookingId($type) {
    // Use switch for broader PHP compatibility (avoids PHP 8+ `match` syntax)
    switch ($type) {
        case 'wedding':
            $prefix = 'WED';
            break;
        case 'baptism':
            $prefix = 'BAP';
            break;
        case 'funeral':
            $prefix = 'FUN';
            break;
        default:
            $prefix = 'SAC';
    }
    $randomNum = str_pad(rand(1000, 9999), 4, '0', STR_PAD_LEFT);
    $year = date('Y');
    return "{$prefix}-{$randomNum}-{$year}";
}

try {
    $conn->begin_transaction();

    // Generate booking ID
    $booking_id = generateBookingId($sacrament_type);

    // Create main booking record
    $stmt = $conn->prepare("INSERT INTO sacrament_bookings (booking_id, sacrament_type, status) VALUES (?, ?, 'pending')");
    $stmt->bind_param("ss", $booking_id, $sacrament_type);
    $stmt->execute();

    if ($sacrament_type === 'wedding') {
        handleWeddingBooking($conn, $booking_id, $_POST);
    } elseif ($sacrament_type === 'baptism') {
        handleBaptismBooking($conn, $booking_id, $_POST);
    } elseif ($sacrament_type === 'funeral') {
        handleFuneralBooking($conn, $booking_id, $_POST);
    }

    $conn->commit();

    // Send confirmation email
    $email = $_POST['groom_email'] ?? $_POST['parent1_email'] ?? $_POST['requestor_email'];
    if ($email) {
        sendConfirmationEmail($email, $booking_id, $sacrament_type);
    }

    echo json_encode([
        "success" => true,
        "message" => "Booking submitted successfully!",
        "booking_id" => $booking_id
    ]);

} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Error processing booking: " . $e->getMessage()]);
}

$conn->close();

/**
 * Handle Wedding Booking
 */
function handleWeddingBooking($conn, $booking_id, $data) {
    $groom_name = $data['groom_name'] ?? '';
    $bride_name = $data['bride_name'] ?? '';
    $groom_email = $data['groom_email'] ?? '';
    $groom_phone = $data['groom_phone'] ?? '';
    $wedding_date = $data['wedding_date'] ?? null;
    $wedding_time = $data['wedding_time'] ?? null;
    $guest_count = isset($data['guest_count']) ? (int)$data['guest_count'] : 0;
    $special_requests = $data['special_requests'] ?? '';

    $stmt = $conn->prepare(
        "INSERT INTO wedding_bookings (
            booking_id, groom_name, bride_name, groom_email, groom_phone,
            wedding_date, wedding_time, guest_count, special_requests, status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')"
    );

    $stmt->bind_param(
        "sssssssis",
        $booking_id,
        $groom_name,
        $bride_name,
        $groom_email,
        $groom_phone,
        $wedding_date,
        $wedding_time,
        $guest_count,
        $special_requests
    );

    if (!$stmt->execute()) {
        throw new Exception($stmt->error);
    }
}

/**
 * Handle Baptism Booking
 */
function handleBaptismBooking($conn, $booking_id, $data) {
    $baptism_type = $data['baptism_type'] ?? '';
    $candidate_name = $data['candidate_name'] ?? '';
    $parent1_name = $data['parent1_name'] ?? '';
    $parent1_email = $data['parent1_email'] ?? '';
    $parent1_phone = $data['parent1_phone'] ?? '';
    $parent2_name = $data['parent2_name'] ?? '';
    $parent2_email = $data['parent2_email'] ?? '';
    $parent2_phone = $data['parent2_phone'] ?? '';
    $godparent_name = $data['godparent_name'] ?? '';
    $baptism_date = $data['baptism_date'] ?? null;
    $special_requests_bap = $data['special_requests_bap'] ?? '';

    $stmt = $conn->prepare(
        "INSERT INTO baptism_bookings (
            booking_id, baptism_type, candidate_name, parent1_name, parent1_email,
            parent1_phone, parent2_name, parent2_email, parent2_phone,
            godparent_name, baptism_date, special_requests, status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')"
    );

    $stmt->bind_param(
        "ssssssssssss",
        $booking_id,
        $baptism_type,
        $candidate_name,
        $parent1_name,
        $parent1_email,
        $parent1_phone,
        $parent2_name,
        $parent2_email,
        $parent2_phone,
        $godparent_name,
        $baptism_date,
        $special_requests_bap
    );

    if (!$stmt->execute()) {
        throw new Exception($stmt->error);
    }
}

/**
 * Handle Funeral Booking
 */
function handleFuneralBooking($conn, $booking_id, $data) {
    $is_urgent = true; // Funerals are always urgent

    $deceased_name = $data['deceased_name'] ?? '';
    $death_date = $data['death_date'] ?? null;
    $requestor_name = $data['requestor_name'] ?? '';
    $relationship = $data['relationship'] ?? '';
    $requestor_email = $data['requestor_email'] ?? '';
    $requestor_phone = $data['requestor_phone'] ?? '';
    $funeral_date = $data['funeral_date'] ?? null;
    $funeral_time = $data['funeral_time'] ?? null;
    $funeral_attendees = isset($data['funeral_attendees']) ? (int)$data['funeral_attendees'] : 0;
    $special_requests_fun = $data['special_requests_fun'] ?? '';
    $is_urgent = 1; // use integer for binding

    $stmt = $conn->prepare(
        "INSERT INTO funeral_bookings (
            booking_id, deceased_name, death_date, requestor_name, relationship,
            requestor_email, requestor_phone, funeral_date, funeral_time,
            funeral_attendees, special_requests, status, is_urgent
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?)"
    );

    $stmt->bind_param(
        "sssssssssisi",
        $booking_id,
        $deceased_name,
        $death_date,
        $requestor_name,
        $relationship,
        $requestor_email,
        $requestor_phone,
        $funeral_date,
        $funeral_time,
        $funeral_attendees,
        $special_requests_fun,
        $is_urgent
    );

    if (!$stmt->execute()) {
        throw new Exception($stmt->error);
    }
}

/**
 * Send Confirmation Email
 */
function sendConfirmationEmail($email, $booking_id, $sacrament_type) {
    $subject = "Booking Confirmation - St. John Bosco Parish";
    $sacrament_label = ucfirst($sacrament_type);

    $message = "
    <html>
        <head>
            <title>Booking Confirmation</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; }
                .header { background-color: #2c3e50; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; }
                .booking-id { background-color: #ecf0f1; padding: 15px; border-left: 4px solid #3498db; margin: 20px 0; }
                .footer { background-color: #34495e; color: white; padding: 20px; text-align: center; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>Booking Confirmation</h2>
                </div>
                <div class='content'>
                    <p>Dear Valued Parishioner,</p>
                    <p>Thank you for submitting your <strong>{$sacrament_label}</strong> booking request to St. John Bosco Parish.</p>
                    
                    <div class='booking-id'>
                        <strong>Your Booking ID:</strong> {$booking_id}<br>
                        <strong>Sacrament Type:</strong> {$sacrament_label}<br>
                        <strong>Status:</strong> Pending Admin Approval
                    </div>

                    <p>Our admin team will review your booking and contact you within 24 hours with approval or further requirements.</p>
                    <p>You can check your booking status anytime using your Booking ID on our website.</p>
                    
                    <p><strong>Important:</strong> Please keep this email for your records.</p>
                    
                    <p>If you have any questions, please contact us at:</p>
                    <p>
                        📞 Phone: (02) 1234-5678<br>
                        📧 Email: bookings@sjbmakati.org
                    </p>
                </div>
                <div class='footer'>
                    <p>St. John Bosco Parish, Makati City</p>
                    <p>Antonio Arnaiz Avenue corner Amorsolo Street</p>
                </div>
            </div>
        </body>
    </html>
    ";

    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8" . "\r\n";
    $headers .= "From: bookings@sjbmakati.org" . "\r\n";

    // Note: Use mail() or PHPMailer in production
    // mail($email, $subject, $message, $headers);
}
?>
