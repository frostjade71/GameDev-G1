<?php
require_once '../../onboarding/config.php';
require_once '../../includes/greeting.php';

// Check if user is logged in and has teacher/admin access
$gradeLevel = $_SESSION['grade_level'] ?? '';
$isTeacherOrAdmin = in_array(strtolower($gradeLevel), array_map('strtolower', ['Teacher', 'Admin', 'Developer']));

if (!function_exists('isLoggedIn') || !isLoggedIn() || !$isTeacherOrAdmin) {
    header('Location: ../../onboarding/login.php');
    exit();
}

// Get current user information
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT username, email, grade_level, section, profile_image FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$current_user = $stmt->fetch();

// Get notification count
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM friend_requests WHERE receiver_id = ? AND status = 'pending'");
$stmt->execute([$user_id]);
$notification_count = $stmt->fetch()['count'];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/x-icon" href="../../assets/menu/favicon.ico">
    <title>Game Controls - Word Weavers</title>
    <link rel="stylesheet" href="../../styles.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../../navigation/shared/navigation.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../../notif/toast.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="assets/css/dashboard.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        .controls-container {
            padding: 2rem;
            max-width: 800px;
            margin: 0 auto;
        }
        .control-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            margin-bottom: 1.5rem;
        }
        .control-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1rem;
            border-bottom: 1px solid #eee;
            padding-bottom: 1rem;
        }
        .control-title h3 {
            margin: 0;
            color: #1f2937;
            font-size: 1.25rem;
        }
        .control-title p {
            margin: 0.25rem 0 0;
            color: #6b7280;
            font-size: 0.875rem;
        }
        .toggle-list {
            display: grid;
            gap: 1rem;
        }
        .toggle-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #f9fafb;
            padding: 1rem;
            border-radius: 8px;
            transition: background 0.2s;
        }
        .toggle-item:hover {
            background: #f3f4f6;
        }
        .grade-label {
            font-weight: 600;
            color: #374151;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .grade-status {
            font-size: 0.8rem;
            margin-left: 0.5rem;
            padding: 0.2rem 0.5rem;
            border-radius: 4px;
        }
        .status-on { background: #d1fae5; color: #047857; }
        .status-off { background: #fee2e2; color: #b91c1c; }

        /* Toggle Switch */
        .switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 28px;
        }
        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 34px;
        }
        .slider:before {
            position: absolute;
            content: "";
            height: 20px;
            width: 20px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }
        input:checked + .slider {
            background-color: #3b82f6;
        }
        input:focus + .slider {
            box-shadow: 0 0 1px #3b82f6;
        }
        input:checked + .slider:before {
            transform: translateX(22px);
        }
    </style>
