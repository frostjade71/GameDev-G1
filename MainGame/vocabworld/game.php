<?php
require_once '../../onboarding/config.php';
require_once '../../includes/greeting.php';

// Check if user is logged in
requireLogin();

// Get user information
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Get user's vocabworld progress
$stmt = $pdo->prepare("SELECT * FROM game_progress WHERE user_id = ? AND game_type = 'vocabworld'");
$stmt->execute([$user_id]);
$progress = $stmt->fetch();

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
    <title>VocabWorld Game - Word Weavers</title>
    <link rel="stylesheet" href="style.css?v=3">
    <link rel="stylesheet" href="navigation/navigation.css?v=3">
    <link rel="stylesheet" href="../../notif/toast.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="game-container">
        <!-- Background -->
        <div class="background-image"></div>
        
        <!-- Header -->
        <header class="top-header">
            <div class="header-left">
                <div class="game-logo-container">
                    <img src="assets/vocabworldhead.png" alt="VocabWorld" class="game-header-logo">
                </div>
            </div>
            <div class="header-right">
                <div class="shard-currency">
                    <img src="assets/currency/shard1.png" alt="Shards" class="shard-icon">
                    <span class="shard-count" id="shard-count">0</span>
                </div>
                <div class="user-profile">
                    <div class="user-info">
                        <span class="greeting"><?php echo getGreeting(); ?></span>
                        <span class="username"><?php echo htmlspecialchars(explode(' ', $user['username'])[0]); ?></span>
                    </div>
                    <div class="profile-dropdown">
                        <a href="#" class="profile-icon">
                            <img src="../../assets/menu/defaultuser.png" alt="Profile" class="profile-img">
                        </a>
                        <div class="profile-dropdown-content">
                            <div class="profile-dropdown-header">
                                <img src="../../assets/menu/defaultuser.png" alt="Profile" class="profile-dropdown-avatar">
                                <div class="profile-dropdown-info">
                                    <div class="profile-dropdown-name"><?php echo htmlspecialchars($user['username']); ?></div>
                                    <div class="profile-dropdown-email"><?php echo htmlspecialchars($user['email']); ?></div>
                                </div>
                            </div>
                            <div class="profile-dropdown-menu">
                                <a href="../../navigation/profile/profile.php" class="profile-dropdown-item">
                                    <i class="fas fa-user"></i>
                                    <span>View Profile</span>
                                </a>
                                <a href="../../navigation/favorites/favorites.php" class="profile-dropdown-item">
                                    <i class="fas fa-star"></i>
                                    <span>My Favorites</span>
                                </a>
                                <a href="../../settings/settings.php" class="profile-dropdown-item">
                                    <i class="fas fa-cog"></i>
                                    <span>Settings</span>
                                </a>
                            </div>
                            <div class="profile-dropdown-footer">
                                <button class="profile-dropdown-item sign-out" onclick="showLogoutModal()">
                                    <i class="fas fa-sign-out-alt"></i>
                                    <span>Sign Out</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Game Screen -->
        <div id="game-screen" class="screen active">
            <!-- Stop Game Button -->
            <button class="stop-game-btn" onclick="stopGame()" title="Stop Game">
                <i class="fas fa-stop"></i>
                <span>Stop</span>
            </button>
            
            <div class="game-header-bar">
                <div class="score-display">
                    <span class="score-label">Score:</span>
                    <span id="current-score">0</span>
                </div>
                <div class="level-display">
                    <span class="level-label">Level:</span>
                    <span id="current-level">1</span>
                </div>
                <div class="streak-display">
                    <span class="streak-label">Streak:</span>
                    <span id="current-streak">0</span>
                </div>
            </div>
            
            <div class="game-content">
                <div class="question-container">
                    <div class="question-type" id="question-type"></div>
                    <div class="question-text" id="question-text"></div>
                    <div class="question-hint" id="question-hint"></div>
                </div>
                
                <div class="answer-container">
                    <div class="answer-input-container" id="answer-input-container">
                        <input type="text" id="answer-input" placeholder="Type your answer here..." autocomplete="off">
                        <button id="submit-answer" onclick="submitAnswer()">Submit</button>
                    </div>
                    <div class="multiple-choice-container" id="multiple-choice-container" style="display: none;">
                        <div class="choice-buttons" id="choice-buttons"></div>
                    </div>
                </div>
                
                <div class="feedback-container" id="feedback-container" style="display: none;">
                    <div class="feedback-text" id="feedback-text"></div>
                    <button id="next-question" onclick="nextQuestion()" style="display: none;">Next Question</button>
                </div>
            </div>
        </div>

        <!-- Game Over Screen -->
        <div id="game-over-screen" class="screen">
            <div class="game-over-container">
                <h2>Game Complete!</h2>
                <div class="final-stats">
                    <div class="stat-item">
                        <span class="stat-label">Final Score:</span>
                        <span class="stat-value" id="final-score">0</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Questions Answered:</span>
                        <span class="stat-value" id="questions-answered">0</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Accuracy:</span>
                        <span class="stat-value" id="accuracy">0%</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Points Earned:</span>
                        <span class="stat-value" id="points-earned">0</span>
                    </div>
                </div>
                <div class="game-over-buttons">
                    <button class="menu-btn primary" onclick="startNewGame()">Play Again</button>
                    <button class="menu-btn secondary" onclick="goToMainMenu()">Main Menu</button>
                </div>
            </div>
        </div>
    </div>

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

    <script src="script.js"></script>
    <script src="../../navigation/shared/profile-dropdown.js"></script>
    <script>
        // Pass PHP data to JavaScript
        window.userData = {
            userId: <?php echo $user_id; ?>,
            username: '<?php echo addslashes($user['username']); ?>',
            gradeLevel: '<?php echo addslashes($user['grade_level']); ?>',
            shards: <?php echo $user_shards; ?>,
            characterData: <?php echo $character_data ? json_encode($character_data) : 'null'; ?>,
            averagePercentage: <?php echo $average_percentage; ?>,
            totalSessions: <?php echo $total_sessions; ?>
        };

        // Logout functionality
        function showLogoutModal() {
            const modal = document.getElementById('logoutModal');
            const confirmation = document.getElementById('logoutConfirmation');
            
            if (modal && confirmation) {
                modal.style.display = 'block';
                modal.classList.add('show');
                confirmation.classList.remove('hide');
                confirmation.classList.add('show');
            }
        }

        function hideLogoutModal() {
            const modal = document.getElementById('logoutModal');
            const confirmation = document.getElementById('logoutConfirmation');
            
            if (modal && confirmation) {
                confirmation.classList.remove('show');
                confirmation.classList.add('hide');
                modal.classList.remove('show');
                modal.style.display = 'none';
            }
        }

        function confirmLogout() {
            // Redirect to logout endpoint
            window.location.href = '../../onboarding/logout.php';
        }

        // Stop Game functionality
        function stopGame() {
            // Save current progress if game is active
            if (typeof game !== 'undefined' && game) {
                game.saveProgress();
            }
            
            // Return to main menu
            window.location.href = 'index.php';
        }

        // Start new game
        function startNewGame() {
            if (typeof game !== 'undefined' && game) {
                game.startNewGame();
            }
        }

        // Go to main menu
        function goToMainMenu() {
            window.location.href = 'index.php';
        }

        function submitAnswer() {
            if (game) game.submitAnswer();
        }

        function nextQuestion() {
            if (game) game.nextQuestion();
        }

        // Initialize shard count display
        function initializeShardDisplay() {
            const shardCountEl = document.getElementById('shard-count');
            if (shardCountEl && window.userData) {
                shardCountEl.textContent = window.userData.shards || 0;
            }
        }

        // Initialize when page loads
        document.addEventListener('DOMContentLoaded', function() {
            initializeShardDisplay();
        });
    </script>
</body>
</html>



