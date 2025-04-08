<?php
require_once __DIR__ . '/../db.php';

// Activer l'affichage des erreurs pour le débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Vérifie si la colonne vote_score existe dans la table forum_comments
function checkVoteScoreColumn() {
    global $conn;
    
    try {
        $result = $conn->query("SHOW COLUMNS FROM forum_comments LIKE 'vote_score'");
        
        if ($result->num_rows === 0) {
            // La colonne n'existe pas, on l'ajoute
            echo "<p>La colonne vote_score n'existe pas dans la table forum_comments. Ajout en cours...</p>";
            
            $conn->query("ALTER TABLE forum_comments ADD COLUMN vote_score INT DEFAULT 0");
            
            // Mise à jour des scores existants
            echo "<p>Mise à jour des scores de vote pour les commentaires existants...</p>";
            
            $comments = $conn->query("SELECT id FROM forum_comments");
            
            $updated = 0;
            while ($comment = $comments->fetch_assoc()) {
                $score_query = $conn->prepare("SELECT COALESCE(SUM(vote_type), 0) as score FROM forum_votes WHERE reference_id = ? AND reference_type = 'comment'");
                $score_query->bind_param("i", $comment['id']);
                $score_query->execute();
                $score_result = $score_query->get_result();
                $score = 0;
                
                if ($score_result && $score_result->num_rows > 0) {
                    $score_data = $score_result->fetch_assoc();
                    $score = intval($score_data['score']);
                }
                
                $update = $conn->prepare("UPDATE forum_comments SET vote_score = ? WHERE id = ?");
                $update->bind_param("ii", $score, $comment['id']);
                $update->execute();
                $updated++;
            }
            
            echo "<p>Mise à jour terminée. $updated commentaires mis à jour.</p>";
            echo "<p>La colonne vote_score a été ajoutée avec succès à la table forum_comments.</p>";
        } else {
            echo "<p>La colonne vote_score existe déjà dans la table forum_comments.</p>";
            
            // On vérifie quand même si tous les commentaires ont un vote_score
            $comments_with_null = $conn->query("SELECT COUNT(*) as count FROM forum_comments WHERE vote_score IS NULL");
            $null_count = $comments_with_null->fetch_assoc()['count'];
            
            if ($null_count > 0) {
                echo "<p>Il y a $null_count commentaires sans score de vote. Mise à jour en cours...</p>";
                $conn->query("UPDATE forum_comments SET vote_score = 0 WHERE vote_score IS NULL");
                echo "<p>Mise à jour terminée.</p>";
            } else {
                echo "<p>Tous les commentaires ont une valeur pour vote_score.</p>";
            }
        }
        
        echo "<p>Affichage des 5 premiers commentaires avec leurs scores:</p>";
        $sample = $conn->query("SELECT id, content, vote_score FROM forum_comments LIMIT 5");
        
        echo "<table border='1'>";
        echo "<tr><th>ID</th><th>Content</th><th>Score</th></tr>";
        
        while ($row = $sample->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['id']) . "</td>";
            echo "<td>" . htmlspecialchars(substr($row['content'], 0, 50)) . "...</td>";
            echo "<td>" . htmlspecialchars($row['vote_score']) . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
        
        return true;
    } catch (Exception $e) {
        echo "<p>Erreur lors de la vérification de la colonne vote_score: " . $e->getMessage() . "</p>";
        return false;
    }
}

// Structure complète de la table forum_comments
function showTableStructure() {
    global $conn;
    
    try {
        $result = $conn->query("DESCRIBE forum_comments");
        
        echo "<h3>Structure de la table forum_comments:</h3>";
        echo "<table border='1'>";
        echo "<tr><th>Champ</th><th>Type</th><th>Null</th><th>Clé</th><th>Défaut</th><th>Extra</th></tr>";
        
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['Field']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Default'] ?? 'NULL') . "</td>";
            echo "<td>" . htmlspecialchars($row['Extra']) . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
        
        return true;
    } catch (Exception $e) {
        echo "<p>Erreur lors de l'affichage de la structure de la table: " . $e->getMessage() . "</p>";
        return false;
    }
}

// Vérifier le nombre de commentaires
function countComments() {
    global $conn;
    
    try {
        $result = $conn->query("SELECT COUNT(*) as count FROM forum_comments");
        $count = $result->fetch_assoc()['count'];
        
        echo "<p>Nombre total de commentaires: $count</p>";
        
        return $count;
    } catch (Exception $e) {
        echo "<p>Erreur lors du comptage des commentaires: " . $e->getMessage() . "</p>";
        return 0;
    }
}

