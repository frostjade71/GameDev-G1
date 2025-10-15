-- Updated Database setup for Word Weavers School Portal
-- Run this script to create the database and all required tables

CREATE DATABASE IF NOT EXISTS school_portal;
USE school_portal;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    grade_level VARCHAR(20) NOT NULL,
    section VARCHAR(20) DEFAULT NULL,
    password VARCHAR(255) NOT NULL,
    about_me TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- User settings table
CREATE TABLE IF NOT EXISTS user_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    bgm_enabled TINYINT(1) DEFAULT 1,
    sfx_enabled TINYINT(1) DEFAULT 1,
    language VARCHAR(10) DEFAULT 'english',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Game scores table
CREATE TABLE IF NOT EXISTS game_scores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    game_type VARCHAR(50) NOT NULL,
    score INT NOT NULL DEFAULT 0,
    level INT NOT NULL DEFAULT 1,
    time_spent INT DEFAULT 0, -- in seconds
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_game (user_id, game_type),
    INDEX idx_score (score DESC)
);

-- User favorites table
CREATE TABLE IF NOT EXISTS user_favorites (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    game_type VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_game (user_id, game_type)
);

-- Game progress table (for tracking unlocked levels, achievements, etc.)
CREATE TABLE IF NOT EXISTS game_progress (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    game_type VARCHAR(50) NOT NULL,
    unlocked_levels TEXT, -- JSON string of unlocked levels
    achievements TEXT, -- JSON string of achievements
    total_play_time INT DEFAULT 0, -- in seconds
    last_played TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_game_progress (user_id, game_type)
);

-- NOTE: If you're running this on an existing database, you may need to add missing columns:
-- For about_me field: ALTER TABLE users ADD COLUMN about_me TEXT DEFAULT NULL AFTER password;
-- For section field: ALTER TABLE users ADD COLUMN section VARCHAR(20) DEFAULT NULL AFTER grade_level;

-- Insert a sample user for testing (password: test123)
-- Only insert if the user doesn't exist
INSERT IGNORE INTO users (username, email, grade_level, password, about_me) VALUES 
('testuser', 'test@example.com', 'Grade 5', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'I love playing educational word games!');

-- Get the user ID for sample data
SET @sample_user_id = (SELECT id FROM users WHERE email = 'test@example.com' LIMIT 1);

-- Insert sample game scores for testing (only if user exists)
INSERT INTO game_scores (user_id, game_type, score, level, time_spent) 
SELECT @sample_user_id, 'grammar-heroes', 1500, 3, 300 WHERE @sample_user_id IS NOT NULL
UNION ALL
SELECT @sample_user_id, 'vocabworld', 2200, 5, 450 WHERE @sample_user_id IS NOT NULL
UNION ALL
SELECT @sample_user_id, 'grammar-heroes', 1800, 4, 280 WHERE @sample_user_id IS NOT NULL;

-- Insert sample favorites for testing (only if user exists)
INSERT IGNORE INTO user_favorites (user_id, game_type) 
SELECT @sample_user_id, 'grammar-heroes' WHERE @sample_user_id IS NOT NULL
UNION ALL
SELECT @sample_user_id, 'vocabworld' WHERE @sample_user_id IS NOT NULL;

-- Insert sample user settings for testing (only if user exists)
INSERT IGNORE INTO user_settings (user_id, bgm_enabled, sfx_enabled, language) 
SELECT @sample_user_id, 1, 1, 'english' WHERE @sample_user_id IS NOT NULL;
