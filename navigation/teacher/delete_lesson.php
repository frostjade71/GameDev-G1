<?php
ob_start();
require_once '../../onboarding/config.php';
header('Content-Type: application/json');

// Check access
session_start();
$gradeLevel = $_SESSION['grade_level'] ?? '';
$isTeacherOrAdmin = in_array(strtolower($gradeLevel), array_map('strtolower', ['Teacher', 'Admin', 'Developer']));

if (!function_exists('isLoggedIn') && !isset($_SESSION['user_id']) || !$isTeacherOrAdmin) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $id = $_POST['id'] ?? null;

        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'ID is required.']);
            exit();
        }

        $stmt = $pdo->prepare("DELETE FROM lessons WHERE id = ?");
        $stmt->execute([$id]);

        ob_clean();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        ob_clean();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
