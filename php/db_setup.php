<?php
// Ce script configure la base de données pour le site MangaMuse
// ATTENTION: À exécuter UNIQUEMENT manuellement pour des raisons de sécurité

// Vérification de sécurité - Ce script doit être lancé explicitement
if (!isset($_GET['setup_confirm']) || $_GET['setup_confirm'] !== 'true') {
    echo '<html>
        <head>
            <title>MangaMuse - Database Setup</title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    background-color: #1a1a1a;
                    color: white;
                    max-width: 900px;
                    margin: 0 auto;
                    padding: 20px;
                }
                h1, h2 {
                    color: #5e72e4;
                }
                pre {
                    background-color: #333;
                    padding: 15px;
                    border-radius: 5px;
                    overflow-x: auto;
                }
                .warning {
                    background-color: #ff46461a;
                    border-left: 4px solid #ff4646;
                    padding: 10px 15px;
                    margin: 20px 0;
                }
                .btn {
                    display: inline-block;
                    background-color: #5e72e4;
                    color: white;
                    text-decoration: none;
                    padding: 10px 20px;
                    border-radius: 4px;
                    margin-top: 20px;
                }
                .btn:hover {
                    background-color: #4a5fd1;
                }
                .actions {
                    margin-top: 30px;
                }
            </style>
        </head>
        <body>
            <h1>MangaMuse - Configuration de la base de données</h1>
            
            <div class="warning">
                <h2>⚠️ Attention</h2>
                <p>Ce script va configurer ou reconfigurer la base de données pour MangaMuse.</p>
                <p>Cette action va créer/recréer toutes les tables nécessaires. Si les tables existent déjà, <strong>TOUTES LES DONNÉES SERONT PERDUES</strong>.</p>
                <p>Ne continuez que si vous comprenez les conséquences.</p>
            </div>
            
            <h2>Actions qui seront effectuées :</h2>
            <ul>
                <li>Création/Recréation des tables pour les utilisateurs</li>
                <li>Création/Recréation des tables pour les animes</li>
                <li>Création/Recréation des tables pour les statuts</li>
                <li>Création/Recréation des tables pour le forum</li>
                <li>Création d\'un compte administrateur par défaut</li>
            </ul>
            
            <div class="actions">
                <a href="?setup_confirm=true" class="btn">J\'ai compris - Configurer la base de données</a>
            </div>
        </body>
    </html>';
    exit;
}

// Si le code continue ici, c'est que la confirmation a été donnée

// Connexion à la base de données
require_once 'db.php';

// Fonction pour exécuter une requête SQL avec gestion d'erreur
function executeQuery($conn, $sql, $description) {
    echo "<p>Exécution: $description... ";
    if ($conn->query($sql) === TRUE) {
        echo "<span style='color: #4CAF50;'>Réussi</span></p>";
        return true;
    } else {
        echo "<span style='color: #F44336;'>Erreur: " . $conn->error . "</span></p>";
        return false;
    }
}

// Début de la page de résultats
echo '<html>
    <head>
        <title>MangaMuse - Database Setup</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                background-color: #1a1a1a;
                color: white;
                max-width: 900px;
                margin: 0 auto;
                padding: 20px;
            }
            h1, h2 {
                color: #5e72e4;
            }
            pre {
                background-color: #333;
                padding: 15px;
                border-radius: 5px;
                overflow-x: auto;
            }
            .success {
                background-color: #4caf501a;
                border-left: 4px solid #4CAF50;
                padding: 10px 15px;
                margin: 20px 0;
            }
            .error {
                background-color: #f443361a;
                border-left: 4px solid #F44336;
                padding: 10px 15px;
                margin: 20px 0;
            }
            .btn {
                display: inline-block;
                background-color: #5e72e4;
                color: white;
                text-decoration: none;
                padding: 10px 20px;
                border-radius: 4px;
                margin-top: 20px;
            }
        </style>
    </head>
    <body>
        <h1>Configuration de la base de données MangaMuse</h1>
        <h2>Rapport d\'exécution</h2>
';

// Tableaux pour suivre les succès et échecs
$success = true;

// 1. Créer la table users (si elle n'existe pas déjà)
$sql_users = "CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    is_admin TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";
$success = executeQuery($conn, $sql_users, "Création de la table users") && $success;

// 2. Créer la table pages (pour les animes)
$sql_pages = "CREATE TABLE IF NOT EXISTS pages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    creator VARCHAR(100),
    broadcast VARCHAR(100),
    img VARCHAR(255),
    description TEXT,
    episodes VARCHAR(50),
    studio VARCHAR(100),
    genres VARCHAR(255),
    style TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";
$success = executeQuery($conn, $sql_pages, "Création de la table pages") && $success;

// 3. Créer la table user_anime_status
$sql_status = "CREATE TABLE IF NOT EXISTS user_anime_status (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    anime_id INT NOT NULL,
    is_favorite TINYINT(1) DEFAULT 0,
    is_watched TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (anime_id) REFERENCES pages(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_anime (user_id, anime_id)
)";
$success = executeQuery($conn, $sql_status, "Création de la table user_anime_status") && $success;

// 4. Inclure et exécuter le script de configuration du forum existant
// On stocke la sortie dans un buffer pour pouvoir l'afficher proprement
echo "<h3>Configuration des tables du forum</h3>";

// Création des tables du forum
// a. Table des communautés
$sql_communities = "CREATE TABLE IF NOT EXISTS forum_communities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    user_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    image_url VARCHAR(255) DEFAULT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
)";
$success = executeQuery($conn, $sql_communities, "Création de la table forum_communities") && $success;

