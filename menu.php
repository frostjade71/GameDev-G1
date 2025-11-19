<?php
require_once 'onboarding/config.php';
require_once 'includes/greeting.php';

// Check if user is logged in, redirect to login if not
if (!isLoggedIn()) {
    header('Location: onboarding/login.php');
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
    header('Location: onboarding/login.php');
    exit();
}

// Get user's game statistics
$games_played = 0;
$high_score = 0;

// Get user's favorites count
$stmt = $pdo->prepare("SELECT COUNT(*) as favorites_count FROM user_favorites WHERE user_id = ?");
$stmt->execute([$user_id]);
$favorites = $stmt->fetch();

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

$favorites_count = $favorites['favorites_count'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="assets/menu/ww_logo_main.webp">
    <title>Menu - Word Weavers</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="navigation/shared/navigation.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="notif/toast.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="menu.css?v=<?php echo time(); ?>">
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
            <img src="assets/menu/Word-Weavers.png" alt="Word Weavers" class="sidebar-logo-img">
        </div>
        <nav class="sidebar-nav">
            <a href="./menu.php" class="nav-link active">
                <i class="fas fa-house"></i>
                <span>Menu</span>
            </a>
            <a href="navigation/favorites/favorites.php" class="nav-link">
                <i class="fas fa-star"></i>
                <span>Favorites</span>
            </a>
            <a href="navigation/friends/friends.php" class="nav-link">
                <i class="fas fa-users"></i>
                <span>Friends</span>
            </a>
            <a href="navigation/profile/profile.php" class="nav-link">
                <i class="fas fa-user"></i>
                <span>Profile</span>
            </a>
        </nav>
    </div>

    <!-- Header -->
    <header class="top-header">
        <div class="header-right">
            <div class="notification-icon" onclick="window.location.href='navigation/notification.php'">
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
                        <img src="assets/menu/defaultuser.png" alt="Profile" class="profile-img">
                    </a>
                    <div class="profile-dropdown-content">
                        <div class="profile-dropdown-header">
                            <img src="assets/menu/defaultuser.png" alt="Profile" class="profile-dropdown-avatar">
                            <div class="profile-dropdown-info">
                                <div class="profile-dropdown-name"><?php echo htmlspecialchars($user['username']); ?></div>
                                <div class="profile-dropdown-email"><?php echo htmlspecialchars($user['email']); ?></div>
                            </div>
                        </div>
                        <div class="profile-dropdown-menu">
                            <a href="navigation/profile/profile.php" class="profile-dropdown-item">
                                <i class="fas fa-user"></i>
                                <span>View Profile</span>
                            </a>
                            <a href="navigation/favorites/favorites.php" class="profile-dropdown-item">
                                <i class="fas fa-star"></i>
                                <span>My Favorites</span>
                            </a>
                            <a href="settings/settings.php" class="profile-dropdown-item">
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

    <!-- Main Content -->
    <div class="main-content">
        <div class="menu-container">
            <!-- Banner -->
            <a href="play/game-selection.php" class="changelog-banner">
                <img src="assets/banner/changelog_banner.png" alt="What's New" class="changelog-banner-image">
            </a>

            <!-- Separator -->
            <div class="menu-separator"></div>

            <!-- Menu Buttons Grid -->
            <div class="menu-buttons-grid">
                <a href="play/game-selection.php" class="menu-button play-button">
                    <div class="button-icon">
                        <img src="assets/pixels/diamondsword.png" alt="Play" class="play-icon">
                    </div>
                    <div class="button-content">
                        <h2>Play</h2>
                        <p>Start your learning adventure</p>
                    </div>
                </a>
                
                <a href="navigation/leaderboards/leaderboards.php" class="menu-button leaderboards-button">
                    <div class="button-icon">
                        <img src="assets/pixels/trophy.png" alt="Leaderboards" class="trophy-icon">
                    </div>
                    <div class="button-content">
                        <h2>Leaderboards</h2>
                        <p>Compete with others</p>
                    </div>
                </a>
                
                <a href="overview/overview.php" class="menu-button overview-button">
                    <div class="button-icon">
                        <img src="assets/pixels/blueorb.png" alt="Overview" class="overview-icon">
                    </div>
                    <div class="button-content">
                        <h2>Overview</h2>
                        <p>Project overview</p>
                    </div>
                </a>
                
                <a href="settings/settings.php" class="menu-button settings-button">
                    <div class="button-icon">
                        <img src="assets/pixels/fix.png" alt="Settings" class="settings-icon">
                    </div>
                    <div class="button-content">
                        <h2>Settings</h2>
                        <p>Customize your experience</p>
                    </div>
                </a>
                
                <a href="credits/credits.php" class="menu-button credits-button">
                    <div class="button-icon">
                        <img src="assets/pixels/greenbook.png" alt="Credits" class="credits-icon">
                    </div>
                    <div class="button-content">
                        <h2>Credits</h2>
                        <p>Meet the team</p>
                    </div>
                </a>
            </div>
        </div>
    </div>

    <div class="toast-overlay"></div>
    <div id="toast" class="toast"></div>
    
    <!-- Logout Confirmation Modal -->
    <div class="toast-overlay" id="logoutModal">
        <div class="toast" id="logoutConfirmation">
            <h3>Logout Confirmation</h3>
            <p>Are you sure you want to logout?</p>
            <div class="modal-buttons">
                <button class="logout-btn" onclick="confirmLogout()">Yes, Logout</button>
                <button class="cancel-btn" onclick="hideLogoutModal()">Cancel</button>
            </div>
        </div>
    </div>

    <script src="script.js"></script>
    <script src="navigation/shared/profile-dropdown.js"></script>
    <script src="navigation/shared/notification-badge.js"></script>
    <script src="menu.js"></script>
    <script>
        // Pass user data to JavaScript
        window.userData = {
            id: <?php echo $user_id; ?>,
            username: '<?php echo htmlspecialchars($user['username']); ?>',
            email: '<?php echo htmlspecialchars($user['email']); ?>',
            gradeLevel: '<?php echo htmlspecialchars($user['grade_level']); ?>',
            gamesPlayed: <?php echo $games_played; ?>,
            highScore: <?php echo $high_score; ?>,
            favoritesCount: <?php echo $favorites_count; ?>
        };

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
            if (typeof playClickSound === 'function') {
                playClickSound();
            }
            
            // Redirect to logout endpoint
            window.location.href = 'onboarding/logout.php';
        }
    </script>
</body>
</html>






