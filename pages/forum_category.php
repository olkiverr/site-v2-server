<?php
require_once __DIR__ . '/../php/db.php';
require_once __DIR__ . '/../php/forum/forum_functions.php';

// Remplacer la vérification de session par l'inclusion de la configuration
include_once '../php/session_config.php';

// Get community ID from URL
$community_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Redirect if no valid community ID
if ($community_id <= 0) {
    header('Location: /4TTJ/Zielinski%20Olivier/Site/site-v2/pages/forum.php');
    exit;
}

// Get community slug for the redirect
$community = getCommunity($community_id);

if ($community) {
    // Redirect to the new m.php page with the community slug
    header('Location: /4TTJ/Zielinski%20Olivier/Site/site-v2/pages/m.php?slug=' . urlencode($community['slug']));
    exit;
} else {
    // If community doesn't exist, redirect to the forum page
    header('Location: /4TTJ/Zielinski%20Olivier/Site/site-v2/pages/forum.php');
    exit;
}

// Pagination
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$per_page = 15;

// Get topics for this community
$topics = getTopics($community_id, $page, $per_page);

// Get total topics for pagination
$total_topics = getTopicCount($community_id);
$total_pages = ceil($total_topics / $per_page);

// Page title
$page_title = htmlspecialchars($community['name']) . " - Forum - MangaMuse";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="/4TTJ/Zielinski%20Olivier/Site/site-v2/css/header.css">
    <link rel="stylesheet" href="/4TTJ/Zielinski%20Olivier/Site/site-v2/css/footer.css">
    <link rel="stylesheet" href="/4TTJ/Zielinski%20Olivier/Site/site-v2/css/forum/forum.css">
</head>
<body>
    <?php include_once __DIR__ . '/../partials/header.php'; ?>
    
    <div class="forum-container">
        <div class="breadcrumb">
            <a href="/4TTJ/Zielinski%20Olivier/Site/site-v2/pages/forum.php">Forum</a> &raquo; <?php echo htmlspecialchars($community['name']); ?>
        </div>
        
        <div class="forum-header">
            <div>
                <h1 class="forum-heading"><?php echo htmlspecialchars($community['name']); ?></h1>
                <p class="community-description"><?php echo htmlspecialchars($community['description']); ?></p>
            </div>
            <?php if (isset($_SESSION['user'])): ?>
                <a href="/4TTJ/Zielinski%20Olivier/Site/site-v2/pages/forum_new_topic.php?community_id=<?php echo $community_id; ?>" class="new-topic-btn">New Topic</a>
            <?php endif; ?>
        </div>
        
        <?php if (!empty($topics)): ?>
            <div class="topic-list">
                <div class="topic-header">
                    <div>Topic</div>
                    <div>Author</div>
                    <div>Replies</div>
                    <div>Votes</div>
                </div>
                
                <?php foreach ($topics as $topic): ?>
                    <div class="topic-item">
                        <div>
                            <a href="/4TTJ/Zielinski%20Olivier/Site/site-v2/pages/forum_topic.php?id=<?php echo $topic['id']; ?>" class="topic-title-link">
                                <?php echo htmlspecialchars($topic['title']); ?>
                            </a>
                            <span class="topic-date"><?php echo formatTimestamp($topic['created_at']); ?></span>
                        </div>
                        <div class="topic-author">
                            <?php echo htmlspecialchars($topic['username'] ?? 'Anonymous'); ?>
                        </div>
                        <div class="topic-stats">
                            <?php echo $topic['comment_count']; ?>
                        </div>
                        <div class="topic-votes">
                            <?php echo $topic['vote_score']; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?id=<?php echo $community_id; ?>&page=<?php echo $page - 1; ?>">&laquo; Previous</a>
                    <?php else: ?>
                        <span class="disabled">&laquo; Previous</span>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <?php if ($i == $page): ?>
                            <span class="current"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="?id=<?php echo $community_id; ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="?id=<?php echo $community_id; ?>&page=<?php echo $page + 1; ?>">Next &raquo;</a>
                    <?php else: ?>
                        <span class="disabled">Next &raquo;</span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="no-topics">
                <p>No topics in this community yet. Be the first to start a discussion!</p>
                <?php if (isset($_SESSION['user'])): ?>
                    <a href="/4TTJ/Zielinski%20Olivier/Site/site-v2/pages/forum_new_topic.php?community_id=<?php echo $community_id; ?>" class="btn btn-success">Create New Topic</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <?php include_once __DIR__ . '/../partials/footer.php'; ?>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Highlight the current nav link
        const forumNavLink = document.querySelector('.forum-nav');
        if (forumNavLink) {
            forumNavLink.style.fontWeight = 'bold';
            forumNavLink.style.borderBottom = '2px solid #fff';
        }
    });
    </script>
</body>
</html> 