<?php
require_once '../onboarding/config.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

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

if (!$input || !isset($input['receiver_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit();
}

$user_id = $_SESSION['user_id'];
$receiver_id = intval($input['receiver_id']);

try {
    // Start transaction
    $pdo->beginTransaction();
    
    // Find the friend request
    $stmt = $pdo->prepare("
        SELECT id, requester_id, receiver_id, status 
        FROM friend_requests 
        WHERE requester_id = ? AND receiver_id = ? AND status = 'pending'
    ");
    $stmt->execute([$user_id, $receiver_id]);
    $friend_request = $stmt->fetch();
    
    if (!$friend_request) {
        $pdo->rollBack();
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'in progress']);
        exit();
    }
    
    // Delete the friend request
    $stmt = $pdo->prepare("DELETE FROM friend_requests WHERE id = ?");
    $stmt->execute([$friend_request['id']]);
    
    // Delete the notification for the receiver
    $stmt = $pdo->prepare("
        DELETE FROM notifications 
        WHERE user_id = ? AND type = 'friend_request' 
        AND JSON_EXTRACT(data, '$.requester_id') = ?
    ");
    $stmt->execute([$receiver_id, $user_id]);
    
    // Commit transaction
    $pdo->commit();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Friend request cancelled successfully',
        'data' => [
            'request_id' => $friend_request['id'],
            'receiver_id' => $receiver_id
        ]
    ]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    $pdo->rollBack();
    
    error_log("Error cancelling friend request: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
}
?>
