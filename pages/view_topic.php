<?php
require_once __DIR__ . '/../php/db.php';
require_once __DIR__ . '/../php/forum/forum_functions.php';

// Activer l'affichage des erreurs
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Remplacer session_start() par l'inclusion de la configuration
include '../php/session_config.php';

// Get topic ID from URL
$topic_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
echo "<!-- Requête d'ID: " . $topic_id . " -->";

// Process comment submission
$comment_error = '';
$comment_success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user']) && isset($_POST['comment_content'])) {
    $comment_content = trim($_POST['comment_content']);
    
    if (empty($comment_content)) {
        $comment_error = 'Le commentaire ne peut pas être vide';
    } else {
        // Add comment
        $comment_id = addComment($topic_id, $_SESSION['user_id'], $comment_content);
        
        if ($comment_id) {
            $comment_success = true;
            // Redirect to avoid resubmission on refresh
            header('Location: /4TTJ/Zielinski%20Olivier/Site/site-v2/pages/view_topic.php?id=' . $topic_id . '&commented=1');
            exit;
        } else {
            $comment_error = 'Échec de l\'ajout du commentaire. Veuillez réessayer.';
        }
    }
}

// Process voting
if (isset($_GET['vote']) && isset($_SESSION['user'])) {
    $vote_type = ($_GET['vote'] === 'up') ? 1 : -1;
    $reference_type = isset($_GET['comment_id']) ? 'comment' : 'topic';
    $reference_id = isset($_GET['comment_id']) ? intval($_GET['comment_id']) : $topic_id;
    
    $new_score = voteOnItem($_SESSION['user_id'], $reference_id, $reference_type, $vote_type);
    
    // Ajout de paramètre à l'URL pour mise à jour du score
    $redirect_url = '/4TTJ/Zielinski%20Olivier/Site/site-v2/pages/view_topic.php?id=' . $topic_id;
    if ($reference_type === 'topic') {
        $redirect_url .= '&topic_voted=1&topic_score=' . $new_score;
    } else {
        $redirect_url .= '&comment_voted=1&comment_id=' . $reference_id . '&comment_score=' . $new_score;
    }
    
    // Redirect to avoid duplicate votes on refresh
    header('Location: ' . $redirect_url);
    exit;
}

// Comment sorting
$sort_by = isset($_GET['sort']) && in_array($_GET['sort'], ['votes', 'date']) ? $_GET['sort'] : 'date';

// Redirect if no valid topic ID
if ($topic_id <= 0) {
    echo "<h1>Erreur</h1><p>ID de sujet invalide</p>";
    exit;
}

// Vérification directe dans la base de données
$topic_check = null;
try {
    $stmt = $conn->prepare("SELECT * FROM forum_topics WHERE id = ?");
    $stmt->bind_param("i", $topic_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $topic_check = $result->fetch_assoc();
        echo "<!-- Sujet trouvé directement dans la base de données -->";
    } else {
        echo "<!-- Aucun sujet trouvé directement dans la base de données -->";
    }
    $stmt->close();
} catch (Exception $e) {
    echo "<!-- Erreur lors de la vérification directe: " . $e->getMessage() . " -->";
}

// Get topic details using the function
$topic = getTopic($topic_id);

// Debug output (hidden in HTML comment)
echo "<!-- Résultat de getTopic: ";
echo $topic ? "Sujet trouvé" : "Sujet NON trouvé";
echo " -->";

