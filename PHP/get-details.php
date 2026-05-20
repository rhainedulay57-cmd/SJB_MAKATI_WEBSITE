<?php
$conn = new mysqli("localhost", "root", "", "sjb_databases");

$type = $_GET['type'];
$id = $_GET['id'];

if ($type == "mass") {
    $result = $conn->query("SELECT * FROM mass_blessing WHERE id=$id");
    $row = $result->fetch_assoc();
    if ($row && !isset($row['status'])) {
        $row['status'] = 'pending';
    }
    echo json_encode($row);
} else {
    $result = $conn->query("SELECT * FROM contact_us_form WHERE id=$id");
    echo json_encode($result->fetch_assoc());
}
?>