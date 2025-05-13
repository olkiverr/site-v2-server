DELIMITER //

-- Procédure stockée pour insérer une nouvelle page avec vérification du statut administrateur
-- Cette procédure est spécifique à la table 'pages'
CREATE PROCEDURE insert_page_admin_only(
    IN p_user_id INT,           -- ID de l'utilisateur qui tente l'insertion
    IN p_title VARCHAR(255),    -- Titre de la page
    IN p_content TEXT,          -- Contenu de la page
    IN p_category VARCHAR(50),  -- Catégorie de la page
    IN p_status VARCHAR(20)     -- Statut de la page (published, draft, etc.)
)
BEGIN
    -- Déclaration des variables
    DECLARE is_user_admin TINYINT DEFAULT 0;
    
    -- Vérifier si l'utilisateur est un administrateur
    SELECT is_admin INTO is_user_admin 
    FROM users 
    WHERE id = p_user_id;
    
    -- Si l'utilisateur est un administrateur, procéder à l'insertion
    IF is_user_admin = 1 THEN
        -- Insertion directe avec des paramètres spécifiques
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
        
        -- Indiquer le succès et renvoyer l'ID de la page créée
        SELECT 
            'Page créée avec succès' AS message, 
            TRUE AS success, 
            LAST_INSERT_ID() AS page_id;
    ELSE
        -- Si l'utilisateur n'est pas un administrateur, renvoyer une erreur
        SELECT 
            'Accès refusé: Privilèges administrateur requis pour créer une page' AS message, 
            FALSE AS success, 
            NULL AS page_id;
    END IF;
END //

DELIMITER ;

-- Exemple d'utilisation:
-- CALL insert_page_admin_only(1, 'Nouvelle Page', 'Contenu de la page', 'general', 'published'); 