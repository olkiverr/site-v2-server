<?php
session_start();
include '../php/db.php';

$response = array();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Récupération et nettoyage des données
    $username = htmlspecialchars(trim($_POST['username']));
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    
    // Vérification du mot de passe
    if (strlen($password) < 8) {
        $response['status'] = 'error';
        $response['message'] = 'Le mot de passe doit contenir au moins 8 caractères';
        echo json_encode($response);
        exit();
    }
    
    // Hachage du mot de passe
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Prévenir les injections SQL avec des requêtes préparées
    $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $username, $email, $hashedPassword);
    
    if ($stmt->execute()) {
        // Récupérer l'ID du nouvel utilisateur
        $userId = $conn->insert_id;
        
        // Générer un token Bearer
        $token = bin2hex(random_bytes(32));
        date_default_timezone_set('Europe/Brussels');
        $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        // Mettre à jour le token dans la base de données
        $tokenStmt = $conn->prepare("UPDATE users SET token = ?, token_expiry = ? WHERE id = ?");
        $tokenStmt->bind_param("ssi", $token, $expiry, $userId);
        $tokenStmt->execute();
        
        // Mettre à jour la session
        $_SESSION['user'] = $username;
        $_SESSION['email'] = $email;
        $_SESSION['is_admin'] = 0; // Default to non-admin
        
        // Réponse de succès avec le token
        $response['status'] = 'success';
        $response['message'] = 'Registration successful';
        $response['token'] = $token;
        $response['token_expiry'] = $expiry;
    } else {
        // Gestion des erreurs potentielles
        if ($conn->errno == 1062) { // Code d'erreur pour entrée dupliquée
            $response['status'] = 'error';
            $response['message'] = 'Ce nom d\'utilisateur ou cet email existe déjà';
        } else {
            $response['status'] = 'error';
            $response['message'] = 'Erreur lors de l\'inscription: ' . $stmt->error;
        }
    }

    $conn->close();
    echo json_encode($response);
    exit();
}

// Générer un token CSRF pour le formulaire
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Mangamuse</title>
    <link rel="stylesheet" href="../css/header.css">
    <link rel="stylesheet" href="../css/footer.css">
    <link rel="stylesheet" href="../css/register.css">
    <link rel="stylesheet" href="../css/snackbar.css">
    <link rel="icon" href="../img/MangaMuse_White-Book.png" type="image/x-icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cabin:ital,wght@0,400..700;1,400..700&display=swap" rel="stylesheet">
</head>
<body>
    <?php include '../partials/header.php'; ?>
    <main class="centered-container">
        <form id="register-form" method="POST" action="">
            <h2>Register</h2>
            <!-- Protection CSRF -->
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            
            <input type="text" name="username" placeholder="Username" required>
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Password" required 
                   pattern=".{8,}" title="Le mot de passe doit contenir au moins 8 caractères">
            <input type="password" name="confirm_password" placeholder="Confirm Password" required>
            
            <div class="password-requirements">
                <p>Le mot de passe doit contenir au moins 8 caractères</p>
            </div>
            
            <div class="terms-container">
                <label for="terms">I accept the&nbsp;<a href="tacs.php" target="_blank">Terms & Services</a>.</label>
                <input type="checkbox" name="terms" id="terms" required>
            </div>
            <button type="submit">Register</button>
            <p>Already have an account? <a href="login.php">Login</a></p>
        </form>
    </main>
    <?php include '../partials/footer.php'; ?>
    <script src="../js/register.js"></script>
    <script src="../js/snackbar.js"></script>
    
    <!-- Ajout d'une validation côté client -->
    <script>
    document.getElementById('register-form').addEventListener('submit', function(e) {
        const password = document.querySelector('input[name="password"]').value;
        const confirmPassword = document.querySelector('input[name="confirm_password"]').value;
        
        if (password !== confirmPassword) {
            e.preventDefault();
            showSnackbar('Les mots de passe ne correspondent pas', 'error');
            return false;
        }
        
        if (password.length < 8) {
            e.preventDefault();
            showSnackbar('Le mot de passe doit contenir au moins 8 caractères', 'error');
            return false;
        }
    });
    </script>
</body>
</html>