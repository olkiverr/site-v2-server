<?php
session_start();
$id = isset($_GET['id']) ? $_GET['id'] : null;
include '../php/db.php';
$sql = "SELECT * FROM pages WHERE id = '$id'";
$result = mysqli_query($conn, $sql);
$row = mysqli_fetch_assoc($result);

// Récupérer le statut de l'anime pour l'utilisateur connecté
$user_id = null;
$status = [
    'is_favorite' => 0,
    'is_watched' => 0
];

if (isset($_SESSION['id'])) {
    $user_id = $_SESSION['id'];
} elseif (isset($_SESSION['is_admin'])) {
    // Pour l'admin, vérifier si id est défini, sinon utiliser une valeur par défaut
    $user_id = isset($_SESSION['id']) ? $_SESSION['id'] : 1;
}

if ($user_id) {
    $status_sql = "SELECT is_favorite, is_watched FROM user_anime_status WHERE user_id = ? AND anime_id = ?";
    $stmt = $conn->prepare($status_sql);
    $stmt->bind_param("ii", $user_id, $id);
    $stmt->execute();
    $status_result = $stmt->get_result();
    
    if ($status_result->num_rows > 0) {
        $status = $status_result->fetch_assoc();
    }
}
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .status-controls {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-top: 15px;
            width: 100%;
        }
        
        .status-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            background-color: #333;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .status-btn:hover {
            background-color: #444;
        }
        
        .status-btn.active {
            background-color: #555;
        }
        
        .status-btn i.fa-star {
            color: #ffd700;
        }
        
        .status-btn i.fa-eye, .status-btn i.fa-eye-slash {
            color: #ffffff;
        }
    </style>
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
                
                <?php if ($user_id): ?>
                <div class="status-controls">
                    <!-- Bouton "vu" -->
                    <button id="watched-btn" class="status-btn <?php echo $status['is_watched'] ? 'active' : ''; ?>" data-id="<?php echo $id; ?>" data-type="watched">
                        <i class="fas <?php echo $status['is_watched'] ? 'fa-eye' : 'fa-eye-slash'; ?>"></i>
                        <?php echo $status['is_watched'] ? 'Vu' : 'Non vu'; ?>
                    </button>
                    
                    <!-- Bouton "favoris" -->
                    <button id="favorite-btn" class="status-btn <?php echo $status['is_favorite'] ? 'active' : ''; ?>" data-id="<?php echo $id; ?>" data-type="favorite">
                        <i class="<?php echo $status['is_favorite'] ? 'fas' : 'far'; ?> fa-star"></i>
                        <?php echo $status['is_favorite'] ? 'Favori' : 'Ajouter aux favoris'; ?>
                    </button>
                </div>
                <?php endif; ?>
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
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Fonction pour mettre à jour le statut
        function updateStatus(animeId, type, button) {
            if (!<?php echo isset($_SESSION['id']) ? 'true' : 'false' ?>) {
                alert('Veuillez vous connecter pour utiliser cette fonctionnalité');
                return;
            }

            const formData = new FormData();
            formData.append('anime_id', animeId);
            formData.append('type', type);

            fetch('../php/update_status.php', {
                method: 'POST',
                body: formData,
                credentials: 'include'
            })
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    console.error('Error:', data.error);
                    alert(data.error);
                } else {
                    // Mise à jour réussie, mettre à jour l'interface
                    const iconElement = button.querySelector('i');
                    
                    if (type === 'watched') {
                        if (data.status) {
                            iconElement.classList.remove('fa-eye-slash');
                            iconElement.classList.add('fa-eye');
                            button.classList.add('active');
                            button.innerHTML = '<i class="fas fa-eye"></i> Vu';
                        } else {
                            iconElement.classList.remove('fa-eye');
                            iconElement.classList.add('fa-eye-slash');
                            button.classList.remove('active');
                            button.innerHTML = '<i class="fas fa-eye-slash"></i> Non vu';
                        }
                    } else if (type === 'favorite') {
                        if (data.status) {
                            iconElement.classList.remove('far');
                            iconElement.classList.add('fas');
                            button.classList.add('active');
                            button.innerHTML = '<i class="fas fa-star"></i> Favori';
                        } else {
                            iconElement.classList.remove('fas');
                            iconElement.classList.add('far');
                            button.classList.remove('active');
                            button.innerHTML = '<i class="far fa-star"></i> Ajouter aux favoris';
                        }
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Une erreur est survenue');
            });
        }

        // Gérer les clics sur les boutons de statut
        const watchedBtn = document.getElementById('watched-btn');
        const favoriteBtn = document.getElementById('favorite-btn');
        
        if (watchedBtn) {
            watchedBtn.addEventListener('click', function() {
                const animeId = this.getAttribute('data-id');
                updateStatus(animeId, 'watched', this);
            });
        }
        
        if (favoriteBtn) {
            favoriteBtn.addEventListener('click', function() {
                const animeId = this.getAttribute('data-id');
                updateStatus(animeId, 'favorite', this);
            });
        }
    });
    </script>
</body>
</html>
