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

/**
 * Ensure a column exists in a table; if missing, add it with the provided definition.
 */
function ensureColumnExists($conn, $table, $column, $definition) {
    $db = $conn->real_escape_string($conn->query("SELECT DATABASE()")->fetch_row()[0]);
    $tableEsc = $conn->real_escape_string($table);
    $columnEsc = $conn->real_escape_string($column);
    $checkSql = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . $db . "' AND TABLE_NAME = '" . $tableEsc . "' AND COLUMN_NAME = '" . $columnEsc . "'";
    $res = $conn->query($checkSql);
    if (!$res) return; // can't check - skip
    if ($res->num_rows === 0) {
        $alterSql = "ALTER TABLE `" . $tableEsc . "` ADD COLUMN `" . $columnEsc . "` " . $definition;
        $conn->query($alterSql);
    }
}

try {
    $conn->begin_transaction();

    // Generate booking ID
    $booking_id = generateBookingId($sacrament_type);

    // Create main booking record
    $stmt = $conn->prepare("INSERT INTO sacrament_bookings (booking_id, sacrament_type, status) VALUES (?, ?, 'pending')");
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("ss", $booking_id, $sacrament_type);
    
    if (!$stmt->execute()) {
        throw new Exception("Main booking insert failed: " . $stmt->error);
    }

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
    
    // Log error for debugging
    error_log("Booking submission error for $sacrament_type: " . $e->getMessage());
    
    echo json_encode([
        "success" => false, 
        "message" => "Error processing booking: " . $e->getMessage(),
        "error_code" => $e->getCode()
    ]);
}

$conn->close();

/**
 * Handle File Upload
 */
