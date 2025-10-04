<?php
require_once '../../onboarding/config.php';
require_once 'shard_manager.php';

// Check if user is logged in
requireLogin();

$user_id = $_SESSION['user_id'];
$shardManager = new ShardManager($pdo);

// Add 50 shards for testing
$result = $shardManager->addShards($user_id, 50, 'Test shards for development', 'vocabworld');

if ($result['success']) {
    echo "Successfully added 50 shards! New balance: " . $result['new_balance'];
} else {
    echo "Error: " . $result['error'];
}
?>
