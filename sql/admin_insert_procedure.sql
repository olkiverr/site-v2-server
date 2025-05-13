DELIMITER //

-- Procédure stockée pour insérer des données avec vérification du statut administrateur
-- Cette procédure vérifie d'abord si l'utilisateur est un administrateur
-- et ne procède à l'insertion que si c'est le cas
CREATE PROCEDURE insert_data_admin_only(
    IN p_user_id INT,                -- ID de l'utilisateur qui tente l'insertion
    IN p_table_name VARCHAR(50),     -- Nom de la table où insérer les données
    IN p_column_names TEXT,          -- Liste des noms de colonnes (format: "col1,col2,col3")
    IN p_values TEXT                 -- Liste des valeurs (format: "val1,val2,val3")
)
BEGIN
    -- Déclaration des variables
    DECLARE is_user_admin TINYINT DEFAULT 0;
    DECLARE sql_query TEXT;
    
    -- Vérifier si l'utilisateur est un administrateur
    SELECT is_admin INTO is_user_admin 
    FROM users 
    WHERE id = p_user_id;
    
    -- Si l'utilisateur est un administrateur, procéder à l'insertion
    IF is_user_admin = 1 THEN
        -- Préparer la requête SQL d'insertion
        SET @sql_query = CONCAT('INSERT INTO ', p_table_name, ' (', p_column_names, ') VALUES (', p_values, ')');
        
        -- Exécuter la requête SQL dynamique
        PREPARE stmt FROM @sql_query;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
        
        -- Indiquer le succès
        SELECT 'Insertion réussie' AS message, TRUE AS success;
    ELSE
        -- Si l'utilisateur n'est pas un administrateur, renvoyer une erreur
        SELECT 'Accès refusé: Privilèges administrateur requis' AS message, FALSE AS success;
    END IF;
END //

DELIMITER ;

-- Exemple d'utilisation:
-- CALL insert_data_admin_only(1, 'pages', 'title,content,created_at', "'Nouvelle page','Contenu de la page',NOW()"); 