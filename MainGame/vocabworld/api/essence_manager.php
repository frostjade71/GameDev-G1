<?php
require_once 'C:/xampp/htdocs/GameDev-G1/onboarding/config.php';

class EssenceManager {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->ensureEssenceTableExists();
    }

    private function ensureEssenceTableExists() {
        $sql = "CREATE TABLE IF NOT EXISTS user_essence (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            essence_amount INT DEFAULT 0,
            last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id)
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
        $stmt = $this->pdo->prepare("
            INSERT INTO user_essence (user_id, essence_amount) 
            VALUES (?, ?) 
            ON DUPLICATE KEY UPDATE essence_amount = essence_amount + ?");
        return $stmt->execute([$user_id, $amount, $amount]);
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