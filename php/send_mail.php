<?php
// Inclure PHPMailer
require_once '../phpmailer/src/PHPMailer.php';
require_once '../phpmailer/src/SMTP.php';
require_once '../phpmailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

session_start();
include '../php/db.php';

// Vérification de la méthode de requête POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Initialisation des variables
    $message = $_POST['message'] ?? '';

    // Si l'utilisateur est connecté, récupérer ses informations de la base de données
    if (isset($_SESSION['user'])) {
        $current_user = $_SESSION['user'];

        // Récupérer les informations de l'utilisateur depuis la base de données
        $sql = "SELECT * FROM users WHERE username = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $current_user);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();

            // Récupérer le nom et l'email de l'utilisateur
            $username = $user['username'];
            $email = $user['email'];
        } else {
            echo "Utilisateur introuvable.";
            exit;
        }
    } else {
        // Si l'utilisateur n'est pas connecté, demander l'e-mail et le nom
        $username = $_POST['name'] ?? '';
        $email = $_POST['email'] ?? '';
    }

    // Vérifier que le message est non vide
    if (empty($message)) {
        echo "Le message est requis.";
        exit;
    }

    // Si l'utilisateur n'est pas connecté, vérifier que tous les champs sont remplis
    if (!isset($_SESSION['user']) && (empty($username) || empty($email))) {
        echo "Nom, e-mail et message sont requis.";
        exit;
    }

    // Créer une instance de PHPMailer pour envoyer l'e-mail
    $mail = new PHPMailer(true);

    try {
        // Configuration du serveur SMTP
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'mangamuse2@gmail.com'; // Remplacez par votre e-mail
        $mail->Password   = 'trgf xcme ibfi oqll'; // Mot de passe d'application
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Désactivation du débogage
        $mail->SMTPDebug = 0; // Désactive l'affichage de l'erreur du serveur SMTP

        // Destinataires
        $mail->setFrom('mangamuse2@gmail.com', 'Mangamuse');
        $mail->addAddress('mangamuse2@gmail.com', 'Mangamuse'); // Votre e-mail

        // Contenu de l'e-mail
        $mail->isHTML(true);
        $mail->Subject = 'Message (Contact) de la part de ' . $username;
        $mail->Body    = 'Nom : ' . $username . '<br>Email : ' . $email . '<br>Message : ' . nl2br($message);
        $mail->AltBody = 'Nom : ' . $username . '\nEmail : ' . $email . '\nMessage : ' . $message;

        // Envoi de l'e-mail
        $mail->send();

        // Envoi de la confirmation à l'utilisateur
        $confirmMail = new PHPMailer(true);
        $confirmMail->isSMTP();
        $confirmMail->Host       = 'smtp.gmail.com';
        $confirmMail->SMTPAuth   = true;
        $confirmMail->Username   = 'mangamuse2@gmail.com';
        $confirmMail->Password   = 'trgf xcme ibfi oqll';
        $confirmMail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $confirmMail->Port       = 587;

        $confirmMail->setFrom('mangamuse2@gmail.com', 'Mangamuse');
        $confirmMail->addAddress($email, $username);

        $confirmMail->isHTML(true);
        $confirmMail->Subject = 'Confirmation de l\'envoi de votre message';
        $confirmMail->Body    = 'Bonjour ' . $username . ',<br><br>Votre message a été envoyé avec succès. Nous reviendrons vers vous dans les plus brefs délais.<br><br>Votre message :<br>' . nl2br($message);
        $confirmMail->AltBody = 'Bonjour ' . $username . ',\n\nVotre message a été envoyé avec succès. Nous reviendrons vers vous dans les plus brefs délais.\n\nVotre message :\n' . $message;

        $confirmMail->send();

        // Redirection vers la page d'accueil
        header('Location: ../index.php');
        exit;

    } catch (Exception $e) {
        header('Location: ../contact.php?error=');
        exit;
    }
} else {
    echo 'Méthode de requête non valide.';
}
?>
