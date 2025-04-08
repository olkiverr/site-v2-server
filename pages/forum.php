<?php
require_once __DIR__ . '/../php/db.php';
require_once __DIR__ . '/../php/forum/forum_functions.php';

// Start the session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Get all communities
$sql = "SELECT c.*, 
        (SELECT COUNT(*) FROM forum_topics WHERE community_id = c.id) AS topic_count,
        u.username as creator
        FROM forum_communities c
        LEFT JOIN users u ON c.user_id = u.id
        ORDER BY c.name ASC";
$result = $conn->query($sql);

$communities = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $communities[] = $row;
    }
}

// Get trending topics
$trending_topics = getTrendingTopics(5);

// Page title
$page_title = "Anime Forum - MangaMuse";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="../css/header.css">
    <link rel="stylesheet" href="../css/footer.css">
    <link rel="stylesheet" href="../css/forum/forum.css">
    <style>
        /* Styles pour la barre de recherche */
        .search-box {
            position: relative;
            margin: 0 0 20px 0;
            width: 100%;
            box-sizing: border-box;
        }
        
        .search-box input {
            width: 100%;
            padding: 12px 15px 12px 40px;
            border: 2px solid #444;
            border-radius: 8px;
            background-color: #2d2d2d;
            color: #fff;
            font-size: 16px;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            box-sizing: border-box;
        }
        
        /* Style pour le conteneur des communautés */
        #communities-list {
            display: grid;
            gap: 15px;
            margin-top: 20px;
            width: 100%;
            box-sizing: border-box;
        }
        
        /* Style pour chaque communauté */
        .community-item {
            background-color: #333;
            border-radius: 8px;
            padding: 15px;
            border: 1px solid #444;
            transition: all 0.3s ease;
            box-sizing: border-box;
            width: 100%;
            margin: 0;
        }

        .categories-section {
            width: 100%;
            padding: 20px;
            box-sizing: border-box;
        }

        .forum-grid {
            display: grid;
            grid-template-columns: 1fr 300px;
            gap: 20px;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            box-sizing: border-box;
        }
        
        /* Message "Aucun résultat" */
        #no-results {
            display: none;
            background-color: #2d2d2d;
            border: 1px solid #444;
            border-radius: 8px;
            margin-top: 15px;
            padding: 15px;
            text-align: center;
            color: #888;
            font-size: 14px;
            animation: fadeIn 0.3s ease;
            box-sizing: border-box;
            width: 100%;
        }
        
        /* Animation pour les résultats */
        .community-item {
            transition: all 0.3s ease;
            opacity: 1;
            transform: translateY(0);
        }
        
        .community-item.hidden {
            opacity: 0;
            transform: translateY(-10px);
            pointer-events: none;
        }
        
        /* Effet de surbrillance pour les résultats correspondants */
        .community-item.highlight {
            background-color: rgba(94, 114, 228, 0.1);
            border-radius: 8px;
            padding: 15px;
            margin: 0;
        }
        
        /* Animation de fade in */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .community-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            border-color: #5e72e4;
        }
        
        .community-name {
            color: #5e72e4;
            font-size: 18px;
            font-weight: bold;
            text-decoration: none;
            display: block;
            margin-bottom: 8px;
        }
        
        .community-description {
            color: #aaa;
            font-size: 14px;
            margin-bottom: 10px;
            line-height: 1.4;
        }
        
        .community-meta {
            color: #666;
            font-size: 12px;
            display: flex;
            gap: 15px;
        }

        @media (max-width: 768px) {
            .forum-grid {
                grid-template-columns: 1fr;
            }

            .categories-section {
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    <?php include_once '../partials/header.php'; ?>
    
    <div class="forum-container">
        <?php if (isset($_SESSION['user']) && $_SESSION['is_admin'] == 1): ?>
            <div class="admin-actions">
                <a href="../pages/forum_setup.php" class="btn btn-sm btn-primary">Setup Forum Database</a>
            </div>
        <?php endif; ?>
        
        <div class="forum-header">
            <h1 class="forum-heading">MangaMuse Forum</h1>
            <?php if (isset($_SESSION['user'])): ?>
                <div class="forum-actions">
                    <a href="../pages/create_community.php" class="btn btn-outline-primary">Create a Community</a>
                    <a href="../pages/forum_new_topic.php" class="new-topic-btn">New Topic</a>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="forum-grid">
            <div class="categories-section">
                <h2 class="section-title">Communities</h2>
                
                <div class="search-box mb-3">
                    <input type="text" id="community-search" class="form-input" placeholder="Search for a community..." onkeyup="searchCommunities()">
                    <div id="no-results" style="display: none; color: #666; padding: 10px; text-align: center;">
                        No communities found matching your search.
                    </div>
                </div>
                
                <?php if (!empty($communities)): ?>
                    <div id="communities-list">
                        <?php foreach ($communities as $community): ?>
                            <div class="community-item">
                                <div class="community-info">
                                    <a href="../pages/m.php?slug=<?php echo $community['slug']; ?>" class="community-name">
                                        m/<?php echo htmlspecialchars($community['slug']); ?>
                                    </a>
                                    <p class="community-description">
                                        <?php echo htmlspecialchars($community['description'] ?: 'No description'); ?>
                                    </p>
                                    <div class="community-meta">
                                        <span class="community-topic-count"><?php echo $community['topic_count']; ?> topics</span>
                                        <?php if ($community['creator']): ?>
                                            <span class="community-creator">Created by <?php echo htmlspecialchars($community['creator']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p>No communities found. Create one to get started!</p>
                <?php endif; ?>
            </div>
            
            <div class="sidebar-section">
                <h2 class="section-title">Popular Topics</h2>
                
                <?php if (!empty($trending_topics)): ?>
                    <?php foreach ($trending_topics as $topic): ?>
                        <div class="trending-topic">
                            <a href="../pages/forum_topic.php?id=<?php echo $topic['id']; ?>" class="topic-title">
                                <?php echo htmlspecialchars($topic['title']); ?>
                            </a>
                            <div class="topic-meta">
                                <span class="topic-community">m/<?php echo htmlspecialchars($topic['community_slug'] ?? 'unknown'); ?></span>
                                <span><?php echo $topic['comment_count']; ?> comments</span>
                                <span><?php echo $topic['vote_score'] ?? 0; ?> votes</span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No popular topics yet. Be the first to start a discussion!</p>
                <?php endif; ?>
                
                <?php if (!isset($_SESSION['user'])): ?>
                    <div class="login-notice">
                        <p>Want to join the discussion? <a href="../pages/login.php">Log in</a> or <a href="../pages/register.php">register</a> to post topics and comments.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <?php include_once '../partials/footer.php'; ?>
    
    <script>
    function searchCommunities() {
        const input = document.getElementById('community-search');
        const filter = input.value.toLowerCase().trim();
        const communitiesList = document.getElementById('communities-list');
        const communities = communitiesList.getElementsByClassName('community-item');
        const noResults = document.getElementById('no-results');
        
        let hasVisibleCommunities = false;
        
        for (let i = 0; i < communities.length; i++) {
            const communityName = communities[i].querySelector('.community-name').textContent.toLowerCase();
            const communityDescription = communities[i].querySelector('.community-description').textContent.toLowerCase();
            const communityMeta = communities[i].querySelector('.community-meta').textContent.toLowerCase();
            
            if (filter === '' || 
                communityName.includes(filter) || 
                communityDescription.includes(filter) ||
                communityMeta.includes(filter)) {
                communities[i].classList.remove('hidden');
                communities[i].classList.add('highlight');
                hasVisibleCommunities = true;
            } else {
                communities[i].classList.add('hidden');
                communities[i].classList.remove('highlight');
            }
        }
        
        // Afficher ou masquer le message "Aucun résultat"
        noResults.style.display = hasVisibleCommunities ? 'none' : 'block';
    }
    
    document.addEventListener('DOMContentLoaded', function() {
        // Highlight the current nav link
        const forumNavLink = document.querySelector('.forum-nav');
        if (forumNavLink) {
            forumNavLink.style.fontWeight = 'bold';
            forumNavLink.style.borderBottom = '2px solid #fff';
        }

        // Amélioration de la barre de recherche
        const searchInput = document.getElementById('community-search');
        if (searchInput) {
            // Ajouter un délai de recherche pour éviter trop d'appels
            let searchTimeout;
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(searchCommunities, 300);
            });

            // Effacer la recherche avec le bouton Escape
            searchInput.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    this.value = '';
                    searchCommunities();
                }
            });
            
            // Focus sur la barre de recherche avec Ctrl+F
            document.addEventListener('keydown', function(e) {
                if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
                    e.preventDefault();
                    searchInput.focus();
                }
            });
        }
    });
    </script>
</body>
</html> 