<?php
require_once '../db.php';

// Activer l'affichage des erreurs
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Vérifier si la colonne vote_score existe
    $result = $conn->query("SHOW COLUMNS FROM forum_topics LIKE 'vote_score'");
    
    if ($result->num_rows == 0) {
        // La colonne n'existe pas, on l'ajoute
        $conn->query("ALTER TABLE forum_topics ADD COLUMN vote_score INT(11) NOT NULL DEFAULT 0");
        echo "La colonne vote_score a été ajoutée à la table forum_topics.<br>";
        
        // Mettre à jour les scores de vote existants
        $conn->query("UPDATE forum_topics t 
                     SET vote_score = (
                         SELECT COALESCE(SUM(vote_type), 0) 
                         FROM forum_votes v 
                         WHERE v.reference_id = t.id 
                         AND v.reference_type = 'topic'
                     )");
        echo "Les scores de vote ont été mis à jour.<br>";
    } else {
        echo "La colonne vote_score existe déjà.<br>";
    }
    
    // Afficher les 10 premiers topics avec leurs scores
    $result = $conn->query("SELECT id, title, vote_score FROM forum_topics ORDER BY vote_score DESC LIMIT 10");
    echo "<br>Top 10 des topics par score :<br>";
    while ($row = $result->fetch_assoc()) {
        echo "Topic {$row['id']}: {$row['title']} - Score: {$row['vote_score']}<br>";
    }
    
} catch (Exception $e) {
    echo "Erreur : " . $e->getMessage();
}
?> 