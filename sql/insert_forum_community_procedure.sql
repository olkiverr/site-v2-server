DELIMITER //

CREATE PROCEDURE InsertForumCommunity (
    IN p_name VARCHAR(255),
    IN p_slug VARCHAR(255),
    IN p_description TEXT,
    IN p_user_id INT
)
BEGIN
    INSERT INTO forum_communities (name, slug, description, user_id)
    VALUES (p_name, p_slug, p_description, p_user_id);
END //

DELIMITER ;
