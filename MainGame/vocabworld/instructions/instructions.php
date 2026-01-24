<?php
require_once '../../../onboarding/config.php';
require_once '../../../includes/greeting.php';

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

// Get shard balance from new shard system
require_once '../shard_manager.php';
$shardManager = new ShardManager($pdo);
$shard_result = $shardManager->ensureShardAccount($user_id);

$user_shards = 0;
if ($shard_result['success']) {
    $user_shards = $shard_result['shard_balance'];
}

// Get essence balance
require_once '../api/essence_manager.php';
$essenceManager = new EssenceManager($pdo);
$current_essence = $essenceManager->getEssence($user_id);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/webp" href="../assets/menu/vv_logo.webp">
    <title>How to Play - VocabWorld</title>
    <link rel="stylesheet" href="../style.css?v=4">
    <link rel="stylesheet" href="../navigation/navigation.css?v=4">
    <link rel="stylesheet" href="instructions.css?v=1">
    <link rel="stylesheet" href="../../../notif/toast.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>
    <?php include '../loaders/loader-component.php'; ?>
    <div class="game-container">
        <!-- Background -->
        <div class="background-image"></div>
        
        <!-- Header -->
        <header class="top-header">
            <div class="header-left">
                <div class="game-logo-container">
                    <img src="../assets/vocabworldhead.png" alt="VocabWorld" class="game-header-logo">
                </div>
            </div>
            <div class="header-right">
                <div class="shard-currency" onclick="toggleCurrencyDropdown(this)">
                    <div class="currency-item shard-item">
                        <img src="../assets/currency/shard1.png" alt="Shards" class="shard-icon">
                        <span class="shard-count" id="shard-count"><?php echo $user_shards; ?></span>
                        <i class="fas fa-chevron-down mobile-only dropdown-arrow" style="font-size: 0.8rem; margin-left: 5px;"></i>
                    </div>
                    <div class="currency-item essence-item">
                        <img src="../assets/currency/essence.png" alt="Essence" class="shard-icon">
                        <span class="shard-count" id="essence-count"><?php echo $current_essence; ?></span>
                    </div>
                </div>
                <div class="user-profile">
                    <div class="user-info">
                        <span class="greeting"><?php echo getGreeting(); ?></span>
                        <span class="username"><?php echo htmlspecialchars(explode(' ', $user['username'])[0]); ?></span>
                    </div>
                    <div class="profile-dropdown">
                        <a href="#" class="profile-icon">
                            <img src="<?php echo !empty($user['profile_image']) ? '../../../' . htmlspecialchars($user['profile_image']) : '../../../assets/menu/defaultuser.png'; ?>" alt="Profile" class="profile-img">
                        </a>
                        <div class="profile-dropdown-content">
                            <div class="profile-dropdown-header">
                                <img src="<?php echo !empty($user['profile_image']) ? '../../../' . htmlspecialchars($user['profile_image']) : '../../../assets/menu/defaultuser.png'; ?>" alt="Profile" class="profile-dropdown-avatar">
                                <div class="profile-dropdown-info">
                                    <div class="profile-dropdown-name"><?php echo htmlspecialchars($user['username']); ?></div>
                                    <div class="profile-dropdown-level">
                                        <img src="../assets/stats/level.png" class="level-icon-mini">
                                        <span>Level <?php echo htmlspecialchars($progress['player_level'] ?? 1); ?></span>
                                    </div>
                                </div>
                            </div>
                            <div class="profile-dropdown-menu">
                                <a href="../charactermenu/character.php" class="profile-dropdown-item">
                                    <img src="../charactermenu/assets/fc1089.png" class="dropdown-item-icon">
                                    <span>View Character</span>
                                </a>
                                <a href="../learnvocabmenu/learn.php" class="profile-dropdown-item">
                                    <img src="../assets/menu/vocabsys.png" class="dropdown-item-icon">
                                    <span>Study & Learn</span>
                                </a>
                             </div>
                            <div class="profile-dropdown-footer">
                                <button class="profile-dropdown-item sign-out" onclick="showLogoutModal()">
                                    <img src="../assets/menu/exit.png" class="dropdown-item-icon">
                                    <span>Sign Out</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Instructions Screen -->
        <div id="instructions-screen" class="screen active">
            <div class="instructions-wrapper">
                <div class="instructions-grid">
                    <!-- Game Overview -->
                    <div class="instruction-card featured">
                        <div class="logo-container">
                            <img src="../assets/menu/instructionsmain.png" alt="VocabWorld Instructions" class="instructions-logo">
                        </div>
                        <h2>Game Overview</h2>
                        <p>VocabWorld is an RPG adventure where you battle monsters by answering vocabulary questions. Explore the world, defeat enemies, and grow stronger with every correct answer!</p>
                        <div class="key-features">
                            <span class="feature-tag"><i class="fas fa-map"></i> Open World Exploration</span>
                            <span class="feature-tag"><i class="fas fa-dragon"></i> Monster Battles</span>
                            <span class="feature-tag"><i class="fas fa-brain"></i> Vocabulary Challenges</span>
                        </div>
                    </div>

                    <!-- Movement & Controls -->
                    <div class="instruction-card">
                        <div class="card-icon">
                            <img src="../assets/menu/playsys.png" alt="Movement" style="width: 100%; height: 100%; object-fit: contain;">
                        </div>
                        <h2>Movement & Controls</h2>
                        <div class="controls-grid">
                            <div class="control-item">
                                <kbd>↑</kbd>
                                <span>Move Up</span>
                            </div>
                            <div class="control-item">
                                <kbd>↓</kbd>
                                <span>Move Down</span>
                            </div>
                            <div class="control-item">
                                <kbd>←</kbd>
                                <span>Move Left</span>
                            </div>
                            <div class="control-item">
                                <kbd>→</kbd>
                                <span>Move Right</span>
                            </div>
                        </div>
                        <p class="tip"><i class="fas fa-lightbulb"></i> Use arrow keys to navigate the world and approach monsters to initiate battles.</p>
                    </div>

                    <!-- Battle System -->
                    <div class="instruction-card">
                        <div class="card-icon">
                            <img src="../assets/menu/vocabsys.png" alt="Battle System">
                        </div>
                        <h2>Battle System</h2>
                        <ul class="feature-list">
                            <li><i class="fas fa-check-circle"></i> Collide with monsters to start a battle</li>
                            <li><i class="fas fa-check-circle"></i> Answer vocabulary questions to defeat enemies</li>
                            <li><i class="fas fa-check-circle"></i> Correct answers destroy the monster</li>
                            <li><i class="fas fa-check-circle"></i> Wrong answers end the battle without reward</li>
                            <li><i class="fas fa-check-circle"></i> Defeat all monsters to complete the level</li>
                        </ul>
                        <div class="battle-example">
                            <strong>Example Question:</strong>
                            <p>"What is the meaning of 'Benevolent'?"</p>
                            <div class="example-options">
                                <span class="option correct">✓ Kind and generous</span>
                                <span class="option">✗ Angry and hostile</span>
                            </div>
                        </div>
                    </div>

                    <!-- Rewards & Progression -->
                    <div class="instruction-card">
                        <div class="card-icon">
                            <i class="fas fa-trophy"></i>
                        </div>
                        <h2>Rewards & Progression</h2>
                        <div class="rewards-grid">
                            <div class="reward-item">
                                <img src="../assets/currency/essence.png" alt="Essence" class="reward-icon">
                                <h3>Essence</h3>
                                <p>Earn 5-10 essence per correct answer</p>
                                <p class="current-balance">Current: <strong><?php echo $current_essence; ?></strong></p>
                            </div>
                            <div class="reward-item">
                                <img src="../assets/currency/shard1.png" alt="Shards" class="reward-icon">
                                <h3>Shards</h3>
                                <p>Use for character customization</p>
                                <p class="current-balance">Current: <strong><?php echo $user_shards; ?></strong></p>
                            </div>
                            <div class="reward-item">
                                <img src="../assets/stats/ratings1.png" alt="Score" class="reward-icon">
                                <h3>Score Points</h3>
                                <p>100 points per correct answer</p>
                                <p class="current-balance">Tracks your performance</p>
                            </div>
                        </div>
                    </div>

                    <!-- Character Stats -->
                    <div class="instruction-card">
                        <div class="card-icon">
                            <img src="../assets/menu/charactersys.png" alt="Character Stats" style="width: 100%; height: 100%; object-fit: contain;">
                        </div>
                        <h2>Character Stats</h2>
                        <div class="stats-explanation">
                            <div class="stat-item">
                                <img src="../assets/stats/heart.png" alt="HP" class="stat-icon-img">
                                <div>
                                    <strong>HP (Health Points)</strong>
                                    <p>Your character's health - starts at 100</p>
                                </div>
                            </div>
                            <div class="stat-item">
                                <img src="../assets/stats/level.png" alt="Level" class="stat-icon-img">
                                <div>
                                    <strong>Level</strong>
                                    <p>Increases as you defeat more monsters</p>
                                </div>
                            </div>
                            <div class="stat-item">
                                <img src="../assets/currency/essence.png" alt="Essence" class="stat-icon-img">
                                <div>
                                    <strong>Essence</strong>
                                    <p>Currency earned from battles</p>
                                </div>
                            </div>
                            <div class="stat-item">
                                <img src="../assets/stats/gwa.png" alt="GWA" class="stat-icon-img">
                                <div>
                                    <strong>GWA (Game Weighted Average)</strong>
                                    <p>Your average score over the last 30 days</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tips & Strategies -->
                    <div class="instruction-card">
                        <div class="card-icon">
                            <img src="../assets/menu/instructionicon.png" alt="Tips" style="width: 100%; height: 100%; object-fit: contain;">
                        </div>
                        <h2>Tips & Strategies</h2>
                        <ul class="tips-list">
                            <li>
                                <i class="fas fa-book-reader"></i>
                                <div>
                                    <strong>Study First</strong>
                                    <p>Visit the Learning Mode to review vocabulary before playing</p>
                                </div>
                            </li>
                            <li>
                                <i class="fas fa-bullseye"></i>
                                <div>
                                    <strong>Take Your Time</strong>
                                    <p>Read questions carefully - there's no time limit</p>
                                </div>
                            </li>
                            <li>
                                <i class="fas fa-redo"></i>
                                <div>
                                    <strong>Practice Regularly</strong>
                                    <p>Daily play improves your vocabulary retention</p>
                                </div>
                            </li>
                            <li>
                                <i class="fas fa-users"></i>
                                <div>
                                    <strong>Customize Your Character</strong>
                                    <p>Use earned shards to personalize your hero</p>
                                </div>
                            </li>
                            <li>
                                <i class="fas fa-chart-bar"></i>
                                <div>
                                    <strong>Track Progress</strong>
                                    <p>Monitor your GWA to see improvement over time</p>
                                </div>
                            </li>
                        </ul>
                    </div>

                    <!-- Learning Mode -->
                    <div class="instruction-card">
                        <div class="card-icon">
                            <img src="../assets/menu/learnvocab.webp" alt="Learning Mode" style="width: 100%; height: 100%; object-fit: contain;">
                        </div>
                        <h2>Learning Mode</h2>
                        <p>Before jumping into battle, use Learning Mode to:</p>
                        <ul class="feature-list">
                            <li><i class="fas fa-check-circle"></i> Browse vocabulary words by grade level</li>
                            <li><i class="fas fa-check-circle"></i> Study definitions and examples</li>
                            <li><i class="fas fa-check-circle"></i> Filter by difficulty (Easy, Medium, Hard)</li>
                            <li><i class="fas fa-check-circle"></i> Search for specific words</li>
                            <li><i class="fas fa-check-circle"></i> Prepare for tougher battles</li>
                        </ul>
                        <div class="cta-box">
                            <i class="fas fa-info-circle"></i>
                            <p>Words are aligned with Grade 7-10 curriculum standards</p>
                        </div>
                    </div>

                    <!-- Ready to Play -->
                    <div class="instruction-card cta-card">
                        <div class="card-icon">
                            <i class="fas fa-rocket"></i>
                        </div>
                        <h2>Ready to Begin Your Adventure?</h2>
                        <p>You now have everything you need to start your VocabWorld journey!</p>
                        <div class="action-buttons">
                            <button class="btn-primary" onclick="startGame()">
                                <i class="fas fa-play"></i> Start Playing
                            </button>
                            <button class="btn-secondary" onclick="goToLearning()">
                                <i class="fas fa-book"></i> Learning Mode
                            </button>
                            <button class="btn-secondary" onclick="goToMainMenu()">
                                <i class="fas fa-home"></i> Main Menu
                            </button>
                        </div>
                    </div>
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
                <button onclick="confirmLogout()" style="background: #ff6b6b; color: white; border: none; padding: 0.5rem 1rem; border-radius: 8px; cursor: pointer; font-family: 'Poppins', sans-serif; font-size: 0.9rem;">Yes, Logout</button>
                <button onclick="hideLogoutModal()" style="background: rgba(255, 255, 255, 0.2); color: white; border: 1px solid rgba(255, 255, 255, 0.3); padding: 0.5rem 1rem; border-radius: 8px; cursor: pointer; font-family: 'Poppins', sans-serif; font-size: 0.9rem;">Cancel</button>
            </div>
        </div>
    </div>

    <script src="../../../navigation/shared/profile-dropdown.js"></script>
    <script>
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
            window.location.href = '../../../onboarding/logout.php';
        }

        // Navigation functions
        function goToMainMenu() {
            window.location.href = '../index.php';
        }

        function startGame() {
            window.location.href = '../game.php';
        }

        function goToLearning() {
            window.location.href = '../learnvocabmenu/learn.php';
        }

        // Smooth scroll for long content
        document.addEventListener('DOMContentLoaded', function() {
            const instructionsWrapper = document.querySelector('.instructions-wrapper');
            if (instructionsWrapper) {
                instructionsWrapper.style.scrollBehavior = 'smooth';
            }
        });

        // Toggle currency dropdown on mobile
        function toggleCurrencyDropdown(element) {
            if (window.innerWidth <= 768) {
                element.classList.toggle('show-dropdown');
            }
        }
    </script>
</body>
</html>