// Vérifier le fonctionnement de la fonction getComments
function testGetComments() {
    global $conn;
    
    try {
        // Récupérer un ID de sujet valide
        $topic_result = $conn->query("SELECT id FROM forum_topics LIMIT 1");
        
        if ($topic_result->num_rows === 0) {
            echo "<p>Aucun sujet trouvé dans la base de données.</p>";
            return false;
        }
        
        $topic_id = $topic_result->fetch_assoc()['id'];
        
        echo "<p>Test de la fonction getComments pour le sujet ID: $topic_id</p>";
        
        // Inclure le fichier de fonctions du forum
        require_once __DIR__ . '/forum_functions.php';
        
        // Appeler la fonction getComments
        $comments = getComments($topic_id, 1, 5, 'date');
        
        if (empty($comments)) {
            echo "<p>Aucun commentaire récupéré pour ce sujet.</p>";
            
            // Vérifier s'il y a effectivement des commentaires dans la base de données pour ce sujet
            $check = $conn->prepare("SELECT COUNT(*) as count FROM forum_comments WHERE topic_id = ?");
            $check->bind_param("i", $topic_id);
            $check->execute();
            $check_result = $check->get_result();
            $comment_count = $check_result->fetch_assoc()['count'];
            
            if ($comment_count > 0) {
                echo "<p>Il y a $comment_count commentaires dans la base de données pour ce sujet, mais getComments() n'en récupère aucun. Il y a un problème avec la fonction.</p>";
            } else {
                echo "<p>Il n'y a effectivement aucun commentaire dans la base de données pour ce sujet.</p>";
            }
        } else {
            echo "<p>" . count($comments) . " commentaires récupérés pour ce sujet.</p>";
            
            echo "<table border='1'>";
            echo "<tr><th>ID</th><th>Content</th><th>Score</th><th>Username</th><th>Replies</th></tr>";
            
            foreach ($comments as $comment) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($comment['id']) . "</td>";
                echo "<td>" . htmlspecialchars(substr($comment['content'], 0, 50)) . "...</td>";
                echo "<td>" . htmlspecialchars($comment['vote_score'] ?? 'NULL') . "</td>";
                echo "<td>" . htmlspecialchars($comment['username'] ?? 'Anonymous') . "</td>";
                echo "<td>" . (isset($comment['replies']) ? count($comment['replies']) : 0) . "</td>";
                echo "</tr>";
            }
            
            echo "</table>";
        }
        
        return true;
    } catch (Exception $e) {
        echo "<p>Erreur lors du test de getComments: " . $e->getMessage() . "</p>";
        return false;
    }
}

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vérification de la table forum_comments</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 20px;
            background-color: #232323;
            color: #f0f0f0;
        }
        
        h1, h2, h3 {
            color: #5e72e4;
        }
        
        table {
            border-collapse: collapse;
            margin: 20px 0;
            width: 100%;
        }
        
        table, th, td {
            border: 1px solid #444;
        }
        
        th, td {
            padding: 10px;
            text-align: left;
        }
        
        th {
            background-color: #333;
        }
        
        p {
            margin-bottom: 10px;
        }
        
        .success {
            color: #4CAF50;
            font-weight: bold;
        }
        
        .error {
            color: #F44336;
            font-weight: bold;
        }
        
        a {
            color: #5e72e4;
            text-decoration: none;
        }
        
        a:hover {
            text-decoration: underline;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background-color: #2a2a2a;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.3);
        }
        
        hr {
            border: none;
            border-top: 1px solid #444;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Vérification de la table forum_comments</h1>
        
        <hr>
        
        <h2>1. Vérification de la colonne vote_score</h2>
        <?php checkVoteScoreColumn(); ?>
        
        <hr>
        
        <h2>2. Structure de la table</h2>
        <?php showTableStructure(); ?>
        
        <hr>
        
        <h2>3. Nombre de commentaires</h2>
        <?php countComments(); ?>
        
        <hr>
        
        <h2>4. Test de la fonction getComments</h2>
        <?php testGetComments(); ?>
        
        <hr>
        
        <p><a href="../pages/forum.php">Retour au forum</a></p>
    </div>
</body>
</html> 