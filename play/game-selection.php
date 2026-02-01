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
$stmt = $pdo->prepare("SELECT username, email, grade_level, profile_image FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    // User not found, destroy session and redirect to login
    session_destroy();
    header('Location: ../onboarding/login.php');
    exit();
}

// Get user's game progress
$game_progress = [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include '../includes/favicon.php'; ?>
    <title>Select Game - Word Weavers</title>
    <link rel="stylesheet" href="../styles.css?v=<?php echo filemtime('../styles.css'); ?>">
    <link rel="stylesheet" href="../navigation/shared/navigation.css?v=<?php echo filemtime('../navigation/shared/navigation.css'); ?>">
    <link rel="stylesheet" href="game-selection.css?v=<?php echo filemtime('game-selection.css'); ?>">
    <link rel="stylesheet" href="../notif/toast.css?v=<?php echo filemtime('../notif/toast.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>
<body>
    <?php include '../includes/page-loader.php'; ?>
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
            <a href="../menu.php?from=selection" class="nav-link">
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
            <?php if (in_array($user['grade_level'], ['Teacher', 'Admin', 'Developer'])): ?>
            <a href="../navigation/teacher/dashboard.php" class="nav-link">
                <i class="fas fa-chalkboard-teacher"></i>
                <span>Teacher</span>
            </a>
            <?php endif; ?>
            <?php if (in_array($user['grade_level'], ['Developer', 'Admin'])): ?>
            <a href="../navigation/admin/dashboard.php" class="nav-link">
                <i class="fas fa-shield-alt"></i>
                <span>Admin</span>
            </a>
            <?php endif; ?>
        </nav>
    </div>

    <!-- Header -->
    <header class="top-header">
        <div class="header-right">
            <div class="notification-icon" onclick="window.location.href='../navigation/notification.php'">
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
                        <img src="<?php echo !empty($user['profile_image']) ? '../' . htmlspecialchars($user['profile_image']) : '../assets/menu/defaultuser.png'; ?>" alt="Profile" class="profile-img">
                    </a>
                    <div class="profile-dropdown-content">
                        <div class="profile-dropdown-header">
                            <img src="<?php echo !empty($user['profile_image']) ? '../' . htmlspecialchars($user['profile_image']) : '../assets/menu/defaultuser.png'; ?>" alt="Profile" class="profile-dropdown-avatar">
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
        <div class="games-carousel-container">
            <h1 class="game-title">Select a Game</h1>
            
            <div class="cards-container">
                <!-- VocabWorld Card -->
                <div class="main card game-card" data-game="vocabbg">
                    <div class="card_content">
                        <img src="../MainGame/vocabworld/assets/menu/spaceplay.gif" alt="VocabWorld Preview" style="width: 100%; height: 100%; object-fit: cover; border-radius: 7px;">
                        <div class="play-icon-overlay">
                            <i class="fas fa-play"></i>
                        </div>
                    </div>
                    <div class="card_back"></div>
                    <div class="data">
                        <div class="img">
                            <img src="../MainGame/vocabworld/assets/menu/vv_logo.webp" alt="Vocabworld Logo">
                        </div>
                        <div class="text">
                            <div class="text_m">Vocabworld</div>
                        </div>
                    </div>
                    <div class="btns">
                        <div class="likes">
                            <svg class="likes_svg" viewBox="-2 0 105 92"><path d="M85.24 2.67C72.29-3.08 55.75 2.67 50 14.9 44.25 2 27-3.8 14.76 2.67 1.1 9.14-5.37 25 5.42 44.38 13.33 58 27 68.11 50 86.81 73.73 68.11 87.39 58 94.58 44.38c10.79-18.7 4.32-35.24-9.34-41.71Z"></path></svg>
                            <span class="likes_text"><?php echo isset($game_progress['vocabworld']) ? $game_progress['vocabworld']['best_score'] : '0'; ?></span>
                        </div>
                        <div class="comments">
                            <svg class="comments_svg" viewBox="-405.9 238 56.3 54.8" title="Level"><path d="M-391 291.4c0 1.5 1.2 1.7 1.9 1.2 1.8-1.6 15.9-14.6 15.9-14.6h19.3c3.8 0 4.4-.8 4.4-4.5v-31.1c0-3.7-.8-4.5-4.4-4.5h-47.4c-3.6 0-4.4.9-4.4 4.5v31.1c0 3.7.7 4.4 4.4 4.4h10.4v13.5z"></path></svg>
                            <span class="comments_text"><?php echo isset($game_progress['vocabworld']) ? $game_progress['vocabworld']['max_level'] : '1'; ?></span>
                        </div>
                    </div>
                </div>

                <!-- Grammar Heroes Card -->
                <div class="main card game-card" data-game="grammarbg">
                    <div class="card_content">
                        <img src="../MainGame/vocabworld/assets/menu/grammarbg.gif" alt="Grammar Heroes Preview" style="width: 100%; height: 100%; object-fit: cover; border-radius: 7px;">
                        <div class="play-icon-overlay">
                            <i class="fas fa-play"></i>
                        </div>
                    </div>
                    <div class="card_back"></div>
                    <div class="data">
                        <div class="img">
                            <img src="../assets/selection/Grammarlogo.webp" alt="Grammar Heroes Logo">
                        </div>
                        <div class="text">
                            <div class="text_m">Grammar Heroes</div>
                        </div>
                    </div>
                    <div class="btns">
                        <div class="likes">
                            <svg class="likes_svg" viewBox="-2 0 105 92"><path d="M85.24 2.67C72.29-3.08 55.75 2.67 50 14.9 44.25 2 27-3.8 14.76 2.67 1.1 9.14-5.37 25 5.42 44.38 13.33 58 27 68.11 50 86.81 73.73 68.11 87.39 58 94.58 44.38c10.79-18.7 4.32-35.24-9.34-41.71Z"></path></svg>
                            <span class="likes_text"><?php echo isset($game_progress['grammar-heroes']) ? $game_progress['grammar-heroes']['best_score'] : '0'; ?></span>
                        </div>
                        <div class="comments">
                            <svg class="comments_svg" viewBox="-405.9 238 56.3 54.8" title="Level"><path d="M-391 291.4c0 1.5 1.2 1.7 1.9 1.2 1.8-1.6 15.9-14.6 15.9-14.6h19.3c3.8 0 4.4-.8 4.4-4.5v-31.1c0-3.7-.8-4.5-4.4-4.5h-47.4c-3.6 0-4.4.9-4.4 4.5v31.1c0 3.7.7 4.4 4.4 4.4h10.4v13.5z"></path></svg>
                            <span class="comments_text"><?php echo isset($game_progress['grammar-heroes']) ? $game_progress['grammar-heroes']['max_level'] : '1'; ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <nav class="menu-buttons">
                <button id="backToMenu" class="back-button">
                    <i class="fas fa-arrow-left back-icon"></i>
                    Back
                </button>
            </nav>
        </div>
    </div>
    <div class="toast-overlay"></div>
    <div id="toast" class="toast"></div>
    <script src="../script.js"></script>
    <script src="../navigation/shared/notification-badge.js"></script>
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

    <script src="game-selection.js?v=<?php echo filemtime('game-selection.js'); ?>"></script>
    <script src="../navigation/shared/profile-dropdown.js"></script>
    <script>
        // Override sound paths for this page
        const originalShowToast = window.showToast;
        const originalPlayClickSound = window.playClickSound;
        
        window.showToast = function(message, iconPath = null) {
            const toast = document.getElementById('toast');
            const overlay = document.querySelector('.toast-overlay');
            
            if (toast && overlay) {
                // Play toast notification sound with correct path
                const toastSound = new Audio('../assets/sounds/toast/toastnotifwarn.mp3');
                toastSound.volume = 0.5;
                toastSound.play().catch(error => {
                    console.log('Error playing toast sound:', error);
                });
                
                // Clear previous content
                toast.innerHTML = '';
                
                // Create container for vertical layout
                const container = document.createElement('div');
                container.style.cssText = 'display: flex; flex-direction: column; align-items: center; text-align: center;';
                
                // Add icon if provided
                if (iconPath) {
                    const icon = document.createElement('img');
                    icon.src = iconPath;
                    icon.alt = 'Icon';
                    icon.style.cssText = 'width: 24px; height: 24px; margin-bottom: 8px;';
                    container.appendChild(icon);
                }
                
                // Add message
                const messageSpan = document.createElement('span');
                messageSpan.textContent = message;
                messageSpan.style.cssText = 'font-family: "Press Start 2P", cursive; font-size: 14px;';
                container.appendChild(messageSpan);
                
                toast.appendChild(container);
                
                // Show overlay and toast
                overlay.classList.add('show');
                toast.classList.remove('hide');
                toast.classList.add('show');
                
                // Hide after delay
                setTimeout(() => {
                    toast.classList.remove('show');
                    toast.classList.add('hide');
                    overlay.classList.remove('show');
                }, 1500);
            } else {
                console.error('Toast or overlay elements not found');
            }
        };
        
        window.playClickSound = function() {
            const clickSound = new Audio('../assets/sounds/clicks/mixkit-stapling-paper-2995.wav');
            clickSound.play().catch(error => {
                console.log('Error playing click sound:', error);
            });
        };
        
        // Override notification badge path for this page
        const originalUpdateNotificationBadge = updateNotificationBadge;
        updateNotificationBadge = function() {
            const badge = document.querySelector('.notification-badge');
            if (!badge) return;
            
            // Make API call to get notification count
            fetch('../navigation/get_notification_count.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const count = data.count;
                        badge.textContent = count;
                        
                        // Add pulse animation if there are new notifications
                        if (count > 0) {
                            badge.classList.add('pulse');
                        } else {
                            badge.classList.remove('pulse');
                        }
                    }
                })
                .catch(error => {
                    console.error('Error updating notification badge:', error);
                });
        };
        
        // Initialize notification badge
        document.addEventListener('DOMContentLoaded', function() {
            updateNotificationBadge();
            // Update every 30 seconds
            setInterval(updateNotificationBadge, 30000);
        });
    </script>
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
