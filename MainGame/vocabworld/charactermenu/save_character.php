<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set JSON content type
header('Content-Type: application/json');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include config file
require_once '../../../onboarding/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'error' => 'User not logged in',
        'session' => $_SESSION
    ]);
    exit;
}

$user_id = $_SESSION['user_id'];

// Log incoming request
error_log('Request method: ' . $_SERVER['REQUEST_METHOD']);
error_log('POST data: ' . print_r($_POST, true));
error_log('Raw input: ' . file_get_contents('php://input'));

// Check if request is using POST method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode([
        'success' => false, 
        'error' => 'Invalid request method',
        'debug' => [
            'method' => $_SERVER['REQUEST_METHOD'],
            'post_data' => $_POST,
            'content_type' => $_SERVER['CONTENT_TYPE'] ?? 'not set',
            'session_id' => session_id(),
            'user_id' => $user_id ?? 'not set'
        ]
    ]);
    exit;
}

// Get username for database storage
try {
    $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    if (!$user) {
        throw new Exception('User not found');
    }
    
    $username = $user['username'];
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage(),
        'debug' => [
            'user_id' => $user_id,
            'session' => $_SESSION
        ]
    ]);
    exit;
}

// Check if selectedCharacter is provided
if (!isset($_POST['selectedCharacter'])) {
    $rawInput = file_get_contents('php://input');
    error_log('Missing selectedCharacter. POST: ' . print_r($_POST, true) . ', Raw input: ' . $rawInput);
    
    http_response_code(400); // Bad Request
    echo json_encode([
        'success' => false, 
        'error' => 'Missing character selection',
        'debug' => [
            'post_data' => $_POST,
            'raw_input' => $rawInput,
            'content_type' => $_SERVER['CONTENT_TYPE'] ?? 'not set',
            'session_id' => session_id()
        ]
    ]);
    exit;
}

$selectedCharacter = trim($_POST['selectedCharacter']);
error_log('Processing character selection: ' . $selectedCharacter);

// Validate character type
$validCharacters = ['boy', 'girl', 'amber'];
if (!in_array($selectedCharacter, $validCharacters, true)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Invalid character type',
        'debug' => [
            'selected_character' => $selectedCharacter,
            'valid_characters' => $validCharacters
        ]
    ]);
    exit;
}

// Character definitions with their full details
$characterDefinitions = [
    'boy' => [
        'name' => 'Ethan',
        'image_path' => '../assets/characters/boy_char/character_ethan.png'
    ],
    'girl' => [
        'name' => 'Emma',
        'image_path' => '../assets/characters/girl_char/character_emma.png'
    ],
    'amber' => [
        'name' => 'Amber',
        'image_path' => '../assets/characters/amber_char/amber.png'
    ]
];

$characterDef = $characterDefinitions[$selectedCharacter];

try {
    // Check if user owns this character
    $stmt = $pdo->prepare("SELECT * FROM character_ownership WHERE user_id = ? AND character_type = ?");
    $stmt->execute([$user_id, $selectedCharacter]);
    $ownership = $stmt->fetch();

    if (!$ownership) {
        http_response_code(403); // Forbidden
        echo json_encode([
            'success' => false, 
            'error' => 'You do not own this character',
            'debug' => [
                'user_id' => $user_id,
                'character' => $selectedCharacter
            ]
        ]);
        exit;
    }

    // Update or insert character selection
    $stmt = $pdo->prepare(
        "INSERT INTO character_selections 
        (user_id, username, game_type, selected_character, character_image_path) 
        VALUES (?, ?, 'vocabworld', ?, ?) 
        ON DUPLICATE KEY UPDATE 
            selected_character = VALUES(selected_character), 
            character_image_path = VALUES(character_image_path), 
            username = VALUES(username), 
            updated_at = CURRENT_TIMESTAMP"
    );
    
    $stmt->execute([
        $user_id, 
        $username, 
        $characterDef['name'], 
        $characterDef['image_path']
    ]);
    
    // Log successful update
    error_log("Successfully updated character selection for user {$user_id} to {$selectedCharacter}");
    
    echo json_encode([
        'success' => true,
        'message' => 'Character selection saved successfully',
        'character' => [
            'type' => $selectedCharacter,
            'name' => $characterDef['name'],
            'image' => $characterDef['image_path']
        ]
    ]);
    
} catch (Exception $e) {
    error_log('Database error in save_character.php: ' . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'error' => 'Database error',
        'debug' => [
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]
    ]);
}
?>
