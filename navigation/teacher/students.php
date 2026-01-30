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
$stmt = $pdo->prepare("
    SELECT COUNT(*) as count 
    FROM friend_requests 
    WHERE receiver_id = ? AND status = 'pending'
");
$stmt->execute([$user_id]);
$notification_count = $stmt->fetch()['count'];

// Get sort parameters for student table
// Build the query for students (exclude Teacher, Admin, Developer roles)
$query = "SELECT id, username, email, grade_level, section, created_at FROM users WHERE grade_level NOT IN ('Teacher', 'Admin', 'Developer')";
$params = [];

$query .= " ORDER BY username ASC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$students = $stmt->fetchAll();

// Get unique grade levels for filter (excluding Teacher, Admin, Developer)
$grade_levels = $pdo->query("SELECT DISTINCT grade_level FROM users WHERE grade_level NOT IN ('Teacher', 'Admin', 'Developer') ORDER BY grade_level")->fetchAll(PDO::FETCH_COLUMN);

// Get total student count
$total_students = count($students);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include '../../includes/favicon.php'; ?>
    <title>Students - Word Weavers</title>
    <link rel="stylesheet" href="../../styles.css?v=<?php echo filemtime('../../styles.css'); ?>">
    <link rel="stylesheet" href="../../navigation/shared/navigation.css?v=<?php echo filemtime('../../navigation/shared/navigation.css'); ?>">
    <link rel="stylesheet" href="../../notif/toast.css?v=<?php echo filemtime('../../notif/toast.css'); ?>">
    <link rel="stylesheet" href="assets/css/dashboard.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="assets/css/students.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
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
            <a href="students.php" class="nav-link active">
                <i class="fas fa-user-graduate"></i>
                <span>Students</span>
            </a>
            <div class="nav-section">
                <div class="nav-section-header">
                    <img src="../../MainGame/vocabworld/assets/menu/vv_logo.webp" alt="Vocabworld" class="nav-section-logo">
                    <span>Vocabworld</span>
                </div>
                <a href="vocabworld.php" class="nav-link nav-sub-link">
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
        <!-- Welcome Section -->
        <div class="welcome-section">
            <div class="welcome-content" style="flex: 0 0 auto;">
                <div class="quick-stat-card" style="background: transparent; border: none; padding: 0; box-shadow: none;">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                    <div class="stat-content">
                        <h3 style="color: var(--white);">Total Students</h3>
                        <div class="value" style="color: var(--white);"><?php echo number_format($total_students); ?></div>
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

        <!-- Students View -->
        <div class="teacher-container">
            <div class="students-section">
                <div class="section-header">
                    <h3><i class="fas fa-list"></i> All Students</h3>
                    <div class="table-controls">
                        <div class="grade-filter">
                            <label for="gradeFilter">Filter by Grade:</label>
                            <select id="gradeFilter">
                                <option value="all">All Grades</option>
                                <?php foreach ($grade_levels as $grade): ?>
                                    <option value="<?= htmlspecialchars($grade) ?>">
                                        <?= htmlspecialchars($grade) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="search-box">
                            <i class="fas fa-search"></i>
                            <input type="text" id="studentSearch" placeholder="Search students...">
                        </div>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="student-table">
                        <thead>
                            <tr>
                                <th class="sortable" data-sort="username" data-order="asc">Name</th>
                                <th class="sortable" data-sort="grade_level" data-order="asc">Grade Level</th>
                                <th class="sortable" data-sort="section" data-order="asc">Section</th>
                                <th class="sortable" data-sort="email" data-order="asc">Email</th>
                                <th class="sortable" data-sort="created_at" data-order="asc">Join Date</th>
                            </tr>
                        </thead>
                        <tbody id="studentsTbody">
                            <?php if (empty($students)): ?>
                                <tr>
                                    <td colspan="5" class="no-students">No students found</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($students as $student): ?>
                                    <tr>
                                        <td data-label="Name"><?= htmlspecialchars($student['username']) ?></td>
                                        <td data-label="Grade Level">
                                            <span class="grade-badge"><?= htmlspecialchars($student['grade_level']) ?></span>
                                        </td>
                                        <td data-label="Section"><?= !empty($student['section']) ? htmlspecialchars($student['section']) : 'N/A' ?></td>
                                        <td data-label="Email"><?= htmlspecialchars($student['email']) ?></td>
                                        <td data-label="Join Date"><?= date('M j, Y', strtotime($student['created_at'])) ?></td>
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
    <script src="assets/js/dashboard.js"></script>
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
