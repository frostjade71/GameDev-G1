<?php
// Disable error display to prevent HTML output
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once '../../../onboarding/config.php';
require_once 'shard_manager.php';

// Check if user is logged in
requireLogin();

// Get user information
$user_id = $_SESSION['user_id'];
$shardManager = new ShardManager($pdo);

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['characterType']) || !isset($input['price'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid request data']);
    exit;
}

$characterType = $input['characterType'];
$price = (int)$input['price'];

// Character definitions
$characterDefinitions = [
    'amber' => [
        'name' => 'Amber',
        'image_path' => '../assets/characters/amber_char/amber.png',
        'price' => 20
    ]
];

// Validate character type
if (!isset($characterDefinitions[$characterType])) {
    echo json_encode(['success' => false, 'error' => 'Invalid character type']);
    exit;
}

$characterDef = $characterDefinitions[$characterType];

// Get user's current shard balance
$shard_balance = $shardManager->getShardBalance($user_id);
if (!$shard_balance) {
    echo json_encode(['success' => false, 'error' => 'No shard account found']);
    exit;
}

// Check if user has enough shards
if ($shard_balance['current_shards'] < $price) {
    echo json_encode(['success' => false, 'error' => 'Not enough shards']);
    exit;
}

// Check if user already owns this character
$stmt = $pdo->prepare("SELECT * FROM character_ownership WHERE user_id = ? AND character_type = ?");
$stmt->execute([$user_id, $characterType]);
$ownership = $stmt->fetch();

if ($ownership) {
    echo json_encode(['success' => false, 'error' => 'Character already owned']);
    exit;
}

try {
    $pdo->beginTransaction();
    
    // Deduct shards using shard manager
    $deduct_result = $shardManager->deductShards(
        $user_id, 
        $price, 
        "Purchased {$characterDef['name']} character", 
        'vocabworld'
    );
    
    if (!$deduct_result['success']) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'error' => $deduct_result['error']]);
        exit;
    }
    
    // Get user's username
    $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    $username = $user['username'];
    
    // Add character ownership
    $stmt = $pdo->prepare("INSERT INTO character_ownership (user_id, username, character_type, character_name, character_image_path, purchased_at) VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->execute([$user_id, $username, $characterType, $characterDef['name'], $characterDef['image_path']]);
    
    // Get the ownership ID for transaction reference
    $ownership_id = $pdo->lastInsertId();
    
    // Update the transaction with the ownership reference
    $stmt = $pdo->prepare("UPDATE shard_transactions SET related_id = ? WHERE user_id = ? AND transaction_type = 'spent' AND amount = ? ORDER BY created_at DESC LIMIT 1");
    $stmt->execute([$ownership_id, $user_id, $price]);
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'newShardCount' => $deduct_result['new_balance'],
        'message' => 'Character purchased successfully'
    ]);
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>
