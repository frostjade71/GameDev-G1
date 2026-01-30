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

// Get user info
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT username, email, grade_level, section, profile_image FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$current_user = $stmt->fetch();

// Get notification count
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM friend_requests WHERE receiver_id = ? AND status = 'pending'");
$stmt->execute([$user_id]);
$notification_count = $stmt->fetch()['count'];

// Get Sections for Dropdown
$sections_stmt = $pdo->query("SELECT DISTINCT section FROM users WHERE section IS NOT NULL AND section != '' ORDER BY section");
$sections = $sections_stmt->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include '../../includes/favicon.php'; ?>
    <title>Create Lesson - Word Weavers</title>
    <link rel="stylesheet" href="../../styles.css?v=<?php echo filemtime('../../styles.css'); ?>">
    <link rel="stylesheet" href="../../navigation/shared/navigation.css?v=<?php echo filemtime('../../navigation/shared/navigation.css'); ?>">
    <link rel="stylesheet" href="../../notif/toast.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="assets/css/dashboard.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="assets/css/vocabulary.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <!-- TinyMCE -->
    <script src="https://cdn.tiny.cloud/1/zsakvou710vz1lno9jg4cswebk4agq3rkdm6xptw78gctcl5/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
    <script>
      tinymce.init({
        selector: '#lessonContent',
        plugins: 'anchor autolink charmap codesample emoticons image link lists media searchreplace table visualblocks wordcount',
        toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | link image media table | align lineheight | numlist bullist indent outdent | emoticons charmap | removeformat',
      });
    </script>
</head>
<body>
    <div class="sidebar teacher-sidebar">
        <!-- Sidebar Content (Same as lessons.php) -->
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
                <a href="vocabworld.php" class="nav-link nav-sub-link">
                    <i class="fas fa-gamepad"></i>
                    <span>Controls</span>
                </a>
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
        <div class="teacher-container">
            <div class="vocabulary-section">
                <div class="section-header" style="justify-content: flex-start; gap: 1rem;">
                    <div class="stat-icon" style="width: 40px; height: 40px; font-size: 1.1rem; background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); border-radius: 8px; box-shadow: none;">
                        <i class="fas fa-plus-circle"></i>
                    </div>
                    <h3 style="margin: 0; font-size: 1.5rem;">Create New Lesson</h3>
                </div>
                
                <form id="createLessonForm" onsubmit="saveLesson(event)" style="background: rgba(255,255,255,0.05); padding: 2rem; border-radius: 12px; border: 1px solid rgba(255,255,255,0.1);">
                    <div class="form-group" style="margin-bottom: 1.5rem;">
                        <label for="title" style="display: block; color: var(--text-secondary); margin-bottom: 0.5rem;">Lesson Title <span style="color: #ff6b6b;">*</span></label>
                        <input type="text" id="title" name="title" required placeholder="Enter lesson title" style="width: 100%; padding: 0.8rem; border-radius: 8px; border: 1px solid rgba(255,255,255,0.2); background: rgba(0,0,0,0.2); color: white;">
                    </div>

                    <div class="form-row" style="display: flex; gap: 20px; margin-bottom: 1.5rem;">
                        <div class="form-group" style="flex: 1;">
                            <label for="gradeLevel" style="display: block; color: var(--text-secondary); margin-bottom: 0.5rem;">Grade Level <span style="color: #ff6b6b;">*</span></label>
                            <select id="gradeLevel" name="grade_level" required style="width: 100%; padding: 0.8rem; border-radius: 8px; border: 1px solid rgba(255,255,255,0.2); background: rgba(0,0,0,0.2); color: white;">
                                <option value="">Select Grade</option>
                                <option value="7">Grade 7</option>
                                <option value="8">Grade 8</option>
                                <option value="9">Grade 9</option>
                                <option value="10">Grade 10</option>
                            </select>
                        </div>
                        <div class="form-group" style="flex: 1;">
                            <label for="section" style="display: block; color: var(--text-secondary); margin-bottom: 0.5rem;">Section</label>
                            <select id="section" name="section" style="width: 100%; padding: 0.8rem; border-radius: 8px; border: 1px solid rgba(255,255,255,0.2); background: rgba(0,0,0,0.2); color: white;">
                                <option value="">All Sections</option>
                                <?php foreach ($sections as $sec): ?>
                                    <option value="<?= htmlspecialchars($sec) ?>"><?= htmlspecialchars($sec) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: 2rem;">
                        <label for="lessonContent" style="display: block; color: var(--text-secondary); margin-bottom: 0.5rem;">Content</label>
                        <textarea id="lessonContent" name="content"></textarea>
                    </div>

                    <div class="form-actions" style="display: flex; gap: 10px; justify-content: flex-end;">
                        <button type="button" onclick="window.location.href='lessons.php'" style="padding: 0.8rem 1.5rem; border-radius: 8px; background: transparent; border: 1px solid rgba(255,255,255,0.2); color: white; cursor: pointer;">Cancel</button>
                        <button type="submit" style="padding: 0.8rem 1.5rem; border-radius: 8px; background: #10b981; border: none; color: white; cursor: pointer; font-weight: 600;">Create Lesson</button>
                    </div>
                </form>
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

    <script>
        function saveLesson(event) {
            event.preventDefault();
            
            const title = document.getElementById('title').value;
            const gradeLevel = document.getElementById('gradeLevel').value;
            const section = document.getElementById('section').value;
            const content = tinymce.get('lessonContent').getContent();

            const formData = new FormData();
            formData.append('title', title);
            formData.append('grade_level', gradeLevel);
            formData.append('section', section);
            formData.append('content', content);

            fetch('save_lesson.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.href = 'lessons.php';
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred.');
            });
        }
    </script>
</body>
</html>
