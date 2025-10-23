<?php
require_once 'C:/xampp/htdocs/GameDev-G1/onboarding/config.php';
require_once 'C:/xampp/htdocs/GameDev-G1/includes/greeting.php';
require_once 'C:/xampp/htdocs/GameDev-G1/MainGame/vocabworld/api/essence_manager.php';
require_once 'C:/xampp/htdocs/GameDev-G1/MainGame/vocabworld/api/level_manager.php';

// Check if user is logged in
requireLogin();

// Get user information
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Initialize Essence Manager
$essenceManager = new EssenceManager($pdo);
$current_essence = $essenceManager->getEssence($user_id);

// Initialize Level Manager
$levelManager = new LevelManager($pdo);
$level_data = $levelManager->getPlayerLevel($user_id);
$player_level = $level_data['level'];
$player_exp = $level_data['experience'];
$exp_to_next = $level_data['exp_to_next_level'];

// Get user's vocabworld progress
$stmt = $pdo->prepare("SELECT * FROM game_progress WHERE user_id = ? AND game_type = 'vocabworld'");
$stmt->execute([$user_id]);
$progress = $stmt->fetch();

// Get user's equipped character
$stmt = $pdo->prepare("
    SELECT cs.character_image_path 
    FROM character_selections cs 
    WHERE cs.user_id = ? 
    AND cs.game_type = 'vocabworld'
    ORDER BY cs.equipped_at DESC 
    LIMIT 1");
$stmt->execute([$user_id]);
$character = $stmt->fetch();

// Convert relative path to absolute path for the game
$default_character = 'assets/characters/boy_char/character_ethan.png';
$character_path = $character ? str_replace('../', '', $character['character_image_path']) : $default_character;

// Calculate GWA from recent scores
$stmt = $pdo->prepare("
    SELECT AVG(score) as avg_score 
    FROM game_scores 
    WHERE user_id = ? 
    AND game_type = 'vocabworld' 
    AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
$stmt->execute([$user_id]);
$avg_result = $stmt->fetch();
$current_gwa = $avg_result['avg_score'] ?? 0;

// Get user's vocabworld scores
$stmt = $pdo->prepare("SELECT * FROM game_scores WHERE user_id = ? AND game_type = 'vocabworld' ORDER BY created_at DESC LIMIT 10");
$stmt->execute([$user_id]);
$scores = $stmt->fetchAll();

// Calculate average percentage
$total_sessions = count($scores);
$average_percentage = 0;
if ($total_sessions > 0) {
    $total_score = array_sum(array_column($scores, 'score'));
    $max_possible_score = $total_sessions * 1000; // Assuming max 1000 points per session
    $average_percentage = round(($total_score / $max_possible_score) * 100, 1);
}

// Get character customization data
$character_data = null;
$user_shards = 0;
if ($progress && $progress['unlocked_levels']) {
    $character_data = json_decode($progress['unlocked_levels'], true);
    $user_shards = $character_data['current_points'] ?? 0;
}

// Get shard balance from new shard system - automatically create account if needed
require_once 'shard_manager.php';
$shardManager = new ShardManager($pdo);
$shard_result = $shardManager->ensureShardAccount($user_id);

if ($shard_result['success']) {
    $user_shards = $shard_result['shard_balance'];
    
    // Log if a new account was created (for debugging)
    if (!$shard_result['account_exists']) {
        error_log("VocabWorld: Created new shard account for user ID: $user_id with initial shards: $user_shards");
    }
} else {
    // Fallback to old system if new system fails
    $shard_balance = $shardManager->getShardBalance($user_id);
    if ($shard_balance) {
        $user_shards = $shard_balance['current_shards'];
    }
    error_log("VocabWorld: Shard account creation failed for user ID: $user_id - " . $shard_result['error']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VocabWorld - Word Weavers RPG</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="navigation/navigation.css">
    <link rel="stylesheet" href="../../notif/toast.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/phaser@3.60.0/dist/phaser.min.js"></script>
    <style>
        #game-container {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background-color: #000;
        }
        .game-ui {
            position: fixed;
            top: 15px;
            left: 15px;
            z-index: 1000;
            font-family: 'Poppins', sans-serif;
        }
        .player-stats {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .stat-item {
            display: flex;
            align-items: center;
            gap: 8px;
            background: linear-gradient(135deg, rgba(0, 0, 0, 0.85), rgba(20, 20, 40, 0.85));
            padding: 8px 15px;
            border-radius: 25px;
            border: 2px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.5);
            min-width: 150px;
            transition: all 0.3s ease;
        }
        .stat-item:hover {
            border-color: rgba(255, 255, 255, 0.4);
            transform: translateX(5px);
        }
        .stat-icon {
            width: 28px;
            height: 28px;
            object-fit: contain;
            filter: drop-shadow(0 0 4px rgba(255, 255, 255, 0.3));
        }
        .stat-label {
            font-size: 0.85rem;
            font-weight: 600;
            color: rgba(255, 255, 255, 0.8);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .stat-value {
            margin-left: auto;
            font-size: 1rem;
            font-weight: 700;
            color: #fff;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.5);
        }
        .stat-item.hp {
            padding: 8px 12px;
            min-width: 200px;
        }
        .hp-bar-container {
            flex: 1;
            height: 20px;
            background: rgba(0, 0, 0, 0.5);
            border-radius: 10px;
            overflow: hidden;
            border: 2px solid rgba(255, 107, 107, 0.3);
            position: relative;
        }
        .hp-bar-fill {
            height: 100%;
            background: linear-gradient(90deg, #ff6b6b, #ff4757);
            border-radius: 8px;
            transition: width 0.5s ease, background 0.3s ease;
            box-shadow: 0 0 10px rgba(255, 107, 107, 0.5);
            position: relative;
        }
        .hp-bar-fill::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 50%;
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.3), transparent);
            border-radius: 8px 8px 0 0;
        }
        .hp-text {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 0.75rem;
            font-weight: 700;
            color: white;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.8);
            z-index: 1;
        }
        /* HP bar color changes based on health */
        .hp-bar-fill.low {
            background: linear-gradient(90deg, #ffa502, #ff6348);
        }
        .hp-bar-fill.critical {
            background: linear-gradient(90deg, #ff4757, #c23616);
            animation: pulse 0.5s infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }
        .stat-item.level {
            padding: 8px 12px;
            min-width: 200px;
        }
        .level-bar-container {
            flex: 1;
            height: 20px;
            background: rgba(0, 0, 0, 0.5);
            border-radius: 10px;
            overflow: hidden;
            border: 2px solid rgba(255, 215, 0, 0.3);
            position: relative;
        }
        .level-bar-fill {
            height: 100%;
            background: linear-gradient(90deg, #ffd700, #ffed4e);
            border-radius: 8px;
            transition: width 0.5s ease;
            box-shadow: 0 0 10px rgba(255, 215, 0, 0.5);
            position: relative;
        }
        .level-bar-fill::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 50%;
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.3), transparent);
            border-radius: 8px 8px 0 0;
        }
        .level-text {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 0.75rem;
            font-weight: 700;
            color: white;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.8);
            z-index: 1;
        }
        .level-number {
            font-size: 0.9rem;
            font-weight: 700;
            color: #ffd700;
            min-width: 30px;
            text-align: center;
        }
        .stat-item.essence .stat-value {
            color: #a78bfa;
        }
        .stat-item.gwa .stat-value {
            color: #4ade80;
        }
        .battle-ui {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 1000;
            background: linear-gradient(135deg, rgba(30, 30, 60, 0.98), rgba(20, 20, 40, 0.98));
            padding: 30px;
            border-radius: 20px;
            border: 4px solid rgba(255, 215, 0, 0.6);
            box-shadow: 0 12px 48px rgba(0, 0, 0, 0.7), inset 0 1px 0 rgba(255, 255, 255, 0.1);
            color: white;
            width: 90%;
            max-width: 700px;
            font-family: 'Poppins', sans-serif;
        }
        .battle-title {
            font-size: 1.8rem;
            font-weight: 800;
            text-align: center;
            color: #ffd700;
            text-transform: uppercase;
            margin-bottom: 20px;
            text-shadow: 3px 3px 6px rgba(0, 0, 0, 0.8), 0 0 20px rgba(255, 215, 0, 0.5);
            letter-spacing: 2px;
        }
        .monster-display {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 20px;
            padding: 15px;
            background: rgba(0, 0, 0, 0.3);
            border-radius: 12px;
            border: 2px solid rgba(255, 255, 255, 0.1);
        }
        .monster-display img {
            width: 120px;
            height: 120px;
            object-fit: contain;
            filter: drop-shadow(0 0 20px rgba(74, 144, 226, 0.6));
            animation: monsterFloat 2s ease-in-out infinite;
        }
        @keyframes monsterFloat {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }
        .question-container h3 {
            font-size: 1.4rem;
            margin-bottom: 20px;
            text-align: center;
            color: #fff;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
            line-height: 1.6;
        }
        .answer-options {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
            margin-top: 15px;
        }
        .answer-btn {
            padding: 15px 20px;
            border: 2px solid rgba(74, 144, 226, 0.3);
            border-radius: 8px;
            background: linear-gradient(135deg, #4a90e2, #357abd);
            color: white;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(74, 144, 226, 0.3);
        }
        .answer-btn:hover:not(:disabled) {
            background: linear-gradient(135deg, #357abd, #2868a8);
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(74, 144, 226, 0.5);
        }
        .answer-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        /* Warning Modal */
        .warning-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            z-index: 10000;
            justify-content: center;
            align-items: center;
        }
        .warning-modal.active {
            display: flex;
        }
        .warning-content {
            background: linear-gradient(135deg, rgba(30, 30, 60, 0.98), rgba(20, 20, 40, 0.98));
            padding: 40px;
            border-radius: 20px;
            border: 4px solid rgba(255, 215, 0, 0.6);
            box-shadow: 0 12px 48px rgba(0, 0, 0, 0.7);
            text-align: center;
            max-width: 500px;
            color: white;
            font-family: 'Poppins', sans-serif;
        }
        .warning-content h2 {
            color: #ff6b6b;
            font-size: 1.8rem;
            margin-bottom: 20px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
        }
        .warning-content p {
            font-size: 1.1rem;
            margin-bottom: 30px;
            line-height: 1.6;
        }
        .warning-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
        }
        .warning-btn {
            padding: 12px 30px;
            font-size: 1rem;
            font-weight: 600;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .warning-btn.stay {
            background: linear-gradient(135deg, #4ade80, #22c55e);
            color: white;
        }
        .warning-btn.stay:hover {
            background: linear-gradient(135deg, #22c55e, #16a34a);
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(74, 222, 128, 0.5);
        }
        .warning-btn.leave {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
        }
        .warning-btn.leave:hover {
            background: linear-gradient(135deg, #dc2626, #b91c1c);
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(239, 68, 68, 0.5);
        }
        
        /* Victory Overview Screen */
        .victory-screen {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.95);
            z-index: 10000;
            justify-content: center;
            align-items: center;
        }
        .victory-screen.active {
            display: flex;
        }
        .victory-content {
            background: linear-gradient(135deg, rgba(30, 30, 60, 0.98), rgba(20, 20, 40, 0.98));
            padding: 50px;
            border-radius: 25px;
            border: 5px solid rgba(255, 215, 0, 0.8);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.9), 0 0 100px rgba(255, 215, 0, 0.3);
            text-align: center;
            max-width: 600px;
            color: white;
            font-family: 'Poppins', sans-serif;
            animation: victoryAppear 0.5s ease-out;
        }
        @keyframes victoryAppear {
            from {
                opacity: 0;
                transform: scale(0.8);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }
        .victory-content h1 {
            color: #ffd700;
            font-size: 2.5rem;
            margin-bottom: 10px;
            text-shadow: 3px 3px 6px rgba(0, 0, 0, 0.8), 0 0 30px rgba(255, 215, 0, 0.6);
            animation: glow 2s ease-in-out infinite;
        }
        @keyframes glow {
            0%, 100% { text-shadow: 3px 3px 6px rgba(0, 0, 0, 0.8), 0 0 30px rgba(255, 215, 0, 0.6); }
            50% { text-shadow: 3px 3px 6px rgba(0, 0, 0, 0.8), 0 0 50px rgba(255, 215, 0, 0.9); }
        }
        .victory-content h2 {
            color: #4ade80;
            font-size: 1.3rem;
            margin-bottom: 30px;
            font-weight: 400;
        }
        .stats-summary {
            background: rgba(0, 0, 0, 0.4);
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            border: 2px solid rgba(255, 255, 255, 0.1);
        }
        .stat-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            margin-bottom: 10px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            border-left: 4px solid;
        }
        .stat-row.exp {
            border-left-color: #ffd700;
        }
        .stat-row.essence {
            border-left-color: #a78bfa;
        }
        .stat-row.gwa {
            border-left-color: #4ade80;
        }
        .stat-row.level {
            border-left-color: #ff6b6b;
        }
        .victory-stat-label {
            font-size: 1.1rem;
            font-weight: 600;
            color: rgba(255, 255, 255, 0.9);
        }
        .victory-stat-value {
            font-size: 1.4rem;
            font-weight: 700;
        }
        .stat-row.exp .victory-stat-value {
            color: #ffd700;
        }
        .stat-row.essence .victory-stat-value {
            color: #a78bfa;
        }
        .stat-row.gwa .victory-stat-value {
            color: #4ade80;
        }
        .stat-row.level .victory-stat-value {
            color: #ff6b6b;
        }
        .victory-buttons {
            display: flex;
            gap: 20px;
            justify-content: center;
        }
        .victory-btn {
            padding: 15px 40px;
            font-size: 1.1rem;
            font-weight: 700;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
        }
        .victory-btn.play-again {
            background: linear-gradient(135deg, #4ade80, #22c55e);
            color: white;
        }
        .victory-btn.play-again:hover {
            background: linear-gradient(135deg, #22c55e, #16a34a);
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(74, 222, 128, 0.5);
        }
        .victory-btn.main-menu {
            background: linear-gradient(135deg, #4a90e2, #357abd);
            color: white;
        }
        .victory-btn.main-menu:hover {
            background: linear-gradient(135deg, #357abd, #2868a8);
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(74, 144, 226, 0.5);
        }
    </style>
</head>
<body>
    <!-- Victory Overview Screen -->
    <div class="victory-screen" id="victory-screen">
        <div class="victory-content">
            <h1>🎉 World Cleared! 🎉</h1>
            <h2>All Monsters Defeated!</h2>
            <div class="stats-summary">
                <div class="stat-row exp">
                    <span class="victory-stat-label">Total EXP Gained</span>
                    <span class="victory-stat-value" id="victory-exp">0</span>
                </div>
                <div class="stat-row essence">
                    <span class="victory-stat-label">Essence Earned</span>
                    <span class="victory-stat-value" id="victory-essence">0</span>
                </div>
                <div class="stat-row gwa">
                    <span class="victory-stat-label">Final GWA</span>
                    <span class="victory-stat-value" id="victory-gwa">0.00</span>
                </div>
            </div>
            <div class="victory-buttons">
                <button class="victory-btn play-again" onclick="playAgain()">Enter Again</button>
                <button class="victory-btn main-menu" onclick="goToMainMenu()">Main Menu</button>
            </div>
        </div>
    </div>

    <!-- Warning Modal -->
    <div class="warning-modal" id="warning-modal">
        <div class="warning-content">
            <h2>⚠️ Reset World?</h2>
            <p>Your current HP and progress in this session will be lost.</p>
            <p style="color: #4ade80; font-size: 0.95rem;">(XP, Essence, and GWA are already saved)</p>
            <div class="warning-buttons">
                <button class="warning-btn stay" onclick="closeWarningModal()">Stay in Game</button>
                <button class="warning-btn leave" onclick="confirmLeave()">Leave Anyway</button>
            </div>
        </div>
    </div>

    <div class="game-ui">
        <div class="player-stats">
            <div class="stat-item hp">
                <img src="assets/stats/heart.png" alt="HP" class="stat-icon">
                <div class="hp-bar-container">
                    <div class="hp-bar-fill" id="hp-bar-fill" style="width: 100%;">
                        <span class="hp-text" id="hp-text">100/100</span>
                    </div>
                </div>
                <span style="display: none;" id="player-hp">100</span>
            </div>
            <div class="stat-item level">
                <img src="assets/stats/level.png" alt="Level" class="stat-icon">
                <span class="level-number" id="player-level"><?php echo $player_level; ?></span>
                <div class="level-bar-container">
                    <div class="level-bar-fill" id="level-bar-fill" style="width: <?php echo ($player_exp / $exp_to_next) * 100; ?>%;">
                        <span class="level-text" id="level-text"><?php echo $player_exp; ?>/<?php echo $exp_to_next; ?></span>
                    </div>
                </div>
            </div>
            <div class="stat-item essence">
                <img src="assets/currency/essence.png" alt="Essence" class="stat-icon">
                <span class="stat-label">Essence</span>
                <span class="stat-value" id="player-essence"><?php echo $current_essence; ?></span>
            </div>
            <div class="stat-item gwa">
                <img src="assets/stats/gwa.png" alt="GWA" class="stat-icon">
                <span class="stat-label">GWA</span>
                <span class="stat-value" id="player-gwa"><?php echo number_format($current_gwa, 2); ?></span>
            </div>
        </div>
    </div>

    <div class="battle-ui" id="battle-ui">
        <div class="battle-title">⚔️ You Bumped a Monster! ⚔️</div>
        <div class="monster-display">
            <img id="battle-monster-img" src="assets/monsters/monster_test.png" alt="Monster">
        </div>
        <div class="question-container">
            <h3 id="question-text"></h3>
            <div class="answer-options" id="answer-options"></div>
        </div>
    </div>

    <div id="game-container"></div>

    <!-- Logout Confirmation Modal -->
    <div class="toast-overlay" id="logoutModal" style="display: none;">
        <div class="toast" id="logoutConfirmation">
            <h3 style="margin-bottom: 1rem; color: #ff6b6b;">Logout Confirmation</h3>
            <p style="margin-bottom: 1.5rem; color: rgba(255, 255, 255, 0.8);">Are you sure you want to logout?</p>
            <div style="display: flex; gap: 1rem; justify-content: center;">
                <button onclick="confirmLogout()" style="background: #ff6b6b; color: white; border: none; padding: 0.5rem 1rem; border-radius: 8px; cursor: pointer; font-family: 'Press Start 2P', cursive; font-size: 0.8rem;">Yes, Logout</button>
                <button onclick="hideLogoutModal()" style="background: rgba(255, 255, 255, 0.2); color: white; border: 1px solid rgba(255, 255, 255, 0.3); padding: 0.5rem 1rem; border-radius: 8px; cursor: pointer; font-family: 'Press Start 2P', cursive; font-size: 0.8rem;">Cancel</button>
            </div>
        </div>
    </div>

    <script>
        // Pass PHP data to JavaScript
        const characterPath = '<?php echo addslashes($character_path); ?>';

        // Game configuration
        const config = {
            type: Phaser.AUTO,
            width: 800,
            height: 600,
            parent: 'game-container',
            physics: {
                default: 'arcade',
                arcade: {
                    gravity: { y: 0 },
                    debug: false
                }
            },
            scene: {
                preload: preload,
                create: create,
                update: update
            }
        };

        // Initialize game
        const game = new Phaser.Game(config);
        let player;
        let cursors;
        let wasdKeys;
        let enemies;
        let inBattle = false;
        let currentEnemy = null;
        let battleUI;

        function preload() {
            // Load assets
            this.load.image('world', 'assets/maps/world_test.png');
            this.load.image('tiles', 'assets/tilesets/fantasy_tiles.png');
            this.load.image('player', characterPath); // Use player's equipped character
            
            // Load monster image
            this.load.image('monster', 'assets/monsters/monster_test.png');
            this.load.tilemapTiledJSON('map', 'assets/maps/world_map.json');
            
            // Add error handling for character image
            this.load.on('loaderror', (file) => {
                if (file.key === 'player') {
                    // If character image fails to load, use default
                    this.load.image('player', 'assets/characters/default_hero.png');
                    this.load.start(); // Restart loading for the default image
                }
            });
        }

        function create() {
            // Create world background
            const worldImage = this.add.image(400, 300, 'world');
            // Scale the world image to fit the game canvas
            const scaleX = 800 / worldImage.width;
            const scaleY = 600 / worldImage.height;
            worldImage.setScale(Math.max(scaleX, scaleY));

            // Create player
            player = this.physics.add.sprite(400, 300, 'player');
            player.setCollideWorldBounds(true);
            
            // Scale the player sprite to a reasonable size
            const targetHeight = 64; // Desired height in pixels
            const scale = targetHeight / player.height;
            player.setScale(scale);

            // Create enemies
            enemies = this.physics.add.group();
            
            // Create 3-5 random monsters
            const monsterCount = Phaser.Math.Between(3, 5);
            for (let i = 0; i < monsterCount; i++) {
                const x = Phaser.Math.Between(100, 700);
                const y = Phaser.Math.Between(100, 500);
                const enemy = enemies.create(x, y, 'monster');
                enemy.setCollideWorldBounds(true);
                
                // Scale monster to match character size (similar to player scaling)
                const monsterTargetHeight = 50; // Slightly smaller than player
                const monsterScale = monsterTargetHeight / enemy.height;
                enemy.setScale(monsterScale);
            }

            // Set up overlap detection (not collision) to prevent pushing monsters
            this.physics.add.overlap(player, enemies, startBattle, null, this);

            // Set up controls
            cursors = this.input.keyboard.createCursorKeys();
            
            // Add WASD keys
            wasdKeys = this.input.keyboard.addKeys({
                up: Phaser.Input.Keyboard.KeyCodes.W,
                down: Phaser.Input.Keyboard.KeyCodes.S,
                left: Phaser.Input.Keyboard.KeyCodes.A,
                right: Phaser.Input.Keyboard.KeyCodes.D
            });

            // Initialize battle UI
            battleUI = document.getElementById('battle-ui');
        }

        function update() {
            if (!inBattle) {
                // Player movement with both arrow keys and WASD
                const speed = 160;
                if (cursors.left.isDown || wasdKeys.left.isDown) {
                    player.setVelocityX(-speed);
                } else if (cursors.right.isDown || wasdKeys.right.isDown) {
                    player.setVelocityX(speed);
                } else {
                    player.setVelocityX(0);
                }

                if (cursors.up.isDown || wasdKeys.up.isDown) {
                    player.setVelocityY(-speed);
                } else if (cursors.down.isDown || wasdKeys.down.isDown) {
                    player.setVelocityY(speed);
                } else {
                    player.setVelocityY(0);
                }
            } else {
                // Stop player movement during battle
                player.setVelocity(0, 0);
            }
        }

        function startBattle(player, enemy) {
            if (!inBattle) {
                inBattle = true;
                currentEnemy = enemy;
                player.setVelocity(0, 0);
                
                showBattleUI();
                fetchVocabularyQuestion();
            }
        }

        function showBattleUI() {
            battleUI.style.display = 'block';
        }

        function hideBattleUI() {
            battleUI.style.display = 'none';
        }

        async function fetchVocabularyQuestion() {
            try {
                const response = await fetch('api/vocabulary.php');
                if (!response.ok) {
                    throw new Error('Failed to fetch question');
                }
                const question = await response.json();
                console.log('Question received:', question);
                displayQuestion(question);
            } catch (error) {
                console.error('Error fetching question:', error);
                // Fallback question if API fails
                displayQuestion({
                    text: 'What is the meaning of "Benevolent"?',
                    correct: 'Kind and generous',
                    options: ['Kind and generous', 'Angry and hostile', 'Tired and weak', 'Fast and strong']
                });
            }
        }

        function displayQuestion(question) {
            const questionText = document.getElementById('question-text');
            const answerOptions = document.getElementById('answer-options');
            
            console.log('Displaying question:', question);
            
            if (!question || !question.text || !question.options) {
                console.error('Invalid question data:', question);
                questionText.textContent = 'Error loading question. Please try again.';
                return;
            }
            
            questionText.textContent = question.text;
            answerOptions.innerHTML = '';

            question.options.forEach(option => {
                const button = document.createElement('button');
                button.className = 'answer-btn';
                button.textContent = option;
                button.onclick = () => checkAnswer(option, question.correct);
                answerOptions.appendChild(button);
            });
        }

        async function checkAnswer(selected, correct) {
            const isCorrect = selected === correct;
            
            // Disable all answer buttons to prevent multiple clicks
            const buttons = document.querySelectorAll('.answer-btn');
            buttons.forEach(btn => btn.disabled = true);
            
            if (isCorrect) {
                // Handle correct answer
                const essence = Math.floor(Math.random() * 6) + 5; // Random 5-10 essence
                tempEssenceGained += essence;
                await updateEssence(essence);
                await updateScore(100);
                
                // Award experience for correct answer
                const levelResult = await updateLevel(true);
                
                // Show success feedback with EXP
                let message = '✓ Correct! Monster defeated! +' + levelResult.exp_gained + ' EXP';
                if (levelResult.leveled_up) {
                    message += '<br><span style="color: #ffd700; font-size: 1.2em;">🎉 LEVEL UP! You are now Level ' + levelResult.new_level + '!</span>';
                }
                document.getElementById('question-text').innerHTML = '<span style="color: #4ade80;">' + message + '</span>';
                
                // Update level display and progress bar
                updateLevelBar(levelResult);
                
                // Wait a moment before destroying monster
                await new Promise(resolve => setTimeout(resolve, levelResult.leveled_up ? 2500 : 1000));
            } else {
                // Handle wrong answer - lose health
                const hpDisplay = document.getElementById('player-hp');
                let currentHP = parseInt(hpDisplay.textContent);
                const maxHP = 100;
                const damage = Math.floor(Math.random() * 16) + 10; // Random damage between 10-25 HP
                currentHP = Math.max(0, currentHP - damage);
                hpDisplay.textContent = currentHP;
                
                // Update HP bar
                updateHPBar(currentHP, maxHP);
                
                // Award small experience for participation
                const levelResult = await updateLevel(false);
                
                // Show damage feedback with EXP
                let message = '✗ Wrong answer! You lost ' + damage + ' HP! +' + levelResult.exp_gained + ' EXP';
                if (levelResult.leveled_up) {
                    message += '<br><span style="color: #ffd700;">🎉 LEVEL UP! You are now Level ' + levelResult.new_level + '!</span>';
                }
                document.getElementById('question-text').innerHTML = '<span style="color: #f87171;">' + message + '</span>';
                
                // Update level display and progress bar
                updateLevelBar(levelResult);
                
                // Wait before continuing
                await new Promise(resolve => setTimeout(resolve, levelResult.leveled_up ? 2500 : 1500));
                
                // Check if player is defeated
                if (currentHP <= 0) {
                    alert('Game Over! You ran out of health.');
                    window.location.href = 'index.php';
                    return;
                }
            }
            
        function updateHPBar(currentHP, maxHP) {
            const hpBarFill = document.getElementById('hp-bar-fill');
            const hpText = document.getElementById('hp-text');
            const percentage = (currentHP / maxHP) * 100;
            
            // Update bar width
            hpBarFill.style.width = percentage + '%';
            
            // Update text
            hpText.textContent = currentHP + '/' + maxHP;
            
            // Change color based on HP percentage
            hpBarFill.classList.remove('low', 'critical');
            if (percentage <= 30) {
                hpBarFill.classList.add('critical');
            } else if (percentage <= 50) {
                hpBarFill.classList.add('low');
            }
        }
        
        function updateLevelBar(levelResult) {
            const levelBarFill = document.getElementById('level-bar-fill');
            const levelText = document.getElementById('level-text');
            const levelNumber = document.getElementById('player-level');
            
            // Update level number if leveled up
            if (levelResult.leveled_up) {
                levelNumber.textContent = levelResult.new_level;
            }
            
            // Calculate percentage for progress bar
            const currentExp = levelResult.current_exp;
            const expToNext = levelResult.exp_to_next_level;
            const percentage = (currentExp / expToNext) * 100;
            
            // Update bar width
            levelBarFill.style.width = percentage + '%';
            
            // Update text
            levelText.textContent = currentExp + '/' + expToNext;
        }

            // Destroy monster in both cases (correct or wrong answer)
            if (currentEnemy) {
                currentEnemy.destroy();
            }

            // End battle
            inBattle = false;
            hideBattleUI();
            
            if (enemies.getChildren().length === 0) {
                // Level complete
                showVictoryScreen();
            }
        }

        // Track temporary level progress (not saved to DB until session ends properly)
        let tempExpGained = 0;
        let tempLevelUps = 0;
        let tempEssenceGained = 0;
        let initialLevel = parseInt(document.getElementById('player-level').textContent);
        let initialGWA = parseFloat(document.getElementById('player-gwa').textContent);
        
        async function updateLevel(isCorrect) {
            // Calculate EXP locally without saving to database
            const expGain = isCorrect ? 25 : 5;
            tempExpGained += expGain;
            
            // Get current level info from display
            const currentLevel = parseInt(document.getElementById('player-level').textContent);
            const levelText = document.getElementById('level-text').textContent;
            const [currentExp, expNeeded] = levelText.split('/').map(Number);
            
            let newExp = currentExp + expGain;
            let newLevel = currentLevel;
            let leveled_up = false;
            
            // Check for level up
            if (newExp >= expNeeded) {
                newExp = newExp - expNeeded;
                newLevel++;
                leveled_up = true;
                tempLevelUps++;
            }
            
            // Calculate new exp needed for next level
            const newExpNeeded = 50 * newLevel;
            
            return {
                leveled_up: leveled_up,
                new_level: newLevel,
                old_level: currentLevel,
                exp_gained: expGain,
                current_exp: newExp,
                exp_to_next_level: newExpNeeded
            };
        }
        
        // Save level progress to database (only called on proper game end)
        async function saveLevelProgress() {
            if (tempExpGained === 0) return;
            
            try {
                const response = await fetch('api/update_level.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ 
                        action: 'add_exp',
                        exp_amount: tempExpGained,
                        is_correct: true // Just to pass validation
                    })
                });
                
                const result = await response.json();
                console.log('Level progress saved:', result);
            } catch (error) {
                console.error('Error saving level progress:', error);
            }
        }

        async function updateEssence(amount) {
            try {
                await fetch('api/update_essence.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ amount })
                });
                
                const essenceDisplay = document.getElementById('player-essence');
                essenceDisplay.textContent = parseInt(essenceDisplay.textContent) + amount;
            } catch (error) {
                console.error('Error updating essence:', error);
            }
        }

        async function updateScore(score) {
            try {
                await fetch('api/save_score.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ score })
                });
            } catch (error) {
                console.error('Error saving score:', error);
            }
        }

        async function showVictoryScreen() {
            // Save level progress before showing victory screen
            await saveLevelProgress();
            
            // Calculate final stats
            const currentGWA = parseFloat(document.getElementById('player-gwa').textContent);
            
            // Update victory screen with stats
            document.getElementById('victory-exp').textContent = tempExpGained;
            document.getElementById('victory-essence').textContent = tempEssenceGained;
            document.getElementById('victory-gwa').textContent = currentGWA.toFixed(2);
            
            // Show victory screen
            document.getElementById('victory-screen').classList.add('active');
            
            // Disable game session warning since we're done
            inGameSession = false;
        }
        
        function playAgain() {
            allowLeave = true;
            location.reload();
        }
        
        function goToMainMenu() {
            allowLeave = true;
            window.location.href = 'index.php';
        }

        // Track if user is in an active game session
        let inGameSession = false;
        let allowLeave = false;
        let modalShown = false;
        
        // Mark session as active when game starts (after first interaction)
        document.addEventListener('keydown', function(e) {
            if (!inGameSession && (e.key.startsWith('Arrow') || ['w', 'a', 's', 'd'].includes(e.key.toLowerCase()))) {
                inGameSession = true;
            }
            
            // Intercept F5 and Ctrl+R (refresh shortcuts)
            if (inGameSession && !allowLeave && !modalShown) {
                if (e.key === 'F5' || (e.ctrlKey && e.key === 'r')) {
                    e.preventDefault();
                    showWarningModal();
                    return false;
                }
            }
        });

        // Add beforeunload event listener to show browser confirmation
        // This shows when user clicks browser refresh button or closes tab
        window.addEventListener('beforeunload', function(e) {
            if (inGameSession && !allowLeave) {
                // This triggers the browser's default "Reload site?" message
                e.preventDefault();
                e.returnValue = '';
                return '';
            }
        });
        
        // Custom warning modal functions
        function showWarningModal() {
            modalShown = true;
            document.getElementById('warning-modal').classList.add('active');
        }
        
        function closeWarningModal() {
            modalShown = false;
            document.getElementById('warning-modal').classList.remove('active');
        }
        
        function confirmLeave() {
            allowLeave = true;
            location.reload();
        }
        
        // Intercept back button or navigation attempts
        window.addEventListener('popstate', function(e) {
            if (inGameSession && !allowLeave && !modalShown) {
                showWarningModal();
                history.pushState(null, null, window.location.href);
            }
        });
        
        // Push initial state to enable popstate detection
        history.pushState(null, null, window.location.href);
    </script>
</body>
</html>



