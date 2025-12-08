<?php
// Admin Action Logger API
// Logs admin actions to the admin_logs table

header('Content-Type: application/json');
require_once '../onboarding/config.php';

// Check if user is logged in and is admin/developer
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['grade_level'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$gradeLevel = $_SESSION['grade_level'];
$isAdminDev = in_array(strtolower($gradeLevel), ['developer', 'admin']);

if (!$isAdminDev) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden']);
    exit();
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['action'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Action required']);
    exit();
}

$admin_id = $_SESSION['user_id'];
$action = $data['action'];
$ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

try {
    $stmt = $pdo->prepare("
        INSERT INTO admin_logs (admin_id, action, action_timestamp, ip_address) 
        VALUES (?, ?, NOW(), ?)
    ");
    
    $stmt->execute([$admin_id, $action, $ip_address]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Action logged successfully'
    ]);
    
} catch (PDOException $e) {
    error_log("Admin Log Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to log action'
    ]);
}
