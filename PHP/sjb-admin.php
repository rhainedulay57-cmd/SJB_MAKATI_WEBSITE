<?php
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

// Fetch mass & blessing requests
$mass_requests = [];
$mass_pending_count = 0;
$mass_approved_count = 0;
$mass_declined_count = 0;
$result = $conn->query("SELECT * FROM `mass_blessing` ORDER BY id ASC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $status = isset($row['status']) ? $row['status'] : 'pending';
        $row['status'] = $status;

        if ($status === 'approved') {
            $mass_approved_count++;
        } elseif ($status === 'declined') {
            $mass_declined_count++;
        } else {
            $mass_pending_count++;
        }

        $mass_requests[] = $row;
    }
}

// Fetch contact form submissions
$contact_requests = [];
$result = $conn->query("SELECT * FROM `contact_us_form` ORDER BY id ASC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $contact_requests[] = $row;
    }
}

// --- Sacrament bookings management (merged from manage-sacrament-bookings.php)

// Handle approve/decline actions via POST (returns JSON)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? null;
    $booking_id = $_POST['booking_id'] ?? null;

    if (!$action || !$booking_id) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Invalid request"]);
        exit();
    }

    try {
        if ($action === 'approve') {
            approveBooking($conn, $booking_id, $_POST['admin_notes'] ?? '');
        } elseif ($action === 'decline') {
            declineBooking($conn, $booking_id, $_POST['decline_reason'] ?? '');
        } else {
            throw new Exception('Invalid action');
        }

        echo json_encode(["success" => true, "message" => "Action completed successfully"]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["success" => false, "message" => $e->getMessage()]);
    }
    exit();
}

// Fetch sacrament booking stats and pending list
$sacrament_type = $_GET['type'] ?? 'all';
$status = $_GET['status'] ?? 'pending';

$bookings = [];
$pending_count = 0;
$approved_count = 0;
$declined_count = 0;

$sql = "SELECT * FROM sacrament_bookings WHERE status = 'pending' ORDER BY created_at DESC";
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $bookings[] = $row;
        $pending_count++;
    }
}

$sql = "SELECT COUNT(*) as count FROM sacrament_bookings WHERE status = 'approved'";
$result = $conn->query($sql);
if ($row = $result->fetch_assoc()) {
    $approved_count = $row['count'];
}

$sql = "SELECT COUNT(*) as count FROM sacrament_bookings WHERE status = 'declined'";
$result = $conn->query($sql);
if ($row = $result->fetch_assoc()) {
    $declined_count = $row['count'];
}

// Also fetch processed bookings (approved / declined)
$processed_bookings = [];
$sql = "SELECT * FROM sacrament_bookings WHERE status != 'pending' ORDER BY updated_at DESC";
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $processed_bookings[] = $row;
    }
}

// Close connection after data collection
$conn->close();

/**
 * Approve Booking
 */
