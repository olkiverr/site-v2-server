<?php
require_once __DIR__ . '/../php/db.php';
require_once __DIR__ . '/../php/forum/forum_functions.php';

// Activer l'affichage des erreurs
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Vérification des sujets dans la base de données</h1>";

// Vérifier la structure de la table forum_topics
echo "<h2>Structure de la table forum_topics</h2>";
$result = $conn->query("DESCRIBE forum_topics");
if ($result) {
    echo "<table border='1'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
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
    echo "<p>Erreur lors de la récupération de la structure de la table: " . $conn->error . "</p>";
}

// Récupérer tous les sujets
echo "<h2>Liste de tous les sujets</h2>";
$result = $conn->query("SELECT * FROM forum_topics ORDER BY id ASC LIMIT 10");
if ($result) {
    if ($result->num_rows > 0) {
        echo "<table border='1'>";
        echo "<tr><th>ID</th><th>Community ID</th><th>User ID</th><th>Title</th><th>Content</th><th>Created At</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['id'] . "</td>";
            echo "<td>" . $row['community_id'] . "</td>";
            echo "<td>" . $row['user_id'] . "</td>";
            echo "<td>" . htmlspecialchars($row['title']) . "</td>";
            echo "<td>" . htmlspecialchars(substr($row['content'], 0, 50)) . "...</td>";
            echo "<td>" . $row['created_at'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>Aucun sujet trouvé dans la base de données.</p>";
    }
} else {
    echo "<p>Erreur lors de la récupération des sujets: " . $conn->error . "</p>";
}

// Tester la fonction getTopic avec quelques IDs
echo "<h2>Test de la fonction getTopic</h2>";
for ($i = 1; $i <= 5; $i++) {
    echo "<h3>Test avec ID = $i</h3>";
    $topic = getTopic($i);
    if ($topic) {
        echo "<pre>";
        print_r($topic);
        echo "</pre>";
    } else {
        echo "<p>Aucun sujet trouvé avec l'ID $i</p>";
    }
}

// Vérifier les communautés
echo "<h2>List of Communities</h2>";
$result = $conn->query("SELECT * FROM forum_communities ORDER BY id ASC");
if ($result) {
    if ($result->num_rows > 0) {
        echo "<table border='1'>";
        echo "<tr><th>ID</th><th>Name</th><th>Slug</th><th>Description</th><th>User ID</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['id'] . "</td>";
            echo "<td>" . htmlspecialchars($row['name']) . "</td>";
            echo "<td>" . htmlspecialchars($row['slug']) . "</td>";
            echo "<td>" . htmlspecialchars($row['description']) . "</td>";
            echo "<td>" . $row['user_id'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No community found in the database.</p>";
    }
} else {
    echo "<p>Error retrieving communities: " . $conn->error . "</p>";
}

// Afficher un formulaire pour créer un sujet de test
echo "<h2>Créer un sujet de test</h2>";
$communities = $conn->query("SELECT id, name FROM forum_communities ORDER BY name");
if ($communities && $communities->num_rows > 0) {
    echo "<form method='post' action=''>";
    echo "<div>";
    echo "<label for='community_id'>Community:</label>";
    echo "<select name='community_id' id='community_id'>";
    while ($community = $communities->fetch_assoc()) {
        echo "<option value='" . $community['id'] . "'>" . htmlspecialchars($community['name']) . "</option>";
    }
    echo "</select>";
    echo "</div>";
    echo "<div style='margin-top: 10px;'>";
    echo "<label for='title'>Titre:</label>";
    echo "<input type='text' name='title' id='title' value='Sujet de test' style='width: 300px;' required>";
    echo "</div>";
    echo "<div style='margin-top: 10px;'>";
    echo "<label for='content'>Contenu:</label>";
    echo "<textarea name='content' id='content' rows='5' style='width: 300px;' required>Ceci est un sujet de test pour vérifier le fonctionnement du forum.</textarea>";
    echo "</div>";
    echo "<div style='margin-top: 10px;'>";
    echo "<button type='submit' name='create_topic'>Créer un sujet de test</button>";
    echo "</div>";
    echo "</form>";
    
    // Traiter la soumission du formulaire
    if (isset($_POST['create_topic'])) {
        $community_id = intval($_POST['community_id']);
        $title = trim($_POST['title']);
        $content = trim($_POST['content']);
        $user_id = 1; // Admin par défaut
        
        $stmt = $conn->prepare("INSERT INTO forum_topics (community_id, user_id, title, content) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiss", $community_id, $user_id, $title, $content);
        
        if ($stmt->execute()) {
            $topic_id = $conn->insert_id;
            echo "<p style='color: green;'>Sujet créé avec succès ! ID: $topic_id</p>";
            echo "<p><a href='view_topic.php?id=$topic_id' target='_blank'>Voir le sujet</a></p>";
        } else {
            echo "<p style='color: red;'>Erreur lors de la création du sujet: " . $stmt->error . "</p>";
        }
    }
} else {
    echo "<p>No community available to create a topic.</p>";
}

// Ajouter un formulaire pour tester directement un ID de sujet
echo "<h2>Tester un ID de sujet existant</h2>";
echo "<form method='get' action='view_topic.php'>";
echo "<div style='margin-bottom: 10px;'>";
echo "<label for='test_id'>ID du sujet à tester:</label>";
echo "<input type='number' name='id' id='test_id' min='1' value='1' style='margin-left: 10px;'>";
echo "</div>";
echo "<button type='submit'>Tester cet ID</button>";
echo "</form>";

echo "<h2>Liens directs pour tester</h2>";
echo "<ul>";
echo "<li><a href='view_topic.php?id=1' target='_blank'>Tester view_topic.php avec ID=1</a></li>";
echo "<li><a href='forum_topic.php?id=1' target='_blank'>Tester forum_topic.php avec ID=1</a></li>";
echo "</ul>";

?> 