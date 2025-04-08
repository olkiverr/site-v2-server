<?php
require_once __DIR__ . '/../php/db.php';
require_once __DIR__ . '/../php/forum/forum_functions.php';

// Activer l'affichage des erreurs
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start the session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Get topic ID from URL
$topic_id = isset($_GET['topic_id']) ? intval($_GET['topic_id']) : 0;

// Function to format dates
function formatDate($date) {
    return date('d/m/Y H:i:s', strtotime($date));
}

// Check if user is logged in
$is_logged_in = isset($_SESSION['user']) && isset($_SESSION['user_id']);
$user_id = $is_logged_in ? $_SESSION['user_id'] : 0;

// Process test vote if requested
$vote_message = '';
if ($is_logged_in && isset($_GET['test_vote'])) {
    $vote_type = $_GET['test_vote'] === 'up' ? 1 : -1;
    $reference_type = $_GET['reference_type'] ?? 'topic';
    $reference_id = $_GET['reference_id'] ?? $topic_id;
    
    $new_score = voteOnItem($user_id, $reference_id, $reference_type, $vote_type);
    $vote_message = "Vote testé: Type=$vote_type, Référence=$reference_type, ID=$reference_id, Nouveau score=$new_score";
}

// Get topic information
$topic = null;
if ($topic_id > 0) {
    $topic = getTopic($topic_id);
}

// Get comments for the topic
$comments = [];
if ($topic_id > 0) {
    $comments = getComments($topic_id, 1, 50);
}

