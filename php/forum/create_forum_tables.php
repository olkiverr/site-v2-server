<?php
require_once '../db.php';

// Create communities table
$sql_communities = "CREATE TABLE IF NOT EXISTS forum_communities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    user_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
)";

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

// Create comments table
$sql_comments = "CREATE TABLE IF NOT EXISTS forum_comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    topic_id INT NOT NULL,
    user_id INT,
    parent_id INT DEFAULT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    vote_score INT DEFAULT 0,
    FOREIGN KEY (topic_id) REFERENCES forum_topics(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (parent_id) REFERENCES forum_comments(id) ON DELETE CASCADE
)";

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

// Execute the queries
if ($conn->query($sql_communities) === TRUE) {
    echo "Table forum_communities created successfully<br>";
} else {
    echo "Error creating table forum_communities: " . $conn->error . "<br>";
}

if ($conn->query($sql_topics) === TRUE) {
    echo "Table forum_topics created successfully<br>";
} else {
    echo "Error creating table forum_topics: " . $conn->error . "<br>";
}

if ($conn->query($sql_comments) === TRUE) {
    echo "Table forum_comments created successfully<br>";
} else {
    echo "Error creating table forum_comments: " . $conn->error . "<br>";
}

if ($conn->query($sql_votes) === TRUE) {
    echo "Table forum_votes created successfully<br>";
} else {
    echo "Error creating table forum_votes: " . $conn->error . "<br>";
}

// Insert some default communities
$default_communities = [
    ['name' => 'Shonen Anime', 'slug' => 'shonen-anime', 'description' => 'Discuss popular shonen anime like Naruto, One Piece, Dragon Ball, etc.', 'user_id' => NULL],
    ['name' => 'Shojo Anime', 'slug' => 'shojo-anime', 'description' => 'Discuss shojo anime series and movies', 'user_id' => NULL],
    ['name' => 'Seinen Anime', 'slug' => 'seinen-anime', 'description' => 'Discuss seinen anime targeted towards adult men', 'user_id' => NULL],
    ['name' => 'Isekai Anime', 'slug' => 'isekai-anime', 'description' => 'Discuss isekai (another world) anime series', 'user_id' => NULL],
    ['name' => 'Studio Ghibli', 'slug' => 'studio-ghibli', 'description' => 'Discuss films and works from Studio Ghibli', 'user_id' => NULL],
    ['name' => 'Upcoming Releases', 'slug' => 'upcoming-releases', 'description' => 'Discuss upcoming anime releases and announcements', 'user_id' => NULL],
    ['name' => 'Anime News', 'slug' => 'anime-news', 'description' => 'Share and discuss the latest anime industry news', 'user_id' => NULL]
];

// Check if communities exist before inserting
$result = $conn->query("SELECT COUNT(*) as count FROM forum_communities");
$row = $result->fetch_assoc();

if ($row['count'] == 0) {
    $stmt = $conn->prepare("INSERT INTO forum_communities (name, slug, description, user_id) VALUES (?, ?, ?, ?)");
    
    foreach ($default_communities as $community) {
        $stmt->bind_param("sssi", $community['name'], $community['slug'], $community['description'], $community['user_id']);
        $stmt->execute();
    }
    
    echo "Default communities inserted successfully<br>";
    $stmt->close();
}

