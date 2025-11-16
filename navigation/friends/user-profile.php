<?php
require_once '../../onboarding/config.php';
require_once '../../includes/greeting.php';
require_once '../../includes/gwa_updater.php';

// Check if user is logged in, redirect to login if not
if (!isLoggedIn()) {
    header('Location: ../../onboarding/login.php');
    exit();
}

// Get current user information
$current_user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT username, email, grade_level, section FROM users WHERE id = ?");
$stmt->execute([$current_user_id]);
$current_user = $stmt->fetch();

if (!$current_user) {
    session_destroy();
    header('Location: ../../onboarding/login.php');
    exit();
}

// Get the user ID from URL parameter
$viewed_user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$viewed_user_id) {
    header('Location: friends.php');
    exit();
}

// Get the viewed user's information
$stmt = $pdo->prepare("SELECT id, username, email, grade_level, section, about_me, created_at FROM users WHERE id = ?");
$stmt->execute([$viewed_user_id]);
$viewed_user = $stmt->fetch();

// Update all user GWAs first
updateAllUserGWAs($pdo, $viewed_user_id);

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
$stmt->execute([$viewed_user_id]);
$game_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

// For backward compatibility, ensure gwa_score is set (fallback to calculation if not in user_gwa)
foreach ($game_stats as &$stat) {
    if (!isset($stat['gwa_score']) || $stat['gwa_score'] === null) {
        $stat['gwa_score'] = $stat['player_level'] * 1.5;
    }
}
unset($stat); // Break the reference

// Calculate player stats
$player_stats = [
    'total_level' => 1,
    'total_experience' => 0,
    'total_monsters_defeated' => 0,
    'total_play_time_seconds' => 0
];

// Calculate totals from game stats
if (!empty($game_stats)) {
    $player_stats['total_level'] = array_sum(array_column($game_stats, 'player_level'));
    $player_stats['total_experience'] = array_sum(array_column($game_stats, 'total_experience_earned'));
    $player_stats['total_monsters_defeated'] = array_sum(array_column($game_stats, 'total_monsters_defeated'));
    $player_stats['total_play_time_seconds'] = array_sum(array_column($game_stats, 'total_play_time_seconds'));
}

// Calculate overall GWA (average of all game GWAs)
$overall_gwa = !empty($game_stats) ? 
    array_sum(array_column($game_stats, 'gwa_score')) / count($game_stats) : 
    0;

if (!$viewed_user) {
    header('Location: friends.php');
    exit();
}

// Check if current user has already sent a friend request to this user
$stmt = $pdo->prepare("SELECT id, status FROM friend_requests WHERE requester_id = ? AND receiver_id = ? AND status = 'pending'");
$stmt->execute([$current_user_id, $viewed_user_id]);
$friend_request = $stmt->fetch();

// Check if the viewed user has sent a friend request to the current user
$stmt = $pdo->prepare("SELECT id, status FROM friend_requests WHERE requester_id = ? AND receiver_id = ? AND status = 'pending'");
$stmt->execute([$viewed_user_id, $current_user_id]);
$received_friend_request = $stmt->fetch();

