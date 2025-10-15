<?php
require_once '../../../onboarding/config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    echo "Please log in to run database update.";
    exit();
}

echo "<h2>Updating Character Selections Table Structure</h2>";

try {
    // Step 1: Update existing data to move character names to selected_character column
    echo "Step 1: Moving character names to selected_character column...<br>";
    $sql = "UPDATE character_selections SET selected_character = character_name WHERE character_name IS NOT NULL";
    $pdo->exec($sql);
    echo "✅ Character names moved to selected_character column.<br>";
    
    // Step 2: Drop the character_name column
    echo "Step 2: Dropping character_name column...<br>";
    $sql = "ALTER TABLE character_selections DROP COLUMN character_name";
    $pdo->exec($sql);
    echo "✅ character_name column removed.<br>";
    
    // Step 3: Update the selected_character column to allow longer character names
    echo "Step 3: Updating selected_character column size...<br>";
    $sql = "ALTER TABLE character_selections MODIFY COLUMN selected_character VARCHAR(100) NOT NULL";
    $pdo->exec($sql);
    echo "✅ selected_character column updated to VARCHAR(100).<br>";
    
    echo "<br><strong>Database structure update completed successfully!</strong><br>";
    echo "The character_selections table now stores character names in the selected_character column.<br>";
    
} catch (Exception $e) {
    echo "❌ Error updating database structure: " . $e->getMessage();
}
?>




