<?php
require_once '../onboarding/config.php';

// Check if user is logged in
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];

try {
    // Start transaction
    $pdo->beginTransaction();

    // Get all pending friend requests for the user
    $stmt = $pdo->prepare("SELECT id FROM friend_requests WHERE receiver_id = ? AND status = 'pending'");
    $stmt->execute([$user_id]);
    $requests = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (empty($requests)) {
        echo json_encode(['success' => false, 'message' => 'No pending requests found']);
        exit();
    }

    // Update all pending requests to declined
    $stmt = $pdo->prepare("UPDATE friend_requests SET status = 'declined' WHERE receiver_id = ? AND status = 'pending'");
    $stmt->execute([$user_id]);

    // Commit transaction
    $pdo->commit();

    echo json_encode(['success' => true, 'message' => 'All friend requests declined successfully']);
} catch (Exception $e) {
    // Rollback transaction on error
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'An error occurred while declining requests']);
}
?>