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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include '../../includes/favicon.php'; ?>
    <title>Analytics - Admin - Word Weavers</title>
    <link rel="stylesheet" href="../../styles.css?v=<?php echo filemtime('../../styles.css'); ?>">
    <link rel="stylesheet" href="../../navigation/shared/navigation.css?v=<?php echo filemtime('../../navigation/shared/navigation.css'); ?>">
    <link rel="stylesheet" href="../../notif/toast.css?v=<?php echo filemtime('../../notif/toast.css'); ?>">
    <link rel="stylesheet" href="assets/css/dashboard.css?v=<?php echo filemtime('assets/css/dashboard.css'); ?>">
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
            <a href="dashboard.php" class="nav-link">
                <i class="fas fa-chart-line"></i>
                <span>Dashboard</span>
            </a>
            <a href="analytics.php" class="nav-link active">
                <i class="fas fa-chart-pie"></i>
                <span>Analytics</span>
            </a>
            <a href="user-management.php" class="nav-link">
                <i class="fas fa-users-cog"></i>
                <span>User Management</span>
            </a>
            <a href="audit-logs.php" class="nav-link">
                <i class="fas fa-history"></i>
                <span>Audit Logs</span>
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
        <!-- Analytics Header -->
        <div class="hero-container">
            <div class="welcome-section">
                <div class="welcome-content">
                    <h2>
                        <img src="../../assets/menu/ww_logo_main.webp" alt="Word Weavers Logo" style="height: 40px; width: auto; margin-right: 15px;">
                        Analytics
                    </h2>
                    <div class="welcome-roles">
                        <span class="welcome-role-badge">
                            <i class="fas fa-chart-pie"></i>
                            System Overview
                        </span>
                        <span class="welcome-role-badge">
                            <i class="fas fa-search-plus"></i>
                            Detailed Insights
                        </span>
                    </div>
                </div>
                <div class="welcome-datetime" style="display: none;">
                    <!-- Hide datetime on subpages if not needed, or keep consistent -->
                </div>
            </div>
        </div>

        <!-- Analytics Charts Grid -->
        <!-- Daily Traffic Chart -->
        <div class="dashboard-card" style="margin-bottom: 2rem;">
            <div class="card-header">
                <h3><i class="fas fa-chart-line"></i> Daily Traffic (Last 30 Days)</h3>
            </div>
            <div class="card-body">
                <canvas id="dailyTrafficChart" style="height: 300px;"></canvas>
            </div>
        </div>

        <div class="dashboard-grid">
            <!-- User Distribution Chart -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h3><i class="fas fa-users"></i> User Distribution by Grade</h3>
                    <div class="chart-filter">
                        <select id="distributionFilter" onchange="updateDistributionChart(this.value)">
                            <option value="grade">By Grade</option>
                            <option value="role">By Role</option>
                        </select>
                    </div>
                </div>
                <div class="card-body">
                    <canvas id="distributionChart"></canvas>
                </div>
            </div>

            <!-- GWA Performance Chart -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h3><i class="fas fa-graduation-cap"></i> Average GWA by Grade</h3>
                </div>
                <div class="card-body">
                    <canvas id="gwaChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Additional Analytics Cards -->
        <div class="dashboard-grid">
            <!-- Role Distribution -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h3><i class="fas fa-user-tag"></i> Role Distribution</h3>
                </div>
                <div class="card-body">
                    <div class="role-stats">
                        <?php if (!empty($dashboardStats['role_distribution'])): ?>
                            <?php foreach ($dashboardStats['role_distribution'] as $role): ?>
                                <div class="role-item">
                                    <div class="role-label">
                                        <?php 
                                            $roleIcon = 'fa-user';
                                            if ($role['role'] === 'Admin') $roleIcon = 'fa-user-shield';
                                            elseif ($role['role'] === 'Teacher') $roleIcon = 'fa-chalkboard-teacher';
                                            elseif ($role['role'] === 'Student') $roleIcon = 'fa-user-graduate';
                                        ?>
                                        <i class="fas <?php echo $roleIcon; ?>" style="margin-right: 8px; opacity: 0.8;"></i>
                                        <?php echo htmlspecialchars($role['role']); ?>
                                    </div>
                                    <div class="role-value"><?php echo $role['count']; ?> users</div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="no-data">No role data available</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Top GWA by Grade -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h3><i class="fas fa-trophy"></i> Top GWA by Grade Level</h3>
                </div>
                <div class="card-body">
                    <div class="gwa-grade-list">
                        <?php if (!empty($dashboardStats['top_gwa_by_grade'])): ?>
                            <?php foreach ($dashboardStats['top_gwa_by_grade'] as $grade): ?>
                                <div class="gwa-grade-item">
                                    <div class="gwa-grade-name">
                                        <i class="fas fa-graduation-cap" style="margin-right: 8px; opacity: 0.8;"></i>
                                        <?php echo htmlspecialchars($grade['grade_level']); ?>
                                    </div>
                                    <div class="gwa-grade-stats">
                                        <span>Avg: <?php echo number_format($grade['avg_gwa'], 2); ?></span>
                                        <span>Students: <?php echo $grade['student_count']; ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="no-data">No GWA data available</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
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
        distribution: <?php echo json_encode($dashboardStats['grade_distribution']); ?>,
        role_distribution: <?php echo json_encode($dashboardStats['role_distribution']); ?>,
        top_gwa: <?php echo json_encode($dashboardStats['top_gwa_by_grade']); ?>,
        daily_traffic: <?php echo json_encode($dashboardStats['daily_traffic'] ?? []); ?>
    };
    
    let distributionChart = null;
    let gwaChart = null;
    let dailyTrafficChart = null;

    const COLOR_PALETTE = [
        '#4cc9f0', // Blue
        '#00ff87', // Green
        '#ffc107', // Amber
        '#f44336', // Red
        '#9c27b0', // Purple
        '#ff6b6b', // Pink
        '#4ecdc4', // Teal
        '#45b7d1'  // Light Blue
    ];

    // Helper to get consistent color for a label
    const labelColorMap = {};
    function getColorForLabel(label, index) {
        if (!labelColorMap[label]) {
            labelColorMap[label] = COLOR_PALETTE[Object.keys(labelColorMap).length % COLOR_PALETTE.length];
        }
        return labelColorMap[label];
    }

    function getColorsForLabels(labels) {
        return labels.map((label, index) => getColorForLabel(label, index));
    }
    
    // Initialize Charts
    document.addEventListener('DOMContentLoaded', function() {
        initializeDistributionChart('grade');
        initializeGWAChart();
        initializeDailyTrafficChart();
    });

    function initializeDailyTrafficChart() {
        const ctx = document.getElementById('dailyTrafficChart');
        if (!ctx) return;

        if (dailyTrafficChart) {
            dailyTrafficChart.destroy();
        }

        const data = chartData.daily_traffic || [];
        // Fill in missing dates with 0 if needed, or just plot available data. 
        // For simplicity, plotting available data. Ideally, we should fill gaps in PHP or JS.
        // Let's create a label array for the last 30 days and map counts to it for a continuous line.
        
        const labels = [];
        const counts = [];
        const today = new Date();
        const dataMap = {};
        
        data.forEach(item => {
            dataMap[item.login_date] = parseInt(item.count);
        });

        for (let i = 29; i >= 0; i--) {
            const d = new Date();
            d.setDate(today.getDate() - i);
            const dateStr = d.toISOString().split('T')[0];
            labels.push(d.toLocaleDateString(undefined, { month: 'short', day: 'numeric' }));
            counts.push(dataMap[dateStr] || 0);
        }

        dailyTrafficChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Daily Logins',
                    data: counts,
                    borderColor: '#4cc9f0', // Blue
                    backgroundColor: 'rgba(76, 201, 240, 0.2)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4, // Smooth curves
                    pointBackgroundColor: '#4cc9f0',
                    pointBorderColor: '#fff',
                    pointHoverBackgroundColor: '#fff',
                    pointHoverBorderColor: '#4cc9f0'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            color: '#e0e0e0',
                            stepSize: 1
                        },
                        grid: {
                            color: 'rgba(255, 255, 255, 0.1)'
                        }
                    },
                    x: {
                        ticks: {
                            color: '#e0e0e0',
                            maxTicksLimit: 10
                        },
                        grid: {
                            display: false
                        }
                    }
                },
                interaction: {
                    mode: 'nearest',
                    axis: 'x',
                    intersect: false
                }
            }
        });
    }

    function initializeDistributionChart(type) {
        const ctx = document.getElementById('distributionChart');
        if (!ctx) return;

        if (distributionChart) {
            distributionChart.destroy();
        }

        let labels, values, title;
        
        if (type === 'grade') {
            if (!chartData.distribution || chartData.distribution.length === 0) return;
            labels = chartData.distribution.map(item => item.grade_level);
            values = chartData.distribution.map(item => item.count);
            title = 'User Distribution by Grade';
        } else {
            if (!chartData.role_distribution || chartData.role_distribution.length === 0) return;
            labels = chartData.role_distribution.map(item => item.role);
            values = chartData.role_distribution.map(item => item.count);
            title = 'User Distribution by Role';
        }

        distributionChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Users',
                    data: values,
                    backgroundColor: getColorsForLabels(labels),
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
                                return context.label + ': ' + context.parsed + ' users';
                            }
                        }
                    }
                }
            }
        });
    }

    function initializeGWAChart() {
        const ctx = document.getElementById('gwaChart');
        if (!ctx) return;

        if (gwaChart) {
            gwaChart.destroy();
        }

        if (!chartData.top_gwa || chartData.top_gwa.length === 0) return;
        
        const labels = chartData.top_gwa.map(item => item.grade_level);
        const values = chartData.top_gwa.map(item => parseFloat(item.avg_gwa).toFixed(2));

        gwaChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Average GWA',
                    data: values,
                    backgroundColor: getColorsForLabels(labels),
                    borderColor: 'rgba(255, 255, 255, 0.2)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'Average GWA: ' + context.parsed.y;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 10,
                        ticks: {
                            color: '#e0e0e0',
                            stepSize: 1
                        },
                        grid: {
                            color: 'rgba(255, 255, 255, 0.1)'
                        }
                    },
                    x: {
                        ticks: {
                            color: '#e0e0e0'
                        },
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    }

    function updateDistributionChart(type) {
        initializeDistributionChart(type);
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
