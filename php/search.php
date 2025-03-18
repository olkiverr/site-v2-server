<?php
include 'db.php';

if (isset($_GET['query'])) {
    $query = $_GET['query'];
    
    // Liste des genres interdits
    $forbidden_genres = ['Ecchi', 'Erotica', 'Hentai'];

    // Modification de la requête SQL pour corriger le problème
    $sql = "SELECT id, title, img FROM pages WHERE title LIKE ?";

    // Ajout de la condition pour exclure les genres interdits
    foreach ($forbidden_genres as $genre) {
        $sql .= " AND genres NOT LIKE ?";
    }

    $stmt = $conn->prepare($sql);

    // Préparation des paramètres avec des références
    $bindParams = array();
    $bindTypes = str_repeat('s', count($forbidden_genres) + 1);
    $bindParams[] = $bindTypes;
    
    // Ajout du paramètre de recherche
    $searchParam = "%{$query}%";
    $bindParams[] = $searchParam;
    
    // Ajout des paramètres pour les genres interdits
    foreach ($forbidden_genres as $genre) {
        $bindParams[] = "%{$genre}%";
    }
    
    // Création d'un tableau de références
    $refs = array();
    foreach($bindParams as $key => $value) {
        $refs[$key] = &$bindParams[$key];
    }

    // Bind des paramètres
    call_user_func_array(array($stmt, 'bind_param'), $refs);

    $stmt->execute();
    $result = $stmt->get_result();

    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }

    header('Content-Type: application/json');
    echo json_encode($data);
}

$conn->close();
?> 