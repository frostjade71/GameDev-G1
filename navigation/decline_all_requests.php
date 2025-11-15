<?php
// Set error reporting
error_reporting(0);
ini_set('display_errors', 0);

// Set content type to JSON first
header('Content-Type: application/json');

require_once '../onboarding/config.php';

// Check if user is logged in
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    die(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

$user_id = $_SESSION['user_id'];

try {
    // Verify database connection
    if (!isset($pdo) || !($pdo instanceof PDO)) {
        throw new Exception('Database connection error');
    }

    // Start transaction
    $pdo->beginTransaction();

    // Delete all pending friend requests for the user
    $stmt = $pdo->prepare("DELETE FROM friend_requests WHERE receiver_id = ? AND status = 'pending'");
    if (!$stmt->execute([$user_id])) {
        throw new Exception('Failed to execute delete query');
    }
    
    $deletedCount = $stmt->rowCount();
    $pdo->commit();

    echo json_encode(['success' => true, 'deleted' => $deletedCount]);
    
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
exit();
?>