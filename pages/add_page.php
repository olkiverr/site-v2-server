<?php
session_start();
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header('Location: /4TTJ/Zielinski%20Olivier/Site/site-v2/index.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Page</title>
    <link rel="stylesheet" href="../css/admin-panel.css">
    <link rel="stylesheet" href="../css/header.css">
    <link rel="stylesheet" href="../css/footer.css">
    <link rel="icon" href="../img/MangaMuse_White-Book.png" type="image/x-icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cabin:ital,wght@0,400..700;1,400..700&display=swap" rel="stylesheet">
</head>
<body>
    <?php include '../partials/header.php'; ?>
    <div class="admin-panel">
        <h2>Add New Page</h2>
        <form action="../php/add_page.php" method="post">
            <div class="form-group">
                <label for="title">Title:</label>
                <input type="text" id="title" name="title" required>
            </div>
            
            <div class="form-group">
                <label for="creator">Creator:</label>
                <input type="text" id="creator" name="creator" required>
            </div>
            
            <div class="form-group">
                <label for="broadcast">Broadcast:</label>
                <input type="text" id="broadcast" name="broadcast" required>
            </div>
            
            <div class="form-group">
                <label for="genres">Genres:</label>
                <input type="text" id="genres" name="genres" required>
            </div>
            
            <div class="form-group">
                <label for="episodes">Episodes:</label>
                <input type="text" id="episodes" name="episodes" required>
            </div>
            
            <div class="form-group">
                <label for="studio">Studio:</label>
                <input type="text" id="studio" name="studio" required>
            </div>
            
            <div class="form-group">
                <label for="description">Description:</label>
                <textarea id="description" name="description" rows="4" required></textarea>
            </div>
            
            <div class="form-group">
                <label for="img">Image URL:</label>
                <select id="img" name="img" required onchange="updateImagePreview(this.value)">
                    <option value="">Select an image</option>
                    <?php
                    $imgDirectory = '../anime_imgs/';
                    $images = glob($imgDirectory . "*.{jpg,jpeg,png,gif}", GLOB_BRACE);
                    
                    foreach($images as $image) {
                        $imageName = basename($image);
                        $imageUrl = '/4TTJ/Zielinski%20Olivier/Site/site-v2/anime_imgs/' . $imageName;
                        echo "<option value='" . $imageUrl . "'>" . $imageName . "</option>";
                    }
                    ?>
                </select>
                <div class="image-preview">
                    <img id="preview" src="" alt="Preview" style="display: none; max-width: 200px; margin-top: 10px;">
                </div>
            </div>
            
            <div class="form-group">
                <label for="category">Category:</label>
                <select id="category" name="category" required>
                    <option value="none">None</option>
                    <option value="trending">Trending</option>
                    <option value="upcoming">Upcoming</option>
                </select>
            </div>
            
            <div class="style-options">
                <h3>Style Options</h3>
                <div class="form-group">
                    <label for="title_color">Title Color:</label>
                    <input type="color" id="title_color" name="title_color" value="#ffffff">
                </div>
                
                <div class="form-group">
                    <label for="label_color">Label Color:</label>
                    <input type="color" id="label_color" name="label_color" value="#ffffff">
                </div>
                
                <div class="form-group">
                    <label for="text_color">Text Color:</label>
                    <input type="color" id="text_color" name="text_color" value="#ffffff">
                </div>
                
                <div class="form-group">
                    <label for="border_color">Border Color:</label>
                    <input type="color" id="border_color" name="border_color" value="#333333">
                </div>
                
                <div class="form-group">
                    <label for="background_color">Background Color:</label>
                    <input type="color" id="background_color" name="background_color" value="#252525">
                </div>
            </div>
            
            <button type="submit">Add Page</button>
            <a href="admin_panel.php?tab=pages" class="button">Cancel</a>
        </form>
    </div>
    <?php include '../partials/footer.php'; ?>
    <script>
        function updateImagePreview(url) {
            const preview = document.getElementById('preview');
            if (url) {
                preview.src = url;
                preview.style.display = 'block';
            } else {
                preview.style.display = 'none';
            }
        }
    </script>
</body>
</html> 