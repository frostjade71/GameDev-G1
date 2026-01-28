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

// Get lesson ID
$lesson_id = $_GET['id'] ?? null;

if (!$lesson_id) {
    header("Location: learn.php");
    exit;
}

// Fetch Lesson
$stmt = $pdo->prepare("SELECT * FROM lessons WHERE id = ?");
$stmt->execute([$lesson_id]);
$lesson = $stmt->fetch();

if (!$lesson) {
    // Lesson not found
    header("Location: learn.php");
    exit;
}

// Get user's vocabworld progress (for shards display)
$stmt = $pdo->prepare("SELECT * FROM game_progress WHERE user_id = ? AND game_type = 'vocabworld'");
$stmt->execute([$user_id]);
$progress = $stmt->fetch();

$character_data = null;
$user_shards = 0;
if ($progress && $progress['unlocked_levels']) {
    $character_data = json_decode($progress['unlocked_levels'], true);
    $user_shards = $character_data['current_points'] ?? 0;
}

require_once '../shard_manager.php';
$shardManager = new ShardManager($pdo);
$shard_balance = $shardManager->getShardBalance($user_id);
if ($shard_balance) {
    $user_shards = $shard_balance['current_shards'];
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
    <title><?= htmlspecialchars($lesson['title']) ?> - VocabWorld</title>
    <link rel="stylesheet" href="../style.css?v=3">
    <link rel="stylesheet" href="../navigation/navigation.css?v=3">
    <link rel="stylesheet" href="learnvocabmenu.css?v=3">
    <link rel="stylesheet" href="../../../notif/toast.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .lesson-view-container {
            max-width: 900px;
            margin: 0 auto;
            padding: 2rem;
            color: white;
            font-family: 'Poppins', sans-serif;
            padding-top: 80px; /* Space for fixed header/back button */
        }

        .lesson-content-card {
            background: rgba(26, 26, 46, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.3);
            position: relative;
            overflow: hidden;
        }

        .lesson-header-banner {
            margin-bottom: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding-bottom: 15px;
        }

        .lesson-title {
            font-size: 1.8rem;
            color: white;
            margin-bottom: 8px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }

        .lesson-meta {
            color: rgba(255, 255, 255, 0.6);
            font-size: 0.8rem;
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .lesson-body {
            color: rgba(255, 255, 255, 0.9);
            line-height: 1.6;
            font-size: 0.95rem;
        }
        
        /* Ensure images in content are responsive */
        .lesson-body img {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
            margin: 10px 0;
        }

        /* Restore standard formatting for TinyMCE content */
        .lesson-body p {
            margin-bottom: 1em;
        }

        .lesson-body h1, .lesson-body h2, .lesson-body h3, 
        .lesson-body h4, .lesson-body h5, .lesson-body h6 {
            color: white;
            margin-top: 1.5em;
            margin-bottom: 0.5em;
            font-weight: 600;
        }

        .lesson-body h1 { font-size: 1.8em; }
        .lesson-body h2 { font-size: 1.5em; }
        .lesson-body h3 { font-size: 1.3em; }
        .lesson-body h4 { font-size: 1.1em; }

        .lesson-body ul, .lesson-body ol {
            margin-bottom: 1em;
            padding-left: 20px;
        }

        .lesson-body li {
            margin-bottom: 0.5em;
        }

        .lesson-body blockquote {
            border-left: 3px solid rgba(255, 255, 255, 0.3);
            margin: 1em 0;
            padding-left: 15px;
            color: rgba(255, 255, 255, 0.7);
            font-style: italic;
        }

        .lesson-body strong {
            font-weight: 600;
            color: white;
        }
        
        .lesson-body a {
            color: #6e8efb;
            text-decoration: underline;
        }

        .back-nav-btn {
            position: fixed;
            top: 100px;
            left: 20px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(5px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
            padding: 6px 16px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            gap: 6px;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s ease;
            z-index: 100;
            font-size: 0.8rem;
            font-family: 'Poppins', sans-serif;
        }

        .back-nav-btn:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }

        @media (max-width: 768px) {
            .lesson-view-container {
                padding: 10px;
                padding-top: 70px; /* Space for fixed header/back button */
            }
            
            .lesson-content-card {
                padding: 15px;
                border-radius: 12px;
            }

            .lesson-title {
                font-size: 1.2rem;
                margin-bottom: 5px;
            }

            .lesson-meta {
                font-size: 0.7rem;
                gap: 10px;
            }

            .lesson-body {
                font-size: 0.85rem;
                line-height: 1.5;
            }
            
            .back-nav-btn {
                top: auto;
                bottom: 20px;
                left: 50%;
                transform: translateX(-50%);
                width: auto;
                background: #3b82f6;
                border: none;
                box-shadow: 0 4px 12px rgba(0,0,0,0.3);
                padding: 10px 20px;
                font-size: 0.85rem;
            }
            
            .back-nav-btn:hover {
                transform: translateX(-50%) translateY(-2px);
            }
        }
    </style>
</head>
<body>
    <?php include '../loaders/loader-component.php'; ?>
    <div class="game-container">
        <!-- Background -->
        <div class="background-image"></div>
        
        <!-- Header (Simplified/Consistent) -->
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
                        <span class="shard-count"><?= $user_shards ?></span>
                        <i class="fas fa-chevron-down mobile-only dropdown-arrow" style="font-size: 0.8rem; margin-left: 5px;"></i>
                    </div>
                    <div class="currency-item essence-item">
                        <img src="../assets/currency/essence.png" alt="Essence" class="shard-icon">
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
                                <a href="learn.php" class="profile-dropdown-item">
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

        <a href="grade<?= $lesson['grade_level'] ?>.php" class="back-nav-btn">
            <i class="fas fa-arrow-left"></i> Back to Lessons
        </a>

        <div id="main-menu" class="screen active" style="overflow-y: auto;">
            <div class="lesson-view-container">
                <div class="lesson-content-card">
                    <div class="lesson-header-banner">
                        <h1 class="lesson-title"><?= htmlspecialchars($lesson['title']) ?></h1>
                        <div class="lesson-meta">
                            <span><i class="far fa-calendar-alt"></i> <?= date('F j, Y', strtotime($lesson['created_at'])) ?></span>
                            <span><i class="fas fa-layer-group"></i> Grade <?= htmlspecialchars($lesson['grade_level']) ?></span>
                        </div>
                    </div>
                    
                    <div class="lesson-body">
                        <?= $lesson['content'] ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="learnvocabmenu.js"></script>
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

        // Toggle currency dropdown on mobile
        function toggleCurrencyDropdown(element) {
            if (window.innerWidth <= 768) {
                element.classList.toggle('show-dropdown');
            }
        }
    </script>
</body>
</html>
