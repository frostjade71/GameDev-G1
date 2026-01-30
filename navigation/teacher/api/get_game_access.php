<?php
require_once '../../../onboarding/config.php';

header('Content-Type: application/json');

// Check if user is logged in (Teachers/Admins only for viewing all)
// But students might need to check their own status, so we'll handle that too.
// For this specific file, it's in navigation/teacher/api, so likely for teacher use.
// However, the game.php is in MainGame, so it might need a separate endpoint or reuse this if accessible.
// Let's make this for the Teacher Dashboard first.

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

try {
    $stmt = $pdo->query("SELECT grade_level, is_enabled FROM game_access_controls ORDER BY grade_level ASC");
    $controls = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format for easier JS consumption: { "7": true, "8": false, ... }
    $formatted = [];
    foreach ($controls as $row) {
        $formatted[$row['grade_level']] = (bool)$row['is_enabled'];
    }

    echo json_encode($formatted);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
