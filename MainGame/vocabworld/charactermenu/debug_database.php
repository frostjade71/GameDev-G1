<?php
require_once '../../../onboarding/config.php';

// Check if user is logged in
requireLogin();

$user_id = $_SESSION['user_id'];

echo "<h2>Database Debug Information</h2>";

// Check if tables exist
$tables = ['user_shards', 'character_ownership', 'shard_transactions', 'character_selections'];

foreach ($tables as $table) {
    try {
        $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$table]);
        $exists = $stmt->fetch();
        echo "<p><strong>$table:</strong> " . ($exists ? "✅ EXISTS" : "❌ MISSING") . "</p>";
    } catch (Exception $e) {
        echo "<p><strong>$table:</strong> ❌ ERROR: " . $e->getMessage() . "</p>";
    }
}

echo "<hr>";

// Check user's shard balance
try {
    $stmt = $pdo->prepare("SELECT * FROM user_shards WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $shard_balance = $stmt->fetch();
    
    if ($shard_balance) {
        echo "<h3>User Shard Balance:</h3>";
        echo "<p>Current Shards: " . $shard_balance['current_shards'] . "</p>";
        echo "<p>Total Earned: " . $shard_balance['total_earned'] . "</p>";
        echo "<p>Total Spent: " . $shard_balance['total_spent'] . "</p>";
        echo "<p>Last Updated: " . $shard_balance['last_updated'] . "</p>";
    } else {
        echo "<p>❌ No shard account found for user</p>";
    }
} catch (Exception $e) {
    echo "<p>❌ Error checking shard balance: " . $e->getMessage() . "</p>";
}

echo "<hr>";

// Check character ownership
try {
    $stmt = $pdo->prepare("SELECT * FROM character_ownership WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $owned_characters = $stmt->fetchAll();
    
    echo "<h3>Owned Characters:</h3>";
    if ($owned_characters) {
        foreach ($owned_characters as $char) {
            echo "<p>- " . $char['character_name'] . " (" . $char['character_type'] . ") - Purchased: " . $char['purchased_at'] . "</p>";
        }
    } else {
        echo "<p>No characters owned</p>";
    }
} catch (Exception $e) {
    echo "<p>❌ Error checking character ownership: " . $e->getMessage() . "</p>";
}

echo "<hr>";

// Check recent transactions
try {
    $stmt = $pdo->prepare("SELECT * FROM shard_transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
    $stmt->execute([$user_id]);
    $transactions = $stmt->fetchAll();
    
    echo "<h3>Recent Transactions:</h3>";
    if ($transactions) {
        foreach ($transactions as $tx) {
            echo "<p>" . $tx['created_at'] . " - " . $tx['transaction_type'] . " " . $tx['amount'] . " shards - " . $tx['description'] . "</p>";
        }
    } else {
        echo "<p>No transactions found</p>";
    }
} catch (Exception $e) {
    echo "<p>❌ Error checking transactions: " . $e->getMessage() . "</p>";
}

echo "<hr>";

// Test shard manager
echo "<h3>Testing Shard Manager:</h3>";
try {
    require_once 'shard_manager.php';
    $shardManager = new ShardManager($pdo);
    $balance = $shardManager->getShardBalance($user_id);
    
    if ($balance) {
        echo "<p>✅ Shard Manager working - Current balance: " . $balance['current_shards'] . "</p>";
    } else {
        echo "<p>❌ Shard Manager not working - No balance found</p>";
    }
} catch (Exception $e) {
    echo "<p>❌ Shard Manager error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='shop_characters.php'>Back to Shop</a></p>";
?>


