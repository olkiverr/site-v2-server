<?php
include 'db.php';

$sql = "CREATE TABLE IF NOT EXISTS user_anime_status (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    anime_id INT NOT NULL,
    is_favorite BOOLEAN DEFAULT FALSE,
    is_watched BOOLEAN DEFAULT FALSE,
    UNIQUE KEY unique_user_anime (user_id, anime_id)
)";

if ($conn->query($sql) === TRUE) {
    echo "Table user_anime_status created successfully";
} else {
    echo "Error creating table: " . $conn->error;
}

$conn->close();
?> 