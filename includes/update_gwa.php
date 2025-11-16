<?php
require_once 'config.php';

/**
 * Updates the GWA for a specific user and game type
 * 
 * @param PDO $pdo Database connection
 * @param int $user_id User ID
 * @param string $game_type Game type (e.g., 'vocabworld')
 * @return bool True on success, false on failure
 */
function updateUserGWA($pdo, $user_id, $game_type) {
    try {
        // Calculate the new GWA
        $stmt = $pdo->prepare("
            SELECT AVG(score) as gwa_score
            FROM game_scores 
            WHERE user_id = ? AND game_type = ?
        ");
        $stmt->execute([$user_id, $game_type]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $gwa = $result['gwa_score'] ? round($result['gwa_score'], 2) : 0;
        
        // Check if a record already exists
        $stmt = $pdo->prepare("
            SELECT id FROM user_gwa 
            WHERE user_id = ? AND game_type = ?
        ");
        $stmt->execute([$user_id, $game_type]);
        
        if ($stmt->rowCount() > 0) {
            // Update existing record
            $stmt = $pdo->prepare("
                UPDATE user_gwa 
                SET gwa = ?, updated_at = NOW()
                WHERE user_id = ? AND game_type = ?
            ");
            return $stmt->execute([$gwa, $user_id, $game_type]);
        } else {
            // Insert new record
            $stmt = $pdo->prepare("
                INSERT INTO user_gwa (user_id, game_type, gwa, created_at, updated_at)
                VALUES (?, ?, ?, NOW(), NOW())
            ");
            return $stmt->execute([$user_id, $game_type, $gwa]);
        }
    } catch (PDOException $e) {
        error_log("Error updating GWA: " . $e->getMessage());
        return false;
    }
}

// Handle direct API calls (if needed)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id']) && isset($_POST['game_type'])) {
    header('Content-Type: application/json');
    
    try {
        $success = updateUserGWA($pdo, $_POST['user_id'], $_POST['game_type']);
        echo json_encode(['success' => $success]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit();
}
?>
