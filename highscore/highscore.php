<?php
require_once '../onboarding/config.php';
require_once '../includes/greeting.php';

// Check if user is logged in, redirect to login if not
if (!isLoggedIn()) {
    header('Location: ../onboarding/login.php');
    exit();
}

// Get user information
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT username, email, grade_level FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    // User not found, destroy session and redirect to login
    session_destroy();
    header('Location: ../onboarding/login.php');
    exit();
}

// Get user's high scores for each game
$stmt = $pdo->prepare("SELECT 
    game_type,
    MAX(score) as best_score,
    MAX(level) as best_level,
    COUNT(*) as games_played
    FROM game_scores 
    WHERE user_id = ? 
    GROUP BY game_type");
$stmt->execute([$user_id]);
$user_scores = [];
while ($row = $stmt->fetch()) {
    $user_scores[$row['game_type']] = [
        'best_score' => $row['best_score'],
        'best_level' => $row['best_level'],
        'games_played' => $row['games_played']
    ];
}

// Get global leaderboard (top 10 scores for each game)
$stmt = $pdo->prepare("SELECT 
    gs.game_type,
    gs.score,
    gs.level,
    u.username,
    gs.created_at
    FROM game_scores gs
    JOIN users u ON gs.user_id = u.id
    WHERE gs.game_type = ? 
    ORDER BY gs.score DESC, gs.level DESC, gs.created_at ASC
    LIMIT 10");

// Get pending friend requests for the current user (for notification count)
$stmt = $pdo->prepare("
    SELECT fr.id, fr.requester_id, fr.created_at, u.username, u.email, u.grade_level
    FROM friend_requests fr
    JOIN users u ON fr.requester_id = u.id
    WHERE fr.receiver_id = ? AND fr.status = 'pending'
    ORDER BY fr.created_at DESC
");
$stmt->execute([$user_id]);
$friend_requests = $stmt->fetchAll();

// Get notification count for badge
$notification_count = count($friend_requests);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Word Weavers - High Scores</title>
    <link rel="stylesheet" href="../navigation/shared/navigation.css">
    <link rel="stylesheet" href="highscore.css">
    <link rel="stylesheet" href="../notif/toast.css">
    <link rel="stylesheet" href="../styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>
<body>
    <!-- Mobile Menu Button -->
    <button class="mobile-menu-btn" aria-label="Open menu">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-logo">
            <img src="../assets/menu/Word-Weavers.png" alt="Word Weavers" class="sidebar-logo-img">
        </div>
        <nav class="sidebar-nav">
            <a href="../index.php?from=selection" class="nav-link">
                <i class="fas fa-house"></i>
                <span>Menu</span>
            </a>
            <a href="../navigation/favorites/favorites.php" class="nav-link">
                <i class="fas fa-star"></i>
                <span>Favorites</span>
            </a>
            <a href="../navigation/friends/friends.php" class="nav-link">
                <i class="fas fa-users"></i>
                <span>Friends</span>
            </a>
            <a href="../navigation/profile/profile.php" class="nav-link">
                <i class="fas fa-user"></i>
                <span>Profile</span>
            </a>
        </nav>
    </div>

    <!-- Header -->
    <header class="top-header">
        <div class="header-right">
            <div class="notification-icon" onclick="window.location.href='../navigation/notification.php'">
                <i class="fas fa-bell"></i>
                <span class="notification-badge"><?php echo $notification_count; ?></span>
            </div>
            <div class="logout-icon" onclick="showLogoutModal()">
                <i class="fas fa-sign-out-alt"></i>
            </div>
            <div class="user-profile">
                <div class="user-info">
                    <span class="greeting"><?php echo getGreeting(); ?></span>
                    <span class="username"><?php echo htmlspecialchars(explode(' ', $user['username'])[0]); ?></span>
                </div>
                <div class="profile-dropdown">
                    <a href="#" class="profile-icon">
                        <img src="../assets/menu/defaultuser.png" alt="Profile" class="profile-img">
                    </a>
                    <div class="profile-dropdown-content">
                        <div class="profile-dropdown-header">
                            <img src="../assets/menu/defaultuser.png" alt="Profile" class="profile-dropdown-avatar">
                            <div class="profile-dropdown-info">
                                <div class="profile-dropdown-name"><?php echo htmlspecialchars($user['username']); ?></div>
                                <div class="profile-dropdown-email"><?php echo htmlspecialchars($user['email']); ?></div>
                            </div>
                        </div>
                        <div class="profile-dropdown-menu">
                            <a href="../navigation/profile/profile.php" class="profile-dropdown-item">
                                <i class="fas fa-user"></i>
                                <span>View Profile</span>
                            </a>
                            <a href="../navigation/favorites/favorites.php" class="profile-dropdown-item">
                                <i class="fas fa-star"></i>
                                <span>My Favorites</span>
                            </a>
                            <a href="../settings/settings.php" class="profile-dropdown-item">
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

    <div class="main-content">
        <div class="highscore-container">
        <div class="logo-container">
            <img src="../assets/menu/highscoremain.png" alt="High Scores" class="highscore-logo">
        </div>
        <div class="highscore-content">
            <div class="game-section grammar-heroes">
                <div class="game-header">
                    <img src="../assets/selection/Grammarlogo.webp" alt="Grammar Heroes" class="game-logo">
                    <h2 class="pixel-font">Grammar Heroes</h2>
                </div>
                <div class="score-list">
                    <div class="score-item personal-best">
                        <span class="score-label">Personal Best:</span>
                        <span class="score"><?php echo $user_scores['grammar-heroes']['best_score'] ?? 0; ?></span>
                    </div>
                    <div class="score-item">
                        <span class="score-label">Best Level:</span>
                        <span class="score"><?php echo $user_scores['grammar-heroes']['best_level'] ?? 0; ?></span>
                    </div>
                    <div class="score-item">
                        <span class="score-label">Games Played:</span>
                        <span class="score"><?php echo $user_scores['grammar-heroes']['games_played'] ?? 0; ?></span>
                    </div>
                </div>
            </div>
            <div class="game-section vocabworld">
                <div class="game-header">
                    <img src="../assets/selection/vocablogo.webp" alt="Vocabworld" class="game-logo">
                    <h2 class="pixel-font">Vocabworld</h2>
                </div>
                <div class="score-list">
                    <div class="score-item personal-best">
                        <span class="score-label">Personal Best:</span>
                        <span class="score"><?php echo $user_scores['vocabworld']['best_score'] ?? 0; ?></span>
                    </div>
                    <div class="score-item">
                        <span class="score-label">Best Level:</span>
                        <span class="score"><?php echo $user_scores['vocabworld']['best_level'] ?? 0; ?></span>
                    </div>
                    <div class="score-item">
                        <span class="score-label">Games Played:</span>
                        <span class="score"><?php echo $user_scores['vocabworld']['games_played'] ?? 0; ?></span>
                    </div>
                </div>
            </div>
            <nav class="menu-buttons">
                <button id="backToMenu" class="back-button">
                    <i class="fas fa-arrow-left back-icon"></i>
                    Back to Menu
                </button>
            </nav>
        </div>
    </div>
    <div class="toast-overlay"></div>
    <div id="toast" class="toast"></div>
    <script src="../script.js"></script>
    <!-- Logout Confirmation Modal -->
    <div class="toast-overlay" id="logoutModal">
        <div class="toast" id="logoutConfirmation">
            <h3 style="margin-bottom: 1rem; color: #ff6b6b;">Logout Confirmation</h3>
            <p style="margin-bottom: 1.5rem; color: rgba(255, 255, 255, 0.8);">Are you sure you want to logout?</p>
            <div style="display: flex; gap: 1rem; justify-content: center;">
                <button onclick="confirmLogout()" style="background: #ff6b6b; color: white; border: none; padding: 0.5rem 1rem; border-radius: 8px; cursor: pointer; font-family: 'Press Start 2P', cursive; font-size: 0.8rem;">Yes, Logout</button>
                <button onclick="hideLogoutModal()" style="background: rgba(255, 255, 255, 0.2); color: white; border: 1px solid rgba(255, 255, 255, 0.3); padding: 0.5rem 1rem; border-radius: 8px; cursor: pointer; font-family: 'Press Start 2P', cursive; font-size: 0.8rem;">Cancel</button>
            </div>
        </div>
    </div>

    <script src="highscore.js"></script>
    <script src="../navigation/shared/profile-dropdown.js"></script>
    <script src="../navigation/shared/notification-badge.js"></script>
    <script>
        // Pass user data to JavaScript
        window.userData = {
            id: <?php echo $user_id; ?>,
            username: '<?php echo htmlspecialchars($user['username']); ?>',
            email: '<?php echo htmlspecialchars($user['email']); ?>',
            gradeLevel: '<?php echo htmlspecialchars($user['grade_level']); ?>'
        };
        
        // Pass user scores data
        window.userScores = <?php echo json_encode($user_scores); ?>;

        // Logout functionality
        function showLogoutModal() {
            const modal = document.getElementById('logoutModal');
            const confirmation = document.getElementById('logoutConfirmation');
            
            if (modal && confirmation) {
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
            }
        }

        function confirmLogout() {
            // Play click sound
            playClickSound();
            
            // Redirect to logout endpoint
            window.location.href = '../onboarding/logout.php';
        }
    </script>
</body>
</html>
