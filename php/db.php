<?php
// Configuration du serveur distant
$remote_config = [
    'servername' => '172.16.20.219',
    'username' => 'zielinski-olivier',
    'password' => 'sofd', // Remplacez par le vrai mot de passe
    'dbname' => 'zielinski-olivier'
];

// Configuration du serveur local (fallback)
$local_config = [
    'servername' => 'localhost',
    'username' => 'root',
    'password' => '',
    'dbname' => 'mangamuse'
];

// Fonction pour tester la connexion
function tryConnection($config) {
    try {
        $conn = new mysqli(
            $config['servername'],
            $config['username'],
            $config['password'],
            $config['dbname']
        );

        // Vérifier la connexion
        if ($conn->connect_error) {
            return false;
        }

        // Définir le jeu de caractères
        $conn->set_charset("utf8mb4");
        
        return $conn;
    } catch (Exception $e) {
        return false;
    }
}

// Essayer d'abord la connexion distante
$conn = tryConnection($remote_config);

// Si la connexion distante échoue, utiliser la connexion locale
if (!$conn) {
    $conn = tryConnection($local_config);
    
    // Si les deux connexions échouent
    if (!$conn) {
        die("Erreur : Impossible de se connecter à la base de données.");
    }
}
?>