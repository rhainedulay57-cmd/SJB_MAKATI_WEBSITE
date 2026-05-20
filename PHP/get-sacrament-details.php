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

if ($type === 'wedding') {
    $sql = "SELECT * FROM wedding_bookings WHERE booking_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $booking_id);
    $stmt->execute();
    $booking = $stmt->get_result()->fetch_assoc();

    if ($booking) {
        echo "
        <h2>💍 Wedding Booking Details</h2>
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
                    <td><strong>Guest Count:</strong></td>
                    <td>" . htmlspecialchars($booking['guest_count']) . " guests</td>
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
        <h2>👶 Baptism Booking Details</h2>
        <div class='detail-section'>
            <h3>Candidate Information</h3>
            <table class='detail-table'>
                <tr>
                    <td><strong>Type:</strong></td>
                    <td>" . htmlspecialchars($booking['baptism_type']) . "</td>
                </tr>
                <tr>
                    <td><strong>Candidate Name:</strong></td>
                    <td>" . htmlspecialchars($booking['candidate_name']) . "</td>
                </tr>
            </table>
        </div>

        <div class='detail-section'>
            <h3>Parent/Guardian Information</h3>
            <table class='detail-table'>
                <tr>
                    <td><strong>Parent 1 Name:</strong></td>
                    <td>" . htmlspecialchars($booking['parent1_name']) . "</td>
                </tr>
                <tr>
                    <td><strong>Email:</strong></td>
                    <td>" . htmlspecialchars($booking['parent1_email']) . "</td>
                </tr>
                <tr>
                    <td><strong>Phone:</strong></td>
                    <td>" . htmlspecialchars($booking['parent1_phone']) . "</td>
                </tr>
                " . ($booking['parent2_name'] ? "
                <tr>
                    <td><strong>Parent 2 Name:</strong></td>
                    <td>" . htmlspecialchars($booking['parent2_name']) . "</td>
                </tr>
                <tr>
                    <td><strong>Email:</strong></td>
                    <td>" . htmlspecialchars($booking['parent2_email']) . "</td>
                </tr>
                <tr>
                    <td><strong>Phone:</strong></td>
                    <td>" . htmlspecialchars($booking['parent2_phone']) . "</td>
                </tr>
                " : "") . "
            </table>
        </div>

        <div class='detail-section'>
            <h3>Godparent/Sponsor</h3>
            <table class='detail-table'>
                <tr>
                    <td><strong>Name:</strong></td>
                    <td>" . htmlspecialchars($booking['godparent_name']) . "</td>
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
        <h2>🙏 Funeral Mass Booking Details</h2>
        <div class='alert alert-urgent'>⏰ URGENT REQUEST - Same-day response required</div>

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
                    <td><strong>Date:</strong></td>
                    <td>" . date('F d, Y', strtotime($booking['funeral_date'])) . "</td>
                </tr>
                <tr>
                    <td><strong>Time:</strong></td>
                    <td>" . date('h:i A', strtotime($booking['funeral_time'])) . "</td>
                </tr>
                <tr>
                    <td><strong>Expected Attendees:</strong></td>
                    <td>" . htmlspecialchars($booking['funeral_attendees']) . "</td>
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
        ";
    }
}

$conn->close();
?>
