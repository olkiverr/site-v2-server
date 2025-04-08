<?php
require_once __DIR__ . '/../php/db.php';

// Start the session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user']) || $_SESSION['is_admin'] != 1) {
    die("Access denied. You must be an admin to run this setup.");
}

// Output buffer to store messages
$output = [];

// Function to run a query and log the result
function runQuery($conn, $sql, $description) {
    global $output;
    
    if ($conn->query($sql) === TRUE) {
        $output[] = "SUCCESS: " . $description;
        return true;
    } else {
        $output[] = "ERROR: " . $description . " - " . $conn->error;
        return false;
    }
}

// Create categories table
$sql_categories = "CREATE TABLE IF NOT EXISTS forum_communities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    slug VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    user_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
)";

runQuery($conn, $sql_categories, "Created forum_communities table");

// Create topics table
$sql_topics = "CREATE TABLE IF NOT EXISTS forum_topics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    community_id INT,
    user_id INT,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    views INT DEFAULT 0,
    FOREIGN KEY (community_id) REFERENCES forum_communities(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
)";

runQuery($conn, $sql_topics, "Created forum_topics table");

// Create comments table
$sql_comments = "CREATE TABLE IF NOT EXISTS forum_comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    topic_id INT,
    user_id INT,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (topic_id) REFERENCES forum_topics(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
)";

runQuery($conn, $sql_comments, "Created forum_comments table");

// Create votes table for topics and comments (like Reddit upvotes/downvotes)
$sql_votes = "CREATE TABLE IF NOT EXISTS forum_votes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    reference_id INT,
    reference_type ENUM('topic', 'comment'),
    vote_type TINYINT NOT NULL, /* 1 for upvote, -1 for downvote */
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_vote (user_id, reference_id, reference_type),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";

runQuery($conn, $sql_votes, "Created forum_votes table");

// Check if communities exist before inserting
$result = $conn->query("SELECT COUNT(*) as count FROM forum_communities");
$row = $result->fetch_assoc();

if ($row['count'] == 0) {
    $default_communities = [
        ['name' => 'General', 'slug' => 'general', 'description' => 'General discussions about anime and manga'],
        ['name' => 'Dragon Ball', 'slug' => 'dragon-ball', 'description' => 'Discussions about Dragon Ball, Dragon Ball Z, Dragon Ball Super and more'],
        ['name' => 'Naruto', 'slug' => 'naruto', 'description' => 'All about Naruto, Naruto Shippuden, Boruto and the ninja world'],
        ['name' => 'One Piece', 'slug' => 'one-piece', 'description' => 'Discussions about One Piece and the journey to find the ultimate treasure']
    ];
    
    $stmt = $conn->prepare("INSERT INTO forum_communities (name, slug, description) VALUES (?, ?, ?)");
    
    if ($stmt) {
        foreach ($default_communities as $community) {
            $stmt->bind_param("sss", $community['name'], $community['slug'], $community['description']);
            if ($stmt->execute()) {
                $output[] = "SUCCESS: Added community '" . $community['name'] . "'";
            } else {
                $output[] = "ERROR: Failed to add community '" . $community['name'] . "' - " . $stmt->error;
            }
        }
        
        $stmt->close();
    } else {
        $output[] = "ERROR: Failed to prepare statement for adding communities - " . $conn->error;
    }
} else {
    $output[] = "INFO: Communities already exist, skipping insertion";
}

// Page title
$page_title = "Forum Setup - MangaMuse";
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
        .setup-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            animation: fadeIn 0.8s ease-out;
        }
        
        .setup-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .setup-title {
            font-size: 2rem;
            color: #fff;
        }
        
        .setup-results {
            background-color: #333;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .result-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .result-item {
            padding: 10px;
            border-bottom: 1px solid #444;
        }
        
        .result-item:last-child {
            border-bottom: none;
        }
        
        .result-success {
            color: #28a745;
        }
        
        .result-error {
            color: #dc3545;
        }
        
        .result-info {
            color: #17a2b8;
        }
        
        .setup-actions {
            text-align: center;
            margin-top: 20px;
        }
        
        .btn {
            display: inline-block;
            font-weight: 400;
            text-align: center;
            white-space: nowrap;
            vertical-align: middle;
            user-select: none;
            border: 1px solid transparent;
            padding: 0.375rem 0.75rem;
            font-size: 1rem;
            line-height: 1.5;
            border-radius: 0.25rem;
            transition: all 0.15s ease-in-out;
            cursor: pointer;
            text-decoration: none;
        }
        
        .btn-primary {
            color: #fff;
            background-color: #007bff;
            border-color: #007bff;
        }
        
        .btn-primary:hover {
            background-color: #0069d9;
            border-color: #0062cc;
        }
    </style>
</head>
<body>
    <?php include_once __DIR__ . '/../partials/header.php'; ?>
    
    <div class="setup-container">
        <div class="setup-header">
            <h1 class="setup-title">Forum Setup</h1>
            <p>This page creates and initializes the forum tables and default categories.</p>
        </div>
        
        <div class="setup-results">
            <h2 class="section-title">Setup Results</h2>
            <ul class="result-list">
                <?php foreach ($output as $message): ?>
                    <li class="result-item <?php 
                        if (strpos($message, 'SUCCESS') === 0) echo 'result-success';
                        elseif (strpos($message, 'ERROR') === 0) echo 'result-error';
                        elseif (strpos($message, 'INFO') === 0) echo 'result-info';
                    ?>">
                        <?php echo htmlspecialchars($message); ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        
        <div class="setup-actions">
            <a href="/4TTJ/Zielinski%20Olivier/Site/site-v2/pages/forum.php" class="btn btn-primary">Go to Forum</a>
        </div>
    </div>
    
    <?php include_once __DIR__ . '/../partials/footer.php'; ?>
</body>
</html> 