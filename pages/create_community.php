<?php
require_once __DIR__ . '/../php/db.php';

// Start the session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if the user is logged in
if (!isset($_SESSION['user'])) {
    header('Location: /4TTJ/Zielinski%20Olivier/Site/site-v2/pages/login.php');
    exit;
}

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    
    // Validate inputs
    if (empty($name)) {
        $errors[] = "Community name is required.";
    } elseif (strlen($name) > 100) {
        $errors[] = "Le nom ne peut pas dépasser 100 caractères.";
    }
    
    if (strlen($description) > 1000) {
        $errors[] = "La description ne peut pas dépasser 1000 caractères.";
    }
    
    // Create slug from name
    $slug = strtolower(preg_replace('/[^a-zA-Z0-9]/', '-', $name));
    $slug = preg_replace('/-+/', '-', $slug);
    $slug = trim($slug, '-');
    
    if (empty($slug)) {
        $errors[] = "Le nom doit contenir au moins un caractère alphanumérique.";
    }
    
    // Check if community already exists
    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT id FROM forum_communities WHERE slug = ? OR name = ?");
        $stmt->bind_param("ss", $slug, $name);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $errors[] = "A community with this name already exists.";
        }
        
        $stmt->close();
    }
    
    // Insert new community
    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO forum_communities (name, slug, description, user_id) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sssi", $name, $slug, $description, $_SESSION['user_id']);
        
        if ($stmt->execute()) {
            $success = true;
            $community_id = $conn->insert_id;
            $community_slug = $slug;
        } else {
            $errors[] = "Error creating community: " . $conn->error;
        }
        
        $stmt->close();
    }
}

// Page title
$page_title = "Create a Community - MangaMuse";
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
        
        .form-input, .form-textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #444;
            border-radius: 4px;
            background-color: #333;
            color: #fff;
            font-size: 1rem;
        }
        
        .form-textarea {
            min-height: 150px;
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
        <h1 class="forum-heading">Create a New Community</h1>
        <p class="text-muted">Create a community to discuss your favorite anime or manga</p>
        
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
                <p>The community has been created successfully!</p>
                <p><a href="/4TTJ/Zielinski%20Olivier/Site/site-v2/pages/m.php?slug=<?php echo $community_slug; ?>">Visiter m/<?php echo htmlspecialchars($community_slug); ?></a></p>
            </div>
        <?php else: ?>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="name" class="form-label">Community Name</label>
                    <input type="text" id="name" name="name" class="form-input" value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" required>
                    <p class="form-help">Par exemple: "Naruto", "One Piece", "Dragon Ball". Ce nom apparaîtra comme m/nom.</p>
                </div>
                
                <div class="form-group">
                    <label for="description" class="form-label">Description (optionnelle)</label>
                    <textarea id="description" name="description" class="form-textarea"><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                    <p class="form-help">Describe your community to help people understand what it's about.</p>
                </div>
                
                <div class="form-group form-submit">
                    <button type="submit" class="btn btn-primary">Create Community</button>
                    <a href="/4TTJ/Zielinski%20Olivier/Site/site-v2/pages/forum.php" class="btn btn-secondary" style="margin-left: 10px;">Cancel</a>
                </div>
            </form>
        <?php endif; ?>
    </div>
    
    <?php include_once __DIR__ . '/../partials/footer.php'; ?>
</body>
</html> 