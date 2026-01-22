<?php
// Database configuration - Auto-detect environment (Docker or Live Server)

// Determine if running on live server or Docker
$isLiveServer = !in_array($_SERVER['HTTP_HOST'] ?? '', ['localhost', 'localhost:8080', '127.0.0.1:8080', '127.0.0.1']);

if ($isLiveServer) {
    // Live server configuration
    $host = 'localhost';
    $dbname = 'frostjad_school_portal';
    $username = 'frostjad_school_portal';
    $password = 'gVpF6VekbCKqYZ4bh2AX';
} else {
    // Docker configuration
    $host = 'db';
    $dbname = 'school_portal';
    $username = 'root';
    $password = 'rootpassword';
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Function to redirect to login if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

// Function to redirect to index if already logged in
function redirectIfLoggedIn() {
    if (isLoggedIn()) {
        header('Location: ../menu.php');
        exit();
    }
}
?>