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

// Get notification count
$stmt = $pdo->prepare("
    SELECT fr.id FROM friend_requests fr
    WHERE fr.receiver_id = ? AND fr.status = 'pending'
");
$stmt->execute([$user_id]);
$notification_count = $stmt->rowCount();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/webp" href="../assets/menu/ww_logo_main.webp">
    <title>Overview - Word Weavers</title>
    <link rel="stylesheet" href="../styles.css">
    <link rel="stylesheet" href="../navigation/shared/navigation.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../notif/toast.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="overview.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>
<body>
    <!-- Mobile Menu Button -->
    <button class="mobile-menu-btn">
        <i class="fas fa-bars"></i>
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
                <img src="../assets/menu/overviewmain.png" alt="Overview" class="overview-title">
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