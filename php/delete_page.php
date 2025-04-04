<?php
// Start the session and check if the user is an admin
session_start();
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header('Location: /4TTJ/Zielinski%20Olivier/Site/site-v2/index.php');
    exit();
}

include_once 'db.php'; // Include database connection

// Check if the 'id' parameter is passed
if (isset($_GET['id'])) {
    $page_id = $_GET['id'];

    // Prepare and execute the SQL query to delete the page
    $sql = "DELETE FROM pages WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $page_id);
    
    if ($stmt->execute()) {
        // Redirect back to the pages tab
        header('Location: /4TTJ/Zielinski%20Olivier/Site/site-v2/pages/admin_panel.php?tab=pages');
        exit();
    } else {
        // Error handling if the deletion fails
        echo "Error deleting page: " . $conn->error;
    }

    $stmt->close();
} else {
    echo "No page ID specified.";
}

$conn->close();
?>
