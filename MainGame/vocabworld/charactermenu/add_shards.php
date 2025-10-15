<?php
require_once '../../../onboarding/config.php';
require_once 'shard_manager.php';

// Check if user is logged in
requireLogin();

// Get user information
$user_id = $_SESSION['user_id'];
$shardManager = new ShardManager($pdo);

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['amount'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid request data']);
    exit;
}

$amount = (int)$input['amount'];
$description = $input['description'] ?? 'Earned from gameplay';
$game_type = $input['game_type'] ?? 'vocabworld';

if ($amount <= 0) {
    echo json_encode(['success' => false, 'error' => 'Amount must be positive']);
    exit;
}

// Add shards to user's account
$result = $shardManager->addShards($user_id, $amount, $description, $game_type);

if ($result['success']) {
    echo json_encode([
        'success' => true,
        'new_balance' => $result['new_balance'],
        'message' => "Added {$amount} shards successfully"
    ]);
} else {
    echo json_encode([
        'success' => false,
        'error' => $result['error']
    ]);
}
?>
