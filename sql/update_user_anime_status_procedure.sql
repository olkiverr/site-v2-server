DELIMITER //

CREATE PROCEDURE UpdateUserAnimeStatus (
    IN p_user_id INT,
    IN p_anime_id INT,
    IN p_is_favorite BOOLEAN,
    IN p_is_watched BOOLEAN
)
BEGIN
    INSERT INTO user_anime_status (user_id, anime_id, is_favorite, is_watched)
    VALUES (p_user_id, p_anime_id, p_is_favorite, p_is_watched)
    ON DUPLICATE KEY UPDATE
        is_favorite = p_is_favorite,
        is_watched = p_is_watched;
END //

DELIMITER ;
