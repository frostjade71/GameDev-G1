<?php
require_once '../../onboarding/config.php';
require_once '../../includes/greeting.php';

// Check if user is logged in and has admin/developer access
$gradeLevel = $_SESSION['grade_level'] ?? '';
$isAdminDev = in_array(strtolower($gradeLevel), array_map('strtolower', ['Developer', 'Admin']));

if (!function_exists('isLoggedIn') || !isLoggedIn() || !$isAdminDev) {
    header('Location: ../../onboarding/login.php');
    exit();
}

// Get current user information
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT username, email, grade_level, section, profile_image FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$current_user = $stmt->fetch();

// Get notification count
$stmt = $pdo->prepare("
    SELECT COUNT(*) as count 
    FROM friend_requests 
    WHERE receiver_id = ? AND status = 'pending'
");
$stmt->execute([$user_id]);
$notification_count = $stmt->fetch()['count'];

// Load dashboard statistics
$dashboardStats = require_once 'api/dashboard-stats.php';

// Get recent admin logs
$stmt = $pdo->prepare("
    SELECT al.*, u.username as admin_username
    FROM admin_logs al
    JOIN users u ON al.admin_id = u.id
    ORDER BY al.action_timestamp DESC
    LIMIT 10
");
$stmt->execute();
$admin_logs = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include '../../includes/favicon.php'; ?>
    <title>Admin Dashboard - Word Weavers</title>
    <link rel="stylesheet" href="../../styles.css?v=<?php echo filemtime('../../styles.css'); ?>">
    <link rel="stylesheet" href="../../navigation/shared/navigation.css?v=<?php echo filemtime('../../navigation/shared/navigation.css'); ?>">
    <link rel="stylesheet" href="../../notif/toast.css?v=<?php echo filemtime('../../notif/toast.css'); ?>">
    <link rel="stylesheet" href="assets/css/dashboard.css?v=<?php echo filemtime('assets/css/dashboard.css'); ?>">
    <link rel="stylesheet" href="assets/css/delete-modal.css?v=<?php echo filemtime('assets/css/delete-modal.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body>
    <?php include '../../includes/page-loader.php'; ?>
    <!-- Mobile Menu Button -->
    <button class="mobile-menu-btn" aria-label="Open menu">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Custom Admin Sidebar -->
    <div class="sidebar admin-sidebar">
        <div class="sidebar-logo">
            <img src="../../assets/menu/Word-Weavers.png" alt="Word Weavers" class="sidebar-logo-img">
        </div>
        <nav class="sidebar-nav">
            <a href="../../menu.php" class="nav-link back-link">
                <i class="fas fa-arrow-left"></i>
                <span>Back to Home</span>
            </a>
            <div class="nav-divider"></div>
            <a href="dashboard.php" class="nav-link active">
                <i class="fas fa-chart-line"></i>
                <span>Dashboard</span>
            </a>
            <a href="analytics.php" class="nav-link">
                <i class="fas fa-chart-pie"></i>
                <span>Analytics</span>
            </a>
            <a href="user-management.php" class="nav-link">
                <i class="fas fa-users-cog"></i>
                <span>User Management</span>
            </a>
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
                            <a href="../favorites/favorites.php" class="profile-dropdown-item">
                                <i class="fas fa-star"></i>
                                <span>My Favorites</span>
                            </a>
                            <a href="../../settings/settings.php" class="profile-dropdown-item">
                                <i class="fas fa-cog"></i>
                                <span>Settings</span>
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
        <!-- Dashboard View -->
        <div class="hero-container">
            <!-- Welcome Section -->
            <div class="welcome-section">
                <div class="welcome-content">
                    <h2><?php 
                        // Get current hour
                        $currentHour = (int)date('G');
                        
                        // Determine greeting and icon based on time
                        if ($currentHour >= 0 && $currentHour < 12) {
                            $greeting = 'Good Morning';
                            $weatherIcon = 'fa-cloud-sun';
                        } elseif ($currentHour >= 12 && $currentHour < 18) {
                            $greeting = 'Good Afternoon';
                            $weatherIcon = 'fa-cloud-sun';
                        } else {
                            $greeting = 'Good Evening';
                            $weatherIcon = 'fa-cloud-moon';
                        }
                        
                        // Get first name from username
                        $nameParts = explode(' ', $current_user['username']);
                        $firstName = $nameParts[0];
                        
                        echo htmlspecialchars($greeting . ', ' . $firstName);
                    ?> <i class="fas <?php echo $weatherIcon; ?> weather-icon"></i></h2>
                    <div class="welcome-roles">
                        <span class="welcome-role-badge">
                            <i class="fas fa-user-shield"></i>
                            Admin Dashboard
                        </span>
                        <span class="welcome-role-badge">
                            <i class="fas fa-code"></i>
                            <?php echo htmlspecialchars($current_user['grade_level']); ?>
                        </span>
                    </div>
                </div>
                <div class="welcome-datetime">
                    <div class="datetime-display">
                        <div class="date-text" id="currentDate"></div>
                        <div class="time-text" id="currentTime"></div>
                    </div>
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="quick-stats">
                <div class="quick-stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Total Users</h3>
                        <div class="value stat-value" data-target="<?php echo $dashboardStats['total_users']; ?>">0</div>
                        <div class="stat-sublabel">
                            <i class="fas fa-user-plus"></i>
                            <?php echo $dashboardStats['recent_users']; ?> this week
                        </div>
                    </div>
                </div>
                <div class="quick-stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
                        <i class="fas fa-user-check"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Active Today</h3>
                        <div class="value stat-value" data-target="<?php echo $dashboardStats['active_today']; ?>">0</div>
                        <div class="stat-sublabel">
                            <i class="fas fa-clock"></i>
                            <?php echo $dashboardStats['active_users']; ?> active total
                        </div>
                    </div>
                </div>
                <div class="quick-stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Average GWA</h3>
                        <div class="value"><?php echo number_format($dashboardStats['avg_gwa'], 2); ?></div>
                        <div class="stat-sublabel">
                            <i class="fas fa-graduation-cap"></i> 
                            Student Performance
                        </div>
                    </div>
                </div>
                <div class="quick-stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);">
                        <i class="fas fa-gem"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Total Essence</h3>
                        <div class="value stat-value" data-target="<?php echo $dashboardStats['total_essence']; ?>">0</div>
                        <div class="stat-sublabel">
                            <i class="fas fa-coins"></i> 
                            <?php echo number_format($dashboardStats['total_shards'] / 1000, 1); ?>k shards
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script>
        function updateDateTime() {
            const now = new Date();
            const dateOptions = { year: 'numeric', month: 'long', day: 'numeric' };
            const dateStr = now.toLocaleDateString('en-US', dateOptions);
            const timeOptions = { hour: 'numeric', minute: '2-digit', hour12: true };
            const timeStr = now.toLocaleTimeString('en-US', timeOptions);
            
            const dateEl = document.getElementById('currentDate');
            const timeEl = document.getElementById('currentTime');
            if(dateEl) dateEl.textContent = dateStr;
            if(timeEl) timeEl.textContent = timeStr;
        }
        updateDateTime();
        setInterval(updateDateTime, 1000);
        </script>

        <!-- Navigation Buttons Section -->
        <div class="dashboard-navigation">
            <div class="admin-management-section">
                <div class="admin-nav-row">
                    <div class="management-header">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); width: 32px; height: 32px; font-size: 1rem;">
                            <i class="fas fa-cogs"></i>
                        </div>
                        <h3>System Management</h3>
                    </div>
                    <div class="admin-cards-container">
                        <div class="nav-card" onclick="window.location.href='analytics.php'">
                            <div class="nav-card-icon">
                                <i class="fas fa-chart-pie"></i>
                            </div>
                            <div class="nav-card-content">
                                <h3>Analytics</h3>
                            </div>
                        </div>
                        <div class="nav-card" onclick="window.location.href='user-management.php'">
                            <div class="nav-card-icon">
                                <i class="fas fa-users-cog"></i>
                            </div>
                            <div class="nav-card-content">
                                <h3>User Management</h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

            <!-- Charts and Activity Section & Leaderboard - Removed as per request -->
            
            <!-- Admin Activity Log - Removed as per request -->
            
        </div>
    </div>

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
    <script src="../shared/profile-dropdown.js"></script>
    <script src="../shared/notification-badge.js"></script>
    <script src="assets/js/dashboard.js"></script>
    <script>
    // Chart data from PHP
    const chartData = {
        distribution: <?php echo json_encode($dashboardStats['grade_distribution']); ?>
    };
    
    let currentChart = null;
    
    // Initialize Chart.js for grade distribution
    document.addEventListener('DOMContentLoaded', function() {
        initializeChart();
        
        // Animate stat counters
        animateCounters();
    });

    function initializeChart() {
        const ctx = document.getElementById('gradeChart');
        if (!ctx) return;

        // Destroy existing chart if it exists
        if (currentChart) {
            currentChart.destroy();
        }

        if (!chartData.distribution || chartData.distribution.length === 0) return;
        
        const labels = chartData.distribution.map(item => item.grade_level);
        const values = chartData.distribution.map(item => item.count);

        currentChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Students',
                    data: values,
                    backgroundColor: [
                        '#4cc9f0',
                        '#00ff87',
                        '#ffc107',
                        '#f44336',
                        '#9c27b0',
                        '#ff6b6b',
                        '#4ecdc4',
                        '#45b7d1'
                    ],
                    borderColor: '#1a1a1a',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: '#e0e0e0',
                            padding: 15,
                            font: {
                                size: 12
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.label + ': ' + context.parsed + ' students';
                            }
                        }
                    }
                }
            }
        });
    }

    function animateCounters() {
        const counters = document.querySelectorAll('.stat-value[data-target]');
        counters.forEach(counter => {
            const target = parseInt(counter.getAttribute('data-target'));
            const duration = 2000;
            const increment = target / (duration / 16);
            let current = 0;

            const updateCounter = () => {
                current += increment;
                if (current < target) {
                    counter.textContent = Math.floor(current).toLocaleString();
                    requestAnimationFrame(updateCounter);
                } else {
                    counter.textContent = target.toLocaleString();
                }
            };

            updateCounter();
        });
    }

    function updateLeaderboard(type) {
        const fameContent = document.getElementById('leaderboard-fame');
        const gwaContent = document.getElementById('leaderboard-gwa');
        
        if (type === 'fame') {
            fameContent.style.display = 'block';
            gwaContent.style.display = 'none';
        } else if (type === 'gwa') {
            fameContent.style.display = 'none';
            gwaContent.style.display = 'block';
        }
    }

    function logAdminAction(action) {
        fetch('../../api/admin-log.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ action: action })
        }).catch(err => console.error('Failed to log action:', err));
    }

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
        window.location.href = '../../onboarding/logout.php';
    }
    </script>
</body>
</html>
