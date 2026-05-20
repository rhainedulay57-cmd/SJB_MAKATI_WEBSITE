<?php
/**
 * Admin Sacrament Booking Management
 * Handles admin approval/decline of bookings
 */

session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: sjb-login-form.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "sjb_databases");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? null;
    $booking_id = $_POST['booking_id'] ?? null;

    if (!$action || !$booking_id) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Invalid request"]);
        exit();
    }

    header('Content-Type: application/json');

    try {
        if ($action === 'approve') {
            approveBooking($conn, $booking_id, $_POST['admin_notes'] ?? '');
        } elseif ($action === 'decline') {
            declineBooking($conn, $booking_id, $_POST['decline_reason'] ?? '');
        } else {
            throw new Exception("Invalid action");
        }

        echo json_encode(["success" => true, "message" => "Action completed successfully"]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["success" => false, "message" => $e->getMessage()]);
    }
    exit();
}

// Fetch bookings with filters
$sacrament_type = $_GET['type'] ?? 'all';
$status = $_GET['status'] ?? 'pending';

$bookings = [];
$pending_count = 0;
$approved_count = 0;
$declined_count = 0;

// Get all pending bookings
$sql = "SELECT * FROM sacrament_bookings WHERE status = 'pending' ORDER BY created_at DESC";
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $bookings[] = $row;
        $pending_count++;
    }
}

// Count approved
$sql = "SELECT COUNT(*) as count FROM sacrament_bookings WHERE status = 'approved'";
$result = $conn->query($sql);
if ($row = $result->fetch_assoc()) {
    $approved_count = $row['count'];
}

// Count declined
$sql = "SELECT COUNT(*) as count FROM sacrament_bookings WHERE status = 'declined'";
$result = $conn->query($sql);
if ($row = $result->fetch_assoc()) {
    $declined_count = $row['count'];
}

/**
 * Approve Booking
 */
function approveBooking($conn, $booking_id, $admin_notes) {
    $stmt = $conn->prepare("UPDATE sacrament_bookings SET status = 'approved', admin_notes = ? WHERE booking_id = ?");
    $stmt->bind_param("ss", $admin_notes, $booking_id);

    if (!$stmt->execute()) {
        throw new Exception("Error approving booking: " . $stmt->error);
    }

    // Get booking details to send email
    $booking = getBookingDetails($conn, $booking_id);
    if ($booking) {
        sendApprovalEmail($booking, $admin_notes);
    }
}

/**
 * Decline Booking
 */
function declineBooking($conn, $booking_id, $decline_reason) {
    $stmt = $conn->prepare("UPDATE sacrament_bookings SET status = 'declined', admin_notes = ? WHERE booking_id = ?");
    $stmt->bind_param("ss", $decline_reason, $booking_id);

    if (!$stmt->execute()) {
        throw new Exception("Error declining booking: " . $stmt->error);
    }

    // Get booking details to send email
    $booking = getBookingDetails($conn, $booking_id);
    if ($booking) {
        sendDeclineEmail($booking, $decline_reason);
    }
}

/**
 * Get Full Booking Details
 */
