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


// Get dashboard statistics
// Total students count
$stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE grade_level NOT IN ('Teacher', 'Admin', 'Developer')");
$total_students = $stmt->fetch()['count'];

// Active students (logged in within last 7 days)
$stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE grade_level NOT IN ('Teacher', 'Admin', 'Developer') AND last_login >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
$active_students = $stmt->fetch()['count'];

// Total vocabulary questions
$total_vocab = 0;
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM vocabulary_questions WHERE is_active = 1");
    $result = $stmt->fetch();
    if ($result) {
        $total_vocab = $result['count'];
    }
} catch (PDOException $e) {
    // vocabulary_questions table may not exist yet
    $total_vocab = 0;
}

// Grade levels count
$stmt = $pdo->query("SELECT COUNT(DISTINCT grade_level) as count FROM users WHERE grade_level NOT IN ('Teacher', 'Admin', 'Developer')");
$grade_levels_count = $stmt->fetch()['count'];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include '../../includes/favicon.php'; ?>
    <title>Teacher Dashboard - Word Weavers</title>
    <link rel="stylesheet" href="../../styles.css?v=<?php echo filemtime('../../styles.css'); ?>">
    <link rel="stylesheet" href="../../navigation/shared/navigation.css?v=<?php echo filemtime('../../navigation/shared/navigation.css'); ?>">
    <link rel="stylesheet" href="../../notif/toast.css?v=<?php echo filemtime('../../notif/toast.css'); ?>">
    <link rel="stylesheet" href="assets/css/dashboard.css?v=<?php echo time(); ?>">
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
            <a href="dashboard.php" class="nav-link active">
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
        <!-- Dashboard View -->
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
                    ?> <i class="fas <?php echo $weatherIcon; ?>" style="margin-left: 8px; opacity: 0.9;"></i></h2>
                    <div class="welcome-roles">
                        <span class="welcome-role-badge">
                            <i class="fas fa-chalkboard-teacher"></i>
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

            <!-- Quick Stats -->
            <div class="quick-stats">
                <div class="quick-stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Total Students</h3>
                        <div class="value"><?php echo number_format($total_students); ?></div>
                    </div>
                </div>
                <div class="quick-stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);">
                        <i class="fas fa-book"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Vocabulary Bank</h3>
                        <div class="value"><?php echo number_format($total_vocab); ?></div>
                    </div>
                </div>
                <div class="quick-stat-card">
                    <div class="stat-icon stat-icon-online" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
                        <div class="online-status-indicator"></div>
                    </div>
                    <div class="stat-content">
                        <h3>Active Students</h3>
                        <div class="value"><?php echo number_format($active_students); ?></div>
                    </div>
                </div>
                <div class="quick-stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">
                        <i class="fas fa-layer-group"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Grade Levels</h3>
                        <div class="value"><?php echo number_format($grade_levels_count); ?></div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Add/Edit Vocabulary Modal -->
    <div class="modal-overlay" id="vocabModal">
        <div class="modal-content vocab-modal">
            <div class="modal-header">
                <h2 id="modalTitle">Add New Vocabulary</h2>
                <button class="modal-close" onclick="closeVocabModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="vocabForm" onsubmit="saveVocab(event)">
                <input type="hidden" id="vocabId" name="vocab_id">
                
                <div class="form-group">
                    <label for="word">Vocabulary Word <span class="required">*</span></label>
                    <input type="text" id="word" name="word" required placeholder="Enter vocabulary word">
                </div>

                <div class="form-group">
                    <label for="definition">Definition <span class="required">*</span></label>
                    <textarea id="definition" name="definition" required rows="3" placeholder="Enter word definition"></textarea>
                </div>

                <div class="form-group">
                    <label for="example">Example Sentence</label>
                    <textarea id="example" name="example" rows="2" placeholder="Enter example sentence (optional)"></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="gradeLevel">Grade Level <span class="required">*</span></label>
                        <select id="gradeLevel" name="grade_level" required>
                            <option value="">Select Grade</option>
                            <option value="7">Grade 7</option>
                            <option value="8">Grade 8</option>
                            <option value="9">Grade 9</option>
                            <option value="10">Grade 10</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="difficulty">Difficulty Level <span class="required">*</span></label>
                        <select id="difficulty" name="difficulty" required>
                            <option value="">Select Level</option>
                            <option value="1">Level 1 (Easy)</option>
                            <option value="2">Level 2</option>
                            <option value="3">Level 3 (Medium)</option>
                            <option value="4">Level 4</option>
                            <option value="5">Level 5 (Hard)</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label>Multiple Choice Options <span class="required">*</span></label>
                    <p class="form-hint">Enter 4 choices. Mark the correct answer with the radio button.</p>
                    
                    <div class="choices-container">
                        <div class="choice-item">
                            <input type="radio" name="correct_choice" value="0" id="correct0" required>
                            <input type="text" name="choices[]" id="choice0" placeholder="Choice 1" required>
                        </div>
                        <div class="choice-item">
                            <input type="radio" name="correct_choice" value="1" id="correct1" required>
                            <input type="text" name="choices[]" id="choice1" placeholder="Choice 2" required>
                        </div>
                        <div class="choice-item">
                            <input type="radio" name="correct_choice" value="2" id="correct2" required>
                            <input type="text" name="choices[]" id="choice2" placeholder="Choice 3" required>
                        </div>
                        <div class="choice-item">
                            <input type="radio" name="correct_choice" value="3" id="correct3" required>
                            <input type="text" name="choices[]" id="choice3" placeholder="Choice 4" required>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="closeVocabModal()">Cancel</button>
                    <button type="submit" class="btn-save">
                        <i class="fas fa-save"></i> Save Vocabulary
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal-overlay" id="deleteModal">
        <div class="modal-content delete-modal">
            <div class="modal-header">
                <h2>Confirm Delete</h2>
                <button class="modal-close" onclick="closeDeleteModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete the vocabulary word "<strong id="deleteWordName"></strong>"?</p>
                <p class="warning-text">This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeDeleteModal()">Cancel</button>
                <button type="button" class="btn-delete-confirm" onclick="confirmDelete()">
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

    // Grade filter function
    function updateGradeFilter(grade) {
        const currentUrl = new URL(window.location.href);
        currentUrl.searchParams.set('grade', grade);
        window.location.href = currentUrl.toString();
    }

    // Vocabulary filter function
    function updateVocabFilter(filterType, value) {
        const currentUrl = new URL(window.location.href);
        currentUrl.searchParams.set(filterType, value);
        window.location.href = currentUrl.toString();
    }
    </script>
</body>
</html>
