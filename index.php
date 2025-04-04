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

// Récupérer les images des catégories et tous les animés en excluant les contenus interdits
$sql = "SELECT id, title, img, category, genres FROM pages 
        WHERE genres NOT LIKE '%Ecchi%' 
        AND genres NOT LIKE '%Erotica%' 
        AND genres NOT LIKE '%Hentai%'";
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

if (isset($_SESSION['id'])) {
    $user_id = $_SESSION['id'];
} elseif (isset($_SESSION['is_admin'])) {
    // Pour l'admin, vérifier si id est défini, sinon utiliser une valeur par défaut (généralement 1 pour l'admin)
    $user_id = isset($_SESSION['id']) ? $_SESSION['id'] : 1; 
} else {
    $user_id = null;
}

$user_statuses = [];
if ($user_id) {
    $status_sql = "SELECT anime_id, is_favorite, is_watched FROM user_anime_status WHERE user_id = ?";
    $stmt = $conn->prepare($status_sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $user_statuses[$row['anime_id']] = [
            'is_favorite' => $row['is_favorite'],
            'is_watched' => $row['is_watched']
        ];
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body class="<?php echo $is_admin ? 'admin' : ''; ?>">
    <button id="scrollButton" class="scroll-button">
        <span class="scroll-down">⬇</span>
        <span class="scroll-up">⬆</span>
    </button>
    <?php include 'partials/header.php'; ?>
    <main>
        <?php foreach ($categories as $category): ?>
            <?php if (!empty($category_images[$category])): ?>
                <div class="<?php echo $category; ?>-slider-container">
                    <p><?php echo ucfirst($category); ?> <?php echo $category === 'trending' ? '🔥' : '⌛'; ?></p>
                    <div class="<?php echo $category; ?>-slider">
                        <button class="slider-button left">&#9664;</button>
                        <?php foreach ($category_images[$category] as $image): ?>
                        <div class="<?php echo $category; ?>-item" data-id="<?php echo $image['id']; ?>">
                        <?php if ($user_id): ?>
                        <div class="overlay-icons">
                                <!-- Icône de "watched" -->
                                <span class="icon-views <?php echo isset($user_statuses[$image['id']]['is_watched']) && $user_statuses[$image['id']]['is_watched'] ? 'active' : ''; ?>">
                                    <i class="fas <?php echo isset($user_statuses[$image['id']]['is_watched']) && $user_statuses[$image['id']]['is_watched'] ? 'fa-eye' : 'fa-eye-slash'; ?>"></i>
                                </span>
                                <!-- Icône de "favorite" -->
                                <span class="icon-star <?php echo isset($user_statuses[$image['id']]['is_favorite']) && $user_statuses[$image['id']]['is_favorite'] ? 'active' : ''; ?>">
                                    <i class="<?php echo isset($user_statuses[$image['id']]['is_favorite']) && $user_statuses[$image['id']]['is_favorite'] ? 'fas' : 'far'; ?> fa-star"></i>
                                </span>
                        </div>
                        <?php endif; ?>
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
                        <?php if ($user_id): ?>
                        <div class="overlay-icons">
                                <!-- Icône de "watched" -->
                                <span class="icon-views <?php echo isset($user_statuses[$anime['id']]['is_watched']) && $user_statuses[$anime['id']]['is_watched'] ? 'active' : ''; ?>">
                                    <i class="fas <?php echo isset($user_statuses[$anime['id']]['is_watched']) && $user_statuses[$anime['id']]['is_watched'] ? 'fa-eye' : 'fa-eye-slash'; ?>"></i>
                                </span>
                                <!-- Icône de "favorite" -->
                                <span class="icon-star <?php echo isset($user_statuses[$anime['id']]['is_favorite']) && $user_statuses[$anime['id']]['is_favorite'] ? 'active' : ''; ?>">
                                    <i class="<?php echo isset($user_statuses[$anime['id']]['is_favorite']) && $user_statuses[$anime['id']]['is_favorite'] ? 'fas' : 'far'; ?> fa-star"></i>
                                </span>
                        </div>
                        <?php endif; ?>
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
            // Pour les clics sur les icônes et leur conteneur
            document.querySelectorAll('.overlay-icons').forEach(overlay => {
                overlay.addEventListener('click', function(e) {
                    e.stopPropagation(); // Empêche la propagation du clic vers le parent
                });
            });

            // Pour la navigation vers la page de l'anime
            document.querySelectorAll('.anime-item, .trending-item, .upcoming-item').forEach(item => {
                item.addEventListener('click', function(e) {
                    // Vérifier si le clic n'est pas sur l'overlay ou ses enfants
                    if (!e.target.closest('.overlay-icons')) {
                        const id = this.getAttribute('data-id');
                        window.location.href = "pages/view_anime.php?id=" + id;
                    }
                });
            });
        });
    </script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const scrollButton = document.getElementById('scrollButton');
        let lastScrollPosition = 0;
        const scrollThreshold = 200; // Seuil de défilement en pixels

        // Fonction pour faire défiler jusqu'en bas
        function scrollToBottom() {
            window.scrollTo({
                top: document.documentElement.scrollHeight,
                behavior: 'smooth'
            });
        }

        // Fonction pour faire défiler jusqu'en haut
        function scrollToTop() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        }

        // Gestion du défilement
        window.addEventListener('scroll', function() {
            const currentScroll = window.pageYOffset || document.documentElement.scrollTop;

            // Afficher ou masquer le bouton
            if (currentScroll > scrollThreshold) {
                scrollButton.style.display = 'flex';
                scrollButton.classList.add('show-up');
                scrollButton.classList.remove('show-down');
                scrollButton.onclick = scrollToTop;
            } else if (currentScroll < scrollThreshold) {
                scrollButton.style.display = 'flex';
                scrollButton.classList.add('show-down');
                scrollButton.classList.remove('show-up');
                scrollButton.onclick = scrollToBottom;
            }

            lastScrollPosition = currentScroll;
        });

        // Vérification initiale de la position
        if (window.pageYOffset < scrollThreshold) {
            scrollButton.style.display = 'flex';
            scrollButton.classList.add('show-down');
            scrollButton.onclick = scrollToBottom;
        }
    });
    </script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Fonction pour mettre à jour le statut
        function updateStatus(animeId, type, icon) {
            if (!<?php echo isset($_SESSION['id']) ? 'true' : 'false' ?>) {
                alert('Veuillez vous connecter pour utiliser cette fonctionnalité');
                return;
            }

            const formData = new FormData();
            formData.append('anime_id', animeId);
            formData.append('type', type);

            fetch('php/update_status.php', {
                method: 'POST',
                body: formData,
                credentials: 'include'
            })
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    console.error('Error:', data.error);
                    // Annuler le changement visuel si erreur
                    icon.classList.toggle('active');
                    const iconElement = icon.querySelector('i');
                    if (type === 'watched') {
                        iconElement.classList.toggle('fa-eye');
                        iconElement.classList.toggle('fa-eye-slash');
                    } else {
                        iconElement.classList.toggle('fas');
                        iconElement.classList.toggle('far');
                    }
                    alert(data.error); // Afficher l'erreur à l'utilisateur
                } else {
                    // Mise à jour réussie, mettre à jour l'interface
                    const iconElement = icon.querySelector('i');
                    if (type === 'watched') {
                        if (data.status) {
                            iconElement.classList.remove('fa-eye-slash');
                            iconElement.classList.add('fa-eye');
                            icon.classList.add('active');
                        } else {
                            iconElement.classList.remove('fa-eye');
                            iconElement.classList.add('fa-eye-slash');
                            icon.classList.remove('active');
                        }
                    } else {
                        if (data.status) {
                            iconElement.classList.remove('far');
                            iconElement.classList.add('fas');
                            icon.classList.add('active');
                        } else {
                            iconElement.classList.remove('fas');
                            iconElement.classList.add('far');
                            icon.classList.remove('active');
                        }
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                // Annuler le changement visuel en cas d'erreur
                icon.classList.toggle('active');
                alert('Une erreur est survenue');
            });
        }

        // Gérer les clics sur l'icône "watched"
        document.querySelectorAll('.icon-views').forEach(icon => {
            icon.addEventListener('click', function(e) {
                e.stopPropagation();
                const animeItem = this.closest('[data-id]');
                const animeId = animeItem.getAttribute('data-id');
                
                this.classList.toggle('active');
                const eyeIcon = this.querySelector('i');
                if (this.classList.contains('active')) {
                    eyeIcon.classList.remove('fa-eye-slash');
                    eyeIcon.classList.add('fa-eye');
                } else {
                    eyeIcon.classList.remove('fa-eye');
                    eyeIcon.classList.add('fa-eye-slash');
                }

                updateStatus(animeId, 'watched', this);
            });
        });

        // Gérer les clics sur l'icône "favorite"
        document.querySelectorAll('.icon-star').forEach(icon => {
            icon.addEventListener('click', function(e) {
                e.stopPropagation();
                const animeItem = this.closest('[data-id]');
                const animeId = animeItem.getAttribute('data-id');
                
                this.classList.toggle('active');
                const starIcon = this.querySelector('i');
                if (this.classList.contains('active')) {
                    starIcon.classList.remove('far', 'fa-star');
                    starIcon.classList.add('fas', 'fa-star');
                } else {
                    starIcon.classList.remove('fas', 'fa-star');
                    starIcon.classList.add('far', 'fa-star');
                }

                updateStatus(animeId, 'favorite', this);
            });
        });
    });
    </script>
    <script src="js/scripts.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</body>
</html>