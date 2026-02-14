<?php
// includes/Logger.php

// Ensure this file is included where $pdo is accessible, or update to include config
if (!isset($pdo)) {
    // Attempt to locate config relative to this file
    // Adjust path as needed based on your structure. 
    // Assuming includes/Logger.php is one level down from root
    $configPath = __DIR__ . '/../onboarding/config.php';
    if (file_exists($configPath)) {
        require_once $configPath;
    }
}

class AuditLogger {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function logAction($action, $userId = null, $username = null, $details = null) {
        try {
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';

            // If userId provided but no username, try to fetch username
            if ($userId && empty($username)) {
                $stmt = $this->pdo->prepare("SELECT username FROM users WHERE id = ?");
                $stmt->execute([$userId]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                $username = $user['username'] ?? 'Unknown';
            }

            $stmt = $this->pdo->prepare("
                INSERT INTO audit_logs (user_id, username, action, details, ip_address, user_agent, created_at)
                VALUES (:user_id, :username, :action, :details, :ip_address, :user_agent, NOW())
            ");
            
            $stmt->execute([
                ':user_id' => $userId,
                ':username' => $username,
                ':action' => $action,
                ':details' => $details,
                ':ip_address' => $ipAddress,
                ':user_agent' => $userAgent
            ]);
            
            return true;
        } catch (PDOException $e) {
            // Fallback logging to file if DB fails
            error_log("Audit Log Failed: " . $e->getMessage());
            return false;
        }
    }
}

// Global helper function for easier usage
if (!function_exists('logAudit')) {
    function logAudit($action, $userId = null, $username = null, $details = null) {
        global $pdo;
        if (isset($pdo)) {
            $logger = new AuditLogger($pdo);
            $logger->logAction($action, $userId, $username, $details);
        }
    }
}
?>
