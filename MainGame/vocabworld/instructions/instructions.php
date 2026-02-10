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
                <header class="instructions-header">
                    <h1>How to Play</h1>
                    <p>Master the world of vocabulary and become the ultimate explorer.</p>
                </header>

                <div class="instructions-grid">
                    <!-- Movement & Controls -->
                    <div class="instruction-card">
                        <div class="card-icon">
                            <img src="assets/fc667.png" alt="Movement">
                        </div>
                        <h2>Movement & Controls</h2>
                        <p>Use the arrow keys to navigate the world. Approach monsters to trigger a vocabulary battle.</p>
                    </div>

                    <!-- Battle Monsters -->
                    <div class="instruction-card">
                        <div class="card-icon">
                            <img src="../assets/stats/sword1.png" alt="Battle">
                        </div>
                        <h2>Battle Monsters</h2>
                        <p>Answer vocabulary questions correctly to defeat enemies. Correct answers deal damage, mistakes cost you health.</p>
                    </div>

                    <!-- Rewards & Progression -->
                    <div class="instruction-card">
                        <div class="card-icon">
                            <img src="assets/fc15.png" alt="Rewards">
                        </div>
                        <h2>Rewards & Progression</h2>
                        <p>Earn Shards and Essence from every victory. Unlock new character's using shards.</p>
                    </div>

                    <!-- Character Stats -->
                    <div class="instruction-card">
                        <div class="card-icon">
                            <img src="../charactermenu/assets/fc1089.png" alt="Stats">
                        </div>
                        <h2>Character Stats</h2>
                        <p>Monitor your HP, Level, and GWA. High GWA shows your mastery over the English language.</p>
                    </div>

                    <!-- Tips & Strategies -->
                    <div class="instruction-card">
                        <div class="card-icon">
                            <img src="../assets/menu/instructionicon.png" alt="Tips">
                        </div>
                        <h2>Tips & Strategies</h2>
                        <p>Read questions carefully. Visit "Learn Vocabulary" before entering the world.</p>
                    </div>

                    <!-- Learn Lessons -->
                    <div class="instruction-card">
                        <div class="card-icon">
                            <img src="../assets/menu/vocabsys.png" alt="Learn">
                        </div>
                        <h2>Learn Lessons</h2>
                        <p>Navigate Learn Vocabulary in the Main menu to start learning lessons provided by your Teachers</p>
                    </div>
                </div>

                <div class="instructions-cta">
                    <button class="btn-game btn-primary" onclick="startGame()">
                        <i class="fas fa-play"></i> Start Playing
                    </button>
                    <button class="btn-game btn-secondary" onclick="goToLearning()">
                        <i class="fas fa-book"></i> Learn Now
                    </button>
                    <button class="btn-game btn-secondary" onclick="goToMainMenu()">
                        <i class="fas fa-home"></i> Home
                    </button>
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
