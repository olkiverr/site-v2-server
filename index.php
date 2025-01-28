<?php
session_start();

$is_admin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;

include 'php/db.php';

$trending_images = [];
$upcoming_images = [];

// Adjust column names as per your database schema
$sql = "SELECT id, url, name FROM images WHERE category = 'trending'";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $trending_images[] = $row;
    }
}

$sql = "SELECT id, url, name FROM images WHERE category = 'upcoming'";
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
        <?php if ($is_admin): ?>
            <p>Trending 🔥<span><img src="img/cog.png" alt="cog" class="admin-cog" onclick="toggleEditMenu('trending')"></span></p>
            <div id="trending-edit-menu" class="edit-menu">
                <form id="trending-edit-form" enctype="multipart/form-data">
                    <label for="trending-image">Image URL:</label>
                    <input type="text" id="trending-image" name="image">
                    <label for="trending-title">Title:</label>
                    <input type="text" id="trending-title" name="title">
                    <button type="button" onclick="addImage('trending')">Add</button>
                    <button type="button" onclick="deleteImage('trending')">Delete</button>
                    <button type="button" onclick="location.reload()">Update</button>
                </form>
            </div>
        <?php else: ?>
            <p>Trending 🔥</p>
        <?php endif; ?>
            <div class="trending-slider">
                <button class="slider-button left">&#9664;</button>
                <?php foreach ($trending_images as $image): ?>
                    <div class="trending-item" data-id="<?php echo $image['id']; ?>">
                        <img src="<?php echo $image['url']; ?>" alt="<?php echo $image['name']; ?>">
                        <p><?php echo $image['name']; ?></p>
                    </div>
                <?php endforeach; ?>
                <button class="slider-button right">&#9654;</button>
            </div>
        </div>
        <div class="upcoming-slider-container">
        <?php if ($is_admin): ?>
            <p>Upcoming ⌛<span><img src="img/cog.png" alt="cog" class="admin-cog" onclick="toggleEditMenu('upcoming')"></span></p>
            <div id="upcoming-edit-menu" class="edit-menu">
                <form id="upcoming-edit-form" enctype="multipart/form-data">
                    <label for="upcoming-image">Image URL:</label>
                    <input type="text" id="upcoming-image" name="image">
                    <label for="upcoming-title">Title:</label>
                    <input type="text" id="upcoming-title" name="title">
                    <button type="button" onclick="addImage('upcoming')">Add</button>
                    <button type="button" onclick="deleteImage('upcoming')">Delete</button>
                    <button type="button" onclick="location.reload()">Update</button>
                </form>
            </div>
        <?php else: ?>
            <p>Upcoming ⌛</p>
        <?php endif; ?>
            <div class="upcoming-slider">
                <button class="slider-button left">&#9664;</button>
                <?php foreach ($upcoming_images as $image): ?>
                    <div class="upcoming-item" data-id="<?php echo $image['id']; ?>">
                        <img src="<?php echo $image['url']; ?>" alt="<?php echo $image['name']; ?>">
                        <p><?php echo $image['name']; ?></p>
                    </div>
                <?php endforeach; ?>
                <button class="slider-button right">&#9654;</button>
            </div>
        </div>
    </main>
    <?php include 'partials/footer.php'; ?> <!-- Include footer partial -->
    <script src="js/scripts.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</body>
</html>