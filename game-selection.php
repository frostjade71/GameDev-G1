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

// Get user's game progress
$stmt = $pdo->prepare("SELECT game_type, MAX(level) as max_level, MAX(score) as best_score 
                      FROM game_scores 
                      WHERE user_id = ? 
                      GROUP BY game_type");
$stmt->execute([$user_id]);
$game_progress = [];
while ($row = $stmt->fetch()) {
    $game_progress[$row['game_type']] = [
        'max_level' => $row['max_level'],
        'best_score' => $row['best_score']
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Game Mode - Word Weavers</title>
    <link rel="stylesheet" href="styles.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="navigation/shared/navigation.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="game-selection.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="notif/toast.css?v=<?php echo time(); ?>">
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
            <a href="menu.php?from=selection" class="nav-link">
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
                <span class="notification-badge">0</span>
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

    <div class="main-content">
        <div class="games-carousel-container">
            <h1 class="game-title">Select Game Mode</h1>
            
            <div class="carousel-container">
                <button class="carousel-button prev">
                    <i class="fas fa-chevron-left"></i>
                </button>
                
                <div class="carousel-track">
                    <div class="game-card" data-game="grammarbg">
                        <div class="card-content">
                            <div class="game-logo">
                                <img src="assets/selection/Grammarlogo.webp" alt="Grammar Heroes Logo">
                            </div>
                            <h2>Grammar Heroes</h2>
                            <p class="game-description">Battle grammar challenges by correcting sentences, and unlock new levels.</p>
                            <?php if (isset($game_progress['grammar-heroes'])): ?>
                                <div class="game-progress">
                                    <span class="progress-text">Best Score: <?php echo $game_progress['grammar-heroes']['best_score']; ?></span>
                                    <span class="progress-text">Level: <?php echo $game_progress['grammar-heroes']['max_level']; ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="game-card" data-game="vocabbg" onclick="playClickSound(); window.location.href='MainGame/vocabworld/index.php'">
                        <div class="card-content">
                            <div class="game-logo">
                                <img src="assets/selection/vocablogo.webp" alt="Vocabworld Logo">
                            </div>
                            <h2>Vocabworld</h2>
                            <p class="game-description">Practice word skills and earn points to customize your character.</p>
                            <?php if (isset($game_progress['vocabworld'])): ?>
                                <div class="game-progress">
                                    <span class="progress-text">Best Score: <?php echo $game_progress['vocabworld']['best_score']; ?></span>
                                    <span class="progress-text">Level: <?php echo $game_progress['vocabworld']['max_level']; ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="game-card coming-soon">
                        <div class="card-content">
                            <div class="game-logo">
                                <i class="fas fa-gamepad"></i>
                            </div>
                            <h2>More Games</h2>
                            <p class="game-description">New exciting games coming soon! Stay tuned for updates.</p>
                            <div class="coming-soon-badge">Coming Soon</div>
                        </div>
                    </div>
                </div>

                <button class="carousel-button next">
                    <i class="fas fa-chevron-right"></i>
                </button>

                <div class="carousel-dots"></div>
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
    <script src="script.js"></script>
    <script src="navigation/shared/notification-badge.js"></script>
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

    <script src="game-selection.js?v=<?php echo time(); ?>"></script>
    <script src="navigation/shared/profile-dropdown.js"></script>
    <script>
        // Pass user data to JavaScript
        window.userData = {
            id: <?php echo $user_id; ?>,
            username: '<?php echo htmlspecialchars($user['username']); ?>',
            email: '<?php echo htmlspecialchars($user['email']); ?>',
            gradeLevel: '<?php echo htmlspecialchars($user['grade_level']); ?>'
        };
        
        // Pass game progress data
        window.gameProgress = <?php echo json_encode($game_progress); ?>;

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
            window.location.href = 'onboarding/logout.php';
        }
        
    </script>
</body>
</html>
