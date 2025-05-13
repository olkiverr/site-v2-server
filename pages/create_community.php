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
    $community_image = isset($_POST['community_image_data']) ? $_POST['community_image_data'] : '';
    
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
        // Commencer une transaction
        $conn->begin_transaction();
        
        try {
            // D'abord insérer sans l'image
            $stmt = $conn->prepare("INSERT INTO forum_communities (name, slug, description, user_id) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("sssi", $name, $slug, $description, $_SESSION['user_id']);
            
            if ($stmt->execute()) {
                $community_id = $conn->insert_id;
                $community_slug = $slug;
                
                // Sauvegarder l'image si elle existe
                if (!empty($community_image)) {
                    // Extraire le contenu de l'image (sans le préfixe data:image/...)
                    if (preg_match('/^data:image\/(\w+);base64,(.+)$/', $community_image, $matches)) {
                        $image_type = $matches[1];
                        $image_data = base64_decode($matches[2]);
                        
                        // Créer le dossier de stockage s'il n'existe pas
                        $upload_dir = __DIR__ . '/../uploads/community_images/';
                        if (!file_exists($upload_dir)) {
                            mkdir($upload_dir, 0777, true);
                        }
                        
                        // Générer un nom unique pour l'image
                        $image_name = 'community_' . $community_id . '_' . time() . '.' . $image_type;
                        $image_path = $upload_dir . $image_name;
                        
                        // Enregistrer l'image
                        if (file_put_contents($image_path, $image_data)) {
                            // Mettre à jour la communauté avec le chemin de l'image
                            $image_url = '../uploads/community_images/' . $image_name;
                            $update_stmt = $conn->prepare("UPDATE forum_communities SET image_url = ? WHERE id = ?");
                            $update_stmt->bind_param("si", $image_url, $community_id);
                            $update_stmt->execute();
                            $update_stmt->close();
                        }
                    }
                }
                
                $success = true;
                $conn->commit();
            } else {
                $errors[] = "Error creating community: " . $conn->error;
                $conn->rollback();
            }
            
            $stmt->close();
        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = "Error: " . $e->getMessage();
        }
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
        
        /* Styles pour l'upload d'image */
        .image-upload-container {
            margin-bottom: 15px;
        }
        
        .image-upload-area {
            border: 2px dashed #444;
            border-radius: 4px;
            padding: 20px;
            text-align: center;
            transition: all 0.3s ease;
            background-color: #333;
            color: #aaa;
            margin-bottom: 10px;
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
            transition: background-color 0.3s;
            margin-top: 10px;
        }
        
        .btn-upload:hover {
            background-color: #4a5fd1;
        }
        
        .image-preview {
            margin-top: 10px;
            text-align: center;
            display: none;
        }
        
        .image-preview img {
            max-width: 100%;
            max-height: 200px;
            border-radius: 4px;
            border: 1px solid #444;
        }
        
        .preview-controls {
            margin-top: 5px;
        }
        
        .btn-remove {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
        }
        
        .btn-remove:hover {
            background-color: #c82333;
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
                
                <div class="form-group">
                    <label for="community_image" class="form-label">Community Image (optionnelle)</label>
                    <div class="image-upload-container" id="community-image-upload">
                        <div class="image-upload-area" id="community-drop-area">
                            <p>Glissez et déposez une image ici ou</p>
                            <input type="file" id="community-file-input" name="community_image" accept="image/*" style="display:none">
                            <button type="button" class="btn-upload" id="community-upload-btn">Sélectionner une image</button>
                        </div>
                        <div class="image-preview" id="community-image-preview"></div>
                        <input type="hidden" name="community_image_data" id="community-image-data">
                        <p class="form-help">Cette image représentera votre communauté dans les listings.</p>
                    </div>
                </div>
                
                <div class="form-group form-submit">
                    <button type="submit" class="btn btn-primary">Create Community</button>
                    <a href="/4TTJ/Zielinski%20Olivier/Site/site-v2/pages/forum.php" class="btn btn-secondary" style="margin-left: 10px;">Cancel</a>
                </div>
            </form>
        <?php endif; ?>
    </div>
    
    <?php include_once __DIR__ . '/../partials/footer.php'; ?>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Configuration du drag & drop pour les images
        setupImageUpload('community');
        
        function setupImageUpload(prefix) {
            const dropArea = document.getElementById(`${prefix}-drop-area`);
            const fileInput = document.getElementById(`${prefix}-file-input`);
            const uploadBtn = document.getElementById(`${prefix}-upload-btn`);
            const imagePreview = document.getElementById(`${prefix}-image-preview`);
            const imageData = document.getElementById(`${prefix}-image-data`);
            
            if (!dropArea || !fileInput || !uploadBtn || !imagePreview) return;
            
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
                    // Masquer la zone de drop
                    dropArea.style.display = 'none';
                    
                    // Afficher l'aperçu
                    imagePreview.innerHTML = `
                        <img src="${e.target.result}" alt="Image preview">
                        <div class="preview-controls">
                            <button type="button" class="btn-remove" id="${prefix}-remove-btn">Supprimer</button>
                        </div>
                    `;
                    imagePreview.style.display = 'block';
                    
                    // Stocker les données de l'image
                    imageData.value = e.target.result;
                    
                    // Ajouter un gestionnaire pour le bouton de suppression
                    document.getElementById(`${prefix}-remove-btn`).addEventListener('click', function() {
                        imagePreview.innerHTML = '';
                        imagePreview.style.display = 'none';
                        imageData.value = '';
                        fileInput.value = '';
                        // Réafficher la zone de drop
                        dropArea.style.display = 'block';
                    });
                };
                
                reader.readAsDataURL(file);
            }
        }
    });
    </script>
</body>
</html> 