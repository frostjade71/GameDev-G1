<?php
require_once '../../../onboarding/config.php';

// Check if user is logged in
requireLogin();

$user_id = $_SESSION['user_id'];

echo "<h2>Database Connection Test</h2>";

try {
    // Test basic connection
    echo "<p>✅ Database connection successful</p>";
    
    // Test user_shards table
    $stmt = $pdo->prepare("SELECT * FROM user_shards WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $shard_balance = $stmt->fetch();
    
    if ($shard_balance) {
        echo "<p>✅ User shard account found</p>";
        echo "<p>Current Shards: " . $shard_balance['current_shards'] . "</p>";
        echo "<p>Total Spent: " . $shard_balance['total_spent'] . "</p>";
    } else {
        echo "<p>❌ No shard account found for user</p>";
    }
    
    // Test character_ownership table
    $stmt = $pdo->prepare("SELECT * FROM character_ownership WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $owned_characters = $stmt->fetchAll();
    
    echo "<p>✅ Character ownership table accessible</p>";
    echo "<p>Owned characters: " . count($owned_characters) . "</p>";
    
    // Test shard_transactions table
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM shard_transactions WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $transaction_count = $stmt->fetch();
    
    echo "<p>✅ Shard transactions table accessible</p>";
    echo "<p>Transaction count: " . $transaction_count['count'] . "</p>";
    
    // Test if Amber is already owned
    $stmt = $pdo->prepare("SELECT * FROM character_ownership WHERE user_id = ? AND character_type = 'amber'");
    $stmt->execute([$user_id]);
    $amber_owned = $stmt->fetch();
    
    if ($amber_owned) {
        echo "<p>⚠️ Amber character is already owned</p>";
    } else {
        echo "<p>✅ Amber character is available for purchase</p>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Database error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='shop_characters.php'>Go to Shop</a> | <a href='test_direct_purchase.php'>Test Direct Purchase</a></p>";
?>


