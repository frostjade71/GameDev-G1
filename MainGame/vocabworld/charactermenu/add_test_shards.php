<?php
require_once '../../../onboarding/config.php';

// Check if user is logged in
requireLogin();

$user_id = $_SESSION['user_id'];

// Get user info
$stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
$username = $user['username'];

echo "<h2>Add Test Shards</h2>";

try {
    require_once 'shard_manager.php';
    $shardManager = new ShardManager($pdo);
    
    // Check current balance
    $current_balance = $shardManager->getShardBalance($user_id);
    
    if (!$current_balance) {
        // Initialize shard account
        $result = $shardManager->initializeShardAccount($user_id, $username, 0);
        if (!$result['success']) {
            echo "<p>❌ Error initializing shard account: " . $result['error'] . "</p>";
            exit;
        }
        echo "<p>✅ Shard account initialized</p>";
    }
    
    // Add 50 test shards
    $result = $shardManager->addShards($user_id, 50, 'Test shards for debugging', 'vocabworld');
    
    if ($result['success']) {
        echo "<p>✅ Added 50 test shards. New balance: " . $result['new_balance'] . "</p>";
    } else {
        echo "<p>❌ Error adding shards: " . $result['error'] . "</p>";
    }
    
    // Show updated balance
    $new_balance = $shardManager->getShardBalance($user_id);
    if ($new_balance) {
        echo "<p>Current Shards: " . $new_balance['current_shards'] . "</p>";
        echo "<p>Total Earned: " . $new_balance['total_earned'] . "</p>";
        echo "<p>Total Spent: " . $new_balance['total_spent'] . "</p>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='debug_database.php'>Check Database</a> | <a href='shop_characters.php'>Go to Shop</a></p>";
?>


