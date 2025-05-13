<?php
require_once __DIR__ . '/../php/db.php';

// Remplacer la vérification de session par l'inclusion de la configuration
include_once '../php/session_config.php';

// Fonction d'affichage formaté
function printTableRow($col1, $col2, $success = true) {
    echo '<tr>';
    echo '<td>' . htmlspecialchars($col1) . '</td>';
    echo '<td class="' . ($success ? 'success' : 'error') . '">' . htmlspecialchars($col2) . '</td>';
    echo '</tr>';
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Debug</title>
    <link rel="stylesheet" href="/4TTJ/Zielinski%20Olivier/Site/site-v2/css/header.css">
    <link rel="stylesheet" href="/4TTJ/Zielinski%20Olivier/Site/site-v2/css/footer.css">
    <style>
        body { font-family: Arial, sans-serif; background-color: #2a2a2a; color: #f0f0f0; padding: 20px; }
        .debug-container { max-width: 1000px; margin: 0 auto; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #444; }
        th { background-color: #333; }
        .success { color: #4CAF50; }
        .error { color: #f44336; }
        .warning { color: #ff9800; }
        .section { margin-top: 30px; }
        h2 { border-bottom: 2px solid #444; padding-bottom: 10px; }
        .actions { margin-top: 20px; }
        .btn { 
            display: inline-block;
            padding: 10px 15px;
            margin-right: 10px;
            background-color: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <?php include_once __DIR__ . '/../partials/header.php'; ?>
    
    <div class="debug-container">
        <h1>Diagnostic Base de données - Forum</h1>
        
        <div class="section">
            <h2>Vérification des Tables</h2>
            <table>
                <tr>
                    <th>Table</th>
                    <th>Statut</th>
                </tr>
                <?php
                // Vérifier l'existence des tables
                $tables = [
                    'forum_communities',
                    'forum_topics',
                    'forum_comments',
                    'forum_votes'
                ];
                
                foreach ($tables as $table) {
                    $result = $conn->query("SHOW TABLES LIKE '$table'");
                    $exists = $result->num_rows > 0;
                    printTableRow($table, $exists ? "Présente" : "Manquante", $exists);
                }
                ?>
            </table>
        </div>
        
        <div class="section">
            <h2>Structure des Tables</h2>
            <table>
                <tr>
                    <th>Champ</th>
                    <th>Statut</th>
                </tr>
                <?php
                // Vérifier la structure des tables
                if ($result = $conn->query("SHOW TABLES LIKE 'forum_communities'")) {
                    if ($result->num_rows > 0) {
                        $fields = $conn->query("DESCRIBE forum_communities");
                        $expected_fields = ['id', 'name', 'slug', 'description', 'user_id', 'created_at'];
                        $found_fields = [];
                        
                        while ($field = $fields->fetch_assoc()) {
                            $found_fields[] = $field['Field'];
                        }
                        
                        foreach ($expected_fields as $field) {
                            $exists = in_array($field, $found_fields);
                            printTableRow("forum_communities.$field", $exists ? "Présent" : "Manquant", $exists);
                        }
                    }
                }
                
                if ($result = $conn->query("SHOW TABLES LIKE 'forum_topics'")) {
                    if ($result->num_rows > 0) {
                        $fields = $conn->query("DESCRIBE forum_topics");
                        $expected_fields = ['id', 'title', 'content', 'user_id', 'community_id', 'created_at', 'views'];
                        $found_fields = [];
                        
                        while ($field = $fields->fetch_assoc()) {
                            $found_fields[] = $field['Field'];
                        }
                        
                        foreach ($expected_fields as $field) {
                            $exists = in_array($field, $found_fields);
                            printTableRow("forum_topics.$field", $exists ? "Présent" : "Manquant", $exists);
                        }
                    }
                }
                ?>
            </table>
        </div>
        
        <div class="section">
            <h2>Données</h2>
            <table>
                <tr>
                    <th>Information</th>
                    <th>Statut</th>
                </tr>
                <?php
                // Compter les données dans les tables
                $stats = [];
                
                // Communautés
                $result = $conn->query("SELECT COUNT(*) as count FROM forum_communities");
                if ($result) {
                    $row = $result->fetch_assoc();
                    $count = $row['count'];
                    $stats['communities'] = $count;
                    printTableRow("Number of communities", $count, $count > 0);
                } else {
                    printTableRow("Number of communities", "Query error", false);
                }
                
                // Topics
                $result = $conn->query("SELECT COUNT(*) as count FROM forum_topics");
                if ($result) {
                    $row = $result->fetch_assoc();
                    $count = $row['count'];
                    $stats['topics'] = $count;
                    printTableRow("Number of topics", $count, $count > 0);
                } else {
                    printTableRow("Number of topics", "Query error", false);
                }
                
                // Commentaires
                $result = $conn->query("SELECT COUNT(*) as count FROM forum_comments");
                if ($result) {
                    $row = $result->fetch_assoc();
                    $count = $row['count'];
                    $stats['comments'] = $count;
                    printTableRow("Number of comments", $count, true);
                } else {
                    printTableRow("Number of comments", "Query error", false);
                }
                
                // Votes
                $result = $conn->query("SELECT COUNT(*) as count FROM forum_votes");
                if ($result) {
                    $row = $result->fetch_assoc();
                    $count = $row['count'];
                    $stats['votes'] = $count;
                    printTableRow("Number of votes", $count, true);
                } else {
                    printTableRow("Number of votes", "Query error", false);
                }
                
                // Vérifier les topics sans communauté
                $result = $conn->query("SELECT COUNT(*) as count FROM forum_topics WHERE community_id NOT IN (SELECT id FROM forum_communities)");
                if ($result) {
                    $row = $result->fetch_assoc();
                    $count = $row['count'];
                    printTableRow("Topics without valid community", $count, $count == 0);
                } else {
                    printTableRow("Topics without valid community", "Query error", false);
                }
                ?>
            </table>
        </div>
        
        <div class="section">
            <h2>List of Communities</h2>
            <table>
                <tr>
                    <th>ID</th>
                    <th>Nom</th>
                    <th>Slug</th>
                    <th>Description</th>
                    <th>Date de création</th>
                </tr>
                <?php
                $result = $conn->query("SELECT * FROM forum_communities ORDER BY id ASC");
                if ($result && $result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>" . $row['id'] . "</td>";
                        echo "<td>" . htmlspecialchars($row['name']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['slug']) . "</td>";
                        echo "<td>" . htmlspecialchars(substr($row['description'], 0, 50)) . (strlen($row['description']) > 50 ? '...' : '') . "</td>";
                        echo "<td>" . $row['created_at'] . "</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='5'>No community found</td></tr>";
                }
                ?>
            </table>
        </div>
        
        <div class="section">
            <h2>Derniers sujets créés</h2>
            <table>
                <tr>
                    <th>ID</th>
                    <th>Titre</th>
                    <th>Community ID</th>
                    <th>Date de création</th>
                    <th>Vues</th>
                </tr>
                <?php
                $result = $conn->query("SELECT * FROM forum_topics ORDER BY id DESC LIMIT 10");
                if ($result && $result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>" . $row['id'] . "</td>";
                        echo "<td>" . htmlspecialchars($row['title']) . "</td>";
                        echo "<td>" . $row['community_id'] . "</td>";
                        echo "<td>" . $row['created_at'] . "</td>";
                        echo "<td>" . $row['views'] . "</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='5'>Aucun sujet trouvé</td></tr>";
                }
                ?>
            </table>
        </div>
        
        <div class="actions">
            <a href="/4TTJ/Zielinski%20Olivier/Site/site-v2/pages/forum.php" class="btn">Retour au Forum</a>
            <a href="/4TTJ/Zielinski%20Olivier/Site/site-v2/pages/check_topics.php" class="btn">Outil de vérification des sujets</a>
        </div>
    </div>
    
    <?php include_once __DIR__ . '/../partials/footer.php'; ?>
</body>
</html> 