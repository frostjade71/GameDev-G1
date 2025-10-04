<?php
// Enable error reporting but capture it
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Start output buffering to catch any errors
ob_start();

try {
    require_once '../../../onboarding/config.php';
    
    // Check if user is logged in
    requireLogin();
    
    $user_id = $_SESSION['user_id'];
    
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['characterType']) || !isset($input['price'])) {
        throw new Exception('Invalid request data');
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
        throw new Exception('Invalid character type');
    }
    
    $characterDef = $characterDefinitions[$characterType];
    
    // Check if user already owns this character
    $stmt = $pdo->prepare("SELECT * FROM character_ownership WHERE user_id = ? AND character_type = ?");
    $stmt->execute([$user_id, $characterType]);
    $ownership = $stmt->fetch();
    
    if ($ownership) {
        throw new Exception('Character already owned');
    }
    
    // Get user's current shard balance
    $stmt = $pdo->prepare("SELECT * FROM user_shards WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $shard_balance = $stmt->fetch();
    
    if (!$shard_balance) {
        throw new Exception('No shard account found');
    }
    
    // Check if user has enough shards
    if ($shard_balance['current_shards'] < $price) {
        throw new Exception('Not enough shards');
    }
    
    // Start transaction
    $pdo->beginTransaction();
    
    // Deduct shards directly
    $new_balance = $shard_balance['current_shards'] - $price;
    $new_total_spent = $shard_balance['total_spent'] + $price;
    
    $stmt = $pdo->prepare("UPDATE user_shards SET current_shards = ?, total_spent = ?, last_updated = CURRENT_TIMESTAMP WHERE user_id = ?");
    $stmt->execute([$new_balance, $new_total_spent, $user_id]);
    
    // Record transaction
    $stmt = $pdo->prepare("INSERT INTO shard_transactions (user_id, username, transaction_type, amount, description, game_type) VALUES (?, ?, 'spent', ?, ?, 'vocabworld')");
    $stmt->execute([$user_id, $_SESSION['username'] ?? 'Unknown', $price, "Purchased {$characterDef['name']} character"]);
    
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
    
    // Clear any output that might have been generated
    ob_clean();
    
    echo json_encode([
        'success' => true,
        'newShardCount' => $new_balance,
        'message' => 'Character purchased successfully'
    ]);
    
} catch (Exception $e) {
    // Rollback transaction if it was started
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    // Clear any output
    ob_clean();
    
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} catch (Error $e) {
    // Handle fatal errors
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    ob_clean();
    
    echo json_encode([
        'success' => false,
        'error' => 'Fatal error: ' . $e->getMessage()
    ]);
}
?>


