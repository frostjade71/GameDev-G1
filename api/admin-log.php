<?php
// Admin Action Logger API
// Logs admin actions to the admin_logs table

header('Content-Type: application/json');
require_once '../onboarding/config.php';

// Check if user is logged in and is admin/developer
session_start();
// Check if user is logged in
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['action'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Action required']);
    exit();
}

require_once '../includes/Logger.php';

$action = $input['action'];
$details = $input['details'] ?? null;
$userId = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? null;

// Use the global helper function
if (function_exists('logAudit')) {
    logAudit($action, $userId, $username, $details);
    echo json_encode(['success' => true, 'message' => 'Action logged successfully']);
} else {
    // Fallback if helper not available (shouldn't happen)
    $logger = new AuditLogger($pdo);
    if ($logger->logAction($action, $userId, $username, $details)) {
        echo json_encode(['success' => true, 'message' => 'Action logged successfully']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to log action']);
    }
}
