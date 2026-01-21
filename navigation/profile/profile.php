<?php
require_once '../../onboarding/config.php';
require_once '../../includes/greeting.php';
require_once '../../includes/gwa_updater.php';

// Check if user is logged in, redirect to login if not
if (!isLoggedIn()) {
    header('Location: ../../onboarding/login.php');
    exit();
}

// Get user information
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT username, email, grade_level, section, about_me, created_at, profile_image FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    // User not found, destroy session and redirect to login
    session_destroy();
    header('Location: ../../onboarding/login.php');
    exit();
}

// Handle profile update
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $section = trim($_POST['section'] ?? '');
    $about_me = trim($_POST['about_me'] ?? '');
    
    // Validate input
    if (empty($username) || empty($email)) {
        echo json_encode(['success' => false, 'message' => 'Username and email are required']);
        exit();
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email format']);
        exit();
    }
    
    // Check if username or email already exists (excluding current user)
    $stmt = $pdo->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
    $stmt->execute([$username, $email, $user_id]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Username or email already exists']);
        exit();
    }
    
    // Update user profile
    $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, section = ?, about_me = ?, updated_at = NOW() WHERE id = ?");
    if ($stmt->execute([$username, $email, $section, $about_me, $user_id])) {
        // Return the updated values for JavaScript to use
        echo json_encode([
            'success' => true, 
            'message' => 'Profile updated successfully!',
            'about_me' => $about_me,
            'section' => $section,
            'username' => $username
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update profile']);
    }
    exit();
}

// Handle profile image upload
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'upload_profile_image') {
    // Check if file was uploaded
    if (!isset($_FILES['profile_image']) || $_FILES['profile_image']['error'] === UPLOAD_ERR_NO_FILE) {
        echo json_encode(['success' => false, 'message' => 'No file was uploaded']);
        exit();
    }
    
    $file = $_FILES['profile_image'];
    
    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'File upload error']);
        exit();
    }
    
    // Validate file size (5MB = 5,242,880 bytes)
    $maxFileSize = 5 * 1024 * 1024; // 5MB
    if ($file['size'] > $maxFileSize) {
        echo json_encode(['success' => false, 'message' => 'File size must be less than 5MB']);
        exit();
    }
    
    // Validate file type
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, $allowedTypes)) {
        echo json_encode(['success' => false, 'message' => 'Only image files (JPG, PNG, GIF, WEBP) are allowed']);
        exit();
    }
    
    // Get file extension
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid file extension']);
        exit();
    }
    
    // Generate unique filename
    $newFilename = 'user_' . $user_id . '_' . time() . '.' . $extension;
    $uploadDir = '../../uploads/profile_avatars/';
    $uploadPath = $uploadDir . $newFilename;
    
    // Create directory if it doesn't exist
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Delete old profile image if exists
    if (!empty($user['profile_image']) && file_exists('../../' . $user['profile_image'])) {
        unlink('../../' . $user['profile_image']);
    }
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
        // Update database with new profile image path
        $relativePath = 'uploads/profile_avatars/' . $newFilename;
        $stmt = $pdo->prepare("UPDATE users SET profile_image = ?, updated_at = NOW() WHERE id = ?");
        
        if ($stmt->execute([$relativePath, $user_id])) {
            echo json_encode([
                'success' => true,
                'message' => 'Profile image updated successfully!',
                'image_path' => $relativePath
            ]);
        } else {
            // Delete uploaded file if database update fails
            unlink($uploadPath);
            echo json_encode(['success' => false, 'message' => 'Failed to update database']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to upload file']);
    }
    exit();
}

