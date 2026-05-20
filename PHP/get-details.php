<?php
$conn = new mysqli("localhost", "root", "", "sjb_databases");
header('Content-Type: application/json');

$type = $_GET['type'] ?? '';
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    echo json_encode(null);
    exit();
}

if ($type === "mass") {
    $result = $conn->query("SELECT * FROM mass_blessing WHERE id={$id} LIMIT 1");
    $row = $result ? $result->fetch_assoc() : null;
    if ($row && !isset($row['status'])) {
        $row['status'] = 'pending';
    }
    echo json_encode($row);
} else {
    $result = $conn->query("SELECT * FROM contact_us_form WHERE id={$id} LIMIT 1");
    $row = $result ? $result->fetch_assoc() : null;
    echo json_encode($row);
}
?>