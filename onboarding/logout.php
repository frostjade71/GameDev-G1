<?php
require_once 'config.php';

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Mark session as inactive in database
if (isset($_SESSION['user_id'])) {
    
    // AUDIT LOG: Logout
    require_once '../includes/Logger.php';
    logAudit('Logout', $_SESSION['user_id'], $_SESSION['username'] ?? null);

    try {
        $stmt = $pdo->prepare("UPDATE user_sessions SET is_active = 0 WHERE session_id = ? AND user_id = ?");
        $stmt->execute([session_id(), $_SESSION['user_id']]);
    } catch (PDOException $e) {
        error_log("Failed to deactivate session: " . $e->getMessage());
    }
}

// Destroy the session
session_destroy();

// Clear any session cookies
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Redirect to login page
header('Location: login.php');
exit();
?>