// Get user's rank from leaderboard
$rank_stmt = $pdo->prepare("
    SELECT COUNT(*) + 1 as user_rank
    FROM users u
    LEFT JOIN game_progress gp ON u.id = gp.user_id AND gp.game_type = 'vocabworld'
    WHERE u.id != ?
    AND (COALESCE(gp.player_level, 1) > COALESCE((SELECT player_level FROM game_progress WHERE user_id = ? AND game_type = 'vocabworld'), 1)
         OR (COALESCE(gp.player_level, 1) = COALESCE((SELECT player_level FROM game_progress WHERE user_id = ? AND game_type = 'vocabworld'), 1)
             AND COALESCE(gp.total_monsters_defeated, 0) > COALESCE((SELECT total_monsters_defeated FROM game_progress WHERE user_id = ? AND game_type = 'vocabworld'), 0)))
");
$rank_stmt->execute([$user_id, $user_id, $user_id, $user_id]);
$user_rank = $rank_stmt->fetchColumn();

// Update all user GWAs first
updateAllUserGWAs($pdo, $user_id);

// Get user's game stats with stored GWA
$stmt = $pdo->prepare("SELECT 
    gp.game_type,
    ug.gwa as gwa_score,
    gp.player_level,
    gp.total_experience_earned,
    gp.total_monsters_defeated,
    gp.total_play_time as total_play_time_seconds
    FROM game_progress gp
    LEFT JOIN user_gwa ug ON gp.user_id = ug.user_id AND gp.game_type = ug.game_type
    WHERE gp.user_id = ?");
$stmt->execute([$user_id]);
$game_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

// For backward compatibility, ensure gwa_score is set (fallback to calculation if not in user_gwa)
foreach ($game_stats as &$stat) {
    if (!isset($stat['gwa_score']) || $stat['gwa_score'] === null) {
        $stat['gwa_score'] = $stat['player_level'] * 1.5;
    }
}
unset($stat); // Break the reference

// Get player level, experience, and monsters defeated
$stmt = $pdo->prepare("
    SELECT 
        COALESCE(SUM(player_level), 1) as total_level,
        COALESCE(SUM(total_experience_earned), 0) as total_experience,
        COALESCE(SUM(total_monsters_defeated), 0) as total_monsters_defeated,
        COALESCE(SUM(total_play_time), 0) as total_play_time_seconds
    FROM game_progress 
    WHERE user_id = ?
");
$stmt->execute([$user_id]);
$player_stats = $stmt->fetch();

// Get character selection data for JavaScript
$character_stmt = $pdo->prepare("
    SELECT character_image_path, selected_character 
    FROM character_selections 
    WHERE user_id = ? AND game_type = 'vocabworld' 
    LIMIT 1
");
$character_stmt->execute([$user_id]);
$character_result = $character_stmt->fetch();

// Set default values for JavaScript
$character_images = [
    'emma' => '../../MainGame/vocabworld/assets/characters/girl_char/character_emma.png',
    'ethan' => '../../MainGame/vocabworld/assets/characters/boy_char/character_ethan.png',
    'amber' => '../../MainGame/vocabworld/assets/characters/amber_char/amber.png',
    'girl' => '../../MainGame/vocabworld/assets/characters/girl_char/character_emma.png',
    'boy' => '../../MainGame/vocabworld/assets/characters/boy_char/character_ethan.png'
];

$character_image = $character_images['ethan'];
$character_name = 'Ethan';

if ($character_result) {
    // If we have a character selection in the database
    if (!empty($character_result['selected_character'])) {
        $character_name = $character_result['selected_character'];
        $char_key = strtolower($character_name);
        
        // If we have a direct match in our character images array
        if (isset($character_images[$char_key])) {
            $character_image = $character_images[$char_key];
        } 
        // Otherwise try to find a matching character in the paths
        else if (!empty($character_result['character_image_path'])) {
            $character_image = $character_result['character_image_path'];
            foreach ($character_images as $char => $path) {
                if (stripos($character_image, $char) !== false) {
                    $character_image = $path;
                    break;
                }
            }
        }
    } 
    // Fallback to extracting name from image path if no selected_character
    else if (!empty($character_result['character_image_path'])) {
        $character_image = $character_result['character_image_path'];
        if (preg_match('/character_([^.]+)\./', $character_image, $matches)) {
            $character_name = ucfirst($matches[1]);
        }
    }
}


// Get Essence
$essence_manager_path = '../../MainGame/vocabworld/api/essence_manager.php';
if (file_exists($essence_manager_path)) {
    require_once $essence_manager_path;
    if (class_exists('EssenceManager')) {
        $essenceManager = new EssenceManager($pdo);
        $essence = $essenceManager->getEssence($user_id);
    }
}

// Get Shards
$shard_manager_path = '../../MainGame/vocabworld/shard_manager.php';
if (file_exists($shard_manager_path)) {
    require_once $shard_manager_path;
    if (class_exists('ShardManager')) {
        $shardManager = new ShardManager($pdo);
        $shard_result = $shardManager->getShardBalance($user_id);
        if ($shard_result && isset($shard_result['current_shards'])) {
            $shards = $shard_result['current_shards'];
        }
    }
}

// Get user's favorites with game info
$stmt = $pdo->prepare("SELECT game_type FROM user_favorites WHERE user_id = ?");
$stmt->execute([$user_id]);
$favorites = $stmt->fetchAll();

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

// Get user's friends count
$stmt = $pdo->prepare("
    SELECT COUNT(*) as friends_count 
    FROM friends 
    WHERE user1_id = ? OR user2_id = ?
");
$stmt->execute([$user_id, $user_id]);
$friends_count = $stmt->fetch()['friends_count'];

// Initialize user_fame table and get user stats
function initializeUserFame($pdo, $username) {
    $stmt = $pdo->prepare("INSERT IGNORE INTO user_fame (username, cresents, views) VALUES (?, 0, 0)");
    $stmt->execute([$username]);
}

function getUserFame($pdo, $username) {
    initializeUserFame($pdo, $username);
    $stmt = $pdo->prepare("SELECT cresents, views FROM user_fame WHERE username = ?");
    $stmt->execute([$username]);
    return $stmt->fetch();
}

// Create user_crescents table if it doesn't exist
$pdo->exec("CREATE TABLE IF NOT EXISTS user_crescents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    giver_username VARCHAR(255) NOT NULL,
    receiver_username VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_crescent (giver_username, receiver_username)
)");

// Get user fame stats
$user_fame = getUserFame($pdo, $user['username']);
$views_count = $user_fame ? $user_fame['views'] : 0;
$crescents_count = $user_fame ? $user_fame['cresents'] : 0;

// No longer calculating user level since it's been replaced with email
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/webp" href="../../assets/images/ww_logo.webp">
    <title>Profile - Word Weavers</title>
    <link rel="stylesheet" href="../../styles.css">
    <link rel="stylesheet" href="../shared/navigation.css?v=<?php echo filemtime('../shared/navigation.css'); ?>">
    <link rel="stylesheet" href="../../notif/toast.css?v=<?php echo filemtime('../../notif/toast.css'); ?>">
    <link rel="stylesheet" href="../../includes/loader.css?v=<?php echo filemtime('../../includes/loader.css'); ?>">
    <link rel="stylesheet" href="../../includes/crop-modal.css?v=<?php echo filemtime('../../includes/crop-modal.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css">
    <link rel="stylesheet" href="profile.css?v=<?php echo filemtime('profile.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>
<body>
    <?php include '../../includes/page-loader.php'; ?>
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
            <a href="profile.php" class="nav-link active">
                <i class="fas fa-user"></i>
                <span>Profile</span>
            </a>
            <?php if (in_array($user['grade_level'], ['Developer', 'Admin'])): ?>
            <a href="../moderation/moderation.php" class="nav-link">
                <i class="fas fa-shield-alt"></i>
                <span>Admin</span>
            </a>
            <?php endif; ?>
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
                    <span class="username"><?php echo htmlspecialchars(explode(' ', $user['username'])[0]); ?></span>
                </div>
                <div class="profile-dropdown">
                    <a href="#" class="profile-icon">
                        <img src="<?php echo !empty($user['profile_image']) ? '../../' . htmlspecialchars($user['profile_image']) : '../../assets/menu/defaultuser.png'; ?>" alt="Profile" class="profile-img">
                    </a>
                    <div class="profile-dropdown-content">
                        <div class="profile-dropdown-header">
                            <img src="<?php echo !empty($user['profile_image']) ? '../../' . htmlspecialchars($user['profile_image']) : '../../assets/menu/defaultuser.png'; ?>" alt="Profile" class="profile-dropdown-avatar">
                            <div class="profile-dropdown-info">
                                <div class="profile-dropdown-name"><?php echo htmlspecialchars($user['username']); ?></div>
                                <div class="profile-dropdown-email"><?php echo htmlspecialchars($user['email']); ?></div>
                            </div>
                        </div>
                        <div class="profile-dropdown-menu">
                            <a href="profile.php" class="profile-dropdown-item">
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
                        </div>
                        <div class="profile-dropdown-footer">
                            <button class="profile-dropdown-item sign-out" onclick="showLogoutModal()">
                                <i class="fas fa-sign-out-alt"></i>
                                <span>Sign Out</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <div class="main-content">
        <div class="profile-container">
            <div class="profile-header">
                <div class="profile-avatar">
                    <img src="<?php echo !empty($user['profile_image']) ? '../../' . htmlspecialchars($user['profile_image']) : '../../assets/menu/defaultuser.png'; ?>" alt="Profile" class="large-avatar" id="profile-avatar-img">
                    <button class="change-avatar-btn" id="change-avatar-btn">
                        <i class="fas fa-camera"></i>
                    </button>
                    <input type="file" id="profile-image-input" accept="image/jpeg,image/jpg,image/png,image/gif,image/webp" style="display: none;">
                </div>
                <div class="profile-info">
                    <h1><?php echo htmlspecialchars($user['username']); ?></h1>
                    <p class="player-email"><?php echo htmlspecialchars($user['email']); ?></p>
                    <p class="about-me-text"><?php echo ($user['about_me'] !== null && $user['about_me'] !== '') ? htmlspecialchars($user['about_me']) : 'Tell us something about yourself...'; ?></p>
                    
                    <!-- User Fame Section -->
                    <div class="user-fame-section">
                        <div class="fame-stats">
                            <div class="fame-item">
                                <div class="tooltip">Friends: <?php echo number_format($friends_count); ?></div>
                                <img src="../../assets/pixels/friendhat.png" alt="Friends" class="fame-icon">
                                <span class="fame-value"><?php echo number_format($friends_count); ?></span>
                            </div>
                            <span class="fame-separator">●</span>
                            <div class="fame-item">
                                <div class="tooltip">Profile Views: <?php echo number_format($views_count); ?></div>
                                <img src="../../assets/pixels/eyeviews.png" alt="Views" class="fame-icon">
                                <span class="fame-value"><?php echo number_format($views_count); ?></span>
                            </div>
                            <span class="fame-separator">●</span>
                            <div class="fame-item">
                                <div class="tooltip">Crescents: <?php echo number_format($crescents_count); ?></div>
                                <img src="../../assets/pixels/cresent.png" alt="Crescents" class="fame-icon">
                                <span class="fame-value"><?php echo number_format($crescents_count); ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="badge-container">
                        <?php 
                        $is_jaderby = (strtolower($user['username']) === 'jaderby garcia peñaranda');
                        $is_admin = ($user['grade_level'] === 'Admin' || $is_jaderby);
                        $is_teacher = ($user['grade_level'] === 'Teacher');
                        
                        if ($is_jaderby): ?>
                            <div class="badge-wrapper" onclick="showBadgeInfo('Developer', 'Lead Developer of Word Weavers'); return false;">
                                <img src="../../assets/badges/developer.png" alt="Developer Badge" class="user-badge">
                                <div class="badge-tooltip">
                                    <span class="badge-title">Developer</span>
                                    <span class="badge-desc">Lead Developer</span>
                                </div>
                            </div>
                        <?php endif; ?>
                        <?php if ($is_admin): ?>
                            <div class="badge-wrapper" onclick="showBadgeInfo('Administrator', 'Has full administrative privileges' . ($is_jaderby ? ' and is the developer' : '') . '.'); return false;">
                                <img src="../../assets/badges/moderator.png" alt="Admin Badge" class="user-badge">
                                <div class="badge-tooltip">
                                    <span class="badge-title">Admin</span>
                                    <span class="badge-desc">System Admin</span>
                                </div>
                            </div>
                        <?php endif; ?>
                        <?php if ($is_teacher): ?>
                            <div class="badge-wrapper" onclick="showBadgeInfo('Teacher', 'Certified educator with teaching privileges.'); return false;">
                                <img src="../../assets/badges/teacher.png" alt="Teacher Badge" class="user-badge">
                                <div class="badge-tooltip">
                                    <span class="badge-title">Teacher</span>
                                    <span class="badge-desc">Educator</span>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Grade & Section Container -->
            <div class="grade-section-container">
                <div class="section-header">
                    <i class="fas fa-graduation-cap"></i>
                    <h3>Grade & Section</h3>
                </div>
                <div class="grade-section-grid">
                    <div class="grade-section-item">
                        <div class="grade-section-icon">
                            <i class="fas fa-user-graduate"></i>
                        </div>
                        <div class="grade-section-details">
                            <span class="grade-section-label">Grade Level</span>
                            <span class="grade-section-value"><?php echo htmlspecialchars($user['grade_level']); ?></span>
                        </div>
                    </div>
                    <div class="grade-section-item">
                        <div class="grade-section-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="grade-section-details">
                            <span class="grade-section-label">Section</span>
                            <span class="grade-section-value"><?php echo !empty($user['section']) ? htmlspecialchars($user['section']) : 'Not specified'; ?></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Player Stats Section (Empty, will be moved to Game Stats) -->
            <div class="player-stats-section" style="display: none;"></div>
            
            <!-- Game Stats Section -->
            <div class="gamestats-section">
                <div class="section-header">
                    <div class="header-title">
                        <i class="fas fa-gamepad"></i>
                        <h3>Game Stats</h3>
                    </div>
                </div>

                <div class="game-stats-body">
                    <!-- Player Profile Section -->
                    <div class="player-profile-section">
                        <div class="character-display">
                            <div class="character-avatar-wrapper">
                                <div class="character-avatar-glow"></div>
                                <img src="<?php echo htmlspecialchars($character_image); ?>" 
                                     alt="<?php echo htmlspecialchars($character_name); ?>" 
                                     class="character-avatar"
                                     id="character-sprite"
                                     data-character="<?php echo strtolower($character_name); ?>">
                            </div>
                            <div class="character-details">
                                <h3 class="character-name" id="character-name"><?php echo htmlspecialchars($character_name); ?></h3>
                                <div class="character-level-badge">
                                    <i class="fas fa-star"></i>
                                    <span>Level <?php echo number_format($player_stats['total_level']); ?></span>
                                </div>
                            </div>
                            <?php if (isset($user_rank)): ?>
                                <div class="character-rank-badge rank-<?php echo $user_rank <= 3 ? $user_rank : 'other'; ?>">
                                    <?php if ($user_rank <= 3): ?>
                                        <i class="fas fa-trophy"></i>
                                    <?php endif; ?>
                                    <span>Rank #<?php echo $user_rank; ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Stats Grid -->
                    <div class="stats-sections">
                        <!-- Combat Stats -->
                        <div class="stats-category">
                            <div class="category-header">
                                <img src="../../assets/pixels/blueorb.png" alt="Stats" style="width: 24px; height: 24px; margin-right: 8px;">
                                <h4>Stats</h4>
                            </div>
                            <div class="stats-cards-grid">
                                <div class="stat-card-modern">
                                    <div class="stat-card-icon">
                                        <img src="../../MainGame/vocabworld/assets/stats/level.png" alt="Level">
                                    </div>
                                    <div class="stat-card-content">
                                        <span class="stat-card-label">Level</span>
                                        <span class="stat-card-value"><?php echo number_format($player_stats['total_level']); ?></span>
                                    </div>
                                </div>
                                <div class="stat-card-modern">
                                    <div class="stat-card-icon">
                                        <img src="../../MainGame/vocabworld/assets/stats/total_xp.png" alt="Experience">
                                    </div>
                                    <div class="stat-card-content">
                                        <span class="stat-card-label">Experience</span>
                                        <span class="stat-card-value"><?php echo number_format($player_stats['total_experience']); ?></span>
                                    </div>
                                </div>
                                <div class="stat-card-modern">
                                    <div class="stat-card-icon">
                                        <img src="../../MainGame/vocabworld/assets/stats/sword1.png" alt="Monsters">
                                    </div>
                                    <div class="stat-card-content">
                                        <span class="stat-card-label">Monsters Defeated</span>
                                        <span class="stat-card-value"><?php echo number_format($player_stats['total_monsters_defeated']); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Resources -->
                        <div class="stats-category">
                            <div class="category-header">
                                <img src="../../assets/pixels/bluedias.png" alt="Resources" style="width: 24px; height: 24px; margin-right: 8px;">
                                <h4>Resources</h4>
                            </div>
                            <div class="stats-cards-grid resources-grid">
                                <div class="stat-card-modern resource-card">
                                    <div class="stat-card-icon">
                                        <img src="../../MainGame/vocabworld/assets/currency/essence.png" alt="Essence">
                                    </div>
                                    <div class="stat-card-content">
                                        <span class="stat-card-label">Essence</span>
                                        <span class="stat-card-value essence-value"><?php echo number_format($essence); ?></span>
                                    </div>
                                </div>
                                <div class="stat-card-modern resource-card">
                                    <div class="stat-card-icon">
                                        <img src="../../MainGame/vocabworld/assets/currency/shard1.png" alt="Shards">
                                    </div>
                                    <div class="stat-card-content">
                                        <span class="stat-card-label">Shards</span>
                                        <span class="stat-card-value shard-value"><?php echo number_format($shards); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Performance -->
                        <div class="stats-category">
                            <div class="category-header">
                                <img src="../../MainGame/vocabworld/assets/menu/instructionicon.png" alt="Performance" style="width: 24px; height: 24px; margin-right: 8px;">
                                <h4>Performance</h4>
                            </div>
                            <div class="stats-cards-grid">
                                <div class="stat-card-modern gwa-card">
                                    <div class="stat-card-icon gwa-icon">
                                        <img src="../../MainGame/vocabworld/assets/stats/gwa.png" alt="GWA">
                                    </div>
                                    <div class="stat-card-content">
                                        <span class="stat-card-label">GWA</span>
                                        <span class="stat-card-value gwa-value-display"><?php echo number_format($player_stats['total_level'] * 1.5, 2); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Favorites Section -->
            <div class="favorites-section">
                <div class="section-header">
                    <i class="fas fa-heart"></i>
                    <h3>Favorite Games</h3>
                </div>
                <div class="favorites-container">
                    <?php if (empty($favorites)): ?>
                        <div class="no-data">
                            <i class="fas fa-heart-broken"></i>
                            <p>No favorite games yet. Add some games to your favorites!</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($favorites as $favorite): ?>
                            <?php
                            $game_name = '';
                            $game_logo = '';
                            switch ($favorite['game_type']) {
                                case 'grammar-heroes':
                                    $game_name = 'Grammar Heroes';
                                    $game_logo = '../../assets/selection/Grammarlogo.webp';
                                    break;
                                case 'vocabworld':
                                    $game_name = 'Vocabworld';
                                    $game_logo = '../../assets/selection/vocablogo.webp';
                                    break;
                                default:
                                    $game_name = ucfirst(str_replace('-', ' ', $favorite['game_type']));
                                    $game_logo = '../../assets/selection/vocablogo.webp';
                            }
                            ?>
                            <div class="favorite-card">
                                <img src="<?php echo $game_logo; ?>" alt="<?php echo $game_name; ?>" class="game-logo" title="<?php echo $game_name; ?>">
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Member Since Section -->
            <div class="member-since-section">
                <p class="member-since">Member since <?php echo date('F j, Y', strtotime($user['created_at'])); ?></p>
            </div>
            
            <div class="profile-settings">
                <h2><i class="fas fa-cog"></i> Profile Settings</h2>
                <form class="settings-form" id="profileForm">
                    <div class="form-group">
                        <label>Player Name</label>
                        <input type="text" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>
                            Email 
                            <span class="info-icon" onclick="showEmailTooltip(this)">
                                <i class="fas fa-info-circle"></i>
                                <span class="tooltip-text">Contact the Developer if you want to change your email</span>
                            </span>
                        </label>
                        <input type="email" value="<?php echo htmlspecialchars($user['email']); ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label>About Me</label>
                        <textarea name="about_me" rows="4" placeholder="Tell us about yourself..."><?php echo htmlspecialchars($user['about_me'] ?? ''); ?></textarea>
                    </div>
                    <div class="form-group">
                        <label>Grade Level</label>
                        <input type="text" value="<?php echo htmlspecialchars($user['grade_level']); ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label>Section</label>
                        <input type="text" name="section" value="<?php echo htmlspecialchars($user['section'] ?? ''); ?>" placeholder="Enter your section (e.g., A, B, Diamond, etc.)">
                    </div>
                    <button type="submit" class="save-button">Save Changes</button>
                </form>
            </div>
        </div>
    </div>

    <div class="toast-overlay"></div>
    <div id="toast" class="toast"></div>
    
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

    <!-- Image Crop Modal -->
    <div class="crop-modal-overlay" id="crop-modal">
        <div class="crop-modal">
            <div class="crop-modal-header">
                <h3>Crop Profile Image</h3>
                <button class="crop-modal-close" id="crop-modal-close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="crop-container">
                <img id="crop-image" src="" alt="Image to crop">
            </div>
            <div class="crop-controls">
                <button class="crop-btn crop-btn-cancel" id="crop-cancel">Cancel</button>
                <button class="crop-btn crop-btn-done" id="crop-done">Done</button>
            </div>
        </div>
    </div>

    <!-- Upload Loader Overlay -->
    <div class="upload-loader-overlay" id="upload-loader">
        <div class="loader"></div>
        <div class="loader-text">Uploading profile image...</div>
    </div>

    <script src="../../script.js"></script>
    <script src="../shared/notification-badge.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js"></script>
    <script>
    // Mobile menu functionality
    document.addEventListener('DOMContentLoaded', function() {
        const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
        const sidebar = document.querySelector('.sidebar');
        const profileTrigger = document.getElementById('profile-dropdown-trigger');
        const dropdownMenu = document.querySelector('.nav-dropdown-menu');
        
        // Initialize mobile menu
        if (mobileMenuBtn && sidebar) {
            // Make sure sidebar is hidden by default on mobile
            if (window.innerWidth <= 768) {
                sidebar.style.transform = 'translateX(-100%)';
            }
            
            // Simple toggle function for mobile menu
            function toggleMobileMenu() {
                if (sidebar.style.transform === 'translateX(0%)') {
                    sidebar.style.transform = 'translateX(-100%)';
                    document.body.style.overflow = '';
                } else {
                    sidebar.style.transform = 'translateX(0%)';
                    document.body.style.overflow = 'hidden';
                }
            }
            
            // Add click event to mobile menu button
            mobileMenuBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                toggleMobileMenu();
            });
            
            // Close menu when clicking outside
            document.addEventListener('click', function(e) {
                if (window.innerWidth <= 768 && 
                    sidebar.style.transform === 'translateX(0%)' && 
                    !sidebar.contains(e.target) && 
                    !mobileMenuBtn.contains(e.target)) {
                    toggleMobileMenu();
                }
            });
            
            // Handle profile dropdown if it exists
            if (profileTrigger && dropdownMenu) {
                // Close dropdown initially
                dropdownMenu.style.display = 'none';
                
                // Toggle dropdown on click
                const toggleDropdown = (e) => {
                    if (e) {
                        e.preventDefault();
                        e.stopPropagation();
                    }
                    
                    const isVisible = dropdownMenu.style.display === 'block';
                    dropdownMenu.style.display = isVisible ? 'none' : 'block';
                    
                    // Toggle active class for arrow rotation
                    const parentItem = profileTrigger.closest('.nav-item-with-dropdown');
                    parentItem.classList.toggle('active', !isVisible);
                    
                    // For mobile, ensure the dropdown is visible in the viewport
                    if (!isVisible && window.innerWidth <= 768) {
                        // Ensure sidebar is open on mobile when clicking dropdown
                        if (!sidebar.classList.contains('active')) {
                            toggleSidebar();
                        }
                        // Small delay to ensure the sidebar is open before scrolling
                        setTimeout(() => {
                            dropdownMenu.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                        }, 50);
                    }
                };
                
                // Handle both touch and click events for better mobile support
                profileTrigger.addEventListener('click', toggleDropdown);
                
                // Close dropdown when clicking outside on both desktop and mobile
                const handleOutsideClick = (e) => {
                    // Don't close if clicking on profile trigger or dropdown menu
                    if (profileTrigger.contains(e.target) || dropdownMenu.contains(e.target)) {
                        return;
                    }
                    
                    // For mobile, check if clicking outside sidebar
                    if (window.innerWidth <= 768) {
                        if (!sidebar.contains(e.target) && !mobileMenuBtn.contains(e.target)) {
                            dropdownMenu.style.display = 'none';
                            profileTrigger.closest('.nav-item-with-dropdown').classList.remove('active');
                        }
                    } else {
                        // For desktop, just close the dropdown
                        dropdownMenu.style.display = 'none';
                        profileTrigger.closest('.nav-item-with-dropdown').classList.remove('active');
                    }
                };
                
                // Use both click and touch events for better mobile support
                document.addEventListener('click', handleOutsideClick);
                document.addEventListener('touchend', handleOutsideClick);
                
                // Close on escape key
                document.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape' && dropdownMenu.style.display === 'block') {
                        dropdownMenu.style.display = 'none';
                        profileTrigger.closest('.nav-item-with-dropdown').classList.remove('active');
                    }
                });
            }
            
            // Close menu when clicking outside on mobile
            document.addEventListener('click', function(e) {
                if (window.innerWidth <= 768 && 
                    sidebar.classList.contains('active') && 
                    !sidebar.contains(e.target) && 
                    !mobileMenuBtn.contains(e.target) &&
                    !(dropdownMenu && dropdownMenu.contains(e.target))) {
                    sidebar.classList.remove('active');
                    document.body.style.overflow = '';
                    
                    // Also close dropdown if open
                    if (dropdownMenu && dropdownMenu.style.display === 'block') {
                        dropdownMenu.style.display = 'none';
                        if (profileTrigger) {
                            profileTrigger.closest('.nav-item-with-dropdown').classList.remove('active');
                        }
                    }
                }
            });
            
            // Close menu when clicking a nav link on mobile
            const navLinks = document.querySelectorAll('.nav-link:not(#profile-dropdown-trigger)');
            navLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    if (window.innerWidth <= 768) {
                        // Don't close if this is the profile dropdown trigger
                        if (this.id === 'profile-dropdown-trigger') {
                            return;
                        }
                        sidebar.classList.remove('active');
                        document.body.style.overflow = '';
                    }
                });
            });
            
            // Handle window resize to ensure proper behavior
            window.addEventListener('resize', function() {
                // If resizing to mobile view, ensure the dropdown is closed
                if (window.innerWidth <= 768) {
                    if (dropdownMenu) {
                        dropdownMenu.style.display = 'none';
                        if (profileTrigger) {
                            profileTrigger.closest('.nav-item-with-dropdown').classList.remove('active');
                        }
                    }
                }
            });
        }
    });
    </script>
    <script>
    // Define logout modal functions in global scope
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
            modal.classList.remove('show');
            confirmation.classList.remove('show');
            confirmation.classList.add('hide');
        }
    }

    function confirmLogout() {
        // Play click sound
        playClickSound();
        
        // Redirect to logout endpoint
        window.location.href = '../../onboarding/logout.php';
    }
    
    // Function to show/hide email tooltip
    function showEmailTooltip(element) {
        element.classList.toggle('show-tooltip');
        
        // Close tooltip when clicking outside
        const closeTooltip = (e) => {
            if (!element.contains(e.target)) {
                element.classList.remove('show-tooltip');
                document.removeEventListener('click', closeTooltip);
            }
        };
        
        // Add event listener to close on outside click
        if (element.classList.contains('show-tooltip')) {
            setTimeout(() => {
                document.addEventListener('click', closeTooltip);
            }, 0);
        } else {
            document.removeEventListener('click', closeTooltip);
        }
    }
    
    // Inline JavaScript to handle profile form
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.querySelector('.settings-form');
        
        if (form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData();
                formData.append('action', 'update_profile');
                formData.append('username', document.querySelector('input[name="username"]').value);
                // Get email from the readonly input
                formData.append('email', document.querySelector('input[type="email"][readonly]').value);
                formData.append('about_me', document.querySelector('textarea[name="about_me"]').value);
                formData.append('section', document.querySelector('input[name="section"]').value);
                
                fetch('profile.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Show success message using the existing toast system
                        if (typeof showToast === 'function') {
                            showToast(data.message);
                        }
                        
                        // Update the About Me text immediately without reload
                        const aboutMeElement = document.querySelector('.about-me-text');
                        if (aboutMeElement && data.about_me !== undefined) {
                            const newText = (data.about_me && data.about_me.trim() !== '') ? data.about_me : 'Tell us something about yourself...';
                            aboutMeElement.textContent = newText;
                        }
                        
                        // Update the Section display immediately without reload
                        const sectionElement = document.querySelector('.section-info .info-value');
                        if (sectionElement && data.section !== undefined) {
                            const sectionText = (data.section && data.section.trim() !== '') ? data.section : 'Not specified';
                            sectionElement.textContent = sectionText;
                        }
                        
                        // Update username in header if it exists (show only first name)
                        const usernameElements = document.querySelectorAll('.username');
                        if (data.username) {
                            usernameElements.forEach(el => {
                                el.textContent = data.username.split(' ')[0];
                            });
                        }
                        
                        // Reload the page after a short delay to ensure all updates are reflected
                        setTimeout(() => {
                            window.location.reload();
                        }, 500);
                        
                    } else {
                        if (typeof showToast === 'function') {
                            showToast('Error: ' + data.message);
                        } else {
                            alert('Error: ' + data.message);
                        }
                    }
                })
                .catch(error => {
                    alert('Error updating profile');
                });
            });
        }
        
        // Profile image upload functionality with cropping
        const changeAvatarBtn = document.getElementById('change-avatar-btn');
        const profileImageInput = document.getElementById('profile-image-input');
        const profileAvatarImg = document.getElementById('profile-avatar-img');
        const cropModal = document.getElementById('crop-modal');
        const cropImage = document.getElementById('crop-image');
        const cropDone = document.getElementById('crop-done');
        const cropCancel = document.getElementById('crop-cancel');
        const cropModalClose = document.getElementById('crop-modal-close');
        
        let cropper = null;
        let selectedFile = null;
        
        if (changeAvatarBtn && profileImageInput) {
            // Trigger file input when button is clicked
            changeAvatarBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                profileImageInput.click();
            });
            
            // Handle file selection
            profileImageInput.addEventListener('change', function(e) {
                const file = e.target.files[0];
                
                if (!file) {
                    return;
                }
                
                // Validate file type
                const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
                if (!allowedTypes.includes(file.type)) {
                    if (typeof showToast === 'function') {
                        showToast('Only image files (JPG, PNG, GIF, WEBP) are allowed');
                    } else {
                        alert('Only image files (JPG, PNG, GIF, WEBP) are allowed');
                    }
                    profileImageInput.value = '';
                    return;
                }
                
                // Validate file size (5MB)
                const maxSize = 5 * 1024 * 1024; // 5MB
                if (file.size > maxSize) {
                    if (typeof showToast === 'function') {
                        showToast('File size must be less than 5MB');
                    } else {
                        alert('File size must be less than 5MB');
                    }
                    profileImageInput.value = '';
                    return;
                }
                
                // Store the selected file
                selectedFile = file;
                
                // Show crop modal
                const reader = new FileReader();
                reader.onload = function(event) {
                    cropImage.src = event.target.result;
                    cropModal.classList.add('show');
                    
                    // Initialize Cropper.js
                    if (cropper) {
                        cropper.destroy();
                    }
                    
                    cropper = new Cropper(cropImage, {
                        aspectRatio: 1, // 1:1 square ratio
                        viewMode: 1,
                        dragMode: 'move',
                        autoCropArea: 1,
                        restore: false,
                        guides: true,
                        center: true,
                        highlight: false,
                        cropBoxMovable: true,
                        cropBoxResizable: true,
                        toggleDragModeOnDblclick: false,
                    });
                };
                reader.readAsDataURL(file);
            });
            
            // Handle crop done button
            if (cropDone) {
                cropDone.addEventListener('click', function() {
                    if (!cropper) return;
                    
                    // Get cropped canvas
                    const canvas = cropper.getCroppedCanvas({
                        width: 500,
                        height: 500,
                        imageSmoothingEnabled: true,
                        imageSmoothingQuality: 'high',
                    });
                    
                    // Convert canvas to blob
                    canvas.toBlob(function(blob) {
                        // Create a new file from the blob
                        const croppedFile = new File([blob], selectedFile.name, {
                            type: selectedFile.type,
                            lastModified: Date.now(),
                        });
                        
                        // Close crop modal
                        cropModal.classList.remove('show');
                        if (cropper) {
                            cropper.destroy();
                            cropper = null;
                        }
                        
                        // Upload the cropped file
                        uploadProfileImage(croppedFile);
                        
                        // Reset file input
                        profileImageInput.value = '';
                    }, selectedFile.type);
                });
            }
            
            // Handle crop cancel
            const closeCropModal = function() {
                cropModal.classList.remove('show');
                if (cropper) {
                    cropper.destroy();
                    cropper = null;
                }
                profileImageInput.value = '';
                selectedFile = null;
            };
            
            if (cropCancel) {
                cropCancel.addEventListener('click', closeCropModal);
            }
            
            if (cropModalClose) {
                cropModalClose.addEventListener('click', closeCropModal);
            }
            
            // Upload function
            function uploadProfileImage(file) {
                const formData = new FormData();
                formData.append('action', 'upload_profile_image');
                formData.append('profile_image', file);
                
                // Show loading overlay
                const uploadLoader = document.getElementById('upload-loader');
                if (uploadLoader) {
                    uploadLoader.classList.add('show');
                }
                
                fetch('profile.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    // Hide loading overlay
                    if (uploadLoader) {
                        uploadLoader.classList.remove('show');
                    }
                    
                    if (data.success) {
                        // Show success message
                        if (typeof showToast === 'function') {
                            showToast(data.message);
                        }
                        
                        // Update all profile images on the page
                        const imagePath = '../../' + data.image_path;
                        
                        // Update large profile avatar
                        if (profileAvatarImg) {
                            profileAvatarImg.src = imagePath;
                        }
                        
                        // Update header profile images
                        const headerProfileImg = document.querySelector('.profile-img');
                        if (headerProfileImg) {
                            headerProfileImg.src = imagePath;
                        }
                        
                        const dropdownAvatar = document.querySelector('.profile-dropdown-avatar');
                        if (dropdownAvatar) {
                            dropdownAvatar.src = imagePath;
                        }
                        
                    } else {
                        if (typeof showToast === 'function') {
                            showToast('Error: ' + data.message);
                        } else {
                            alert('Error: ' + data.message);
                        }
                    }
                })
                .catch(error => {
                    // Hide loading overlay
                    const uploadLoader = document.getElementById('upload-loader');
                    if (uploadLoader) {
                        uploadLoader.classList.remove('show');
                    }
                    
                    console.error('Upload error:', error);
                    if (typeof showToast === 'function') {
                        showToast('Error uploading profile image');
                    } else {
                        alert('Error uploading profile image');
                    }
                });
            }
        }
    });
    </script>
    <script>
        // Pass user data to JavaScript
        window.userData = {
            id: <?php echo $user_id; ?>,
            username: '<?php echo htmlspecialchars($user['username']); ?>',
            email: '<?php echo htmlspecialchars($user['email']); ?>',
            gradeLevel: '<?php echo htmlspecialchars($user['grade_level']); ?>',
            section: '<?php echo htmlspecialchars($user['section'] ?? ''); ?>',
            aboutMe: '<?php echo htmlspecialchars($user['about_me'] ?? ''); ?>',
            favorites: <?php echo json_encode($favorites); ?>
        };

        // Logout functionality is now in global scope above

        // Game stats filter functionality
        document.addEventListener('DOMContentLoaded', function() {
            // No need for game stats sort functionality as there's only one option
        });
    </script>
</body>
</html>
