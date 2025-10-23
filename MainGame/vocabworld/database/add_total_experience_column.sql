-- Add total_experience_earned column to game_progress table
ALTER TABLE game_progress 
ADD COLUMN IF NOT EXISTS total_experience_earned INT DEFAULT 0;

-- Update existing records to set total_experience_earned to current experience_points
UPDATE game_progress 
SET total_experience_earned = experience_points 
WHERE total_experience_earned IS NULL;
