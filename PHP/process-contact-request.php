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

if (!$action || !$id) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Invalid request"]);
    exit();
}

if ($action == "delete") {
    $sql = "DELETE FROM `contact_us_form` WHERE id = " . intval($id);
    if ($conn->query($sql)) {
        echo json_encode(["success" => true, "message" => "Contact request deleted successfully!"]);
    } else {
        echo json_encode(["success" => false, "message" => "Error: " . $conn->error]);
    }
} elseif ($action == "accept") {
    $sql = "DELETE FROM `contact_us_form` WHERE id = " . intval($id);
    if ($conn->query($sql)) {
        echo json_encode(["success" => true, "message" => "Contact request accepted!"]);
    } else {
        echo json_encode(["success" => false, "message" => "Error: " . $conn->error]);
    }
}

$conn->close();
?>
