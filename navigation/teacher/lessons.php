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

// Handle Section Filter
$selected_grade = $_GET['grade'] ?? 'all';
$selected_section = $_GET['section'] ?? 'all';

// Build Query
$query = "SELECT l.*, u.username as creator_name, u.profile_image as creator_image 
          FROM lessons l 
          LEFT JOIN users u ON l.created_by = u.id 
          WHERE 1=1";
$params = [];

if ($selected_grade !== 'all') {
    $query .= " AND l.grade_level = ?";
    $params[] = $selected_grade;
}

if ($selected_section !== 'all') {
    $query .= " AND l.section = ?";
    $params[] = $selected_section;
}

$query .= " ORDER BY l.created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$lessons = $stmt->fetchAll();

$total_lessons = count($lessons);

// Get unique sections for filter (optional, can be hardcoded or fetched)
$sections_stmt = $pdo->query("SELECT DISTINCT section FROM users WHERE section IS NOT NULL AND section != '' ORDER BY section");
$sections = $sections_stmt->fetchAll(PDO::FETCH_COLUMN);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include '../../includes/favicon.php'; ?>
    <title>Lessons - Word Weavers</title>
    <link rel="stylesheet" href="../../styles.css?v=<?php echo filemtime('../../styles.css'); ?>">
    <link rel="stylesheet" href="../../navigation/shared/navigation.css?v=<?php echo filemtime('../../navigation/shared/navigation.css'); ?>">
    <link rel="stylesheet" href="../../notif/toast.css?v=<?php echo filemtime('../../notif/toast.css'); ?>">
    <link rel="stylesheet" href="assets/css/dashboard.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="assets/css/vocabulary.css?v=<?php echo time(); ?>"> <!-- Reusing vocabulary css for table styles -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        /* Specific styles for lessons page if needed */
        .lesson-truncate {
            max-width: 300px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
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
                <!-- Controls Link -->
                <a href="vocabworld.php" class="nav-link nav-sub-link">
                    <i class="fas fa-gamepad"></i>
                    <span>Controls</span>
                </a>
                <!-- Lessons Link (Active) -->
                <a href="lessons.php" class="nav-link nav-sub-link active">
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
                        <i class="fas fa-chalkboard-teacher"></i>
                    </div>
                    <div class="stat-content">
                        <h3 style="color: var(--white);">Total Lessons</h3>
                        <div class="value" id="totalLessonsValue" style="color: var(--white);"><?php echo number_format($total_lessons); ?></div>
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
        <style>
        /* Responsive adjustments for table controls */
        #filterForm {
            display: flex; 
            gap: 10px; 
            align-items: center;
            flex-wrap: wrap;
        }
        
        @media (max-width: 768px) {
            .section-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .table-controls {
                margin-left: 0 !important;
                width: 100%;
                flex-direction: column;
                align-items: stretch;
                gap: 10px;
            }

            #filterForm {
                width: 100%;
                flex-direction: column;
                align-items: stretch;
            }

            .grade-filter, .search-box {
                width: 100%;
            }

            .add-vocab-btn {
                width: 100%;
                justify-content: center;
            }
        }
        </style>
        <!-- Lessons View -->
        <div class="teacher-container">
            <div class="vocabulary-section">
                <div class="section-header">
                    <h3 style="transform: translateY(-3px);"><i class="fas fa-chalkboard-teacher"></i> All Lessons</h3>
                    <div class="table-controls" style="margin-left: auto;">
                        <div id="filterForm">
                            <div class="grade-filter">
                                <label for="gradeFilter">Grade:</label>
                                <select id="gradeFilter" name="grade" onchange="filterLessons()">
                                    <option value="all" <?= $selected_grade === 'all' ? 'selected' : '' ?>>All Grades</option>
                                    <option value="7" <?= $selected_grade === '7' ? 'selected' : '' ?>>Grade 7</option>
                                    <option value="8" <?= $selected_grade === '8' ? 'selected' : '' ?>>Grade 8</option>
                                    <option value="9" <?= $selected_grade === '9' ? 'selected' : '' ?>>Grade 9</option>
                                    <option value="10" <?= $selected_grade === '10' ? 'selected' : '' ?>>Grade 10</option>
                                </select>
                            </div>
                            <div class="grade-filter">
                                <label for="sectionFilter">Section:</label>
                                <select id="sectionFilter" name="section" onchange="filterLessons()">
                                    <option value="all">All Sections</option>
                                    <?php foreach ($sections as $sec): ?>
                                        <option value="<?= htmlspecialchars($sec) ?>" <?= $selected_section === $sec ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($sec) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="search-box">
                            <i class="fas fa-search"></i>
                            <input type="text" id="lessonSearch" placeholder="Search lessons..." onkeyup="filterLessons()">
                        </div>
                        <a href="create_lesson.php" class="add-vocab-btn" style="padding: 0.4rem 0.8rem; font-size: 0.85rem; text-decoration: none;">
                            <i class="fas fa-plus"></i>
                            <span>Create Lesson</span>
                        </a>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="vocab-table">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Grade</th>
                                <th>Section</th>
                                <th>Created by</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="lessonsTableBody">
                            <?php if (empty($lessons)): ?>
                                <tr>
                                    <td colspan="6" class="no-vocab">No lessons found. Click "Create Lesson" to add one.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($lessons as $lesson): ?>
                                    <tr>
                                        <td data-label="Title">
                                            <strong><?= htmlspecialchars($lesson['title']) ?></strong>
                                        </td>
                                        <td data-label="Grade">
                                            <span class="grade-badge">Grade <?= htmlspecialchars($lesson['grade_level']) ?></span>
                                        </td>
                                        <td data-label="Section">
                                            <?= htmlspecialchars($lesson['section'] ?: 'All Sections') ?>
                                        </td>
                                        <td data-label="Created by">
                                            <div class="creator-info" style="display: flex; align-items: center; gap: 8px;">
                                                <img src="<?= !empty($lesson['creator_image']) ? '../../' . htmlspecialchars($lesson['creator_image']) : '../../assets/menu/defaultuser.png' ?>" 
                                                     alt="Avatar" 
                                                     class="creator-avatar"
                                                     style="width: 24px; height: 24px; border-radius: 50%; object-fit: cover; border: 1px solid rgba(255,255,255,0.2);">
                                                <span class="creator-name"><?= htmlspecialchars(explode(' ', $lesson['creator_name'])[0]) ?></span>
                                            </div>
                                        </td>
                                        <td data-label="Date">
                                            <?= date('M d, Y', strtotime($lesson['created_at'])) ?>
                                        </td>
                                        <td data-label="Actions" class="action-buttons">
                                            <a href="edit_lesson.php?id=<?= $lesson['id'] ?>" class="btn-edit" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button class="btn-delete" onclick="deleteLesson(<?= $lesson['id'] ?>, '<?= addslashes(htmlspecialchars($lesson['title'])) ?>')" title="Delete">
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

    <!-- Delete Confirmation Modal -->
    <div class="modal-overlay" id="deleteModal">
        <div class="modal-content delete-modal">
            <div class="modal-header">
                <div class="modal-title-wrapper" style="display: flex; align-items: center; gap: 15px;">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #ff4757 0%, #ff6b6b 100%); width: 40px; height: 40px; font-size: 18px;">
                        <i class="fas fa-trash-alt"></i>
                    </div>
                    <h2 style="margin: 0; color: white; font-size: 1.3rem;">Delete Lesson</h2>
                </div>
                <button class="modal-close" onclick="closeDeleteModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete the lesson "<strong id="deleteLessonTitle" style="color: #ff4757;"></strong>"?</p>
                <p class="warning-text" style="color: rgba(255,255,255,0.6); font-size: 0.9rem; margin-top: 5px;">This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeDeleteModal()">Cancel</button>
                <button type="button" class="btn-delete-confirm" onclick="confirmDelete()" style="background: rgba(255, 71, 87, 0.1); border: 1px solid rgba(255, 71, 87, 0.3); color: #ff4757; padding: 0.6rem 1.2rem; border-radius: 8px; cursor: pointer; font-weight: 600; transition: all 0.3s ease;">
                    <i class="fas fa-trash"></i> Delete
                </button>
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

    // Delete Lesson functionality
    let lessonToDeleteId = null;

    function deleteLesson(id, title) {
        lessonToDeleteId = id;
        document.getElementById('deleteLessonTitle').textContent = title;
        document.getElementById('deleteModal').classList.add('show');
    }

    function closeDeleteModal() {
        document.getElementById('deleteModal').classList.remove('show');
        lessonToDeleteId = null;
    }

    function confirmDelete() {
        if (!lessonToDeleteId) return;
        
        fetch('delete_lesson.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'id=' + lessonToDeleteId
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // If filtering is in place, we should reload the list or remove row, 
                // but reloading page to be safe is fine, OR we could call filterLessons()
                // simpler is to reload, but user wanted no reload on FILTER.
                // Let's call filterLessons() to refresh the list without page reload.
                filterLessons(); 
                closeDeleteModal();
                // We might also need to reload because 'total lessons' might change
            } else {
                alert('Error deleting lesson: ' + data.message);
                closeDeleteModal();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred.');
            closeDeleteModal();
        });
    }

    function filterLessons() {
        const grade = document.getElementById('gradeFilter').value;
        const section = document.getElementById('sectionFilter').value;
        const search = document.getElementById('lessonSearch').value;

        fetch(`fetch_lessons.php?grade=${encodeURIComponent(grade)}&section=${encodeURIComponent(section)}&search=${encodeURIComponent(search)}`)
            .then(response => response.json())
            .then(data => {
                document.getElementById('lessonsTableBody').innerHTML = data.html;
                document.getElementById('totalLessonsValue').textContent = data.total;
            })
            .catch(error => console.error('Error fetching lessons:', error));
    }
    </script>
</body>
</html>
