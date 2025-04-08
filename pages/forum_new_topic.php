<?php
require_once __DIR__ . '/../php/db.php';
require_once __DIR__ . '/../php/forum/forum_functions.php';

// Start the session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if the user is logged in
if (!isset($_SESSION['user'])) {
    header('Location: /4TTJ/Zielinski%20Olivier/Site/site-v2/pages/login.php');
    exit;
}

// Get all communities for dropdown
$communities = getAllCommunities();

// Get pre-selected community if provided
$selected_community_id = isset($_GET['community_id']) ? intval($_GET['community_id']) : 0;

// Process form submission
$errors = [];
$success = false;
$topic_id = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $community_id = isset($_POST['community_id']) ? intval($_POST['community_id']) : 0;
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $user_id = $_SESSION['user_id'];
    
    // Validate inputs
    if ($community_id <= 0) {
        $errors[] = "Please select a community.";
    }
    
    if (empty($title)) {
        $errors[] = "Le titre est requis.";
    } elseif (strlen($title) > 255) {
        $errors[] = "Le titre ne peut pas dépasser 255 caractères.";
    }
    
    if (empty($content)) {
        $errors[] = "Le contenu est requis.";
    }
    
    // Create topic if no errors
    if (empty($errors)) {
        $topic_id = createTopic($community_id, $user_id, $title, $content);
        
        if ($topic_id > 0) {
            $success = true;
            
            // Get community slug for redirect
            $community = getCommunity($community_id);
            $community_slug = $community['slug'];
        } else {
            $errors[] = "Erreur lors de la création du sujet. Veuillez réessayer.";
        }
    }
}

// Page title
$page_title = "Nouveau Sujet - MangaMuse";
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="/4TTJ/Zielinski%20Olivier/Site/site-v2/css/header.css">
    <link rel="stylesheet" href="/4TTJ/Zielinski%20Olivier/Site/site-v2/css/footer.css">
    <link rel="stylesheet" href="/4TTJ/Zielinski%20Olivier/Site/site-v2/css/forum/forum.css">
    <style>
        .form-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #fff;
        }
        
        .form-input, .form-textarea, .form-select {
            width: 100%;
            padding: 10px;
            border: 1px solid #444;
            border-radius: 4px;
            background-color: #333;
            color: #fff;
            font-size: 1rem;
        }
        
        .form-textarea {
            min-height: 200px;
            resize: vertical;
        }
        
        .form-help {
            margin-top: 5px;
            font-size: 0.85rem;
            color: #aaa;
        }
        
        .form-submit {
            margin-top: 30px;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        
        .alert-danger {
            background-color: rgba(220, 53, 69, 0.2);
            color: #dc3545;
            border: 1px solid #dc3545;
        }
        
        .alert-success {
            background-color: rgba(40, 167, 69, 0.2);
            color: #28a745;
            border: 1px solid #28a745;
        }
    </style>
</head>
<body>
    <?php include_once __DIR__ . '/../partials/header.php'; ?>
    
    <div class="form-container">
        <div class="breadcrumb">
            <a href="/4TTJ/Zielinski%20Olivier/Site/site-v2/pages/forum.php">Forum</a> &raquo; Nouveau Sujet
        </div>
        
        <h1 class="forum-heading">Créer un Nouveau Sujet</h1>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul style="margin: 0; padding-left: 20px;">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                <p>Votre sujet a été créé avec succès!</p>
                <p><a href="/4TTJ/Zielinski%20Olivier/Site/site-v2/pages/forum_topic.php?id=<?php echo $topic_id; ?>">Voir le sujet</a> ou <a href="/4TTJ/Zielinski%20Olivier/Site/site-v2/pages/m.php?slug=<?php echo $community_slug; ?>">Retourner à m/<?php echo htmlspecialchars($community_slug); ?></a></p>
            </div>
        <?php else: ?>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="community_id" class="form-label">Community</label>
                    <select id="community_id" name="community_id" class="form-select" required>
                        <option value="">Select a community</option>
                        <?php foreach ($communities as $community): ?>
                            <option value="<?php echo $community['id']; ?>" <?php echo $community['id'] == $selected_community_id ? 'selected' : ''; ?>>
                                m/<?php echo htmlspecialchars($community['slug']); ?> - <?php echo htmlspecialchars($community['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (empty($communities)): ?>
                        <p class="form-help">
                            No community available. <a href="/4TTJ/Zielinski%20Olivier/Site/site-v2/pages/create_community.php">Create a community</a> first.
                        </p>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label for="title" class="form-label">Titre</label>
                    <input type="text" id="title" name="title" class="form-input" value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="content" class="form-label">Contenu</label>
                    <textarea id="content" name="content" class="form-textarea" required><?php echo isset($_POST['content']) ? htmlspecialchars($_POST['content']) : ''; ?></textarea>
                    <p class="form-help">Vous pouvez utiliser le formatage de texte simple. Les liens seront automatiquement détectés.</p>
                </div>
                
                <div class="form-group form-submit">
                    <button type="submit" class="btn btn-primary">Publier le Sujet</button>
                    <a href="/4TTJ/Zielinski%20Olivier/Site/site-v2/pages/forum.php" class="btn btn-secondary" style="margin-left: 10px;">Cancel</a>
                </div>
            </form>
        <?php endif; ?>
    </div>
    
    <?php include_once __DIR__ . '/../partials/footer.php'; ?>
</body>
</html> 