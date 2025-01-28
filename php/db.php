<?php
$servername = "172.16.20.219";
$username = "olivier";
$password = "sofd";
$dbname = "mangamuse";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
