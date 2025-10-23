<?php
require_once 'C:/xampp/htdocs/GameDev-G1/MainGame/vocabworld/vocabulary_data.php';
require_once 'C:/xampp/htdocs/GameDev-G1/onboarding/config.php';

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
    
    // Get a random word based on user's grade level
    $grade_level = $user['grade_level'] ?? 7; // Default to grade 7 if not set
    $words = VocabularyData::getWordsByGrade($grade_level);
    
    // Convert to array if it's not already (in case it returns an iterator)
    $words = array_values($words);
    
    error_log("Words found for grade $grade_level: " . count($words));
    
    if (empty($words)) {
        // Fallback to grade 7 words if no grade-specific words found
        error_log("No words found for grade $grade_level, falling back to grade 7");
        $words = VocabularyData::getWordsByGrade(7);
        $words = array_values($words);
    }
    
    if (empty($words)) {
        // Last resort: Return a grade 7 default question
        echo json_encode([
            'text' => 'What is the meaning of "abundant"?',
            'correct' => 'existing in large quantities; plentiful',
            'options' => ['existing in large quantities; plentiful', 'very small in size', 'difficult to understand', 'moving very slowly']
        ]);
        return;
    }
    
    // Select a random word
    $word = $words[array_rand($words)];
    
    error_log("Selected word: " . $word['word'] . " (Grade " . $word['grade'] . ")");
    
    // Create wrong options from other words in the same grade
    $wrong_options = [];
    $other_words = array_filter($words, function($w) use ($word) {
        return $w['word'] !== $word['word'];
    });
    $other_words = array_values($other_words);
    
    // Get 3 random wrong definitions
    shuffle($other_words);
    for ($i = 0; $i < min(3, count($other_words)); $i++) {
        $wrong_options[] = $other_words[$i]['definition'];
    }
    
    // If we don't have enough wrong options, add generic ones
    while (count($wrong_options) < 3) {
        $generic_options = [
            'very large in size',
            'moving very quickly',
            'difficult to understand',
            'easy to remember'
        ];
        $wrong_options[] = $generic_options[array_rand($generic_options)];
    }
    
    // Create options array with correct answer
    $options = array_merge([$word['definition']], array_slice($wrong_options, 0, 3));
    shuffle($options);
    
    echo json_encode([
        'text' => "What is the meaning of \"{$word['word']}\"?",
        'correct' => $word['definition'],
        'options' => $options
    ]);
}

function handlePostRequest() {
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
            
            $words = VocabularyData::getVocabularyWords();
            $filtered = [];
            
            foreach ($words as $word) {
                if (!empty($difficulties) && !in_array($word['difficulty'], $difficulties)) {
                    continue;
                }
                if (!empty($grades) && !in_array($word['grade'], $grades)) {
                    continue;
                }
                $filtered[] = $word;
            }
            
            // Shuffle and limit
            shuffle($filtered);
            $filtered = array_slice($filtered, 0, $count);
            
            echo json_encode([
                'success' => true,
                'data' => $filtered,
                'count' => count($filtered)
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