function approveBooking($conn, $booking_id, $admin_notes) {
    $stmt = $conn->prepare("UPDATE sacrament_bookings SET status = 'approved', admin_notes = ? WHERE booking_id = ?");
    $stmt->bind_param("ss", $admin_notes, $booking_id);

    if (!$stmt->execute()) {
        throw new Exception("Error approving booking: " . $stmt->error);
    }

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
 * Send Approval Email (no-op if mail is disabled)
 */
function sendApprovalEmail($booking, $admin_notes) {
    $email = $booking['email'] ?? $booking['email2'] ?? $booking['email3'];
    if (!$email) return;

    $subject = "Booking Approved - St. John Bosco Parish";
    $sacrament_label = ucfirst($booking['sacrament_type']);

    $message = "<html><body>Booking {$booking['booking_id']} approved. Notes: " . htmlspecialchars($admin_notes) . "</body></html>";
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

    $message = "<html><body>Booking {$booking['booking_id']} declined. Reason: " . htmlspecialchars($decline_reason) . "</body></html>";
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: bookings@sjbmakati.org\r\n";

    // mail($email, $subject, $message, $headers);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../CSS/sjb-global.css">
    <link rel="stylesheet" href="../CSS/sjb-admin.css">
    <link rel="stylesheet" href="../CSS/sjb-admin-bookings.css">
    <title>Admin Dashboard | St. John Bosco Parish</title>
</head>
<body>
    <header>
        <img src="../IMAGES/sjb-logo.png" alt="logo">
        <nav class="navbar">
            <ul>
                <li class="sign-in-btn">
                    <span class="admin-label">Admin</span>
                    <a href="../PHP/sjb-logout.php" title="Logout" class="logout-btn">Logout</a>
                </li>
            </ul>
        </nav>
    </header>

    <main class="admin-container">
        <h1>Admin Dashboard</h1>

        <!-- MASS & BLESSING REQUESTS -->
        <section class="admin-section">
            <h2>Mass & Blessing Requests</h2>
            <div class="stats-container" style="display:flex; gap:12px; margin-bottom:16px;">
                <div class="stat-card pending">
                    <span class="stat-number"><?php echo $mass_pending_count; ?></span>
                    <span class="stat-label">Pending</span>
                </div>
                <div class="stat-card approved">
                    <span class="stat-number"><?php echo $mass_approved_count; ?></span>
                    <span class="stat-label">Approved</span>
                </div>
                <div class="stat-card declined">
                    <span class="stat-number"><?php echo $mass_declined_count; ?></span>
                    <span class="stat-label">Declined</span>
                </div>
            </div>
            <?php if (count($mass_requests) > 0): ?>
                <table class="requests-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Service</th>
                            <th>Date Filled</th>
                            <th>Attendees</th>
                            <th>Contact Person</th>
                            <th>Email</th>
                            <th>Mobile No.</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($mass_requests as $req): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($req['id']); ?></td>
                                <td><?php echo htmlspecialchars(implode(', ', (array)$req['service'])); ?></td>
                                <td><?php echo htmlspecialchars($req['date_filled']); ?></td>
                                <td><?php echo htmlspecialchars($req['attendees']); ?></td>
                                <td><?php echo htmlspecialchars($req['contact_person']); ?></td>
                                <td><?php echo htmlspecialchars($req['email']); ?></td>
                                <td><?php echo htmlspecialchars($req['mobile_no']); ?></td>
                                <td><span class="status-badge <?php echo htmlspecialchars($req['status']); ?>"><?php echo ucfirst(htmlspecialchars($req['status'])); ?></span></td>
                                <td>
                                    <button class="btn-view" onclick="viewDetails('mass', <?php echo $req['id']; ?>)">View</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No mass or blessing requests yet.</p>
            <?php endif; ?>
        </section>

            <!-- SACRAMENT BOOKINGS -->
            <section class="admin-section">
                <h2>Sacrament Booking Management</h2>

                <div class="stats-container" style="display:flex; gap:12px; margin-bottom:16px;">
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

                <!-- Pending Bookings -->
                <h3 style="margin-top:0;">⏳ Pending Bookings</h3>
                <?php if (count($bookings) > 0): ?>
                    <table class="bookings-table">
                        <thead>
                            <tr>
                                <th>Booking ID</th>
                                <th>Type</th>
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
                                                $icon = '📋';
                                                if ($booking['sacrament_type'] === 'wedding') $icon = '💍';
                                                elseif ($booking['sacrament_type'] === 'baptism') $icon = '👶';
                                                elseif ($booking['sacrament_type'] === 'funeral') $icon = '🙏';
                                                echo $icon . ' ' . ucfirst($booking['sacrament_type']);
                                            ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($booking['created_at'])); ?></td>
                                    <td class="actions-cell" onclick="event.stopPropagation();">
                                        <div class="action-buttons">
                                            <button class="btn-approve" onclick="approveBooking('<?php echo htmlspecialchars($booking['booking_id']); ?>')">✓ Approve</button>
                                            <button class="btn-decline" onclick="declineBooking('<?php echo htmlspecialchars($booking['booking_id']); ?>')">✗ Decline</button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="no-data">✓ No pending bookings.</p>
                <?php endif; ?>

                <!-- Processed Bookings -->
                <h3 style="margin-top:32px;">📁 Processed Bookings (Approved / Declined)</h3>
                <?php if (count($processed_bookings) > 0): ?>
                    <table class="processed-table">
                        <thead>
                            <tr>
                                <th>Booking ID</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Admin Notes</th>
                                <th>Submitted</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($processed_bookings as $p): ?>
                                <tr onclick="viewBookingDetails('<?php echo htmlspecialchars($p['booking_id']); ?>', '<?php echo htmlspecialchars($p['sacrament_type']); ?>')">
                                    <td><strong><?php echo htmlspecialchars($p['booking_id']); ?></strong></td>
                                    <td><?php echo ucfirst($p['sacrament_type']); ?></td>
                                    <td><?php echo ucfirst($p['status']); ?></td>
                                    <td><?php echo htmlspecialchars($p['admin_notes'] ?? ''); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($p['created_at'])); ?></td>
                                    <td onclick="event.stopPropagation();"><button class="btn-view" onclick="viewBookingDetails('<?php echo htmlspecialchars($p['booking_id']); ?>', '<?php echo htmlspecialchars($p['sacrament_type']); ?>')">View</button></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="no-data">✓ No processed bookings yet.</p>
                <?php endif; ?>
            </section>
    </main>

    <div id="modal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <div id="modal-body"></div>
        </div>
    </div>

    <!-- SACRAMENT DETAILS + ACTION MODALS -->
    <div id="detailsModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeDetailsModal()">&times;</span>
            <div id="modalBody"></div>
        </div>
    </div>

    <div id="approvalModal" class="modal">
        <div class="modal-content form-modal">
            <span class="close" onclick="closeApprovalModal()">&times;</span>
            <h2>✓ Approve Booking</h2>
            <form id="approvalForm">
                <input type="hidden" id="booking_id_hidden" name="booking_id">
                <div class="form-group">
                    <label for="admin_notes">Admin Notes (Optional)</label>
                    <textarea id="admin_notes" name="admin_notes" rows="4" placeholder="Add any notes..."></textarea>
                </div>
                <button type="submit" class="btn-approve">Approve Booking</button>
                <button type="button" class="btn-cancel" onclick="closeApprovalModal()">Cancel</button>
            </form>
        </div>
    </div>

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
            fetch(`get-sacrament-details.php?booking_id=${bookingId}&type=${sacramentType}`)
                .then(res => res.text())
                .then(html => {
                    document.getElementById('modalBody').innerHTML = html;
                    document.getElementById('detailsModal').style.display = 'block';
                })
                .catch(err => console.error('Error:', err));
        }

        function closeDetailsModal() { document.getElementById('detailsModal').style.display = 'none'; }
        function closeApprovalModal() { document.getElementById('approvalModal').style.display = 'none'; }
        function closeDeclineModal() { document.getElementById('declineModal').style.display = 'none'; }

        function approveBooking(bookingId) {
            document.getElementById('booking_id_hidden').value = bookingId;
            document.getElementById('approvalModal').style.display = 'block';
        }

        function declineBooking(bookingId) {
            document.getElementById('booking_id_hidden2').value = bookingId;
            document.getElementById('declineModal').style.display = 'block';
        }

        document.getElementById('approvalForm')?.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            formData.append('action', 'approve');

            fetch('', { method: 'POST', body: formData })
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
            .catch(err => console.error('Error:', err));
        });

        document.getElementById('declineForm')?.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            formData.append('action', 'decline');

            fetch('', { method: 'POST', body: formData })
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
            .catch(err => console.error('Error:', err));
        });
    </script>

