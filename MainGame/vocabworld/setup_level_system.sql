-- Add player_level and experience columns to game_progress table
ALTER TABLE game_progress 
ADD COLUMN IF NOT EXISTS player_level INT DEFAULT 1,
ADD COLUMN IF NOT EXISTS experience_points INT DEFAULT 0,
ADD COLUMN IF NOT EXISTS total_monsters_defeated INT DEFAULT 0,
ADD COLUMN IF NOT EXISTS username VARCHAR(50);

-- Update existing records to have default level 1
UPDATE game_progress 
SET player_level = 1, experience_points = 0, total_monsters_defeated = 0 
WHERE player_level IS NULL;

-- Populate username column from users table
UPDATE game_progress gp
INNER JOIN users u ON gp.user_id = u.id
SET gp.username = u.username
WHERE gp.username IS NULL;
