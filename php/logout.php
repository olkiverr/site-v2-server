<?php
session_start();
session_destroy();
header("Location: /site-v2/pages/login.php");
exit();
?>
