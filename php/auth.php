<?php
session_start();
include 'db.php';

header('Content-Type: application/json');

$response = array();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $sql = "SELECT * FROM users WHERE username='$username'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if (password_verify($password, $row['password'])) {
            $_SESSION['user'] = $username;
            $_SESSION['is_admin'] = $row['is_admin'];
            $response['status'] = 'success';
            $response['message'] = 'Login successful';
        } else {
            $response['status'] = 'error';
            $response['message'] = 'Invalid password';
        }
    } else {
        $response['status'] = 'error';
        $response['message'] = 'No user found with that username';
    }

    $conn->close();
} else {
    $response['status'] = 'error';
    $response['message'] = 'Invalid request method';
}

echo json_encode($response);
?>
