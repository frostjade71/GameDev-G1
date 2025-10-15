<?php
require_once '../onboarding/config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit();
}

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_score') {
    $user_id = $_SESSION['user_id'];
    $game_type = $_POST['game_type'] ?? '';
    $score = intval($_POST['score'] ?? 0);
    $level = intval($_POST['level'] ?? 1);
    
    // Validate input
    if (empty($game_type) || $score < 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid input']);
        exit();
    }
    
    try {
        // Insert the score
        $stmt = $pdo->prepare("INSERT INTO game_scores (user_id, game_type, score, level) VALUES (?, ?, ?, ?)");
        $stmt->execute([$user_id, $game_type, $score, $level]);
        
        echo json_encode(['success' => true, 'message' => 'Score saved successfully']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>
