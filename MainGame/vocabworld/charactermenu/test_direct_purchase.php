<?php
require_once '../../../onboarding/config.php';

// Check if user is logged in
requireLogin();

$user_id = $_SESSION['user_id'];

echo "<h2>Direct Purchase Test</h2>";

// Simulate the purchase data
$test_data = [
    'characterType' => 'amber',
    'price' => 20
];

echo "<h3>Test Data:</h3>";
echo "<pre>" . print_r($test_data, true) . "</pre>";

// Test the purchase logic directly
try {
    require_once 'shard_manager.php';
    $shardManager = new ShardManager($pdo);
    
    // Check current balance
    $shard_balance = $shardManager->getShardBalance($user_id);
    echo "<h3>Current Shard Balance:</h3>";
    if ($shard_balance) {
        echo "<p>Current Shards: " . $shard_balance['current_shards'] . "</p>";
        echo "<p>Total Earned: " . $shard_balance['total_earned'] . "</p>";
        echo "<p>Total Spent: " . $shard_balance['total_spent'] . "</p>";
    } else {
        echo "<p>❌ No shard account found</p>";
        exit;
    }
    
    // Check if user already owns Amber
    $stmt = $pdo->prepare("SELECT * FROM character_ownership WHERE user_id = ? AND character_type = ?");
    $stmt->execute([$user_id, 'amber']);
    $ownership = $stmt->fetch();
    
    if ($ownership) {
        echo "<p>❌ User already owns Amber character</p>";
        exit;
    }
    
    // Test the purchase
    $characterType = 'amber';
    $price = 20;
    
    if ($shard_balance['current_shards'] < $price) {
        echo "<p>❌ Not enough shards. Need: $price, Have: " . $shard_balance['current_shards'] . "</p>";
        exit;
    }
    
    echo "<h3>Attempting Purchase...</h3>";
    
    $pdo->beginTransaction();
    
    // Deduct shards
    $deduct_result = $shardManager->deductShards(
        $user_id, 
        $price, 
        "Purchased Amber character", 
        'vocabworld'
    );
    
    if (!$deduct_result['success']) {
        $pdo->rollBack();
        echo "<p>❌ Failed to deduct shards: " . $deduct_result['error'] . "</p>";
        exit;
    }
    
    echo "<p>✅ Shards deducted successfully. New balance: " . $deduct_result['new_balance'] . "</p>";
    
    // Get user's username
    $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    $username = $user['username'];
    
    // Add character ownership
    $stmt = $pdo->prepare("INSERT INTO character_ownership (user_id, username, character_type, character_name, character_image_path, purchased_at) VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->execute([$user_id, $username, 'amber', 'Amber', '../assets/characters/amber_char/amber.png']);
    
    $ownership_id = $pdo->lastInsertId();
    echo "<p>✅ Character ownership added. ID: $ownership_id</p>";
    
    // Update transaction reference
    $stmt = $pdo->prepare("UPDATE shard_transactions SET related_id = ? WHERE user_id = ? AND transaction_type = 'spent' AND amount = ? ORDER BY created_at DESC LIMIT 1");
    $stmt->execute([$ownership_id, $user_id, $price]);
    
    $pdo->commit();
    
    echo "<p>✅ Purchase completed successfully!</p>";
    
    // Show updated balance
    $new_balance = $shardManager->getShardBalance($user_id);
    if ($new_balance) {
        echo "<h3>Updated Balance:</h3>";
        echo "<p>Current Shards: " . $new_balance['current_shards'] . "</p>";
        echo "<p>Total Spent: " . $new_balance['total_spent'] . "</p>";
    }
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='debug_database.php'>Check Database</a> | <a href='shop_characters.php'>Go to Shop</a></p>";
?>


