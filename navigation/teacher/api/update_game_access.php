<?php
require_once '../../../onboarding/config.php';

header('Content-Type: application/json');

// Check if user is logged in and is Teacher/Admin
$gradeLevel = $_SESSION['grade_level'] ?? '';
$isTeacherOrAdmin = in_array(strtolower($gradeLevel), array_map('strtolower', ['Teacher', 'Admin', 'Developer']));

if (!isset($_SESSION['user_id']) || !$isTeacherOrAdmin) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['grade_level']) || !isset($data['is_enabled'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit();
}

$targetGrade = (int)$data['grade_level'];
$isEnabled = (int)$data['is_enabled']; // 0 or 1

try {
    $stmt = $pdo->prepare("INSERT INTO game_access_controls (grade_level, is_enabled) VALUES (?, ?) ON DUPLICATE KEY UPDATE is_enabled = ?");
    $stmt->execute([$targetGrade, $isEnabled, $isEnabled]);

    echo json_encode(['success' => true, 'message' => 'Settings updated']);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
