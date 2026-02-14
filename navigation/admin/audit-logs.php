<?php
require_once '../../onboarding/config.php';
require_once '../../includes/greeting.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: ../../onboarding/login.php');
    exit();
}

// Check if user is admin or developer
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT grade_level, username, profile_image, email FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$current_user = $stmt->fetch(); // Renamed to $current_user to match other admin pages

$is_jaderby = (strtolower($current_user['username']) === 'jaderby garcia peñaranda');
$is_admin = ($current_user['grade_level'] === 'Admin' || $is_jaderby);

if (!$is_admin) {
    header('Location: ../../menu.php');
    exit();
}

// Pagination setup
$page = isset($_GET['p']) ? max(1, intval($_GET['p'])) : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Filter setup
$filter_action = isset($_GET['action']) ? $_GET['action'] : '';
$filter_user = isset($_GET['user']) ? $_GET['user'] : '';

// Build query
$query = "SELECT a.*, u.username as user_username, u.profile_image 
          FROM audit_logs a 
          LEFT JOIN users u ON a.user_id = u.id 
          WHERE 1=1";
$params = [];

if (!empty($filter_action)) {
    $query .= " AND a.action = ?";
    $params[] = $filter_action;
}

if (!empty($filter_user)) {
    $query .= " AND (a.username LIKE ? OR u.username LIKE ?)";
    $params[] = "%$filter_user%";
    $params[] = "%$filter_user%";
}

// Get total count for pagination
$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM ($query) as sub");
$count_stmt->execute($params);
$total_rows = $count_stmt->fetchColumn();
$total_pages = ceil($total_rows / $limit);

// Add sorting and limits
$query .= " ORDER BY a.created_at DESC LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$logs = $stmt->fetchAll();

// Get unique actions for filter dropdown
$actions_stmt = $pdo->query("SELECT DISTINCT action FROM audit_logs ORDER BY action");
$actions = $actions_stmt->fetchAll(PDO::FETCH_COLUMN);

