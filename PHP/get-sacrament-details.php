<?php
/**
 * Get Sacrament Booking Details
 */

$conn = new mysqli("localhost", "root", "", "sjb_databases");

if ($conn->connect_error) {
    die("Connection failed");
}

$booking_id = $_GET['booking_id'] ?? null;
$type = $_GET['type'] ?? null;

if (!$booking_id || !$type) {
    die("Invalid request");
}

header('Content-Type: text/html; charset=utf-8');

/**
 * Helper function to generate file display HTML
 */
function displayFileLink($file_path, $label) {
    if (!$file_path) {
        return "<p style='color: #999;'>No file uploaded</p>";
    }
    
    $full_path = dirname(__DIR__) . '/' . $file_path;
    $file_exists = file_exists($full_path);
    $file_name = basename($file_path);
    $file_icon = getFileIcon($file_path);
    
    if ($file_exists) {
        $file_size = filesize($full_path);
        $file_size_display = formatFileSize($file_size);
        return "
        <div style='background: #f8f9fa; padding: 10px; border-radius: 4px; margin: 8px 0; border-left: 4px solid #3498db;'>
            <p style='margin: 0 0 8px 0;'>
                <strong>$label</strong><br>
                <span style='color: #666; font-size: 0.9em;'>$file_name ($file_size_display)</span>
            </p>
            <p style='margin: 0;'>
                <a href='download-file.php?file=" . urlencode($file_path) . "' style='display: inline-block; padding: 6px 12px; background: #3498db; color: white; text-decoration: none; border-radius: 3px; margin-right: 8px; font-size: 0.9em;'>Download</a>
                <a href='../". htmlspecialchars($file_path) . "' target='_blank' style='display: inline-block; padding: 6px 12px; background: #27ae60; color: white; text-decoration: none; border-radius: 3px; font-size: 0.9em;'>View</a>
            </p>
        </div>";
    } else {
        return "<p style='color: #e74c3c;'>File not found: $file_name</p>";
    }
}

/**
 * Get file icon based on extension
 */
function getFileIcon($file_path) {
    $ext = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
    switch ($ext) {
        case 'pdf':
            return 'PDF';
        case 'jpg':
        case 'jpeg':
        case 'png':
        case 'gif':
            return 'Image';
        case 'doc':
        case 'docx':
            return 'Document';
        default:
            return 'File';
    }
}

/**
 * Format file size for display
 */
function formatFileSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= (1 << (10 * $pow));
    return round($bytes, 2) . ' ' . $units[$pow];
}

/**
 * Render one or multiple files. Accepts a JSON array or single path.
 */
function displayFiles($file_field, $label) {
    if (!$file_field) {
        return "<p style='color: #999;'>No file uploaded</p>";
    }

    $decoded = json_decode($file_field, true);
    if (is_array($decoded)) {
        $out = "";
        foreach ($decoded as $i => $p) {
            $out .= displayFileLink($p, $label . ' - part ' . ($i + 1));
        }
        return $out;
    }

    return displayFileLink($file_field, $label);
}

