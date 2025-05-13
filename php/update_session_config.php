<?php
// Script pour mettre à jour la configuration de session dans tous les fichiers PHP

// Définir le répertoire racine
$root_dir = dirname(__DIR__);

// Fonction pour parcourir récursivement un répertoire
function updateSessionConfig($dir) {
    global $root_dir;
    $files = scandir($dir);
    
    foreach ($files as $file) {
        if ($file === '.' || $file === '..' || $file === 'session_config.php') {
            continue;
        }
        
        $path = $dir . '/' . $file;
        
        if (is_dir($path)) {
            // Récursion dans les sous-répertoires
            updateSessionConfig($path);
        } elseif (pathinfo($path, PATHINFO_EXTENSION) === 'php') {
            // Traiter seulement les fichiers PHP
            $content = file_get_contents($path);
            
            // Vérifier si le fichier contient session_start()
            if (strpos($content, 'session_start()') !== false) {
                echo "Mise à jour du fichier: " . $path . "<br>";
                
                // Déterminer le chemin relatif vers session_config.php
                $relative_path = str_replace($root_dir, '', $dir);
                $depth = substr_count($relative_path, '/');
                $config_path = str_repeat('../', $depth) . 'php/session_config.php';
                
                // Si nous sommes dans le dossier php, ajuster le chemin
                if ($relative_path === '/php') {
                    $config_path = 'session_config.php';
                }
                
                // Remplacer session_start() par l'inclusion de la configuration
                $content = preg_replace(
                    '/session_start\(\);/i',
                    "// Remplacer session_start() par l'inclusion de la configuration\ninclude '$config_path';",
                    $content
                );
                
                // Écrire le contenu modifié dans le fichier
                file_put_contents($path, $content);
            }
        }
    }
}

// Démarrer la mise à jour
echo "Démarrage de la mise à jour des fichiers...<br>";
updateSessionConfig($root_dir);
echo "Mise à jour terminée!<br>";