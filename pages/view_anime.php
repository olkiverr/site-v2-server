<?php
session_start();
$id = isset($_GET['id']) ? $_GET['id'] : null;
include '../php/db.php';
$sql = "SELECT * FROM pages WHERE id = '$id'";
$result = mysqli_query($conn, $sql);
$row = mysqli_fetch_assoc($result);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $row['title']; ?> - Mangamuse</title>
    <link rel="stylesheet" href="../css/view-page.css">
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
    
    <style><?php echo htmlspecialchars($row['style']); ?></style>

    <h1><?php echo htmlspecialchars($row['title']); ?></h1>

    <div class="page-prev">

        <div class="img-infos">
            <div class="img">
                <img src="<?php echo htmlspecialchars($row['img']); ?>" alt="<?php echo htmlspecialchars($row['title']); ?>">
            </div>
            <div class="infos">
                <h2><?php echo htmlspecialchars($row['title']); ?></h2>
                <ul>
                    <li><strong>Creator: </strong><?php echo htmlspecialchars($row['creator']); ?></li>
                    <li><strong>Broadcast: </strong><?php echo htmlspecialchars($row['broadcast']); ?></li>
                    <li><strong>Genres: </strong><?php echo htmlspecialchars($row['genres']); ?></li>
                    <li><strong>Episodes: </strong><?php echo htmlspecialchars($row['episodes']); ?></li>
                    <li><strong>Studio: </strong><?php echo htmlspecialchars($row['studio']); ?></li>
                </ul>
            </div>
        </div>
        <div class="description">
            <p><?php echo htmlspecialchars($row['description']); ?></p>
        </div>
    </div>

    </main>
    <?php include '../partials/footer.php' ?>
</body>
</html>
