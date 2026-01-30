<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define the base path relative to the current file's location
$basePath = dirname(dirname(__DIR__)); // This should point to the GameDev-G1 directory

// List of possible config file locations to check
$possibleConfigPaths = [
    $basePath . '/onboarding/config.php',
    dirname($basePath) . '/onboarding/config.php',
    __DIR__ . '/../../onboarding/config.php',
    '/home/wordweav/domains/wh1487294.ispot.cc/public_html/GameDev-G1/onboarding/config.php',
    'onboarding/config.php',
    '../onboarding/config.php',
    '../../onboarding/config.php'
];

// Find the config file
$configPath = '';
foreach ($possibleConfigPaths as $path) {
    if (file_exists($path)) {
        $configPath = $path;
        $basePath = dirname(dirname($path)); // Set base path to GameDev-G1 directory
        break;
    }
}

if (empty($configPath)) {
    die('Could not locate the config file. Tried the following paths:<br>' . 
        implode('<br>', array_map('htmlspecialchars', $possibleConfigPaths)));
}

// Include required files
$requiredFiles = [
    $configPath,
    $basePath . '/includes/greeting.php',
    __DIR__ . '/api/essence_manager.php',
    __DIR__ . '/api/level_manager.php'
];

foreach ($requiredFiles as $file) {
    if (!file_exists($file)) {
        die("Required file not found: " . htmlspecialchars($file));
    }
    require_once $file;
}

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

// Note: game_scores table has been removed
// Note: game_scores table has been removed
$current_gwa = $player_level * 1.5;

// Note: game_scores table has been removed
$total_sessions = 0;
$average_percentage = 0;

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

// Check game access permission
$game_access_allowed = true;
$user_grade = $user['grade_level'] ?? '';

