<?php
// Remplacer session_start() par l'inclusion de la configuration
include '../php/session_config.php';
include '../php/db.php';

if (isset($_SESSION['user'])) {
    $is_connected = isset($_SESSION['user']);
} else {
    $is_connected = false;
}

// Check if there is a success or error message
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
            <h1><i class="fas fa-envelope"></i> Contact Us</h1>
            
            <?php if ($success): ?>
                <div class="message success">
                    <i class="fas fa-check-circle"></i> Your message has been sent successfully! We will get back to you as soon as possible.
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="message error">
                    <i class="fas fa-exclamation-circle"></i> An error occurred while sending the message. Please try again.
                </div>
            <?php endif; ?>
            
            <form action="../php/send_mail.php" method="POST">
                <?php if (!$is_connected): ?>
                    <div class="form-group">
                        <label for="name">Name</label>
                        <input placeholder="Your name" type="text" name="name" id="name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input placeholder="Your email" type="email" name="email" id="email" required>
                    </div>
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="message">Message</label>
                    <textarea placeholder="Type your message here..." name="message" id="message" required></textarea>
                </div>
                
                <button type="submit">
                    <i class="fas fa-paper-plane"></i> Send
                </button>
            </form>
        </div>
    </main>
    
    <?php include '../partials/footer.php' ?>
</body>
</html>