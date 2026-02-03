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

// Get vocabulary questions with choices (Fetch ALL for client-side filtering)
$vocabulary_questions = [];
$total_vocab = 0;

try {
    $query = "SELECT vq.*, u.username as creator_name, u.profile_image as creator_image,
              (SELECT COUNT(*) FROM vocabulary_choices WHERE question_id = vq.id) as choice_count
              FROM vocabulary_questions vq
              LEFT JOIN users u ON vq.created_by = u.id
              WHERE vq.is_active = 1
              ORDER BY vq.created_at DESC";

    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $vocabulary_questions = $stmt->fetchAll();
} catch (PDOException $e) {
    // vocabulary_questions table may not exist yet
    $vocabulary_questions = [];
}

// Get total count
$total_vocab = count($vocabulary_questions);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include '../../includes/favicon.php'; ?>
    <title>Vocabulary Bank - Word Weavers</title>
    <link rel="stylesheet" href="../../styles.css?v=<?php echo filemtime('../../styles.css'); ?>">
    <link rel="stylesheet" href="../../navigation/shared/navigation.css?v=<?php echo filemtime('../../navigation/shared/navigation.css'); ?>">
    <link rel="stylesheet" href="../../notif/toast.css?v=<?php echo filemtime('../../notif/toast.css'); ?>">
    <link rel="stylesheet" href="assets/css/dashboard.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="assets/css/vocabulary.css?v=<?php echo time(); ?>">
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
                <a href="lessons.php" class="nav-link nav-sub-link">
                    <i class="fas fa-chalkboard-teacher"></i>
                    <span>Lessons</span>
                </a>
                <a href="vocabulary.php" class="nav-link nav-sub-link active">
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
        <div class="hero-container">
            <div class="welcome-section">
                <div class="welcome-content" style="flex: 0 0 auto;">
                    <div class="quick-stat-card" style="background: transparent; border: none; padding: 0; box-shadow: none;">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #00C853 0%, #009624 100%);">
                            <i class="fas fa-book"></i>
                        </div>
                        <div class="stat-content">
                            <h3 style="color: var(--white);">Total Vocabularies</h3>
                            <div class="value" style="color: var(--white);"><?php echo number_format($total_vocab); ?></div>
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
        .filter-group {
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

            .filter-group {
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
        <!-- Vocabulary Bank View -->
        <div class="teacher-container">
            <div class="vocabulary-section">
                <div class="section-header">
                    <h3 style="transform: translateY(-3px);"><i class="fas fa-list"></i> All Vocabulary Questions</h3>
                    <div class="table-controls" style="margin-left: auto;">
                        <div class="filter-group">
                            <div class="grade-filter">
                                <label for="gradeFilterVocab">Grade:</label>
                                <select id="gradeFilterVocab">
                                    <option value="all">All Grades</option>
                                    <option value="7">Grade 7</option>
                                    <option value="8">Grade 8</option>
                                    <option value="9">Grade 9</option>
                                    <option value="10">Grade 10</option>
                                </select>
                            </div>
                            <div class="grade-filter">
                                <label for="difficultyFilter">Difficulty:</label>
                                <select id="difficultyFilter">
                                    <option value="all">All Levels</option>
                                    <option value="1">Level 1</option>
                                    <option value="2">Level 2</option>
                                    <option value="3">Level 3</option>
                                    <option value="4">Level 4</option>
                                    <option value="5">Level 5</option>
                                </select>
                            </div>
                        </div>
                        <div class="search-box">
                            <i class="fas fa-search"></i>
                            <input type="text" id="vocabSearch" placeholder="Search vocabulary...">
                        </div>
                        <button class="add-vocab-btn" onclick="openAddModal()" style="padding: 0.4rem 0.8rem; font-size: 0.85rem;">
                            <i class="fas fa-plus"></i>
                            <span>Add New Word</span>
                        </button>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="vocab-table">
                        <thead>
                            <tr>
                                <th class="sortable" data-sort="word" data-order="asc">Title</th>
                                <th class="sortable" data-sort="definition" data-order="asc">Question</th>
                                <th class="sortable" data-sort="grade" data-order="asc">Grade</th>
                                <th class="sortable" data-sort="difficulty" data-order="asc">Difficulty</th>
                                <th>Created by</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="vocabTbody">
                            <?php if (empty($vocabulary_questions)): ?>
                                <tr>
                                    <td colspan="6" class="no-vocab">No vocabulary questions found. Click "Add New Word" to create one.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($vocabulary_questions as $question): ?>
                                    <tr data-word="<?= strtolower(htmlspecialchars($question['word'])) ?>" 
                                        data-definition="<?= strtolower(htmlspecialchars($question['definition'])) ?>"
                                        data-grade="<?= htmlspecialchars($question['grade_level']) ?>"
                                        data-difficulty="<?= htmlspecialchars($question['difficulty']) ?>">
                                        <td data-label="Title">
                                            <strong><?= htmlspecialchars($question['word']) ?></strong>
                                        </td>
                                        <td data-label="Question" class="definition-cell">
                                            <?= htmlspecialchars(substr($question['definition'], 0, 80)) . (strlen($question['definition']) > 80 ? '...' : '') ?>
                                        </td>
                                        <td data-label="Grade">
                                            <span class="grade-badge">Grade <?= htmlspecialchars($question['grade_level']) ?></span>
                                        </td>
                                        <td data-label="Difficulty">
                                            <span class="difficulty-badge diff-<?= $question['difficulty'] ?>">
                                                Level <?= $question['difficulty'] ?>
                                            </span>
                                        </td>
                                        <td data-label="Created by">
                                            <div class="creator-info" style="display: flex; align-items: center; gap: 8px;">
                                                <img src="<?= !empty($question['creator_image']) ? '../../' . htmlspecialchars($question['creator_image']) : '../../assets/menu/defaultuser.png' ?>" 
                                                     alt="Avatar" 
                                                     class="creator-avatar"
                                                     style="width: 24px; height: 24px; border-radius: 50%; object-fit: cover; border: 1px solid rgba(255,255,255,0.2);">
                                                <span class="creator-name"><?= htmlspecialchars(explode(' ', $question['creator_name'])[0]) ?></span>
                                            </div>
                                        </td>
                                        <td data-label="Actions" class="action-buttons">
                                            <button class="btn-edit" onclick="editVocab(<?= $question['id'] ?>)" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn-delete" onclick="deleteVocab(<?= $question['id'] ?>, '<?= htmlspecialchars($question['word']) ?>')" title="Delete">
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

    <!-- Add/Edit Vocabulary Modal -->
    <div class="modal-overlay" id="vocabModal">
        <div class="modal-content vocab-modal">
            <div class="modal-header">
                <div class="modal-title-wrapper" style="display: flex; align-items: center; gap: 15px;">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #00C853 0%, #009624 100%); width: 40px; height: 40px; font-size: 18px;">
                        <i class="fas fa-book"></i>
                    </div>
                    <h2 id="modalTitle">Add New Vocabulary</h2>
                </div>
                <button class="modal-close" onclick="closeVocabModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="vocabForm" onsubmit="saveVocab(event)">
                <input type="hidden" id="vocabId" name="vocab_id">
                
                <div class="form-group">
                    <label for="word">Title <span class="required">*</span></label>
                    <input type="text" id="word" name="word" required placeholder="Enter title">
                </div>

                <div class="form-group">
                    <label for="definition">Question <span class="required">*</span></label>
                    <textarea id="definition" name="definition" required rows="3" placeholder="Enter question"></textarea>
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
                <div class="modal-title-wrapper" style="display: flex; align-items: center; gap: 15px;">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #ff4757 0%, #ff6b6b 100%); width: 40px; height: 40px; font-size: 18px;">
                        <i class="fas fa-trash-alt"></i>
                    </div>
                    <h2 style="margin: 0; color: white; font-size: 1.3rem;">Delete Vocabulary</h2>
                </div>
                <button class="modal-close" onclick="closeDeleteModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete the vocabulary word "<strong id="deleteWordName" style="color: #ff4757;"></strong>"?</p>
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
    </script>
</body>
</html>
