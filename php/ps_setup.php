<?php
// Ce script configure les procédures stockées pour la base de données MangaMuse
// Attention : à exécuter avec précaution

// Vérification de sécurité - Ce script doit être lancé explicitement
if (!isset($_GET['setup_confirm']) || $_GET['setup_confirm'] !== 'true') {
    echo '<html>
        <head>
            <title>MangaMuse - Stored Procedures Setup</title>
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
                .btn:hover {
                    background-color: #4a5fd1;
                }
                .actions {
                    margin-top: 30px;
                }
            </style>
        </head>
        <body>
            <h1>MangaMuse - Configuration des procédures stockées</h1>
            
            <div class="warning">
                <h2>⚠️ Attention</h2>
                <p>Ce script va configurer ou reconfigurer les procédures stockées pour MangaMuse.</p>
                <p>Cette action va créer/recréer toutes les procédures. Si elles existent déjà, <strong>ELLES SERONT REMPLACÉES</strong>.</p>
                <p>Ne continuez que si vous comprenez les conséquences.</p>
            </div>
            
            <h2>Actions qui seront effectuées :</h2>
            <ul>
                <li>Création/Recréation de la procédure AddUser</li>
                <li>Création/Recréation des procédures pour le forum</li>
                <li>Création/Recréation des procédures de gestion des pages</li>
            </ul>
            
            <div class="actions">
                <a href="?setup_confirm=true" class="btn">J\'ai compris - Configurer les procédures stockées</a>
            </div>
        </body>
    </html>';
    exit;
}

// Connexion à la base de données
require_once 'db.php';