function handleFileUpload($file_input_name, $booking_id, $file_type) {
    // Create uploads directory if it doesn't exist
    $upload_base_dir = __DIR__ . '/../UPLOADS';
    if (!is_dir($upload_base_dir)) {
        mkdir($upload_base_dir, 0755, true);
    }
    
    $booking_dir = $upload_base_dir . '/' . $booking_id;
    if (!is_dir($booking_dir)) {
        mkdir($booking_dir, 0755, true);
    }
    
    // Check if file was uploaded
    if (!isset($_FILES[$file_input_name]) || $_FILES[$file_input_name]['error'] !== UPLOAD_ERR_OK) {
        return null;
    }
    
    $file = $_FILES[$file_input_name];
    
    // Validate file extension and MIME type
    $allowed_extensions = ['pdf', 'jpg', 'jpeg', 'png', 'webp', 'gif', 'jfif', 'bmp'];
    $allowed_mime_types = [
        'application/pdf',
        'image/jpeg',
        'image/png',
        'image/webp',
        'image/gif',
        'image/bmp'
    ];
    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime_type, $allowed_mime_types)) {
        throw new Exception("Invalid file type for {$file_type}. Allowed: PDF, JPG, PNG, WEBP, GIF, BMP");
    }

    if ($file_ext && !in_array($file_ext, $allowed_extensions)) {
        // Accept files with valid MIME types even if the extension is uncommon or missing.
        $extension_map = [
            'application/pdf' => 'pdf',
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
            'image/bmp' => 'bmp'
        ];
        $file_ext = $extension_map[$mime_type] ?? $file_ext;
    }

    if ($file['size'] > 5 * 1024 * 1024) { // 5MB limit
        throw new Exception("File {$file_type} is too large. Maximum 5MB allowed.");
    }
    
    // Generate unique filename
    $filename = $file_type . '_' . time() . '.' . $file_ext;
    $file_path = $booking_dir . '/' . $filename;
    
    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $file_path)) {
        throw new Exception("Failed to upload {$file_type}");
    }
    
    // Return relative path for storage
    return 'UPLOADS/' . $booking_id . '/' . $filename;
}

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
    $special_requests = $data['special_requests'] ?? '';
    
    // Validate required fields
    if (!$groom_email) {
        throw new Exception("Wedding booking: groom_email is required");
    }
    
    // Handle file uploads for individual wedding requirement documents
    $wedding_requirements = [];
    foreach (['baptismal_confirmation', 'marriage_license', 'individual_photos', 'banns_permits', 'cenomar'] as $field) {
        try {
            $uploadedFile = handleFileUpload($field, $booking_id, $field);
            if ($uploadedFile) {
                $wedding_requirements[] = $uploadedFile;
            }
        } catch (Exception $e) {
            throw new Exception("Wedding booking: " . $e->getMessage());
        }
    }
    $requirements_file = !empty($wedding_requirements) ? json_encode($wedding_requirements) : null;
    $deposit_file = handleFileUpload('wedding_deposit_proof', $booking_id, 'deposit_proof');
    $contribution_file = handleFileUpload('wedding_contribution_proof', $booking_id, 'contribution_proof');

    $stmt = $conn->prepare(
        "INSERT INTO wedding_bookings (
            booking_id, groom_name, bride_name, groom_email, groom_phone,
            wedding_date, wedding_time, special_requests, requirements_file, deposit_proof_file, contribution_proof_file, status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')"
    );

    if (!$stmt) {
        throw new Exception("Wedding booking prepare failed: " . $conn->error);
    }

    $stmt->bind_param(
        "sssssssssss",
        $booking_id,
        $groom_name,
        $bride_name,
        $groom_email,
        $groom_phone,
        $wedding_date,
        $wedding_time,
        $special_requests,
        $requirements_file,
        $deposit_file,
        $contribution_file
    );

    if (!$stmt->execute()) {
        throw new Exception("Wedding booking insert failed: " . $stmt->error);
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
    $baptism_date = $data['baptism_date'] ?? null;
    $special_requests_bap = $data['special_requests_bap'] ?? '';
    
    // Validate required fields
    if (!$parent1_email) {
        throw new Exception("Baptism booking: parent1_email is required");
    }
    if (!$baptism_type) {
        throw new Exception("Baptism booking: baptism_type is required");
    }
    
    // Handle file uploads
    $birth_certificate_file = handleFileUpload('birth_certificate', $booking_id, 'birth_certificate');
    $confirmation_certificate_file = handleFileUpload('confirmation_certificate', $booking_id, 'confirmation_certificate');
    $attendance_proof_file = handleFileUpload('attendance_proof', $booking_id, 'attendance_proof');
    $donation_file = handleFileUpload('baptism_donation_proof', $booking_id, 'donation_proof');

    $stmt = $conn->prepare(
        "INSERT INTO baptism_bookings (
            booking_id, baptism_type, candidate_name, parent1_name, parent1_email,
            parent1_phone, parent2_name, parent2_email, parent2_phone,
            baptism_date, special_requests, birth_certificate_file, confirmation_certificate_file, attendance_proof_file, donation_proof_file, status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')"
    );

    if (!$stmt) {
        throw new Exception("Baptism booking prepare failed: " . $conn->error);
    }

    $stmt->bind_param(
        "sssssssssssssss",
        $booking_id,
        $baptism_type,
        $candidate_name,
        $parent1_name,
        $parent1_email,
        $parent1_phone,
        $parent2_name,
        $parent2_email,
        $parent2_phone,
        $baptism_date,
        $special_requests_bap,
        $birth_certificate_file,
        $confirmation_certificate_file,
        $attendance_proof_file,
        $donation_file
    );

    if (!$stmt->execute()) {
        throw new Exception("Baptism booking insert failed: " . $stmt->error);
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
    $funeral_chapel = $data['funeral_chapel'] ?? null;
    $needs_funeral_mass = $data['needs_funeral_mass'] ?? null;
    $special_requests_fun = $data['special_requests_fun'] ?? '';
    $is_urgent = 1; // use integer for binding

    // Validate required fields
    if (!$requestor_email) {
        throw new Exception("Funeral booking: requestor_email is required");
    }

    // Ensure legacy/missing columns exist in funeral_bookings
    try {
        ensureColumnExists($conn, 'funeral_bookings', 'funeral_payment_proof_file', "VARCHAR(255) NULL");
    } catch (Exception $e) {
        // Non-fatal: continue, the INSERT may still fail and be reported to caller
    }

    // Handle optional payment proof upload (from the contact page funeral form)
    $funeral_payment_proof = null;
    try {
        $funeral_payment_proof = handleFileUpload('funeral_payment_proof', $booking_id, 'funeral_payment_proof');
    } catch (Exception $e) {
        throw new Exception("Funeral booking: " . $e->getMessage());
    }

    $status = 'pending';
    $stmt = $conn->prepare(
        "INSERT INTO funeral_bookings (
            booking_id, deceased_name, death_date, requestor_name, relationship,
            requestor_email, requestor_phone, funeral_date, funeral_time,
            funeral_chapel, needs_funeral_mass, funeral_payment_proof_file, special_requests, status, is_urgent
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );

    if (!$stmt) {
        throw new Exception("Funeral booking prepare failed: " . $conn->error);
    }

    $stmt->bind_param(
        "ssssssssssssssi",
        $booking_id,
        $deceased_name,
        $death_date,
        $requestor_name,
        $relationship,
        $requestor_email,
        $requestor_phone,
        $funeral_date,
        $funeral_time,
        $funeral_chapel,
        $needs_funeral_mass,
        $funeral_payment_proof,
        $special_requests_fun,
        $status,
        $is_urgent
    );

    if (!$stmt->execute()) {
        throw new Exception("Funeral booking insert failed: " . $stmt->error);
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