// Redirect if topic doesn't exist
if (!$topic) {
    // Affichons les détails de débogage
    echo "<h1>Sujet introuvable</h1>";
    echo "<p>Le sujet avec l'ID $topic_id n'a pas été trouvé.</p>";
    
    if ($topic_check) {
        echo "<p>Cependant, le sujet existe bien dans la base de données:</p>";
        echo "<pre>";
        print_r($topic_check);
        echo "</pre>";
        
        echo "<p>Checking associated community:</p>";
        if (isset($topic_check['community_id'])) {
            $community_check = null;
            $stmt = $conn->prepare("SELECT * FROM forum_communities WHERE id = ?");
            $stmt->bind_param("i", $topic_check['community_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $community_check = $result->fetch_assoc();
                echo "<pre>";
                print_r($community_check);
                echo "</pre>";
            } else {
                echo "<p>No community found with ID " . $topic_check['community_id'] . "</p>";
            }
            $stmt->close();
        } else {
            echo "<p>No community ID associated with this topic.</p>";
        }
    }
    
    echo "<p><a href='/4TTJ/Zielinski%20Olivier/Site/site-v2/pages/forum.php'>Retour au forum</a></p>";
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

// Get comments for this topic
echo "<!-- Récupération des commentaires pour le sujet $topic_id avec le tri: $sort_by -->";
$comments = getComments($topic_id, 1, 50, $sort_by);
echo "<!-- Nombre de commentaires récupérés: " . count($comments) . " -->";

// Vérification des commentaires dans la base de données
try {
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM forum_comments WHERE topic_id = ?");
    $stmt->bind_param("i", $topic_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        echo "<!-- Nombre de commentaires dans la base de données: " . $row['count'] . " -->";
    }
    $stmt->close();
} catch (Exception $e) {
    echo "<!-- Erreur lors de la vérification des commentaires: " . $e->getMessage() . " -->";
}

// Get user's vote on this topic if logged in
$user_topic_vote = 0;
if (isset($_SESSION['user'])) {
    $user_topic_vote = getUserVote($_SESSION['user_id'], $topic_id, 'topic');
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($topic['title']); ?> - Forum MangaMuse</title>
    <link rel="stylesheet" href="/4TTJ/Zielinski%20Olivier/Site/site-v2/css/header.css">
    <link rel="stylesheet" href="/4TTJ/Zielinski%20Olivier/Site/site-v2/css/footer.css">
    <link rel="stylesheet" href="/4TTJ/Zielinski%20Olivier/Site/site-v2/css/forum/forum.css">
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
        }
        
        .breadcrumb a {
            color: #5e72e4;
            text-decoration: none;
        }
        
        .breadcrumb a:hover {
            text-decoration: underline;
        }
        
        .topic {
            background-color: #333;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.3);
            border: 1px solid #444;
        }
        
        .topic-title {
            font-size: 24px;
            margin-bottom: 15px;
            color: #fff;
        }
        
        .topic-meta {
            color: #aaa;
            font-size: 14px;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #444;
        }
        
        .topic-content {
            margin-bottom: 20px;
            line-height: 1.6;
            font-size: 16px;
        }
        
        .comments-section {
            margin-top: 30px;
        }
        
        .comments-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #444;
        }
        
        .comment {
            background-color: #2d2d2d;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.2);
            border: 1px solid #444;
        }
        
        .comment-meta {
            color: #aaa;
            font-size: 14px;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid #444;
        }
        
        .comment-content {
            margin-bottom: 15px;
            line-height: 1.5;
        }
        
        .votes {
            display: flex;
            align-items: center;
            padding-top: 10px;
        }
        
        .vote-btn {
            background: none;
            border: none;
            font-size: 18px;
            cursor: pointer;
            color: #aaa;
            padding: 5px;
        }
        
        .vote-btn:hover {
            color: #5e72e4;
        }
        
        .vote-score {
            margin: 0 10px;
            font-weight: bold;
            color: #fff;
        }
        
        .comment-form {
            background-color: #333;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
            border: 1px solid #444;
        }
        
        .comment-form h3 {
            margin-top: 0;
            margin-bottom: 15px;
            color: #fff;
        }
        
        .comment-form textarea {
            width: 100%;
            padding: 12px;
            border-radius: 4px;
            background-color: #222;
            color: #fff;
            border: 1px solid #444;
            resize: vertical;
            margin-bottom: 15px;
        }
        
        .comment-form button {
            padding: 10px 20px;
            background-color: #5e72e4;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 15px;
        }
        
        .comment-form button:hover {
            background-color: #4a5fd1;
        }
        
        .login-notice {
            background-color: #333;
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
            text-align: center;
            border: 1px solid #444;
        }
        
        .login-notice a {
            color: #5e72e4;
            text-decoration: none;
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
    </style>
</head>
<body>
    <?php include_once __DIR__ . '/../partials/header.php'; ?>
    
    <div class="topic-container">
        <div class="breadcrumb">
            <a href="/4TTJ/Zielinski%20Olivier/Site/site-v2/pages/forum.php">Forum</a> &raquo; 
            <a href="/4TTJ/Zielinski%20Olivier/Site/site-v2/pages/m.php?slug=<?php echo htmlspecialchars($community['slug']); ?>"><?php echo htmlspecialchars($community['name']); ?></a> &raquo; 
            <?php echo htmlspecialchars($topic['title']); ?>
            <?php if (isset($_SESSION['user'])): ?>
                <a href="/4TTJ/Zielinski%20Olivier/Site/site-v2/pages/debug_comments.php?topic_id=<?php echo $topic_id; ?>" style="float: right; font-size: 12px; color: #888;">Diagnostic</a>
            <?php endif; ?>
        </div>
        
        <div class="topic">
            <h1 class="topic-title"><?php echo htmlspecialchars($topic['title']); ?></h1>
            <div class="topic-meta">
                Posté par <?php echo htmlspecialchars(isset($topic['username']) ? $topic['username'] : 'Anonyme'); ?> • 
                <?php echo isset($topic['created_at']) ? formatTimestamp($topic['created_at']) : 'date inconnue'; ?> • 
                Vues: <?php echo isset($topic['views']) ? $topic['views'] : 0; ?>
            </div>
            <div class="topic-content">
                <?php echo nl2br(htmlspecialchars($topic['content'])); ?>
            </div>
            <div class="vote-container">
                <?php if (isset($_SESSION['user'])): ?>
                    <a href="/4TTJ/Zielinski%20Olivier/Site/site-v2/pages/view_topic.php?id=<?php echo $topic_id; ?>&vote=up" class="vote-btn <?php echo isset($user_topic_vote) && $user_topic_vote > 0 ? 'voted-up' : ''; ?>">&#9650;</a>
                    <span class="vote-score"><?php echo isset($topic['vote_score']) ? $topic['vote_score'] : 0; ?></span>
                    <a href="/4TTJ/Zielinski%20Olivier/Site/site-v2/pages/view_topic.php?id=<?php echo $topic_id; ?>&vote=down" class="vote-btn <?php echo isset($user_topic_vote) && $user_topic_vote < 0 ? 'voted-down' : ''; ?>">&#9660;</a>
                <?php else: ?>
                    <span class="vote-score"><?php echo isset($topic['vote_score']) ? $topic['vote_score'] : 0; ?> votes</span>
                    <small class="login-to-vote">(Connectez-vous pour voter)</small>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="comments-section">
            <div class="comments-header-container">
                <h2 class="comments-header">Commentaires (<?php echo count($comments); ?>)</h2>
                
                <div class="sort-options">
                    <span>Trier par :</span>
                    <a href="?id=<?php echo $topic_id; ?>&sort=date" class="sort-link <?php echo (!isset($_GET['sort']) || $_GET['sort'] === 'date') ? 'active' : ''; ?>">Date</a>
                    <a href="?id=<?php echo $topic_id; ?>&sort=votes" class="sort-link <?php echo (isset($_GET['sort']) && $_GET['sort'] === 'votes') ? 'active' : ''; ?>">Votes</a>
                </div>
            </div>
            
            <?php if (!empty($comments)): ?>
                <?php foreach ($comments as $comment): ?>
                    <div class="comment" id="comment-<?php echo $comment['id']; ?>">
                        <div class="comment-meta">
                            <?php echo htmlspecialchars(isset($comment['username']) ? $comment['username'] : 'Anonyme'); ?> • 
                            <?php echo isset($comment['created_at']) ? formatTimestamp($comment['created_at']) : 'date inconnue'; ?>
                        </div>
                        <div class="comment-content">
                            <?php echo nl2br(htmlspecialchars($comment['content'])); ?>
                        </div>
                        <div class="vote-container">
                            <?php if (isset($_SESSION['user'])):
                                $user_comment_vote = getUserVote($_SESSION['user_id'], $comment['id'], 'comment');
                            ?>
                                <a href="/4TTJ/Zielinski%20Olivier/Site/site-v2/pages/view_topic.php?id=<?php echo $topic_id; ?>&comment_id=<?php echo $comment['id']; ?>&vote=up" class="vote-btn <?php echo ($user_comment_vote === 1) ? 'voted-up' : ''; ?>">&#9650;</a>
                                <span class="vote-score"><?php echo isset($comment['vote_score']) ? $comment['vote_score'] : 0; ?></span>
                                <a href="/4TTJ/Zielinski%20Olivier/Site/site-v2/pages/view_topic.php?id=<?php echo $topic_id; ?>&comment_id=<?php echo $comment['id']; ?>&vote=down" class="vote-btn <?php echo ($user_comment_vote === -1) ? 'voted-down' : ''; ?>">&#9660;</a>
                            <?php else: ?>
                                <span class="vote-score"><?php echo isset($comment['vote_score']) ? $comment['vote_score'] : 0; ?> votes</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>Aucun commentaire pour le moment.</p>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['user'])): ?>
                <div class="comment-form">
                    <h3>Ajouter un commentaire</h3>
                    <?php if (!empty($comment_error)): ?>
                        <div class="alert alert-danger"><?php echo $comment_error; ?></div>
                    <?php endif; ?>
                    
                    <?php if (isset($_GET['commented']) && $_GET['commented'] == 1): ?>
                        <div class="alert alert-success">Votre commentaire a été ajouté avec succès!</div>
                    <?php endif; ?>
                    
                    <form action="/4TTJ/Zielinski%20Olivier/Site/site-v2/pages/view_topic.php?id=<?php echo $topic_id; ?>" method="post">
                        <textarea name="comment_content" rows="4" required placeholder="Écrivez votre commentaire ici..."></textarea>
                        <button type="submit">Envoyer</button>
                    </form>
                </div>
            <?php else: ?>
                <div class="login-notice">
                    <p>Connectez-vous pour participer à la discussion.</p>
                    <a href="/4TTJ/Zielinski%20Olivier/Site/site-v2/pages/login.php">Se connecter</a> ou 
                    <a href="/4TTJ/Zielinski%20Olivier/Site/site-v2/pages/register.php">S'inscrire</a>
                </div>
            <?php endif; ?>
        </div>
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
        
        // Scroll to comment form if commented=1 is in the URL
        if (window.location.search.includes('commented=1')) {
            const commentForm = document.querySelector('.comment-form');
            if (commentForm) {
                commentForm.scrollIntoView({ behavior: 'smooth' });
            }
        }
        
        // Gestion des votes - mettre à jour le score
        const urlParams = new URLSearchParams(window.location.search);
        
        // Vérifier si un vote sur le sujet a eu lieu
        if (urlParams.has('topic_voted') && urlParams.has('topic_score')) {
            const newScore = urlParams.get('topic_score');
            const scoreElement = document.querySelector('.topic .vote-score');
            if (scoreElement) {
                scoreElement.textContent = newScore;
            }
        }
        
        // Vérifier si un vote sur un commentaire a eu lieu
        if (urlParams.has('comment_voted') && urlParams.has('comment_id') && urlParams.has('comment_score')) {
            const commentId = urlParams.get('comment_id');
            const newScore = urlParams.get('comment_score');
            
            // Trouver le commentaire correspondant et mettre à jour son score
            const commentScoreElement = document.querySelector(`#comment-${commentId} .vote-score`);
            if (commentScoreElement) {
                commentScoreElement.textContent = newScore;
                
                // Faire défiler vers le commentaire voté
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
        
        // Nettoyer l'URL après avoir traité les paramètres
        if (window.history && window.history.replaceState) {
            // Conserver uniquement le paramètre id dans l'URL
            const cleanUrl = `${window.location.pathname}?id=${urlParams.get('id')}`;
            window.history.replaceState({}, document.title, cleanUrl);
        }
    });
    </script>
</body>
</html> 