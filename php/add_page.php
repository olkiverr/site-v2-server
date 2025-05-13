<?php
include 'db.php';
include 'session_config.php'; // Ajout pour accéder à $_SESSION

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['id'])) {
    echo "Erreur: Vous devez être connecté pour ajouter une page.";
    exit();
}

// Get form data
$title = mysqli_real_escape_string($conn, $_POST['title']);
$creator = mysqli_real_escape_string($conn, $_POST['creator']);
$broadcast = mysqli_real_escape_string($conn, $_POST['broadcast']);
$genres = mysqli_real_escape_string($conn, $_POST['genres']);
$episodes = mysqli_real_escape_string($conn, $_POST['episodes']);
$studio = mysqli_real_escape_string($conn, $_POST['studio']);
$description = mysqli_real_escape_string($conn, $_POST['description']);
$img = mysqli_real_escape_string($conn, $_POST['img']);
$category = mysqli_real_escape_string($conn, $_POST['category']);

// Récupérer les couleurs depuis le formulaire
$title_color = mysqli_real_escape_string($conn, $_POST['title_color']);
$label_color = mysqli_real_escape_string($conn, $_POST['label_color']);
$text_color = mysqli_real_escape_string($conn, $_POST['text_color']);
$border_color = mysqli_real_escape_string($conn, $_POST['border_color']);
$background_color = mysqli_real_escape_string($conn, $_POST['background_color']);

// Build the CSS style string
$style = "
.img-infos {
    display: flex;
    flex-direction: row;
    height: 40%;
    width: 100%;
    padding: 10px 0;
    background-color: $background_color;
}

.img {
    display: flex;
    justify-content: space-around;
    width: 30%;
    height: 100%;
}

.img > img {
    height: 100%;
    border-radius: 10px;
}

.infos {
    width: 70%;
    height: 100%;
    border-left: 1px solid $border_color;
    padding-left: 10px;
}

.infos h2 {
    color: $title_color;
}

.infos strong {
    color: $label_color;
}

.infos li {
    color: $text_color;
}

.description {
    color: $text_color;
    height: 60%;
    width: 100%;
    padding: 10px 0;
    background-color: $background_color;
}";

// Récupérer l'ID de l'utilisateur
$user_id = $_SESSION['id'];

// Préparer les noms de colonnes et les valeurs pour la procédure stockée
$column_names = "title, creator, broadcast, genres, episodes, studio, description, img, category, style, title_color, label_color, text_color, border_color, background_color";

$values = "'" . $title . "', '" . $creator . "', '" . $broadcast . "', '" . $genres . "', '" . $episodes . "', '" . 
          $studio . "', '" . $description . "', '" . $img . "', '" . $category . "', '" . $style . "', '" . 
          $title_color . "', '" . $label_color . "', '" . $text_color . "', '" . $border_color . "', '" . $background_color . "'";

// Utiliser la procédure stockée pour insérer les données
$stmt = $conn->prepare("CALL insert_data_admin_only(?, ?, ?, ?)");
$stmt->bind_param("isss", $user_id, $table_name, $column_names, $values);
$table_name = "pages";
$stmt->execute();

// Récupérer le résultat de la procédure
$result = $stmt->get_result();
$response = $result->fetch_assoc();

if ($response['success']) {
    header("Location: ../pages/admin_panel.php?tab=pages");
} else {
    echo "Erreur: " . $response['message'] . "<br>";
}

$stmt->close();
$conn->close();
?>