</head>
<body>
    <?php include '../../includes/page-loader.php'; ?>
    <!-- Mobile Menu Button -->
    <button class="mobile-menu-btn" aria-label="Open menu">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Custom Teacher Sidebar -->
    <div class="sidebar teacher-sidebar">
        <div class="sidebar-logo">
            <img src="../../assets/menu/Word-Weavers.png" alt="Word Weavers" class="sidebar-logo-img">
        </div>
        <nav class="sidebar-nav">
            <a href="../../menu.php" class="nav-link back-link">
                <i class="fas fa-arrow-left"></i>
                <span>Back to Home</span>
            </a>
            <div class="nav-divider"></div>
            <a href="dashboard.php" class="nav-link">
                <i class="fas fa-chart-line"></i>
                <span>Dashboard</span>
            </a>
            <a href="students.php" class="nav-link">
                <i class="fas fa-user-graduate"></i>
                <span>Students</span>
            </a>
            <div class="nav-section">
                <div class="nav-section-header">
                    <img src="../../MainGame/vocabworld/assets/menu/vv_logo.webp" alt="Vocabworld" class="nav-section-logo">
                    <span>Vocabworld</span>
                </div>
                <!-- Control Button Here -->
                <a href="vocabworld.php" class="nav-link nav-sub-link active">
                    <i class="fas fa-gamepad"></i>
                    <span>Controls</span>
                </a>
                <a href="lessons.php" class="nav-link nav-sub-link">
                    <i class="fas fa-chalkboard-teacher"></i>
                    <span>Lessons</span>
                </a>
                <a href="vocabulary.php" class="nav-link nav-sub-link">
                    <i class="fas fa-book"></i>
                    <span>Vocabulary Bank</span>
                </a>
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
                    <span class="username"><?php echo htmlspecialchars(explode(' ', $current_user['username'])[0]); ?></span>
                </div>
                <div class="profile-dropdown">
                    <a href="#" class="profile-icon">
                        <img src="<?php echo !empty($current_user['profile_image']) ? '../../' . htmlspecialchars($current_user['profile_image']) : '../../assets/menu/defaultuser.png'; ?>" alt="Profile" class="profile-img">
                    </a>
                    <div class="profile-dropdown-content">
                        <!-- Standard profile dropdown content -->
                        <div class="profile-dropdown-header">
                            <img src="<?php echo !empty($current_user['profile_image']) ? '../../' . htmlspecialchars($current_user['profile_image']) : '../../assets/menu/defaultuser.png'; ?>" alt="Profile" class="profile-dropdown-avatar">
                            <div class="profile-dropdown-info">
                                <div class="profile-dropdown-name"><?php echo htmlspecialchars($current_user['username']); ?></div>
                                <div class="profile-dropdown-email"><?php echo htmlspecialchars($current_user['email']); ?></div>
                            </div>
                        </div>
                        <div class="profile-dropdown-menu">
                            <a href="../profile/profile.php" class="profile-dropdown-item">
                                <i class="fas fa-user"></i>
                                <span>View Profile</span>
                            </a>
                            <div class="dropdown-divider"></div>
                            <a href="#" class="profile-dropdown-item" onclick="showLogoutModal()">
                                <i class="fas fa-sign-out-alt"></i>
                                <span>Logout</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <div class="main-content">
        <div class="controls-container">
            <div class="welcome-section" style="margin-bottom: 2rem;">
                <div class="welcome-content">
                    <h2>Game Configuration</h2>
                    <p>Manage access and settings for Vocabworld.</p>
                </div>
            </div>

            <div class="control-card">
                <div class="control-header">
                    <div class="control-title">
                        <h3><i class="fas fa-lock text-blue-500 mr-2"></i> Access Control</h3>
                        <p>Toggle game access for each grade level.</p>
                    </div>
                </div>
                <div class="toggle-list" id="gradeToggles">
                    <!-- Loading toggles... -->
                    <div class="toggle-item">Loading settings...</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Notification -->
    <div id="toast" class="toast-notification"></div>

    <script src="../../script.js"></script>
    <script src="../shared/profile-dropdown.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            loadGameAccess();
        });

        async function loadGameAccess() {
            const container = document.getElementById('gradeToggles');
            try {
                const response = await fetch('api/get_game_access.php');
                const data = await response.json();
                
                if (data.error) throw new Error(data.error);

                container.innerHTML = '';
                const grades = [7, 8, 9, 10];
                
                grades.forEach(grade => {
                    const isEnabled = data[grade] !== false; // Default true if not set
                    const statusClass = isEnabled ? 'status-on' : 'status-off';
                    const statusText = isEnabled ? 'Active' : 'Disabled';
                    
                    const html = `
                        <div class="toggle-item">
                            <div class="grade-label">
                                <i class="fas fa-users"></i>
                                Grade ${grade} Students
                                <span class="grade-status ${statusClass}" id="status-${grade}">${statusText}</span>
                            </div>
                            <label class="switch">
                                <input type="checkbox" ${isEnabled ? 'checked' : ''} 
                                    onchange="toggleGrade(${grade}, this.checked)">
                                <span class="slider"></span>
                            </label>
                        </div>
                    `;
                    container.insertAdjacentHTML('beforeend', html);
                });

            } catch (error) {
                console.error('Error:', error);
                container.innerHTML = '<div class="error">Failed to load settings.</div>';
            }
        }

        async function toggleGrade(grade, isEnabled) {
            try {
                const response = await fetch('api/update_game_access.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ grade_level: grade, is_enabled: isEnabled })
                });
                
                const result = await response.json();
                if (result.success) {
                    showToast(`Grade ${grade} access ${isEnabled ? 'enabled' : 'disabled'}`, 'success');
                    
                    // Update status badge
                    const badge = document.getElementById(`status-${grade}`);
                    if (badge) {
                        badge.className = `grade-status ${isEnabled ? 'status-on' : 'status-off'}`;
                        badge.textContent = isEnabled ? 'Active' : 'Disabled';
                    }
                } else {
                    showToast('Failed to update settings', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showToast('Network error', 'error');
            }
        }

        function showToast(message, type = 'info') {
            const toast = document.getElementById('toast');
            toast.textContent = message;
            toast.className = `toast-notification show ${type}`;
            setTimeout(() => {
                toast.classList.remove('show');
            }, 3000);
        }

        function showLogoutModal() {
            // Implementation specific to your project's logout modal
            if(confirm('Are you sure you want to logout?')) {
                window.location.href = '../../onboarding/logout.php';
            }
        }
    </script>
</body>
</html>
