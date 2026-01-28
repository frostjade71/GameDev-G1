<?php
// Prevent any HTML output
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');

try {
    require_once '../../../onboarding/config.php';

    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Not logged in');
    }

    $user_id = $_SESSION['user_id'];

    // Get JSON input
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (!isset($data['shards_to_buy'])) {
        throw new Exception('Invalid request: Missing shards_to_buy');
    }

    $shards_to_buy = (int)$data['shards_to_buy'];
    if ($shards_to_buy <= 0) {
        throw new Exception('Invalid amount');
    }

    // Conversion rate: 20 Essence = 1 Shard
    $essence_cost = $shards_to_buy * 20;

    // Managers
    require_once '../api/essence_manager.php';
    require_once '../shard_manager.php';

    // Verify PDO exists
    if (!isset($pdo)) {
        throw new Exception('Database connection failed');
    }

    $essenceManager = new EssenceManager($pdo);
    $shardManager = new ShardManager($pdo);

    // Check if user has enough essence
    $current_essence = $essenceManager->getEssence($user_id);

    if ($current_essence < $essence_cost) {
        throw new Exception('Insufficient Essence');
    }

    $pdo->beginTransaction();

    // 1. Deduct Essence
    // addEssence with negative amount works as deduction
    $essenceManager->addEssence($user_id, -$essence_cost);

    // 2. Add Shards
    $shard_result = $shardManager->addShards($user_id, $shards_to_buy, 'Converted from Essence', 'vocabworld');
    
    if (!$shard_result['success']) {
        throw new Exception($shard_result['error']);
    }

    $pdo->commit();
    
    echo json_encode([
        'success' => true, 
        'new_essence' => $current_essence - $essence_cost,
        'new_shards' => $shard_result['new_balance']
    ]);

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Conversion Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} catch (Error $e) {
    // Catch fatal errors
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Conversion Fatal Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Server error occurred']);
}
?>