<footer class="footer">

    <div class="footer-defi">
        <h2>St. John Bosco Parish</h2>
        <p>Antonio Arnaiz Avenue corner Amorsolo Street</p>
        <p>Pio del Pilar, 1230 Makati City, Metro Manila, Philippines</p>
        <p>Contact Us:</p>
        <p><a href="tel:(+632) 8894-5932">(+632) 8894-5932 to 34</a></p>
        <p><a href="tel:(+63) 945-551-0931">(+63) 945-551-0931</a></p>
        <p>(+632) 8815-2844 (Telefax)</p>
        <p><a href="mailto:info@sjbmakati.com">info@sjbmakati.com</a></p>
        <span>Copyright © 2025. All Rights Reserved.<br>
            Managed by Social Communications Ministry</span>
    </div>

    <div class="map-container">
        <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3861.8523228205436!2d121.01243837627293!3d14.550436278300499!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3397c913c65ac6b1%3A0x1cb5a45f2e690a11!2sSt.%20John%20Bosco%20Parish%20Church%20-%20Makati%20City%20(Archdiocese%20of%20Manila)!5e0!3m2!1sen!2sph!4v1776674077717!5m2!1sen!2sph" width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
    </div>

    <div class="footer-links">
        <p><b>OTHER LINKS:</b></p>
            <li><a href="https://www.vatican.va/content/vatican/it.html">THE VATICAN</a></li>
            <li><a href="https://rcam.org/">ARCHDIOCESE of Manila</a></li>
            <li><a href="https://www.infoans.org/en">SALESIAN INT'L NEWS AGENCY (ANS)</a></li>
            <li><a href="https://www.bosco.link/">SALESIAN NEWS HUB (EAO)</a></li>
            <li><a href="https://www.sdb.org/">SALESIANS OF DON BOSCO (main)</a></li>
            <li><a href="https://sdb.org.ph/fin/">SALESIANS OF DON BOSCO (FIN)</a></li>
            <li><a href="http://dbfis.org/">SALESIANS OF DON BOSCO (FIS)</a></li>
            <li><a href="http://www.fmafil.org/">SALESIAN SISTERS OF DON BOSCO</a></li>
            <li><a href="https://salesianmissions.org/">SALESIAN MISSIONS PHILIPPINES</a></li>
            <li><a href="https://cbcponline.net/">CBCP</a></li>
            <li><a href="https://www.wordandlife.org/">WORD & LIFE</a></li>
    </div>

