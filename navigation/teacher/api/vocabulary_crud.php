<?php
require_once '../../../onboarding/config.php';

// Check if user is logged in and has teacher/admin access
$gradeLevel = $_SESSION['grade_level'] ?? '';
$isTeacherOrAdmin = in_array(strtolower($gradeLevel), array_map('strtolower', ['Teacher', 'Admin', 'Developer']));

if (!function_exists('isLoggedIn') || !isLoggedIn() || !$isTeacherOrAdmin) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

$user_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';

header('Content-Type: application/json');

try {
    switch ($action) {
        case 'add':
            // Add new vocabulary question
            $word = trim($_POST['word'] ?? '');
            $definition = trim($_POST['definition'] ?? '');
            $example = trim($_POST['example'] ?? '');
            $grade_level = $_POST['grade_level'] ?? '';
            $difficulty = intval($_POST['difficulty'] ?? 1);
            $choices = $_POST['choices'] ?? [];
            $correct_choice = intval($_POST['correct_choice'] ?? 0);

            if (empty($word) || empty($definition) || empty($grade_level) || count($choices) !== 4) {
                throw new Exception('Please fill in all required fields and provide 4 choices');
            }

            // Insert vocabulary question
            $stmt = $pdo->prepare("
                INSERT INTO vocabulary_questions (word, definition, example_sentence, difficulty, grade_level, created_by)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$word, $definition, $example, $difficulty, $grade_level, $user_id]);
            $question_id = $pdo->lastInsertId();

            // Insert choices
            $stmt = $pdo->prepare("
                INSERT INTO vocabulary_choices (question_id, choice_text, is_correct)
                VALUES (?, ?, ?)
            ");

            foreach ($choices as $index => $choice_text) {
                $is_correct = ($index == $correct_choice) ? 1 : 0;
                $stmt->execute([$question_id, trim($choice_text), $is_correct]);
            }

            echo json_encode(['success' => true, 'message' => 'Vocabulary added successfully']);
            break;

        case 'edit':
            // Edit existing vocabulary question
            $vocab_id = intval($_POST['vocab_id'] ?? 0);
            $word = trim($_POST['word'] ?? '');
            $definition = trim($_POST['definition'] ?? '');
            $example = trim($_POST['example'] ?? '');
            $grade_level = $_POST['grade_level'] ?? '';
            $difficulty = intval($_POST['difficulty'] ?? 1);
            $choices = $_POST['choices'] ?? [];
            $correct_choice = intval($_POST['correct_choice'] ?? 0);

            if (empty($word) || empty($definition) || empty($grade_level) || count($choices) !== 4) {
                throw new Exception('Please fill in all required fields and provide 4 choices');
            }

            // Update vocabulary question
            $stmt = $pdo->prepare("
                UPDATE vocabulary_questions 
                SET word = ?, definition = ?, example_sentence = ?, difficulty = ?, grade_level = ?, updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ");
            $stmt->execute([$word, $definition, $example, $difficulty, $grade_level, $vocab_id]);

            // Delete old choices
            $stmt = $pdo->prepare("DELETE FROM vocabulary_choices WHERE question_id = ?");
            $stmt->execute([$vocab_id]);

            // Insert new choices
            $stmt = $pdo->prepare("
                INSERT INTO vocabulary_choices (question_id, choice_text, is_correct)
                VALUES (?, ?, ?)
            ");

            foreach ($choices as $index => $choice_text) {
                $is_correct = ($index == $correct_choice) ? 1 : 0;
                $stmt->execute([$vocab_id, trim($choice_text), $is_correct]);
            }

            echo json_encode(['success' => true, 'message' => 'Vocabulary updated successfully']);
            break;

        case 'delete':
            // Delete vocabulary question (soft delete)
            $vocab_id = intval($_POST['vocab_id'] ?? 0);

            $stmt = $pdo->prepare("UPDATE vocabulary_questions SET is_active = 0 WHERE id = ?");
            $stmt->execute([$vocab_id]);

            echo json_encode(['success' => true, 'message' => 'Vocabulary deleted successfully']);
            break;

        case 'get':
            // Get single vocabulary question with choices
            $vocab_id = intval($_GET['vocab_id'] ?? 0);

            $stmt = $pdo->prepare("
                SELECT * FROM vocabulary_questions WHERE id = ? AND is_active = 1
            ");
            $stmt->execute([$vocab_id]);
            $question = $stmt->fetch();

            if (!$question) {
                throw new Exception('Vocabulary not found');
            }

            // Get choices
            $stmt = $pdo->prepare("
                SELECT * FROM vocabulary_choices WHERE question_id = ? ORDER BY id
            ");
            $stmt->execute([$vocab_id]);
            $choices = $stmt->fetchAll();

            echo json_encode([
                'success' => true,
                'question' => $question,
                'choices' => $choices
            ]);
            break;

        default:
            throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
