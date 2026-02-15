<?php
require_once 'onboarding/config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: onboarding/login.php');
    exit();
}

// Redirect to menu.php
header('Location: menu.php');
exit();
?>

