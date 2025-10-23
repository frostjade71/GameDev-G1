<?php
require_once 'C:/xampp/htdocs/GameDev-G1/onboarding/config.php';
require_once 'level_manager.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$user_id = $_SESSION['user_id'];
$levelManager = new LevelManager($pdo);

// Get request data
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid input']);
    exit;
}

$action = $input['action'] ?? '';

try {
    switch ($action) {
        case 'add_exp':
            $exp_amount = intval($input['exp_amount'] ?? 0);
            $is_correct = $input['is_correct'] ?? false;
            
            if ($exp_amount <= 0) {
                $exp_amount = $levelManager->calculateExpReward($is_correct);
            }
            
            $result = $levelManager->addExperience($user_id, $exp_amount);
            
            // Increment monster count if correct answer
            if ($is_correct) {
                $levelManager->incrementMonsterCount($user_id);
            }
            
            echo json_encode([
                'success' => true,
                'data' => $result
            ]);
            break;
            
        case 'get_level':
            $level_data = $levelManager->getPlayerLevel($user_id);
            echo json_encode([
                'success' => true,
                'data' => $level_data
            ]);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
