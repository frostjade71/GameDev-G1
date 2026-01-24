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

// Get essence balance
require_once 'api/essence_manager.php';
$essenceManager = new EssenceManager($pdo);
$current_essence = $essenceManager->getEssence($user_id);

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
    <link rel="icon" type="image/webp" href="assets/menu/vv_logo.webp">
    <title>VocabWorld</title>
    <link rel="stylesheet" href="style.css?v=3">
    <link rel="stylesheet" href="navigation/navigation.css?v=3">
    <link rel="stylesheet" href="../../notif/toast.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <?php include 'loaders/loader-component.php'; ?>
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
                <div class="shard-currency" onclick="toggleCurrencyDropdown(this)">
                    <div class="currency-item shard-item">
                        <img src="assets/currency/shard1.png" alt="Shards" class="shard-icon">
                        <span class="shard-count" id="shard-count">0</span>
                        <i class="fas fa-chevron-down mobile-only dropdown-arrow" style="font-size: 0.8rem; margin-left: 5px;"></i>
                    </div>
                    <div class="currency-item essence-item">
                        <img src="assets/currency/essence.png" alt="Essence" class="shard-icon">
                        <span class="shard-count"><?php echo $current_essence; ?></span>
                    </div>
                </div>
                <div class="user-profile">
                    <div class="user-info">
                        <span class="greeting"><?php echo getGreeting(); ?></span>
                        <span class="username"><?php echo htmlspecialchars(explode(' ', $user['username'])[0]); ?></span>
                    </div>
                    <div class="profile-dropdown">
                        <a href="#" class="profile-icon">
                            <img src="<?php echo !empty($user['profile_image']) ? '../../' . htmlspecialchars($user['profile_image']) : '../../assets/menu/defaultuser.png'; ?>" alt="Profile" class="profile-img">
                        </a>
                        <div class="profile-dropdown-content">
                            <div class="profile-dropdown-header">
                                <img src="<?php echo !empty($user['profile_image']) ? '../../' . htmlspecialchars($user['profile_image']) : '../../assets/menu/defaultuser.png'; ?>" alt="Profile" class="profile-dropdown-avatar">
                                <div class="profile-dropdown-info">
                                    <div class="profile-dropdown-name"><?php echo htmlspecialchars($user['username']); ?></div>
                                    <div class="profile-dropdown-level">
                                        <img src="assets/stats/level.png" class="level-icon-mini">
                                        <span>Level <?php echo htmlspecialchars($progress['player_level'] ?? 1); ?></span>
                                    </div>
                                </div>
                            </div>
                            <div class="profile-dropdown-menu">
                                <a href="charactermenu/character.php" class="profile-dropdown-item">
                                    <img src="charactermenu/assets/fc1089.png" class="dropdown-item-icon">
                                    <span>View Character</span>
                                </a>
                                <a href="learnvocabmenu/learn.php" class="profile-dropdown-item">
                                    <img src="assets/menu/vocabsys.png" class="dropdown-item-icon">
                                    <span>Study & Learn</span>
                                </a>
                             </div>
                            <div class="profile-dropdown-footer">
                                <button class="profile-dropdown-item sign-out" onclick="showLogoutModal()">
                                    <img src="assets/menu/exit.png" class="dropdown-item-icon">
                                    <span>Sign Out</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Menu -->
        <div id="main-menu" class="screen active" style="padding-top: calc(50px + 2rem) !important; padding: 1rem !important;">
            <div class="menu-container" style="min-height: calc(100vh - 200px) !important; padding: 5rem 0 !important;">
                <div class="vocabworld-grid">
                    <a href="game.php" class="vocabworld-card start-game-card" style="background-image: url('assets/menu/spaceplay.gif'); background-size: cover; background-position: center;">
                        <img src="assets/menu/playsys.png" alt="Play" class="card-icon">
                        <h2>Start Game</h2>
                        <p>Begin your vocabulary adventure</p>
                    </a>
                    <a href="learnvocabmenu/learn.php" class="vocabworld-card learn-card" style="background-image: url('assets/menu/learnvocab.webp'); background-size: cover; background-position: center;">
                        <img src="assets/menu/vocabsys.png" alt="Learn" class="card-icon">
                        <h2>Learn Vocabulary</h2>
                        <p>Study words and definitions</p>
                    </a>
                    <a href="charactermenu/character.php" class="vocabworld-card character-card" style="background-image: url('assets/menu/charactercuz.webp'); background-size: cover; background-position: center;">
                        <img src="assets/menu/charactersys.png" alt="Character" class="card-icon">
                        <h2>Your Character</h2>
                        <p>Customize your avatar</p>
                    </a>
                    <a href="instructions/instructions.php" class="vocabworld-card instructions-card" style="background-image: url('assets/menu/instructionbg.webp'); background-size: cover; background-position: center;">
                        <img src="assets/menu/instructionicon.png" alt="Instructions" class="card-icon">
                        <h2>Instructions</h2>
                        <p>Learn how to play</p>
                    </a>
                    <a href="../../play/game-selection.php" class="vocabworld-card back-to-games-card" style="background-image: url('assets/menu/backportal.webp'); background-size: cover; background-position: center;">
                        <img src="assets/menu/portal1.png" alt="Back" class="card-icon">
                        <h2>Back to Games</h2>
                        <p>Return to game selection</p>
                    </a>
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

    <script src="../../navigation/shared/profile-dropdown.js"></script>
    <script>
        // Pass user data to JavaScript
        window.userData = {
            userId: <?php echo $user_id; ?>,
            username: '<?php echo htmlspecialchars($user['username']); ?>',
            email: '<?php echo htmlspecialchars($user['email']); ?>',
            shards: <?php echo $user_shards; ?>,
            characterData: <?php echo json_encode($character_data); ?>,
            totalSessions: 0,
            averagePercentage: 0
        };
    </script>
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
            // Redirect to logout endpoint
            window.location.href = '../../onboarding/logout.php';
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
        // Toggle currency dropdown on mobile
        function toggleCurrencyDropdown(element) {
            if (window.innerWidth <= 768) {
                element.classList.toggle('show-dropdown');
            }
        }

    </script>
</body>
</html>
