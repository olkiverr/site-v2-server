<?php
require_once __DIR__ . '/../php/db.php';
require_once __DIR__ . '/../php/forum/forum_functions.php';

// Function to display nested replies recursively
function displayReplies($replies, $topic_id, $is_logged_in, $user_id) {
    foreach ($replies as $reply): ?>
        <div class="reply" id="comment-<?php echo $reply['id']; ?>">
            <?php if ($is_logged_in && isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
                <div class="admin-controls">
                    <button onclick="showEditCommentForm(<?php echo $reply['id']; ?>)" class="admin-btn edit-btn">Edit</button>
                    <form method="post" style="display: inline;">
                        <input type="hidden" name="delete_comment" value="1">
                        <input type="hidden" name="comment_id" value="<?php echo $reply['id']; ?>">
                        <button type="submit" class="admin-btn delete-btn" onclick="return confirm('Are you sure you want to delete this reply?')">Delete</button>
                    </form>
                </div>
                
                <div id="edit-comment-form-<?php echo $reply['id']; ?>" class="edit-comment-form" style="display: none;">
                    <form method="post">
                        <input type="hidden" name="edit_comment" value="1">
                        <input type="hidden" name="comment_id" value="<?php echo $reply['id']; ?>">
                        <textarea name="comment_content" required><?php echo htmlspecialchars($reply['content']); ?></textarea>
                        <button type="submit">Save</button>
                        <button type="button" onclick="hideEditCommentForm(<?php echo $reply['id']; ?>)">Cancel</button>
                    </form>
                </div>
            <?php endif; ?>
            <div class="comment-meta">
                <span class="comment-author"><?php echo htmlspecialchars($reply['username'] ?? 'Anonymous'); ?></span>
                <span><?php echo formatTimestamp($reply['created_at']); ?></span>
            </div>
            <div class="comment-content">
                <?php echo nl2br(htmlspecialchars($reply['content'])); ?>
            </div>
            <div class="vote-container">
                <?php if ($is_logged_in): 
                    $user_reply_vote = getUserVote($user_id, $reply['id'], 'comment');
                ?>
                    <a href="?id=<?php echo $topic_id; ?>&comment_id=<?php echo $reply['id']; ?>&vote=up" class="vote-btn <?php echo ($user_reply_vote === 1) ? 'voted-up' : ''; ?>">&#9650;</a>
                    <span class="vote-score"><?php echo $reply['vote_score']; ?></span>
                <?php else: ?>
                    <span class="vote-score"><?php echo $reply['vote_score']; ?> votes</span>
                <?php endif; ?>
                
                <?php if ($is_logged_in): ?>
                    <button class="reply-btn" onclick="showReplyForm(<?php echo $reply['id']; ?>)">Reply</button>
                <?php endif; ?>
            </div>

            <!-- Reply form for nested replies (hidden by default) -->
            <div id="reply-form-<?php echo $reply['id']; ?>" class="reply-form nested" style="display: none;">
                <form action="?id=<?php echo $topic_id; ?>" method="post">
                    <input type="hidden" name="parent_id" value="<?php echo $reply['id']; ?>">
                    <textarea name="comment_content" required placeholder="Write your reply here..."></textarea>
                    <div class="form-buttons">
                        <button type="submit">Submit Reply</button>
                        <button type="button" onclick="hideReplyForm(<?php echo $reply['id']; ?>)">Cancel</button>
                    </div>
                </form>
            </div>

            <!-- Nested replies -->
            <?php if (!empty($reply['replies'])): ?>
                <div class="replies-container nested">
                    <?php displayReplies($reply['replies'], $topic_id, $is_logged_in, $user_id); ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endforeach;
}

// Activer l'affichage des erreurs pour le débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Remplacer session_start() par l'inclusion de la configuration
include '../php/session_config.php';

// Get topic ID from URL
$topic_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Redirect if no valid topic ID
if ($topic_id <= 0) {
    header('Location: /4TTJ/Zielinski%20Olivier/Site/site-v2/pages/forum.php');
    exit;
}

// Get topic details
$topic = getTopic($topic_id);

// Redirect if topic doesn't exist
if (!$topic) {
    header('Location: /4TTJ/Zielinski%20Olivier/Site/site-v2/pages/forum.php');
    exit;
}

// Get community details
$community = null;
if (!empty($topic['community_id']) && $topic['community_id'] > 0) {
    $community = getCommunity($topic['community_id']);
}

// S'assurer que community est défini même si null
if (!$community) {
    $community = [
        'id' => $topic['community_id'] ?? 0,
        'name' => $topic['community_name'] ?? 'Unknown Community',
        'slug' => $topic['community_slug'] ?? 'unknown',
        'description' => ''
    ];
}

// Check if user is logged in
$is_logged_in = isset($_SESSION['user']) && isset($_SESSION['user_id']);
$user_id = $is_logged_in ? $_SESSION['user_id'] : 0;

// Process comment submission
$comment_error = '';
$comment_success = false;

// Traitement des actions d'administration
if ($is_logged_in && isset($_SESSION['is_admin']) && $_SESSION['is_admin']) {
    // Suppression de commentaire
    if (isset($_POST['delete_comment'])) {
        $comment_id = intval($_POST['comment_id']);
        if (deleteComment($comment_id, $user_id, true)) {
            header('Location: ../pages/forum_topic.php?id=' . $topic_id . '&action=comment_deleted');
            exit;
        }
    }
    
    // Suppression de topic
    if (isset($_POST['delete_topic'])) {
        if (deleteTopic($topic_id, $user_id, true)) {
            header('Location: ../pages/forum.php?action=topic_deleted');
            exit;
        }
    }
    
    // Modification de commentaire
    if (isset($_POST['edit_comment'])) {
        $comment_id = intval($_POST['comment_id']);
        $content = trim($_POST['comment_content']);
        if (updateComment($comment_id, $content, $user_id, true)) {
            header('Location: ../pages/forum_topic.php?id=' . $topic_id . '&action=comment_updated');
            exit;
        }
    }
    
    // Modification de topic
    if (isset($_POST['edit_topic'])) {
        $title = trim($_POST['topic_title']);
        $content = trim($_POST['topic_content']);
        if (updateTopic($topic_id, $title, $content, $user_id, true)) {
            header('Location: ../pages/forum_topic.php?id=' . $topic_id . '&action=topic_updated');
            exit;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $is_logged_in) {
    if (isset($_POST['comment_content'])) {
        $comment_content = trim($_POST['comment_content']);
        $parent_id = isset($_POST['parent_id']) ? intval($_POST['parent_id']) : null;
        
        if (empty($comment_content)) {
            $comment_error = 'Comment cannot be empty';
        } else {
            // Add comment
            $comment_id = addComment($topic_id, $user_id, $comment_content, $parent_id);
            
            if ($comment_id) {
                $comment_success = true;
                // Redirect to avoid resubmission on refresh
                header('Location: ../pages/forum_topic.php?id=' . $topic_id . '&commented=1');
                exit;
            } else {
                $comment_error = 'Failed to add comment. Please try again.';
            }
        }
    }
}

// Process voting
if (isset($_GET['vote']) && $is_logged_in) {
    $vote_type = ($_GET['vote'] === 'up') ? 1 : -1;
    $reference_type = isset($_GET['comment_id']) ? 'comment' : 'topic';
    $reference_id = isset($_GET['comment_id']) ? intval($_GET['comment_id']) : $topic_id;
    
    // Désactiver les votes sur les sujets
    if ($reference_type === 'topic') {
        header('Location: /4TTJ/Zielinski%20Olivier/Site/site-v2/pages/forum_topic.php?id=' . $topic_id);
        exit;
    }
    
    $new_score = voteOnItem($user_id, $reference_id, $reference_type, $vote_type);
    
    // Ajout de paramètre à l'URL pour mise à jour du score
    $redirect_url = '/4TTJ/Zielinski%20Olivier/Site/site-v2/pages/forum_topic.php?id=' . $topic_id;
    $sort_param = isset($_GET['sort']) ? '&sort=' . $_GET['sort'] : '';
    $redirect_url .= $sort_param;
    if ($reference_type === 'topic') {
        $redirect_url .= '&topic_voted=1&topic_score=' . $new_score;
    } else {
        $redirect_url .= '&comment_voted=1&comment_id=' . $reference_id . '&comment_score=' . $new_score;
    }
    
    // Redirect to avoid duplicate votes on refresh
    header('Location: ' . $redirect_url);
    exit;
}

// Pagination for comments
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$per_page = 20;

// Comment sorting
$sort_by = isset($_GET['sort']) && in_array($_GET['sort'], ['votes', 'date']) ? $_GET['sort'] : 'date';

// Get total comments for pagination
$total_comments = getCommentCount($topic_id);
$total_pages = ceil($total_comments / $per_page);

// Get comments for this topic
$comments = getComments($topic_id, $page, $per_page, $sort_by);

// Debug information
error_log("Debug - Topic ID: " . $topic_id);
error_log("Debug - Total Comments: " . $total_comments);
error_log("Debug - Comments Array Count: " . count($comments));
error_log("Debug - Sort By: " . $sort_by);
error_log("Debug - Page: " . $page);

// Page title
$page_title = htmlspecialchars($topic['title']) . " - Forum - MangaMuse";
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
    <link rel="icon" href="../img/MangaMuse_White-Book.png" type="image/x-icon">
    <style>
        .topic-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
            color: #f0f0f0;
        }
        
        .breadcrumb {
            margin-bottom: 20px;
            padding: 10px 0;
            border-bottom: 1px solid #444;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .breadcrumb a {
            color: #5e72e4;
            text-decoration: none;
        }
        
        .breadcrumb a:hover {
            text-decoration: underline;
        }
        
        .breadcrumb-path {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .breadcrumb-separator {
            color: #aaa;
            margin: 0 5px;
        }
        
        .topic {
            background-color: #333;
            border-radius: 8px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
            border: 1px solid #444;
        }
        
        .topic-title {
            font-size: 26px;
            margin-bottom: 15px;
            color: #fff;
            line-height: 1.3;
        }
        
        .topic-header-container {
            display: flex;
            flex-direction: row;
            justify-content: flex-start;
            align-items: flex-start;
            margin-bottom: 20px;
            gap: 25px;
        }
        
        .topic-title-container {
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        
        .topic-meta {
            color: #aaa;
            font-size: 14px;
            margin-bottom: 0;
            padding-bottom: 15px;
            border-bottom: 1px solid #444;
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .topic-image-container {
            width: 150px;
            height: 150px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #2a2a2a;
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid #444;
            order: -1;
            align-self: flex-start;
            position: relative;
            cursor: pointer;
        }
        
        .topic-image-container::after {
            content: "🔍";
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: white;
            font-size: 24px;
            background-color: rgba(0, 0, 0, 0.6);
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .topic-image-container:hover::after {
            opacity: 1;
        }
        
        .topic-image {
            max-width: 100%;
            max-height: 100%;
            object-fit: cover;
            width: 100%;
            height: 100%;
        }
        
        .topic-content {
            margin-bottom: 20px;
            line-height: 1.7;
            font-size: 16px;
            background-color: #2d2d2d;
            border-radius: 8px;
            padding: 25px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.2);
        }
        
        .vote-container {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 15px 0;
            padding-top: 10px;
        }
        
        .vote-btn {
            background: none;
            border: none;
            cursor: pointer;
            font-size: 20px;
            color: #aaa;
            padding: 5px;
            transition: color 0.2s;
        }
        
        .vote-btn:hover {
            color: #5e72e4;
        }
        
        .vote-btn.voted-up {
            color: #4CAF50;
        }
        
        .vote-btn.voted-down {
            color: #F44336;
        }
        
        .vote-score {
            margin: 0 10px;
            font-weight: bold;
            color: #fff;
            font-size: 16px;
        }
        
        .login-to-vote {
            font-size: 14px;
            color: #aaa;
        }
        
        .comments-section {
            margin-top: 40px;
            width: 100%;
        }
        
        .comments-header {
            font-size: 22px;
            margin-bottom: 0;
            color: #fff;
        }
        
        .comments-header-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid #444;
        }
        
        .sort-options {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 14px;
        }
        
        .sort-link {
            padding: 6px 12px;
            text-decoration: none;
            color: #ddd;
            border-radius: 4px;
            background-color: #444;
            transition: all 0.2s ease;
        }
        
        .sort-link:hover {
            background-color: #555;
        }
        
        .sort-link.active {
            background-color: #5e72e4;
            color: white;
        }
        
        .comment {
            background-color: #2d2d2d;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.2);
            border: 1px solid #444;
            width: 100%;
            box-sizing: border-box;
        }
        
        .comment-meta {
            color: #aaa;
            font-size: 14px;
            margin-bottom: 12px;
            padding-bottom: 12px;
            border-bottom: 1px solid #444;
            display: flex;
            gap: 15px;
        }
        
        .comment-author {
            font-weight: bold;
            color: #ddd;
        }
        
        .comment-content {
            margin-bottom: 15px;
            line-height: 1.6;
            width: 100%;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }
        
        .votes {
            display: flex;
            align-items: center;
            padding-top: 10px;
        }
        
        .comment-form {
            background-color: #333;
            padding: 25px;
            border-radius: 8px;
            margin-top: 30px;
            margin-bottom: 30px;
            border: 1px solid #444;
            width: 100%;
            box-sizing: border-box;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }
        
        .comment-form h3 {
            margin-top: 0;
            margin-bottom: 20px;
            color: #fff;
            font-size: 20px;
        }
        
        .comment-form textarea {
            width: 100%;
            padding: 15px;
            border-radius: 6px;
            background-color: #222;
            color: #fff;
            border: 1px solid #444;
            resize: vertical;
            margin-bottom: 20px;
            box-sizing: border-box;
            min-height: 120px;
            font-family: inherit;
            font-size: 15px;
        }
        
        .comment-form button {
            padding: 10px 25px;
            background-color: #5e72e4;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
            transition: all 0.2s ease;
        }
        
        .comment-form button:hover {
            background-color: #4a5fd1;
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
            transition: all 0.2s ease;
        }
        
        .pagination a:hover {
            background-color: #5e72e4;
            transform: translateY(-2px);
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
        
        .login-notice {
            background-color: #333;
            padding: 20px;
            border-radius: 8px;
            margin: 30px 0;
            text-align: center;
            border: 1px solid #444;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }
        
        .login-notice a {
            color: #5e72e4;
            text-decoration: none;
            font-weight: 500;
        }
        
        .login-notice a:hover {
            text-decoration: underline;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
        }
        
        .alert-success {
            background-color: rgba(76, 175, 80, 0.2);
            border: 1px solid #4CAF50;
            color: #4CAF50;
        }
        
        .alert-danger {
            background-color: rgba(244, 67, 54, 0.2);
            border: 1px solid #F44336;
            color: #F44336;
        }
        
        .reply-btn {
            background: none;
            border: none;
            color: #5e72e4;
            cursor: pointer;
            font-size: 14px;
            padding: 5px 10px;
            margin-left: 15px;
            transition: all 0.2s ease;
        }
        
        .reply-btn:hover {
            text-decoration: underline;
            transform: translateY(-1px);
        }
        
        .reply-form {
            margin-top: 15px;
            padding: 15px;
            background-color: #2a2a2a;
            border-radius: 6px;
            border: 1px solid #444;
            width: 100%;
            box-sizing: border-box;
        }
        
        .reply-form textarea {
            width: 100%;
            padding: 12px;
            border-radius: 4px;
            background-color: #222;
            color: #fff;
            border: 1px solid #444;
            resize: vertical;
            margin-bottom: 15px;
            box-sizing: border-box;
            min-height: 100px;
            font-family: inherit;
            font-size: 14px;
        }
        
        .form-buttons {
            display: flex;
            gap: 10px;
        }
        
        .form-buttons button {
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.2s ease;
        }
        
        .form-buttons button[type="submit"] {
            background-color: #5e72e4;
            color: white;
            border: none;
        }
        
        .form-buttons button[type="submit"]:hover {
            background-color: #4a5fd1;
            transform: translateY(-2px);
        }
        
        .form-buttons button[type="button"] {
            background-color: #444;
            color: #ddd;
            border: none;
        }
        
        .form-buttons button[type="button"]:hover {
            background-color: #555;
        }
        
        .replies-container {
            margin-top: 15px;
            border-left: 2px solid #5e72e4;
            padding-left: 20px;
            width: calc(100% - 20px);
            box-sizing: border-box;
        }
        
        .reply {
            background-color: #2a2a2a;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.2);
            border: 1px solid #444;
            width: 100%;
            box-sizing: border-box;
        }
        
        /* Limiter la profondeur des commentaires imbriqués pour éviter le rétrécissement */
        .replies-container .replies-container .replies-container {
            margin-left: 0;
            width: 100%;
            border-left-color: #4a5fd1;
        }
        
        /* Style pour les réponses profondément imbriquées */
        .replies-container .replies-container .replies-container .reply {
            border-left: 3px solid #5e72e4;
        }
        
        .admin-controls {
            float: right;
            margin-bottom: 10px;
            display: flex;
            gap: 8px;
        }
        
        .admin-btn {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 13px;
            transition: all 0.2s ease;
        }
        
        .admin-btn:hover {
            transform: translateY(-2px);
        }
        
        .edit-btn {
            background-color: #4a90e2;
            color: white;
        }
        
        .edit-btn:hover {
            background-color: #3a80d2;
        }
        
        .delete-btn {
            background-color: #e74c3c;
            color: white;
        }
        
        .delete-btn:hover {
            background-color: #d73c2c;
        }
        
        .edit-comment-form, #edit-topic-form {
            margin: 15px 0;
            padding: 15px;
            background-color: #2a2a2a;
            border-radius: 6px;
            width: 100%;
            box-sizing: border-box;
            border: 1px solid #444;
        }
        
        .edit-comment-form textarea, #edit-topic-form textarea {
            width: 100%;
            min-height: 120px;
            margin-bottom: 15px;
            padding: 12px;
            background-color: #222;
            color: white;
            border: 1px solid #444;
            border-radius: 4px;
            box-sizing: border-box;
            font-family: inherit;
            font-size: 14px;
        }
        
        #edit-topic-form input[type="text"] {
            width: 100%;
            padding: 12px;
            margin-bottom: 15px;
            background-color: #222;
            color: white;
            border: 1px solid #444;
            border-radius: 4px;
            box-sizing: border-box;
            font-family: inherit;
            font-size: 16px;
        }
        
        .image-upload-area {
            border: 2px dashed #444;
            border-radius: 6px;
            padding: 25px;
            text-align: center;
            transition: all 0.3s ease;
            background-color: #2a2a2a;
            color: #aaa;
            margin-bottom: 15px;
        }
        
        .image-upload-area.active {
            border-color: #5e72e4;
            background-color: rgba(94, 114, 228, 0.1);
        }
        
        .btn-upload {
            background-color: #5e72e4;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
            font-size: 14px;
        }
        
        .btn-upload:hover {
            background-color: #4a5fd1;
            transform: translateY(-2px);
        }
        
        .image-preview {
            margin-top: 15px;
            text-align: center;
        }
        
        .image-preview img {
            max-width: 100%;
            max-height: 200px;
            border-radius: 6px;
            border: 1px solid #444;
        }
        
        .preview-controls {
            margin-top: 10px;
        }
        
        .btn-remove {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 13px;
            transition: all 0.2s ease;
        }
        
        .btn-remove:hover {
            background-color: #c82333;
            transform: translateY(-2px);
        }
        
        .image-edit-container {
            margin-bottom: 20px;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .topic-header-container {
                flex-direction: column;
                align-items: center;
            }
            
            .topic-image-container {
                width: 100%;
                height: auto;
                max-height: 300px;
                margin-bottom: 20px;
                order: -1;
            }
            
            .topic-title-container {
                width: 100%;
                text-align: center;
            }
            
            .comment-meta {
                flex-direction: column;
                gap: 5px;
            }
            
            .admin-controls {
                float: none;
                margin-bottom: 15px;
                justify-content: flex-end;
            }
            
            .comments-header-container {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .sort-options {
                margin-top: 10px;
            }
        }

        /* Modal/Lightbox pour l'image agrandie */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.9);
            z-index: 1000;
            overflow: auto;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.4s ease;
        }
        
        .modal.show {
            opacity: 1;
            display: flex;
        }
        
        .modal-content {
            display: block;
            max-width: 90%;
            max-height: 90%;
            margin: auto;
            object-fit: contain;
            transform: scale(0.8);
            opacity: 0;
            transition: all 0.4s ease;
        }
        
        .modal.show .modal-content {
            transform: scale(1);
            opacity: 1;
        }
        
        .close {
            position: absolute;
            top: 20px;
            right: 30px;
            color: #f1f1f1;
            font-size: 40px;
            font-weight: bold;
            transition: 0.3s;
            cursor: pointer;
            opacity: 0;
            transform: rotate(-90deg);
            transition: all 0.3s ease;
        }
        
        .modal.show .close {
            opacity: 1;
            transform: rotate(0);
        }
        
        .close:hover,
        .close:focus {
            color: #bbb;
            text-decoration: none;
            transform: rotate(90deg);
        }
    </style>
</head>
<body>
    <?php include_once '../partials/header.php'; ?>
    
    <div class="container">
        <div class="topic-container">
            <div class="breadcrumb">
                <div class="breadcrumb-path">
                    <a href="../pages/forum.php">Forum</a>
                    <span class="breadcrumb-separator">&raquo;</span>
                    <a href="../pages/m.php?slug=<?php echo htmlspecialchars($community['slug']); ?>">m/<?php echo htmlspecialchars($community['slug']); ?></a>
                    <span class="breadcrumb-separator">&raquo;</span>
                    <span><?php echo htmlspecialchars($topic['title']); ?></span>
                </div>
                <?php if (isset($_SESSION['user'])): ?>
                    <a href="../pages/debug_comments.php?topic_id=<?php echo $topic_id; ?>" style="float: right; font-size: 12px; color: #888;">Diagnostic</a>
                <?php endif; ?>
            </div>
            
            <div class="topic">
                <?php if ($is_logged_in && isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
                    <div class="admin-controls">
                        <button onclick="showEditTopicForm()" class="admin-btn edit-btn">Edit</button>
                        <form method="post" style="display: inline;">
                            <input type="hidden" name="delete_topic" value="1">
                            <button type="submit" class="admin-btn delete-btn" onclick="return confirm('Are you sure you want to delete this topic?')">Delete</button>
                        </form>
                    </div>
                    
                    <div id="edit-topic-form" style="display: none;">
                        <form method="post" enctype="multipart/form-data">
                            <input type="hidden" name="edit_topic" value="1">
                            <input type="text" name="topic_title" value="<?php echo htmlspecialchars($topic['title']); ?>" required>
                            
                            <div class="image-edit-container">
                                <label>Topic Image</label>
                                <div class="image-upload-area" id="topic-drop-area">
                                    <p>Drag & drop an image here or</p>
                                    <input type="file" id="topic-file-input" name="topic_image" accept="image/*" style="display:none">
                                    <button type="button" class="btn-upload" id="topic-upload-btn">Select an image</button>
                                </div>
                                <div class="image-preview" id="topic-image-preview">
                                    <?php if (!empty($topic['image_url'])): ?>
                                        <img src="<?php echo htmlspecialchars($topic['image_url']); ?>" alt="Current image">
                                        <div class="preview-controls">
                                            <button type="button" class="btn-remove" id="topic-remove-btn">Remove</button>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <input type="hidden" name="topic_image_data" id="topic-image-data">
                                <input type="hidden" name="remove_current_image" id="remove-current-image" value="0">
                            </div>
                            
                            <textarea name="topic_content" required><?php echo htmlspecialchars($topic['content']); ?></textarea>
                            <button type="submit">Save</button>
                            <button type="button" onclick="hideEditTopicForm()">Cancel</button>
                        </form>
                    </div>
                <?php endif; ?>
                
                <div class="topic-header-container">
                    <div class="topic-title-container">
                        <h1 class="topic-title"><?php echo htmlspecialchars($topic['title']); ?></h1>
                        <div class="topic-meta">
                            <span>By: <?php echo htmlspecialchars($topic['username'] ?? 'Anonymous'); ?></span>
                            <span>Posted: <?php echo formatTimestamp($topic['created_at']); ?></span>
                            <span>Views: <?php echo $topic['views']; ?></span>
                        </div>
                    </div>
                    <?php if (!empty($topic['image_url'])): ?>
                    <div class="topic-image-container" id="topic-image-container">
                        <img src="<?php echo htmlspecialchars($topic['image_url']); ?>" alt="Topic image" class="topic-image" id="topic-image">
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="topic-content">
                    <?php 
                    // Ne pas utiliser htmlspecialchars pour le contenu car il contient du HTML formaté
                    echo $topic['content']; 
                    ?>
                </div>
            </div>

            <?php if ($is_logged_in): ?>
                    <div class="comment-form">
                        <h3>Add a comment</h3>
                        
                        <?php if (!empty($comment_error)): ?>
                            <div class="alert alert-danger"><?php echo $comment_error; ?></div>
                        <?php endif; ?>
                        
                        <?php if (isset($_GET['commented']) && $_GET['commented'] == 1): ?>
                            <div class="alert alert-success">Your comment has been successfully added!</div>
                        <?php endif; ?>
                        
                        <form action="?id=<?php echo $topic_id; ?>" method="post">
                            <textarea name="comment_content" id="comment_content" required placeholder="Write your comment here..."></textarea>
                            <button type="submit">Submit</button>
                        </form>
                    </div>
                <?php else: ?>
                    <div class="login-notice">
                        <p>Want to join the discussion? <a href="../pages/login.php">Log in</a> or <a href="../pages/register.php">register</a> to post comments.</p>
                    </div>
                <?php endif; ?>
            
            <div class="comments-section">
                <div class="comments-header-container">
                    <h2 class="comments-header">Comments (<?php echo $total_comments; ?>)</h2>
                    <div class="sort-options">
                        <span>Sort by: </span>
                        <a href="?id=<?php echo $topic_id; ?>&sort=date" class="sort-link <?php echo $sort_by == 'date' ? 'active' : ''; ?>">Date</a>
                        <a href="?id=<?php echo $topic_id; ?>&sort=votes" class="sort-link <?php echo $sort_by == 'votes' ? 'active' : ''; ?>">Best</a>
                    </div>
                </div>
                <?php if ($total_comments > 0): ?>
                    <?php if (!empty($comments)): ?>
                        <?php foreach ($comments as $comment): ?>
                            <div class="comment" id="comment-<?php echo $comment['id']; ?>">
                                <?php if ($is_logged_in && isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
                                    <div class="admin-controls">
                                        <button onclick="showEditCommentForm(<?php echo $comment['id']; ?>)" class="admin-btn edit-btn">Edit</button>
                                        <form method="post" style="display: inline;">
                                            <input type="hidden" name="delete_comment" value="1">
                                            <input type="hidden" name="comment_id" value="<?php echo $comment['id']; ?>">
                                            <button type="submit" class="admin-btn delete-btn" onclick="return confirm('Are you sure you want to delete this comment?')">Delete</button>
                                        </form>
                                    </div>
                                    
                                    <div id="edit-comment-form-<?php echo $comment['id']; ?>" class="edit-comment-form" style="display: none;">
                                        <form method="post">
                                            <input type="hidden" name="edit_comment" value="1">
                                            <input type="hidden" name="comment_id" value="<?php echo $comment['id']; ?>">
                                            <textarea name="comment_content" required><?php echo htmlspecialchars($comment['content']); ?></textarea>
                                            <button type="submit">Save</button>
                                            <button type="button" onclick="hideEditCommentForm(<?php echo $comment['id']; ?>)">Cancel</button>
                                        </form>
                                    </div>
                                <?php endif; ?>
                                <div class="comment-meta">
                                    <span class="comment-author"><?php echo htmlspecialchars($comment['username'] ?? 'Anonymous'); ?></span>
                                    <span><?php echo formatTimestamp($comment['created_at']); ?></span>
                                </div>
                                <div class="comment-content">
                                    <?php echo nl2br(htmlspecialchars($comment['content'])); ?>
                                </div>
                                <div class="vote-container">
                                    <?php if ($is_logged_in): 
                                        $user_comment_vote = getUserVote($user_id, $comment['id'], 'comment');
                                    ?>
                                        <a href="?id=<?php echo $topic_id; ?>&comment_id=<?php echo $comment['id']; ?>&vote=up" class="vote-btn <?php echo ($user_comment_vote === 1) ? 'voted-up' : ''; ?>">&#9650;</a>
                                        <span class="vote-score"><?php echo $comment['vote_score']; ?></span>
                                    <?php else: ?>
                                        <span class="vote-score"><?php echo $comment['vote_score']; ?> votes</span>
                                    <?php endif; ?>
                                    
                                    <?php if ($is_logged_in): ?>
                                        <button class="reply-btn" onclick="showReplyForm(<?php echo $comment['id']; ?>)">Reply</button>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Reply form (hidden by default) -->
                                <div id="reply-form-<?php echo $comment['id']; ?>" class="reply-form" style="display: none;">
                                    <form action="?id=<?php echo $topic_id; ?>" method="post">
                                        <input type="hidden" name="parent_id" value="<?php echo $comment['id']; ?>">
                                        <textarea name="comment_content" required placeholder="Write your reply here..."></textarea>
                                        <div class="form-buttons">
                                            <button type="submit">Submit Reply</button>
                                            <button type="button" onclick="hideReplyForm(<?php echo $comment['id']; ?>)">Cancel</button>
                                        </div>
                                    </form>
                                </div>
                                
                                <!-- Replies to this comment -->
                                <?php if (!empty($comment['replies'])): ?>
                                    <div class="replies-container">
                                        <?php displayReplies($comment['replies'], $topic_id, $is_logged_in, $user_id); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>Error loading comments. Please try again later.</p>
                        <?php error_log("Comments array is empty but total_comments is " . $total_comments); ?>
                    <?php endif; ?>
                <?php else: ?>
                    <p>No comments yet. Be the first to comment!</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <?php include_once '../partials/footer.php'; ?>
    
    <!-- Modal pour afficher l'image en grand -->
    <div id="imageModal" class="modal">
        <span class="close">&times;</span>
        <img class="modal-content" id="modalImage">
    </div>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Fonction pour gérer l'envoi avec Entrée
        function handleEnterKeyPress(event, form) {
            // Si Shift + Entrée est pressé, permettre le saut de ligne
            if (event.key === 'Enter' && !event.shiftKey) {
                event.preventDefault();
                form.submit();
            }
        }

        // Ajouter les gestionnaires d'événements à tous les textareas
        document.querySelectorAll('textarea[name="comment_content"]').forEach(textarea => {
            textarea.addEventListener('keydown', function(event) {
                handleEnterKeyPress(event, this.closest('form'));
            });
        });

        // Configuration de la modal pour l'image
        const modal = document.getElementById("imageModal");
        const modalImg = document.getElementById("modalImage");
        const imageContainer = document.getElementById("topic-image-container");
        const img = document.getElementById("topic-image");
        const closeBtn = document.querySelector(".close");

        // Si l'image existe, ajouter l'événement de clic
        if (imageContainer && img) {
            imageContainer.addEventListener("click", function() {
                modal.style.display = "flex";
                modalImg.src = img.src;
                
                // Déclencher le reflow pour que les transitions fonctionnent
                void modal.offsetWidth;
                
                // Ajouter la classe pour déclencher l'animation
                modal.classList.add("show");
            });
        }

        // Fermer la modal lors du clic sur le X
        if (closeBtn) {
            closeBtn.addEventListener("click", closeModal);
        }

        // Fermer également la modal lors du clic à l'extérieur de l'image
        window.addEventListener("click", function(event) {
            if (event.target === modal) {
                closeModal();
            }
        });
        
        // Fonction de fermeture avec animation
        function closeModal() {
            // Retirer la classe pour lancer l'animation de sortie
            modal.classList.remove("show");
            
            // Attendre que l'animation soit terminée avant de cacher la modal
            setTimeout(function() {
                modal.style.display = "none";
            }, 400); // Même durée que la transition CSS
        }

        // Fonctions pour gérer les formulaires de réponse
        window.showReplyForm = function(commentId) {
            const replyForm = document.getElementById('reply-form-' + commentId);
            if (replyForm) {
                // Masquer tous les autres formulaires de réponse
                document.querySelectorAll('.reply-form').forEach(form => {
                    if (form !== replyForm) {
                        form.style.display = 'none';
                    }
                });
                // Afficher le formulaire sélectionné
                replyForm.style.display = 'block';
                // Focus sur le textarea
                const textarea = replyForm.querySelector('textarea');
                if (textarea) {
                    textarea.focus();
                    // Ajouter le gestionnaire d'événements pour le nouveau textarea
                    textarea.addEventListener('keydown', function(event) {
                        handleEnterKeyPress(event, this.closest('form'));
                    });
                }
            }
        };

        window.hideReplyForm = function(commentId) {
            const replyForm = document.getElementById('reply-form-' + commentId);
            if (replyForm) {
                replyForm.style.display = 'none';
            }
        };

        // Highlight the current nav link
        const forumNavLink = document.querySelector('.forum-nav');
        if (forumNavLink) {
            forumNavLink.style.fontWeight = 'bold';
            forumNavLink.style.borderBottom = '2px solid #fff';
        }
        
        // Scroll to comment if commented=1 is in the URL
        if (window.location.search.includes('commented=1')) {
            window.scrollTo({
                top: document.body.scrollHeight,
                behavior: 'smooth'
            });
        }
        
        // Vote handling - update score
        const urlParams = new URLSearchParams(window.location.search);
        
        // Check if a topic vote occurred
        if (urlParams.has('topic_voted') && urlParams.has('topic_score')) {
            const newScore = urlParams.get('topic_score');
            const scoreElement = document.querySelector('.topic .vote-score');
            if (scoreElement) {
                scoreElement.textContent = newScore;
            }
        }
        
        // Check if a comment vote occurred
        if (urlParams.has('comment_voted') && urlParams.has('comment_id') && urlParams.has('comment_score')) {
            const commentId = urlParams.get('comment_id');
            const newScore = urlParams.get('comment_score');
            
            // Find the corresponding comment and update its score
            const commentScoreElement = document.querySelector(`#comment-${commentId} .vote-score`);
            if (commentScoreElement) {
                commentScoreElement.textContent = newScore;
                
                // Scroll to the voted comment
                const commentElement = document.querySelector(`#comment-${commentId}`);
                if (commentElement) {
                    commentElement.scrollIntoView({ behavior: 'smooth' });
                    commentElement.style.transition = 'background-color 0.5s';
                    commentElement.style.backgroundColor = '#3a3a3a';
                    setTimeout(() => {
                        commentElement.style.backgroundColor = '';
                    }, 1500);
                }
            }
        }
        
        // Clean URL after processing parameters
        if (window.history && window.history.replaceState) {
            const cleanUrl = new URL(window.location.href);
            
            // Keep id, page and sort if they exist
            const params = new URLSearchParams();
            if (urlParams.has('id')) params.set('id', urlParams.get('id'));
            if (urlParams.has('page')) params.set('page', urlParams.get('page'));
            if (urlParams.has('sort')) params.set('sort', urlParams.get('sort'));
            
            cleanUrl.search = params.toString();
            window.history.replaceState({}, document.title, cleanUrl);
        }

        function showEditTopicForm() {
            document.getElementById('edit-topic-form').style.display = 'block';
            setupImageUpload('topic');
        }

        function hideEditTopicForm() {
            document.getElementById('edit-topic-form').style.display = 'none';
        }

        function showEditCommentForm(commentId) {
            document.getElementById('edit-comment-form-' + commentId).style.display = 'block';
        }

        function hideEditCommentForm(commentId) {
            document.getElementById('edit-comment-form-' + commentId).style.display = 'none';
        }
        
        function setupImageUpload(prefix) {
            const dropArea = document.getElementById(`${prefix}-drop-area`);
            const fileInput = document.getElementById(`${prefix}-file-input`);
            const uploadBtn = document.getElementById(`${prefix}-upload-btn`);
            const imagePreview = document.getElementById(`${prefix}-image-preview`);
            const imageData = document.getElementById(`${prefix}-image-data`);
            const removeCurrentImageInput = document.getElementById('remove-current-image');
            
            if (!dropArea || !fileInput || !uploadBtn || !imagePreview) return;
            
            // Vérifier si un bouton de suppression existe déjà
            const existingRemoveBtn = document.getElementById(`${prefix}-remove-btn`);
            if (existingRemoveBtn) {
                existingRemoveBtn.addEventListener('click', function() {
                    imagePreview.innerHTML = '';
                    if (removeCurrentImageInput) {
                        removeCurrentImageInput.value = '1';
                    }
                });
            }
            
            // Ouvrir le sélecteur de fichier quand on clique sur le bouton
            uploadBtn.addEventListener('click', function() {
                fileInput.click();
            });
            
            // Gérer le glisser-déposer
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                dropArea.addEventListener(eventName, preventDefaults, false);
            });
            
            function preventDefaults(e) {
                e.preventDefault();
                e.stopPropagation();
            }
            
            ['dragenter', 'dragover'].forEach(eventName => {
                dropArea.addEventListener(eventName, highlight, false);
            });
            
            ['dragleave', 'drop'].forEach(eventName => {
                dropArea.addEventListener(eventName, unhighlight, false);
            });
            
            function highlight() {
                dropArea.classList.add('active');
            }
            
            function unhighlight() {
                dropArea.classList.remove('active');
            }
            
            // Gérer le drop
            dropArea.addEventListener('drop', handleDrop, false);
            
            function handleDrop(e) {
                const dt = e.dataTransfer;
                const files = dt.files;
                handleFiles(files);
            }
            
            // Gérer la sélection via input file
            fileInput.addEventListener('change', function() {
                handleFiles(this.files);
            });
            
            function handleFiles(files) {
                if (files.length > 0) {
                    const file = files[0];
                    
                    // Vérifier que c'est bien une image
                    if (!file.type.match('image.*')) {
                        alert('Veuillez sélectionner une image valide.');
                        return;
                    }
                    
                    // Vérifier la taille (max 5MB)
                    if (file.size > 5 * 1024 * 1024) {
                        alert('L\'image est trop volumineuse. Veuillez choisir une image de moins de 5 Mo.');
                        return;
                    }
                    
                    previewFile(file);
                }
            }
            
            function previewFile(file) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    // Afficher l'aperçu
                    imagePreview.innerHTML = `
                        <img src="${e.target.result}" alt="Image preview">
                        <div class="preview-controls">
                            <button type="button" class="btn-remove" id="${prefix}-remove-btn">Supprimer</button>
                        </div>
                    `;
                    imagePreview.style.display = 'block';
                    
                    // Stocker les données de l'image
                    if (imageData) {
                        imageData.value = e.target.result;
                    }
                    
                    // Si on ajoute une nouvelle image, on ne supprime pas l'ancienne
                    if (removeCurrentImageInput) {
                        removeCurrentImageInput.value = '0';
                    }
                    
                    // Ajouter un gestionnaire pour le bouton de suppression
                    document.getElementById(`${prefix}-remove-btn`).addEventListener('click', function() {
                        imagePreview.innerHTML = '';
                        imagePreview.style.display = 'none';
                        if (imageData) {
                            imageData.value = '';
                        }
                        fileInput.value = '';
                        
                        // Si on supprime l'aperçu, on indique qu'il faut supprimer l'image actuelle
                        if (removeCurrentImageInput) {
                            removeCurrentImageInput.value = '1';
                        }
                    });
                };
                
                reader.readAsDataURL(file);
            }
        }

        window.showEditTopicForm = showEditTopicForm;
        window.hideEditTopicForm = hideEditTopicForm;
        window.showEditCommentForm = showEditCommentForm;
        window.hideEditCommentForm = hideEditCommentForm;
    });
    </script>
</body>
</html>