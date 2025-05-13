<?php
require_once __DIR__ . '/../php/db.php';
require_once __DIR__ . '/../php/forum/forum_functions.php';

// Start the session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Vérifier si l'utilisateur est connecté et est admin
if (!isset($_SESSION['user']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header('Location: ../pages/forum.php');
    exit;
}

// Récupérer l'ID de la communauté
$community_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($community_id <= 0) {
    header('Location: ../pages/forum.php');
    exit;
}

// Récupérer les informations de la communauté
$stmt = $conn->prepare("SELECT * FROM forum_communities WHERE id = ?");
$stmt->bind_param("i", $community_id);
$stmt->execute();
$result = $stmt->get_result();
$community = $result->fetch_assoc();
$stmt->close();

if (!$community) {
    header('Location: ../pages/forum.php');
    exit;
}

$error = '';
$success = '';

// Traiter le formulaire de modification
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $slug = trim($_POST['slug']);
    $description = trim($_POST['description']);
    $community_image = isset($_POST['community_image_data']) ? $_POST['community_image_data'] : '';
    $remove_current_image = isset($_POST['remove_current_image']) && $_POST['remove_current_image'] == '1';
    
    // Validation
    if (empty($name)) {
        $error = 'Community name is required';
    } elseif (empty($slug)) {
        $error = 'Community slug is required';
    } else {
        // Commencer une transaction
        $conn->begin_transaction();
        
        try {
            // Vérifier si le slug existe déjà pour une autre communauté
            $stmt = $conn->prepare("SELECT id FROM forum_communities WHERE slug = ? AND id != ?");
            $stmt->bind_param("si", $slug, $community_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $error = 'This slug is already used by another community';
                $conn->rollback();
            } else {
                // Mettre à jour les informations de base de la communauté
                $stmt = $conn->prepare("UPDATE forum_communities SET name = ?, slug = ?, description = ? WHERE id = ?");
                $stmt->bind_param("sssi", $name, $slug, $description, $community_id);
                
                if (!$stmt->execute()) {
                    throw new Exception('Error updating community information');
                }
                
                // Gérer la suppression d'image
                if ($remove_current_image && !empty($community['image_url'])) {
                    $stmt = $conn->prepare("UPDATE forum_communities SET image_url = NULL WHERE id = ?");
                    $stmt->bind_param("i", $community_id);
                    if (!$stmt->execute()) {
                        throw new Exception('Error removing community image');
                    }
                    
                    // Tenter de supprimer le fichier image
                    $image_path = __DIR__ . '/../' . str_replace('../', '', $community['image_url']);
                    if (file_exists($image_path)) {
                        @unlink($image_path);
                    }
                    
                    $community['image_url'] = null;
                }
                
                // Gérer le téléchargement d'une nouvelle image
                if (!empty($community_image)) {
                    // Extraire le contenu de l'image
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
                            // Supprimer l'ancienne image si elle existe
                            if (!empty($community['image_url'])) {
                                $old_image_path = __DIR__ . '/../' . str_replace('../', '', $community['image_url']);
                                if (file_exists($old_image_path)) {
                                    @unlink($old_image_path);
                                }
                            }
                            
                            // Mettre à jour le chemin de l'image
                            $image_url = '../uploads/community_images/' . $image_name;
                            $stmt = $conn->prepare("UPDATE forum_communities SET image_url = ? WHERE id = ?");
                            $stmt->bind_param("si", $image_url, $community_id);
                            if (!$stmt->execute()) {
                                throw new Exception('Error updating community image');
                            }
                            
                            $community['image_url'] = $image_url;
                        } else {
                            throw new Exception('Error saving image file');
                        }
                    }
                }
                
                $conn->commit();
                $success = 'Community updated successfully';
                
                // Mettre à jour les données de la communauté en mémoire
                $community['name'] = $name;
                $community['slug'] = $slug;
                $community['description'] = $description;
            }
        } catch (Exception $e) {
            $conn->rollback();
            $error = $e->getMessage();
        }
    }
}

