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
        $title = $_POST['title'] ?? '';
        $content = $_POST['content'] ?? ''; // WYSIWYG Content
        $grade_level = $_POST['grade_level'] ?? '';
        $section = $_POST['section'] ?? '';
        $user_id = $_SESSION['user_id'];

        if (empty($title) || empty($grade_level)) {
            echo json_encode(['success' => false, 'message' => 'Title and Grade Level are required.']);
            exit();
        }

        if ($id) {
            // Update
            $stmt = $pdo->prepare("UPDATE lessons SET title = ?, content = ?, grade_level = ?, section = ? WHERE id = ?");
            $stmt->execute([$title, $content, $grade_level, $section, $id]);
        } else {
            // Create
            $stmt = $pdo->prepare("INSERT INTO lessons (title, content, grade_level, section, created_by) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$title, $content, $grade_level, $section, $user_id]);
        }

        ob_clean();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        ob_clean(); // Clean buffer in case of errors
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
