<?php
include 'db.php';

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

// Insert into database
$sql = "INSERT INTO pages (title, creator, broadcast, genres, episodes, studio, description, img, category, style, 
        title_color, label_color, text_color, border_color, background_color) 
        VALUES ('$title', '$creator', '$broadcast', '$genres', '$episodes', '$studio', '$description', '$img', 
                '$category', '$style', '$title_color', '$label_color', '$text_color', '$border_color', '$background_color')";

if ($conn->query($sql) === TRUE) {
    header("Location: ../pages/admin_panel.php?tab=pages");
} else {
    echo "Error: " . $sql . "<br>" . $conn->error;
}

$conn->close();
?>
