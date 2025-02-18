<?php
session_start();
include '../php/db.php';

if (isset($_SESSION['user'])) {
    $current_user = $_SESSION['user'];

    $sql = "SELECT * FROM users WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $current_user);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        $username = $user['username'];
        $email = $user['email'];
    }

    $stmt->close();
    $conn->close();
} else {
    echo "Utilisateur non connecté.";
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account - Mangamuse</title>
    <link rel="stylesheet" href="../css/account.css ">
    <link rel="stylesheet" href="../css/header.css">
    <link rel="icon" href="../img/MangaMuse_White-Book.png" type="image/x-icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cabin:ital,wght@0,400..700;1,400..700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <?php include '../partials/header.php'; ?> <!-- Include header partial -->
    <div class="account">
        <h2>Account</h2>
        <form action="" method="POST">
            <input type="text" name="new-name" id="name-input" value="<?php echo $username?>" disabled>
            <input type="email" name="new-email" id="email-input" value="<?php echo $email?>" disabled>
            <button type="submit" class="save-info" id="save-info" onclick="save()">Save</button>
        </form>
        <button class="edit-info" id="edit-info" onclick="toggleEdit()">Edit User Infos</button>
    </div>
    <?php include '../partials/footer.php'; ?>
    <script src="../js/account.js"></script>
</body>
</html>