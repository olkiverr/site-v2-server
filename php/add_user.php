<?php
include 'db.php';

$username = $_POST['username'];
$email = $_POST['email'];
$password = password_hash($_POST['password'], PASSWORD_DEFAULT);
$is_admin = isset($_POST['is_admin']) ? 1 : 0;

$stmt = $conn->prepare("CALL AddUser(?, ?, ?, ?)");
$stmt->bind_param("sssi", $username, $email, $password, $is_admin);

if ($stmt->execute()) {
    header("Location: ../pages/admin_panel.php?tab=users");
} else {
    echo "Error: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>
