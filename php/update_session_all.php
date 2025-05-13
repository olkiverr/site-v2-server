<?php
/**
 * Script pour mettre à jour la gestion des sessions dans tous les fichiers PHP du site
 * Ce script remplace toutes les occurrences de l'ancienne gestion de session par
 * l'inclusion du fichier de configuration centralisé
 */

// Définir le répertoire racine
$root_dir = dirname(__DIR__);

// Compteurs pour les statistiques
$total_files = 0;
$updated_files = 0;

// Fonction pour mettre à jour un fichier PHP
function updateSessionConfig($file_path) {
    global $updated_files;
    
    // Lire le contenu du fichier
    $content = file_get_contents($file_path);
    
    // Ancienne méthode de gestion des sessions à remplacer
    $oldCode = "// Start the session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}";
    
    // Nouvelle méthode avec le fichier de configuration
    $relativePath = str_replace(dirname(__DIR__), '', dirname($file_path));
    $depth = substr_count($relativePath, DIRECTORY_SEPARATOR);
    $prefix = str_repeat('../', $depth);
    
    if (empty($prefix)) {
        $prefix = './';
    }
    
    $newCode = "// Remplacer la vérification de session par l'inclusion de la configuration
include_once '{$prefix}php/session_config.php'";
    
    // Alternative en une ligne
    $oldCodeOneLine = "if(session_status()==PHP_SESSION_NONE){session_start();}";
    $oldCodeAlt = "session_start();";
    
    // Rechercher et remplacer le code
    $updatedContent = str_replace($oldCode, $newCode, $content);
    $updatedContent = str_replace($oldCodeOneLine, $newCode, $updatedContent);
    $updatedContent = str_replace($oldCodeAlt, "include_once '{$prefix}php/session_config.php';", $updatedContent);
    
    // Vérifier si des modifications ont été apportées
    if ($updatedContent !== $content) {
        // Écrire le contenu mis à jour dans le fichier
        file_put_contents($file_path, $updatedContent);
        echo "Mise à jour du fichier : " . $file_path . "<br>";
        $updated_files++;
        return true;
    }
    
    return false;
}

// Fonction pour ajouter layout.css aux fichiers HTML
function addLayoutCSS($file_path) {
    // Lire le contenu du fichier
    $content = file_get_contents($file_path);
    
    // Vérifier si layout.css est déjà inclus
    if (strpos($content, 'layout.css') !== false) {
        return false;
    }
    
    // Rechercher les liens CSS header.css et footer.css
    if (preg_match('/(<link[^>]*footer\.css[^>]*>)/i', $content, $matches)) {
        $footerLink = $matches[1];
        
        // Déterminer le chemin relatif correct pour layout.css
        $layoutLink = str_replace('footer.css', 'layout.css', $footerLink);
        
        // Ajouter le lien layout.css après footer.css
        $updatedContent = str_replace($footerLink, $footerLink . "\n    " . $layoutLink, $content);
        
        // Écrire le contenu mis à jour dans le fichier
        file_put_contents($file_path, $updatedContent);
        echo "Ajout de layout.css au fichier : " . $file_path . "<br>";
        return true;
    }
    
    return false;
}

// Fonction pour parcourir récursivement un répertoire
function processDirectory($dir) {
    global $total_files;
    
    $files = scandir($dir);
    
    foreach ($files as $file) {
        if ($file === '.' || $file === '..' || $file === 'session_config.php' || $file === 'update_session_all.php') {
            continue;
        }
        
        $path = $dir . DIRECTORY_SEPARATOR . $file;
        
        if (is_dir($path)) {
            // Récursion dans les sous-répertoires
            processDirectory($path);
        } elseif (pathinfo($path, PATHINFO_EXTENSION) === 'php') {
            // Traiter les fichiers PHP
            $total_files++;
            updateSessionConfig($path);
            addLayoutCSS($path);
        }
    }
}

// Afficher l'en-tête
echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Mise à jour des sessions</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; margin: 20px; }
        h1, h2 { color: #333; }
        .result { margin: 20px 0; padding: 10px; background: #f0f0f0; border-radius: 5px; }
        .success { color: green; }
        .info { color: blue; }
    </style>
</head>
<body>
    <h1>Mise à jour de la gestion des sessions</h1>";

// Démarrer le processus
echo "<p>Démarrage de la mise à jour des fichiers...</p>";
processDirectory($root_dir);

// Afficher les statistiques
echo "<div class='result'>
    <h2>Résultats :</h2>
    <p><strong>Total des fichiers analysés :</strong> {$total_files}</p>
    <p><strong>Fichiers mis à jour :</strong> {$updated_files}</p>
</div>";

echo "<p class='success'>Mise à jour terminée !</p>";
echo "<p class='info'>Pour appliquer ces modifications, il faut vider le cache du navigateur et se reconnecter.</p>";
echo "</body></html>"; 