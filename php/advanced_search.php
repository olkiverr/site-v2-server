<?php
include 'db.php';

$title = isset($_GET['title']) ? $_GET['title'] : '';
$selectedGenres = isset($_GET['genres']) ? $_GET['genres'] : [];

// Liste des genres interdits
$forbidden_genres = ['Ecchi', 'Erotica', 'Hentai'];

$sql = "SELECT id, title, img FROM pages WHERE 1=1";
$params = array();
$types = "";

// Ajouter la condition de titre si fournie
if (!empty($title)) {
    $sql .= " AND title LIKE ?";
    $params[] = "%$title%";
    $types .= "s";
}

// Ajouter les conditions de genres si sélectionnés
if (!empty($selectedGenres)) {
    foreach ($selectedGenres as $genre) {
        $sql .= " AND genres LIKE ?";
        $params[] = "%$genre%";
        $types .= "s";
    }
}

// Exclure les genres interdits
foreach ($forbidden_genres as $genre) {
    $sql .= " AND genres NOT LIKE ?";
    $params[] = "%$genre%";
    $types .= "s";
}

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