<?php
require_once __DIR__ . '/../php/db.php';
require_once __DIR__ . '/../php/forum/forum_functions.php';

// Remplacer la vérification de session par l'inclusion de la configuration
include_once '../php/session_config.php';

// Activer l'affichage des erreurs pour le débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Récupérer l'ID du sujet depuis l'URL
$topic_id = isset($_GET['topic_id']) ? intval($_GET['topic_id']) : 0;

// Fonction pour formater les dates
function formatDate($date) {
    return date('d/m/Y H:i:s', strtotime($date));
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug Comments</title>
    <link rel="stylesheet" href="/4TTJ/Zielinski%20Olivier/Site/site-v2/css/header.css">
    <link rel="stylesheet" href="/4TTJ/Zielinski%20Olivier/Site/site-v2/css/footer.css">
    <style>
        body { font-family: Arial, sans-serif; background-color: #2a2a2a; color: #f0f0f0; padding: 20px; }
        .debug-container { max-width: 1000px; margin: 0 auto; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #444; }
        th { background-color: #333; }
        pre { background-color: #333; padding: 10px; border-radius: 4px; overflow-x: auto; }
        .section { margin-top: 30px; }
        h2 { border-bottom: 2px solid #444; padding-bottom: 10px; }
        .form-container { margin-bottom: 30px; padding: 15px; background-color: #333; border-radius: 8px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; }
        input, select { padding: 8px; background-color: #222; color: #fff; border: 1px solid #444; border-radius: 4px; }
        button { padding: 8px 16px; background-color: #5e72e4; color: white; border: none; border-radius: 4px; cursor: pointer; }
        .success { color: #4CAF50; }
        .error { color: #f44336; }
    </style>
</head>
<body>
    <?php include_once __DIR__ . '/../partials/header.php'; ?>
    
    <div class="debug-container">
        <h1>Débogage des Commentaires</h1>
        
        <div class="form-container">
            <form method="GET" action="">
                <div class="form-group">
                    <label for="topic_id">ID du Sujet:</label>
                    <input type="number" id="topic_id" name="topic_id" value="<?php echo $topic_id; ?>" min="1" required>
                </div>
                <button type="submit">Afficher les Commentaires</button>
            </form>
        </div>
        
        <?php if ($topic_id > 0): ?>
            <div class="section">
                <h2>Informations sur le Sujet (ID: <?php echo $topic_id; ?>)</h2>
                <?php
                // Récupérer les informations sur le sujet
                $topic_query = "SELECT * FROM forum_topics WHERE id = ?";
                $stmt = $conn->prepare($topic_query);
                $stmt->bind_param("i", $topic_id);
                $stmt->execute();
                $topic_result = $stmt->get_result();
                
                if ($topic_result->num_rows > 0) {
                    $topic = $topic_result->fetch_assoc();
                    echo "<table>";
                    echo "<tr><th>Champ</th><th>Valeur</th></tr>";
                    foreach ($topic as $key => $value) {
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($key) . "</td>";
                        echo "<td>" . htmlspecialchars($value) . "</td>";
                        echo "</tr>";
                    }
                    echo "</table>";
                } else {
                    echo "<p class='error'>Aucun sujet trouvé avec l'ID: $topic_id</p>";
                }
                $stmt->close();
                ?>
            </div>
            
            <div class="section">
                <h2>Commentaires du Sujet</h2>
                <?php
                // Vérifier si la table forum_comments existe
                $table_check = $conn->query("SHOW TABLES LIKE 'forum_comments'");
                if ($table_check->num_rows === 0) {
                    echo "<p class='error'>La table 'forum_comments' n'existe pas dans la base de données!</p>";
                } else {
                    // Récupérer les commentaires directement
                    $comments_query = "SELECT c.*, u.username 
                                      FROM forum_comments c
                                      LEFT JOIN users u ON c.user_id = u.id
                                      WHERE c.topic_id = ?
                                      ORDER BY c.created_at DESC";
                    $stmt = $conn->prepare($comments_query);
                    $stmt->bind_param("i", $topic_id);
                    $stmt->execute();
                    $comments_result = $stmt->get_result();
                    
                    if ($comments_result->num_rows > 0) {
                        echo "<p class='success'>Nombre de commentaires trouvés: " . $comments_result->num_rows . "</p>";
                        echo "<table>";
                        echo "<tr>
                                <th>ID</th>
                                <th>Contenu</th>
                                <th>Utilisateur</th>
                                <th>Date</th>
                              </tr>";
                        
                        while ($comment = $comments_result->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>" . $comment['id'] . "</td>";
                            echo "<td>" . htmlspecialchars(substr($comment['content'], 0, 50)) . (strlen($comment['content']) > 50 ? '...' : '') . "</td>";
                            echo "<td>" . htmlspecialchars($comment['username'] ?? 'Anonyme') . " (ID: " . $comment['user_id'] . ")</td>";
                            echo "<td>" . formatDate($comment['created_at']) . "</td>";
                            echo "</tr>";
                        }
                        echo "</table>";
                    } else {
                        echo "<p class='error'>Aucun commentaire trouvé pour ce sujet.</p>";
                    }
                    $stmt->close();
                }
                ?>
            </div>
            
            <div class="section">
                <h2>Structure de la Table des Commentaires</h2>
                <?php
                $structure = $conn->query("DESCRIBE forum_comments");
                if ($structure) {
                    echo "<table>";
                    echo "<tr><th>Champ</th><th>Type</th><th>Null</th><th>Clé</th><th>Défaut</th><th>Extra</th></tr>";
                    
                    while ($field = $structure->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>" . $field['Field'] . "</td>";
                        echo "<td>" . $field['Type'] . "</td>";
                        echo "<td>" . $field['Null'] . "</td>";
                        echo "<td>" . $field['Key'] . "</td>";
                        echo "<td>" . ($field['Default'] ? $field['Default'] : 'NULL') . "</td>";
                        echo "<td>" . $field['Extra'] . "</td>";
                        echo "</tr>";
                    }
                    echo "</table>";
                } else {
                    echo "<p class='error'>Impossible de récupérer la structure de la table.</p>";
                }
                ?>
            </div>
            
            <div class="section">
                <h2>Ajouter un Commentaire de Test</h2>
                <?php
                if (isset($_POST['add_comment']) && isset($_SESSION['user'])) {
                    $content = trim($_POST['comment_content']);
                    $user_id = $_SESSION['user_id'];
                    
                    if (!empty($content)) {
                        $insert = $conn->prepare("INSERT INTO forum_comments (topic_id, user_id, content) VALUES (?, ?, ?)");
                        $insert->bind_param("iis", $topic_id, $user_id, $content);
                        
                        if ($insert->execute()) {
                            echo "<p class='success'>Commentaire ajouté avec succès! (ID: " . $conn->insert_id . ")</p>";
                        } else {
                            echo "<p class='error'>Erreur lors de l'ajout du commentaire: " . $insert->error . "</p>";
                        }
                        $insert->close();
                    } else {
                        echo "<p class='error'>Le contenu du commentaire ne peut pas être vide.</p>";
                    }
                }
                
                if (isset($_SESSION['user'])) {
                    ?>
                    <form method="POST" action="?topic_id=<?php echo $topic_id; ?>">
                        <div class="form-group">
                            <label for="comment_content">Contenu du commentaire:</label>
                            <textarea id="comment_content" name="comment_content" rows="4" style="width: 100%; background-color: #222; color: #fff; border: 1px solid #444; padding: 10px; border-radius: 4px;" required></textarea>
                        </div>
                        <button type="submit" name="add_comment">Ajouter Commentaire</button>
                    </form>
                    <?php
                } else {
                    echo "<p>Connectez-vous pour ajouter un commentaire de test.</p>";
                }
                ?>
            </div>
        <?php else: ?>
            <p>Veuillez saisir un ID de sujet pour voir ses commentaires.</p>
        <?php endif; ?>
    </div>
    
    <?php include_once __DIR__ . '/../partials/footer.php'; ?>
</body>
</html> 