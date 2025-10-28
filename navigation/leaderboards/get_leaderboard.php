<?php
require_once '../../onboarding/config.php';

// Set JSON content type
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

// Get sort parameters
$sortBy = $_GET['sort'] ?? 'level';
$sortDir = strtoupper($_GET['sort_dir'] ?? 'DESC');
$userId = $_GET['user_id'] ?? 0;

// Validate sort direction
$sortDir = in_array($sortDir, ['ASC', 'DESC']) ? $sortDir : 'DESC';

// Validate sort column
$validSorts = [
    'level', 'monsters_defeated', 'essence', 
    'shards', 'characters_owned', 'gwa'
];

if (!in_array($sortBy, $validSorts)) {
    $sortBy = 'level';
}

try {
    // Build the query with proper column mapping
    $query = "
        SELECT 
            u.id as user_id,
            u.username,
            COALESCE(ue.essence_amount, 0) as essence,
            COALESCE(us.current_shards, 0) as shards,
            COALESCE(gp.player_level, 1) as level,
            COALESCE(gp.total_monsters_defeated, 0) as monsters_defeated,
            COALESCE((
                SELECT COUNT(*) 
                FROM character_selections 
                WHERE user_id = u.id
            ), 0) as characters_owned,
            COALESCE((
                SELECT AVG(score) 
                FROM game_scores 
                WHERE user_id = u.id
            ), 0) as gwa
        FROM users u
        LEFT JOIN user_essence ue ON u.id = ue.user_id
        LEFT JOIN user_shards us ON u.id = us.user_id
        LEFT JOIN game_progress gp ON u.id = gp.user_id AND gp.game_type = 'vocabworld'
        ORDER BY $sortBy $sortDir, username ASC
        LIMIT 10
    ";

    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $leaderboardData = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format the data
    $formattedData = [];
    foreach ($leaderboardData as $row) {
        // Ensure all required fields have values
        $formattedData[] = [
            'user_id' => (int)$row['user_id'],
            'username' => $row['username'],
            'level' => (int)($row['level'] ?? 1),
            'monsters_defeated' => (int)($row['monsters_defeated'] ?? 0),
            'essence' => (int)($row['essence'] ?? 0),
            'shards' => (int)($row['shards'] ?? 0),
            'characters_owned' => (int)($row['characters_owned'] ?? 0),
            'gwa' => (float)($row['gwa'] ?? 0)
        ];
    }

    // Return the data
    echo json_encode([
        'success' => true,
        'data' => $formattedData,
        'sort' => $sortBy,
        'sort_dir' => $sortDir
    ]);

} catch (PDOException $e) {
    // Log the error for debugging
    error_log("Leaderboard query error: " . $e->getMessage());
    
    // Return a generic error message
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while loading the leaderboard.'
    ]);
}
?>
