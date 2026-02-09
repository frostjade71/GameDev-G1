<?php
require_once '../../../onboarding/config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    echo "Please log in to run database setup.";
    exit();
}

echo "<h2>Complete Database Setup</h2>";

try {
    // Create character_selections table
    $sql = "
    CREATE TABLE IF NOT EXISTS character_selections (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        username VARCHAR(100) NOT NULL,
        game_type VARCHAR(50) NOT NULL DEFAULT 'vocabworld',
        selected_character VARCHAR(100) NOT NULL,
        character_image_path VARCHAR(255) NOT NULL,
        equipped_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        UNIQUE KEY unique_user_game_character (user_id, game_type)
    )";
    
    $pdo->exec($sql);
    echo "✅ Character selections table created successfully.<br>";
    
    // Create character_ownership table
    $sql = "
    CREATE TABLE IF NOT EXISTS character_ownership (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        username VARCHAR(100) NOT NULL,
        character_type VARCHAR(50) NOT NULL,
        character_name VARCHAR(100) NOT NULL,
        character_image_path VARCHAR(255) NOT NULL,
        purchased_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        UNIQUE KEY unique_user_character (user_id, character_type)
    )";
    
    $pdo->exec($sql);
    echo "✅ Character ownership table created successfully.<br>";
    
    // Create user_shards table
    $sql = "
    CREATE TABLE IF NOT EXISTS user_shards (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        username VARCHAR(100) NOT NULL,
        current_shards INT NOT NULL DEFAULT 0,
        total_earned INT NOT NULL DEFAULT 0,
        total_spent INT NOT NULL DEFAULT 0,
        last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        UNIQUE KEY unique_user_shards (user_id)
    )";
    
    $pdo->exec($sql);
    echo "✅ User shards table created successfully.<br>";
    
    // Create shard_transactions table
    $sql = "
    CREATE TABLE IF NOT EXISTS shard_transactions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        username VARCHAR(100) NOT NULL,
        transaction_type ENUM('earned', 'spent') NOT NULL,
        amount INT NOT NULL,
        description VARCHAR(255) NOT NULL,
        game_type VARCHAR(50) DEFAULT 'vocabworld',
        related_id INT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    
    $pdo->exec($sql);
    echo "✅ Shard transactions table created successfully.<br>";
    
    // Insert default character ownership for existing users
    $sql = "
    INSERT IGNORE INTO character_ownership (user_id, username, character_type, character_name, character_image_path)
    SELECT 
        u.id,
        u.username,
        'boy',
        'Ethan',
        '../assets/characters/boy_char/character_ethan.png'
    FROM users u
    WHERE NOT EXISTS (
        SELECT 1 FROM character_ownership co 
        WHERE co.user_id = u.id AND co.character_type = 'boy'
    )";
    
    $pdo->exec($sql);
    echo "✅ Default boy character ownership inserted.<br>";
    
    $sql = "
    INSERT IGNORE INTO character_ownership (user_id, username, character_type, character_name, character_image_path)
    SELECT 
        u.id,
        u.username,
        'girl',
        'Emma',
        '../assets/characters/girl_char/character_emma.png'
    FROM users u
    WHERE NOT EXISTS (
        SELECT 1 FROM character_ownership co 
        WHERE co.user_id = u.id AND co.character_type = 'girl'
    )";
    
    $pdo->exec($sql);
    echo "✅ Default girl character ownership inserted.<br>";
    
    // Initialize user_shards for existing users
    $sql = "
    INSERT IGNORE INTO user_shards (user_id, username, current_shards, total_earned, total_spent)
    SELECT 
        u.id,
        u.username,
        COALESCE(JSON_EXTRACT(gp.unlocked_levels, '$.current_points'), 0) as current_shards,
        COALESCE(JSON_EXTRACT(gp.unlocked_levels, '$.current_points'), 0) as total_earned,
        0 as total_spent
    FROM users u
    LEFT JOIN game_progress gp ON u.id = gp.user_id AND gp.game_type = 'vocabworld'
    WHERE NOT EXISTS (
        SELECT 1 FROM user_shards us 
        WHERE us.user_id = u.id
    )";
    
    $pdo->exec($sql);
    echo "✅ User shard accounts initialized.<br>";
    
    // Insert default character selections for existing users
    $sql = "
    INSERT IGNORE INTO character_selections (user_id, username, game_type, selected_character, character_image_path)
    SELECT 
        u.id,
        u.username,
        'vocabworld',
        'Ethan',
        '../assets/characters/boy_char/character_ethan.png'
    FROM users u
    WHERE NOT EXISTS (
        SELECT 1 FROM character_selections cs 
        WHERE cs.user_id = u.id AND cs.game_type = 'vocabworld'
    )";
    
    $pdo->exec($sql);
    echo "✅ Default character selections inserted.<br>";
    
    echo "<br><strong>✅ Database setup completed successfully!</strong><br>";
    echo "<p><a href='debug_database.php'>Check Database Status</a> | <a href='add_test_shards.php'>Add Test Shards</a> | <a href='shop_characters.php'>Go to Shop</a></p>";
    
} catch (Exception $e) {
    echo "❌ Error setting up database: " . $e->getMessage();
}
?>


