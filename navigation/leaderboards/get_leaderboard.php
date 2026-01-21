<?php
require_once __DIR__ . '/../../onboarding/config.php';

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
    // First, get all users ranked by the current sort criteria
    $rankQuery = "
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
            COALESCE(ug.gwa, 0) as gwa,
            @rank := @rank + 1 as rank
        FROM (SELECT @rank := 0) r,
             users u
        LEFT JOIN user_essence ue ON u.id = ue.user_id
        LEFT JOIN user_shards us ON u.id = us.user_id
        LEFT JOIN game_progress gp ON u.id = gp.user_id AND gp.game_type = 'vocabworld'
        LEFT JOIN user_gwa ug ON u.id = ug.user_id AND ug.game_type = 'vocabworld'
        ORDER BY $sortBy $sortDir, username ASC
    ";
    
    // Get the leaderboard data with simpler approach
    $orderByClause = $sortBy . ' ' . $sortDir;
    if ($sortBy === 'level') {
        $orderByClause = 'COALESCE(gp.player_level, 1) ' . $sortDir;
    } elseif ($sortBy === 'monsters_defeated') {
        $orderByClause = 'COALESCE(gp.total_monsters_defeated, 0) ' . $sortDir;
    } elseif ($sortBy === 'essence') {
        $orderByClause = 'COALESCE(ue.essence_amount, 0) ' . $sortDir;
    } elseif ($sortBy === 'shards') {
        $orderByClause = 'COALESCE(us.current_shards, 0) ' . $sortDir;
    } elseif ($sortBy === 'characters_owned') {
        $orderByClause = 'characters_owned ' . $sortDir;
    } elseif ($sortBy === 'gwa') {
        $orderByClause = 'COALESCE(ug.gwa, 0) ' . $sortDir;
    }
    
    $query = "
        SELECT 
            u.id as user_id,
            u.username,
            u.profile_image,
            COALESCE(ue.essence_amount, 0) as essence,
            COALESCE(us.current_shards, 0) as shards,
            COALESCE(gp.player_level, 1) as level,
            COALESCE(gp.total_monsters_defeated, 0) as monsters_defeated,
            COALESCE((
                SELECT COUNT(*) 
                FROM character_selections 
                WHERE user_id = u.id
            ), 0) as characters_owned,
            COALESCE(ug.gwa, 0) as gwa
        FROM users u
        LEFT JOIN user_essence ue ON u.id = ue.user_id
        LEFT JOIN user_shards us ON u.id = us.user_id
        LEFT JOIN game_progress gp ON u.id = gp.user_id AND gp.game_type = 'vocabworld'
        LEFT JOIN user_gwa ug ON u.id = ug.user_id AND ug.game_type = 'vocabworld'
        ORDER BY $orderByClause, u.username ASC
        LIMIT 10
    ";
    
    // Get the current user's rank with simpler approach
    $userRankQuery = "
        SELECT 
            u.id as user_id,
            u.username,
            u.profile_image,
            COALESCE(ue.essence_amount, 0) as essence,
            COALESCE(us.current_shards, 0) as shards,
            COALESCE(gp.player_level, 1) as level,
            COALESCE(gp.total_monsters_defeated, 0) as monsters_defeated,
            COALESCE((
                SELECT COUNT(*) 
                FROM character_selections 
                WHERE user_id = u.id
            ), 0) as characters_owned,
            COALESCE(ug.gwa, 0) as gwa
        FROM users u
        LEFT JOIN user_essence ue ON u.id = ue.user_id
        LEFT JOIN user_shards us ON u.id = us.user_id
        LEFT JOIN game_progress gp ON u.id = gp.user_id AND gp.game_type = 'vocabworld'
        LEFT JOIN user_gwa ug ON u.id = ug.user_id AND ug.game_type = 'vocabworld'
        WHERE u.id = :user_id
    ";

    // Get the leaderboard data
    $leaderboardStmt = $pdo->prepare($query);
    $leaderboardStmt->execute();
    $leaderboardData = $leaderboardStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get the current user's rank
    $userRankStmt = $pdo->prepare($userRankQuery);
    $userRankStmt->execute(['user_id' => $userId]);
    $userRankData = $userRankStmt->fetch(PDO::FETCH_ASSOC);
    
    // Format the data and calculate ranks
    $formattedData = [];
    $rank = 1;
    foreach ($leaderboardData as $index => $row) {
        // Calculate rank based on sort criteria
        if ($index > 0) {
            $prevRow = $leaderboardData[$index - 1];
            $prevValue = $prevRow[$sortBy];
            $currentValue = $row[$sortBy];
            
            // If same value as previous, same rank; otherwise increment
            if ($prevValue != $currentValue) {
                $rank = $index + 1;
            }
        }
        
        $formattedData[] = [
            'user_id' => (int)$row['user_id'],
            'username' => $row['username'],
            'level' => (int)($row['level'] ?? 1),
            'monsters_defeated' => (int)($row['monsters_defeated'] ?? 0),
            'essence' => (int)($row['essence'] ?? 0),
            'shards' => (int)($row['shards'] ?? 0),
            'characters_owned' => (int)($row['characters_owned'] ?? 0),
            'gwa' => (float)($row['gwa'] ?? 0),
            'profile_image' => $row['profile_image'],
            'rank' => $rank
        ];
    }
    
    // Calculate user rank
    $userRank = null;
    if ($userRankData) {
        // Simple rank calculation - get all users sorted and find position
        $allUsersQuery = "
            SELECT u.id as user_id
            FROM users u
            LEFT JOIN user_essence ue ON u.id = ue.user_id
            LEFT JOIN user_shards us ON u.id = us.user_id
            LEFT JOIN game_progress gp ON u.id = gp.user_id AND gp.game_type = 'vocabworld'
            LEFT JOIN user_gwa ug ON u.id = ug.user_id AND ug.game_type = 'vocabworld'
            ORDER BY $orderByClause, u.username ASC
        ";
        
        $allUsersStmt = $pdo->prepare($allUsersQuery);
        $allUsersStmt->execute();
        $allUsers = $allUsersStmt->fetchAll(PDO::FETCH_COLUMN, 0);
        
        // Find user's position in the sorted list
        $userPosition = array_search($userId, $allUsers);
        $rank = $userPosition !== false ? $userPosition + 1 : 1;
        
        $userRank = [
            'user_id' => (int)$userRankData['user_id'],
            'username' => $userRankData['username'],
            'level' => (int)($userRankData['level'] ?? 1),
            'monsters_defeated' => (int)($userRankData['monsters_defeated'] ?? 0),
            'essence' => (int)($userRankData['essence'] ?? 0),
            'shards' => (int)($userRankData['shards'] ?? 0),
            'characters_owned' => (int)($userRankData['characters_owned'] ?? 0),
            'gwa' => (float)($userRankData['gwa'] ?? 0),
            'profile_image' => $userRankData['profile_image'],
            'rank' => $rank
        ];
    }

    // Return the data
    echo json_encode([
        'success' => true,
        'data' => $formattedData,
        'user_rank' => $userRank,
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
