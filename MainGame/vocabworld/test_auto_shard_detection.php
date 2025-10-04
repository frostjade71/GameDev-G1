<?php
require_once '../../onboarding/config.php';

// Check if user is logged in
requireLogin();

$user_id = $_SESSION['user_id'];

echo "<h2>VocabWorld Automatic Shard Detection Test</h2>";
echo "<p>Testing for User ID: $user_id</p>";

try {
    // Test the new automatic shard detection system
    require_once 'shard_manager.php';
    $shardManager = new ShardManager($pdo);
    
    echo "<h3>Testing ensureShardAccount() function:</h3>";
    $result = $shardManager->ensureShardAccount($user_id);
    
    if ($result['success']) {
        echo "<p style='color: green;'>✅ Shard account detection successful!</p>";
        echo "<p><strong>Account exists:</strong> " . ($result['account_exists'] ? 'Yes' : 'No') . "</p>";
        echo "<p><strong>Shard balance:</strong> " . $result['shard_balance'] . "</p>";
        
        if (!$result['account_exists']) {
            echo "<p style='color: blue;'>ℹ️ New shard account was created automatically</p>";
        }
        
        if (isset($result['message'])) {
            echo "<p><strong>Message:</strong> " . $result['message'] . "</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ Shard account detection failed!</p>";
        echo "<p><strong>Error:</strong> " . $result['error'] . "</p>";
    }
    
    // Test getting shard balance
    echo "<h3>Testing getShardBalance() function:</h3>";
    $balance = $shardManager->getShardBalance($user_id);
    
    if ($balance) {
        echo "<p style='color: green;'>✅ Shard balance retrieved successfully!</p>";
        echo "<p><strong>Current shards:</strong> " . $balance['current_shards'] . "</p>";
        echo "<p><strong>Total earned:</strong> " . $balance['total_earned'] . "</p>";
        echo "<p><strong>Total spent:</strong> " . $balance['total_spent'] . "</p>";
        echo "<p><strong>Last updated:</strong> " . $balance['last_updated'] . "</p>";
    } else {
        echo "<p style='color: red;'>❌ No shard balance found</p>";
    }
    
    // Test transaction history
    echo "<h3>Testing transaction history:</h3>";
    $transactions = $shardManager->getTransactionHistory($user_id, 5);
    
    if (count($transactions) > 0) {
        echo "<p style='color: green;'>✅ Transaction history retrieved successfully!</p>";
        echo "<p><strong>Recent transactions:</strong></p>";
        echo "<ul>";
        foreach ($transactions as $transaction) {
            $type = $transaction['transaction_type'] == 'earned' ? 'Earned' : 'Spent';
            $color = $transaction['transaction_type'] == 'earned' ? 'green' : 'red';
            echo "<li style='color: $color;'>$type {$transaction['amount']} shards - {$transaction['description']} ({$transaction['created_at']})</li>";
        }
        echo "</ul>";
    } else {
        echo "<p style='color: orange;'>⚠️ No transaction history found</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='index.php'>← Back to VocabWorld</a></p>";
?>