if ($type === 'wedding') {
    $sql = "SELECT * FROM wedding_bookings WHERE booking_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $booking_id);
    $stmt->execute();
    $booking = $stmt->get_result()->fetch_assoc();

    if ($booking) {
        echo "
        <h2>Wedding Booking Details</h2>
        <div class='detail-section'>
            <h3>Couple Information</h3>
            <table class='detail-table'>
                <tr>
                    <td><strong>Groom:</strong></td>
                    <td>" . htmlspecialchars($booking['groom_name']) . "</td>
                </tr>
                <tr>
                    <td><strong>Bride:</strong></td>
                    <td>" . htmlspecialchars($booking['bride_name']) . "</td>
                </tr>
                <tr>
                    <td><strong>Email:</strong></td>
                    <td>" . htmlspecialchars($booking['groom_email']) . "</td>
                </tr>
                <tr>
                    <td><strong>Phone:</strong></td>
                    <td>" . htmlspecialchars($booking['groom_phone']) . "</td>
                </tr>
            </table>
        </div>

        <div class='detail-section'>
            <h3>Wedding Details</h3>
            <table class='detail-table'>
                <tr>
                    <td><strong>Date:</strong></td>
                    <td>" . date('F d, Y', strtotime($booking['wedding_date'])) . "</td>
                </tr>
                <tr>
                    <td><strong>Time:</strong></td>
                    <td>" . date('h:i A', strtotime($booking['wedding_time'])) . "</td>
                </tr>
                <tr>
                    <td><strong>Special Requests:</strong></td>
                    <td>" . htmlspecialchars($booking['special_requests'] ?: 'None') . "</td>
                </tr>
                <tr>
                    <td><strong>Submitted:</strong></td>
                    <td>" . date('M d, Y h:i A', strtotime($booking['created_at'])) . "</td>
                </tr>
            </table>
        </div>

        <div class='detail-section'>
            <h3>Uploaded Documents & Payment Proofs</h3>
            " . displayFiles($booking['requirements_file'] ?? null, 'Requirements Documents (Baptismal/Confirmation Certificates,etc.)') . "
            " . displayFileLink($booking['deposit_proof_file'] ?? null, 'Deposit Payment Proof (PHP 10,000)') . "
            " . displayFileLink($booking['contribution_proof_file'] ?? null, 'Contribution Payment Proof (PHP 29,500)') . "
        </div>
        ";
    }
} elseif ($type === 'baptism') {
    $sql = "SELECT * FROM baptism_bookings WHERE booking_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $booking_id);
    $stmt->execute();
    $booking = $stmt->get_result()->fetch_assoc();

    if ($booking) {
        echo "
        <h2>Baptism Booking Details</h2>
        <div class='detail-section'>
            <h3>Candidate Information</h3>
            <table class='detail-table'>
                <tr>
                    <td><strong>Type:</strong></td>
                    <td>" . htmlspecialchars($booking['baptism_type']) . "</td>
                </tr>
                <tr>
                    <td><strong>Child Name:</strong></td>
                    <td>" . htmlspecialchars($booking['candidate_name']) . "</td>
                </tr>
            </table>
        </div>

        <div class='detail-section'>
            <h3>Parent/Guardian Information</h3>
            <table class='detail-table'>
                <tr>
                    <td><strong>Father Name:</strong></td>
                    <td>" . htmlspecialchars($booking['parent1_name']) . "</td>
                </tr>
                
                <tr>
                    <td><strong>Mother Name:</strong></td>
                    <td>" . htmlspecialchars($booking['parent2_name']) . "</td>
                </tr>
                <tr>
                    <td><strong>Email:</strong></td>
                    <td>" . htmlspecialchars($booking['parent1_email']) . "</td>
                </tr>
                <tr>
                    <td><strong>Phone:</strong></td>
                    <td>" . htmlspecialchars($booking['parent1_phone']) . "</td>
                </tr>

            </table>
        </div>

        <div class='detail-section'>
            <h3>Baptism Details</h3>
            <table class='detail-table'>
                <tr>
                    <td><strong>Date:</strong></td>
                    <td>" . date('F d, Y', strtotime($booking['baptism_date'])) . "</td>
                </tr>
                <tr>
                    <td><strong>Special Requests:</strong></td>
                    <td>" . htmlspecialchars($booking['special_requests'] ?: 'None') . "</td>
                </tr>
                <tr>
                    <td><strong>Submitted:</strong></td>
                    <td>" . date('M d, Y h:i A', strtotime($booking['created_at'])) . "</td>
                </tr>
            </table>
        </div>

        <div class='detail-section'>
            <h3>Uploaded Documents & Payment Proofs</h3>
            " . displayFileLink($booking['birth_certificate_file'] ?? null, 'Birth Certificate') . "
            " . displayFileLink($booking['confirmation_certificate_file'] ?? null, 'Godparent Confirmation Certificate') . "
            " . displayFileLink($booking['attendance_proof_file'] ?? null, 'Attendance Proof') . "
            " . displayFileLink($booking['donation_proof_file'] ?? null, 'Baptismal Fees Payment Proof') . "
        </div>
        ";
    }
} elseif ($type === 'funeral') {
    $sql = "SELECT * FROM funeral_bookings WHERE booking_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $booking_id);
    $stmt->execute();
    $booking = $stmt->get_result()->fetch_assoc();

    if ($booking) {
        echo "
        <h2>Funeral Booking Details</h2>
        <div class='alert alert-urgent'>URGENT REQUEST - Same-day response required</div>

        <div class='detail-section'>
            <h3>Deceased Information</h3>
            <table class='detail-table'>
                <tr>
                    <td><strong>Name:</strong></td>
                    <td>" . htmlspecialchars($booking['deceased_name']) . "</td>
                </tr>
                <tr>
                    <td><strong>Date of Death:</strong></td>
                    <td>" . date('F d, Y', strtotime($booking['death_date'])) . "</td>
                </tr>
            </table>
        </div>

        <div class='detail-section'>
            <h3>Requestor Information</h3>
            <table class='detail-table'>
                <tr>
                    <td><strong>Name:</strong></td>
                    <td>" . htmlspecialchars($booking['requestor_name']) . "</td>
                </tr>
                <tr>
                    <td><strong>Relationship:</strong></td>
                    <td>" . htmlspecialchars($booking['relationship']) . "</td>
                </tr>
                <tr>
                    <td><strong>Email:</strong></td>
                    <td>" . htmlspecialchars($booking['requestor_email']) . "</td>
                </tr>
                <tr>
                    <td><strong>Phone:</strong></td>
                    <td>" . htmlspecialchars($booking['requestor_phone']) . "</td>
                </tr>
            </table>
        </div>

        <div class='detail-section'>
            <h3>Funeral Details</h3>
            <table class='detail-table'>
                <tr>
                    <td><strong>Preferred Chapel:</strong></td>
                    <td>" . htmlspecialchars($booking['funeral_chapel'] ?? 'Not specified') . "</td>
                </tr>
                <tr>
                    <td><strong>Funeral Mass Required:</strong></td>
                    <td>" . htmlspecialchars($booking['needs_funeral_mass'] ?? 'Not specified') . "</td>
                </tr>
                <tr>
                    <td><strong>Date:</strong></td>
                    <td>" . date('F d, Y', strtotime($booking['funeral_date'])) . "</td>
                </tr>
                <tr>
                    <td><strong>Time:</strong></td>
                    <td>" . date('h:i A', strtotime($booking['funeral_time'])) . "</td>
                </tr>
                <tr>
                    <td><strong>Special Requests:</strong></td>
                    <td>" . htmlspecialchars($booking['special_requests'] ?: 'None') . "</td>
                </tr>
                <tr>
                    <td><strong>Submitted:</strong></td>
                    <td>" . date('M d, Y h:i A', strtotime($booking['created_at'])) . "</td>
                </tr>
                <tr>
                    <td><strong>Payment Proof:</strong></td>
                    <td>" . displayFiles($booking['funeral_payment_proof_file'] ?? null, 'Payment Proof') . "</td>
                </tr>
            </table>
        </div>
        </div>

        ";
    }
}

$conn->close();
?>