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

// Get pending friend requests for the current user
$stmt = $pdo->prepare("
    SELECT fr.id, fr.requester_id, fr.created_at, u.username, u.email, u.grade_level
    FROM friend_requests fr
    JOIN users u ON fr.requester_id = u.id
    WHERE fr.receiver_id = ? AND fr.status = 'pending'
    ORDER BY fr.created_at DESC
");
$stmt->execute([$user_id]);
$friend_requests = $stmt->fetchAll();

// Get crescent notifications
$stmt = $pdo->prepare("
    SELECT id, type, message, data, created_at
    FROM notifications
    WHERE user_id = ? AND type = 'cresent_received'
");
$stmt->execute([$user_id]);
$cresent_notifications = $stmt->fetchAll();

// Get notification count for badge (both friend requests and crescent notifications)
$notification_count = count($friend_requests) + count($cresent_notifications);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/webp" href="../assets/menu/ww_logo_main.webp">
    <title>Credits - Word Weavers</title>
    <link rel="stylesheet" href="../navigation/shared/navigation.css?v=<?php echo filemtime('../navigation/shared/navigation.css'); ?>">
    <link rel="stylesheet" href="../styles.css?v=<?php echo filemtime('../styles.css'); ?>">
    <link rel="stylesheet" href="credits.css?v=<?php echo filemtime('credits.css'); ?>">
    <link rel="stylesheet" href="../notif/toast.css?v=<?php echo filemtime('../notif/toast.css'); ?>">
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
            <a href="../menu.php" class="nav-link">
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
            <?php if (in_array($user['grade_level'], ['Developer', 'Admin'])): ?>
            <a href="../navigation/moderation/moderation.php" class="nav-link">
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
        <div class="credits-container">
        <div class="credits-title">
            
<img src="../assets/menu/Word-Weavers.png" alt="Word Weavers Logo" class="credits-big-logo">
        </div>
        <div class="credits-section members-container">
            <h2>Group 3 Members</h2>
            <div class="members-list">
                <div class="member-card">Alfred Estares</div>
                <div class="member-card">Loren Mae Pascual</div>
                <div class="member-card">Jaderby Peñaranda</div>
                <div class="member-card">Jeric Ganancial</div>
                <div class="member-card">Ria Jhen Boreres</div>
                <div class="member-card">Ken Erickson Bacarisas</div>
            </div>
        </div>
        <div class="credits-section developer-container">
            <h2>Developer</h2>
            <div class="developer-card" id="developerName">Jaderby Peñaranda</div>
        </div>
        <div class="credits-section github-container">
            <a href="https://github.com/frostjade71/GameDev-G1" target="_blank" rel="noopener noreferrer" class="github-link">
                <i class="fab fa-github github-logo"></i>
            </a>
            <a href="https://discord.gg/nPjnxqXdny" target="_blank" rel="noopener noreferrer" class="discord-link">
                <i class="fab fa-discord discord-logo"></i>
            </a>
            <a href="https://mail.google.com/mail/u/0/?fs=1&to=wordweavershccci@gmail.com&tf=cm" target="_blank" rel="noopener noreferrer" class="email-link">
                <i class="fas fa-envelope email-logo"></i>
            </a>
        </div>
        <footer class="credits-footer">
            <p>© 2025 WordWeaversHCCCI. All rights reserved.</p>
        </footer>
        </div>
    </div>

    <!-- Gravatar Hover Card -->
    <div class="gravatar-card-overlay" id="gravatarOverlay"></div>
    <div class="gravatar-card-container" id="gravatarCardContainer">
        <div class="gravatar-hovercard">
            <div class="gravatar-hovercard__inner">
                <div class="gravatar-hovercard__header-image" style="background: url(&quot;https://1.gravatar.com/userimage/274162724/ded22cbae356004a1dc7b5d07bf9c96e?size=1024&quot;) 1% 8% / 142% no-repeat;"></div>
                <div class="gravatar-hovercard__header">
                    <a class="gravatar-hovercard__avatar-link" href="https://jaderbypenaranda.link?utm_source=hovercard" target="_blank">
                        <img class="gravatar-hovercard__avatar" src="https://2.gravatar.com/avatar/e8c90b8d9f6760afe027d12769ea696c178548ae500ea2a2e55ff770fa12f7b6?s=256&amp;d=initials" width="104" height="104" alt="Jaderby Peñaranda">
                    </a>
                    <a class="gravatar-hovercard__personal-info-link" href="https://jaderbypenaranda.link?utm_source=hovercard" target="_blank">
                        <h4 class="gravatar-hovercard__name">Jaderby Peñaranda</h4>
                        <p class="gravatar-hovercard__job">Computer Science Senior, HCCCI</p>
                    </a>
                </div>
                <div class="gravatar-hovercard__body">
                    <p class="gravatar-hovercard__description">Hello.</p>
                </div>
                <div class="gravatar-hovercard__social-links">
                    <a class="gravatar-hovercard__social-link" href="https://jaderbypenaranda.link?utm_source=hovercard" target="_blank" data-service-name="gravatar">
                        <img class="gravatar-hovercard__social-icon" src="https://s.gravatar.com/icons/gravatar.svg" width="32" height="32" alt="Gravatar">
                    </a>
                    <a class="gravatar-hovercard__social-link" href="https://www.linkedin.com/in/jaderby-pe%C3%B1aranda-830670359" target="_blank" data-service-name="linkedin">
                        <img class="gravatar-hovercard__social-icon" src="https://s.gravatar.com/icons/linkedin.svg" width="32" height="32" alt="LinkedIn">
                    </a>
                    <a class="gravatar-hovercard__social-link" href="https://github.com/frostjade71" target="_blank" data-service-name="github">
                        <img class="gravatar-hovercard__social-icon" src="https://s.gravatar.com/icons/github.svg" width="32" height="32" alt="GitHub">
                    </a>
                    <a class="gravatar-hovercard__social-link" href="https://support.gravatar.com/profiles/verified-accounts/#facebook" target="_blank" data-service-name="facebook">
                        <img class="gravatar-hovercard__social-icon" src="https://s.gravatar.com/icons/facebook.svg" width="32" height="32" alt="Facebook">
                    </a>
                </div>
                <div class="gravatar-hovercard__footer">
                    <a class="gravatar-hovercard__profile-url" title="https://jaderbypenaranda.link" href="https://jaderbypenaranda.link/?utm_source=profile-card" target="_blank">
                        jaderbypenaranda.link
                    </a>
                    <a class="gravatar-hovercard__profile-link" href="https://jaderbypenaranda.link/?utm_source=profile-card" target="_blank">
                        View profile →
                    </a>
                </div>
                <div class="gravatar-hovercard__profile-color" style="background: linear-gradient(138deg, rgb(15, 44, 133) 0%, rgb(142, 48, 112) 55%, rgb(71, 34, 44) 100%);"></div>
            </div>
            <button class="gravatar-card-close" id="closeGravatar">&times;</button>
        </div>
    </div>

    <div class="toast-overlay"></div>
    <div id="toast" class="toast"></div>
    <script src="../script.js"></script>
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

    <script src="credits.js"></script>
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