// Fonction pour exécuter une requête SQL avec gestion d'erreur
function executeStoredProcedure($conn, $sql, $description) {
    echo "<p>Configuration : $description... ";
    if ($conn->multi_query($sql) === TRUE) {
        // Vider les résultats restants
        while ($conn->more_results()) {
            $conn->next_result();
        }
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
        <title>MangaMuse - Stored Procedures Setup</title>
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
        <h1>Configuration des procédures stockées MangaMuse</h1>
        <h2>Rapport d\'exécution</h2>
';

$success = true;

// 1. Procédure AddUser
$sql_add_user = "
DROP PROCEDURE IF EXISTS AddUser;
CREATE PROCEDURE AddUser (
    IN p_username VARCHAR(50),
    IN p_email VARCHAR(100),
    IN p_password VARCHAR(255),
    IN p_is_admin TINYINT(1),
    IN p_created_by INT /* ID de l'utilisateur qui crée le nouvel utilisateur */
)
BEGIN
    DECLARE creator_is_admin TINYINT DEFAULT 0;
    
    /* Vérifier si le créateur existe et est admin */
    SELECT is_admin INTO creator_is_admin 
    FROM users 
    WHERE id = p_created_by;
    
    /* Si le créateur n'existe pas ou n'est pas admin */
    IF creator_is_admin = 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Accès refusé: Privilèges administrateur requis pour créer un utilisateur';
    ELSE
        /* Vérifier si le nom d'utilisateur existe déjà */
        IF EXISTS (SELECT 1 FROM users WHERE username = p_username) THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Ce nom d\'utilisateur existe déjà';
        END IF;
        
        /* Vérifier si l'email existe déjà */
        IF EXISTS (SELECT 1 FROM users WHERE email = p_email) THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Cet email est déjà utilisé';
        END IF;
        
        /* Si toutes les vérifications sont passées, créer l'utilisateur */
        INSERT INTO users (username, email, password, is_admin)
        VALUES (p_username, p_email, p_password, p_is_admin);
        
        SELECT 'Utilisateur créé avec succès' AS message, TRUE AS success;
    END IF;
END";

$success = executeStoredProcedure($conn, $sql_add_user, "Création de la procédure AddUser") && $success;

// 2. Procédure InsertForumCommunity
$sql_insert_community = "
DROP PROCEDURE IF EXISTS InsertForumCommunity;
CREATE PROCEDURE InsertForumCommunity (
    IN p_name VARCHAR(100),
    IN p_slug VARCHAR(100),
    IN p_description TEXT,
    IN p_user_id INT
)
BEGIN
    INSERT INTO forum_communities (name, slug, description, user_id)
    VALUES (p_name, p_slug, p_description, p_user_id);
END";

$success = executeStoredProcedure($conn, $sql_insert_community, "Création de la procédure InsertForumCommunity") && $success;

// 3. Procédure AddPage (pour les animes)
$sql_add_page = "
DROP PROCEDURE IF EXISTS AddPage;
CREATE PROCEDURE AddPage (
    IN p_title VARCHAR(255),
    IN p_creator VARCHAR(100),
    IN p_broadcast VARCHAR(100),
    IN p_img VARCHAR(255),
    IN p_description TEXT,
    IN p_episodes VARCHAR(50),
    IN p_studio VARCHAR(100),
    IN p_genres VARCHAR(255),
    IN p_style TEXT
)
BEGIN
    INSERT INTO pages (title, creator, broadcast, img, description, episodes, studio, genres, style)
    VALUES (p_title, p_creator, p_broadcast, p_img, p_description, p_episodes, p_studio, p_genres, p_style);
END";

$success = executeStoredProcedure($conn, $sql_add_page, "Création de la procédure AddPage") && $success;

// 4. Procédure UpdateUserStatus
$sql_update_status = "
DROP PROCEDURE IF EXISTS UpdateUserStatus;
CREATE PROCEDURE UpdateUserStatus (
    IN p_user_id INT,
    IN p_anime_id INT,
    IN p_is_favorite TINYINT(1),
    IN p_is_watched TINYINT(1)
)
BEGIN
    INSERT INTO user_anime_status (user_id, anime_id, is_favorite, is_watched)
    VALUES (p_user_id, p_anime_id, p_is_favorite, p_is_watched)
    ON DUPLICATE KEY UPDATE
        is_favorite = p_is_favorite,
        is_watched = p_is_watched;
END";

$success = executeStoredProcedure($conn, $sql_update_status, "Création de la procédure UpdateUserStatus") && $success;

// 5. Procédure InsertForumTopic
$sql_insert_forum_topic = "
DROP PROCEDURE IF EXISTS InsertForumTopic;
CREATE PROCEDURE InsertForumTopic (
    IN p_community_id INT,
    IN p_user_id INT,
    IN p_title VARCHAR(255),
    IN p_content TEXT
)
BEGIN
    INSERT INTO forum_topics (community_id, user_id, title, content)
    VALUES (p_community_id, p_user_id, p_title, p_content);
END";

$success = executeStoredProcedure($conn, $sql_insert_forum_topic, "Création de la procédure InsertForumTopic") && $success;

// 6. Procédure InsertNewUserStat
$sql_insert_new_user_stat = "
DROP PROCEDURE IF EXISTS InsertNewUserStat;
CREATE PROCEDURE InsertNewUserStat (
    IN p_date DATE
)
BEGIN
    INSERT INTO new_users (date, count)
    VALUES (p_date, 1)
    ON DUPLICATE KEY UPDATE
        count = count + 1;
END";

$success = executeStoredProcedure($conn, $sql_insert_new_user_stat, "Création de la procédure InsertNewUserStat") && $success;

// 7. Procédure InsertSiteVisit
$sql_insert_site_visit = "
DROP PROCEDURE IF EXISTS InsertSiteVisit;
CREATE PROCEDURE InsertSiteVisit (
    IN p_date DATE
)
BEGIN
    INSERT INTO site_visits (date, count)
    VALUES (p_date, 1)
    ON DUPLICATE KEY UPDATE
        count = count + 1;
END";

$success = executeStoredProcedure($conn, $sql_insert_site_visit, "Création de la procédure InsertSiteVisit") && $success;

// 8. Procédure insert_data_admin_only
$sql_admin_insert = "
DROP PROCEDURE IF EXISTS insert_data_admin_only;
CREATE PROCEDURE insert_data_admin_only(
    IN p_user_id INT,
    IN p_table_name VARCHAR(50),
    IN p_column_names TEXT,
    IN p_values TEXT
)
BEGIN
    DECLARE is_user_admin TINYINT DEFAULT 0;
    DECLARE sql_query TEXT;
    
    SELECT is_admin INTO is_user_admin 
    FROM users 
    WHERE id = p_user_id;
    
    IF is_user_admin = 1 THEN
        SET @sql_query = CONCAT('INSERT INTO ', p_table_name, ' (', p_column_names, ') VALUES (', p_values, ')');
        
        PREPARE stmt FROM @sql_query;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
        
        SELECT 'Insertion réussie' AS message, TRUE AS success;
    ELSE
        SELECT 'Accès refusé: Privilèges administrateur requis' AS message, FALSE AS success;
    END IF;
END";

$success = executeStoredProcedure($conn, $sql_admin_insert, "Création de la procédure insert_data_admin_only") && $success;

// 9. Procédure insert_page_admin_only
$sql_admin_insert_pages = "
DROP PROCEDURE IF EXISTS insert_page_admin_only;
CREATE PROCEDURE insert_page_admin_only(
    IN p_user_id INT,
    IN p_title VARCHAR(255),
    IN p_content TEXT,
    IN p_category VARCHAR(50),
    IN p_status VARCHAR(20)
)
BEGIN
    DECLARE is_user_admin TINYINT DEFAULT 0;
    
    SELECT is_admin INTO is_user_admin 
    FROM users 
    WHERE id = p_user_id;
    
    IF is_user_admin = 1 THEN
        INSERT INTO pages (
            title, 
            content, 
            category, 
            status, 
            created_at, 
            updated_at, 
            created_by
        ) VALUES (
            p_title, 
            p_content, 
            p_category, 
            p_status, 
            NOW(), 
            NOW(), 
            p_user_id
        );
        
        SELECT 
            'Page créée avec succès' AS message, 
            TRUE AS success, 
            LAST_INSERT_ID() AS page_id;
    ELSE
        SELECT 
            'Accès refusé: Privilèges administrateur requis pour créer une page' AS message, 
            FALSE AS success, 
            NULL AS page_id;
    END IF;
END";

$success = executeStoredProcedure($conn, $sql_admin_insert_pages, "Création de la procédure insert_page_admin_only") && $success;

// 10. Procédure DeleteUser
$sql_delete_user = "
DROP PROCEDURE IF EXISTS DeleteUser;
CREATE PROCEDURE DeleteUser (
    IN p_user_id INT,          /* ID de l'utilisateur à supprimer */
    IN p_admin_id INT          /* ID de l'admin qui fait la suppression */
)
BEGIN
    DECLARE is_admin TINYINT DEFAULT 0;
    DECLARE target_is_admin TINYINT DEFAULT 0;
    
    /* Vérifier si l'utilisateur qui fait la suppression est admin */
    SELECT is_admin INTO is_admin 
    FROM users 
    WHERE id = p_admin_id;
    
    /* Vérifier si l'utilisateur à supprimer existe et s'il est admin */
    SELECT is_admin INTO target_is_admin 
    FROM users 
    WHERE id = p_user_id;
    
    IF NOT EXISTS (SELECT 1 FROM users WHERE id = p_user_id) THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'L\'utilisateur à supprimer n\'existe pas';
    ELSEIF is_admin = 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Accès refusé: Privilèges administrateur requis pour supprimer un utilisateur';
    ELSEIF target_is_admin = 1 AND p_admin_id != p_user_id THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Accès refusé: Impossible de supprimer un autre administrateur';
    ELSE
        /* Supprimer l'utilisateur */
        DELETE FROM users WHERE id = p_user_id;
        SELECT 'Utilisateur supprimé avec succès' AS message, TRUE AS success;
    END IF;
END";

$success = executeStoredProcedure($conn, $sql_delete_user, "Création de la procédure DeleteUser") && $success;

// Résultat final
if ($success) {
    echo "<div class='success'>
        <h2>✅ Configuration réussie</h2>
        <p>Toutes les procédures stockées ont été créées avec succès.</p>
    </div>";
} else {
    echo "<div class='error'>
        <h2>❌ Erreurs détectées</h2>
        <p>Certaines opérations ont échoué. Vérifiez les messages d'erreur ci-dessus.</p>
    </div>";
}

echo '<a href="index.php" class="btn">Retour à l\'accueil</a>
    </body>
</html>';
?>
