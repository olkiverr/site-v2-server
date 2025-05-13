<?php
include 'db.php';
include 'session_config.php';

// Fonction pour vérifier le token Bearer
function verifyBearerToken() {
    if (!isset($_SERVER['HTTP_AUTHORIZATION'])) {
        http_response_code(401);
        echo json_encode(["message" => "Token missing"]);
        exit();
    }

    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    list($type, $token) = explode(" ", $authHeader);

    if ($type !== "Bearer" || empty($token)) {
        http_response_code(401);
        echo json_encode(["message" => "Invalid token format"]);
        exit();
    }

    global $conn;
    $stmt = $conn->prepare("SELECT id, token_expiry FROM users WHERE token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        http_response_code(401);
        echo json_encode(["message" => "Token not found"]);
        exit();
    }

    $user = $result->fetch_assoc();
    // Vérifier si le token a expiré
    if (strtotime($user['token_expiry']) < time()) {
        http_response_code(401);
        echo json_encode(["message" => "Token expired"]);
        exit();
    }

    return $user['id']; // Retourne l'ID de l'utilisateur
}

// Exemple de route protégée
$userId = verifyBearerToken(); // Vérifie le token
echo json_encode(["message" => "Protected content", "user_id" => $userId]);
?>