function getBookingDetails($conn, $booking_id) {
    $sql = "SELECT sb.*, 
            wb.groom_email as email, wb.groom_name as contact_name,
            bb.parent1_email as email2, bb.parent1_name as contact_name2,
            fb.requestor_email as email3, fb.requestor_name as contact_name3
            FROM sacrament_bookings sb
            LEFT JOIN wedding_bookings wb ON sb.booking_id = wb.booking_id
            LEFT JOIN baptism_bookings bb ON sb.booking_id = bb.booking_id
            LEFT JOIN funeral_bookings fb ON sb.booking_id = fb.booking_id
            WHERE sb.booking_id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $booking_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

/**
 * Send Approval Email
 */
function sendApprovalEmail($booking, $admin_notes) {
    $email = $booking['email'] ?? $booking['email2'] ?? $booking['email3'];
    if (!$email) return;

    $subject = "Booking Approved - St. John Bosco Parish";
    $sacrament_label = ucfirst($booking['sacrament_type']);

    $message = "
    <html>
        <head>
            <title>Booking Approved</title>
            <style>
                body { font-family: Arial, sans-serif; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; }
                .header { background-color: #27ae60; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; }
                .status { background-color: #d5f4e6; padding: 15px; border-left: 4px solid #27ae60; margin: 20px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>✓ Booking Approved</h2>
                </div>
                <div class='content'>
                    <p>Dear Parishioner,</p>
                    <p>Great news! Your <strong>{$sacrament_label}</strong> booking has been <strong>APPROVED</strong>.</p>
                    
                    <div class='status'>
                        <strong>Booking ID:</strong> {$booking['booking_id']}<br>
                        <strong>Status:</strong> APPROVED<br>
                        <strong>Admin Notes:</strong> " . htmlspecialchars($admin_notes) . "
                    </div>

                    <p>Our parish secretary will contact you shortly to finalize the details and answer any questions you may have.</p>
                    <p>Thank you for choosing St. John Bosco Parish!</p>
                </div>
            </div>
        </body>
    </html>
    ";

    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: bookings@sjbmakati.org\r\n";

    // mail($email, $subject, $message, $headers);
}

/**
 * Send Decline Email
 */
function sendDeclineEmail($booking, $decline_reason) {
    $email = $booking['email'] ?? $booking['email2'] ?? $booking['email3'];
    if (!$email) return;

    $subject = "Booking Status Update - St. John Bosco Parish";
    $sacrament_label = ucfirst($booking['sacrament_type']);

    $message = "
    <html>
        <head>
            <title>Booking Status</title>
            <style>
                body { font-family: Arial, sans-serif; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; }
                .header { background-color: #e74c3c; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; }
                .status { background-color: #fadbd8; padding: 15px; border-left: 4px solid #e74c3c; margin: 20px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>Booking Status Update</h2>
                </div>
                <div class='content'>
                    <p>Dear Parishioner,</p>
                    <p>Thank you for submitting your <strong>{$sacrament_label}</strong> booking request.</p>
                    
                    <div class='status'>
                        <strong>Booking ID:</strong> {$booking['booking_id']}<br>
                        <strong>Status:</strong> Unable to Process<br>
                        <strong>Reason:</strong> " . htmlspecialchars($decline_reason) . "
                    </div>

                    <p>Please contact our parish office for more information or to discuss alternative dates/arrangements.</p>
                    <p>Contact: (02) 1234-5678 or bookings@sjbmakati.org</p>
                </div>
            </div>
        </body>
    </html>
    ";

    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: bookings@sjbmakati.org\r\n";

    // mail($email, $subject, $message, $headers);
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../CSS/sjb-global.css">
    <link rel="stylesheet" href="../CSS/sjb-admin-bookings.css">
    <title>Manage Sacrament Bookings | Admin Panel</title>
</head>
<body>
    <header>
        <img src="../IMAGES/sjb-logo.png" alt="logo">
        <nav class="navbar">
            <ul>
                <li><a href="sjb-admin.php">← Back to Admin</a></li>
                <li class="sign-in-btn">
                    <span class="admin-label">Admin</span>
                    <a href="sjb-logout.php" title="Logout" class="logout-btn">Logout</a>
                </li>
            </ul>
        </nav>
    </header>

    <main class="admin-container">
        <h1>💍 Sacrament Booking Management</h1>

        <!-- STATS -->
        <div class="stats-container">
            <div class="stat-card pending">
                <span class="stat-number"><?php echo $pending_count; ?></span>
                <span class="stat-label">Pending</span>
            </div>
            <div class="stat-card approved">
                <span class="stat-number"><?php echo $approved_count; ?></span>
                <span class="stat-label">Approved</span>
            </div>
            <div class="stat-card declined">
                <span class="stat-number"><?php echo $declined_count; ?></span>
                <span class="stat-label">Declined</span>
            </div>
        </div>

        <!-- PENDING BOOKINGS -->
        <section class="admin-section">
            <h2>⏳ Pending Bookings</h2>
            
            <?php if (count($bookings) > 0): ?>
                <table class="bookings-table">
                    <thead>
                        <tr>
                            <th>Booking ID</th>
                            <th>Type</th>
                            <th>Contact Name</th>
                            <th>Email</th>
                            <th>Submitted</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bookings as $booking): ?>
                            <tr onclick="viewBookingDetails('<?php echo htmlspecialchars($booking['booking_id']); ?>', '<?php echo htmlspecialchars($booking['sacrament_type']); ?>')">
                                <td><strong><?php echo htmlspecialchars($booking['booking_id']); ?></strong></td>
                                <td>
                                    <span class="badge badge-<?php echo $booking['sacrament_type']; ?>">
                                        <?php 
                                            $icon = match($booking['sacrament_type']) {
                                                'wedding' => '💍',
                                                'baptism' => '👶',
                                                'funeral' => '🙏',
                                                default => '📋'
                                            };
                                            echo $icon . ' ' . ucfirst($booking['sacrament_type']);
                                        ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars(substr($booking['sacrament_type'] === 'wedding' ? 'Groom' : 'Requestor', 0, 20)); ?></td>
                                <td><small>View details</small></td>
                                <td><?php echo date('M d, Y', strtotime($booking['created_at'])); ?></td>
                                <td onclick="event.stopPropagation();">
                                    <button class="btn-approve" onclick="approveBooking('<?php echo htmlspecialchars($booking['booking_id']); ?>')">✓ Approve</button>
                                    <button class="btn-decline" onclick="declineBooking('<?php echo htmlspecialchars($booking['booking_id']); ?>')">✗ Decline</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="no-data">✓ No pending bookings. Great job!</p>
            <?php endif; ?>
        </section>
    </main>

    <!-- MODAL FOR DETAILS -->
    <div id="detailsModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <div id="modalBody"></div>
        </div>
    </div>

    <!-- MODAL FOR APPROVAL -->
    <div id="approvalModal" class="modal">
        <div class="modal-content form-modal">
            <span class="close" onclick="closeApprovalModal()">&times;</span>
            <h2>✓ Approve Booking</h2>
            <form id="approvalForm">
                <input type="hidden" id="booking_id_hidden" name="booking_id">
                <div class="form-group">
                    <label for="admin_notes">Admin Notes (Optional)</label>
                    <textarea id="admin_notes" name="admin_notes" rows="4" placeholder="Add any notes for the parishioner..."></textarea>
                </div>
                <button type="submit" class="btn-approve">Approve Booking</button>
                <button type="button" class="btn-cancel" onclick="closeApprovalModal()">Cancel</button>
            </form>
        </div>
    </div>

    <!-- MODAL FOR DECLINE -->
    <div id="declineModal" class="modal">
        <div class="modal-content form-modal">
            <span class="close" onclick="closeDeclineModal()">&times;</span>
            <h2>✗ Decline Booking</h2>
            <form id="declineForm">
                <input type="hidden" id="booking_id_hidden2" name="booking_id">
                <div class="form-group">
                    <label for="decline_reason">Reason for Decline *</label>
                    <textarea id="decline_reason" name="decline_reason" rows="4" placeholder="Please provide a reason..." required></textarea>
                </div>
                <button type="submit" class="btn-decline">Decline Booking</button>
                <button type="button" class="btn-cancel" onclick="closeDeclineModal()">Cancel</button>
            </form>
        </div>
    </div>

    <script>
        function viewBookingDetails(bookingId, sacramentType) {
            // Fetch booking details via AJAX
            fetch(`get-sacrament-details.php?booking_id=${bookingId}&type=${sacramentType}`)
                .then(response => response.html())
                .then(html => {
                    document.getElementById('modalBody').innerHTML = html;
                    document.getElementById('detailsModal').style.display = 'block';
                })
                .catch(error => console.error('Error:', error));
        }

        function approveBooking(bookingId) {
            document.getElementById('booking_id_hidden').value = bookingId;
            document.getElementById('approvalModal').style.display = 'block';
        }

        function declineBooking(bookingId) {
            document.getElementById('booking_id_hidden2').value = bookingId;
            document.getElementById('declineModal').style.display = 'block';
        }

        function closeModal() {
            document.getElementById('detailsModal').style.display = 'none';
        }

        function closeApprovalModal() {
            document.getElementById('approvalModal').style.display = 'none';
        }

        function closeDeclineModal() {
            document.getElementById('declineModal').style.display = 'none';
        }

        // Handle approval form submission
        document.getElementById('approvalForm')?.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            formData.append('action', 'approve');

            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Booking approved successfully!');
                    closeApprovalModal();
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => console.error('Error:', error));
        });

        // Handle decline form submission
        document.getElementById('declineForm')?.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            formData.append('action', 'decline');

            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Booking declined successfully!');
                    closeDeclineModal();
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => console.error('Error:', error));
        });
    </script>
</body>
</html>
