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
$stmt = $pdo->prepare("SELECT username, email, grade_level, profile_image FROM users WHERE id = ?");
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

// Get sort parameters for user table
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'id';
$order = isset($_GET['order']) ? $_GET['order'] : 'asc';
$grade_filter = isset($_GET['grade']) ? $_GET['grade'] : 'all';

// Validate sort column
$valid_columns = ['id', 'username', 'email', 'grade_level', 'created_at'];
$sort = in_array($sort, $valid_columns) ? $sort : 'id';
$order = $order === 'desc' ? 'DESC' : 'ASC';

// Build the query for user table
$query = "SELECT id, username, email, grade_level, created_at FROM users";
$params = [];

if ($grade_filter !== 'all') {
    $query .= " WHERE grade_level = ?";
    $params[] = $grade_filter;
}

$query .= " ORDER BY $sort $order";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$users = $stmt->fetchAll();

// Get unique grade levels for filter
$grade_levels = $pdo->query("SELECT DISTINCT grade_level FROM users ORDER BY grade_level")->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include '../../includes/favicon.php'; ?>
    <title>User Management - Admin - Word Weavers</title>
    <link rel="stylesheet" href="../../styles.css?v=<?php echo filemtime('../../styles.css'); ?>">
    <link rel="stylesheet" href="../../navigation/shared/navigation.css?v=<?php echo filemtime('../../navigation/shared/navigation.css'); ?>">
    <link rel="stylesheet" href="../../notif/toast.css?v=<?php echo filemtime('../../notif/toast.css'); ?>">
    <link rel="stylesheet" href="assets/css/dashboard.css?v=<?php echo filemtime('assets/css/dashboard.css'); ?>">
    <link rel="stylesheet" href="assets/css/delete-modal.css?v=<?php echo filemtime('assets/css/delete-modal.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="../../navigation/teacher/assets/css/vocabulary.css?v=<?php echo time(); ?>">
    <style>
        /* Override or add specific admin styles if needed */
        .admin-container {
            /* Inherit base styles from teacher-container in vocabulary.css */
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
            <a href="user-management.php" class="nav-link active">
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
        <!-- User Management Header -->
        <div class="hero-container">
            <div class="welcome-section">
                <div class="welcome-content">
                    <h2>
                        <img src="../../assets/menu/ww_logo_main.webp" alt="Word Weavers Logo" style="height: 40px; width: auto; margin-right: 15px;">
                        User Management
                    </h2>
                    <div class="welcome-roles">
                        <span class="welcome-role-badge">
                            <i class="fas fa-users-cog"></i>
                            Manage Users
                        </span>
                        <span class="welcome-role-badge">
                            <i class="fas fa-shield-alt"></i>
                            Permissions
                        </span>
                    </div>
                </div>
                <div class="welcome-datetime" style="display: none;"></div>
            </div>
        </div>

            <!-- User Management Section -->
            <div class="admin-container">
                <div class="vocabulary-section">
                    <div class="section-header">
                        <h3 style="transform: translateY(-3px);"><i class="fas fa-users-cog"></i> User Management</h3>
                        <div class="table-controls" style="margin-left: auto;">
                            <div class="filter-group">
                                <div class="grade-filter">
                                    <label for="gradeFilter">Filter by Grade:</label>
                                    <select id="gradeFilter" onchange="updateGradeFilter(this.value)">
                                        <option value="all" <?= $grade_filter === 'all' ? 'selected' : '' ?>>All Grades</option>
                                        <?php foreach ($grade_levels as $grade): ?>
                                            <option value="<?= htmlspecialchars($grade) ?>" <?= $grade_filter === $grade ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($grade) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="search-box">
                                <i class="fas fa-search"></i>
                                <input type="text" id="userSearch" placeholder="Search users...">
                            </div>
                            <button class="add-vocab-btn" onclick="exportUsers()" style="padding: 0.4rem 0.8rem; font-size: 0.85rem; background: rgba(0, 255, 135, 0.15); border: 1px solid rgba(0, 255, 135, 0.3); color: rgba(0, 255, 135, 1);">
                                <i class="fas fa-download"></i>
                                <span>Export</span>
                            </button>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <div id="loadingIndicator" style="display:none;">
                            <div class="loading-content">
                                <i class="fas fa-spinner fa-spin"></i>
                                <div style="color:white; margin-top:10px;">Loading users...</div>
                            </div>
                        </div>
                        <table class="vocab-table">
                            <thead>
                                <tr>
                                    <th class="sortable" data-sort="id" data-order="<?= $sort === 'id' && $order === 'ASC' ? 'desc' : 'asc' ?>">
                                        ID <?= $sort === 'id' ? ($order === 'ASC' ? '↑' : '↓') : '' ?>
                                    </th>
                                    <th class="sortable" data-sort="username" data-order="<?= $sort === 'username' && $order === 'ASC' ? 'desc' : 'asc' ?>">
                                        Username <?= $sort === 'username' ? ($order === 'ASC' ? '↑' : '↓') : '' ?>
                                    </th>
                                    <th class="sortable" data-sort="email" data-order="<?= $sort === 'email' && $order === 'ASC' ? 'desc' : 'asc' ?>">
                                        Email <?= $sort === 'email' ? ($order === 'ASC' ? '↑' : '↓') : '' ?>
                                    </th>
                                    <th>Grade Level</th>
                                    <th class="sortable" data-sort="created_at" data-order="<?= $sort === 'created_at' && $order === 'ASC' ? 'desc' : 'asc' ?>">
                                        Join Date <?= $sort === 'created_at' ? ($order === 'ASC' ? '↑' : '↓') : '' ?>
                                    </th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="usersTbody">
                                <?php if (empty($users)): ?>
                                    <tr>
                                        <td colspan="6" class="no-vocab">No users found</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($users as $user): ?>
                                        <tr>
                                            <td data-label="ID"><?= htmlspecialchars($user['id']) ?></td>
                                            <td data-label="Username">
                                                <div class="creator-info" style="display: flex; align-items: center; gap: 8px;">
                                                    <img src="../../assets/menu/defaultuser.png" 
                                                         alt="Avatar" 
                                                         class="creator-avatar"
                                                         style="width: 24px; height: 24px; border-radius: 50%; object-fit: cover; border: 1px solid rgba(255,255,255,0.2);">
                                                    <span class="creator-name"><?= htmlspecialchars($user['username']) ?></span>
                                                </div>
                                            </td>
                                            <td data-label="Email"><?= htmlspecialchars($user['email']) ?></td>
                                            <td data-label="Grade Level">
                                                <span class="grade-badge"><?= htmlspecialchars($user['grade_level']) ?></span>
                                            </td>
                                            <td data-label="Join Date"><?= date('M j, Y', strtotime($user['created_at'])) ?></td>
                                            <td class="action-buttons" data-label="Actions">
                                                <button class="btn-edit" onclick="viewUser(<?= $user['id'] ?>)" title="View">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="btn-delete" onclick="deleteUser(<?= $user['id'] ?>, '<?= htmlspecialchars(addslashes($user['username'])) ?>')" title="Delete" <?= ($user['id'] === $_SESSION['user_id'] || $_SESSION['grade_level'] !== 'Developer') ? 'disabled style="opacity:0.5;cursor:not-allowed;"' : '' ?>>
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
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
    <script src="assets/js/user-management.js"></script>
    <script>
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
