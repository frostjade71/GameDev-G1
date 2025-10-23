<?php
require_once '../../../onboarding/config.php';
require_once '../api/essence_manager.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

// Get the POST data
$data = json_decode(file_get_contents('php://input'), true);
if (!isset($data['amount'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing amount parameter']);
    exit;
}

$user_id = $_SESSION['user_id'];
$amount = intval($data['amount']);

// Initialize essence manager
$essenceManager = new EssenceManager($pdo);

// Update essence
if ($essenceManager->addEssence($user_id, $amount)) {
    echo json_encode(['success' => true, 'amount' => $amount]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to update essence']);
}
?>