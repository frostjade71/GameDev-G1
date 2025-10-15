<?php
require_once '../../onboarding/config.php';
require_once 'shard_manager.php';

// Check if user is logged in
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'User not logged in']);
    exit();
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON input']);
    exit();
}

$user_id = $_SESSION['user_id'];
$game_type = 'vocabworld';
$score = intval($input['score'] ?? 0);
$level = intval($input['level'] ?? 1);
$questions_answered = intval($input['questionsAnswered'] ?? 0);
$correct_answers = intval($input['correctAnswers'] ?? 0);
$points = intval($input['points'] ?? 0);

// Initialize shard manager
$shardManager = new ShardManager($pdo);

try {
    // Start transaction
    $pdo->beginTransaction();
    
    // Save game score
    $stmt = $pdo->prepare("
        INSERT INTO game_scores (user_id, game_type, score, level, time_spent) 
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([$user_id, $game_type, $score, $level, 0]);
    
    // Update or create game progress
    $stmt = $pdo->prepare("
        INSERT INTO game_progress (user_id, game_type, unlocked_levels, achievements, total_play_time, last_played) 
        VALUES (?, ?, ?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE 
        unlocked_levels = VALUES(unlocked_levels),
        total_play_time = total_play_time + VALUES(total_play_time),
        last_played = NOW(),
        updated_at = NOW()
    ");
    
    // Create progress data
    $progress_data = [
        'total_sessions' => 1,
        'total_score' => $score,
        'highest_level' => $level,
        'total_questions' => $questions_answered,
        'total_correct' => $correct_answers,
        'current_points' => $points
    ];
    
    $achievements = [];
    
    $stmt->execute([
        $user_id, 
        $game_type, 
        json_encode($progress_data), 
        json_encode($achievements), 
        0
    ]);
    
    // Award shards if points were earned
    if ($points > 0) {
        $shard_result = $shardManager->addShards(
            $user_id, 
            $points, 
            "Earned from gameplay - Level {$level}", 
            $game_type
        );
        
        if (!$shard_result['success']) {
            $pdo->rollBack();
            echo json_encode([
                'success' => false,
                'error' => 'Failed to award shards: ' . $shard_result['error']
            ]);
            exit();
        }
        
        // Update progress data with new shard balance
        $progress_data['current_points'] = $shard_result['new_balance'];
    }
    
    // Commit transaction
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Progress saved successfully',
        'data' => $progress_data,
        'shards_awarded' => $points > 0 ? $points : 0,
        'new_shard_balance' => $points > 0 ? $shard_result['new_balance'] : null
    ]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    $pdo->rollBack();
    
    http_response_code(500);
    echo json_encode([
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
