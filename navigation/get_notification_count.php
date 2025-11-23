<?php
require_once '../onboarding/config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

$user_id = $_SESSION['user_id'];

try {
    // Get count of pending friend requests for the current user
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count
        FROM friend_requests 
        WHERE receiver_id = ? AND status = 'pending'
    ");
    $stmt->execute([$user_id]);
    $friend_requests_count = $stmt->fetch()['count'];
    
    // Get count of crescent notifications for the current user
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count
        FROM notifications
        WHERE user_id = ? AND type = 'cresent_received'
    ");
    $stmt->execute([$user_id]);
    $cresent_notifications_count = $stmt->fetch()['count'];
    
    // Total notification count
    $notification_count = $friend_requests_count + $cresent_notifications_count;
    
    echo json_encode([
        'success' => true,
        'count' => $notification_count
    ]);
    
} catch (Exception $e) {
    error_log("Error getting notification count: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
}
?>
