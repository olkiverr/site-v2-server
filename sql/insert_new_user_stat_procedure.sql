DELIMITER //

CREATE PROCEDURE InsertNewUserStat (
    IN p_date DATE
)
BEGIN
    INSERT INTO new_users (date, count)
    VALUES (p_date, 1)
    ON DUPLICATE KEY UPDATE
        count = count + 1;
END //

DELIMITER ;
