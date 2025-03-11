<?php
session_start();

$is_admin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;

include 'php/db.php';

$categories = ['trending', 'upcoming']; // Catégories pour les sliders
$all_animes = []; // Tableau pour tous les animés
$category_images = [
    'trending' => [],
    'upcoming' => []
];

// Récupérer les images des catégories et tous les animés
$sql = "SELECT id, title, img, category FROM pages";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    // Parcourir les résultats et les classer par catégorie
    while ($row = $result->fetch_assoc()) {
        if (in_array($row['category'], $categories)) {
            $category_images[$row['category']][] = $row;
        }
        $all_animes[] = $row; // Ajouter tous les animés
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
        <?php foreach ($categories as $category): ?>
            <?php if (!empty($category_images[$category])): ?>
                <div class="<?php echo $category; ?>-slider-container">
                    <p><?php echo ucfirst($category); ?> <?php echo $category === 'trending' ? '🔥' : '⌛'; ?></p>
                    <div class="<?php echo $category; ?>-slider">
                        <button class="slider-button left">&#9664;</button>
                        <?php foreach ($category_images[$category] as $image): ?>
                            <div class="<?php echo $category; ?>-item" data-id="<?php echo $image['id']; ?>">
                                <img src="<?php echo $image['img']; ?>" alt="<?php echo $image['title']; ?>">
                                <p><?php echo $image['title']; ?></p>
                            </div>
                        <?php endforeach; ?>
                        <button class="slider-button right">&#9654;</button>
                    </div>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>

        <div class="all">
            <?php if (!empty($all_animes)): ?>
                <p>All Animes</p>
                <div class="anime-list">
                    <?php foreach ($all_animes as $anime): ?>
                        <div class="anime-item" data-id="<?php echo $anime['id']; ?>">
                            <img src="<?php echo $anime['img']; ?>" alt="<?php echo $anime['title']; ?>">
                            <p><?php echo $anime['title']; ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p>No animés found.</p>
            <?php endif; ?>
        </div>

    </main>
    <?php include 'partials/footer.php'; ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const animeItems = document.querySelectorAll('.anime-item, .trending-item, .upcoming-item');

            animeItems.forEach(item => {
                item.addEventListener('click', function() {
                    const id = item.getAttribute('data-id');
                    window.location.href = "pages/view_anime.php?id=" + id;
                });
            });
        });
    </script>
    <script src="js/scripts.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</body>
</html>