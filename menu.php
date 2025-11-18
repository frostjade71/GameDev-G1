<?php
// Start output buffering
ob_start();

// Set security headers
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

// Set cache control headers
$cache_time = 3600; // 1 hour
header('Cache-Control: private, max-age=' . $cache_time);
header('Pragma: cache');
header_remove('Expires');

require_once 'onboarding/config.php';
require_once 'includes/greeting.php';

// Check if user is logged in, redirect to login if not
if (!isLoggedIn()) {
    header('Location: onboarding/login.php');
    exit();
}

try {
    $user_id = $_SESSION['user_id'];
    
    // Combine database queries for better performance
    $stmt = $pdo->prepare("
        SELECT 
            u.username, 
            u.email, 
            u.grade_level,
            (SELECT COUNT(*) FROM game_scores WHERE user_id = ?) as games_played,
            (SELECT MAX(score) FROM game_scores WHERE user_id = ?) as high_score,
            (SELECT COUNT(*) FROM user_favorites WHERE user_id = ?) as favorites_count
        FROM users u 
        WHERE u.id = ?
        LIMIT 1
    ");
    $stmt->execute([$user_id, $user_id, $user_id, $user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        // User not found, destroy session and redirect to login
        session_destroy();
        header('Location: onboarding/login.php');
        exit();
    }

    // Get pending friend requests count in a single query
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as request_count 
        FROM friend_requests 
        WHERE receiver_id = ? AND status = 'pending'
    ");
    $stmt->execute([$user_id]);
    $notification_count = $stmt->fetchColumn();

    // Set default values if null
    $games_played = (int)($user['games_played'] ?? 0);
    $high_score = (int)($user['high_score'] ?? 0);
    $favorites_count = (int)($user['favorites_count'] ?? 0);
    
    // Free the result
    $user['games_played'] = $games_played;
    $user['high_score'] = $high_score;
    $user['favorites_count'] = $favorites_count;
    
} catch (PDOException $e) {
    // Log error and show generic message
    error_log('Database error in menu.php: ' . $e->getMessage());
    die('An error occurred while loading the page. Please try again later.');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="theme-color" content="#ffffff">
    <link rel="icon" type="image/webp" href="assets/menu/ww_logo_main.webp" sizes="any">
    <title>Menu - Word Weavers</title>
    
    <!-- Preload critical CSS -->
    <link rel="preload" href="styles.css" as="style">
    <link rel="preload" href="navigation/shared/navigation.css" as="style">
    <link rel="preload" href="menu.css" as="style">
    
    <!-- Load styles -->
    <link rel="stylesheet" href="styles.css" media="print" onload="this.media='all'">
    <link rel="stylesheet" href="navigation/shared/navigation.css" media="print" onload="this.media='all'">
    <link rel="stylesheet" href="menu.css" media="print" onload="this.media='all'">
    
    <!-- Load non-critical CSS asynchronously -->
    <link rel="stylesheet" href="notif/toast.css" media="print" onload="this.media='all'">
    
    <!-- Preconnect to CDN -->
    <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
    <!-- Load Font Awesome with integrity check -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" 
          integrity="sha512-z3gLpd7yknf1YoNbCzqRKc4qyor8gaKU1qmn+CShxbuBusANI9QpRohGBreCFkKxLhei6S9CQXFEbbKuqLg0DA==" 
          crossorigin="anonymous" 
          referrerpolicy="no-referrer" />
    
    <!-- Preload critical above-the-fold images -->
    <link rel="preload" as="image" href="assets/menu/Word-Weavers.png" imagesrcset="assets/menu/Word-Weavers.png" fetchpriority="high">
    <link rel="preload" as="image" href="assets/menu/blue-play.png" imagesrcset="assets/menu/blue-play.png" fetchpriority="high">
    
    <!-- Preload other important images -->
    <link rel="preload" as="image" href="assets/banner/changelog_banner.png" imagesrcset="assets/banner/changelog_banner.png" fetchpriority="low">
    <link rel="preload" as="image" href="assets/menu/trophy-menu.png" imagesrcset="assets/menu/trophy-menu.png" fetchpriority="low">
</head>
<body>
    <!-- Mobile Menu Button -->
    <button class="mobile-menu-btn" aria-label="Open menu">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-logo">
            <img src="assets/menu/Word-Weavers.png" alt="Word Weavers" class="sidebar-logo-img" width="200" height="50" loading="eager" decoding="async">
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
            <a href="game-selection.php" class="changelog-banner">
                <img src="assets/banner/changelog_banner.png" alt="What's New" class="changelog-banner-image">
            </a>

            <!-- Separator -->
            <div class="menu-separator"></div>

            <!-- Menu Buttons Grid -->
            <div class="menu-buttons-grid">
                <a href="game-selection.php" class="menu-button play-button">
                    <div class="button-icon">
                        <img src="assets/menu/blue-play.png" alt="Play" class="play-icon">
                    </div>
                    <div class="button-content">
                        <h2>Play</h2>
                        <p>Start your learning adventure</p>
                    </div>
                </a>
                
                <a href="navigation/leaderboards/leaderboards.php" class="menu-button leaderboards-button">
                    <div class="button-icon">
                        <img src="assets/menu/trophy-menu.png" alt="Leaderboards" class="trophy-icon">
                    </div>
                    <div class="button-content">
                        <h2>Leaderboards</h2>
                        <p>Compete with others</p>
                    </div>
                </a>
                
                <a href="overview/overview.php" class="menu-button overview-button">
                    <div class="button-icon">
                        <i class="fas fa-book"></i>
                    </div>
                    <div class="button-content">
                        <h2>Overview</h2>
                        <p>Project overview</p>
                    </div>
                </a>
                
                <a href="settings/settings.php" class="menu-button settings-button">
                    <div class="button-icon">
                        <i class="fas fa-cog"></i>
                    </div>
                    <div class="button-content">
                        <h2>Settings</h2>
                        <p>Customize your experience</p>
                    </div>
                </a>
                
                <a href="credits.php" class="menu-button credits-button">
                    <div class="button-icon">
                        <i class="fas fa-info-circle"></i>
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






