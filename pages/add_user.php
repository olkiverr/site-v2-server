<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add User</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="../css/header.css">
    <link rel="stylesheet" href="../css/add-user.css">
    <link rel="icon" href="../img/MangaMuse_White-Book.png" type="image/x-icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cabin:ital,wght@0,400..700;1,400..700&display=swap" rel="stylesheet">
</head>
<body>
    <?php include '../partials/header.php'; ?>
    <main>
        <section id="main-content">
            <h2>Add User</h2>
            <form action="../php/add_user.php" method="post">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" required>
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required>
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
                <label for="is_admin">Admin:</label>
                <input type="checkbox" id="is_admin" name="is_admin">
                <button type="submit">Add User</button>
            </form>
            <a href="admin_panel.php?tab=users" class="return-button">Return to Admin Panel</a>
        </section>
    </main>
    <?php include '../partials/footer.php'; ?>
</body>
</html>
