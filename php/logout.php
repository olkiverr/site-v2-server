<?php
// Remplacer session_start() par l'inclusion de la configuration
include 'session_config.php';
// Vider toutes les variables de session
$_SESSION = array();
// Détruire la session
session_destroy();
session_write_close();
// Rediriger avec un paramètre pour forcer le rechargement
header('Location: ../index.php');
exit;
?>