// Notification count (for header)
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
    <title>Audit Logs - Word Weavers Admin</title>
    <link rel="stylesheet" href="../../styles.css?v=<?php echo filemtime('../../styles.css'); ?>">
    <link rel="stylesheet" href="../../navigation/shared/navigation.css?v=<?php echo filemtime('../../navigation/shared/navigation.css'); ?>">
    <link rel="stylesheet" href="../../notif/toast.css?v=<?php echo filemtime('../../notif/toast.css'); ?>">
    <link rel="stylesheet" href="assets/css/dashboard.css?v=<?php echo filemtime('assets/css/dashboard.css'); ?>">
    <link rel="stylesheet" href="assets/css/delete-modal.css?v=<?php echo filemtime('assets/css/delete-modal.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="../../navigation/teacher/assets/css/vocabulary.css?v=<?php echo time(); ?>">
    <style>
        .admin-container {
            background: rgba(10, 10, 15, 0.95);
            border-radius: 25px;
            padding: 1.25rem;
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            border: 2px solid rgba(96, 239, 255, 0.15);
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.2);
            position: relative;
            overflow: hidden;
        }
        .admin-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, rgba(96, 239, 255, 0.8), rgba(0, 255, 135, 0.8));
        }

        /* Audit specific action badges */
        .log-action-badge {
            display: inline-block;
            padding: 0.3rem 0.8rem;
            border-radius: 15px;
            font-size: 0.75rem;
            font-weight: bold;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .action-login { background: rgba(0, 255, 0, 0.2); border-color: rgba(0, 255, 0, 0.4); color: #8aff8a; }
        .action-logout { background: rgba(255, 0, 0, 0.2); border-color: rgba(255, 0, 0, 0.4); color: #ff8a8a; }
        .action-update { background: rgba(96, 239, 255, 0.2); border-color: rgba(96, 239, 255, 0.4); color: #60efff; }
        .action-game { background: rgba(255, 165, 0, 0.2); border-color: rgba(255, 165, 0, 0.4); color: #ffdca8; }
        .action-friend { background: rgba(255, 0, 255, 0.2); border-color: rgba(255, 0, 255, 0.4); color: #ff8aff; }
    </style>
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
            <a href="analytics.php" class="nav-link">
                <i class="fas fa-chart-pie"></i>
                <span>Analytics</span>
            </a>
            <a href="user-management.php" class="nav-link">
                <i class="fas fa-users-cog"></i>
                <span>User Management</span>
            </a>
            <a href="audit-logs.php" class="nav-link active">
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

    <div class="main-content">
        <!-- Hero Section -->
        <div class="hero-container">
            <div class="welcome-section">
                <div class="welcome-content">
                    <h2>
                        <img src="../../assets/menu/ww_logo_main.webp" alt="Word Weavers Logo" style="height: 40px; width: auto; margin-right: 15px;">
                        System Audit Logs
                    </h2>
                    <div class="welcome-roles">
                        <span class="welcome-role-badge">
                            <i class="fas fa-shield-alt"></i>
                            Security & Monitoring
                        </span>
                        <span class="welcome-role-badge">
                            <i class="fas fa-history"></i>
                            Activity Tracking
                        </span>
                    </div>
                </div>
                <!-- Optional date time can go here if same JS is used -->
            </div>
        </div>

        <!-- Audit Logs Table Section -->
        <div class="admin-container">
            <div class="vocabulary-section">
                <div class="section-header">
                    <h3 style="transform: translateY(-3px);"><i class="fas fa-history"></i> Recent Activity</h3>
                    
                    <div class="table-controls" style="margin-left: auto;">
                        <form method="GET" class="filter-form" style="display: flex; gap: 0.5rem; margin: 0; background: none; padding: 0; border: none;">
                            <div class="filter-group">
                                <div class="grade-filter">
                                    <label for="actionFilter">Filter by Action:</label>
                                    <select id="actionFilter" name="action" onchange="this.form.submit()">
                                        <option value="">All Actions</option>
                                        <?php foreach ($actions as $action): ?>
                                            <option value="<?php echo htmlspecialchars($action); ?>" <?php echo $filter_action === $action ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($action); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="search-box">
                                <i class="fas fa-search"></i>
                                <input type="text" name="user" placeholder="Search User..." value="<?php echo htmlspecialchars($filter_user); ?>">
                            </div>
                             <button type="submit" style="display: none;">Filter</button>
                        </form>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="vocab-table">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Action</th>
                                <th>Details</th>
                                <th>IP Address</th>
                                <th>Date & Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($logs)): ?>
                                <tr>
                                    <td colspan="5" class="no-vocab">No audit logs found matching your criteria.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($logs as $log): ?>
                                    <?php
                                        // Determine style based on action
                                        $actionClass = '';
                                        $act = strtolower($log['action']);
                                        if (strpos($act, 'login') !== false) $actionClass = 'action-login';
                                        elseif (strpos($act, 'logout') !== false) $actionClass = 'action-logout';
                                        elseif (strpos($act, 'update') !== false) $actionClass = 'action-update';
                                        elseif (strpos($act, 'game') !== false || strpos($act, 'click') !== false) $actionClass = 'action-game';
                                        elseif (strpos($act, 'friend') !== false) $actionClass = 'action-friend';
                                    ?>
                                    <tr>
                                        <td data-label="User">
                                            <div class="creator-info" style="display: flex; align-items: center; gap: 8px;">
                                                <img src="<?php echo !empty($log['profile_image']) ? '../../' . htmlspecialchars($log['profile_image']) : '../../assets/menu/defaultuser.png'; ?>" 
                                                     alt="Avatar" 
                                                     class="creator-avatar"
                                                     style="width: 24px; height: 24px; border-radius: 50%; object-fit: cover; border: 1px solid rgba(255,255,255,0.2);">
                                                <span class="creator-name"><?php echo htmlspecialchars($log['username']); ?></span>
                                            </div>
                                        </td>
                                        <td data-label="Action">
                                            <span class="log-action-badge <?php echo $actionClass; ?>">
                                                <?php echo htmlspecialchars($log['action']); ?>
                                            </span>
                                        </td>
                                        <td data-label="Details" style="max-width: 300px; white-space: normal; line-height: 1.4; color: #ccc;">
                                            <?php echo htmlspecialchars($log['details']); ?>
                                        </td>
                                        <td data-label="IP Address"><?php echo htmlspecialchars($log['ip_address']); ?></td>
                                        <td data-label="Date"><?php echo date('M j, Y g:i A', strtotime($log['created_at'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <div class="pagination" style="display: flex; justify-content: center; gap: 0.5rem; margin-top: 2rem;">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?p=<?php echo $i; ?>&action=<?php echo urlencode($filter_action); ?>&user=<?php echo urlencode($filter_user); ?>" 
                           class="page-link <?php echo $i === $page ? 'active' : ''; ?>"
                           style="padding: 0.5rem 1rem; background: rgba(0, 0, 0, 0.6); border: 1px solid rgba(96, 239, 255, 0.3); color:white; text-decoration: none; border-radius: 5px; transition: all 0.3s ease; <?php echo $i === $page ? 'background: rgba(96, 239, 255, 0.2); border-color: #60efff;' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                </div>
                <?php endif; ?>
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

    <!-- Script imports (reuse main scripts) -->
    <script src="../../script.js"></script>
    <script src="../shared/profile-dropdown.js"></script>
    <script src="../shared/notification-badge.js"></script>
    <script>
        function showLogoutModal() {
            document.getElementById('logoutModal').classList.add('show');
            document.getElementById('logoutConfirmation').classList.add('show');
        }
        function hideLogoutModal() {
            document.getElementById('logoutModal').classList.remove('show');
            document.getElementById('logoutConfirmation').classList.remove('show');
        }
        function confirmLogout() {
            window.location.href = '../../onboarding/logout.php';
        }
    </script>
</body>
</html>
