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

class EssenceManager {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->ensureEssenceTableExists();
    }

    private function ensureEssenceTableExists() {
        // First create the table if it doesn't exist
        $sql = "CREATE TABLE IF NOT EXISTS user_essence (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            username VARCHAR(50) NOT NULL,
            essence_amount INT DEFAULT 0,
            last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (username) REFERENCES users(username) ON UPDATE CASCADE ON DELETE CASCADE,
            UNIQUE KEY unique_user (user_id)
        )";
        $this->pdo->exec($sql);
    }

    public function getEssence($user_id) {
        $stmt = $this->pdo->prepare("SELECT essence_amount FROM user_essence WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['essence_amount'] : 0;
    }

    public function addEssence($user_id, $amount) {
        // Get the username first
        $stmt = $this->pdo->prepare("SELECT username FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            throw new Exception("User not found");
        }
        
        $username = $user['username'];
        
        $stmt = $this->pdo->prepare("
            INSERT INTO user_essence (user_id, username, essence_amount) 
            VALUES (?, ?, ?) 
            ON DUPLICATE KEY UPDATE 
                essence_amount = essence_amount + VALUES(essence_amount),
                username = VALUES(username)");
        return $stmt->execute([$user_id, $username, $amount]);
    }

    public function updateGWA($user_id, $score) {
        // Update the user's GWA in their profile
        $stmt = $this->pdo->prepare("
            UPDATE users 
            SET gwa = (
                SELECT AVG(score) 
                FROM game_scores 
                WHERE user_id = ? 
                AND game_type = 'vocabworld' 
                AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            )
            WHERE id = ?");
        return $stmt->execute([$user_id, $user_id]);
    }
}
?>