// Page title
$page_title = "Edit Community - MangaMuse";
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
        .edit-community-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .edit-community-form {
            background-color: #333;
            border-radius: 8px;
            padding: 20px;
            margin-top: 20px;
            border: 1px solid #444;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #fff;
        }
        
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border-radius: 4px;
            background-color: #222;
            color: #fff;
            border: 1px solid #444;
            box-sizing: border-box;
        }
        
        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }
        
        .btn-group {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        
        .btn {
            padding: 10px 20px;
            border-radius: 4px;
            text-decoration: none;
            font-weight: bold;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background-color: #5e72e4;
            color: white;
            border: none;
        }
        
        .btn-primary:hover {
            background-color: #4a5fd1;
        }
        
        .btn-secondary {
            background-color: #444;
            color: #ddd;
            border: none;
        }
        
        .btn-secondary:hover {
            background-color: #555;
        }
        
        .btn-danger {
            background-color: #F44336;
            color: white;
            border: none;
        }
        
        .btn-danger:hover {
            background-color: #D32F2F;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
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
            background-color: #222;
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
        
        .current-image {
            margin-bottom: 15px;
            text-align: center;
        }
        
        .current-image img {
            max-width: 100%;
            max-height: 200px;
            border-radius: 4px;
            border: 1px solid #444;
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
    <?php include_once '../partials/header.php'; ?>
    
    <div class="edit-community-container">
        <div class="breadcrumb">
            <a href="../pages/forum.php">Forum</a> &raquo; 
            <a href="../pages/m.php?slug=<?php echo htmlspecialchars($community['slug']); ?>">m/<?php echo htmlspecialchars($community['slug']); ?></a> &raquo; 
            Edit Community
        </div>
        
        <h1>Edit Community</h1>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <div class="edit-community-form">
            <form method="post">
                <div class="form-group">
                    <label for="name">Community Name</label>
                    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($community['name']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="slug">Community Slug</label>
                    <input type="text" id="slug" name="slug" value="<?php echo htmlspecialchars($community['slug']); ?>" required>
                    <small style="color: #888;">The slug will be used in the URL (e.g., m/your-slug)</small>
                </div>
                
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description"><?php echo htmlspecialchars($community['description']); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label>Community Image</label>
                    
                    <?php if (!empty($community['image_url'])): ?>
                    <div class="current-image" id="current-image-container">
                        <p>Current image:</p>
                        <img src="<?php echo htmlspecialchars($community['image_url']); ?>" alt="Community image">
                        <div class="preview-controls">
                            <button type="button" class="btn-remove" id="remove-current-image">Remove Image</button>
                        </div>
                        <input type="hidden" name="remove_current_image" id="remove-current-image-input" value="0">
                    </div>
                    <?php endif; ?>
                    
                    <div class="image-upload-container" id="community-image-upload">
                        <div class="image-upload-area" id="community-drop-area">
                            <p>Drag & drop a new image here or</p>
                            <input type="file" id="community-file-input" name="community_image" accept="image/*" style="display:none">
                            <button type="button" class="btn-upload" id="community-upload-btn">Select an image</button>
                        </div>
                        <div class="image-preview" id="community-image-preview"></div>
                        <input type="hidden" name="community_image_data" id="community-image-data">
                    </div>
                </div>
                
                <div class="btn-group">
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                    <a href="../pages/m.php?slug=<?php echo htmlspecialchars($community['slug']); ?>" class="btn btn-secondary">Cancel</a>
                    <button type="button" class="btn btn-danger" onclick="confirmDelete()">Delete Community</button>
                </div>
            </form>
        </div>
    </div>
    
    <?php include_once '../partials/footer.php'; ?>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Fonction de confirmation de suppression
        window.confirmDelete = function() {
            if (confirm('Are you sure you want to delete this community? This will also delete all posts and comments in this community. This action cannot be undone.')) {
                window.location.href = 'delete_community.php?id=<?php echo $community_id; ?>';
            }
        };
        
        // Gestion de la suppression de l'image actuelle
        const removeCurrentBtn = document.getElementById('remove-current-image');
        const currentImageContainer = document.getElementById('current-image-container');
        const removeCurrentInput = document.getElementById('remove-current-image-input');
        
        if (removeCurrentBtn && currentImageContainer && removeCurrentInput) {
            removeCurrentBtn.addEventListener('click', function() {
                currentImageContainer.style.display = 'none';
                removeCurrentInput.value = '1';
            });
        }
        
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
                    // Masquer l'image actuelle si elle existe
                    const currentImageContainer = document.getElementById('current-image-container');
                    if (currentImageContainer) {
                        currentImageContainer.style.display = 'none';
                        // Indiquer qu'on veut supprimer l'ancienne image
                        const removeCurrentInput = document.getElementById('remove-current-image-input');
                        if (removeCurrentInput) {
                            removeCurrentInput.value = '1';
                        }
                    }
                    
                    // Masquer la zone de drop
                    dropArea.style.display = 'none';
                    
                    // Afficher l'aperçu
                    imagePreview.innerHTML = `
                        <img src="${e.target.result}" alt="Image preview">
                        <div class="preview-controls">
                            <button type="button" class="btn-remove" id="${prefix}-remove-btn">Remove Image</button>
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
                        
                        // Réafficher l'image actuelle si elle existe
                        if (currentImageContainer) {
                            currentImageContainer.style.display = 'block';
                            const removeCurrentInput = document.getElementById('remove-current-image-input');
                            if (removeCurrentInput) {
                                removeCurrentInput.value = '0';
                            }
                        }
                    });
                };
                
                reader.readAsDataURL(file);
            }
        }
    });
    </script>
</body>
</html> 