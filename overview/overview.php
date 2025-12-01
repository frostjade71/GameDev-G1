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
$stmt = $pdo->prepare("SELECT username, email FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

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
    </button>

    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-logo">
            <img src="../assets/menu/Word-Weavers.png" alt="Word Weavers Logo" class="sidebar-logo-img">
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
        <div class="overview-container">
            <div class="section-header">
                <h1 class="overview-title">Overview</h1>
            </div>

            <div class="overview-content">
                <div class="overview-card">
                    <div class="content-wrapper">
                        <div class="title-image">
                            <img src="../assets/menu/Word_weavers.png" alt="Word Weavers" class="title-logo">
                        </div>
                        <div class="text-section">
                            <p><strong>Word Weavers</strong> is a web-based educational game dashboard developed by Group 1, Computer Science students from Holy Cross College of Carigara Incorporated. It is designed to store and organize various educational games that aim to enhance the English language skills of high school students in Grades 7 to 10. The platform also allows teachers and students to monitor their performance through detailed progress reports, interactive scoreboards, and performance analytics.</p>
                            <div class="separator"></div>
                            <p>This website was developed in response to the growing need for modern learning tools that combine effective teaching methods with the specific learning needs of adolescents. Many students learn better through visual and interactive experiences rather than traditional lectures, so this platform aims to make learning more engaging, enjoyable, and effective for them.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Logout Modal -->
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

    <script src="../script.js"></script>
    <script src="overview.js"></script>
    <script src="../navigation/shared/profile-dropdown.js"></script>
    <script src="../navigation/shared/notification-badge.js"></script>
    <script>
        function confirmLogout() {
            playClickSound();
            window.location.href = '../onboarding/logout.php';
        }
    </script>
</body>
</html>