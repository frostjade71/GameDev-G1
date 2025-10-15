<?php
require_once '../../../onboarding/config.php';

// Check if user is logged in (optional - for testing)
if (!isLoggedIn()) {
    echo "Please log in to run database setup.";
    exit();
}

try {
    // Create character_selections table (updated structure)
    $sql = "
    CREATE TABLE IF NOT EXISTS character_selections (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        game_type VARCHAR(50) NOT NULL DEFAULT 'vocabworld',
        selected_character VARCHAR(100) NOT NULL,
        character_image_path VARCHAR(255) NOT NULL,
        equipped_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        UNIQUE KEY unique_user_game_character (user_id, game_type)
    )";
    
    $pdo->exec($sql);
    echo "Character selections table created successfully.<br>";
    
    // Insert default character selections for existing users (updated structure)
    $sql = "
    INSERT IGNORE INTO character_selections (user_id, game_type, selected_character, character_image_path)
    SELECT 
        u.id,
        'vocabworld',
        'Ethan',
        'assets/characters/boy_char/character_ethan.png'
    FROM users u
    WHERE NOT EXISTS (
        SELECT 1 FROM character_selections cs 
        WHERE cs.user_id = u.id AND cs.game_type = 'vocabworld'
    )";
    
    $pdo->exec($sql);
    echo "Default character selections inserted for existing users.<br>";
    
    echo "<br>Database setup completed successfully!";
    
} catch (Exception $e) {
    echo "Error setting up database: " . $e->getMessage();
}
?>
