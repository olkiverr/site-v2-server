<?php
include 'db.php';

$username = $_POST['username'];
$email = $_POST['email'];
$password = password_hash($_POST['password'], PASSWORD_DEFAULT);
$is_admin = isset($_POST['is_admin']) ? 1 : 0;

$sql = "INSERT INTO users (username, email, password, is_admin) VALUES ('$username', '$email', '$password', '$is_admin')";

if ($conn->query($sql) === TRUE) {
    header("Location: ../pages/admin_panel.php?tab=users");
} else {
    echo "Error: " . $sql . "<br>" . $conn->error;
}

$conn->close();
?>
