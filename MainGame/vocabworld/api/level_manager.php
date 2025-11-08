<?php
// List of possible config file locations to check
$possibleConfigPaths = [
    __DIR__ . '/../../onboarding/config.php',
    __DIR__ . '/../../../onboarding/config.php',
    '/home/wordweav/domains/wh1487294.ispot.cc/public_html/GameDev-G1/onboarding/config.php',
    dirname(dirname(__DIR__)) . '/onboarding/config.php'
];

// Find the config file
$configPath = '';
foreach ($possibleConfigPaths as $path) {
    if (file_exists($path)) {
        $configPath = $path;
        break;
    }
}

if (empty($configPath)) {
    die('Could not locate the config file. Tried the following paths:<br>' . 
        implode('<br>', array_map('htmlspecialchars', $possibleConfigPaths)));
}

require_once $configPath;

// Level Manager for VocabWorld
// Handles player level progression and experience points

class LevelManager {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Calculate experience needed for a specific level
     * Formula: 50 * level (linear growth)
     * Level 1->2: 50 EXP, Level 2->3: 100 EXP, Level 3->4: 150 EXP, etc.
     */
    public function getExpForLevel($level) {
        return 50 * $level;
    }
    
    /**
     * Get player's current level and experience
     */
    public function getPlayerLevel($user_id) {
        $stmt = $this->pdo->prepare("
            SELECT player_level, experience_points, total_experience_earned, total_monsters_defeated 
            FROM game_progress 
            WHERE user_id = ? AND game_type = 'vocabworld'
        ");
        $stmt->execute([$user_id]);
        $result = $stmt->fetch();
        
        if (!$result) {
            // Create initial progress if doesn't exist
            $this->initializeProgress($user_id);
            return [
                'level' => 1,
                'experience' => 0,
                'total_experience_earned' => 0,
                'monsters_defeated' => 0,
                'exp_to_next_level' => $this->getExpForLevel(1)
            ];
        }
        
        $current_level = $result['player_level'] ?? 1;
        
        return [
            'level' => $current_level,
            'experience' => $result['experience_points'] ?? 0,
            'total_experience_earned' => $result['total_experience_earned'] ?? 0,
            'monsters_defeated' => $result['total_monsters_defeated'] ?? 0,
            'exp_to_next_level' => $this->getExpForLevel($current_level)
        ];
    }
    
    /**
     * Initialize progress for new player
     */
    private function initializeProgress($user_id) {
        // Get username from users table
        $stmt = $this->pdo->prepare("SELECT username FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        $username = $user['username'] ?? null;
        
        $stmt = $this->pdo->prepare("
            INSERT INTO game_progress 
            (user_id, username, game_type, player_level, experience_points, total_experience_earned, total_monsters_defeated, last_played) 
            VALUES (?, ?, 'vocabworld', 1, 0, 0, 0, NOW())
            ON DUPLICATE KEY UPDATE 
            username = VALUES(username),
            player_level = COALESCE(player_level, 1),
            experience_points = COALESCE(experience_points, 0),
            total_experience_earned = COALESCE(total_experience_earned, 0),
            total_monsters_defeated = COALESCE(total_monsters_defeated, 0)
        
        ");
        $stmt->execute([$user_id, $username]);
    }
    
    /**
     * Add experience points and check for level up
     * Returns: ['leveled_up' => bool, 'new_level' => int, 'exp_gained' => int]
     */
    public function addExperience($user_id, $exp_amount) {
        $player_data = $this->getPlayerLevel($user_id);
        $current_level = $player_data['level'];
        $current_exp = $player_data['experience'];
        
        // Add experience
        $new_exp = $current_exp + $exp_amount;
        
        // Check for level up
        $leveled_up = false;
        $new_level = $current_level;
        
        while ($new_exp >= $this->getExpForLevel($new_level)) {
            $new_exp -= $this->getExpForLevel($new_level);
            $new_level++;
            $leveled_up = true;
        }
        
        // Update database - add exp_amount to total_experience_earned
        $stmt = $this->pdo->prepare("
            UPDATE game_progress 
            SET 
                player_level = ?, 
                experience_points = ?, 
                total_experience_earned = total_experience_earned + ?,
                updated_at = NOW() 
            WHERE user_id = ? AND game_type = 'vocabworld'
        ");
        $stmt->execute([$new_level, $new_exp, $exp_amount, $user_id]);
        
        return [
            'leveled_up' => $leveled_up,
            'new_level' => $new_level,
            'old_level' => $current_level,
            'exp_gained' => $exp_amount,
            'current_exp' => $new_exp,
            'exp_to_next_level' => $this->getExpForLevel($new_level)
        ];
    }
    
    /**
     * Increment monster defeat count
     */
    public function incrementMonsterCount($user_id) {
        $stmt = $this->pdo->prepare("
            UPDATE game_progress 
            SET total_monsters_defeated = total_monsters_defeated + 1 
            WHERE user_id = ? AND game_type = 'vocabworld'
        ");
        $stmt->execute([$user_id]);
    }
    
    /**
     * Calculate experience reward based on answer correctness
     */
    public function calculateExpReward($is_correct) {
        if ($is_correct) {
            return 25; // 25 EXP for correct answer
        } else {
            return 5; // 5 EXP for wrong answer (participation reward)
        }
    }
}
?>
