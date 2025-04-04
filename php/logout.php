<?php
session_start();
// Vider toutes les variables de session
$_SESSION = array();
// Détruire la session
session_destroy();
session_write_close();
// Rediriger avec un paramètre pour forcer le rechargement
header('Location: ../index.php');
exit;
?>
