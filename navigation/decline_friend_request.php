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

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['request_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit();
}

$user_id = $_SESSION['user_id'];
$request_id = intval($input['request_id']);

try {
    // Start transaction
    $pdo->beginTransaction();
    
    // Verify the friend request exists and belongs to the current user
    $stmt = $pdo->prepare("
        SELECT id, requester_id, receiver_id, status 
        FROM friend_requests 
        WHERE id = ? AND receiver_id = ? AND status = 'pending'
    ");
    $stmt->execute([$request_id, $user_id]);
    $friend_request = $stmt->fetch();
    
    if (!$friend_request) {
        $pdo->rollBack();
        // If friend request doesn't exist, it's already been processed - treat as success
        echo json_encode([
            'success' => true, 
            'message' => 'Friend request already processed',
            'data' => [
                'request_id' => $request_id,
                'already_processed' => true
            ]
        ]);
        exit();
    }
    
    // Delete the friend request (same as cancel request)
    $stmt = $pdo->prepare("DELETE FROM friend_requests WHERE id = ?");
    $stmt->execute([$request_id]);
    
    // Delete the notification for the receiver (same as cancel request)
    $stmt = $pdo->prepare("
        DELETE FROM notifications 
        WHERE user_id = ? AND type = 'friend_request' 
        AND JSON_EXTRACT(data, '$.requester_id') = ?
    ");
    $stmt->execute([$user_id, $friend_request['requester_id']]);
    
    // Commit transaction
    $pdo->commit();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Friend request declined and removed successfully',
        'data' => [
            'request_id' => $request_id,
            'requester_id' => $friend_request['requester_id'],
            'decliner_id' => $user_id
        ]
    ]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    $pdo->rollBack();
    
    error_log("Error declining friend request: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
}
?>
