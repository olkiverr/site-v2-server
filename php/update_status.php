<?php
// Remplacer session_start() par l'inclusion de la configuration
include 'session_config.php';
include 'db.php';

header('Content-Type: application/json'); // Forcer le type de contenu en JSON

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['id'])) { // Utiliser $_SESSION['id'] au lieu de user_id
    echo json_encode(['error' => 'User not connected']);
    exit;
}

if (!isset($_POST['anime_id']) || !isset($_POST['type'])) {
    echo json_encode(['error' => 'Missing data']);
    exit;
}

$anime_id = $_POST['anime_id'];
$type = $_POST['type'];
$user_id = $_SESSION['id']; // Utiliser directement l'ID de session

try {
    // Vérifier si un statut existe déjà
    $check_sql = "SELECT * FROM user_anime_status WHERE user_id = ? AND anime_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ii", $user_id, $anime_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();

    if ($result->num_rows > 0) {
        // Mettre à jour le statut existant
        $row = $result->fetch_assoc();
        $is_favorite = $type === 'favorite' ? !$row['is_favorite'] : $row['is_favorite'];
        $is_watched = $type === 'watched' ? !$row['is_watched'] : $row['is_watched'];
    } else {
        // Insérer un nouveau statut
        $is_favorite = $type === 'favorite' ? 1 : 0;
        $is_watched = $type === 'watched' ? 1 : 0;
    }

    // Appeler la procédure stockée pour mettre à jour ou insérer le statut
    $stmt = $conn->prepare("CALL UpdateUserAnimeStatus(?, ?, ?, ?)");
    $stmt->bind_param("iiii", $user_id, $anime_id, $is_favorite, $is_watched);
    $stmt->execute();

    echo json_encode(['success' => true, 'status' => $type === 'favorite' ? $is_favorite : $is_watched]);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}

$conn->close();
?>