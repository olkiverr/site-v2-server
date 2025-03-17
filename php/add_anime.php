<?php
// Inclure le fichier de connexion à la base de données
include('db.php');

// Fonction pour récupérer et insérer les animés
function insertAnime($url, $category, $conn) {
    $response = file_get_contents($url);
    if ($response === false) {
        echo "❌ Erreur lors de la récupération des données API.";
        return;
    }
    
    $data = json_decode($response, true);
    if (!isset($data["data"]) || empty($data["data"])) {
        echo "❌ Aucune donnée trouvée.";
        return;
    }

    foreach ($data["data"] as $anime) {
        $id = $anime["mal_id"];
        $title = $anime["title"];
        $broadcast = $anime["broadcast"]["string"] ?? "Date inconnue";
        $episodes = $anime["episodes"] ?? 0;
        $img = $anime["images"]["jpg"]["image_url"] ?? "";
        $synopsis = $anime["synopsis"] ?? "Pas de description.";
        
        // Récupération des studios, genres et créateurs
        $genres = implode(", ", array_column($anime["genres"], "name")) ?: "Non spécifié";
        $studio = implode(", ", array_column($anime["studios"], "name")) ?: "Non spécifié";
        $creator = implode(", ", array_column($anime["producers"], "name")) ?: "Non spécifié"; 

        // Vérifier si l'anime existe déjà dans la base
        $stmt = $conn->prepare("SELECT id FROM pages WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            echo "⚠️ L'anime '$title' (ID: $id) est déjà enregistré.<br>";
        } else {
            // Insérer l'anime dans la base
            $stmt = $conn->prepare("INSERT INTO pages (id, title, creator, broadcast, genres, episodes, studio, img, category, description)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

            $stmt->bind_param("isssisisss", $id, $title, $creator, $broadcast, $genres, $episodes, $studio, $img, $category, $synopsis);
            $stmt->execute();

            echo "✅ Anime '$title' ajouté à la base de données.<br>";
        }

        $stmt->close();
    }
}

// 🔥 Ajout des tendances
insertAnime("https://api.jikan.moe/v4/top/anime?page=1", "trending", $conn);

// 📅 Ajout des animés à venir
insertAnime("https://api.jikan.moe/v4/seasons/upcoming?page=1", "upcoming", $conn);

// 🔍 Ajout des autres animés (non classés)
insertAnime("https://api.jikan.moe/v4/anime?page=1", "none", $conn);

// Fermer la connexion à la base de données
$conn->close();
?>