// Get votes for the topic
$topic_votes = [];
if ($topic_id > 0) {
    try {
        $stmt = $conn->prepare("SELECT v.*, u.username 
                                FROM forum_votes v 
                                LEFT JOIN users u ON v.user_id = u.id 
                                WHERE v.reference_id = ? AND v.reference_type = 'topic'");
        $stmt->bind_param("i", $topic_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $topic_votes[] = $row;
        }
        
        $stmt->close();
    } catch (Exception $e) {
        echo "Erreur lors de la récupération des votes du sujet: " . $e->getMessage();
    }
}

// Get votes for comments
$comment_votes = [];
if ($topic_id > 0) {
    try {
        $comment_ids = array_column($comments, 'id');
        if (!empty($comment_ids)) {
            $placeholders = str_repeat('?,', count($comment_ids) - 1) . '?';
            $stmt = $conn->prepare("SELECT v.*, u.username 
                                    FROM forum_votes v 
                                    LEFT JOIN users u ON v.user_id = u.id 
                                    WHERE v.reference_id IN ($placeholders) AND v.reference_type = 'comment'");
            
            $types = str_repeat('i', count($comment_ids));
            $stmt->bind_param($types, ...$comment_ids);
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                $comment_votes[] = $row;
            }
            
            $stmt->close();
        }
    } catch (Exception $e) {
        echo "Erreur lors de la récupération des votes des commentaires: " . $e->getMessage();
    }
}

// Check if forum_votes table exists
$table_exists = false;
try {
    $result = $conn->query("SHOW TABLES LIKE 'forum_votes'");
    $table_exists = $result->num_rows > 0;
} catch (Exception $e) {
    echo "Erreur lors de la vérification de la table forum_votes: " . $e->getMessage();
}

// Get table structure if it exists
$table_structure = [];
if ($table_exists) {
    try {
        $result = $conn->query("DESCRIBE forum_votes");
        while ($row = $result->fetch_assoc()) {
            $table_structure[] = $row;
        }
    } catch (Exception $e) {
        echo "Erreur lors de la récupération de la structure de la table: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnostic des Votes - Forum</title>
    <link rel="stylesheet" href="/4TTJ/Zielinski%20Olivier/Site/site-v2/css/header.css">
    <link rel="stylesheet" href="/4TTJ/Zielinski%20Olivier/Site/site-v2/css/footer.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background-color: #fff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h1, h2, h3 {
            color: #333;
        }
        .section {
            margin-bottom: 30px;
            padding: 15px;
            background-color: #f9f9f9;
            border-radius: 5px;
            border: 1px solid #ddd;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="text"], input[type="number"], select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        button {
            background-color: #4CAF50;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        button:hover {
            background-color: #45a049;
        }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .alert-success {
            background-color: #dff0d8;
            border: 1px solid #d6e9c6;
            color: #3c763d;
        }
        .alert-danger {
            background-color: #f2dede;
            border: 1px solid #ebccd1;
            color: #a94442;
        }
        .alert-info {
            background-color: #d9edf7;
            border: 1px solid #bce8f1;
            color: #31708f;
        }
        .debug-info {
            font-family: monospace;
            background-color: #f5f5f5;
            padding: 10px;
            border-radius: 4px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <?php include_once __DIR__ . '/../partials/header.php'; ?>
    
    <div class="container">
        <h1>Diagnostic des Votes</h1>
        
        <?php if (!empty($vote_message)): ?>
            <div class="alert alert-success">
                <?php echo $vote_message; ?>
            </div>
        <?php endif; ?>
        
        <div class="section">
            <h2>Informations sur la Table</h2>
            <?php if ($table_exists): ?>
                <div class="alert alert-success">
                    La table forum_votes existe.
                </div>
                
                <h3>Structure de la Table</h3>
                <table>
                    <tr>
                        <th>Champ</th>
                        <th>Type</th>
                        <th>Null</th>
                        <th>Clé</th>
                        <th>Défaut</th>
                        <th>Extra</th>
                    </tr>
                    <?php foreach ($table_structure as $field): ?>
                        <tr>
                            <td><?php echo $field['Field']; ?></td>
                            <td><?php echo $field['Type']; ?></td>
                            <td><?php echo $field['Null']; ?></td>
                            <td><?php echo $field['Key']; ?></td>
                            <td><?php echo $field['Default']; ?></td>
                            <td><?php echo $field['Extra']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            <?php else: ?>
                <div class="alert alert-danger">
                    La table forum_votes n'existe pas. Veuillez vérifier la structure de la base de données.
                </div>
            <?php endif; ?>
        </div>
        
        <div class="section">
            <h2>Sélectionner un Sujet</h2>
            <form method="get" action="">
                <div class="form-group">
                    <label for="topic_id">ID du Sujet:</label>
                    <input type="number" id="topic_id" name="topic_id" value="<?php echo $topic_id; ?>" required>
                </div>
                <button type="submit">Afficher les Votes</button>
            </form>
        </div>
        
        <?php if ($topic_id > 0): ?>
            <div class="section">
                <h2>Informations sur le Sujet</h2>
                <?php if ($topic): ?>
                    <table>
                        <tr>
                            <th>ID</th>
                            <td><?php echo $topic['id']; ?></td>
                        </tr>
                        <tr>
                            <th>Titre</th>
                            <td><?php echo htmlspecialchars($topic['title']); ?></td>
                        </tr>
                        <tr>
                            <th>Auteur</th>
                            <td><?php echo htmlspecialchars($topic['username'] ?? 'Anonyme'); ?></td>
                        </tr>
                        <tr>
                            <th>Date de création</th>
                            <td><?php echo isset($topic['created_at']) ? formatDate($topic['created_at']) : 'Non disponible'; ?></td>
                        </tr>
                        <tr>
                            <th>Score de vote</th>
                            <td><?php echo $topic['vote_score'] ?? 0; ?></td>
                        </tr>
                    </table>
                    
                    <h3>Votes sur ce Sujet</h3>
                    <?php if (!empty($topic_votes)): ?>
                        <table>
                            <tr>
                                <th>ID</th>
                                <th>Utilisateur</th>
                                <th>Type de Vote</th>
                                <th>Date</th>
                            </tr>
                            <?php foreach ($topic_votes as $vote): ?>
                                <tr>
                                    <td><?php echo $vote['id']; ?></td>
                                    <td><?php echo htmlspecialchars($vote['username'] ?? 'Anonyme'); ?></td>
                                    <td><?php echo $vote['vote_type'] == 1 ? 'Positif' : 'Négatif'; ?></td>
                                    <td><?php echo formatDate($vote['created_at']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </table>
                    <?php else: ?>
                        <p>Aucun vote pour ce sujet.</p>
                    <?php endif; ?>
                    
                    <?php if ($is_logged_in): ?>
                        <h3>Tester un Vote sur ce Sujet</h3>
                        <form method="get" action="">
                            <input type="hidden" name="topic_id" value="<?php echo $topic_id; ?>">
                            <input type="hidden" name="reference_type" value="topic">
                            <input type="hidden" name="reference_id" value="<?php echo $topic_id; ?>">
                            <div class="form-group">
                                <label>Type de Vote:</label>
                                <select name="test_vote">
                                    <option value="up">Positif</option>
                                    <option value="down">Négatif</option>
                                </select>
                            </div>
                            <button type="submit">Tester le Vote</button>
                        </form>
                    <?php else: ?>
                        <div class="alert alert-info">
                            Connectez-vous pour tester les votes.
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="alert alert-danger">
                        Sujet non trouvé.
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="section">
                <h2>Commentaires et Votes</h2>
                <?php if (!empty($comments)): ?>
                    <h3>Liste des Commentaires</h3>
                    <table>
                        <tr>
                            <th>ID</th>
                            <th>Auteur</th>
                            <th>Contenu</th>
                            <th>Date</th>
                            <th>Score de Vote</th>
                            <th>Actions</th>
                        </tr>
                        <?php foreach ($comments as $comment): ?>
                            <tr>
                                <td><?php echo $comment['id']; ?></td>
                                <td><?php echo htmlspecialchars($comment['username'] ?? 'Anonyme'); ?></td>
                                <td><?php echo htmlspecialchars(substr($comment['content'], 0, 50)) . (strlen($comment['content']) > 50 ? '...' : ''); ?></td>
                                <td><?php echo formatDate($comment['created_at']); ?></td>
                                <td><?php echo $comment['vote_score'] ?? 0; ?></td>
                                <td>
                                    <?php if ($is_logged_in): ?>
                                        <form method="get" action="" style="display: inline;">
                                            <input type="hidden" name="topic_id" value="<?php echo $topic_id; ?>">
                                            <input type="hidden" name="reference_type" value="comment">
                                            <input type="hidden" name="reference_id" value="<?php echo $comment['id']; ?>">
                                            <input type="hidden" name="test_vote" value="up">
                                            <button type="submit" style="padding: 5px; background-color: #4CAF50;">+</button>
                                        </form>
                                        <form method="get" action="" style="display: inline;">
                                            <input type="hidden" name="topic_id" value="<?php echo $topic_id; ?>">
                                            <input type="hidden" name="reference_type" value="comment">
                                            <input type="hidden" name="reference_id" value="<?php echo $comment['id']; ?>">
                                            <input type="hidden" name="test_vote" value="down">
                                            <button type="submit" style="padding: 5px; background-color: #f44336;">-</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                    
                    <h3>Votes sur les Commentaires</h3>
                    <?php if (!empty($comment_votes)): ?>
                        <table>
                            <tr>
                                <th>ID</th>
                                <th>ID Commentaire</th>
                                <th>Utilisateur</th>
                                <th>Type de Vote</th>
                                <th>Date</th>
                            </tr>
                            <?php foreach ($comment_votes as $vote): ?>
                                <tr>
                                    <td><?php echo $vote['id']; ?></td>
                                    <td><?php echo $vote['reference_id']; ?></td>
                                    <td><?php echo htmlspecialchars($vote['username'] ?? 'Anonyme'); ?></td>
                                    <td><?php echo $vote['vote_type'] == 1 ? 'Positif' : 'Négatif'; ?></td>
                                    <td><?php echo formatDate($vote['created_at']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </table>
                    <?php else: ?>
                        <p>Aucun vote pour les commentaires de ce sujet.</p>
                    <?php endif; ?>
                <?php else: ?>
                    <p>Aucun commentaire pour ce sujet.</p>
                <?php endif; ?>
            </div>
            
            <div class="section">
                <h2>Informations de Débogage</h2>
                <h3>Fonction getUserVote</h3>
                <?php if ($is_logged_in): ?>
                    <p>Vote de l'utilisateur actuel sur le sujet: <?php echo getUserVote($user_id, $topic_id, 'topic'); ?></p>
                    
                    <h4>Votes sur les commentaires:</h4>
                    <ul>
                        <?php foreach ($comments as $comment): ?>
                            <li>Commentaire #<?php echo $comment['id']; ?>: <?php echo getUserVote($user_id, $comment['id'], 'comment'); ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p>Connectez-vous pour voir vos votes.</p>
                <?php endif; ?>
                
                <h3>Requête SQL pour les votes du sujet</h3>
                <div class="debug-info">
                    SELECT v.*, u.username FROM forum_votes v LEFT JOIN users u ON v.user_id = u.id WHERE v.reference_id = <?php echo $topic_id; ?> AND v.reference_type = 'topic'
                </div>
                
                <h3>Requête SQL pour le score de vote du sujet</h3>
                <div class="debug-info">
                    SELECT COALESCE(SUM(vote_type), 0) as vote_score FROM forum_votes WHERE reference_id = <?php echo $topic_id; ?> AND reference_type = 'topic'
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <?php include_once __DIR__ . '/../partials/footer.php'; ?>
</body>
</html> 