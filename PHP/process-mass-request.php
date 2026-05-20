<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    http_response_code(403);
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit();
}

$conn = new mysqli("localhost", "root", "", "sjb_databases");

if ($conn->connect_error) {
    echo json_encode(["success" => false, "message" => "Connection failed: " . $conn->connect_error]);
    exit();
}

$action = $_POST['action'] ?? null;
$id = $_POST['id'] ?? null;

function ensureStatusColumn($conn) {
    $columnCheck = $conn->query("SHOW COLUMNS FROM `mass_blessing` LIKE 'status'");
    if ($columnCheck && $columnCheck->num_rows === 0) {
        $conn->query("ALTER TABLE `mass_blessing` ADD COLUMN `status` ENUM('pending','approved','declined') NOT NULL DEFAULT 'pending'");
    }
}

if (!$action || !$id) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Invalid request"]);
    exit();
}

if ($action == "delete") {
    $sql = "DELETE FROM `mass_blessing` WHERE id = " . intval($id);
    if ($conn->query($sql)) {
        echo json_encode(["success" => true, "message" => "Mass request deleted successfully!"]);
    } else {
        echo json_encode(["success" => false, "message" => "Error: " . $conn->error]);
    }
} elseif ($action == "accept") {
    ensureStatusColumn($conn);
    $sql = "UPDATE `mass_blessing` SET status = 'approved' WHERE id = " . intval($id);
    if ($conn->query($sql)) {
        echo json_encode(["success" => true, "message" => "Mass request approved!"]);
    } else {
        echo json_encode(["success" => false, "message" => "Error: " . $conn->error]);
    }
} elseif ($action == "decline") {
    ensureStatusColumn($conn);
    $sql = "UPDATE `mass_blessing` SET status = 'declined' WHERE id = " . intval($id);
    if ($conn->query($sql)) {
        echo json_encode(["success" => true, "message" => "Mass request declined!"]);
    } else {
        echo json_encode(["success" => false, "message" => "Error: " . $conn->error]);
    }
}

$conn->close();
?>