// Check if users are already friends
$stmt = $pdo->prepare("
    SELECT id FROM friends 
    WHERE (user1_id = ? AND user2_id = ?) OR (user1_id = ? AND user2_id = ?)
");
$stmt->execute([$current_user_id, $viewed_user_id, $viewed_user_id, $current_user_id]);
$are_friends = $stmt->fetch();

// Get notification count for current user
$stmt = $pdo->prepare("
    SELECT COUNT(*) as count
    FROM friend_requests 
    WHERE receiver_id = ? AND status = 'pending'
");
$stmt->execute([$current_user_id]);
$notification_result = $stmt->fetch();
$notification_count = $notification_result['count'];

// Get viewed user's rank from leaderboard
$rank_stmt = $pdo->prepare("
    SELECT position FROM (
        SELECT 
            u.id as user_id,
            ROW_NUMBER() OVER (ORDER BY COALESCE(gp.player_level, 1) DESC, 
                              COALESCE(gp.total_monsters_defeated, 0) DESC,
                              (SELECT AVG(score) FROM game_scores WHERE user_id = u.id) DESC) as position
        FROM users u
        LEFT JOIN game_progress gp ON u.id = gp.user_id AND gp.game_type = 'vocabworld'
    ) as ranked_users
    WHERE user_id = ?
");
$rank_stmt->execute([$viewed_user_id]);
$user_rank = $rank_stmt->fetchColumn();

// Get viewed user's game statistics
$stmt = $pdo->prepare("SELECT 
    game_type,
    AVG(score) as gwa_score,
    COUNT(*) as play_count,
    MAX(score) as best_score,
    SUM(score) as total_score
    FROM game_scores 
    WHERE user_id = ?
    GROUP BY game_type");
$stmt->execute([$viewed_user_id]);
$game_stats = $stmt->fetchAll();

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
$stmt->execute([$viewed_user_id]);
$player_stats = $stmt->fetch();

// Get character selection data for JavaScript
// Debug: Check if user has a character selection
$character_stmt = $pdo->prepare("
    SELECT character_image_path, selected_character 
    FROM character_selections 
    WHERE user_id = ? AND game_type = 'vocabworld' 
    LIMIT 1
");
$character_stmt->execute([$viewed_user_id]);
$character_result = $character_stmt->fetch();

// Debug output
// echo "<pre>Character Result: "; print_r($character_result); echo "</pre>";

// Set default values for JavaScript
$character_image = '../../MainGame/vocabworld/assets/characters/boy_char/character_ethan.png';
$character_name = 'Ethan';

if ($character_result) {
    // Debug: Check what's in the result
    // echo "<pre>Character Result: "; print_r($character_result); echo "</pre>";
    // echo "<p>Image Path: " . ($character_result['character_image_path'] ?? 'Not set') . "</p>";
    if (!empty($character_result['character_image_path'])) {
        $character_image = $character_result['character_image_path'];
    }
    if (!empty($character_result['selected_character'])) {
        $character_name = $character_result['selected_character'];
    } else if (preg_match('/character_([^.]+)\./', $character_image, $matches)) {
        $character_name = ucfirst($matches[1]);
    }
}

// Initialize essence and shards with default values
$essence = 0;
$shards = 0;

// Get viewed user's favorites
$stmt = $pdo->prepare("SELECT game_type FROM user_favorites WHERE user_id = ?");
$stmt->execute([$viewed_user_id]);
$favorites = $stmt->fetchAll();

// Get viewed user's friends count
$stmt = $pdo->prepare("
    SELECT COUNT(*) as friends_count 
    FROM friends 
    WHERE user1_id = ? OR user2_id = ?
");
$stmt->execute([$viewed_user_id, $viewed_user_id]);
$friends_count = $stmt->fetch()['friends_count'];

// Handle friend request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'send_request') {
        if (!$friend_request) {
            // First, check if there's already a pending request in either direction
            $stmt = $pdo->prepare("SELECT id, status FROM friend_requests WHERE (requester_id = ? AND receiver_id = ?) OR (requester_id = ? AND receiver_id = ?) LIMIT 1");
            $stmt->execute([$current_user_id, $viewed_user_id, $viewed_user_id, $current_user_id]);
            $existing_request = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existing_request) {
                // If there's already a request, just update the status to pending if needed
                if ($existing_request['status'] !== 'pending') {
                    $stmt = $pdo->prepare("UPDATE friend_requests SET status = 'pending', created_at = NOW() WHERE id = ?");
                    $stmt->execute([$existing_request['id']]);
                }
                // No need to create a notification since one already exists
            } else {
                // Only create a new request if one doesn't exist
                $stmt = $pdo->prepare("INSERT INTO friend_requests (requester_id, receiver_id, status, created_at) VALUES (?, ?, 'pending', NOW())");
                $stmt->execute([$current_user_id, $viewed_user_id]);
                
                // Create notification for the receiver
                $stmt = $pdo->prepare("INSERT INTO notifications (user_id, type, message, data, created_at) VALUES (?, 'friend_request', ?, ?, NOW())");
                $message = $current_user['username'] . ' sent you a friend request';
                $data = json_encode(['requester_id' => $current_user_id, 'requester_name' => $current_user['username']]);
                $stmt->execute([$viewed_user_id, $message, $data]);
            }
            
            // Refresh friend request status
            $stmt = $pdo->prepare("SELECT id, status FROM friend_requests WHERE (requester_id = ? AND receiver_id = ?) OR (requester_id = ? AND receiver_id = ?) LIMIT 1");
            $stmt->execute([$current_user_id, $viewed_user_id, $viewed_user_id, $current_user_id]);
            $friend_request = $stmt->fetch();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../../assets/menu/ww_logo_main.webp">
    <title><?php echo htmlspecialchars($viewed_user['username']); ?> - Word Weavers</title>
    <link rel="stylesheet" href="../../navigation/shared/navigation.css">
    <link rel="stylesheet" href="../../notif/toast.css">
    <link rel="stylesheet" href="../../styles.css">
    <link rel="stylesheet" href="user-profile.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>
<body>
    <!-- Mobile Menu Button -->
    <button class="mobile-menu-btn" aria-label="Open menu" aria-expanded="false" aria-controls="sidebar">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-logo">
            <img src="../../assets/menu/Word-Weavers.png" alt="Word Weavers" class="sidebar-logo-img">
        </div>
        <nav class="sidebar-nav">
            <a href="../../menu.php" class="nav-link">
                <i class="fas fa-house"></i>
                <span>Menu</span>
            </a>
            <a href="../../navigation/favorites/favorites.php" class="nav-link">
                <i class="fas fa-star"></i>
                <span>Favorites</span>
            </a>
            <a href="friends.php" class="nav-link active">
                <i class="fas fa-users"></i>
                <span>Friends</span>
            </a>
            <a href="../../navigation/profile/profile.php" class="nav-link">
                <i class="fas fa-user"></i>
                <span>Profile</span>
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
                            <a href="../../navigation/profile/profile.php" class="profile-dropdown-item">
                                <i class="fas fa-user"></i>
                                <span>View Profile</span>
                            </a>
                            <a href="../../navigation/favorites/favorites.php" class="profile-dropdown-item">
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

    <!-- Notification Dropdown -->
    <div class="notification-dropdown" id="notificationDropdown">
        <div class="notification-header">
            <h3>Notifications</h3>
            <button class="mark-all-read" onclick="markAllAsRead()">Mark all as read</button>
        </div>
        <div class="notification-list" id="notificationList">
            <!-- Notifications will be loaded here -->
        </div>
        <div class="notification-footer">
            <a href="#" class="view-all-notifications">View all notifications</a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="user-profile-container">
            <!-- User Profile Header -->
            <div class="user-profile-header">
                <div class="profile-avatar">
                    <img src="../../assets/menu/defaultuser.png" alt="<?php echo htmlspecialchars($viewed_user['username']); ?>" class="large-avatar">
                </div>
                <div class="profile-info">
                    <div class="profile-name-section">
                        <h1><?php echo htmlspecialchars($viewed_user['username']); ?></h1>
                        <form method="POST" class="friend-request-form">
                            <input type="hidden" name="action" value="send_request">
                             <?php if ($are_friends): ?>
                                 <button type="button" class="friend-request-btn remove-friend" onclick="removeFriend(<?php echo $viewed_user_id; ?>, '<?php echo htmlspecialchars($viewed_user['username']); ?>', this)">
                                     <i class="fas fa-user-minus"></i>
                                     Remove Friend
                                 </button>
                             <?php elseif ($received_friend_request): ?>
                                 <button type="button" class="friend-request-btn pending-request" disabled>
                                     <i class="fas fa-clock"></i>
                                     Request Sent to You
                                 </button>
                             <?php elseif ($friend_request): ?>
                                 <button type="button" class="friend-request-btn cancel-request" onclick="cancelFriendRequest(<?php echo $viewed_user_id; ?>, '<?php echo htmlspecialchars($viewed_user['username']); ?>', this)">
                                     <i class="fas fa-times"></i>
                                     Cancel Request
                                 </button>
                             <?php else: ?>
                                 <button type="submit" class="friend-request-btn">
                                     <i class="fas fa-user-plus"></i>
                                     Add Friend
                                 </button>
                             <?php endif; ?>
                        </form>
                    </div>
                    <p class="player-email"><?php echo htmlspecialchars($viewed_user['email']); ?></p>
                    <p class="friends-count"><?php echo $friends_count; ?> Friends</p>
                    <?php if (!empty($viewed_user['about_me'])): ?>
                        <p class="about-me"><?php echo htmlspecialchars($viewed_user['about_me']); ?></p>
                    <?php endif; ?>
                    <div class="badge-container">
                        <?php 
                        $is_jaderby = (strtolower($viewed_user['username']) === 'jaderby garcia peñaranda');
                        $is_admin = ($viewed_user['grade_level'] === 'Admin' || $is_jaderby);
                        $is_teacher = ($viewed_user['grade_level'] === 'Teacher');
                        
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
                <div class="grade-section-content">
                    <div class="grade-info">
                        <span class="info-label">Grade:</span>
                        <span class="info-value"><?php echo htmlspecialchars($viewed_user['grade_level']); ?></span>
                    </div>
                    <div class="section-info">
                        <span class="info-label">Section:</span>
                        <span class="info-value"><?php echo !empty($viewed_user['section']) ? htmlspecialchars($viewed_user['section']) : 'Not specified'; ?></span>
                    </div>
                </div>
            </div>

            <!-- Game Stats Section -->
            <div class="gamestats-section">
                <div class="section-header">
                    <div class="header-title">
                        <i class="fas fa-gamepad"></i>
                        <h3>Game Stats</h3>
                    </div>
                    <div class="sort-dropdown">
                        <select id="game-stats-sort">
                            <option value="vocabworld" selected>Vocabworld</option>
                        </select>
                    </div>
                </div>
                
                <div class="gamestats-layout">
                    <!-- Character Preview -->
                    <div class="character-preview-container">
                        <div class="character-preview-content">
                            <div class="character-sprite-container">
                                <?php 
                                // Determine the correct character image based on the character name
                                $character_images = [
                                    'emma' => '../../MainGame/vocabworld/assets/characters/girl_char/character_emma.png',
                                    'ethan' => '../../MainGame/vocabworld/assets/characters/boy_char/character_ethan.png',
                                    'amber' => '../../MainGame/vocabworld/assets/characters/amber_char/amber.png',
                                    'girl' => '../../MainGame/vocabworld/assets/characters/girl_char/character_emma.png',
                                    'boy' => '../../MainGame/vocabworld/assets/characters/boy_char/character_ethan.png'
                                ];

                                // Convert character name to lowercase for case-insensitive matching
                                $char_key = strtolower($character_name);
                                
                                // If we have a direct match, use it
                                if (isset($character_images[$char_key])) {
                                    $character_image = $character_images[$char_key];
                                } 
                                // Otherwise, check if the path contains a character name
                                else {
                                    foreach ($character_images as $char => $path) {
                                        if (stripos($character_image, $char) !== false) {
                                            $character_image = $path;
                                            break;
                                        }
                                    }
                                }
                                
                                // Final fallback to Ethan if still not found
                                if (!isset($character_images[strtolower($character_name)]) && !file_exists($_SERVER['DOCUMENT_ROOT'] . '/GameDev-G1/' . ltrim($character_image, '/'))) {
                                    $character_image = $character_images['ethan'];
                                }
                                
                                // Output the image with the correct path
                                echo '<img src="' . htmlspecialchars($character_image) . '" 
                                     alt="' . htmlspecialchars($character_name) . '" 
                                     class="character-sprite"
                                     data-character="' . strtolower($character_name) . '">';
                                ?>
                                <div class="character-glow"></div>
                            </div>
                            <div class="character-info">
                                <div class="character-name"><?php echo htmlspecialchars($character_name); ?></div>
                                <div class="character-level">Level <?php echo number_format($player_stats ? $player_stats['total_level'] : 1); ?></div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Player Stats -->
                    <div class="stats-container">
                        <div class="section-subtitle">Stats</div>
                        <div class="stats-grid">
                            <div class="stat-card">
                                <?php if (isset($user_rank)): ?>
                                    <div class="rank-badge <?php echo $user_rank <= 3 ? 'rank-' . $user_rank : 'rank-other'; ?>">
                                        <?php if ($user_rank <= 3): ?>
                                            <i class="fas fa-trophy"></i>
                                        <?php endif; ?>
                                        #<?php echo $user_rank; ?>
                                    </div>
                                <?php endif; ?>
                                <div class="stat-icon">
                                    <img src="../../MainGame/vocabworld/assets/stats/level.png" alt="Level" class="stat-icon-img">
                                </div>
                                <div class="stat-info">
                                    <span class="stat-label">Level</span>
                                    <span class="stat-value"><?php echo number_format($player_stats ? $player_stats['total_level'] : 1); ?></span>
                                </div>
                            </div>
                            <div class="stat-card">
                                <?php if (isset($user_rank)): ?>
                                    <div class="rank-badge <?php echo $user_rank <= 3 ? 'rank-' . $user_rank : 'rank-other'; ?>">
                                        <?php if ($user_rank <= 3): ?>
                                            <i class="fas fa-trophy"></i>
                                        <?php endif; ?>
                                        #<?php echo $user_rank; ?>
                                    </div>
                                <?php endif; ?>
                                <div class="stat-icon">
                                    <img src="../../MainGame/vocabworld/assets/stats/total_xp.png" alt="Experience" class="stat-icon-img">
                                </div>
                                <div class="stat-info">
                                    <span class="stat-label">Experience</span>
                                    <span class="stat-value"><?php echo number_format($player_stats ? $player_stats['total_experience'] : 0); ?></span>
                                </div>
                            </div>
                            <div class="stat-card">
                                <?php if (isset($user_rank)): ?>
                                    <div class="rank-badge <?php echo $user_rank <= 3 ? 'rank-' . $user_rank : 'rank-other'; ?>">
                                        <?php if ($user_rank <= 3): ?>
                                            <i class="fas fa-trophy"></i>
                                        <?php endif; ?>
                                        #<?php echo $user_rank; ?>
                                    </div>
                                <?php endif; ?>
                                <div class="stat-icon">
                                    <img src="../../MainGame/vocabworld/assets/stats/sword1.png" alt="Monsters Defeated" class="stat-icon-img">
                                </div>
                                <div class="stat-info">
                                    <span class="stat-label">Monsters Defeated</span>
                                    <span class="stat-value"><?php echo number_format($player_stats ? $player_stats['total_monsters_defeated'] : 0); ?></span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="section-subtitle">Resources</div>
                        
                        <div class="stats-grid stats-grid-resources">
                            <div class="stat-card">
                                <div class="stat-icon">
                                    <img src="../../MainGame/vocabworld/assets/currency/essence.png" alt="Essence" class="stat-icon-img">
                                </div>
                                <div class="stat-info">
                                    <span class="stat-label">Essence</span>
                                    <span class="stat-value"><?php echo number_format($essence); ?></span>
                                </div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-icon">
                                    <img src="../../MainGame/vocabworld/assets/currency/shard1.png" alt="Shards" class="stat-icon-img">
                                </div>
                                <div class="stat-info">
                                    <span class="stat-label">Shards</span>
                                    <span class="stat-value"><?php echo number_format($shards); ?></span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="section-subtitle">Ratings</div>
                        
                        <div class="stats-grid">
                            <div class="stat-card gwa-stat-card">
                                <div class="stat-icon">
                                    <img src="../../MainGame/vocabworld/assets/stats/gwa.png" alt="GWA" class="stat-icon-img">
                                </div>
                                <div class="stat-info">
                                    <span class="stat-label">GWA</span>
                                    <span class="stat-value gwa-value"><?php echo number_format($overall_gwa, 2); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <?php if (!empty($game_stats)): ?>
                    <div class="game-cards-grid">
                        <?php foreach ($game_stats as $game): ?>
                            <?php
                            $game_name = '';
                            $game_icon = 'fa-gamepad';
                            
                            switch ($game['game_type']) {
                                case 'vocabworld':
                                    $game_name = 'Vocab World';
                                    $game_icon = 'fa-book';
                                    break;
                                case 'grammar-heroes':
                                    $game_name = 'Grammar Heroes';
                                    $game_icon = 'fa-spell-check';
                                    break;
                                default:
                                    $game_name = ucwords(str_replace('-', ' ', $game['game_type']));
                            }
                            ?>
                            <div class="game-card">
                                <div class="game-card-header">
                                    <i class="fas <?php echo $game_icon; ?>"></i>
                                    <h4><?php echo $game_name; ?></h4>
                                </div>
                                <div class="game-card-stats">
                                    <div class="game-stat">
                                        <span class="stat-label">GWA:</span>
                                        <span class="stat-value"><?php echo number_format($game['gwa_score'], 1); ?></span>
                                    </div>
                                    <div class="game-stat">
                                        <span class="stat-label">Best:</span>
                                        <span class="stat-value"><?php echo $game['best_score']; ?></span>
                                    </div>
                                    <div class="game-stat">
                                        <span class="stat-label">Plays:</span>
                                        <span class="stat-value"><?php echo $game['play_count']; ?></span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

<!-- User Favorites -->
            <div class="user-favorites-section">
                <h2><i class="fas fa-star"></i> Favorite Games</h2>
                <?php if (!empty($favorites)): ?>
                    <div class="favorites-grid">
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
                                <div class="game-logo-container">
                                    <img src="<?php echo $game_logo; ?>" alt="<?php echo $game_name; ?>" class="game-logo">
                                </div>
                                <div class="favorite-info">
                                    <h4><?php echo $game_name; ?></h4>
                                    <i class="fas fa-heart favorite-icon"></i>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="no-data">
                        <i class="fas fa-star"></i>
                        <p>No favorite games yet</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Member Since Section -->
            <div class="member-since-section">
                <div class="member-since-content">
                    <i class="fas fa-calendar-alt"></i>
                    <span>Member since <?php echo date('F j, Y', strtotime($viewed_user['created_at'])); ?></span>
                </div>
            </div>

            <!-- Back Button -->
            <div class="back-button-container">
                <a href="friends.php" class="back-button">
                    <i class="fas fa-arrow-left"></i>
                    Back
                </a>
            </div>
        </div>
    </div>

    <div class="toast-overlay"></div>
    <div id="toast" class="toast"></div>
    
    <!-- Logout Confirmation Modal -->
    <div class="toast-overlay" id="logoutModal">
        <div class="toast" id="logoutConfirmation">
            <h3 style="margin-bottom: 1rem; color:rgb(255, 255, 255);">Logout Confirmation</h3>
            <p style="margin-bottom: 1.5rem; color: rgba(255, 255, 255, 0.8);">Are you sure you want to logout?</p>
            <div style="display: flex; gap: 1rem; justify-content: center;">
                <button onclick="confirmLogout()" style="background: #ff6b6b; color: white; border: none; padding: 0.5rem 1rem; border-radius: 8px; cursor: pointer; font-family: 'Press Start 2P', cursive; font-size: 0.8rem;">Yes, Logout</button>
                <button onclick="hideLogoutModal()" style="background: rgba(255, 255, 255, 0.2); color: white; border: 1px solid rgba(255, 255, 255, 0.3); padding: 0.5rem 1rem; border-radius: 8px; cursor: pointer; font-family: 'Press Start 2P', cursive; font-size: 0.8rem;">Cancel</button>
            </div>
        </div>
    </div>

    <!-- Remove Friend Confirmation Modal -->
    <div class="modal-overlay" id="removeFriendModal">
        <div class="modal-content" id="removeFriendConfirmation">
            <div class="modal-header">
                <div class="confirmation-icon">
                    <i class="fas fa-user-minus"></i>
                </div>
                <h3>Remove Friend</h3>
            </div>
            <div class="modal-body">
                <p id="removeFriendMessage">Are you sure you want to remove this friend?</p>
            </div>
            <div class="modal-footer">
                <button id="confirmRemoveBtn" class="modal-btn remove-btn">
                    <i class="fas fa-check"></i>
                    Remove
                </button>
                <button id="cancelRemoveBtn" class="modal-btn cancel-btn">
                    <i class="fas fa-times"></i>
                    No
                </button>
            </div>
        </div>
    </div>

    <script src="../../script.js"></script>
    <script src="../../navigation/shared/profile-dropdown.js"></script>
    <script src="user-profile.js"></script>
    <script>
        // Mobile menu functionality
        document.addEventListener('DOMContentLoaded', function() {
            const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
            const sidebar = document.querySelector('.sidebar');

            if (mobileMenuBtn && sidebar) {
                mobileMenuBtn.addEventListener('click', function() {
                    sidebar.classList.toggle('active');
                    this.setAttribute('aria-expanded', 
                        this.getAttribute('aria-expanded') === 'true' ? 'false' : 'true'
                    );
                    this.querySelector('i').classList.toggle('fa-times');
                    this.querySelector('i').classList.toggle('fa-bars');
                });

                // Close menu when clicking outside
                document.addEventListener('click', function(event) {
                    const isClickInside = sidebar.contains(event.target) || 
                                        mobileMenuBtn.contains(event.target);
                    
                    if (!isClickInside && sidebar.classList.contains('active')) {
                        sidebar.classList.remove('active');
                        mobileMenuBtn.setAttribute('aria-expanded', 'false');
                        mobileMenuBtn.querySelector('i').classList.remove('fa-times');
                        mobileMenuBtn.querySelector('i').classList.add('fa-bars');
                    }
                });
            }
        });

        // Pass user data to JavaScript
        window.userData = {
            id: <?php echo $current_user_id; ?>,
            username: '<?php echo htmlspecialchars($current_user['username']); ?>',
            email: '<?php echo htmlspecialchars($current_user['email']); ?>',
            gradeLevel: '<?php echo htmlspecialchars($current_user['grade_level']); ?>'
        };

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
             // Play click sound
             playClickSound();
             
             // Redirect to logout endpoint
             window.location.href = '../../onboarding/logout.php';
         }

         // Add event listeners for the confirmation buttons
         document.addEventListener('DOMContentLoaded', function() {
             const confirmRemoveBtn = document.getElementById('confirmRemoveBtn');
             const cancelRemoveBtn = document.getElementById('cancelRemoveBtn');
             
             if (confirmRemoveBtn) {
                 confirmRemoveBtn.addEventListener('click', confirmRemoveFriend);
             }
             
             if (cancelRemoveBtn) {
                 cancelRemoveBtn.addEventListener('click', cancelRemoveFriend);
             }
             
             // Close modal when clicking outside
             const removeFriendModal = document.getElementById('removeFriendModal');
             if (removeFriendModal) {
                 removeFriendModal.addEventListener('click', function(e) {
                     if (e.target === removeFriendModal) {
                         cancelRemoveFriend();
                     }
                 });
             }
             
             // Close modal with Escape key
             document.addEventListener('keydown', function(e) {
                 if (e.key === 'Escape') {
                     const modal = document.getElementById('removeFriendModal');
                     if (modal && modal.classList.contains('show')) {
                         cancelRemoveFriend();
                     }
                 }
             });
         });

         // Cancel friend request functionality
         function cancelFriendRequest(receiverId, receiverName, buttonElement) {
             console.log('Cancel request called for receiver:', receiverId);
             
             // Disable button and show loading state
             buttonElement.disabled = true;
             buttonElement.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Cancelling...';
             
             // Make API call to cancel friend request
             fetch('../cancel_friend_request.php', {
                 method: 'POST',
                 headers: {
                     'Content-Type': 'application/json',
                 },
                 body: JSON.stringify({
                     receiver_id: receiverId
                 })
             })
             .then(response => response.json())
             .then(data => {
                 if (data.success) {
                     // Show success message
                     showToast(`Friend request to ${receiverName} has been cancelled.`, 'success');
                     
                     // Update button to "Add Friend"
                     buttonElement.innerHTML = '<i class="fas fa-user-plus"></i> Add Friend';
                     buttonElement.className = 'friend-request-btn';
                     buttonElement.type = 'submit';
                     buttonElement.disabled = false;
                     buttonElement.onclick = null;
                     
                     // Update the form action
                     const form = buttonElement.closest('form');
                     if (form) {
                         form.querySelector('input[name="action"]').value = 'send_request';
                     }
                 } else {
                     // Show error message
                     showToast(data.message || 'Failed to cancel friend request. Please try again.', 'error');
                     
                     // Reset button state
                     buttonElement.disabled = false;
                     buttonElement.innerHTML = '<i class="fas fa-times"></i> Cancel Request';
                 }
             })
             .catch(error => {
                 console.error('Error cancelling friend request:', error);
                 console.error('Error details:', error.message);
                 
                 // Show error message
                 showToast('Network error. Please check your connection and try again.', 'error');
                 
                 // Reset button state
                 buttonElement.disabled = false;
                 buttonElement.innerHTML = '<i class="fas fa-times"></i> Cancel Request';
             });
         }

         // Remove friend functionality
         function removeFriend(friendId, friendName, buttonElement) {
             console.log('removeFriend called with:', friendId, friendName);
             
             // Store the parameters for later use
             window.pendingRemoveFriend = {
                 friendId: friendId,
                 friendName: friendName,
                 buttonElement: buttonElement
             };
             
             // Update the message in the modal
             const messageElement = document.getElementById('removeFriendMessage');
             if (messageElement) {
                 messageElement.textContent = `Are you sure you want to remove ${friendName} as a friend?`;
             }
             
             // Show custom confirmation dialog
             showRemoveFriendModal();
         }

         // Show remove friend confirmation modal
         function showRemoveFriendModal() {
             const modal = document.getElementById('removeFriendModal');
             console.log('showRemoveFriendModal called, modal found:', modal);
             if (modal) {
                 modal.classList.add('show');
                 modal.style.display = 'flex';
                 console.log('Modal should now be visible');
             } else {
                 console.error('Modal element not found!');
             }
         }

         // Hide remove friend confirmation modal
         function hideRemoveFriendModal() {
             const modal = document.getElementById('removeFriendModal');
             if (modal) {
                 modal.classList.remove('show');
                 modal.style.display = 'none';
             }
         }

         // Confirm remove friend
         function confirmRemoveFriend() {
             const { friendId, friendName, buttonElement } = window.pendingRemoveFriend;
             
             // Hide the modal
             hideRemoveFriendModal();
             
             // Disable button and show loading state
             buttonElement.disabled = true;
             buttonElement.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Removing...';
             
             // Make API call to remove friend
             fetch('../remove_friend.php', {
                 method: 'POST',
                 headers: {
                     'Content-Type': 'application/json',
                 },
                 body: JSON.stringify({
                     friend_id: friendId
                 })
             })
             .then(response => response.json())
             .then(data => {
                 if (data.success) {
                     // Show success message
                     showToast(`${friendName} has been removed from your friends.`, 'success');
                     
                     // Redirect to friends page with hard refresh after a short delay
                     setTimeout(() => {
                         // Force a hard refresh by adding a timestamp parameter
                         const timestamp = new Date().getTime();
                         window.location.replace(`friends.php?t=${timestamp}`);
                     }, 1500);
                 } else {
                     // Show error message
                     showToast(data.message || 'Failed to remove friend. Please try again.', 'error');
                     
                     // Reset button state
                     buttonElement.disabled = false;
                     buttonElement.innerHTML = '<i class="fas fa-user-minus"></i> Remove Friend';
                 }
             })
             .catch(error => {
                 console.error('Error removing friend:', error);
                 
                 // Show error message
                 showToast('Network error. Please check your connection and try again.', 'error');
                 
                 // Reset button state
                 buttonElement.disabled = false;
                 buttonElement.innerHTML = '<i class="fas fa-user-minus"></i> Remove Friend';
             });
         }

         // Cancel remove friend
         function cancelRemoveFriend() {
             hideRemoveFriendModal();
             // Clear the pending data
             window.pendingRemoveFriend = null;
         }

         // Toast notification system
         function showToast(message, type = 'info') {
             const toast = document.getElementById('toast');
             const toastOverlay = document.querySelector('.toast-overlay');
             
             if (!toast || !toastOverlay) return;
             
             // Set message and type
             toast.textContent = message;
             toast.className = `toast ${type}`;
             
             // Show toast
             toastOverlay.classList.add('show');
             toast.classList.add('show');
             
             // Hide after 3 seconds
             setTimeout(() => {
                 toast.classList.remove('show');
                 toastOverlay.classList.remove('show');
             }, 3000);
         }
    </script>
</body>
</html>