// Migration: If the old forum_categories table exists and we need to migrate data
if ($conn->query("SHOW TABLES LIKE 'forum_categories'")->num_rows > 0) {
    // Check if we need to migrate topics from category_id to community_id
    $result = $conn->query("SHOW COLUMNS FROM forum_topics LIKE 'category_id'");
    if ($result->num_rows > 0) {
        // Create a temporary table mapping
        $conn->query("CREATE TEMPORARY TABLE category_community_map (
            category_id INT,
            community_id INT
        )");
        
        // Get all categories and find matching communities by name
        $categories = $conn->query("SELECT * FROM forum_categories");
        while ($category = $categories->fetch_assoc()) {
            // Clean the name to create a slug
            $slug = strtolower(str_replace(' ', '-', preg_replace('/[^A-Za-z0-9\-]/', '', $category['name'])));
            
            // Check if a community with this slug exists
            $stmt = $conn->prepare("SELECT id FROM forum_communities WHERE slug = ?");
            $stmt->bind_param("s", $slug);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                // Community exists
                $community = $result->fetch_assoc();
                $community_id = $community['id'];
            } else {
                // Create new community
                $stmt = $conn->prepare("INSERT INTO forum_communities (name, slug, description) VALUES (?, ?, ?)");
                $stmt->bind_param("sss", $category['name'], $slug, $category['description']);
                $stmt->execute();
                $community_id = $conn->insert_id;
            }
            
            // Insert into mapping table
            $stmt = $conn->prepare("INSERT INTO category_community_map (category_id, community_id) VALUES (?, ?)");
            $stmt->bind_param("ii", $category['id'], $community_id);
            $stmt->execute();
        }
        
        // Update topics to use community_id
        $conn->query("UPDATE forum_topics t 
                      JOIN category_community_map m ON t.category_id = m.category_id 
                      SET t.community_id = m.community_id");
        
        // Drop the category_id column if the migration is complete
        $conn->query("ALTER TABLE forum_topics DROP FOREIGN KEY forum_topics_ibfk_1");
        $conn->query("ALTER TABLE forum_topics DROP COLUMN category_id");
        
        echo "Migrated topics from categories to communities successfully<br>";
    }
    
    // We can drop the old categories table if no longer needed
    // $conn->query("DROP TABLE forum_categories");
    // echo "Dropped old forum_categories table<br>";
}

// Vérifier si la colonne vote_score existe
$result = $conn->query("SHOW COLUMNS FROM forum_comments LIKE 'vote_score'");
if ($result->num_rows === 0) {
    // Ajouter la colonne vote_score si elle n'existe pas
    $conn->query("ALTER TABLE forum_comments ADD COLUMN vote_score INT DEFAULT 0");
    echo "Colonne vote_score ajoutée à la table forum_comments<br>";
}

// Mettre à jour les scores de vote existants
$conn->query("
    UPDATE forum_comments c
    SET c.vote_score = COALESCE(
        (SELECT SUM(vote_type) 
         FROM forum_votes 
         WHERE reference_id = c.id AND reference_type = 'comment'),
        0)
");
echo "Scores de vote mis à jour pour tous les commentaires<br>";

// Afficher la structure de la table
$result = $conn->query("DESCRIBE forum_comments");
echo "<h3>Structure de la table forum_comments :</h3>";
echo "<table border='1'>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    foreach ($row as $value) {
        echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
    }
    echo "</tr>";
}
echo "</table>";

// Afficher quelques statistiques
$stats = $conn->query("
    SELECT 
        COUNT(*) as total_comments,
        COUNT(DISTINCT topic_id) as topics_with_comments,
        COUNT(DISTINCT user_id) as users_with_comments,
        COUNT(CASE WHEN parent_id IS NOT NULL THEN 1 END) as reply_count,
        COUNT(CASE WHEN vote_score != 0 THEN 1 END) as comments_with_votes
    FROM forum_comments
");
$stats_data = $stats->fetch_assoc();

echo "<h3>Statistiques :</h3>";
echo "<ul>";
echo "<li>Number of replies: " . $stats_data['reply_count'] . "</li>";
echo "<li>Nombre total de commentaires : " . $stats_data['total_comments'] . "</li>";
echo "<li>Nombre de sujets avec commentaires : " . $stats_data['topics_with_comments'] . "</li>";
echo "<li>Nombre d'utilisateurs ayant commenté : " . $stats_data['users_with_comments'] . "</li>";
echo "<li>Nombre de commentaires avec votes : " . $stats_data['comments_with_votes'] . "</li>";
echo "</ul>";

$conn->close();
echo "Forum tables setup completed!";
?> 