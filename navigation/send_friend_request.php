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

if (!$input || !isset($input['receiver_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit();
}

$user_id = $_SESSION['user_id'];
$receiver_id = intval($input['receiver_id']);

// Check if trying to send request to self
if ($user_id === $receiver_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Cannot send friend request to yourself']);
    exit();
}

try {
    // Check if receiver exists
    $stmt = $pdo->prepare("SELECT id, username FROM users WHERE id = ?");
    $stmt->execute([$receiver_id]);
    $receiver = $stmt->fetch();
    
    if (!$receiver) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit();
    }
    
    // Check if there's already a pending request between these users
    $stmt = $pdo->prepare("
        SELECT id, status 
        FROM friend_requests 
        WHERE (requester_id = ? AND receiver_id = ?) OR (requester_id = ? AND receiver_id = ?)
    ");
    $stmt->execute([$user_id, $receiver_id, $receiver_id, $user_id]);
    $existing_request = $stmt->fetch();
    
    if ($existing_request) {
        if ($existing_request['status'] === 'pending') {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Friend request already exists']);
            exit();
        } elseif ($existing_request['status'] === 'accepted') {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'You are already friends with this user']);
            exit();
        }
    }
    
    // Check if they are already friends
    $user1_id = min($user_id, $receiver_id);
    $user2_id = max($user_id, $receiver_id);
    
    $stmt = $pdo->prepare("SELECT id FROM friends WHERE user1_id = ? AND user2_id = ?");
    $stmt->execute([$user1_id, $user2_id]);
    $existing_friendship = $stmt->fetch();
    
    if ($existing_friendship) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'You are already friends with this user']);
        exit();
    }
    
    // Start transaction
    $pdo->beginTransaction();
    
    // Insert friend request
    $stmt = $pdo->prepare("
        INSERT INTO friend_requests (requester_id, receiver_id, status) 
        VALUES (?, ?, 'pending')
    ");
    $stmt->execute([$user_id, $receiver_id]);
    $request_id = $pdo->lastInsertId();
    
    // Create notification for the receiver
    $stmt = $pdo->prepare("
        INSERT INTO notifications (user_id, type, message, data) 
        VALUES (?, 'friend_request', ?, ?)
    ");
    $message = "You have a new friend request!";
    $data = json_encode(['requester_id' => $user_id, 'request_id' => $request_id]);
    $stmt->execute([$receiver_id, $message, $data]);
    
    // Commit transaction
    $pdo->commit();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Friend request sent successfully',
        'data' => [
            'request_id' => $request_id,
            'receiver_id' => $receiver_id,
            'receiver_username' => $receiver['username']
        ]
    ]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    $pdo->rollBack();
    
    error_log("Error sending friend request: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
}
?>
