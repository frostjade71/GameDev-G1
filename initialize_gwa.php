<?php
require_once 'onboarding/config.php';

// Get all users who have game scores but no GWA records
$stmt = $pdo->query("
    SELECT DISTINCT gs.user_id, gs.game_type
    FROM game_scores gs
    LEFT JOIN user_gwa ug ON gs.user_id = ug.user_id AND gs.game_type = ug.game_type
    WHERE ug.id IS NULL
");

$usersToInitialize = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($usersToInitialize)) {
    echo "All users already have GWA records.\n";
    exit(0);
}

echo "Initializing GWA records for " . count($usersToInitialize) . " user-game combinations...\n";

// Include the update_gwa function
require_once 'includes/update_gwa.php';

$updated = 0;
$errors = 0;

foreach ($usersToInitialize as $user) {
    $success = updateUserGWA($pdo, $user['user_id'], $user['game_type']);
    
    if ($success) {
        $updated++;
        echo "[SUCCESS] Updated GWA for user {$user['user_id']} - {$user['game_type']}\n";
    } else {
        $errors++;
        echo "[ERROR] Failed to update GWA for user {$user['user_id']} - {$user['game_type']}\n";
    }
}

echo "\nInitialization complete.\n";
echo "Successfully updated: $updated\n";
echo "Errors: $errors\n";

echo "\nYou can now run this script periodically to ensure all users have GWA records.\n";
?>
