<?php
require_once '../../onboarding/config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'User not logged in']);
    exit();
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON input']);
    exit();
}

$user_id = $_SESSION['user_id'];
$game_type = 'vocabworld';
$character_data = $input['characterData'] ?? [];
$selected_character = $input['selectedCharacter'] ?? null;

// Get username for database storage
$stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
$username = $user['username'] ?? '';

try {
    // Handle character selection storage
    if ($selected_character) {
        // Define character information
        $character_info = [
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
            ],
            'kael' => [
                'name' => 'Kael',
                'image_path' => '../assets/characters/kael_char/kael.png'
            ],
            'rex' => [
                'name' => 'Rex',
                'image_path' => '../assets/characters/rex_char/rex.png'
            ],
            'orion' => [
                'name' => 'Orion',
                'image_path' => '../assets/characters/orion_char/orion.png'
            ],
            'ember' => [
                'name' => 'Ember',
                'image_path' => '../assets/characters/ember_char/ember.png'
            ],
            'astra' => [
                'name' => 'Astra',
                'image_path' => '../assets/characters/astra_char/astra.png'
            ],
            'sylvi' => [
                'name' => 'Sylvi',
                'image_path' => '../assets/characters/sylvi_char/sylvi.png'
            ]
        ];
        
        if (isset($character_info[$selected_character])) {
            $char_data = $character_info[$selected_character];
            
            // Check if user already has a character selection
            $stmt = $pdo->prepare("SELECT id FROM character_selections WHERE user_id = ? AND game_type = ?");
            $stmt->execute([$user_id, $game_type]);
            $existing_selection = $stmt->fetch();
            
            if ($existing_selection) {
                // Update existing character selection (now storing character name in selected_character column)
                $stmt = $pdo->prepare("
                    UPDATE character_selections 
                    SET selected_character = ?, character_image_path = ?, username = ?, updated_at = NOW() 
                    WHERE user_id = ? AND game_type = ?
                ");
                $stmt->execute([
                    $char_data['name'],
                    $char_data['image_path'],
                    $username,
                    $user_id,
                    $game_type
                ]);
            } else {
                // Insert new character selection (now storing character name in selected_character column)
                $stmt = $pdo->prepare("
                    INSERT INTO character_selections (user_id, username, game_type, selected_character, character_image_path) 
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $user_id,
                    $username,
                    $game_type,
                    $char_data['name'],
                    $char_data['image_path']
                ]);
            }
        }
    }
    
    // Handle character customization data storage
    if (!empty($character_data)) {
        // Get existing progress or create new
        $stmt = $pdo->prepare("SELECT * FROM game_progress WHERE user_id = ? AND game_type = ?");
        $stmt->execute([$user_id, $game_type]);
        $progress = $stmt->fetch();
        
        if ($progress) {
            // Update existing progress
            $unlocked_levels = json_decode($progress['unlocked_levels'], true) ?? [];
            $unlocked_levels['character_customization'] = $character_data;
            
            $stmt = $pdo->prepare("
                UPDATE game_progress 
                SET unlocked_levels = ?, updated_at = NOW() 
                WHERE user_id = ? AND game_type = ?
            ");
            $stmt->execute([json_encode($unlocked_levels), $user_id, $game_type]);
        } else {
            // Create new progress record
            $progress_data = [
                'character_customization' => $character_data
            ];
            
            $stmt = $pdo->prepare("
                INSERT INTO game_progress (user_id, game_type, unlocked_levels, achievements, total_play_time, last_played) 
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $user_id, 
                $game_type, 
                json_encode($progress_data), 
                json_encode([]), 
                0
            ]);
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Character data saved successfully',
        'character_data' => $character_data,
        'selected_character' => $selected_character,
        'debug' => [
            'character_found' => isset($character_info[$selected_character]),
            'character_info' => $character_info[$selected_character] ?? 'Not found'
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
