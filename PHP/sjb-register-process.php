<?php

if ($_SERVER["REQUEST_METHOD"] != "POST") {
    header("Location: sjb-register.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "sjb_databases");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$Fname = $_POST['firstname'];
$Lname = $_POST['lastname'];
$email = $_POST['email'];
$phone = $_POST['phone'];
$password = $_POST['password'];

// Prepared statement
$stmt = $conn->prepare("INSERT INTO `register`
(firstname, lastname, email, phone, password)
VALUES (?, ?, ?, ?, ?)");

$stmt->bind_param("sssss", $Fname, $Lname, $email, $phone, $password);

if ($stmt->execute()) {
    echo "Registration Successful!";
} else {
    echo "Error: " . $stmt->error;
}

$stmt->close();
$conn->close();

?>