<?php
include 'db.php';

$id = $_POST['id'];
$username = $_POST['username'];
$email = $_POST['email'];
$is_admin = isset($_POST['is_admin']) ? 1 : 0;

$sql = "UPDATE users SET username='$username', email='$email', is_admin='$is_admin'";

if (!empty($_POST['password'])) {
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $sql .= ", password='$password'";
}

$sql .= " WHERE id='$id'";

if ($conn->query($sql) === TRUE) {
    header("Location: ../pages/admin_panel.php?tab=users");
} else {
    echo "Error: " . $sql . "<br>" . $conn->error;
}

$conn->close();
?>