// Only check for students (non-staff)
if (!in_array($user_grade, ['Teacher', 'Admin', 'Developer'])) {
    // Extract just the number if grade matches "Grade 7" format, or use as is if just "7"
    // Assuming database stores "7", "8" etc. based on previous context, but user table might have "Grade 7".
    // Let's try to parse integer from the string to be safe, or match exact string if needed.
    // Based on game_access_controls.sql which uses INT, we need the number.
    $grade_num = (int)filter_var($user_grade, FILTER_SANITIZE_NUMBER_INT);
    
    if ($grade_num > 0) {
        $stmt = $pdo->prepare("SELECT is_enabled FROM game_access_controls WHERE grade_level = ?");
        $stmt->execute([$grade_num]);
        $access_control = $stmt->fetch();

        // If record exists and is_enabled is 0, deny access
        if ($access_control && $access_control['is_enabled'] == 0) {
            $game_access_allowed = false;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/webp" href="assets/menu/vv_logo.webp">
    <title>Elmvale - Tutorial</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="game.css">
    <link rel="stylesheet" href="navigation/navigation.css">
    <link rel="stylesheet" href="../../notif/toast.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/phaser@3.60.0/dist/phaser.min.js"></script>
</head>
<body>
    <?php include 'loaders/loader-component.php'; ?>
    <?php include 'loaders/phaser-loader.php'; ?>
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
            <p style="color: #4ade80; font-size: 0.95rem;">(Essence is saved, but XP is lost)</p>
            <div class="warning-buttons">
                <button class="warning-btn stay" onclick="closeWarningModal()">Stay in Game</button>
                <button class="warning-btn leave" onclick="confirmLeave()">Leave Anyway</button>
            </div>
        </div>
    </div>

    <!-- Access Denied Modal -->
    <div class="warning-modal" id="access-denied-modal" style="z-index: 2000;">
        <div class="warning-content">
            <h2>⛔ Not Started Yet</h2>
            <p>Please wait for your teacher to begin the game or study some lessons.</p>
            <div class="warning-buttons">
                <!-- Button to Study -->
                <button class="warning-btn stay" onclick="window.location.href='learnvocabmenu/learn.php'">Study</button>
                <!-- Button to Exit -->
                <button class="warning-btn leave" onclick="window.location.href='../../menu.php'">Okay</button>
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
            <div class="stat-item essence-gwa">
                <div class="stat-sub-item">
                    <img src="assets/currency/essence.png" alt="Essence" class="stat-icon">
                    <span class="stat-value" id="player-essence"><?php echo $current_essence; ?></span>
                </div>
                <div class="stat-divider"></div>
                <div class="stat-sub-item">
                    <img src="assets/stats/gwa.png" alt="GWA" class="stat-icon">
                    <span class="stat-value" id="player-gwa"><?php echo number_format($current_gwa, 2); ?></span>
                </div>
            </div>
        </div>
        <!-- Mobile Back Button -->
        <button class="mobile-back-btn" id="mobile-back-btn" onclick="handleMobileBack()">
            <i class="fas fa-arrow-left"></i>
        </button>
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

    <!-- Mobile Joystick -->
    <div class="joystick-container" id="joystick-container">
        <div class="joystick-base">
            <div class="joystick-stick" id="joystick-stick"></div>
        </div>
    </div>

    <!-- Logout Confirmation Modal -->
    <div class="toast-overlay" id="logoutModal">
        <div class="toast" id="logoutConfirmation">
            <h3 class="toast-title">Logout Confirmation</h3>
            <p class="toast-message">Are you sure you want to logout?</p>
            <div class="toast-buttons">
                <button class="toast-btn toast-btn-confirm" onclick="confirmLogout()">Yes, Logout</button>
                <button class="toast-btn toast-btn-cancel" onclick="hideLogoutModal()">Cancel</button>
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
            render: {
                pixelArt: true,  // This tells Phaser to handle pixel art better
                antialias: false // Disables anti-aliasing
            },
            scene: {
                preload: preload,
                create: create,
                update: update
            }
        };

        // Access Control Flag from PHP
        const isGameAllowed = <?php echo $game_access_allowed ? 'true' : 'false'; ?>;

        // Initialize game variable
        let game = null;

        if (isGameAllowed) {
            game = new Phaser.Game(config);
        } else {
            // Show access denied modal immediately
            document.addEventListener('DOMContentLoaded', () => {
                document.getElementById('access-denied-modal').classList.add('active');
            });
        }
        
        
        let player;
        let cursors;
        let wasdKeys;
        let enemies;
        let inBattle = false;
        let currentEnemy = null;
        let battleUI;

        // Joystick variables
        let joystickActive = false;
        let joystickDirection = { x: 0, y: 0 };

        // Initialize joystick for mobile
        function initJoystick() {
            const joystickContainer = document.getElementById('joystick-container');
            const joystickStick = document.getElementById('joystick-stick');
            const joystickBase = joystickContainer.querySelector('.joystick-base');
            
            if (!joystickContainer || !joystickStick || !joystickBase) return;
            
            const baseRect = joystickBase.getBoundingClientRect();
            const maxDistance = 35; // Maximum distance the stick can move from center
            
            let isDragging = false;
            let startX = 0;
            let startY = 0;
            
            function handleStart(e) {
                if (inBattle) return;
                isDragging = true;
                joystickActive = true;
                
                const touch = e.touches ? e.touches[0] : e;
                startX = touch.clientX;
                startY = touch.clientY;
            }
            
            function handleMove(e) {
                if (!isDragging || inBattle) return;
                e.preventDefault();
                
                const touch = e.touches ? e.touches[0] : e;
                const deltaX = touch.clientX - startX;
                const deltaY = touch.clientY - startY;
                
                // Calculate distance from center
                const distance = Math.sqrt(deltaX * deltaX + deltaY * deltaY);
                
                // Limit the stick movement
                let moveX = deltaX;
                let moveY = deltaY;
                
                if (distance > maxDistance) {
                    const angle = Math.atan2(deltaY, deltaX);
                    moveX = Math.cos(angle) * maxDistance;
                    moveY = Math.sin(angle) * maxDistance;
                }
                
                // Update stick position
                joystickStick.style.transform = `translate(${moveX}px, ${moveY}px)`;
                
                // Update direction for game movement
                joystickDirection.x = moveX / maxDistance;
                joystickDirection.y = moveY / maxDistance;
            }
            
            function handleEnd() {
                isDragging = false;
                joystickActive = false;
                joystickStick.style.transform = 'translate(0, 0)';
                joystickDirection = { x: 0, y: 0 };
            }
            
            // Touch events
            joystickBase.addEventListener('touchstart', handleStart, { passive: false });
            document.addEventListener('touchmove', handleMove, { passive: false });
            document.addEventListener('touchend', handleEnd);
            document.addEventListener('touchcancel', handleEnd);
            
            // Mouse events for testing on desktop
            joystickBase.addEventListener('mousedown', handleStart);
            document.addEventListener('mousemove', handleMove);
            document.addEventListener('mouseup', handleEnd);
        }

        // Initialize joystick when DOM is ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initJoystick);
        } else {
            initJoystick();
        }

        function preload() {
            // Load assets
            this.load.image('world', 'assets/maps/world_test.png');
            // Commenting out missing assets to avoid 404s
            // this.load.image('tiles', 'assets/tilesets/fantasy_tiles.png');
            
            // Check if character has a spritesheet version
            let spritesheetPath = null;
            if (characterPath.includes('character_ethan.png')) {
                spritesheetPath = characterPath.replace('character_ethan.png', 'character_ethan_frame32x32.png');
            } else if (characterPath.includes('amber.png')) {
                spritesheetPath = characterPath.replace('amber.png', 'character_6_frame32x32.png');
            } else if (characterPath.includes('character_emma.png')) {
                spritesheetPath = characterPath.replace('character_emma.png', 'character_emma_frame32x32.png');
            }

            if (spritesheetPath) {
                this.load.spritesheet('player', spritesheetPath, { frameWidth: 32, frameHeight: 32 });
            } else {
                this.load.image('player', characterPath); // Use player's equipped character
            }
            
            // Load monster image
            this.load.image('monster', 'assets/monsters/monster_test.png');
            // Commenting out missing assets to avoid 404s
            // this.load.tilemapTiledJSON('map', 'assets/maps/world_map.json');
            
            // Add error handling for character image
            this.load.on('loaderror', (file) => {
                if (file.key === 'player') {
                    // If character image fails to load, use default
                    this.load.image('player', 'assets/characters/boy_char/character_ethan.png');
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
            
            // Define animations if character is animated
            const isAnimated = characterPath.includes('character_ethan.png') || 
                               characterPath.includes('amber.png') || 
                               characterPath.includes('character_emma.png');

            if (isAnimated) {
                const anims = this.anims;
                const config = [
                    { key: 'down', start: 0, end: 2 },
                    { key: 'left', start: 3, end: 5 },
                    { key: 'right', start: 6, end: 8 },
                    { key: 'up', start: 9, end: 11 }
                ];
                
                config.forEach(anim => {
                    anims.create({
                        key: anim.key,
                        frames: anims.generateFrameNumbers('player', { start: anim.start, end: anim.end }),
                        frameRate: 10,
                        repeat: -1
                    });
                });
            }
            
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

            // Hide the Phaser loader once everything is created
            if (typeof hidePhaserLoader === 'function') {
                hidePhaserLoader();
            }
        }

        function update() {
            if (!inBattle) {
                const speed = 160;
                let velocityX = 0;
                let velocityY = 0;
                
                // Keyboard controls (Arrow keys and WASD)
                if (cursors.left.isDown || wasdKeys.left.isDown) {
                    velocityX = -speed;
                } else if (cursors.right.isDown || wasdKeys.right.isDown) {
                    velocityX = speed;
                }

                if (cursors.up.isDown || wasdKeys.up.isDown) {
                    velocityY = -speed;
                } else if (cursors.down.isDown || wasdKeys.down.isDown) {
                    velocityY = speed;
                }
                
                // Joystick controls (override keyboard if active)
                if (joystickActive) {
                    velocityX = joystickDirection.x * speed;
                    velocityY = joystickDirection.y * speed;
                }
                
                player.setVelocityX(velocityX);
                player.setVelocityY(velocityY);

                // Play animations with priority for the stronger axis (helpful for joysticks)
                if (velocityX !== 0 || velocityY !== 0) {
                    if (Math.abs(velocityX) > Math.abs(velocityY)) {
                        // Horizontal movement is stronger
                        if (velocityX < 0) {
                            player.anims.play('left', true);
                        } else {
                            player.anims.play('right', true);
                        }
                    } else {
                        // Vertical movement is stronger
                        if (velocityY < 0) {
                            player.anims.play('up', true);
                        } else {
                            player.anims.play('down', true);
                        }
                    }
                } else {
                    player.anims.stop();
                    // Set idle frame if animated
                    const isAnimated = characterPath.includes('character_ethan.png') || 
                                       characterPath.includes('amber.png') || 
                                       characterPath.includes('character_emma.png');
                    if (isAnimated) {
                        player.setFrame(0);
                    }
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
                const gwaDisplay = document.getElementById('player-gwa');
                if (gwaDisplay) {
                    gwaDisplay.textContent = (levelResult.new_level * 1.5).toFixed(2);
                }
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

        
        async function showVictoryScreen() {
            // Save level progress before showing victory screen
            await saveLevelProgress();
            
            // Update victory screen with stats
            document.getElementById('victory-exp').textContent = tempExpGained;
            document.getElementById('victory-essence').textContent = tempEssenceGained;
            
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
        
        // Handle mobile back button click
        function handleMobileBack() {
            if (inGameSession && !allowLeave && !modalShown) {
                showWarningModal();
            } else if (!inGameSession) {
                // If game hasn't started, allow direct navigation
                window.location.href = 'index.php';
            }
        }
        
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
            window.location.href = 'index.php';
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



