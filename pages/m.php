<?php
require_once __DIR__ . '/../php/db.php';
require_once __DIR__ . '/../php/forum/forum_functions.php';

// Start the session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Get community slug from URL
$community_slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';

if (empty($community_slug)) {
    header('Location: ../pages/forum.php');
    exit;
}

// Get community details
$stmt = $conn->prepare("SELECT * FROM forum_communities WHERE slug = ?");
$stmt->bind_param("s", $community_slug);
$stmt->execute();
$result = $stmt->get_result();
$community = $result->fetch_assoc();
$stmt->close();

// Redirect if community doesn't exist
if (!$community) {
    header('Location: ../pages/forum.php');
    exit;
}

// Pagination
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$per_page = 15;

// Get topics for this community
$sql = "SELECT t.*, u.username, 
        (SELECT COUNT(*) FROM forum_comments WHERE topic_id = t.id) AS comment_count,
        COALESCE((SELECT SUM(vote_type) FROM forum_votes WHERE reference_id = t.id AND reference_type = 'topic'), 0) AS vote_score
        FROM forum_topics t
        LEFT JOIN users u ON t.user_id = u.id
        WHERE t.community_id = ?
        ORDER BY t.created_at DESC
        LIMIT ?, ?";

$offset = ($page - 1) * $per_page;
$stmt = $conn->prepare($sql);
$stmt->bind_param("iii", $community['id'], $offset, $per_page);
$stmt->execute();
$result = $stmt->get_result();

$topics = [];
while ($row = $result->fetch_assoc()) {
    // Check if the topic still exists and has an ID
    if (!empty($row['id'])) {
        $topics[] = $row;
    }
}
$stmt->close();

// Get total topics for pagination
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM forum_topics WHERE community_id = ?");
$stmt->bind_param("i", $community['id']);
$stmt->execute();
$result = $stmt->get_result();
$total_topics = $result->fetch_assoc()['count'];
$stmt->close();

$total_pages = ceil($total_topics / $per_page);

