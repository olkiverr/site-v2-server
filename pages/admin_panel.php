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
    <title>Admin Panel</title>
    <link rel="stylesheet" href="../css/admin-panel.css">
    <link rel="stylesheet" href="../css/header.css">
    <link rel="stylesheet" href="../css/footer.css">
    <link rel="icon" href="../img/MangaMuse_White-Book.png" type="image/x-icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cabin:ital,wght@0,400..700;1,400..700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="../js/admin-panel.js"></script>
</head>
<body>
    <?php include '../partials/header.php'; ?> <!-- Include header partial -->
    <div class="admin-panel">
        <h2>Admin Panel</h2>
        <div class="tabs">
            <div class="tab active-tab" data-tab="dashboard">Dashboard</div>
            <div class="tab" data-tab="users">Users</div>
            <div class="tab" data-tab="pages">Pages</div>
            <div class="tab" data-tab="settings">Settings</div>
        </div>
        <div class="tab-content active-tab" id="dashboard">
            <h3>Dashboard</h3>
            <p>Welcome to the admin dashboard. Here you can find an overview of the site statistics and recent activities.</p>
            <div class="dashboard-info">
                <div class="info-box small-box">
                    <h4>Total Users</h4>
                    <p><?php
                        include '../php/db.php';
                        $sql = "SELECT COUNT(*) as total_users FROM users";
                        $result = $conn->query($sql);
                        $row = $result->fetch_assoc();
                        echo $row['total_users'];
                    ?></p>
                </div>
                <div class="info-box small-box">
                    <h4>Total Admins</h4>
                    <p><?php
                        $sql = "SELECT COUNT(*) as total_admins FROM users WHERE is_admin = 1";
                        $result = $conn->query($sql);
                        $row = $result->fetch_assoc();
                        echo $row['total_admins'];
                    ?></p>
                </div>
                <div class="info-box small-box">
                    <h4>Total Animes</h4>
                    <p><?php
                        $sql = "SELECT COUNT(*) as total_pages FROM pages";
                        $result = $conn->query($sql);
                        $row = $result->fetch_assoc();
                        echo $row['total_pages'];
                    ?></p>
                </div>
            </div>
            <div class="info-box large-chart">
                <h4>Connections Over Time</h4>
                <canvas id="connectionsChart"></canvas>
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        const ctx = document.getElementById('connectionsChart').getContext('2d');
                        const connectionsChart = new Chart(ctx, {
                            type: 'line',
                            data: {
                                labels: ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
                                datasets: [{
                                    label: 'Connections',
                                    data: [90, 50, 20, 100, 20, 50, 50, 90, 20, 100, 50, 50],
                                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                                    borderColor: 'rgba(75, 192, 192, 1)',
                                    borderWidth: 1
                                }]
                            },
                            options: {
                                scales: {
                                    y: {
                                        beginAtZero: true
                                    }
                                }
                            }
                        });
                    });
                </script>
            </div>
        </div>
        <div class="tab-content" id="users">
            <h3>Manage Users</h3>
            <input type="text" id="userSearch" placeholder="Search for users..." onkeyup="searchUsers()">
            <button onclick="location.href='add_user.php?tab=users'">Add User</button>
            <?php
            // Fetch users from the database
            include_once '../php/db.php';
            $sql = "SELECT id, username, email, is_admin FROM users";
            $result = $conn->query($sql);

            if ($result->num_rows > 0) {
                echo "<table id='usersTable'>";
                echo "<tr><th>ID</th><th>Username</th><th>Email</th><th>Admin</th><th>Actions</th></tr>";
                while($row = $result->fetch_assoc()) {
                    $isAdmin = $row["is_admin"] ? "Yes" : "No";
                    echo "<tr><td>" . $row["id"]. "</td><td>" . $row["username"]. "</td><td>" . $row["email"]. "</td><td>" . $isAdmin . "</td><td><a href='edit_user.php?id=" . $row["id"] . "&tab=users' class='button'>Edit</a> <a href='../php/delete_user.php?id=" . $row["id"] . "&tab=users' class='button'>Delete</a></td></tr>";
                }
                echo "</table>";
            } else {
                echo "0 results";
            }
            ?>
        </div>
        <div class="tab-content" id="pages">
            <h3>Pages</h3>
            <button onclick="location.href='add_page.php'" class="add-button">Add New Page</button>
            
            <!-- Ajout de la barre de recherche et du filtre par genre -->
            <div class="search-filter-container">
                <div class="search-container">
                    <input type="text" id="pageSearch" placeholder="Search for anime..." onkeyup="searchAnime()">
                </div>
                <div class="filter-container">
                    <select id="genreFilter" onchange="filterByGenre()">
                        <option value="">All Genres</option>
                        <?php
                        // Récupérer tous les genres uniques de la base de données
                        $genres_query = "SELECT DISTINCT genres FROM pages";
                        $genres_result = $conn->query($genres_query);
                        
                        // Tableau pour stocker tous les genres uniques
                        $all_genres = array();
                        
                        // Parcourir tous les enregistrements
                        while ($genre_row = $genres_result->fetch_assoc()) {
                            // Diviser la chaîne de genres en un tableau
                            $genre_list = explode(', ', $genre_row['genres']);
                            
                            // Ajouter chaque genre au tableau global
                            foreach ($genre_list as $genre) {
                                $genre = trim($genre);
                                if (!in_array($genre, $all_genres) && !empty($genre)) {
                                    $all_genres[] = $genre;
                                }
                            }
                        }
                        
                        // Trier les genres par ordre alphabétique
                        sort($all_genres);
                        
                        // Afficher chaque genre comme option dans la liste déroulante
                        foreach ($all_genres as $genre) {
                            echo "<option value=\"" . htmlspecialchars($genre) . "\">" . htmlspecialchars($genre) . "</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="select-all-container">
                    <button id="select-all-btn" onclick="showSelectAllConfirmation()">
                        <i class="fas fa-check-square"></i> Tout sélectionner
                    </button>
                </div>
            </div>
            
            <!-- Ajout de l'indicateur de résultats -->
            <div class="search-results-info">
                <span id="results-count">
                    <?php echo $result->num_rows; ?> animés trouvés
                </span>
            </div>
            
            <?php
            include_once '../php/db.php';
            $sql = "SELECT * FROM pages";
            $result = $conn->query($sql);
            ?>
            <div class="tabs-pages" id="anime-container">
                <?php 
                while ($row = $result->fetch_assoc()): ?>
                    <div class="tab-page" data-tab="<?php echo $row['id']; ?>" data-title="<?php echo htmlspecialchars($row['title']); ?>" data-genres="<?php echo htmlspecialchars($row['genres']); ?>">
                        <img src="<?php echo htmlspecialchars($row['img']); ?>" alt="<?php echo htmlspecialchars($row['title']); ?>" class="anime-thumbnail">
                        <div class="anime-title"><?php echo htmlspecialchars($row['title']); ?></div> <!-- Le titre ici -->
                        <div class="select-checkbox" data-id="<?php echo $row['id']; ?>">
                            <i class="far fa-square"></i>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
            
            <!-- Bouton de suppression de la sélection -->
            <div id="delete-selection-container">
                <button id="delete-selection-btn" style="display: none;">
                    <i class="fas fa-trash-alt"></i> Supprimer la sélection
                </button>
            </div>
            
            <!-- Modal de confirmation -->
            <div id="delete-confirm-modal" class="modal">
                <div class="modal-content">
                    <h4>Confirmer la suppression</h4>
                    <p>Êtes-vous sûr de vouloir supprimer les <span id="count-selected">0</span> animés sélectionnés?</p>
                    <div class="modal-actions">
                        <button id="confirm-delete">Confirmer</button>
                        <button id="cancel-delete">Annuler</button>
                    </div>
                </div>
            </div>
            
            <!-- Modal de confirmation pour tout sélectionner -->
            <div id="select-all-confirm-modal" class="modal">
                <div class="modal-content">
                    <h4>Tout sélectionner</h4>
                    <p>Voulez-vous sélectionner tous les <span id="count-visible">0</span> animés actuellement affichés?</p>
                    <div class="modal-actions">
                        <button id="confirm-select-all">Confirmer</button>
                        <button id="cancel-select-all">Annuler</button>
                    </div>
                </div>
            </div>
        </div>
        <div class="tab-content" id="settings">
            <h3>Site Settings</h3>
            <form>
                <label for="site-name">Site Name</label>
                <input type="text" id="site-name" name="site-name">
                <label for="admin-email">Admin Email</label>
                <input type="email" id="admin-email" name="admin-email">
                <button type="submit">Save Settings</button>
            </form>
        </div>
    </div>
    <?php include '../partials/footer.php'; ?> <!-- Include footer partial -->
</body>
</html>
