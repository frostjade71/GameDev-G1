<?php
require_once '../onboarding/config.php';
require_once '../includes/greeting.php';

if (!isLoggedIn()) {
    header('Location: ../onboarding/login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT username, email, grade_level, profile_image FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    session_destroy();
    header('Location: ../onboarding/login.php');
    exit();
}

$stmt = $pdo->prepare("SELECT bgm_enabled, sfx_enabled, language FROM user_settings WHERE user_id = ?");
$stmt->execute([$user_id]);
$settings = $stmt->fetch();
if (!$settings) {
    $settings = [
        'bgm_enabled' => 1,
        'sfx_enabled' => 1,
        'language' => 'english'
    ];
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
    <?php include '../includes/favicon.php'; ?>
    <title>Settings - Word Weavers</title>
    <link rel="stylesheet" href="../navigation/shared/navigation.css?v=<?php echo filemtime('../navigation/shared/navigation.css'); ?>">
    <link rel="stylesheet" href="../styles.css?v=<?php echo filemtime('../styles.css'); ?>">
    <link rel="stylesheet" href="../notif/toast.css?v=<?php echo filemtime('../notif/toast.css'); ?>">
    <link rel="stylesheet" href="settings.css?v=<?php echo filemtime('settings.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        body {
            background-image: url('../assets/menu/menubg.jpg');
            background-size: cover;
            background-position: center;
            min-height: 100vh;
            color: white;
            font-family: var(--font-pixel);
            margin: 0;
            padding: 0;
        }
        .main-content {
            position: fixed;
            left: 250px;
            top: 60px;
            right: 0;
            bottom: 0;
            padding: 2rem;
            overflow-y: auto;
            display: flex;
            justify-content: center;
            align-items: flex-start;
        }
        @media screen and (max-width: 768px) {
            .main-content {
                left: 0;
                padding: 1rem;
                padding-top: 70px;
            }
        }
        .settings-container {
            background: linear-gradient(135deg, rgba(0, 0, 0, 0.85), rgba(20, 20, 20, 0.95));
            backdrop-filter: blur(15px);
            border-radius: 25px;
            padding: 1.25rem;
            width: 100%;
            max-width: 500px;
            border: 2px solid rgba(96, 239, 255, 0.15);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            position: relative;
            overflow: hidden;
            margin: 0 auto;
        }
        .settings-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, rgba(96, 239, 255, 0.8), rgba(0, 255, 135, 0.8));
        }
        .settings-header {
            text-align: center;
            padding-bottom: 1.5rem;
            border-bottom: 2px solid rgba(96, 239, 255, 0.1);
            margin-bottom: 1.5rem;
        }
        .settings-logo {
            width: 200px;
            height: auto;
            max-width: 80%;
            object-fit: contain;
        }
        @media (max-width: 768px) {
            .settings-header {
                padding-bottom: 1rem;
                margin-bottom: 1rem;
            }
            .settings-logo {
                width: 180px;
            }
        }
        @media (max-width: 480px) {
            .settings-header {
                padding-bottom: 0.8rem;
                margin-bottom: 0.8rem;
            }
            .settings-logo {
                width: 150px;
            }
        }
        .settings-form {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        .form-group label {
            color: rgba(255,255,255,0.8);
            font-size: 0.9rem;
        }
        .form-group input[type="checkbox"] {
            accent-color: #60efff;
            width: 20px;
            height: 20px;
        }
        .form-group select {
            background: rgba(20, 20, 20, 0.95);
            border: 1px solid rgba(96, 239, 255, 0.2);
            border-radius: 12px;
            padding: 1rem;
            color: white;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            cursor: pointer;
            font-family: var(--font-pixel);
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
            position: relative;
            width: 100%;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .language-select-container {
            position: relative;
            width: 100%;
        }

        .language-select-container::after {
            content: '\f0d7';
            font-family: 'Font Awesome 5 Free';
            font-weight: 900;
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(96, 239, 255, 0.8);
            pointer-events: none;
            transition: all 0.3s ease;
        }

        .language-select-container:hover::after {
            color: rgba(0, 255, 135, 0.8);
        }

        .form-group select:hover {
            border-color: rgba(0, 255, 135, 0.4);
            background: rgba(30, 30, 30, 0.95);
        }

        .form-group select:focus {
            outline: none;
            border-color: rgba(0, 255, 135, 0.8);
            background: rgba(30, 30, 30, 0.95);
            box-shadow: 0 0 15px rgba(0, 255, 135, 0.2);
        }

        .form-group select option {
            background: rgba(20, 20, 20, 0.95);
            color: white;
            padding: 1rem;
            font-family: var(--font-pixel);
            font-size: 0.85rem;
        }

        .form-group select option:hover {
            background: rgba(96, 239, 255, 0.2);
        }

        .language-flag {
            display: inline-block;
            width: 24px;
            height: 24px;
            margin-right: 10px;
            vertical-align: middle;
            border-radius: 4px;
            object-fit: cover;
        }

        @media (max-width: 768px) {
            .form-group select {
                padding: 0.8rem;
                font-size: 0.8rem;
            }
            .form-group select option {
                font-size: 0.75rem;
            }
            .language-flag {
                width: 20px;
                height: 20px;
            }
        }

        @media (max-width: 480px) {
            .form-group select {
                padding: 0.7rem;
                font-size: 0.7rem;
            }
            .form-group select option {
                font-size: 0.65rem;
            }
            .language-flag {
                width: 18px;
                height: 18px;
            }
        }
        .save-button {
            background: linear-gradient(45deg, rgba(96,239,255,0.8), rgba(0,255,135,0.8));
            border: none;
            border-radius: 8px;
            padding: 1rem;
            color: white;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 1rem;
            font-family: var(--font-pixel);
        }
        .save-button:hover {
            transform: translateY(-2px);
            background: linear-gradient(45deg, rgba(96,239,255,1), rgba(0,255,135,1));
            box-shadow: 0 5px 15px rgba(0,255,135,0.3);
        }
        .settings-separator {
            margin: 2rem 0;
            height: 2px;
            background: rgba(96, 239, 255, 0.1);
            position: relative;
        }

        .settings-separator::before {
            content: '';
            position: absolute;
            top: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 2px;
            background: linear-gradient(90deg, rgba(96, 239, 255, 0.8), rgba(0, 255, 135, 0.8));
        }

        .about-text {
            color: rgba(255,255,255,0.8);
            font-size: 0.9rem;
            font-style: italic;
            margin-top: 1rem;
            line-height: 1.4;
            text-align: center;
            padding: 0 1rem;
        }

        @media (max-width: 768px) {
            .settings-separator {
                margin: 1.5rem 0;
            }
            .settings-separator::before {
                width: 50px;
            }
        }

        @media (max-width: 480px) {
            .settings-separator {
                margin: 1rem 0;
            }
            .settings-separator::before {
                width: 40px;
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/page-loader.php'; ?>
    <button class="mobile-menu-btn" aria-label="Open menu">
        <i class="fas fa-bars"></i>
    </button>
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
                            <a href="settings.php" class="profile-dropdown-item">
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
        <div class="settings-container">
            <div class="settings-header">
                <img src="../assets/menu/settingsmain.png" alt="Settings" class="settings-logo">
            </div>
            <form class="settings-form" id="settingsForm">
                <div class="form-group">
                    <label for="bgmToggle">Background Music</label>
                    <input type="checkbox" id="bgmToggle" <?php echo $settings['bgm_enabled'] ? 'checked' : ''; ?>>
                </div>
                <div class="form-group">
                    <label for="sfxToggle">Sound Effects</label>
                    <input type="checkbox" id="sfxToggle" <?php echo $settings['sfx_enabled'] ? 'checked' : ''; ?>>
                </div>
                <div class="form-group">
                    <label for="language">Select Language</label>
                    <div class="language-select-container">
                        <select id="language">
                            <option value="english" <?php echo $settings['language'] === 'english' ? 'selected' : ''; ?>>
                                🇺🇸 English
                            </option>
                        </select>
                    </div>
                </div>
                <button type="submit" class="save-button">Save Settings</button>
            </form>
            <div class="button-separator"></div>
            <button class="reset-progress-button" id="resetProgressBtn">Reset Game Progress</button>
        </div>
    </div>
    <div class="toast-overlay"></div>
    <div id="toast" class="toast"></div>
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
    <script>
        // Direct event listener for reset button
        document.getElementById('resetProgressBtn').addEventListener('click', function() {
            // Add visual feedback to the button
            this.style.transform = 'scale(0.95)';
            setTimeout(() => {
                this.style.transform = 'scale(1)';
            }, 100);
            
            // Create a new toast element to avoid CSS conflicts
            const newToast = document.createElement('div');
            newToast.innerHTML = '<div style="text-align: center;"><img src="../assets/pixels/hammer.png" style="width: 20px; height: 20px; margin-bottom: 8px; display: block; margin-left: auto; margin-right: auto;"> not yet working.. it couldve been worse if it was working lol</div>';
            newToast.style.cssText = `
                position: fixed !important;
                top: 50% !important;
                left: 50% !important;
                transform: translate(-50%, -50%) !important;
                background: rgba(0, 0, 0, 0.95) !important;
                color: white !important;
                padding: 15px 25px !important;
                border-radius: 10px !important;
                z-index: 99999 !important;
                font-size: 14px !important;
                font-family: Arial, sans-serif !important;
                box-shadow: 0 4px 20px rgba(0, 0, 0, 0.5) !important;
                border: 1px solid rgba(255, 255, 255, 0.2) !important;
                opacity: 0 !important;
                transition: opacity 0.3s ease !important;
                pointer-events: none !important;
                text-align: center !important;
                max-width: 80% !important;
                word-wrap: break-word !important;
            `;
            
            document.body.appendChild(newToast);
            
            // Fade in
            setTimeout(() => {
                newToast.style.opacity = '1';
            }, 50);
            
            // Remove after 3 seconds
            setTimeout(() => {
                newToast.style.opacity = '0';
                setTimeout(() => {
                    if (newToast.parentNode) {
                        newToast.parentNode.removeChild(newToast);
                    }
                }, 300);
            }, 3000);
        });
    </script>
    <script src="../script.js"></script>
    <script src="settings.js"></script>
    <script src="../navigation/shared/profile-dropdown.js"></script>
    <script src="../navigation/shared/notification-badge.js"></script>
    <script>
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
            playClickSound();
            window.location.href = '../onboarding/logout.php';
        }
    </script>
</body>
</html>
