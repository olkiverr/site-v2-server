CREATE DATABASE IF NOT EXISTS mangamuse;

USE mangamuse;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL,
    password VARCHAR(255) NOT NULL,
    is_admin BOOLEAN DEFAULT FALSE
);

INSERT INTO `users` (`id`, `username`, `email`, `password`, `is_admin`) VALUES
(1, 'olkiverr', 'olivier@gmail.com', '$2y$10$uUKRQvZVeE2BYXH2riGvGuRBbSCWRM5WsMx2OKla7.OSs7IPqV0wC', 1);

CREATE TABLE IF NOT EXISTS images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    url VARCHAR(255) NOT NULL,
    name VARCHAR(100) NOT NULL,
    category ENUM('trending', 'upcoming') NOT NULL
);

INSERT INTO `images` (`id`, `url`, `name`, `category`) VALUES
(1, 'anime_img/SNK.webp', 'Attack Of Titan', 'trending'),
(2, 'anime_img/DBZ.jpg', 'Dragon Ball Z', 'trending'),
(3, 'anime_img/SNK.webp', 'Attack Of Titan', 'upcoming'),
(4, 'anime_img/DBZ.jpg', 'Dragon Ball Z', 'upcoming');
COMMIT;