<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// List of possible config file locations to check
$possibleConfigPaths = [
    __DIR__ . '/../../onboarding/config.php',
    __DIR__ . '/../../../onboarding/config.php',
    '/home/wordweav/domains/wh1487294.ispot.cc/public_html/GameDev-G1/onboarding/config.php',
    dirname(dirname(__DIR__)) . '/onboarding/config.php'
];

// Find the config file
$configPath = '';
foreach ($possibleConfigPaths as $path) {
    if (file_exists($path)) {
        $configPath = $path;
        break;
    }
}

if (empty($configPath)) {
    http_response_code(500);
    die('Could not locate the config file. Tried: ' . implode(', ', $possibleConfigPaths));
}

require_once $configPath;

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

// Get user's grade level
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT grade_level FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            handleGetRequest();
            break;
        case 'POST':
            handlePostRequest();
            break;
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}

function handleGetRequest() {
    global $user, $pdo;
    
    // Debug: Log the user's grade level
    error_log("User grade level: " . ($user['grade_level'] ?? 'NOT SET'));
    
    // Get user's grade level (extract number from grade level string)
    $grade_level = $user['grade_level'] ?? '7';
    
    // Extract numeric grade from strings like "Grade 7", "7", etc.
    if (preg_match('/\d+/', $grade_level, $matches)) {
        $grade_level = $matches[0];
    }
    
    // Fetch questions from database based on user's grade level
    $stmt = $pdo->prepare("
        SELECT vq.id, vq.word, vq.definition, vq.difficulty, vq.grade_level
        FROM vocabulary_questions vq
        WHERE vq.is_active = 1 AND vq.grade_level = ?
        ORDER BY RAND()
        LIMIT 1
    ");
    $stmt->execute([$grade_level]);
    $question = $stmt->fetch();
    
    // If no questions found for this grade, try any grade
    if (!$question) {
        error_log("No questions found for grade $grade_level, trying any grade");
        $stmt = $pdo->prepare("
            SELECT vq.id, vq.word, vq.definition, vq.difficulty, vq.grade_level
            FROM vocabulary_questions vq
            WHERE vq.is_active = 1
            ORDER BY RAND()
            LIMIT 1
        ");
        $stmt->execute();
        $question = $stmt->fetch();
    }
    
    // If still no questions, return fallback
    if (!$question) {
        error_log("No questions found in database, using fallback");
        echo json_encode([
            'text' => 'What is the meaning of "abundant"?',
            'correct' => 'existing in large quantities; plentiful',
            'options' => ['existing in large quantities; plentiful', 'very small in size', 'difficult to understand', 'moving very slowly']
        ]);
        return;
    }
    
    error_log("Selected word: " . $question['word'] . " (Grade " . $question['grade_level'] . ")");
    
    // Get choices for this question
    $stmt = $pdo->prepare("
        SELECT choice_text, is_correct
        FROM vocabulary_choices
        WHERE question_id = ?
        ORDER BY id
    ");
    $stmt->execute([$question['id']]);
    $choices = $stmt->fetchAll();
    
    // If no choices found, create them from other questions
    if (empty($choices) || count($choices) < 4) {
        error_log("No choices found for question, generating from other definitions");
        
        // Get other definitions as wrong options
        $stmt = $pdo->prepare("
            SELECT definition
            FROM vocabulary_questions
            WHERE id != ? AND is_active = 1
            ORDER BY RAND()
            LIMIT 3
        ");
        $stmt->execute([$question['id']]);
        $wrong_options = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // If not enough wrong options, add generic ones
        while (count($wrong_options) < 3) {
            $generic_options = [
                'very large in size',
                'moving very quickly',
                'difficult to understand',
                'easy to remember'
            ];
            $wrong_options[] = $generic_options[array_rand($generic_options)];
        }
        
        // Create options array
        $options = array_merge([$question['definition']], array_slice($wrong_options, 0, 3));
        shuffle($options);
        
        echo json_encode([
            'text' => "What is the meaning of \"{$question['word']}\"?",
            'correct' => $question['definition'],
            'options' => $options
        ]);
        return;
    }
    
    // Use the choices from database
    $correct_answer = '';
    $all_options = [];
    
    foreach ($choices as $choice) {
        $all_options[] = $choice['choice_text'];
        if ($choice['is_correct']) {
            $correct_answer = $choice['choice_text'];
        }
    }
    
    // Shuffle options for randomness
    shuffle($all_options);
    
    echo json_encode([
        'text' => "What is the meaning of \"{$question['word']}\"?",
        'correct' => $correct_answer,
        'options' => $all_options
    ]);
}

function handlePostRequest() {
    global $pdo;
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Invalid JSON input'
        ]);
        return;
    }
    
    $action = $input['action'] ?? '';
    
    switch ($action) {
        case 'batch':
            $difficulties = $input['difficulties'] ?? [];
            $grades = $input['grades'] ?? [];
            $count = intval($input['count'] ?? 10);
            
            // Build query with filters
            $query = "SELECT vq.*, 
                      (SELECT GROUP_CONCAT(choice_text) FROM vocabulary_choices WHERE question_id = vq.id) as choices
                      FROM vocabulary_questions vq
                      WHERE vq.is_active = 1";
            $params = [];
            
            if (!empty($difficulties)) {
                $placeholders = str_repeat('?,', count($difficulties) - 1) . '?';
                $query .= " AND vq.difficulty IN ($placeholders)";
                $params = array_merge($params, $difficulties);
            }
            
            if (!empty($grades)) {
                $placeholders = str_repeat('?,', count($grades) - 1) . '?';
                $query .= " AND vq.grade_level IN ($placeholders)";
                $params = array_merge($params, $grades);
            }
            
            $query .= " ORDER BY RAND() LIMIT ?";
            $params[] = $count;
            
            $stmt = $pdo->prepare($query);
            $stmt->execute($params);
            $questions = $stmt->fetchAll();
            
            echo json_encode([
                'success' => true,
                'data' => $questions,
                'count' => count($questions)
            ]);
            break;
            
        default:
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Invalid action'
            ]);
            break;
    }
}
?>
