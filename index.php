<?php
session_start();

$is_admin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;

include 'php/db.php';

$trending_images = [];
$upcoming_images = [];

// Requête pour récupérer les données de la table 'pages' pour le slider Trending
$sql = "SELECT id, title, img FROM pages WHERE category = 'trending'";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $trending_images[] = $row;
    }
}

// Requête pour récupérer les données de la table 'pages' pour le slider Upcoming
$sql = "SELECT id, title, img FROM pages WHERE category = 'upcoming'";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $upcoming_images[] = $row;
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mangamuse</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/header.css">
    <link rel="stylesheet" href="css/footer.css">
    <link rel="icon" href="img/MangaMuse_White-Book.png" type="image/x-icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cabin:ital,wght@0,400..700;1,400..700&display=swap" rel="stylesheet">
</head>
<body class="<?php echo $is_admin ? 'admin' : ''; ?>">
    <?php include 'partials/header.php'; ?> <!-- Include header partial -->
    <main>
        <div class="trending-slider-container">
            <p>Trending 🔥</p>
            <div class="trending-slider">
                <button class="slider-button left">&#9664;</button>
                <?php foreach ($trending_images as $image): ?>
                    <div class="trending-item" data-id="<?php echo $image['id']; ?>">
                        <img src="<?php echo $image['img']; ?>" alt="<?php echo $image['title']; ?>">
                        <p><?php echo $image['title']; ?></p>
                    </div>
                <?php endforeach; ?>
                <button class="slider-button right">&#9654;</button>
            </div>
        </div>

        <!-- Vérification si la liste des upcoming images est vide -->
        <?php if (!empty($upcoming_images)): ?>
            <div class="upcoming-slider-container">
                <p>Upcoming ⌛</p>
                <div class="upcoming-slider">
                    <button class="slider-button left">&#9664;</button>
                    <?php foreach ($upcoming_images as $image): ?>
                        <div class="upcoming-item" data-id="<?php echo $image['id']; ?>">
                            <img src="<?php echo $image['img']; ?>" alt="<?php echo $image['title']; ?>">
                            <p><?php echo $image['title']; ?></p>
                        </div>
                    <?php endforeach; ?>
                    <button class="slider-button right">&#9654;</button>
                </div>
            </div>
        <?php endif; ?>
    </main>
    <?php include 'partials/footer.php'; ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const trendingItems = document.querySelectorAll('.trending-item, .upcoming-item');

            trendingItems.forEach(item => {
                item.addEventListener('click', function() {
                    const id = item.getAttribute('data-id');
                    console.log(id);
                    window.location.href = "pages/view_anime.php?id=" + id;
                });
            });
        });
    </script>
    <script src="js/scripts.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</body>
</html>
