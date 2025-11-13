<?php
require_once '../onboarding/config.php';

// Set content type to JSON
header('Content-Type: application/json');

// Check if user is logged in
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];
$response = [];

try {
    // Start transaction
    $pdo->beginTransaction();

    // Delete all pending friend requests for the user
    $stmt = $pdo->prepare("DELETE FROM friend_requests WHERE receiver_id = ? AND status = 'pending'");
    $stmt->execute([$user_id]);
    $deletedCount = $stmt->rowCount();

    // Commit transaction
    $pdo->commit();

    $response = [
        'success' => true,
        'message' => $deletedCount > 0 
            ? 'Successfully declined all friend requests' 
            : 'No pending friend requests to decline',
        'count' => $deletedCount
    ];
} catch (Exception $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    http_response_code(500);
    $response = [
        'success' => false,
        'message' => 'An error occurred while processing your request',
        'error' => $e->getMessage()
    ];
}

// Ensure no output before this
ob_clean();
echo json_encode($response);
?>