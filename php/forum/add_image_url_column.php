<?php
require_once __DIR__ . '/../db.php';

// Start the session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if the column exists first
$column_check = $conn->query("SHOW COLUMNS FROM forum_topics LIKE 'image_url'");

if ($column_check->num_rows == 0) {
    // The column doesn't exist, add it
    $sql = "ALTER TABLE forum_topics ADD COLUMN image_url VARCHAR(255) DEFAULT NULL";
    
    if ($conn->query($sql) === TRUE) {
        echo "La colonne image_url a été ajoutée avec succès à la table forum_topics.";
    } else {
        echo "Erreur lors de l'ajout de la colonne: " . $conn->error;
    }
} else {
    echo "La colonne image_url existe déjà dans la table forum_topics.";
}

// Création du dossier de stockage s'il n'existe pas
$upload_dir = __DIR__ . '/../../uploads/topic_images/';
if (!file_exists($upload_dir)) {
    if (mkdir($upload_dir, 0777, true)) {
        echo "<br>Le dossier de stockage des images a été créé avec succès.";
    } else {
        echo "<br>Erreur lors de la création du dossier de stockage des images.";
    }
} else {
    echo "<br>Le dossier de stockage des images existe déjà.";
}

echo "<br><br><a href='../../pages/forum.php'>Retour au forum</a>";
?> 