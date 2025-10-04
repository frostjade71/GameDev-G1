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

if (!$input || !isset($input['friend_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit();
}

$user_id = $_SESSION['user_id'];
$friend_id = intval($input['friend_id']);

try {
    // Start transaction
    $pdo->beginTransaction();
    
    // Verify the friendship exists
    $stmt = $pdo->prepare("
        SELECT id FROM friends 
        WHERE (user1_id = ? AND user2_id = ?) OR (user1_id = ? AND user2_id = ?)
    ");
    $stmt->execute([$user_id, $friend_id, $friend_id, $user_id]);
    $friendship = $stmt->fetch();
    
    if (!$friendship) {
        $pdo->rollBack();
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'in progress']);
        exit();
    }
    
    // Delete the friendship
    $stmt = $pdo->prepare("DELETE FROM friends WHERE id = ?");
    $stmt->execute([$friendship['id']]);
    
    // Create notification for the removed friend (optional)
    $stmt = $pdo->prepare("
        INSERT INTO notifications (user_id, type, message, data) 
        VALUES (?, 'friend_removed', ?, ?)
    ");
    $message = "You are no longer friends with this user.";
    $data = json_encode(['remover_id' => $user_id, 'friendship_id' => $friendship['id']]);
    $stmt->execute([$friend_id, $message, $data]);
    
    // Commit transaction
    $pdo->commit();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Friend removed successfully',
        'data' => [
            'friendship_id' => $friendship['id'],
            'friend_id' => $friend_id,
            'remover_id' => $user_id
        ]
    ]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    $pdo->rollBack();
    
    error_log("Error removing friend: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
}
?>
