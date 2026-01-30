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

// Check game access permission
$game_access_allowed = true;
$user_grade = $user['grade_level'] ?? '';
$db_is_enabled = null;

// Only check for students (non-staff)
if (!in_array($user_grade, ['Teacher', 'Admin', 'Developer'])) {
    $grade_num = (int)filter_var($user_grade, FILTER_SANITIZE_NUMBER_INT);
    
    if ($grade_num > 0) {
        $stmt = $pdo->prepare("SELECT is_enabled FROM game_access_controls WHERE grade_level = ?");
        $stmt->execute([$grade_num]);
        $access_control = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($access_control) {
            $db_is_enabled = (int)$access_control['is_enabled'];
            if ($db_is_enabled === 0) {
                $game_access_allowed = false;
            }
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
                    <a href="javascript:void(0)" onclick="handleStartGame()" class="vocabworld-card start-game-card" style="background-image: url('assets/menu/spaceplay.gif'); background-size: cover; background-position: center;">
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

    </div>

    <!-- Access Denied Modal -->
    <!-- Access Denied Modal -->
    <style>
        .access-modal-content {
            background: rgba(20, 20, 30, 0.95);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            padding: 0;
            width: 90%;
            max-width: 340px; /* Smaller default width */
            box-shadow: 0 10px 40px rgba(0,0,0,0.5);
        }
        .access-modal-header {
            padding: 15px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .access-modal-body {
            padding: 20px;
            text-align: left;
        }
        .access-modal-footer {
            padding: 15px 20px;
            background: rgba(0, 0, 0, 0.2);
            border-radius: 0 0 16px 16px;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }
        .access-icon-box {
            width: 36px;
            height: 36px;
            font-size: 16px;
        }
        
        /* Mobile Compact Styles */
        @media (max-width: 480px) {
            .access-modal-content {
                max-width: 90%;
            }
            .access-modal-header {
                padding: 12px 15px;
            }
            .access-modal-body {
                padding: 15px 15px 20px;
            }
            .access-modal-footer {
                padding: 12px 15px;
            }
            .access-modal-header h2 {
                font-size: 1.1rem !important;
            }
            .access-icon-box {
                width: 32px;
                height: 32px;
                font-size: 14px;
                border-radius: 8px !important;
            }
        }
    </style>
    
    <div id="accessDeniedModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 10000; justify-content: center; align-items: center; background: rgba(0, 0, 0, 0.7); backdrop-filter: blur(5px);" onclick="hideAccessDeniedModal()">
        <div class="access-modal-content" onclick="event.stopPropagation()">
            <div class="access-modal-header">
                <div class="modal-title-wrapper" style="display: flex; align-items: center; gap: 12px;">
                    <div class="stat-icon access-icon-box" style="background: linear-gradient(135deg, #ef4444 0%, #b91c1c 100%); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: white;">
                        <i class="fas fa-lock"></i>
                    </div>
                    <h2 style="margin: 0; color: white; font-size: 1.2rem; font-family: 'Poppins', sans-serif; font-weight: 600;">Not Started Yet</h2>
                </div>
                <button onclick="hideAccessDeniedModal()" style="background: transparent; border: none; color: rgba(255,255,255,0.5); cursor: pointer; font-size: 1.1rem;">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="access-modal-body">
                <p style="color: rgba(255, 255, 255, 0.9); font-size: 0.95rem; line-height: 1.5; margin-bottom: 5px;">This game is currently locked for your grade level.</p>
                <p style="color: rgba(255, 255, 255, 0.6); font-size: 0.85rem; margin: 0;">Please wait for your teacher to begin the game or study some lessons.</p>
            </div>
            <div class="access-modal-footer">
                <button onclick="hideAccessDeniedModal()" style="background: transparent; color: rgba(255, 255, 255, 0.7); border: 1px solid rgba(255, 255, 255, 0.2); padding: 0.5rem 1rem; border-radius: 8px; cursor: pointer; font-family: 'Poppins', sans-serif; font-weight: 500; font-size: 0.9rem; transition: all 0.3s ease;">Okay</button>
                <button onclick="window.location.href='learnvocabmenu/learn.php'" style="background: linear-gradient(135deg, #4ade80 0%, #22c55e 100%); color: white; border: none; padding: 0.5rem 1rem; border-radius: 8px; cursor: pointer; font-family: 'Poppins', sans-serif; font-weight: 600; font-size: 0.9rem; box-shadow: 0 4px 12px rgba(34, 197, 94, 0.3); display: flex; align-items: center; gap: 6px;">
                    <i class="fas fa-book-open"></i> Study
                </button>
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
        function toggleCurrencyDropdown(element) {
            if (window.innerWidth <= 768) {
                element.classList.toggle('show-dropdown');
            }
        }

        // Access Control Logic
        const isGameAllowed = <?php echo $game_access_allowed ? 'true' : 'false'; ?>;

        function handleStartGame() {
            if (isGameAllowed) {
                window.location.href = 'game.php';
            } else {
                showAccessDeniedModal();
            }
        }

        function showAccessDeniedModal() {
            const modal = document.getElementById('accessDeniedModal');
            if (modal) {
                modal.style.display = 'flex';
                // Remove toast related logic since we aren't using toast classes anymore
            }
        }

        function hideAccessDeniedModal() {
            const modal = document.getElementById('accessDeniedModal');
            const toast = document.getElementById('accessDeniedToast');
            if (modal) {
                modal.style.display = 'none';
                modal.classList.remove('show');
                if (toast) {
                    toast.classList.remove('show');
                    toast.classList.add('hide');
                }
            }
        }
    </script>
</body>
</html>
