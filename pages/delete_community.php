<?php
require_once __DIR__ . '/../php/db.php';
require_once __DIR__ . '/../php/forum/forum_functions.php';

// Start the session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Vérifier si l'utilisateur est connecté et est admin
if (!isset($_SESSION['user']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header('Location: ../pages/forum.php');
    exit;
}

// Récupérer l'ID de la communauté
$community_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($community_id <= 0) {
    header('Location: ../pages/forum.php');
    exit;
}

// Récupérer les informations de la communauté
$stmt = $conn->prepare("SELECT * FROM forum_communities WHERE id = ?");
$stmt->bind_param("i", $community_id);
$stmt->execute();
$result = $stmt->get_result();
$community = $result->fetch_assoc();
$stmt->close();

if (!$community) {
    header('Location: ../pages/forum.php');
    exit;
}

// Démarrer une transaction pour s'assurer que toutes les suppressions sont effectuées
$conn->begin_transaction();

try {
    // 1. Supprimer tous les commentaires des posts de cette communauté
    $stmt = $conn->prepare("
        DELETE c FROM forum_comments c
        INNER JOIN forum_topics t ON c.topic_id = t.id
        WHERE t.community_id = ?
    ");
    $stmt->bind_param("i", $community_id);
    $stmt->execute();
    $stmt->close();

    // 2. Supprimer tous les posts de cette communauté
    $stmt = $conn->prepare("DELETE FROM forum_topics WHERE community_id = ?");
    $stmt->bind_param("i", $community_id);
    $stmt->execute();
    $stmt->close();

    // 3. Supprimer la communauté elle-même
    $stmt = $conn->prepare("DELETE FROM forum_communities WHERE id = ?");
    $stmt->bind_param("i", $community_id);
    $stmt->execute();
    $stmt->close();

    // Valider la transaction
    $conn->commit();

    // Rediriger vers le forum avec un message de succès
    header('Location: ../pages/forum.php?success=community_deleted');
    exit;

} catch (Exception $e) {
    // En cas d'erreur, annuler la transaction
    $conn->rollback();
    
    // Rediriger vers le forum avec un message d'erreur
    header('Location: ../pages/forum.php?error=delete_failed');
    exit;
} 