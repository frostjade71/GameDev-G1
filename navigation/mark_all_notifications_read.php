<?php
require_once '../onboarding/config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

$user_id = $_SESSION['user_id'];

try {
    // Start transaction
    $pdo->beginTransaction();
    
    // Get all pending friend requests for the current user
    $stmt = $pdo->prepare("
        SELECT id, requester_id 
        FROM friend_requests 
        WHERE receiver_id = ? AND status = 'pending'
    ");
    $stmt->execute([$user_id]);
    $friend_requests = $stmt->fetchAll();
    
    $deleted_requests = 0;
    $deleted_notifications = 0;
    
    // Delete each friend request and its associated notification
    foreach ($friend_requests as $request) {
        // Delete the friend request
        $stmt = $pdo->prepare("DELETE FROM friend_requests WHERE id = ?");
        $stmt->execute([$request['id']]);
        $deleted_requests++;
        
        // Delete the associated notification
        $stmt = $pdo->prepare("
            DELETE FROM notifications 
            WHERE user_id = ? AND type = 'friend_request' 
            AND JSON_EXTRACT(data, '$.requester_id') = ?
        ");
        $stmt->execute([$user_id, $request['requester_id']]);
        $deleted_notifications++;
    }
    
    // Commit transaction
    $pdo->commit();
    
    echo json_encode([
        'success' => true, 
        'message' => "Marked all friend requests as read and removed {$deleted_requests} friend requests",
        'data' => [
            'user_id' => $user_id,
            'friend_requests_deleted' => $deleted_requests,
            'notifications_deleted' => $deleted_notifications
        ]
    ]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    $pdo->rollBack();
    
    error_log("Error marking notifications as read: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
}
?>
