-- Create user_fame table for tracking views and crescents
CREATE TABLE IF NOT EXISTS user_fame (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(255) NOT NULL UNIQUE,
    cresents INT DEFAULT 0,
    views INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create index for faster lookups
CREATE INDEX idx_user_fame_username ON user_fame(username);

-- Initialize existing users in user_fame table
INSERT IGNORE INTO user_fame (username, cresents, views)
SELECT username, 0, 0 FROM users;
