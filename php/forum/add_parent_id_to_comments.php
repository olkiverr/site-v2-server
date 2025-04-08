<?php
require_once __DIR__ . '/../db.php';

// Add parent_id column to forum_comments table
$sql = "ALTER TABLE forum_comments ADD COLUMN parent_id INT DEFAULT NULL AFTER id, 
        ADD FOREIGN KEY (parent_id) REFERENCES forum_comments(id) ON DELETE CASCADE";

try {
    if ($conn->query($sql)) {
        echo "Column parent_id added successfully to forum_comments table.";
    } else {
        echo "Error adding column: " . $conn->error;
    }
} catch (mysqli_sql_exception $e) {
    // Check if the column already exists
    if ($e->getCode() == 1060) { // Duplicate column error
        echo "Column parent_id already exists in forum_comments table.";
    } else {
        echo "Error: " . $e->getMessage();
    }
}

$conn->close();
?> 