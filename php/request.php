<?php
// Paramètres de votre application
$client_id = '436c15b8c397f78fef4377bf3c61d6b4';
$client_secret = '12b685b4bab557fc1b6d3fe02f93be54814a8ef89fd50dc9878f92eeb19d61dc';
$redirect_uri = 'http://172.16.20.219/4TTJ/Zielinski%20Olivier/Site/site-v2/index.php';

// Étape 1 : Récupérer le code d'autorisation depuis l'URL
$code = $_GET['code'] ?? null;

if ($code) {
    // Étape 2 : Échanger le code contre un jeton d'accès
    $url = 'https://myanimelist.net/v1/oauth2/token';
    $data = [
        'code' => $code,
        'client_id' => $client_id,
        'client_secret' => $client_secret,
        'redirect_uri' => $redirect_uri,
        'grant_type' => 'authorization_code',
    ];

    $options = [
        'http' => [
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'content' => http_build_query($data),
        ],
    ];

    $context = stream_context_create($options);
    $response = file_get_contents($url, false, $context);

    if ($response === FALSE) {
        // Gérer l'erreur
        die('Erreur lors de la récupération du jeton d\'accès.');
    }

    $json = json_decode($response, true);
    $access_token = $json['access_token'] ?? null;

    if ($access_token) {
        // Le jeton d'accès est obtenu avec succès
        // Stockez-le dans une session ou une base de données selon vos besoins
    } else {
        // Gérer l'erreur si le jeton d'accès n'est pas présent
        die('Jeton d\'accès non reçu.');
    }
} else {
    // Gérer le cas où le code d'autorisation n'est pas présent dans l'URL
    die('Code d\'autorisation manquant.');
}
?>
