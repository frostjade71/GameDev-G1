-- Character Selection Database Setup
-- This script creates a table to store user character selections

-- Create character_selections table (updated structure with username)
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
);

-- Create character_ownership table
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
);

-- Create user_shards table (Bank system for shards)
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
);

-- Create shard_transactions table (Transaction history)
CREATE TABLE IF NOT EXISTS shard_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    username VARCHAR(100) NOT NULL,
    transaction_type ENUM('earned', 'spent') NOT NULL,
    amount INT NOT NULL,
    description VARCHAR(255) NOT NULL,
    game_type VARCHAR(50) DEFAULT 'vocabworld',
    related_id INT NULL, -- Can reference character_ownership.id for purchases
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Insert default character ownership for existing users
-- This will give all existing users the free characters (boy and girl)
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
);

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
);

-- Initialize user_shards for existing users
-- This will create shard accounts for all existing users
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
);

-- Insert default character selections for existing users
-- This will set 'boy' (Ethan) as the default character for all existing users
INSERT IGNORE INTO character_selections (user_id, game_type, selected_character, character_name, character_image_path)
SELECT 
    u.id,
    'vocabworld',
    'boy',
    'Ethan',
    '../assets/characters/boy_char/character_ethan.png'
FROM users u
WHERE NOT EXISTS (
    SELECT 1 FROM character_selections cs 
    WHERE cs.user_id = u.id AND cs.game_type = 'vocabworld'
);
