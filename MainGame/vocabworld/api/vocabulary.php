<?php
require_once '../../vocabulary_data.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

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
    $action = $_GET['action'] ?? 'all';
    
    switch ($action) {
        case 'all':
            $words = VocabularyData::getVocabularyWords();
            echo json_encode([
                'success' => true,
                'data' => $words,
                'count' => count($words)
            ]);
            break;
            
        case 'difficulty':
            $difficulty = intval($_GET['level'] ?? 1);
            $words = VocabularyData::getWordsByDifficulty($difficulty);
            echo json_encode([
                'success' => true,
                'data' => $words,
                'count' => count($words),
                'difficulty' => $difficulty
            ]);
            break;
            
        case 'grade':
            $grade = intval($_GET['level'] ?? 7);
            $words = VocabularyData::getWordsByGrade($grade);
            echo json_encode([
                'success' => true,
                'data' => $words,
                'count' => count($words),
                'grade' => $grade
            ]);
            break;
            
        case 'random':
            $difficulty = isset($_GET['difficulty']) ? intval($_GET['difficulty']) : null;
            $grade = isset($_GET['grade']) ? intval($_GET['grade']) : null;
            $word = VocabularyData::getRandomWord($difficulty, $grade);
            
            if ($word) {
                echo json_encode([
                    'success' => true,
                    'data' => $word
                ]);
            } else {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'error' => 'No words found for the specified criteria'
                ]);
            }
            break;
            
        case 'search':
            $query = $_GET['q'] ?? '';
            if (empty($query)) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'Search query is required'
                ]);
                break;
            }
            
            $words = VocabularyData::searchWords($query);
            echo json_encode([
                'success' => true,
                'data' => $words,
                'count' => count($words),
                'query' => $query
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