</footer>

    <script>
function viewDetails(type, id) {
    fetch(`get-details.php?type=${type}&id=${id}`)
        .then(res => res.json())
        .then(data => {
            let html = "<h3>Details</h3>";

            for (const key in data) {
                html += `<p><strong>${key}:</strong> ${data[key]}</p>`;
            }

            // Add Accept and Delete buttons for mass requests
            if (type === "mass") {
                if (data.status === 'pending' || !data.status) {
                    html += `<div style="margin-top: 20px; display: flex; gap: 10px;">
                        <button class="btn-approve" onclick="acceptMassRequest(${id})" style="background-color: #28a745; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer;">✓ Approve</button>
                        <button class="btn-decline" onclick="declineMassRequest(${id})" style="background-color: #dc3545; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer;">✗ Decline</button>
                    </div>`;
                } else {
                    html += `<p><strong>Status:</strong> ${data.status.charAt(0).toUpperCase() + data.status.slice(1)}</p>`;
                }
            }

            document.getElementById("modal-body").innerHTML = html;
            document.getElementById("modal").style.display = "block";
        });
}

function closeModal() {
    document.getElementById("modal").style.display = "none";
}

function acceptMassRequest(id) {
    if (confirm("Are you sure you want to accept this mass request?")) {
        fetch("process-mass-request.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded"
            },
            body: "action=accept&id=" + id
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                closeModal();
                location.reload();
            } else {
                alert("Error: " + data.message);
            }
        })
        .catch(err => {
            alert("Error: " + err);
        });
    }
}

function declineMassRequest(id) {
    if (confirm("Are you sure you want to decline this mass request?")) {
        fetch("process-mass-request.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded"
            },
            body: "action=decline&id=" + id
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                closeModal();
                location.reload();
            } else {
                alert("Error: " + data.message);
            }
        })
        .catch(err => {
            alert("Error: " + err);
        });
    }
}

function deleteMassRequest(id) {
    if (confirm("Are you sure you want to delete this mass request?")) {
        fetch("process-mass-request.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded"
            },
            body: "action=delete&id=" + id
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                closeModal();
                location.reload();
            } else {
                alert("Error: " + data.message);
            }
        })
        .catch(err => {
            alert("Error: " + err);
        });
    }
}

function acceptContactRequest(id) {
    if (confirm("Are you sure you want to accept this contact request?")) {
        fetch("process-contact-request.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded"
            },
            body: "action=accept&id=" + id
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                location.reload();
            } else {
                alert("Error: " + data.message);
            }
        })
        .catch(err => {
            alert("Error: " + err);
        });
    }
}

function deleteContactRequest(id) {
    if (confirm("Are you sure you want to delete this contact request?")) {
        fetch("process-contact-request.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded"
            },
            body: "action=delete&id=" + id
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                location.reload();
            } else {
                alert("Error: " + data.message);
            }
        })
        .catch(err => {
            alert("Error: " + err);
        });
    }
}
    </script>
</body>
</html>
