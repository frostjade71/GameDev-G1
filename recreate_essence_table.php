<?php
require_once 'C:/xampp/htdocs/GameDev-G1/onboarding/config.php';

try {
    // Drop the existing table if it exists
    $pdo->exec("DROP TABLE IF EXISTS user_essence");
    
    // Include the EssenceManager class to recreate the table
    require_once 'C:/xampp/htdocs/GameDev-G1/MainGame/vocabworld/api/essence_manager.php';
    
    // This will recreate the table with the new schema
    $essenceManager = new EssenceManager($pdo);
    
    // Get all users and initialize their essence if needed
    $users = $pdo->query("SELECT id, username FROM users");
    
    foreach ($users as $user) {
        // This will create a new entry with 0 essence if it doesn't exist
        $essenceManager->addEssence($user['id'], 0);
    }
    
    // Verify the table structure
    $stmt = $pdo->query("SHOW CREATE TABLE user_essence");
    $table = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "Successfully recreated user_essence table with username field.\n\n";
    echo "Table structure:\n";
    echo $table['Create Table'] . "\n\n";
    
    // Show sample data
    $sample = $pdo->query("SELECT ue.id, ue.user_id, ue.username, ue.essence_amount, ue.last_updated 
                          FROM user_essence ue 
                          ORDER BY ue.id LIMIT 5");
    echo "Sample data (first 5 records):\n";
    echo "ID\tUser ID\tUsername\tEssence\tLast Updated\n";
    echo str_repeat("-", 80) . "\n";
    
    while ($row = $sample->fetch(PDO::FETCH_ASSOC)) {
        echo "{$row['id']}\t";
        echo "{$row['user_id']}\t";
        echo "{$row['username']}\t";
        echo "{$row['essence_amount']}\t";
        echo "{$row['last_updated']}\n";
    }
    
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

echo "\nScript completed successfully. The user_essence table has been recreated with the username field.\n";
?>
