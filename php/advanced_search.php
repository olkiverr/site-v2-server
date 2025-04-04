<?php
include 'db.php';
session_start();

$title = isset($_GET['title']) ? $_GET['title'] : '';
$selectedGenres = isset($_GET['genres']) ? $_GET['genres'] : [];
$favoriteOnly = isset($_GET['favorite']) && $_GET['favorite'] === 'true';
$watchedOnly = isset($_GET['watched']) && $_GET['watched'] === 'true';

// Vérifier si l'utilisateur est connecté
$user_id = null;
if (isset($_SESSION['is_admin'])) {
    // Pour l'admin, vérifier si id est défini, sinon utiliser une valeur par défaut
    $user_id = isset($_SESSION['id']) ? $_SESSION['id'] : 1;
} elseif (isset($_SESSION['id'])) {
    $user_id = $_SESSION['id'];
}

// Liste des genres interdits
$forbidden_genres = ['Ecchi', 'Erotica', 'Hentai'];

$sql = "SELECT DISTINCT p.id, p.title, p.img FROM pages p";

// Ajouter les joins si nécessaire pour les filtres favoris/vus
if (($favoriteOnly || $watchedOnly) && $user_id) {
    $sql .= " LEFT JOIN user_anime_status uas ON p.id = uas.anime_id";
    $sql .= " WHERE uas.user_id = ?"; // Spécifier l'utilisateur actuel
} else {
    $sql .= " WHERE 1=1";
}

$params = array();
$types = "";

// Ajouter le user_id aux paramètres si nécessaire
if (($favoriteOnly || $watchedOnly) && $user_id) {
    $params[] = $user_id;
    $types .= "i";
}

// Ajouter les conditions pour favoris/vus
if ($favoriteOnly && $user_id) {
    $sql .= " AND uas.is_favorite = 1";
}
if ($watchedOnly && $user_id) {
    $sql .= " AND uas.is_watched = 1";
}

// Ajouter la condition de titre si fournie
if (!empty($title)) {
    $sql .= " AND p.title LIKE ?";
    $params[] = "%$title%";
    $types .= "s";
}

// Ajouter les conditions de genres si sélectionnés
if (!empty($selectedGenres)) {
    foreach ($selectedGenres as $genre) {
        $sql .= " AND p.genres LIKE ?";
        $params[] = "%$genre%";
        $types .= "s";
    }
}

// Exclure les genres interdits
foreach ($forbidden_genres as $genre) {
    $sql .= " AND p.genres NOT LIKE ?";
    $params[] = "%$genre%";
    $types .= "s";
}

// Exécuter la requête
$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $bindParams = array($types);
    foreach ($params as $param) {
        $bindParams[] = $param;
    }
    $refs = array();
    foreach ($bindParams as $key => $value) {
        $refs[$key] = &$bindParams[$key];
    }
    call_user_func_array(array($stmt, 'bind_param'), $refs);
}

$stmt->execute();
$result = $stmt->get_result();

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

header('Content-Type: application/json');
echo json_encode($data);

$conn->close();
?> 