// b. Table des sujets (topics)
$sql_topics = "CREATE TABLE IF NOT EXISTS forum_topics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    community_id INT,
    user_id INT,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    views INT DEFAULT 0,
    image_url VARCHAR(255) DEFAULT NULL,
    FOREIGN KEY (community_id) REFERENCES forum_communities(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
)";
$success = executeQuery($conn, $sql_topics, "Création de la table forum_topics") && $success;

// c. Table des commentaires
$sql_comments = "CREATE TABLE IF NOT EXISTS forum_comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    topic_id INT NOT NULL,
    user_id INT,
    parent_id INT DEFAULT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    edited_at TIMESTAMP NULL DEFAULT NULL,
    vote_score INT DEFAULT 0,
    FOREIGN KEY (topic_id) REFERENCES forum_topics(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (parent_id) REFERENCES forum_comments(id) ON DELETE CASCADE
)";
$success = executeQuery($conn, $sql_comments, "Création de la table forum_comments") && $success;

// d. Table des votes
$sql_votes = "CREATE TABLE IF NOT EXISTS forum_votes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    reference_id INT,
    reference_type ENUM('topic', 'comment'),
    vote_type TINYINT NOT NULL, /* 1 pour upvote, -1 pour downvote */
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_vote (user_id, reference_id, reference_type),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";
$success = executeQuery($conn, $sql_votes, "Création de la table forum_votes") && $success;

// e. Table des vues (optionnel)
$sql_views = "CREATE TABLE IF NOT EXISTS forum_views (
    id INT AUTO_INCREMENT PRIMARY KEY,
    topic_id INT,
    user_id INT NULL,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (topic_id) REFERENCES forum_topics(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";
$success = executeQuery($conn, $sql_views, "Création de la table forum_views") && $success;

// Insertion des communautés par défaut
$check_communities = "SELECT COUNT(*) as count FROM forum_communities";
$result = $conn->query($check_communities);
$row = $result->fetch_assoc();

if ($row['count'] == 0) {
    // Communautés par défaut
    $default_communities = [
        ['name' => 'Shonen Anime', 'slug' => 'shonen-anime', 'description' => 'Discuss popular shonen anime like Naruto, One Piece, Dragon Ball, etc.', 'user_id' => NULL],
        ['name' => 'Shojo Anime', 'slug' => 'shojo-anime', 'description' => 'Discuss shojo anime series and movies', 'user_id' => NULL],
        ['name' => 'Seinen Anime', 'slug' => 'seinen-anime', 'description' => 'Discuss seinen anime targeted towards adult men', 'user_id' => NULL],
        ['name' => 'Isekai Anime', 'slug' => 'isekai-anime', 'description' => 'Discuss isekai (another world) anime series', 'user_id' => NULL],
        ['name' => 'Studio Ghibli', 'slug' => 'studio-ghibli', 'description' => 'Discuss films and works from Studio Ghibli', 'user_id' => NULL],
        ['name' => 'Upcoming Releases', 'slug' => 'upcoming-releases', 'description' => 'Discuss upcoming anime releases and announcements', 'user_id' => NULL],
        ['name' => 'Anime News', 'slug' => 'anime-news', 'description' => 'Share and discuss the latest anime industry news', 'user_id' => NULL]
    ];
    
    $stmt = $conn->prepare("INSERT INTO forum_communities (name, slug, description, user_id) VALUES (?, ?, ?, ?)");
    
    foreach ($default_communities as $community) {
        $stmt->bind_param("sssi", $community['name'], $community['slug'], $community['description'], $community['user_id']);
        $stmt->execute();
    }
    
    echo "<p>Insertion des communautés par défaut... <span style='color: #4CAF50;'>Réussi</span></p>";
    $stmt->close();
}

// 5. Créer un utilisateur admin par défaut (si aucun admin n'existe)
$check_admin = "SELECT COUNT(*) as count FROM users WHERE is_admin = 1";
$result = $conn->query($check_admin);
$row = $result->fetch_assoc();

if ($row['count'] == 0) {
    // Créer un admin par défaut
    $admin_username = "admin";
    $admin_password = password_hash("admin123", PASSWORD_DEFAULT); // Utiliser un mot de passe plus sécurisé en production
    $admin_email = "admin@mangamuse.com";
    
    // Replace direct SQL query for admin creation with a call to the stored procedure
    $stmt = $conn->prepare("CALL AddUser(?, ?, ?, ?)");
    $stmt->bind_param("sssi", $admin_username, $admin_email, $admin_password, 1);
    $stmt->execute();
    $stmt->close();
    
    echo "<div class='success'>
        <p><strong>Informations de connexion administrateur par défaut :</strong></p>
        <p>Nom d'utilisateur : admin</p>
        <p>Mot de passe : admin123</p>
        <p><strong>Important :</strong> Veuillez changer ce mot de passe immédiatement après vous être connecté.</p>
    </div>";
}

// Résultat final
if ($success) {
    echo "<div class='success'>
        <h2>✅ Configuration réussie</h2>
        <p>Toutes les tables ont été créées avec succès.</p>
    </div>";
} else {
    echo "<div class='error'>
        <h2>❌ Erreurs détectées</h2>
        <p>Certaines opérations ont échoué. Vérifiez les messages d'erreur ci-dessus.</p>
    </div>";
}

echo '<a href="../index.php" class="btn">Retour à l\'accueil</a>
    </body>
</html>';
?>