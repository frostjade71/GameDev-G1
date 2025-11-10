<?php
require_once '../../onboarding/config.php';
require_once '../../includes/greeting.php';

// Check if user is logged in, redirect to login if not
if (!isLoggedIn()) {
    header('Location: ../../onboarding/login.php');
    exit();
}

// Get user information
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT username, email, grade_level, section, about_me, created_at FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    // User not found, destroy session and redirect to login
    session_destroy();
    header('Location: ../../onboarding/login.php');
    exit();
}

// Handle profile update
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $section = trim($_POST['section'] ?? '');
    $about_me = trim($_POST['about_me'] ?? '');
    
    // Validate input
    if (empty($username) || empty($email)) {
        echo json_encode(['success' => false, 'message' => 'Username and email are required']);
        exit();
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email format']);
        exit();
    }
    
    // Check if username or email already exists (excluding current user)
    $stmt = $pdo->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
    $stmt->execute([$username, $email, $user_id]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Username or email already exists']);
        exit();
    }
    
    // Update user profile
    $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, section = ?, about_me = ?, updated_at = NOW() WHERE id = ?");
    if ($stmt->execute([$username, $email, $section, $about_me, $user_id])) {
        // Return the updated values for JavaScript to use
        echo json_encode([
            'success' => true, 
            'message' => 'Profile updated successfully!',
            'about_me' => $about_me,
            'section' => $section,
            'username' => $username
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update profile']);
    }
    exit();
}

// Get user's GWA (General Weighted Average) for each game
$stmt = $pdo->prepare("SELECT 
    game_type,
    AVG(score) as gwa_score,
    COUNT(*) as play_count,
    MAX(score) as best_score
    FROM game_scores 
    WHERE user_id = ?
    GROUP BY game_type");
$stmt->execute([$user_id]);
$game_gwa = $stmt->fetchAll();

// Get user's favorites with game info
$stmt = $pdo->prepare("SELECT game_type FROM user_favorites WHERE user_id = ?");
$stmt->execute([$user_id]);
$favorites = $stmt->fetchAll();

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

// Get user's friends count
$stmt = $pdo->prepare("
    SELECT COUNT(*) as friends_count 
    FROM friends 
    WHERE user1_id = ? OR user2_id = ?
");
$stmt->execute([$user_id, $user_id]);
$friends_count = $stmt->fetch()['friends_count'];

// No longer calculating user level since it's been replaced with email
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../../assets/menu/ww_logo_main.webp">
    <title>Profile - Word Weavers</title>
    <link rel="stylesheet" href="../../styles.css">
    <link rel="stylesheet" href="../shared/navigation.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../../notif/toast.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="profile.css?v=<?php echo time(); ?>">
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
            <img src="../../assets/menu/Word-Weavers.png" alt="Word Weavers" class="sidebar-logo-img">
        </div>
        <nav class="sidebar-nav">
            <a href="../../menu.php" class="nav-link">
                <i class="fas fa-house"></i>
                <span>Menu</span>
            </a>
            <a href="../favorites/favorites.php" class="nav-link">
                <i class="fas fa-star"></i>
                <span>Favorites</span>
            </a>
            <a href="../friends/friends.php" class="nav-link">
                <i class="fas fa-users"></i>
                <span>Friends</span>
            </a>
            <div class="nav-item-with-dropdown">
                <a href="profile.php" class="nav-link active" id="profile-dropdown-trigger">
                    <i class="fas fa-user"></i>
                    <span>Profile</span>
                    <i class="fas fa-chevron-down dropdown-arrow"></i>
                </a>
                <div class="nav-dropdown-menu">
                    <?php if (in_array($user['grade_level'], ['Developer', 'Admin'])): ?>
                    <a href="../moderation/moderation.php" class="nav-dropdown-item" id="moderation-panel">
                        <i class="fas fa-shield-alt"></i>
                        <span>Moderation</span>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </nav>
    </div>

    <!-- Header -->
    <header class="top-header">
        <div class="header-right">
            <div class="notification-icon" onclick="window.location.href='../notification.php'">
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
                        <img src="../../assets/menu/defaultuser.png" alt="Profile" class="profile-img">
                    </a>
                    <div class="profile-dropdown-content">
                        <div class="profile-dropdown-header">
                            <img src="../../assets/menu/defaultuser.png" alt="Profile" class="profile-dropdown-avatar">
                            <div class="profile-dropdown-info">
                                <div class="profile-dropdown-name"><?php echo htmlspecialchars($user['username']); ?></div>
                                <div class="profile-dropdown-email"><?php echo htmlspecialchars($user['email']); ?></div>
                            </div>
                        </div>
                        <div class="profile-dropdown-menu">
                            <a href="profile.php" class="profile-dropdown-item">
                                <i class="fas fa-user"></i>
                                <span>View Profile</span>
                            </a>
                            <a href="../favorites/favorites.php" class="profile-dropdown-item">
                                <i class="fas fa-star"></i>
                                <span>My Favorites</span>
                            </a>
                            <a href="../../settings/settings.php" class="profile-dropdown-item">
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
        <div class="profile-container">
            <div class="profile-header">
                <div class="profile-avatar">
                    <img src="../../assets/menu/defaultuser.png" alt="Profile" class="large-avatar">
                    <button class="change-avatar-btn">
                        <i class="fas fa-camera"></i>
                    </button>
                </div>
                <div class="profile-info">
                    <h1><?php echo htmlspecialchars($user['username']); ?></h1>
                    <p class="player-email"><?php echo htmlspecialchars($user['email']); ?></p>
                    <p class="friends-count"><?php echo $friends_count; ?> Friends</p>
                    <p class="about-me-text"><?php echo ($user['about_me'] !== null && $user['about_me'] !== '') ? htmlspecialchars($user['about_me']) : 'Tell us something about yourself...'; ?></p>
                    <div class="badge-container">
                        <?php 
                        $is_jaderby = (strtolower($user['username']) === 'jaderby garcia peñaranda');
                        $is_admin = ($user['grade_level'] === 'Admin' || $is_jaderby);
                        $is_teacher = ($user['grade_level'] === 'Teacher');
                        
                        if ($is_jaderby): ?>
                            <div class="badge-wrapper" onclick="showBadgeInfo('Developer', 'Lead Developer of Word Weavers'); return false;">
                                <img src="../../assets/badges/developer.png" alt="Developer Badge" class="user-badge">
                                <div class="badge-tooltip">
                                    <span class="badge-title">Developer</span>
                                    <span class="badge-desc">Lead Developer</span>
                                </div>
                            </div>
                        <?php endif; ?>
                        <?php if ($is_admin): ?>
                            <div class="badge-wrapper" onclick="showBadgeInfo('Administrator', 'Has full administrative privileges' . ($is_jaderby ? ' and is the developer' : '') . '.'); return false;">
                                <img src="../../assets/badges/moderator.png" alt="Admin Badge" class="user-badge">
                                <div class="badge-tooltip">
                                    <span class="badge-title">Admin</span>
                                    <span class="badge-desc">System Admin</span>
                                </div>
                            </div>
                        <?php endif; ?>
                        <?php if ($is_teacher): ?>
                            <div class="badge-wrapper" onclick="showBadgeInfo('Teacher', 'Certified educator with teaching privileges.'); return false;">
                                <img src="../../assets/badges/teacher.png" alt="Teacher Badge" class="user-badge">
                                <div class="badge-tooltip">
                                    <span class="badge-title">Teacher</span>
                                    <span class="badge-desc">Educator</span>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Grade & Section Container -->
            <div class="grade-section-container">
                <div class="section-header">
                    <i class="fas fa-graduation-cap"></i>
                    <h3>Grade & Section</h3>
                </div>
                <div class="grade-section-content">
                    <div class="grade-info">
                        <span class="info-label">Grade:</span>
                        <span class="info-value"><?php echo htmlspecialchars($user['grade_level']); ?></span>
                    </div>
                    <div class="section-info">
                        <span class="info-label">Section:</span>
                        <span class="info-value"><?php echo !empty($user['section']) ? htmlspecialchars($user['section']) : 'Not specified'; ?></span>
                    </div>
                </div>
            </div>
            
            <!-- GWA Section -->
            <div class="gwa-section">
                <div class="section-header">
                    <i class="fas fa-chart-line"></i>
                    <h3>General Weighted Average (GWA)</h3>
                </div>
                <div class="gwa-container">
                    <?php if (empty($game_gwa)): ?>
                        <div class="no-data">
                            <i class="fas fa-chart-bar"></i>
                            <p>No game data available yet. Play some games to see your GWA!</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($game_gwa as $game): ?>
                            <?php
                            $game_name = '';
                            $game_logo = '';
                            switch ($game['game_type']) {
                                case 'grammar-heroes':
                                    $game_name = 'Grammar Heroes';
                                    $game_logo = '../../assets/selection/Grammarlogo.webp';
                                    break;
                                case 'vocabworld':
                                    $game_name = 'Vocabworld';
                                    $game_logo = '../../assets/selection/vocablogo.webp';
                                    break;
                                default:
                                    $game_name = ucfirst(str_replace('-', ' ', $game['game_type']));
                                    $game_logo = '../../assets/selection/vocablogo.webp';
                            }
                            ?>
                            <div class="gwa-card">
                                <div class="game-logo-container">
                                    <img src="<?php echo $game_logo; ?>" alt="<?php echo $game_name; ?>" class="game-logo">
                                </div>
                                <div class="gwa-info">
                                    <h4><?php echo $game_name; ?></h4>
                                    <div class="gwa-stats">
                                        <div class="gwa-item">
                                            <span class="gwa-label">GWA:</span>
                                            <span class="gwa-value"><?php echo number_format($game['gwa_score'], 1); ?></span>
                                        </div>
                                        <div class="gwa-item">
                                            <span class="gwa-label">Best:</span>
                                            <span class="gwa-value"><?php echo number_format($game['best_score']); ?></span>
                                        </div>
                                        <div class="gwa-item">
                                            <span class="gwa-label">Plays:</span>
                                            <span class="gwa-value"><?php echo $game['play_count']; ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Favorites Section -->
            <div class="favorites-section">
                <div class="section-header">
                    <i class="fas fa-heart"></i>
                    <h3>Favorite Games</h3>
                </div>
                <div class="favorites-container">
                    <?php if (empty($favorites)): ?>
                        <div class="no-data">
                            <i class="fas fa-heart-broken"></i>
                            <p>No favorite games yet. Add some games to your favorites!</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($favorites as $favorite): ?>
                            <?php
                            $game_name = '';
                            $game_logo = '';
                            switch ($favorite['game_type']) {
                                case 'grammar-heroes':
                                    $game_name = 'Grammar Heroes';
                                    $game_logo = '../../assets/selection/Grammarlogo.webp';
                                    break;
                                case 'vocabworld':
                                    $game_name = 'Vocabworld';
                                    $game_logo = '../../assets/selection/vocablogo.webp';
                                    break;
                                default:
                                    $game_name = ucfirst(str_replace('-', ' ', $favorite['game_type']));
                                    $game_logo = '../../assets/selection/vocablogo.webp';
                            }
                            ?>
                            <div class="favorite-card">
                                <div class="game-logo-container">
                                    <img src="<?php echo $game_logo; ?>" alt="<?php echo $game_name; ?>" class="game-logo">
                                </div>
                                <div class="favorite-info">
                                    <h4><?php echo $game_name; ?></h4>
                                    <i class="fas fa-heart favorite-icon"></i>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Member Since Section -->
            <div class="member-since-section">
                <p class="member-since">Member since <?php echo date('F j, Y', strtotime($user['created_at'])); ?></p>
            </div>
            
            <div class="profile-settings">
                <h2><i class="fas fa-cog"></i> Profile Settings</h2>
                <form class="settings-form" id="profileForm">
                    <div class="form-group">
                        <label>Player Name</label>
                        <input type="text" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>About Me</label>
                        <textarea name="about_me" rows="4" placeholder="Tell us about yourself..."><?php echo htmlspecialchars($user['about_me'] ?? ''); ?></textarea>
                    </div>
                    <div class="form-group">
                        <label>Grade Level</label>
                        <input type="text" value="<?php echo htmlspecialchars($user['grade_level']); ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label>Section</label>
                        <input type="text" name="section" value="<?php echo htmlspecialchars($user['section'] ?? ''); ?>" placeholder="Enter your section (e.g., A, B, Diamond, etc.)">
                    </div>
                    <button type="submit" class="save-button">Save Changes</button>
                </form>
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

    <script src="../../script.js"></script>
    <script src="../shared/notification-badge.js"></script>
    <script>
    // Mobile menu functionality
    document.addEventListener('DOMContentLoaded', function() {
        const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
        const sidebar = document.querySelector('.sidebar');
        const profileTrigger = document.getElementById('profile-dropdown-trigger');
        const dropdownMenu = document.querySelector('.nav-dropdown-menu');
        
        // Initialize mobile menu
        if (mobileMenuBtn && sidebar) {
            // Make sure sidebar is hidden by default on mobile
            if (window.innerWidth <= 768) {
                sidebar.style.transform = 'translateX(-100%)';
            }
            
            // Simple toggle function for mobile menu
            function toggleMobileMenu() {
                if (sidebar.style.transform === 'translateX(0%)') {
                    sidebar.style.transform = 'translateX(-100%)';
                    document.body.style.overflow = '';
                } else {
                    sidebar.style.transform = 'translateX(0%)';
                    document.body.style.overflow = 'hidden';
                }
            }
            
            // Add click event to mobile menu button
            mobileMenuBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                toggleMobileMenu();
            });
            
            // Close menu when clicking outside
            document.addEventListener('click', function(e) {
                if (window.innerWidth <= 768 && 
                    sidebar.style.transform === 'translateX(0%)' && 
                    !sidebar.contains(e.target) && 
                    !mobileMenuBtn.contains(e.target)) {
                    toggleMobileMenu();
                }
            });
            
            // Handle profile dropdown if it exists
            if (profileTrigger && dropdownMenu) {
                // Close dropdown initially
                dropdownMenu.style.display = 'none';
                
                // Toggle dropdown on click
                const toggleDropdown = (e) => {
                    if (e) {
                        e.preventDefault();
                        e.stopPropagation();
                    }
                    
                    const isVisible = dropdownMenu.style.display === 'block';
                    dropdownMenu.style.display = isVisible ? 'none' : 'block';
                    
                    // Toggle active class for arrow rotation
                    const parentItem = profileTrigger.closest('.nav-item-with-dropdown');
                    parentItem.classList.toggle('active', !isVisible);
                    
                    // For mobile, ensure the dropdown is visible in the viewport
                    if (!isVisible && window.innerWidth <= 768) {
                        // Ensure sidebar is open on mobile when clicking dropdown
                        if (!sidebar.classList.contains('active')) {
                            toggleSidebar();
                        }
                        // Small delay to ensure the sidebar is open before scrolling
                        setTimeout(() => {
                            dropdownMenu.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                        }, 50);
                    }
                };
                
                // Handle both touch and click events for better mobile support
                profileTrigger.addEventListener('click', toggleDropdown);
                
                // Close dropdown when clicking outside on both desktop and mobile
                const handleOutsideClick = (e) => {
                    // Don't close if clicking on profile trigger or dropdown menu
                    if (profileTrigger.contains(e.target) || dropdownMenu.contains(e.target)) {
                        return;
                    }
                    
                    // For mobile, check if clicking outside sidebar
                    if (window.innerWidth <= 768) {
                        if (!sidebar.contains(e.target) && !mobileMenuBtn.contains(e.target)) {
                            dropdownMenu.style.display = 'none';
                            profileTrigger.closest('.nav-item-with-dropdown').classList.remove('active');
                        }
                    } else {
                        // For desktop, just close the dropdown
                        dropdownMenu.style.display = 'none';
                        profileTrigger.closest('.nav-item-with-dropdown').classList.remove('active');
                    }
                };
                
                // Use both click and touch events for better mobile support
                document.addEventListener('click', handleOutsideClick);
                document.addEventListener('touchend', handleOutsideClick);
                
                // Close on escape key
                document.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape' && dropdownMenu.style.display === 'block') {
                        dropdownMenu.style.display = 'none';
                        profileTrigger.closest('.nav-item-with-dropdown').classList.remove('active');
                    }
                });
            }
            
            // Close menu when clicking outside on mobile
            document.addEventListener('click', function(e) {
                if (window.innerWidth <= 768 && 
                    sidebar.classList.contains('active') && 
                    !sidebar.contains(e.target) && 
                    !mobileMenuBtn.contains(e.target) &&
                    !(dropdownMenu && dropdownMenu.contains(e.target))) {
                    sidebar.classList.remove('active');
                    document.body.style.overflow = '';
                    
                    // Also close dropdown if open
                    if (dropdownMenu && dropdownMenu.style.display === 'block') {
                        dropdownMenu.style.display = 'none';
                        if (profileTrigger) {
                            profileTrigger.closest('.nav-item-with-dropdown').classList.remove('active');
                        }
                    }
                }
            });
            
            // Close menu when clicking a nav link on mobile
            const navLinks = document.querySelectorAll('.nav-link:not(#profile-dropdown-trigger)');
            navLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    if (window.innerWidth <= 768) {
                        // Don't close if this is the profile dropdown trigger
                        if (this.id === 'profile-dropdown-trigger') {
                            return;
                        }
                        sidebar.classList.remove('active');
                        document.body.style.overflow = '';
                    }
                });
            });
            
            // Handle window resize to ensure proper behavior
            window.addEventListener('resize', function() {
                // If resizing to mobile view, ensure the dropdown is closed
                if (window.innerWidth <= 768) {
                    if (dropdownMenu) {
                        dropdownMenu.style.display = 'none';
                        if (profileTrigger) {
                            profileTrigger.closest('.nav-item-with-dropdown').classList.remove('active');
                        }
                    }
                }
            });
        }
    });
    </script>
    <script>
    // Inline JavaScript to handle profile form
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.querySelector('.settings-form');
        
        if (form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData();
                formData.append('action', 'update_profile');
                formData.append('username', document.querySelector('input[name="username"]').value);
                formData.append('email', document.querySelector('input[name="email"]').value);
                formData.append('about_me', document.querySelector('textarea[name="about_me"]').value);
                formData.append('section', document.querySelector('input[name="section"]').value);
                
                fetch('profile.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Show success message using the existing toast system
                        if (typeof showToast === 'function') {
                            showToast(data.message);
                        }
                        
                        // Update the About Me text immediately without reload
                        const aboutMeElement = document.querySelector('.about-me-text');
                        if (aboutMeElement && data.about_me !== undefined) {
                            const newText = (data.about_me && data.about_me.trim() !== '') ? data.about_me : 'Tell us something about yourself...';
                            aboutMeElement.textContent = newText;
                        }
                        
                        // Update the Section display immediately without reload
                        const sectionElement = document.querySelector('.section-info .info-value');
                        if (sectionElement && data.section !== undefined) {
                            const sectionText = (data.section && data.section.trim() !== '') ? data.section : 'Not specified';
                            sectionElement.textContent = sectionText;
                        }
                        
                        // Update username in header if it exists (show only first name)
                        const usernameElements = document.querySelectorAll('.username');
                        if (data.username) {
                            usernameElements.forEach(el => {
                                el.textContent = data.username.split(' ')[0];
                            });
                        }
                        
                        // Fallback: if the text didn't update properly, reload after a short delay
                        setTimeout(() => {
                            const currentText = aboutMeElement?.textContent;
                            if (data.about_me && data.about_me.trim() !== '' && currentText === 'Tell us something about yourself...') {
                                window.location.reload();
                            }
                        }, 500);
                        
                    } else {
                        if (typeof showToast === 'function') {
                            showToast('Error: ' + data.message);
                        } else {
                            alert('Error: ' + data.message);
                        }
                    }
                })
                .catch(error => {
                    alert('Error updating profile');
                });
            });
        }
    });
    </script>
    <script>
        // Pass user data to JavaScript
        window.userData = {
            id: <?php echo $user_id; ?>,
            username: '<?php echo htmlspecialchars($user['username']); ?>',
            email: '<?php echo htmlspecialchars($user['email']); ?>',
            gradeLevel: '<?php echo htmlspecialchars($user['grade_level']); ?>',
            section: '<?php echo htmlspecialchars($user['section'] ?? ''); ?>',
            aboutMe: '<?php echo htmlspecialchars($user['about_me'] ?? ''); ?>',
            gameGWA: <?php echo json_encode($game_gwa); ?>,
            favorites: <?php echo json_encode($favorites); ?>
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
            playClickSound();
            
            // Redirect to logout endpoint
            window.location.href = '../../onboarding/logout.php';
        }
    </script>
</body>
</html>