-- Complete SQL Update for character_selections table
-- This script removes character_name column and moves its content to selected_character

-- Step 1: Backup existing data (optional but recommended)
CREATE TABLE IF NOT EXISTS character_selections_backup AS 
SELECT * FROM character_selections;

-- Step 2: Update existing data to move character names to selected_character column
UPDATE character_selections 
SET selected_character = character_name 
WHERE character_name IS NOT NULL AND character_name != '';

-- Step 3: Drop the character_name column
ALTER TABLE character_selections DROP COLUMN character_name;

-- Step 4: Update the selected_character column to allow longer character names
ALTER TABLE character_selections MODIFY COLUMN selected_character VARCHAR(100) NOT NULL;

-- Step 5: Verify the changes
SELECT 
    id,
    user_id,
    game_type,
    selected_character,
    character_image_path,
    equipped_at,
    updated_at
FROM character_selections 
LIMIT 5;

-- Optional: Drop backup table after verification (uncomment if needed)
-- DROP TABLE IF EXISTS character_selections_backup;




