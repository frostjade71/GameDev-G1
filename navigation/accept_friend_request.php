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

if (!$input || !isset($input['request_id']) || !isset($input['requester_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit();
}

$user_id = $_SESSION['user_id'];
$request_id = intval($input['request_id']);
$requester_id = intval($input['requester_id']);

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
                'requester_id' => $requester_id,
                'accepter_id' => $user_id,
                'already_processed' => true
            ]
        ]);
        exit();
    }
    
    // Verify the requester_id matches
    if ($friend_request['requester_id'] != $requester_id) {
        $pdo->rollBack();
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid requester ID']);
        exit();
    }
    
    // Add friendship to friends table (ensure consistent ordering)
    $user1_id = min($user_id, $requester_id);
    $user2_id = max($user_id, $requester_id);
    
    $stmt = $pdo->prepare("
        INSERT INTO friends (user1_id, user2_id) 
        VALUES (?, ?)
        ON DUPLICATE KEY UPDATE created_at = CURRENT_TIMESTAMP
    ");
    $stmt->execute([$user1_id, $user2_id]);
    
    // Delete the friend request after creating friendship
    $stmt = $pdo->prepare("DELETE FROM friend_requests WHERE id = ?");
    $stmt->execute([$request_id]);
    
    // Create notification for the requester
    $stmt = $pdo->prepare("
        INSERT INTO notifications (user_id, type, message, data) 
        VALUES (?, 'friend_request_accepted', ?, ?)
    ");
    $message = "Your friend request has been accepted!";
    $data = json_encode(['accepter_id' => $user_id, 'request_id' => $request_id]);
    $stmt->execute([$requester_id, $message, $data]);
    
    // AUDIT LOG: Accepted Friend Request
    if (!isset($_SESSION['username'])) {
        $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $u = $stmt->fetch();
        $_SESSION['username'] = $u['username'];
    }
    require_once '../includes/Logger.php';
    logAudit('Accepted Friend Request', $user_id, $_SESSION['username'], "Accepted friend request from User ID: $requester_id");
    
    
    // Commit transaction
    $pdo->commit();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Friend added successfully',
        'data' => [
            'request_id' => $request_id,
            'requester_id' => $requester_id,
            'accepter_id' => $user_id
        ]
    ]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    $pdo->rollBack();
    
    error_log("Error accepting friend request: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
}
?>
