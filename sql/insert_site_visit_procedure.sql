DELIMITER //

CREATE PROCEDURE InsertSiteVisit (
    IN p_date DATE
)
BEGIN
    INSERT INTO site_visits (date, count)
    VALUES (p_date, 1)
    ON DUPLICATE KEY UPDATE
        count = count + 1;
END //

DELIMITER ;
