<?php
session_start();
include '../php/db.php';

if (isset($_SESSION['user'])) {
    $is_connected = isset($_SESSION['user']);
} else {
    $is_connected = false;
}

// Vérifier s'il y a un message de succès ou d'erreur
$success = isset($_GET['success']) ? true : false;
$error = isset($_GET['error']) ? true : false;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/header.css">
    <link rel="stylesheet" href="../css/footer.css">
    <link rel="stylesheet" href="../css/contact.css">
    <link rel="shortcut icon" href="../img/MangaMuse_White-Book.png" type="image/x-icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cabin:ital,wght@0,400..700;1,400..700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <title>Contact - Mangamuse</title>
</head>
<body>
    <?php include '../partials/header.php' ?>
    
    <main>
        <div class="contact-container">
            <h1><i class="fas fa-envelope"></i> Contactez-nous</h1>
            
            <?php if ($success): ?>
                <div class="message success">
                    <i class="fas fa-check-circle"></i> Votre message a été envoyé avec succès ! Nous vous répondrons dans les plus brefs délais.
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="message error">
                    <i class="fas fa-exclamation-circle"></i> Une erreur s'est produite lors de l'envoi du message. Veuillez réessayer.
                </div>
            <?php endif; ?>
            
            <form action="../php/send_mail.php" method="POST">
                <?php if (!$is_connected): ?>
                    <div class="form-group">
                        <label for="name">Nom</label>
                        <input placeholder="Votre nom" type="text" name="name" id="name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input placeholder="Votre email" type="email" name="email" id="email" required>
                    </div>
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="message">Message</label>
                    <textarea placeholder="Tapez votre message ici..." name="message" id="message" required></textarea>
                </div>
                
                <button type="submit">
                    <i class="fas fa-paper-plane"></i> Envoyer
                </button>
            </form>
        </div>
    </main>
    
    <?php if ($is_connected): ?>
        <footer>
            <p><span><a href="42.php" style="text-decoration: none; color: white; cursor: text;">&copy;</a></span> 2025 Mangamuse by Zielinski Olivier</p>
        </footer>
    <?php else: ?>
        <?php include '../partials/footer.php' ?>
    <?php endif;?>
</body>
</html>