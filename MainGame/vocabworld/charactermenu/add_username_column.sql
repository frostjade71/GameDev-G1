-- Add username column to character_selections table
-- This script adds a username column for better management

-- Step 1: Add username column to character_selections table
ALTER TABLE character_selections ADD COLUMN username VARCHAR(100) NOT NULL DEFAULT '';

-- Step 2: Update existing records with usernames from users table
UPDATE character_selections cs
JOIN users u ON cs.user_id = u.id
SET cs.username = u.username;

-- Step 3: Make username column NOT NULL (remove default)
ALTER TABLE character_selections MODIFY COLUMN username VARCHAR(100) NOT NULL;

-- Step 4: Verify the changes
SELECT 
    id,
    user_id,
    username,
    game_type,
    selected_character,
    character_image_path,
    equipped_at,
    updated_at
FROM character_selections 
LIMIT 5;




