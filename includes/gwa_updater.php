<?php
/**
 * GWA Updater Utility
 * Handles automatic updating of user GWA scores in the database
 */

require_once __DIR__ . '/../onboarding/config.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

/**
 * Updates the GWA for a specific user and game type
 * 
 * @param PDO $pdo Database connection
 * @param int $user_id User ID
 * @param string $game_type Game type (e.g., 'vocabworld', 'grammarheroes')
 * @return bool True on success, false on failure
 */
function updateUserGWA($pdo, $user_id, $game_type) {
    // Debug log
    error_log("Updating GWA for user $user_id, game type: $game_type");
    
    // Get the player's total level for this game type
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(player_level), 1) as total_level
        FROM game_progress 
        WHERE user_id = ? AND game_type = ?
    ");
    $stmt->execute([$user_id, $game_type]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$result || !isset($result['total_level'])) {
        error_log("No game progress found for user $user_id, game type: $game_type");
        return false;
    }
    
    // Calculate GWA using the same formula as in profile.php
    $gwa = $result['total_level'] * 1.5;
    error_log("Calculated GWA: $gwa (from total_level: {$result['total_level']})");
    
    // Check if a record already exists
    try {
        $checkStmt = $pdo->prepare("
            SELECT id, gwa as current_gwa FROM user_gwa 
            WHERE user_id = ? AND game_type = ?
        ");
        $checkStmt->execute([$user_id, $game_type]);
        $currentGWA = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($currentGWA) {
            error_log("Existing GWA record found. Current GWA: " . $currentGWA['current_gwa']);
        } else {
            error_log("No existing GWA record found, will create new one");
        }
    
    } catch (PDOException $e) {
        error_log("Error checking for existing GWA: " . $e->getMessage());
        return false;
    }
    
    if (isset($currentGWA) && $currentGWA) {
        // Update existing record
        try {
            $updateStmt = $pdo->prepare("
                UPDATE user_gwa 
                SET gwa = ?, updated_at = CURRENT_TIMESTAMP 
                WHERE user_id = ? AND game_type = ?
            ");
            $success = $updateStmt->execute([$gwa, $user_id, $game_type]);
            error_log("Updated GWA record: " . ($success ? 'success' : 'failed'));
            return $success;
        } catch (PDOException $e) {
            error_log("Error updating GWA: " . $e->getMessage());
            return false;
        }
    } else {
        // Insert new record
        try {
            $insertStmt = $pdo->prepare("
                INSERT INTO user_gwa (user_id, game_type, gwa)
                VALUES (?, ?, ?)
            ");
            $success = $insertStmt->execute([$user_id, $game_type, $gwa]);
            error_log("Inserted new GWA record: " . ($success ? 'success' : 'failed'));
            if (!$success) {
                error_log("PDO Error: " . implode(" ", $insertStmt->errorInfo()));
            }
            return $success;
        } catch (PDOException $e) {
            error_log("Error inserting new GWA record: " . $e->getMessage());
            return false;
        }
    }
}

/**
 * Updates GWA for all game types for a specific user
 * 
 * @param PDO $pdo Database connection
 * @param int $user_id User ID
 * @return array Array with results for each game type
 */
function updateAllUserGWAs($pdo, $user_id) {
    // Get all game types the user has progress for
    $stmt = $pdo->prepare("
        SELECT DISTINCT game_type 
        FROM game_progress 
        WHERE user_id = ?
    ");
    $stmt->execute([$user_id]);
    $game_types = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // If no game progress, return false
    if (empty($game_types)) {
        error_log("No game progress found for user $user_id");
        return false;
    }
    
    $results = [];
    foreach ($game_types as $game_type) {
        $results[$game_type] = updateUserGWA($pdo, $user_id, $game_type);
    }
    
    return $results;
}

/**
 * Gets the current GWA for a user and game type
 * 
 * @param PDO $pdo Database connection
 * @param int $user_id User ID
 * @param string $game_type Game type
 * @return float|null The GWA or null if not found
 */
function getUserGWA($pdo, $user_id, $game_type) {
    $stmt = $pdo->prepare("
        SELECT gwa FROM user_gwa 
        WHERE user_id = ? AND game_type = ?
    ");
    $stmt->execute([$user_id, $game_type]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $result ? (float)$result['gwa'] : null;
}

/**
 * Gets all GWAs for a user
 * 
 * @param PDO $pdo Database connection
 * @param int $user_id User ID
 * @return array Associative array of game_type => gwa
 */
function getAllUserGWAs($pdo, $user_id) {
    $stmt = $pdo->prepare("
        SELECT game_type, gwa 
        FROM user_gwa 
        WHERE user_id = ?
    ");
    $stmt->execute([$user_id]);
    
    $results = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $results[$row['game_type']] = (float)$row['gwa'];
    }
    
    return $results;
}
?>
