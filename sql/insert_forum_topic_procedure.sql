DELIMITER //

CREATE PROCEDURE InsertForumTopic (
    IN p_community_id INT,
    IN p_user_id INT,
    IN p_title VARCHAR(255),
    IN p_content TEXT
)
BEGIN
    INSERT INTO forum_topics (community_id, user_id, title, content)
    VALUES (p_community_id, p_user_id, p_title, p_content);
END //

DELIMITER ;
