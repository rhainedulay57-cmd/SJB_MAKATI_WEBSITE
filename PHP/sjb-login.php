<?php
session_start();

if ($_SERVER["REQUEST_METHOD"] != "POST") {
    header("Location: sjb-login-form.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "sjb_databases");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$email = $_POST['email'];
$password = $_POST['password'];

// Check if user exists
$stmt = $conn->prepare("SELECT id, firstname, email FROM `register` WHERE email = ? AND password = ?");
$stmt->bind_param("ss", $email, $password);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $_SESSION['user_id'] = $row['id'];
    $_SESSION['user_name'] = $row['firstname'];
    $_SESSION['user_email'] = $row['email'];
    
    // Check if user is admin (admin@sjbmakati.org)
    if ($email === "admin@sjbmakati.org") {
        $_SESSION['is_admin'] = 1;
        echo "Admin Login Successful!";
    } else {
        $_SESSION['is_admin'] = 0;
        echo "Login Successful!";
    }
} else {
    echo "Error: Invalid email or password";
}

$stmt->close();
$conn->close();
?>

