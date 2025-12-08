<?php
/**
 * Delete User API Endpoint
 * Handles user deletion from the database
 */

require_once '../onboarding/config.php';

// Set JSON header
header('Content-Type: application/json');

// Check if user is logged in and is admin/developer
if (!isset($_SESSION['user_id']) || !isset($_SESSION['grade_level'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

$gradeLevel = $_SESSION['grade_level'];
$isAdminDev = in_array(strtolower($gradeLevel), ['developer', 'admin']);

if (!$isAdminDev) {
    echo json_encode(['success' => false, 'message' => 'Insufficient permissions']);
    exit();
}

// Only developers can delete users
if (strtolower($gradeLevel) !== 'developer') {
    echo json_encode(['success' => false, 'message' => 'Only developers can delete users']);
    exit();
}

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);
$userId = $input['user_id'] ?? null;

if (!$userId) {
    echo json_encode(['success' => false, 'message' => 'User ID is required']);
    exit();
}

// Prevent self-deletion
if ($userId == $_SESSION['user_id']) {
    echo json_encode(['success' => false, 'message' => 'You cannot delete your own account']);
    exit();
}

try {
    // Get user info before deletion for logging
    $stmt = $pdo->prepare("SELECT username, email, grade_level FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit();
    }
    
    // Delete user (CASCADE will handle related records)
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    
    // Log admin action
    try {
        $logStmt = $pdo->prepare("
            INSERT INTO admin_logs (admin_id, action, action_timestamp, ip_address) 
            VALUES (?, ?, NOW(), ?)
        ");
        $logStmt->execute([
            $_SESSION['user_id'],
            "Deleted user: {$user['username']} (ID: {$userId}, Email: {$user['email']}, Grade: {$user['grade_level']})",
            $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
    } catch (PDOException $e) {
        // Log error but don't fail the deletion
        error_log("Failed to log admin action: " . $e->getMessage());
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'User deleted successfully',
        'deleted_user' => $user['username']
    ]);
    
} catch (PDOException $e) {
    error_log("Delete user error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
