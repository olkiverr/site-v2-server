<?php
session_start();
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header('Location: /4TTJ/Zielinski%20Olivier/Site/site-v2/index.php');
    exit();
}

$success = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    include '../php/db.php';
    
    $id = $_POST['id'];
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $creator = mysqli_real_escape_string($conn, $_POST['creator']);
    $broadcast = mysqli_real_escape_string($conn, $_POST['broadcast']);
    $genres = mysqli_real_escape_string($conn, $_POST['genres']);
    $episodes = mysqli_real_escape_string($conn, $_POST['episodes']);
    $studio = mysqli_real_escape_string($conn, $_POST['studio']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $img = mysqli_real_escape_string($conn, $_POST['img']);
    $category = mysqli_real_escape_string($conn, $_POST['category']);
    
    // Build the CSS style string
    $style = "
.img-infos {
    display: flex;
    flex-direction: row;
    height: 40%;
    width: 100%;
    padding: 10px 0;
    background-color: " . $_POST['background_color'] . ";
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
    border-left: 1px solid " . $_POST['border_color'] . ";
    padding-left: 10px;
}

.infos h2 {
    color: " . $_POST['title_color'] . ";
}

.infos strong {
    color: " . $_POST['label_color'] . ";
}

.infos li {
    color: " . $_POST['text_color'] . ";
}

.description {
    color: " . $_POST['text_color'] . ";
    height: 60%;
    width: 100%;
    padding: 10px 0;
    background-color: " . $_POST['background_color'] . ";
}";

    $style = mysqli_real_escape_string($conn, $style);
    
    $sql = "UPDATE pages SET title='$title', creator='$creator', broadcast='$broadcast', 
            genres='$genres', episodes='$episodes', studio='$studio', 
            description='$description', img='$img', category='$category', style='$style' WHERE id='$id'";
    
    if (mysqli_query($conn, $sql)) {
        $success = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel</title>
    <link rel="stylesheet" href="../css/edit-page.css">
    <link rel="stylesheet" href="../css/header.css">
    <link rel="stylesheet" href="../css/footer.css">
    <link rel="icon" href="../img/MangaMuse_White-Book.png" type="image/x-icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cabin:ital,wght@0,400..700;1,400..700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <?php include '../partials/header.php'; ?>
    <main>
    <?php
    $id = isset($_GET['id']) ? $_GET['id'] : null;

    include '../php/db.php';
    $sql = "SELECT * FROM pages WHERE id = '$id'";
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);

    // Extract colors from style
    preg_match('/title_color: ([^;]+)/', $row['style'], $title_color);
    preg_match('/label_color: ([^;]+)/', $row['style'], $label_color);
    preg_match('/text_color: ([^;]+)/', $row['style'], $text_color);
    preg_match('/border_color: ([^;]+)/', $row['style'], $border_color);
    preg_match('/background-color: ([^;]+)/', $row['style'], $background_color);

    $title_color = isset($title_color[1]) ? $title_color[1] : '#FFFFFF';
    $label_color = isset($label_color[1]) ? $label_color[1] : '#FFFFFF';
    $text_color = isset($text_color[1]) ? $text_color[1] : '#FFFFFF';
    $border_color = isset($border_color[1]) ? $border_color[1] : '#9E9E9E';
    $background_color = isset($background_color[1]) ? $background_color[1] : '#000000';
    ?>
    <style><?php echo htmlspecialchars($row['style']); ?></style>

    <h1>Page <?php echo htmlspecialchars($row['id']); ?></h1>

    <?php if ($success): ?>
        <script>
            window.location.href = "view_anime.php?id=<?php echo htmlspecialchars($id); ?>";
        </script>
    <?php endif; ?>

    <form method="POST">
        <input type="hidden" name="id" value="<?php echo htmlspecialchars($row['id']); ?>">
        <div class="page-prev">
            <div class="img-infos">
                <div class="img">
                    <img src="<?php echo htmlspecialchars($row['img']); ?>" alt="<?php echo htmlspecialchars($row['title']); ?>">
                    <input type="text" name="img" value="<?php echo htmlspecialchars($row['img']); ?>" style="margin-top: 10px; width: 100%;" placeholder="Image URL">
                </div>
                <div class="infos">
                    <input type="text" name="title" value="<?php echo htmlspecialchars($row['title']); ?>" style="font-size: 1.5em; margin-bottom: 10px;">
                    <ul>
                        <li><strong>Creator: </strong><input type="text" name="creator" value="<?php echo htmlspecialchars($row['creator']); ?>"></li>
                        <li><strong>Broadcast: </strong><input type="text" name="broadcast" value="<?php echo htmlspecialchars($row['broadcast']); ?>"></li>
                        <li><strong>Genres: </strong><input type="text" name="genres" value="<?php echo htmlspecialchars($row['genres']); ?>"></li>
                        <li><strong>Episodes: </strong><input type="text" name="episodes" value="<?php echo htmlspecialchars($row['episodes']); ?>"></li>
                        <li><strong>Studio: </strong><input type="text" name="studio" value="<?php echo htmlspecialchars($row['studio']); ?>"></li>
                        <li><strong>Category: </strong>
                            <select name="category">
                                <option value="none" <?php echo ($row['category'] == 'none' ? 'selected' : ''); ?>>None</option>
                                <option value="trending" <?php echo ($row['category'] == 'trending' ? 'selected' : ''); ?>>Trending</option>
                                <option value="upcoming" <?php echo ($row['category'] == 'upcoming' ? 'selected' : ''); ?>>Upcoming</option>
                            </select>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="description">
                <textarea name="description" rows="4" style="width: 100%;"><?php echo htmlspecialchars($row['description']); ?></textarea>
            </div>
            <div class="style-options" style="margin-top: 20px;">
                <h3>Style Options</h3>
                <label>Title Color: <input type="color" name="title_color" value="<?php echo $title_color; ?>"></label>
                <label>Label Color: <input type="color" name="label_color" value="<?php echo $label_color; ?>"></label>
                <label>Text Color: <input type="color" name="text_color" value="<?php echo $text_color; ?>"></label>
                <label>Border Color: <input type="color" name="border_color" value="<?php echo $border_color; ?>"></label>
                <label>Background Color: <input type="color" name="background_color" value="<?php echo $background_color; ?>"></label>
            </div>
            <button type="submit" style="margin-top: 20px;">Save Changes</button>
        </div>
    </form>

    </main>
    <?php include '../partials/footer.php' ?>
</body>
</html>