// Page title
$page_title = "m/" . htmlspecialchars($community['slug']) . " - MangaMuse";
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
        .forum-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            box-sizing: border-box;
        }

        .breadcrumb {
            margin-bottom: 20px;
            padding: 10px 0;
            border-bottom: 1px solid #444;
        }

        .breadcrumb a {
            color: #5e72e4;
            text-decoration: none;
        }

        .breadcrumb a:hover {
            text-decoration: underline;
        }

        .community-header {
            background-color: #333;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            border: 1px solid #444;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .community-info h1 {
            color: #fff;
            margin: 0 0 10px 0;
            font-size: 24px;
        }

        .community-description {
            color: #aaa;
            margin: 0;
            font-size: 16px;
        }

        .new-topic-btn {
            background-color: #5e72e4;
            color: white;
            padding: 10px 20px;
            border-radius: 4px;
            text-decoration: none;
            font-weight: bold;
            transition: all 0.3s ease;
        }

        .new-topic-btn:hover {
            background-color: #4a5fd1;
            transform: translateY(-2px);
        }

        .topic-list {
            background-color: #333;
            border-radius: 8px;
            border: 1px solid #444;
            overflow: hidden;
        }

        .topic-header {
            display: grid;
            grid-template-columns: 60px 1fr 100px 100px;
            padding: 15px 20px;
            background-color: #2d2d2d;
            color: #aaa;
            font-weight: bold;
            border-bottom: 1px solid #444;
        }

        .topic-item {
            display: grid;
            grid-template-columns: 60px 1fr 100px 100px;
            padding: 15px 20px;
            border-bottom: 1px solid #444;
            transition: all 0.3s ease;
        }

        .topic-item:last-child {
            border-bottom: none;
        }

        .topic-item:hover {
            background-color: #3a3a3a;
        }

        .vote-info {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .vote-count {
            color: #5e72e4;
            font-weight: bold;
            font-size: 18px;
        }

        .topic-content {
            padding: 0 15px;
        }

        .topic-title {
            color: #fff;
            font-size: 18px;
            font-weight: bold;
            text-decoration: none;
            display: block;
            margin-bottom: 5px;
        }

        .topic-title:hover {
            color: #5e72e4;
        }

        .topic-meta {
            color: #aaa;
            font-size: 14px;
            display: flex;
            gap: 15px;
        }

        .topic-author {
            color: #5e72e4;
        }

        .topic-date {
            color: #888;
        }

        .topic-comments {
            color: #888;
        }

        .no-topics {
            background-color: #333;
            border-radius: 8px;
            padding: 30px;
            text-align: center;
            border: 1px solid #444;
            color: #aaa;
        }

        .no-topics p {
            margin-bottom: 20px;
            font-size: 16px;
        }

        .btn-success {
            background-color: #4CAF50;
            color: white;
            padding: 10px 20px;
            border-radius: 4px;
            text-decoration: none;
            font-weight: bold;
            transition: all 0.3s ease;
        }

        .btn-success:hover {
            background-color: #45a049;
            transform: translateY(-2px);
        }

        .pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 30px;
        }

        .pagination a,
        .pagination span {
            padding: 8px 12px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 14px;
        }

        .pagination a {
            background-color: #444;
            color: #ddd;
        }

        .pagination a:hover {
            background-color: #5e72e4;
        }

        .pagination span.current {
            background-color: #5e72e4;
            color: white;
        }

        .pagination span.disabled {
            background-color: #333;
            color: #777;
            cursor: not-allowed;
        }

        @media (max-width: 768px) {
            .topic-header,
            .topic-item {
                grid-template-columns: 50px 1fr;
            }

            .topic-header div:nth-child(3),
            .topic-header div:nth-child(4),
            .topic-item div:nth-child(3),
            .topic-item div:nth-child(4) {
                display: none;
            }

            .topic-meta {
                flex-direction: column;
                gap: 5px;
            }

            .community-header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }

            .new-topic-btn {
                width: 100%;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <?php include_once '../partials/header.php'; ?>
    
    <div class="forum-container">
        <div class="breadcrumb">
            <a href="../pages/forum.php">Forum</a> &raquo; m/<?php echo htmlspecialchars($community['slug']); ?>
        </div>
        
        <div class="community-header">
            <div class="community-info">
                <h1 class="forum-heading">m/<?php echo htmlspecialchars($community['slug']); ?></h1>
                <?php if (!empty($community['description'])): ?>
                    <p class="community-description"><?php echo htmlspecialchars($community['description']); ?></p>
                <?php endif; ?>
            </div>
            <?php if (isset($_SESSION['user'])): ?>
                <a href="../pages/forum_new_topic.php?community_id=<?php echo $community['id']; ?>" class="new-topic-btn">New Topic</a>
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
                        <div class="vote-info">
                            <span class="vote-count"><?php echo isset($topic['vote_score']) ? $topic['vote_score'] : 0; ?></span>
                        </div>
                        <div class="topic-content">
                            <a href="../pages/forum_topic.php?id=<?php echo $topic['id']; ?>" class="topic-title"><?php echo htmlspecialchars($topic['title']); ?></a>
                            <div class="topic-meta">
                                <span class="topic-author">By <?php echo htmlspecialchars($topic['username'] ?? 'Anonymous'); ?></span>
                                <span class="topic-date">On <?php echo isset($topic['created_at']) ? date('d/m/Y at H:i', strtotime($topic['created_at'])) : 'unknown date'; ?></span>
                                <span class="topic-comments"><?php echo isset($topic['comment_count']) ? $topic['comment_count'] : 0; ?> comments</span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?slug=<?php echo $community_slug; ?>&page=<?php echo $page - 1; ?>">&laquo; Previous</a>
                    <?php else: ?>
                        <span class="disabled">&laquo; Previous</span>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <?php if ($i == $page): ?>
                            <span class="current"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="?slug=<?php echo $community_slug; ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="?slug=<?php echo $community_slug; ?>&page=<?php echo $page + 1; ?>">Next &raquo;</a>
                    <?php else: ?>
                        <span class="disabled">Next &raquo;</span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="no-topics">
                <p>No topics in this community yet. Be the first to start a discussion!</p>
                <?php if (isset($_SESSION['user'])): ?>
                    <a href="../pages/forum_new_topic.php?community_id=<?php echo $community['id']; ?>" class="btn btn-success">Create a New Topic</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <?php include_once '../partials/footer.php'; ?>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Track clicks on topic links
        const topicLinks = document.querySelectorAll('.topic-title-link');
        topicLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                // Don't block the normal link behavior
                console.log('Click on topic link: ' + this.href);
            });
        });
    });
    </script>
</body>
</html> 