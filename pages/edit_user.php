<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User</title>
    <link rel="stylesheet" href="../css/header.css">
    <link rel="stylesheet" href="../css/footer.css">
    <link rel="stylesheet" href="../css/edit-user.css">
    <link rel="icon" href="../img/MangaMuse_White-Book.png" type="image/x-icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cabin:ital,wght@0,400..700&display=swap" rel="stylesheet">
</head>
<body>
    <?php include '../partials/header.php'; ?>
    <main>
        <section id="main-content">
            <h2>Edit User</h2>
            <?php
            include '../php/db.php';
            $id = $_GET['id'];
            $sql = "SELECT * FROM users WHERE id='$id'";
            $result = $conn->query($sql);
            $user = $result->fetch_assoc();
            ?>
            <form action="../php/edit_user.php" method="post">
                <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" value="<?php echo $user['username']; ?>" required>
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" value="<?php echo $user['email']; ?>" required>
                <label for="password">Password:</label>
                <input type="password" id="password" name="password">
                <label for="is_admin">Admin:</label>
                <input type="checkbox" id="is_admin" name="is_admin" <?php echo $user['is_admin'] ? 'checked' : ''; ?>>
                <button type="submit">Update User</button>
            </form>
            <a href="admin_panel.php?tab=users" class="return-button">Return to Admin Panel</a>
        </section>
    </main>
    <?php include '../partials/footer.php'; ?>
</body>
</html>
