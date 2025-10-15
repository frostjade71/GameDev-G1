-- Update character_selections table structure
-- Remove character_name column and move its content to selected_character

-- Step 1: Update existing data to move character names to selected_character column
UPDATE character_selections 
SET selected_character = character_name 
WHERE character_name IS NOT NULL;

-- Step 2: Drop the character_name column
ALTER TABLE character_selections DROP COLUMN character_name;

-- Step 3: Update the selected_character column to allow longer character names
ALTER TABLE character_selections MODIFY COLUMN selected_character VARCHAR(100) NOT NULL;




