<?php
// Try different paths to find config.php
$config_paths = [
    '../../onboarding/config.php',  // From vocabworld directory
    '../../../onboarding/config.php'  // From charactermenu directory
];

$config_loaded = false;
foreach ($config_paths as $path) {
    if (file_exists($path)) {
        require_once $path;
        $config_loaded = true;
        break;
    }
}

if (!$config_loaded) {
    die('Error: Could not find config.php file');
}

class ShardManager {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Add shards to user's account (earn shards)
     */
    public function addShards($user_id, $amount, $description = 'Earned from gameplay', $game_type = 'vocabworld') {
        $transactionStarted = false;
        try {
            if (!$this->pdo->inTransaction()) {
                $this->pdo->beginTransaction();
                $transactionStarted = true;
            }
            
            // Get user info
            $stmt = $this->pdo->prepare("SELECT username FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();
            $username = $user['username'];
            
            // Get current shard balance
            $stmt = $this->pdo->prepare("SELECT * FROM user_shards WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $shard_account = $stmt->fetch();
            
            if (!$shard_account) {
                // Create new shard account
                $stmt = $this->pdo->prepare("INSERT INTO user_shards (user_id, username, current_shards, total_earned, total_spent) VALUES (?, ?, ?, ?, 0)");
                $stmt->execute([$user_id, $username, $amount, $amount]);
                
                // If this was a new account, $new_balance is just amount
                $new_balance = $amount;
            } else {
                // Update existing account
                $new_balance = $shard_account['current_shards'] + $amount;
                $new_total_earned = $shard_account['total_earned'] + $amount;
                
                $stmt = $this->pdo->prepare("UPDATE user_shards SET current_shards = ?, total_earned = ?, last_updated = CURRENT_TIMESTAMP WHERE user_id = ?");
                $stmt->execute([$new_balance, $new_total_earned, $user_id]);
            }
            
            // Record transaction
            $stmt = $this->pdo->prepare("INSERT INTO shard_transactions (user_id, username, transaction_type, amount, description, game_type) VALUES (?, ?, 'earned', ?, ?, ?)");
            $stmt->execute([$user_id, $username, $amount, $description, $game_type]);
            
            if ($transactionStarted) {
                $this->pdo->commit();
            }
            return ['success' => true, 'new_balance' => $new_balance];
            
        } catch (Exception $e) {
            if ($transactionStarted && $this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            // If we didn't start the transaction, we re-throw so the parent can handle rollback
            if (!$transactionStarted) {
                throw $e;
            }
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Deduct shards from user's account (spend shards)
     */
    public function deductShards($user_id, $amount, $description = 'Spent on purchase', $game_type = 'vocabworld', $related_id = null) {
        $transactionStarted = false;
        try {
            if (!$this->pdo->inTransaction()) {
                $this->pdo->beginTransaction();
                $transactionStarted = true;
            }
            
            // Get user info
            $stmt = $this->pdo->prepare("SELECT username FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();
            $username = $user['username'];
            
            // Get current shard balance
            $stmt = $this->pdo->prepare("SELECT * FROM user_shards WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $shard_account = $stmt->fetch();
            
            if (!$shard_account) {
                if ($transactionStarted) $this->pdo->rollBack();
                return ['success' => false, 'error' => 'No shard account found'];
            }
            
            if ($shard_account['current_shards'] < $amount) {
                if ($transactionStarted) $this->pdo->rollBack();
                return ['success' => false, 'error' => 'Insufficient shards'];
            }
            
            // Update account
            $new_balance = $shard_account['current_shards'] - $amount;
            $new_total_spent = $shard_account['total_spent'] + $amount;
            
            $stmt = $this->pdo->prepare("UPDATE user_shards SET current_shards = ?, total_spent = ?, last_updated = CURRENT_TIMESTAMP WHERE user_id = ?");
            $stmt->execute([$new_balance, $new_total_spent, $user_id]);
            
            // Record transaction
            $stmt = $this->pdo->prepare("INSERT INTO shard_transactions (user_id, username, transaction_type, amount, description, game_type, related_id) VALUES (?, ?, 'spent', ?, ?, ?, ?)");
            $stmt->execute([$user_id, $username, $amount, $description, $game_type, $related_id]);
            
            if ($transactionStarted) {
                $this->pdo->commit();
            }
            return ['success' => true, 'new_balance' => $new_balance];
            
        } catch (Exception $e) {
            if ($transactionStarted && $this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            if (!$transactionStarted) {
                throw $e;
            }
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Get user's current shard balance
     */
    public function getShardBalance($user_id) {
        $stmt = $this->pdo->prepare("SELECT * FROM user_shards WHERE user_id = ?");
        $stmt->execute([$user_id]);
        return $stmt->fetch();
    }
    
    /**
     * Get user's transaction history
     */
    public function getTransactionHistory($user_id, $limit = 50) {
        $stmt = $this->pdo->prepare("SELECT * FROM shard_transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT ?");
        $stmt->execute([$user_id, $limit]);
        return $stmt->fetchAll();
    }
    
    /**
     * Initialize shard account for new user
     */
    public function initializeShardAccount($user_id, $username, $initial_shards = 0) {
        try {
            $stmt = $this->pdo->prepare("INSERT INTO user_shards (user_id, username, current_shards, total_earned, total_spent) VALUES (?, ?, ?, ?, 0)");
            $stmt->execute([$user_id, $username, $initial_shards, $initial_shards]);
            
            if ($initial_shards > 0) {
                $stmt = $this->pdo->prepare("INSERT INTO shard_transactions (user_id, username, transaction_type, amount, description, game_type) VALUES (?, ?, 'earned', ?, 'Initial shards', 'vocabworld')");
                $stmt->execute([$user_id, $username, $initial_shards]);
            }
            
            return ['success' => true];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Ensure user has a shard account - creates one if they don't exist
     * This is the main function to call when a user enters vocabworld
     */
    public function ensureShardAccount($user_id) {
        try {
            // Check if user already has a shard account
            $stmt = $this->pdo->prepare("SELECT * FROM user_shards WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $existing_account = $stmt->fetch();
            
            if ($existing_account) {
                // User already has an account, return their balance
                return [
                    'success' => true, 
                    'account_exists' => true,
                    'shard_balance' => $existing_account['current_shards'],
                    'account_data' => $existing_account
                ];
            }
            
            // User doesn't have an account, create one
            $stmt = $this->pdo->prepare("SELECT username FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();
            
            if (!$user) {
                return ['success' => false, 'error' => 'User not found'];
            }
            
            $username = $user['username'];
            
            // Check if user has any existing progress with points
            $stmt = $this->pdo->prepare("SELECT * FROM game_progress WHERE user_id = ? AND game_type = 'vocabworld'");
            $stmt->execute([$user_id]);
            $progress = $stmt->fetch();
            
            $initial_shards = 0;
            if ($progress && $progress['unlocked_levels']) {
                $character_data = json_decode($progress['unlocked_levels'], true);
                $initial_shards = $character_data['current_points'] ?? 0;
            }
            
            // Create new shard account
            $result = $this->initializeShardAccount($user_id, $username, $initial_shards);
            
            if ($result['success']) {
                return [
                    'success' => true,
                    'account_exists' => false,
                    'shard_balance' => $initial_shards,
                    'message' => 'New shard account created'
                ];
            } else {
                return $result;
            }
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
?>
