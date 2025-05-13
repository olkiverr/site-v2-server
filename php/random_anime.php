<?php
require_once 'db.php';

// Récupérer un animé aléatoire depuis la base de données
function getRandomAnime() {
    global $conn;
    
    // Sélectionner un animé aléatoire en excluant les contenus pour adultes
    $sql = "SELECT id FROM pages 
            WHERE genres NOT LIKE '%Ecchi%' 
            AND genres NOT LIKE '%Erotica%' 
            AND genres NOT LIKE '%Hentai%'
            ORDER BY RAND() 
            LIMIT 1";
    
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['id'];
    }
    
    return null;
}

// Récupérer l'ID d'un animé aléatoire
$random_anime_id = getRandomAnime();

// Rediriger vers la page de l'animé
if ($random_anime_id) {
    header("Location: /4TTJ/Zielinski%20Olivier/Site/site-v2/pages/view_anime.php?id=" . $random_anime_id);
} else {
    // Si aucun animé n'est trouvé, rediriger vers la page d'accueil
    header("Location: /4TTJ/Zielinski%20Olivier/Site/site-v2/index.php");
}
exit;
?> 