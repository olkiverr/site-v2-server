-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1:3306
-- Généré le : lun. 17 fév. 2025 à 12:38
-- Version du serveur : 9.1.0
-- Version de PHP : 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `mangamuse`
--

-- --------------------------------------------------------

--
-- Structure de la table `pages`
--

DROP TABLE IF EXISTS `pages`;
CREATE TABLE IF NOT EXISTS `pages` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `creator` varchar(255) NOT NULL,
  `broadcast` varchar(255) NOT NULL,
  `genres` varchar(255) NOT NULL,
  `episodes` int NOT NULL,
  `studio` varchar(255) NOT NULL,
  `description` varchar(9999) NOT NULL,
  `style` varchar(999) NOT NULL,
  `img` varchar(255) NOT NULL,
  `category` varchar(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `pages`
--

INSERT INTO `pages` (`id`, `title`, `creator`, `broadcast`, `genres`, `episodes`, `studio`, `description`, `style`, `img`, `category`) VALUES
(1, 'Dragon Ball Z', 'Akira Toriyama', '1989 - 1996', 'Action, Adventure, Fantasy, Martial Arts', 291, 'Toei Animation', 'Dragon Ball Z follows the continued adventures of Goku, a Saiyan warrior who lives on Earth and protects the planet from various powerful threats. As Goku delves deeper into his past, he encounters increasingly formidable enemies, ranging from ruthless space tyrants to nearly invincible beings. Throughout the series, Goku and his allies engage in epic battles, discovering hidden powers and transformations that push the limits of their abilities.\n\nAs the story unfolds, Goku forms new alliances and faces new challenges, all while striving to protect Earth and his loved ones. With each battle, Goku surpasses his own limits, driven by his unwavering desire to safeguard the world and uncover the true potential of his strength.\n\n', '\n.img-infos {\n    display: flex;\n    flex-direction: row;\n    height: 40%;\n    width: 100%;\n    padding: 10px 0;\n}\n\n.img {\n    display: flex;\n    justify-content: space-around;\n    width: 30%;\n    height: 100%;\n}\n\n.img > img {\n    height: 100%;\n    border-radius: 10px;\n}\n\n.infos {\n    color: white;\n    width: 70%;\n    height: 100%;\n    border-left: 1px solid rgb(158, 158, 158);\n    padding-left: 10px;\n}\n\n.description {\n    color: white;\n    height: 60%;\n    width: 100%;\n    padding: 10px 0;\n}', '/4TTJ/Zielinski%20Olivier/Site/site-v2/anime_imgs/DBZ.jpg', 'trending'),
(2, 'Sword Art Online', 'Reki Kawahara', '2012', 'Action, Adventure, Fantasy, Romance, Science Fiction', 100, 'A-1 Pictures', 'Sword Art Online is a popular anime set in the near future, where virtual reality massively multiplayer online role-playing games (VRMMORPGs) have become the norm. The story begins when thousands of players log into a new virtual reality game called Sword Art Online, only to discover that they are unable to log out. The creator of the game traps the players in the world, and the only way to escape is to reach the top of the game’s tower and defeat the final boss. As the players face the challenge of surviving in this virtual world, they must battle fierce monsters, form alliances, and try to uncover the mysteries of the game. At the center of the story is Kirito, a skilled and solitary player, who becomes a key figure in the fight for survival, while forming deep relationships with other players, including Asuna, a fellow player who becomes his close companion and romantic interest.', '\r\n.img-infos {\r\n    display: flex;\r\n    flex-direction: row;\r\n    height: 40%;\r\n    width: 100%;\r\n    padding: 10px 0;\r\n    background-color: #252525;\r\n}\r\n\r\n.img {\r\n    display: flex;\r\n    justify-content: space-around;\r\n    width: 30%;\r\n    height: 100%;\r\n}\r\n\r\n.img > img {\r\n    height: 100%;\r\n    border-radius: 10px;\r\n}\r\n\r\n.infos {\r\n    width: 70%;\r\n    height: 100%;\r\n    border-left: 1px solid #9e9e9e;\r\n    padding-left: 10px;\r\n}\r\n\r\n.infos h2 {\r\n    color: #ffffff;\r\n}\r\n\r\n.infos strong {\r\n    color: #ffffff;\r\n}\r\n\r\n.infos li {\r\n    color: #ffffff;\r\n}\r\n\r\n.description {\r\n    color: #ffffff;\r\n    height: 60%;\r\n    width: 100%;\r\n    padding: 10px 0;\r\n    background-color: #252525;\r\n}', '/4TTJ/Zielinski%20Olivier/Site/site-v2/anime_imgs/sword_art_online.jpg', 'upcoming');

-- --------------------------------------------------------

--
-- Structure de la table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `is_admin` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `is_admin`) VALUES
(1, 'olkiverr', 'olivier@gmail.com', '$2y$10$uUKRQvZVeE2BYXH2riGvGuRBbSCWRM5WsMx2OKla7.OSs7IPqV0wC', 1),
(3, 'samsky', 'samymebrek16@hotmail.com', '$2y$10$SPAuZVl7P7YQsg4GlEPWru6vj76oJ2kvkRNGim1eyHTIOaMRnXsXS', 0),
(4, 'AJRF', 'AJRF@gmail.com', '$2y$10$4CVMihoxODqNepTAaYPiae8nykrxBBNHMLTUJQaNW7xf5FuS7h4rW', 0),
(5, 'mateusz', 'mateuszryzko@gmail.com', '$2y$10$Viqn.fh6604WFgYXXD1BmeYxm.oJ5lipkpbu0A9UtDYJLXe9/bYDC', 0),
(6, 'cuirc', 'mathias@e.com', '$2y$10$FgdXUeUJ9YU1TdaubB5adeBYu1Ov2NhGucpNhJsLkpNK7dBBjLwaa', 0),
(7, 'mateusz', 'mateuszryzko@gmail.com', '$2y$10$LXSvn52alHF/nYImUnGCW.ok36IghZFfUWTSpRyzS542VAlyeQeYi', 0);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
