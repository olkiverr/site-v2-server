DELIMITER //

CREATE PROCEDURE AddUser (
    IN p_username VARCHAR(255),
    IN p_email VARCHAR(255),
    IN p_password VARCHAR(255),
    IN p_is_admin BOOLEAN
)
BEGIN
    INSERT INTO users (username, email, password, is_admin)
    VALUES (p_username, p_email, p_password, p_is_admin);
END //

DELIMITER ;
