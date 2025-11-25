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
$stmt = $pdo->prepare("SELECT username, email, grade_level, section FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$current_user = $stmt->fetch();

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

// Update session with the grade level from database if not set
if (!isset($_SESSION['grade_level']) && isset($current_user['grade_level'])) {
    $_SESSION['grade_level'] = $current_user['grade_level'];
    // Refresh the page to update session
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}

// Get sort parameters
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'id';
$order = isset($_GET['order']) ? $_GET['order'] : 'asc';
$grade_filter = isset($_GET['grade']) ? $_GET['grade'] : 'all';

// Validate sort column
$valid_columns = ['id', 'username', 'email', 'grade_level', 'section', 'created_at'];
$sort = in_array($sort, $valid_columns) ? $sort : 'id';
$order = $order === 'desc' ? 'DESC' : 'ASC';

// Build the query
$query = "SELECT id, username, email, grade_level, section, created_at FROM users";
$params = [];

// Add grade level filter if not 'all'
if ($grade_filter !== 'all') {
    $query .= " WHERE grade_level = ?";
    $params[] = $grade_filter;
}

// Add sorting
$query .= " ORDER BY $sort $order";

// Execute the query
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$users = $stmt->fetchAll();

// Get unique grade levels for filter
$grade_levels = $pdo->query("SELECT DISTINCT grade_level FROM users ORDER BY grade_level")->fetchAll(PDO::FETCH_COLUMN);

// Get pending friend requests for the current user (for notification count)
$stmt = $pdo->prepare("
    SELECT COUNT(*) as count 
    FROM friend_requests 
    WHERE receiver_id = ? AND status = 'pending'
");
$stmt->execute([$user_id]);
$notification_count = $stmt->fetch()['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../../assets/menu/ww_logo_main.webp">
    <title>Moderation Panel - Word Weavers</title>
    <link rel="stylesheet" href="../../styles.css">
    <link rel="stylesheet" href="../shared/navigation.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../../notif/toast.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="moderation.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap" rel="stylesheet">
</head>
<body>
    <!-- Mobile Menu Button -->
    <button class="mobile-menu-btn" aria-label="Open menu">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-logo">
            <img src="../../assets/menu/Word-Weavers.png" alt="Word Weavers" class="sidebar-logo-img">
        </div>
        <nav class="sidebar-nav">
            <a href="../../menu.php" class="nav-link">
                <i class="fas fa-house"></i>
                <span>Menu</span>
            </a>
            <a href="../favorites/favorites.php" class="nav-link">
                <i class="fas fa-star"></i>
                <span>Favorites</span>
            </a>
            <a href="../friends/friends.php" class="nav-link">
                <i class="fas fa-users"></i>
                <span>Friends</span>
            </a>
            <div class="nav-item-with-dropdown">
                <a href="../profile/profile.php" class="nav-link" id="profile-dropdown-trigger">
                    <i class="fas fa-user"></i>
                    <span>Profile</span>
                    <i class="fas fa-chevron-down dropdown-arrow"></i>
                </a>
                <div class="nav-dropdown-menu">
                    <a href="moderation.php" class="nav-dropdown-item active" id="moderation-panel">
                        <i class="fas fa-shield-alt"></i>
                        <span>Moderation</span>
                    </a>
                </div>
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
                        <img src="../../assets/menu/defaultuser.png" alt="Profile" class="profile-img">
                    </a>
                    <div class="profile-dropdown-content">
                        <div class="profile-dropdown-header">
                            <img src="../../assets/menu/defaultuser.png" alt="Profile" class="profile-dropdown-avatar">
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
        <div class="moderation-container" id="moderationContainer" data-initial-sort="<?= htmlspecialchars($sort) ?>" data-initial-order="<?= strtolower($order) === 'desc' ? 'desc' : 'asc' ?>" data-initial-grade="<?= htmlspecialchars($grade_filter) ?>">
            <div class="moderation-header">
                <div class="header-title">
                    <img src="../../assets/menu/ww_logo_main.webp" alt="Word Weavers Logo" class="header-logo">
                    <h2>User Management</h2>
                </div>
                <div class="table-controls">
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
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" id="userSearch" placeholder="Search users...">
                    </div>
                </div>
            </div>
            
            <div class="table-responsive">
                <div id="loadingIndicator" style="display:none;">
                    <div class="loading-content">
                        <i class="fas fa-spinner fa-spin"></i>
                        <div>Loading users...</div>
                    </div>
                </div>
                <table class="user-table">
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
                            <th>Section</th>
                            <th class="sortable" data-sort="created_at" data-order="<?= $sort === 'created_at' && $order === 'ASC' ? 'desc' : 'asc' ?>">
                                Join Date <?= $sort === 'created_at' ? ($order === 'ASC' ? '↑' : '↓') : '' ?>
                            </th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="usersTbody">
                        <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="7" class="no-users">No users found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td data-label="ID"><?= htmlspecialchars($user['id']) ?></td>
                                    <td data-label="Username"><?= htmlspecialchars($user['username']) ?></td>
                                    <td data-label="Email"><?= htmlspecialchars($user['email']) ?></td>
                                    <td data-label="Grade Level"><?= htmlspecialchars($user['grade_level']) ?></td>
                                    <td data-label="Section"><?= !empty($user['section']) ? htmlspecialchars($user['section']) : 'N/A' ?></td>
                                    <td data-label="Join Date"><?= date('M j, Y', strtotime($user['created_at'])) ?></td>
                                    <td class="actions" data-label="Actions">
                                        <button class="btn-view" onclick="viewUser(<?= $user['id'] ?>)" title="View">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn-warn" onclick="warnUser(<?= $user['id'] ?>)" title="Warn" <?= $user['id'] === $_SESSION['user_id'] ? 'disabled="disabled"' : '' ?>>
                                            <i class="fas fa-exclamation-triangle"></i>
                                        </button>
                                        <?php if ($_SESSION['grade_level'] === 'Developer' && $user['id'] !== $_SESSION['user_id']): ?>
                                            <button class="btn-delete" onclick="deleteUser(<?= $user['id'] ?>, '<?= htmlspecialchars(addslashes($user['username'])) ?>')" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        <?php else: ?>
                                            <button class="btn-delete" disabled title="Delete (Admin only)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
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
    <script src="moderation.js"></script>
    <script>
    // Page-specific functionality (mobile menu handled by shared script.js)
    document.addEventListener('DOMContentLoaded', function() {
        // Search functionality
        const searchInput = document.getElementById('userSearch');
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase();
                const userCards = document.querySelectorAll('.user-card');
                
                userCards.forEach(card => {
                    const username = card.querySelector('h4').textContent.toLowerCase();
                    const email = card.querySelector('.user-email').textContent.toLowerCase();
                    const section = card.querySelector('.user-section')?.textContent.toLowerCase() || '';
                    
                    if (username.includes(searchTerm) || email.includes(searchTerm) || section.includes(searchTerm)) {
                        card.style.display = '';
                    } else {
                        card.style.display = 'none';
                    }
                });
            });
        }
    });

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
        // Redirect to logout endpoint
        window.location.href = '../../onboarding/logout.php';
    }
    </script>
</body>
</html>