<?php
/**
 * Configuration des sessions pour éviter les conflits avec d'autres sites
 * sur le même serveur.
 */

// Inclure le système d'analytics
require_once __DIR__ . '/analytics.php';

// Démarrer la session si elle n'est pas déjà démarrée
if (session_status() == PHP_SESSION_NONE) {
    // Configurer les cookies de session
    session_name('MangaMuse');
    
    // Définir les options de cookie sécurisées
    $cookie_params = session_get_cookie_params();
    session_set_cookie_params([
        'lifetime' => $cookie_params['lifetime'],
        'path' => '/',
        'domain' => '',
        'secure' => false, // À activer en production et avec HTTPS
        'httponly' => true,
        'samesite' => 'Lax' // Protège contre les attaques CSRF
    ]);
    
    session_start();
} 