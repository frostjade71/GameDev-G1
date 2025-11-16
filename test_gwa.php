<?php
// test_gwa.php
require_once __DIR__ . '/onboarding/config.php';
require_once __DIR__ . '/includes/gwa_updater.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Test user ID - change this to your user ID
$test_user_id = 1; // Replace with your actual user ID

echo "<h1>GWA Update Test</h1>";

try {
    // Test database connection
    echo "<h2>Database Connection Test</h2>";
    $pdo->query("SELECT 1");
    echo "✅ Database connection successful!<br><br>";
    
    // Check if user_gwa table exists
    echo "<h2>Checking user_gwa Table</h2>";
    $tableExists = $pdo->query("SHOW TABLES LIKE 'user_gwa'")->rowCount() > 0;
    
    if ($tableExists) {
        echo "✅ user_gwa table exists<br>";
        
        // Show current records
        $records = $pdo->query("SELECT * FROM user_gwa WHERE user_id = $test_user_id")->fetchAll(PDO::FETCH_ASSOC);
        if (count($records) > 0) {
            echo "<h3>Current GWA Records:</h3>";
            echo "<pre>" . print_r($records, true) . "</pre>";
        } else {
            echo "ℹ️ No GWA records found for user ID: $test_user_id<br>";
        }
    } else {
        echo "❌ user_gwa table does not exist!<br>";
        echo "Please make sure the table was created with the correct structure.<br>";
        echo "Here's the SQL to create it:<br>";
        echo "<pre>
        CREATE TABLE `user_gwa` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `user_id` int(11) NOT NULL,
          `game_type` varchar(50) NOT NULL,
          `gwa` decimal(5,2) NOT NULL,
          `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
          `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
          PRIMARY KEY (`id`),
          UNIQUE KEY `user_game` (`user_id`,`game_type`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
        </pre>";
        exit;
    }
    
    // Check if user has any game scores
    echo "<h2>Checking Game Scores</h2>";
    $scores = $pdo->query("SELECT game_type, COUNT(*) as count, AVG(score) as avg_score 
                          FROM game_scores 
                          WHERE user_id = $test_user_id 
                          GROUP BY game_type")->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($scores) > 0) {
        echo "<h3>Found Game Scores:</h3>";
        echo "<pre>" . print_r($scores, true) . "</pre>";
    } else {
        echo "❌ No game scores found for user ID: $test_user_id<br>";
        echo "The user needs to have at least one score in the game_scores table to calculate GWA.";
        exit;
    }
    
    // Test updating GWA
    echo "<h2>Updating GWA</h2>";
    $results = updateAllUserGWAs($pdo, $test_user_id);
    
    echo "<h3>Update Results:</h3>";
    echo "<pre>" . print_r($results, true) . "</pre>";
    
    // Check updated records
    $updated = $pdo->query("SELECT * FROM user_gwa WHERE user_id = $test_user_id")->fetchAll(PDO::FETCH_ASSOC);
    echo "<h3>Updated GWA Records:</h3>";
    if (count($updated) > 0) {
        echo "<pre>" . print_r($updated, true) . "</pre>";
    } else {
        echo "❌ Still no GWA records after update. Check the error log for details.<br>";
        echo "Error log location: " . ini_get('error_log');
    }
    
} catch (PDOException $e) {
    echo "<div style='color: red;'>❌ Database Error: " . $e->getMessage() . "</div>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>
