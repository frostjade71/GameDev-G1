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
        <div class="dashboard-container">
            <!-- Dashboard Header -->
            <div class="dashboard-header">
                <div class="header-title">
                    <img src="../../assets/menu/ww_logo_main.webp" alt="Word Weavers Logo" class="header-logo">
                    <div>
                        <h1>Admin Dashboard</h1>
                        <p>System Overview & Statistics</p>
                    </div>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="stats-grid">
                <!-- Total Users Card -->
                <div class="stat-card stat-primary">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value" data-target="<?php echo $dashboardStats['total_users']; ?>">0</div>
                        <div class="stat-label">Total Users</div>
                        <div class="stat-sublabel">
                            <i class="fas fa-user-plus"></i>
                            <?php echo $dashboardStats['recent_users']; ?> new this week
                        </div>
                    </div>
                </div>

                <!-- Active Users Card -->
                <div class="stat-card stat-success">
                    <div class="stat-icon">
                        <i class="fas fa-user-check"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value" data-target="<?php echo $dashboardStats['active_users']; ?>">0</div>
                        <div class="stat-label">Active Users</div>
                        <div class="stat-sublabel">
                            <i class="fas fa-clock"></i>
                            <?php echo $dashboardStats['active_today']; ?> active this day
                        </div>
                    </div>
                </div>

                <!-- Average GWA Card -->
                <div class="stat-card stat-warning">
                    <div class="stat-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo number_format($dashboardStats['avg_gwa'], 2); ?></div>
                        <div class="stat-label">Average GWA</div>
                        <div class="stat-sublabel">
                            <i class="fas fa-graduation-cap"></i> 
                            Academic Performance
                        </div>
                    </div>
                </div>

                <!-- Total Essence Card -->
                <div class="stat-card stat-info">
                    <div class="stat-icon">
                        <i class="fas fa-gem"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value" data-target="<?php echo $dashboardStats['total_essence']; ?>">0</div>
                        <div class="stat-label">Total Essence</div>
                        <div class="stat-sublabel">
                            <i class="fas fa-coins"></i> 
                            <?php echo number_format($dashboardStats['total_shards']); ?> shards
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts and Activity Section -->
            <div class="dashboard-grid">
                <!-- Analytics Chart -->
                <div class="dashboard-card">
                    <div class="card-header">
                        <h3><i class="fas fa-chart-pie"></i> User Distribution</h3>
                    </div>
                    <div class="card-body">
                        <canvas id="gradeChart"></canvas>
                    </div>
                </div>

                <!-- Recent Activity Timeline -->
                <div class="dashboard-card">
                    <div class="card-header">
                        <h3><i class="fas fa-clock"></i> Recent Logins</h3>
                    </div>
                    <div class="card-body">
                        <div class="activity-timeline">
                            <?php if (!empty($dashboardStats['recent_activity'])): ?>
                                <?php foreach (array_slice($dashboardStats['recent_activity'], 0, 5) as $activity): ?>
                                    <div class="timeline-item">
                                        <div class="timeline-dot"></div>
                                        <div class="timeline-content">
                                            <div class="timeline-title"><?php echo htmlspecialchars($activity['username']); ?></div>
                                            <div class="timeline-meta">
                                                <span class="timeline-badge"><?php echo htmlspecialchars($activity['grade_level']); ?></span>
                                                <span class="timeline-time">
                                                    <i class="fas fa-sign-in-alt"></i>
                                                    <?php 
                                                    if ($activity['last_login']) {
                                                        $loginTime = strtotime($activity['last_login']);
                                                        $now = time();
                                                        $diff = $now - $loginTime;
                                                        
                                                        if ($diff < 60) {
                                                            echo 'Just now';
                                                        } elseif ($diff < 3600) {
                                                            echo floor($diff / 60) . ' min ago';
                                                        } elseif ($diff < 86400) {
                                                            echo floor($diff / 3600) . ' hours ago';
                                                        } else {
                                                            echo date('M j, g:i A', $loginTime);
                                                        }
                                                    } else {
                                                        echo 'Never logged in';
                                                    }
                                                    ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="no-data">No recent login activity</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Top Performers Card -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h3><i class="fas fa-trophy"></i> Leaderboard</h3>
                    <div class="chart-filter">
                        <select id="leaderboardFilter" onchange="updateLeaderboard(this.value)">
                            <option value="fame">Top Fame</option>
                            <option value="gwa">Top GWA</option>
                        </select>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Top Fame Content -->
                    <div id="leaderboard-fame" class="leaderboard-content">
                        <div class="performers-grid">
                            <?php if (!empty($dashboardStats['top_performers'])): ?>
                                <?php foreach ($dashboardStats['top_performers'] as $index => $performer): ?>
                                    <div class="performer-card">
                                        <div class="performer-rank">#<?php echo $index + 1; ?></div>
                                        <div class="performer-info">
                                            <div class="performer-name"><?php echo htmlspecialchars($performer['username']); ?></div>
                                            <div class="performer-stats">
                                                <span><i class="fas fa-eye"></i> <?php echo number_format($performer['views']); ?></span>
                                                <span><i class="fas fa-moon"></i> <?php echo $performer['cresents']; ?></span>
                                            </div>
                                        </div>
                                        <div class="performer-score"><?php echo number_format($performer['fame_score']); ?></div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="no-data">No performance data available</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Top GWA Content -->
                    <div id="leaderboard-gwa" class="leaderboard-content" style="display: none;">
                        <div class="performers-grid">
                            <?php if (!empty($dashboardStats['top_gwa_users'])): ?>
                                <?php foreach ($dashboardStats['top_gwa_users'] as $index => $student): ?>
                                    <div class="performer-card">
                                        <div class="performer-rank">#<?php echo $index + 1; ?></div>
                                        <div class="performer-info">
                                            <div class="performer-name"><?php echo htmlspecialchars($student['username']); ?></div>
                                            <div class="performer-stats">
                                                <span><i class="fas fa-user-graduate"></i> <?php echo htmlspecialchars($student['grade_level']); ?></span>
                                            </div>
                                        </div>
                                        <div class="performer-score"><?php echo number_format($student['gwa'], 2); ?></div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="no-data">No GWA data available</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Admin Activity Log -->
            <?php if (!empty($admin_logs)): ?>
            <div class="dashboard-card">
                <div class="card-header">
                    <h3><i class="fas fa-history"></i> Admin Activity Log</h3>
                </div>
                <div class="card-body">
                    <div class="log-timeline">
                        <?php foreach ($admin_logs as $log): ?>
                            <div class="log-item">
                                <div class="log-time"><?php echo date('M j, g:i A', strtotime($log['action_timestamp'])); ?></div>
                                <div class="log-content">
                                    <span class="log-admin"><?php echo htmlspecialchars($log['admin_username']); ?></span>
                                    <span class="log-action"><?php echo htmlspecialchars($log['action']); ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
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
