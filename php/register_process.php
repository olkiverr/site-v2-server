<?php

require_once __DIR__ . '/analytics.php';

if ($stmt->affected_rows > 0) {
    recordNewUser();
    
    header("Location: /4TTJ/Zielinski%20Olivier/Site/site-v2/pages/login.php?success=1");
    exit();
} 