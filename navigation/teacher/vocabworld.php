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
    <?php include '../../includes/favicon.php'; ?>
    <title>Game Controls - Word Weavers</title>
    <link rel="stylesheet" href="../../styles.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../../navigation/shared/navigation.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../../notif/toast.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="assets/css/dashboard.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        /* Removed controls-container max-width to match other teacher pages */
        /* .control-card styles replaced by .vocabulary-section from dashboard.css */
        /* .control-card styles are now handled by .vocabulary-section */
        .control-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1rem;
            border-bottom: 1px solid rgba(96, 239, 255, 0.2);
            padding-bottom: 1rem;
        }
        .control-title h3 {
            margin: 0;
            color: white;
            font-size: 1.25rem;
        }
        .control-title p {
            margin: 0.25rem 0 0;
            color: rgba(255, 255, 255, 0.7);
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
            background: rgba(255, 255, 255, 0.05);
            padding: 1rem;
            border-radius: 8px;
            transition: background 0.2s;
            border: 1px solid rgba(96, 239, 255, 0.1);
        }
        .toggle-item:hover {
            background: rgba(255, 255, 255, 0.1);
        }
        .grade-label {
            font-weight: 600;
            color: rgba(255, 255, 255, 0.9);
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

        /* Mobile Optimization */
        @media (max-width: 768px) {
            .teacher-container {
                padding: 0.8rem !important;
                border-radius: 15px;
            }
            
            .vocabulary-section {
                padding: 1rem !important; /* Override dashboard.css padding */
            }

            .welcome-section {
                padding: 1.5rem !important;
                flex-direction: column;
                gap: 1.5rem;
                text-align: center;
            }

            .welcome-content h2 {
                justify-content: center;
                font-size: 1.5rem;
            }
            
            .quick-stat-card {
                width: 100%;
                justify-content: center;
            }

            .welcome-datetime {
                display: none !important;
            }

            .control-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }
            
            .control-title h3 {
                font-size: 1.1rem;
            }
            
            .toggle-item {
                padding: 0.8rem;
                gap: 1rem;
                /* Ensure items stay within bounds */
                max-width: 100%; 
                box-sizing: border-box;
            }
            
            /* Prevent label from pushing switch off-screen */
            .grade-label {
                font-size: 0.9rem;
                flex: 1; /* Allow taking available space */
                min-width: 0; /* Allow shrinking if needed */
                white-space: normal; /* Allow text wrapping */
            }

            /* Prevent switch from shrinking */
            .switch {
                flex-shrink: 0; 
            }

            .grade-status {
                font-size: 0.7rem;
                padding: 0.1rem 0.3rem;
            }
            .grade-status {
                font-size: 0.7rem;
                padding: 0.1rem 0.3rem;
            }
        }

        /* Toast Redesign (Stacking Support) */
        #toast-container {
            position: fixed;
            top: 20px;
            right: -5px;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .game-toast {
            background: rgba(0, 0, 0, 0.9);
            color: white;
            padding: 15px 25px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
            opacity: 0;
            transform: translateX(100%);
            transition: all 0.3s ease;
            min-width: 250px;
            border-left: 4px solid #3b82f6;
            backdrop-filter: blur(5px);
            font-size: 0.9rem;
            pointer-events: auto;
        }

        .game-toast.show {
            opacity: 1;
            transform: translateX(0);
        }

        .game-toast.success {
            border-left-color: #10b981;
        }

        .game-toast.error {
            border-left-color: #ef4444;
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
        <!-- Welcome Section -->
        <div class="welcome-section">
            <div class="welcome-content" style="flex: 0 0 auto;">
                <div class="quick-stat-card" style="background: transparent; border: none; padding: 0; box-shadow: none;">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
                        <i class="fas fa-gamepad"></i>
                    </div>
                    <div class="stat-content">
                        <h3 style="color: var(--white);">Game Access</h3>
                        <div class="value" style="color: var(--white); font-size: 1rem; margin-top: 5px;">Manage Grades</div>
                    </div>
                </div>
            </div>

            <div class="welcome-datetime">
                <div class="datetime-display">
                    <div class="date-text" id="currentDate"></div>
                    <div class="time-text" id="currentTime"></div>
                </div>
            </div>
        </div>

        <script>
        function updateDateTime() {
            const now = new Date();
            
            // Format date: December 15, 2025
            const dateOptions = { year: 'numeric', month: 'long', day: 'numeric' };
            const dateStr = now.toLocaleDateString('en-US', dateOptions);
            
            // Format time: 1:16 PM
            const timeOptions = { hour: 'numeric', minute: '2-digit', hour12: true };
            const timeStr = now.toLocaleTimeString('en-US', timeOptions);
            
            document.getElementById('currentDate').textContent = dateStr;
            document.getElementById('currentTime').textContent = timeStr;
        }

        // Update immediately and then every second
        updateDateTime();
        setInterval(updateDateTime, 1000);
        </script>

        <div class="teacher-container">
            <div class="vocabulary-section">
                <!-- Header with Icon -->
                <div class="section-header" style="margin-bottom: 20px;">
                    <h3 style="transform: translateY(-3px);"><i class="fas fa-lock text-blue-500 mr-2"></i> Access Control</h3>
                    <p style="margin: 0; color: #6b7280; font-size: 0.95rem; margin-left: 10px;">Toggle game access for each grade level.</p>
                </div>

                <div class="toggle-list" id="gradeToggles">
                    <!-- Loading toggles... -->
                    <div class="toggle-item">Loading settings...</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Container -->
    <div id="toast-container"></div>

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
            const container = document.getElementById('toast-container');
            
            // Create toast element
            const toast = document.createElement('div');
            toast.className = `game-toast ${type}`;
            toast.textContent = message;
            
            // Append to container
            container.appendChild(toast);
            
            // Trigger animation
            requestAnimationFrame(() => {
                toast.classList.add('show');
            });
            
            // Remove after 3 seconds
            setTimeout(() => {
                toast.classList.remove('show');
                
                // Wait for transition to finish before removing from DOM
                setTimeout(() => {
                    if (toast.parentElement) {
                        container.removeChild(toast);
                    }
                }, 300); // Match transition duration
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
