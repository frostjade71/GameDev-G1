<?php
require_once '../../../onboarding/config.php';

class ShardManager {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Add shards to user's account (earn shards)
     */
    public function addShards($user_id, $amount, $description = 'Earned from gameplay', $game_type = 'vocabworld') {
        try {
            $this->pdo->beginTransaction();
            
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
            
            $this->pdo->commit();
            return ['success' => true, 'new_balance' => $new_balance ?? $amount];
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Deduct shards from user's account (spend shards)
     */
    public function deductShards($user_id, $amount, $description = 'Spent on purchase', $game_type = 'vocabworld', $related_id = null) {
        try {
            $this->pdo->beginTransaction();
            
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
                $this->pdo->rollBack();
                return ['success' => false, 'error' => 'No shard account found'];
            }
            
            if ($shard_account['current_shards'] < $amount) {
                $this->pdo->rollBack();
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
            
            $this->pdo->commit();
            return ['success' => true, 'new_balance' => $new_balance];
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
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
}
?>
