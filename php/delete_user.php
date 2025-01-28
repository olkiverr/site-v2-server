<?php
include 'db.php';

$id = $_GET['id'];

$sql = "DELETE FROM users WHERE id='$id'";

if ($conn->query($sql) === TRUE) {
    header("Location: ../pages/admin_panel.php?tab=users");
} else {
    echo "Error: " . $sql . "<br>" . $conn->error;
}

$conn->close();
?>
