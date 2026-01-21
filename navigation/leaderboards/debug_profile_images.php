<?php
require_once '../onboarding/config.php';

// Check if profile_image column exists and has data
$stmt = $pdo->prepare("
    SELECT 
        u.id,
        u.username,
        u.profile_image,
        COALESCE(gp.player_level, 1) as level
    FROM users u
    LEFT JOIN game_progress gp ON u.id = gp.user_id AND gp.game_type = 'vocabworld'
    ORDER BY level DESC
    LIMIT 5
");
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h2>Top 5 Users - Profile Image Debug</h2>";
echo "<table border='1' cellpadding='10'>";
echo "<tr><th>ID</th><th>Username</th><th>Profile Image Path</th><th>Level</th><th>Image Exists?</th></tr>";

foreach ($users as $user) {
    $image_path = $user['profile_image'];
    $full_path = '../' . $image_path;
    $exists = !empty($image_path) && file_exists($full_path) ? 'YES' : 'NO';
    
    echo "<tr>";
    echo "<td>" . htmlspecialchars($user['id']) . "</td>";
    echo "<td>" . htmlspecialchars($user['username']) . "</td>";
    echo "<td>" . htmlspecialchars($image_path ?? 'NULL') . "</td>";
    echo "<td>" . htmlspecialchars($user['level']) . "</td>";
    echo "<td style='color: " . ($exists === 'YES' ? 'green' : 'red') . "'>" . $exists . "</td>";
    echo "</tr>";
}

echo "</table>";

echo "<br><br><h3>Profile Image Display Test:</h3>";
foreach ($users as $user) {
    $img_src = !empty($user['profile_image']) ? '../' . htmlspecialchars($user['profile_image']) : '../assets/menu/defaultuser.png';
    echo "<div style='margin: 10px; display: inline-block; text-align: center;'>";
    echo "<img src='" . $img_src . "' alt='" . htmlspecialchars($user['username']) . "' style='width: 80px; height: 80px; border-radius: 50%; border: 2px solid #60efff;'><br>";
    echo htmlspecialchars($user['username']);
    echo "</div>";
}
?>
