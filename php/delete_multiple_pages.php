<?php
session_start();

// Vérifier si l'utilisateur est un administrateur
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    http_response_code(403); // Forbidden
    echo "Accès refusé";
    exit;
}

// Vérifier si des IDs ont été envoyés
if (!isset($_POST['ids'])) {
    http_response_code(400); // Bad Request
    echo "Aucun ID fourni";
    exit;
}

// Récupérer et valider les IDs
$ids = json_decode($_POST['ids']);
if (!is_array($ids) || empty($ids)) {
    http_response_code(400); // Bad Request
    echo "IDs invalides";
    exit;
}

// Se connecter à la base de données
include 'db.php';

// Préparer les IDs pour la requête SQL
$placeholders = implode(',', array_fill(0, count($ids), '?'));
$types = str_repeat('i', count($ids));

// Préparer la requête de suppression
$sql = "DELETE FROM pages WHERE id IN ($placeholders)";
$stmt = $conn->prepare($sql);

// Lier les paramètres
$stmt->bind_param($types, ...$ids);

// Exécuter la requête
if ($stmt->execute()) {
    // Succès
    echo "Pages supprimées avec succès";
} else {
    // Erreur
    http_response_code(500); // Internal Server Error
    echo "Erreur lors de la suppression des pages: " . $conn->error;
}

// Fermer la connexion
$stmt->close();
$conn->close(); 