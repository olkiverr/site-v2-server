<?php
require_once __DIR__ . '/php/db.php';

// Start the session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user']) || $_SESSION['is_admin'] != 1) {
    die("Access denied. You must be an admin to run this script.");
}

$message = "";
$status = "";

// Check if the "None" category already exists
$checkSql = "SELECT * FROM forum_categories WHERE name = 'None'";
$result = $conn->query($checkSql);

if ($result && $result->num_rows > 0) {
    $message = "The 'None' category already exists.";
    $status = "info";
} else {
    try {
        // Add the "None" category
        $insertSql = "INSERT INTO forum_categories (name, description) VALUES ('None', 'General discussions not fitting into other categories')";
        if ($conn->query($insertSql) === TRUE) {
            $message = "Added 'None' category successfully!";
            $status = "success";
        } else {
            $message = "Error adding 'None' category: " . $conn->error;
            $status = "error";
        }
    } catch (Exception $e) {
        $message = "Error: " . $e->getMessage();
        $status = "error";
    }
}

// Page title
$page_title = "Add None Category - MangaMuse";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="/4TTJ/Zielinski%20Olivier/Site/site-v2/css/header.css">
    <link rel="stylesheet" href="/4TTJ/Zielinski%20Olivier/Site/site-v2/css/footer.css">
    <link rel="stylesheet" href="/4TTJ/Zielinski%20Olivier/Site/site-v2/css/forum/forum.css">
    <style>
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            animation: fadeIn 0.8s ease-out;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .title {
            font-size: 2rem;
            color: #fff;
        }
        
        .result-box {
            background-color: #333;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .result-success {
            color: #28a745;
            border-left: 4px solid #28a745;
            padding-left: 15px;
        }
        
        .result-error {
            color: #dc3545;
            border-left: 4px solid #dc3545;
            padding-left: 15px;
        }
        
        .result-info {
            color: #17a2b8;
            border-left: 4px solid #17a2b8;
            padding-left: 15px;
        }
        
        .actions {
            text-align: center;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <?php include_once __DIR__ . '/partials/header.php'; ?>
    
    <div class="container">
        <div class="header">
            <h1 class="title">Add "None" Category</h1>
            <p>This page adds a "None" category to your forum for general discussions.</p>
        </div>
        
        <div class="result-box">
            <h2 class="section-title">Result</h2>
            <p class="result-<?php echo $status; ?>">
                <?php echo $message; ?>
            </p>
        </div>
        
        <div class="actions">
            <a href="/4TTJ/Zielinski%20Olivier/Site/site-v2/pages/forum.php" class="btn btn-primary">Return to Forum</a>
        </div>
    </div>
    
    <?php include_once __DIR__ . '/partials/footer.php'; ?>
</body>
</html> 