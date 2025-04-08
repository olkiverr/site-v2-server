<?php
require_once __DIR__ . '/../db.php';

// Activer l'affichage des erreurs
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Création de la table forum_votes</h1>";

// Vérifier si la table existe déjà
$table_exists = false;
try {
    $result = $conn->query("SHOW TABLES LIKE 'forum_votes'");
    $table_exists = $result->num_rows > 0;
    
    if ($table_exists) {
        echo "<p>La table forum_votes existe déjà.</p>";
        
        // Afficher la structure de la table
        echo "<h2>Structure de la table forum_votes</h2>";
        $result = $conn->query("DESCRIBE forum_votes");
        echo "<table border='1'>";
        echo "<tr><th>Champ</th><th>Type</th><th>Null</th><th>Clé</th><th>Défaut</th><th>Extra</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['Field'] . "</td>";
            echo "<td>" . $row['Type'] . "</td>";
            echo "<td>" . $row['Null'] . "</td>";
            echo "<td>" . $row['Key'] . "</td>";
            echo "<td>" . $row['Default'] . "</td>";
            echo "<td>" . $row['Extra'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>La table forum_votes n'existe pas. Création en cours...</p>";
        
        // Créer la table forum_votes
        $sql = "CREATE TABLE forum_votes (
            id INT(11) NOT NULL AUTO_INCREMENT,
            user_id INT(11) NOT NULL,
            reference_id INT(11) NOT NULL,
            reference_type VARCHAR(20) NOT NULL,
            vote_type TINYINT(1) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY user_reference (user_id, reference_id, reference_type),
            KEY reference (reference_id, reference_type)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        
        if ($conn->query($sql) === TRUE) {
            echo "<p>Table forum_votes créée avec succès.</p>";
        } else {
            echo "<p>Erreur lors de la création de la table: " . $conn->error . "</p>";
        }
    }
} catch (Exception $e) {
    echo "<p>Erreur: " . $e->getMessage() . "</p>";
}

// Afficher les votes existants
if ($table_exists) {
    echo "<h2>Votes existants</h2>";
    $result = $conn->query("SELECT * FROM forum_votes ORDER BY created_at DESC LIMIT 50");
    
    if ($result->num_rows > 0) {
        echo "<table border='1'>";
        echo "<tr><th>ID</th><th>User ID</th><th>Reference ID</th><th>Reference Type</th><th>Vote Type</th><th>Created At</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['id'] . "</td>";
            echo "<td>" . $row['user_id'] . "</td>";
            echo "<td>" . $row['reference_id'] . "</td>";
            echo "<td>" . $row['reference_type'] . "</td>";
            echo "<td>" . $row['vote_type'] . "</td>";
            echo "<td>" . $row['created_at'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>Aucun vote n'existe encore.</p>";
    }
}

// Afficher les scores de vote pour les sujets
echo "<h2>Scores de vote pour les sujets</h2>";
$result = $conn->query("
    SELECT t.id, t.title, COALESCE(SUM(v.vote_type), 0) as vote_score 
    FROM forum_topics t 
    LEFT JOIN forum_votes v ON t.id = v.reference_id AND v.reference_type = 'topic' 
    GROUP BY t.id 
    ORDER BY vote_score DESC 
    LIMIT 10
");

if ($result->num_rows > 0) {
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Titre</th><th>Score</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . htmlspecialchars($row['title']) . "</td>";
        echo "<td>" . $row['vote_score'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>Aucun sujet n'a de votes.</p>";
}

// Afficher les scores de vote pour les commentaires
echo "<h2>Scores de vote pour les commentaires</h2>";
$result = $conn->query("
    SELECT c.id, c.content, COALESCE(SUM(v.vote_type), 0) as vote_score 
    FROM forum_comments c 
    LEFT JOIN forum_votes v ON c.id = v.reference_id AND v.reference_type = 'comment' 
    GROUP BY c.id 
    ORDER BY vote_score DESC 
    LIMIT 10
");

if ($result->num_rows > 0) {
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Contenu</th><th>Score</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . htmlspecialchars(substr($row['content'], 0, 50)) . (strlen($row['content']) > 50 ? '...' : '') . "</td>";
        echo "<td>" . $row['vote_score'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>Aucun commentaire n'a de votes.</p>";
}

echo "<p><a href='/4TTJ/Zielinski%20Olivier/Site/site-v2/pages/debug_votes.php'>Retour à la page de débogage des votes</a></p>";
?> 