<?php
/**
 * Database Migration: Add profile_image column to users table
 * Run this file once to add the profile_image column
 * Access via: http://localhost:8080/database/run_migration.php
 */

require_once '../onboarding/config.php';

try {
    // Check if column already exists
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'profile_image'");
    $columnExists = $stmt->fetch();
    
    if ($columnExists) {
        echo "✓ Column 'profile_image' already exists in users table.<br>";
    } else {
        // Add profile_image column
        $sql = "ALTER TABLE users 
                ADD COLUMN profile_image VARCHAR(255) NULL DEFAULT NULL 
                COMMENT 'Path to user profile image, relative to project root'";
        
        $pdo->exec($sql);
        echo "✓ Successfully added 'profile_image' column to users table.<br>";
    }
    
    // Check if index exists
    $stmt = $pdo->query("SHOW INDEX FROM users WHERE Key_name = 'idx_profile_image'");
    $indexExists = $stmt->fetch();
    
    if ($indexExists) {
        echo "✓ Index 'idx_profile_image' already exists.<br>";
    } else {
        // Add index for faster lookups
        $sql = "CREATE INDEX idx_profile_image ON users(profile_image)";
        $pdo->exec($sql);
        echo "✓ Successfully created index 'idx_profile_image'.<br>";
    }
    
    echo "<br><strong>Migration completed successfully!</strong><br>";
    echo "You can now use the profile image upload feature.<br>";
    echo "<br><a href='../navigation/profile/profile.php'>Go to Profile Page</a>";
    
} catch (PDOException $e) {
    echo "❌ Migration failed: " . $e->getMessage();
}
?>
