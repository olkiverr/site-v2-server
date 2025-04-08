<?php
session_start();
include 'db.php';

ini_set('display_errors', 1); // Afficher toutes les erreurs PHP
error_reporting(E_ALL); // Rapport des erreurs

header('Content-Type: application/json'); // Définir l'en-tête en JSON

$response = array(); // Tableau de réponse

// Vérification de la méthode POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Récupération des données du formulaire
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Recherche de l'utilisateur dans la base de données
    $sql = "SELECT * FROM users WHERE username='$username'";
    $result = $conn->query($sql);

    // Si l'utilisateur existe
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        // Vérification du mot de passe
        if (password_verify($password, $row['password'])) {
            // Générer un token Bearer unique
            $token = bin2hex(random_bytes(32)); // Générer un token aléatoire
            date_default_timezone_set('Europe/Brussels');
            $expiry = date('Y-m-d H:i:s', strtotime('+1 hour')); // Expiration dans 1 heure

            // Mise à jour du token et de sa date d'expiration dans la base de données
            $stmt = $conn->prepare("UPDATE users SET token = ?, token_expiry = ? WHERE username = ?");
            $stmt->bind_param("sss", $token, $expiry, $username);
            $stmt->execute();

            // Mettre à jour la session pour indiquer que l'utilisateur est connecté
            $_SESSION['id'] = $row['id'];
            $_SESSION['user_id'] = $row['id']; // Ajout explicite de user_id
            $_SESSION['user'] = $username; // Nom d'utilisateur dans la session
            $_SESSION['email'] = $row['email'];
            $_SESSION['is_admin'] = $row['is_admin'];
            
            // Réponse de succès avec le token
            $response['status'] = 'success';
            $response['token'] = $token;
            $response['message'] = 'Login successful';
        } else {
            // Mauvais mot de passe
            $response['status'] = 'error';
            $response['message'] = 'Invalid password';
        }
    } else {
        // Utilisateur non trouvé
        $response['status'] = 'error';
        $response['message'] = 'No user found with that username';
    }

    // Fermeture de la connexion à la base de données
    $conn->close();
} else {
    // Si la méthode de la requête n'est pas POST
    $response['status'] = 'error';
    $response['message'] = 'Invalid request method';
}

// Renvoyer la réponse JSON
echo json_encode($response);
exit(); // S'assurer que rien d'autre n'est envoyé après
?>