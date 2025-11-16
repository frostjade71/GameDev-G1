<?php
require_once 'onboarding/config.php';

try {
    // Check if user_gwa table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'user_gwa'");
    if ($stmt->rowCount() > 0) {
        echo "user_gwa table exists.\n";
        
        // Get count of records
        $count = $pdo->query("SELECT COUNT(*) as count FROM user_gwa")->fetch()['count'];
        echo "Number of records in user_gwa table: " . $count . "\n";
        
        // Get sample data
        $sample = $pdo->query("SELECT * FROM user_gwa LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
        echo "Sample data from user_gwa table:\n";
        print_r($sample);
    } else {
        echo "user_gwa table does not exist.\n";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
