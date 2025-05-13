<?php
// Remplacer session_start() par l'inclusion de la configuration
include '../php/session_config.php';
require_once __DIR__ . '/../php/db.php';
require_once __DIR__ . '/../php/forum/forum_functions.php';

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
    $topic_image = isset($_POST['topic_image_data']) ? $_POST['topic_image_data'] : '';
    
    // Vérifier que l'ID de l'utilisateur est disponible dans la session
    if (!isset($_SESSION['user_id'])) {
        $errors[] = "Session utilisateur non valide. Veuillez vous reconnecter.";
    } else {
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
            // Commencer une transaction
            $conn->begin_transaction();
            
            try {
                // Préparer la requête avec des paramètres liés pour éviter les injections SQL
                $stmt = $conn->prepare("INSERT INTO forum_topics (community_id, user_id, title, content, created_at) VALUES (?, ?, ?, ?, NOW())");
                $stmt->bind_param("iiss", $community_id, $user_id, $title, $content);
                
                if ($stmt->execute()) {
                    $topic_id = $stmt->insert_id;
                    
                    // Sauvegarder l'image si elle existe
                    if (!empty($topic_image)) {
                        // Extraire le contenu de l'image (sans le préfixe data:image/...)
                        if (preg_match('/^data:image\/(\w+);base64,(.+)$/', $topic_image, $matches)) {
                            $image_type = $matches[1];
                            $image_data = base64_decode($matches[2]);
                            
                            // Créer le dossier de stockage s'il n'existe pas
                            $upload_dir = __DIR__ . '/../uploads/topic_images/';
                            if (!file_exists($upload_dir)) {
                                mkdir($upload_dir, 0777, true);
                            }
                            
                            // Générer un nom unique pour l'image
                            $image_name = 'topic_' . $topic_id . '_' . time() . '.' . $image_type;
                            $image_path = $upload_dir . $image_name;
                            
                            // Enregistrer l'image
                            if (file_put_contents($image_path, $image_data)) {
                                // Mettre à jour le topic avec le chemin de l'image
                                $image_url = '../uploads/topic_images/' . $image_name;
                                $update_stmt = $conn->prepare("UPDATE forum_topics SET image_url = ? WHERE id = ?");
                                $update_stmt->bind_param("si", $image_url, $topic_id);
                                $update_stmt->execute();
                                $update_stmt->close();
                            }
                        }
                    }
                    
                    $success = true;
                    
                    // Get community slug for redirect
                    $stmt = $conn->prepare("SELECT slug FROM forum_communities WHERE id = ?");
                    $stmt->bind_param("i", $community_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $community = $result->fetch_assoc();
                    $community_slug = $community['slug'];
                    
                    // Valider la transaction
                    $conn->commit();
                } else {
                    $errors[] = "Erreur lors de la création du sujet. Veuillez réessayer.";
                    $conn->rollback();
                }
            } catch (Exception $e) {
                // En cas d'erreur, annuler la transaction
                $conn->rollback();
                $errors[] = "Erreur: " . $e->getMessage();
            }
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
    <!-- Remplacer par CKEditor 5 avec tous les plugins (version complète) -->
    <script src="https://cdn.ckeditor.com/ckeditor5/41.0.0/super-build/ckeditor.js"></script>
    <!-- La version super-build contient déjà les traductions -->
    <style>
        .form-container {
            max-width: 1000px;
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
        
        .form-input, .form-select {
            width: 100%;
            padding: 12px;
            border: 1px solid #444;
            border-radius: 4px;
            background-color: #333;
            color: #fff;
            font-size: 1rem;
        }
        
        .ck-editor__editable {
            min-height: 400px;
            background-color: #333 !important;
            color: #fff !important;
        }
        
        .ck.ck-editor__main>.ck-editor__editable {
            background-color: #333 !important;
        }
        
        .ck.ck-toolbar {
            background-color: #333 !important;
            border: 1px solid #444 !important;
        }
        
        .ck.ck-toolbar__separator {
            background-color: #444 !important;
        }
        
        .ck.ck-button {
            color: #fff !important;
        }
        
        .ck.ck-button:hover {
            background-color: #444 !important;
        }
        
        .ck.ck-button.ck-on {
            background-color: #444 !important;
        }
        
        /* Style pour le dropdown de taille de police */
        .ck-dropdown__panel {
            background-color: #222 !important;
            border-color: #444 !important;
        }
        
        .ck-list {
            background-color: #222 !important;
        }
        
        .ck-list__item {
            color: #fff !important;
        }
        
        .ck-list__item:hover {
            background-color: #444 !important;
        }
        
        .ck-list__item_active {
            background-color: #5e72e4 !important;
        }
        
        .font-size-control {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            gap: 10px;
        }
        
        .font-size-label {
            color: #fff;
            font-weight: bold;
        }
        
        .font-size-input {
            width: 70px;
            padding: 8px;
            border-radius: 4px;
            background-color: #333;
            color: #fff;
            border: 1px solid #444;
            text-align: center;
        }
        
        .btn-apply {
            background-color: #5e72e4;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .btn-apply:hover {
            background-color: #4a5fd1;
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
                    <label for="topic_image" class="form-label">Image du topic (optionnel)</label>
                    <div class="image-upload-container" id="topic-image-upload">
                        <div class="image-upload-area" id="topic-drop-area">
                            <p>Glissez et déposez une image ici ou</p>
                            <input type="file" id="topic-file-input" name="topic_image" accept="image/*" style="display:none">
                            <button type="button" class="btn-upload" id="topic-upload-btn">Sélectionner une image</button>
                        </div>
                        <div class="image-preview" id="topic-image-preview"></div>
                        <input type="hidden" name="topic_image_data" id="topic-image-data">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="content" class="form-label">Contenu</label>
                    <div id="editor-container">
                        <textarea id="editor" name="content"><?php echo isset($_POST['content']) ? htmlspecialchars($_POST['content']) : ''; ?></textarea>
                    </div>
                    <p class="form-help">Vous pouvez utiliser le formatage de texte enrichi. Les liens seront automatiquement détectés.</p>
                </div>
                
                <div class="form-group form-submit">
                    <button type="submit" class="btn btn-primary">Publier le Sujet</button>
                    <a href="/4TTJ/Zielinski%20Olivier/Site/site-v2/pages/forum.php" class="btn btn-secondary" style="margin-left: 10px;">Cancel</a>
                </div>
            </form>
        <?php endif; ?>
    </div>
    
    <?php include_once __DIR__ . '/../partials/footer.php'; ?>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // CKEditor 5 super-build (version complète avec tous les plugins inclus)
            CKEDITOR.ClassicEditor.create(document.getElementById("editor"), {
                // Configuration de l'éditeur
                toolbar: {
                    items: [
                        'heading', '|',
                        'bold', 'italic', 'strikethrough', 'underline', '|',
                        'fontSize', 'fontFamily', 'fontColor', 'fontBackgroundColor', '|',
                        'bulletedList', 'numberedList', '|',
                        'alignment', 'outdent', 'indent', '|',
                        'link', 'blockQuote', 'insertTable', 'undo', 'redo'
                    ],
                    shouldNotGroupWhenFull: true
                },
                // Plugins activés
                plugins: [
                    'Alignment', 'Autoformat', 'BlockQuote', 'Bold', 'Essentials', 
                    'FontBackgroundColor', 'FontColor', 'FontFamily', 'FontSize', 
                    'Heading', 'Highlight', 'Indent', 'Italic', 'Link', 'List', 
                    'Paragraph', 'PasteFromOffice', 'RemoveFormat', 'SpecialCharacters', 
                    'Strikethrough', 'Table', 'Underline'
                ],
                // Configuration des options de taille de police
                fontSize: {
                    options: [
                        9, 10, 11, 12, 13, 14, 16, 18, 20, 22, 24, 26, 28, 36, 48
                    ],
                    supportAllValues: true
                },
                // Configuration des options de famille de police
                fontFamily: {
                    options: [
                        'default',
                        'Arial, Helvetica, sans-serif',
                        'Courier New, Courier, monospace',
                        'Georgia, serif',
                        'Times New Roman, Times, serif',
                        'Trebuchet MS, Helvetica, sans-serif',
                        'Verdana, Geneva, sans-serif'
                    ],
                    supportAllValues: true
                },
                language: 'fr',
                placeholder: 'Écrivez votre message ici...'
            })
            .then(editor => {
                window.editor = editor;
                
                // Appliquer le style sombre à l'éditeur
                const editorElement = document.querySelector('.ck-editor__editable');
                if (editorElement) {
                    editorElement.style.backgroundColor = '#333';
                    editorElement.style.color = '#fff';
                    editorElement.style.minHeight = '400px';
                }
                
                const toolbarElement = document.querySelector('.ck-toolbar');
                if (toolbarElement) {
                    toolbarElement.style.backgroundColor = '#333';
                    toolbarElement.style.border = '1px solid #444';
                }
                
                // Validation du formulaire
                document.querySelector('form').addEventListener('submit', function(e) {
                    if (editor.getData().trim() === '') {
                        e.preventDefault();
                        alert('Le contenu est requis.');
                        return false;
                    }
                    
                    // Mettre à jour le contenu du textarea avant soumission
                    document.getElementById('editor').value = editor.getData();
                });
            })
            .catch(error => {
                console.error('Erreur lors de l\'initialisation de l\'éditeur:', error);
                
                // Utiliser un éditeur basique en cas d'échec
                const textarea = document.getElementById('editor');
                textarea.style.display = 'block';
                textarea.style.width = '100%';
                textarea.style.minHeight = '400px';
                textarea.style.padding = '10px';
                textarea.style.backgroundColor = '#333';
                textarea.style.color = '#fff';
                textarea.style.border = '1px solid #444';
                
                document.querySelector('form').addEventListener('submit', function(e) {
                    if (textarea.value.trim() === '') {
                        e.preventDefault();
                        alert('Le contenu est requis.');
                        return false;
                    }
                });
            });
            
            // Configuration du drag & drop pour les images
            setupImageUpload('topic');
            
            function setupImageUpload(prefix) {
                const dropArea = document.getElementById(`${prefix}-drop-area`);
                const fileInput = document.getElementById(`${prefix}-file-input`);
                const uploadBtn = document.getElementById(`${prefix}-upload-btn`);
                const imagePreview = document.getElementById(`${prefix}-image-preview`);
                const imageData = document.getElementById(`${prefix}-image-data`);
                
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