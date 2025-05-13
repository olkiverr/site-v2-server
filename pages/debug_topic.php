<?php
require_once __DIR__ . '/../php/db.php';
require_once __DIR__ . '/../php/forum/forum_functions.php';

// Remplacer la vérification de session par l'inclusion de la configuration
include_once '../php/session_config.php';

// Activer l'affichage des erreurs
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Débogage de la page de sujet</h1>";

// Get topic ID from URL
$topic_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
echo "<p>ID du sujet demandé: " . $topic_id . "</p>";

if ($topic_id <= 0) {
    echo "<p>Erreur: ID de sujet invalide</p>";
    exit;
}

// Get topic details
$topic = getTopic($topic_id);

if (!$topic) {
    echo "<p>Erreur: Sujet non trouvé</p>";
    exit;
}

echo "<h2>Détails du sujet</h2>";
echo "<pre>";
print_r($topic);
echo "</pre>";

// Get community details
$community = getCommunity($topic['community_id']);

echo "<h2>Community Details</h2>";
echo "<pre>";
print_r($community);
echo "</pre>";

// Tester les liens
echo "<h2>Liens testés</h2>";
echo "<p>Lien direct vers le sujet: <a href='/4TTJ/Zielinski%20Olivier/Site/site-v2/pages/forum_topic.php?id=" . $topic_id . "'>forum_topic.php?id=" . $topic_id . "</a></p>";
echo "<p>Link to community: <a href='/4TTJ/Zielinski%20Olivier/Site/site-v2/pages/m.php?slug=" . urlencode($topic['community_slug']) . "'>m.php?slug=" . $topic['community_slug'] . "</a></p>";

// Vérifier si la redirection se fait à partir de la page m.php
echo "<h2>Test de redirection</h2>";
echo "<p>URL de redirection depuis m.php: <code>/4TTJ/Zielinski%20Olivier/Site/site-v2/pages/forum_topic.php?id=" . $topic_id . "</code></p>";
?> 