<?php
require_once '../../../onboarding/config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    echo "Please log in to run database update.";
    exit();
}

echo "<h2>Adding Username Column to Character Selections Table</h2>";

try {
    // Step 1: Add username column to character_selections table
    echo "Step 1: Adding username column...<br>";
    $sql = "ALTER TABLE character_selections ADD COLUMN username VARCHAR(100) NOT NULL DEFAULT ''";
    $pdo->exec($sql);
    echo "✅ Username column added.<br>";
    
    // Step 2: Update existing records with usernames from users table
    echo "Step 2: Updating existing records with usernames...<br>";
    $sql = "UPDATE character_selections cs
            JOIN users u ON cs.user_id = u.id
            SET cs.username = u.username";
    $pdo->exec($sql);
    echo "✅ Existing records updated with usernames.<br>";
    
    // Step 3: Make username column NOT NULL (remove default)
    echo "Step 3: Making username column NOT NULL...<br>";
    $sql = "ALTER TABLE character_selections MODIFY COLUMN username VARCHAR(100) NOT NULL";
    $pdo->exec($sql);
    echo "✅ Username column set to NOT NULL.<br>";
    
    // Step 4: Verify the changes
    echo "Step 4: Verifying changes...<br>";
    $stmt = $pdo->query("SELECT id, user_id, username, game_type, selected_character, character_image_path, equipped_at, updated_at FROM character_selections LIMIT 5");
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>ID</th><th>User ID</th><th>Username</th><th>Game Type</th><th>Selected Character</th><th>Image Path</th><th>Equipped At</th></tr>";
    foreach ($results as $row) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['id']) . "</td>";
        echo "<td>" . htmlspecialchars($row['user_id']) . "</td>";
        echo "<td>" . htmlspecialchars($row['username']) . "</td>";
        echo "<td>" . htmlspecialchars($row['game_type']) . "</td>";
        echo "<td>" . htmlspecialchars($row['selected_character']) . "</td>";
        echo "<td>" . htmlspecialchars($row['character_image_path']) . "</td>";
        echo "<td>" . htmlspecialchars($row['equipped_at']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<br><strong>Username column added successfully!</strong><br>";
    echo "The character_selections table now includes usernames for better management.<br>";
    
} catch (Exception $e) {
    echo "❌ Error adding username column: " . $e->getMessage();
}
?>




