<?php
require_once '../../../onboarding/config.php';

// Check if user is logged in
requireLogin();

// Get user information
$user_id = $_SESSION['user_id'];

// Get username for database storage
$stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
$username = $user['username'] ?? '';

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['selectedCharacter'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid request data']);
    exit;
}

$selectedCharacter = $input['selectedCharacter'];

// Character definitions
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

// Validate character type
if (!isset($characterDefinitions[$selectedCharacter])) {
    echo json_encode(['success' => false, 'error' => 'Invalid character type']);
    exit;
}

$characterDef = $characterDefinitions[$selectedCharacter];

// Check if user owns this character
$stmt = $pdo->prepare("SELECT * FROM character_ownership WHERE user_id = ? AND character_type = ?");
$stmt->execute([$user_id, $selectedCharacter]);
$ownership = $stmt->fetch();

if (!$ownership) {
    echo json_encode(['success' => false, 'error' => 'You do not own this character']);
    exit;
}

try {
    // Update or insert character selection (now storing character name in selected_character column)
    $stmt = $pdo->prepare("INSERT INTO character_selections (user_id, username, game_type, selected_character, character_image_path) VALUES (?, ?, 'vocabworld', ?, ?) ON DUPLICATE KEY UPDATE selected_character = VALUES(selected_character), character_image_path = VALUES(character_image_path), username = VALUES(username), updated_at = CURRENT_TIMESTAMP");
    $stmt->execute([$user_id, $username, $characterDef['name'], $characterDef['image_path']]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Character selection saved successfully'
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>
