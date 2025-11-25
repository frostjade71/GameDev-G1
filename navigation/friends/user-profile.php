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

// Get viewed user's rank from leaderboard
$rank_stmt = $pdo->prepare("
    SELECT COUNT(*) + 1 as user_rank
    FROM users u
    LEFT JOIN game_progress gp ON u.id = gp.user_id AND gp.game_type = 'vocabworld'
    WHERE u.id != ?
    AND (COALESCE(gp.player_level, 1) > COALESCE((SELECT player_level FROM game_progress WHERE user_id = ? AND game_type = 'vocabworld'), 1)
         OR (COALESCE(gp.player_level, 1) = COALESCE((SELECT player_level FROM game_progress WHERE user_id = ? AND game_type = 'vocabworld'), 1)
             AND COALESCE(gp.total_monsters_defeated, 0) > COALESCE((SELECT total_monsters_defeated FROM game_progress WHERE user_id = ? AND game_type = 'vocabworld'), 0)))
");
$rank_stmt->execute([$viewed_user_id, $viewed_user_id, $viewed_user_id, $viewed_user_id]);
$user_rank = $rank_stmt->fetchColumn();

// Get character selection data for JavaScript
$character_stmt = $pdo->prepare("
    SELECT character_image_path, selected_character 
    FROM character_selections 
    WHERE user_id = ? AND game_type = 'vocabworld' 
    LIMIT 1
");
$character_stmt->execute([$viewed_user_id]);
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
            // Try to extract character name from the stored path
            foreach ($character_images as $char => $path) {
                if (stripos($character_result['character_image_path'], $char) !== false) {
                    $character_image = $path; // Use the correct path from our array
                    break;
                }
            }
        }
    } 
    // Fallback to extracting name from image path if no selected_character
    else if (!empty($character_result['character_image_path'])) {
        // Try to match the stored path to our known character paths
        foreach ($character_images as $char => $path) {
            if (stripos($character_result['character_image_path'], $char) !== false) {
                $character_image = $path; // Use the correct path from our array
                $character_name = ucfirst($char);
                break;
            }
        }
    }
}

// Initialize essence and shards with default values since tables don't exist
$essence = 0;
$shards = 0;

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

// Get pending friend requests for the current user
$stmt = $pdo->prepare("
    SELECT COUNT(*) as count
    FROM friend_requests 
    WHERE receiver_id = ? AND status = 'pending'
");
$stmt->execute([$current_user_id]);
$friend_requests_count = $stmt->fetch()['count'];

// Get crescent notifications for the current user
$stmt = $pdo->prepare("
    SELECT COUNT(*) as count
    FROM notifications
    WHERE user_id = ? AND type = 'cresent_received'
");
$stmt->execute([$current_user_id]);
$cresent_notifications_count = $stmt->fetch()['count'];

// Total notification count
$notification_count = $friend_requests_count + $cresent_notifications_count;

// Get viewed user's rank from leaderboard
$rank_stmt = $pdo->prepare("
    SELECT COUNT(*) + 1 as user_rank
    FROM users u
    LEFT JOIN game_progress gp ON u.id = gp.user_id AND gp.game_type = 'vocabworld'
    WHERE u.id != ?
    AND (COALESCE(gp.player_level, 1) > COALESCE((SELECT player_level FROM game_progress WHERE user_id = ? AND game_type = 'vocabworld'), 1)
         OR (COALESCE(gp.player_level, 1) = COALESCE((SELECT player_level FROM game_progress WHERE user_id = ? AND game_type = 'vocabworld'), 1)
             AND COALESCE(gp.total_monsters_defeated, 0) > COALESCE((SELECT total_monsters_defeated FROM game_progress WHERE user_id = ? AND game_type = 'vocabworld'), 0)))
");
$rank_stmt->execute([$viewed_user_id, $viewed_user_id, $viewed_user_id, $viewed_user_id]);
$user_rank = $rank_stmt->fetchColumn();

// Get viewed user's game statistics
$game_stats = [
    ['game_type' => 'vocabworld', 'gwa_score' => 0, 'play_count' => 0, 'best_score' => 0, 'total_score' => 0],
    ['game_type' => 'grammarheroes', 'gwa_score' => 0, 'play_count' => 0, 'best_score' => 0, 'total_score' => 0]
];

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

// Get Essence and Shards
$essence = 0;
$shards = 0;

// Get Essence
$essence_manager_path = '../../MainGame/vocabworld/api/essence_manager.php';
if (file_exists($essence_manager_path)) {
    require_once $essence_manager_path;
    if (class_exists('EssenceManager')) {
        $essenceManager = new EssenceManager($pdo);
        $essence = $essenceManager->getEssence($viewed_user_id);
    }
}

// Get Shards
$shard_manager_path = '../../MainGame/vocabworld/shard_manager.php';
if (file_exists($shard_manager_path)) {
    require_once $shard_manager_path;
    if (class_exists('ShardManager')) {
        $shardManager = new ShardManager($pdo);
        $shard_result = $shardManager->getShardBalance($viewed_user_id);
        if ($shard_result && isset($shard_result['current_shards'])) {
            $shards = $shard_result['current_shards'];
        }
    }
}

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

function incrementView($pdo, $username) {
    initializeUserFame($pdo, $username);
    $stmt = $pdo->prepare("UPDATE user_fame SET views = views + 1 WHERE username = ?");
    $stmt->execute([$username]);
}

function toggleCrescent($pdo, $viewer_username, $viewed_username) {
    initializeUserFame($pdo, $viewer_username);
    initializeUserFame($pdo, $viewed_username);
    
    // Check if viewer has already given a crescent to viewed user
    $stmt = $pdo->prepare("SELECT id FROM user_crescents WHERE giver_username = ? AND receiver_username = ?");
    $stmt->execute([$viewer_username, $viewed_username]);
    $existing = $stmt->fetch();
    
    if ($existing) {
        // Remove crescent
        $stmt = $pdo->prepare("DELETE FROM user_crescents WHERE giver_username = ? AND receiver_username = ?");
        $stmt->execute([$viewer_username, $viewed_username]);
        $stmt = $pdo->prepare("UPDATE user_fame SET cresents = cresents - 1 WHERE username = ?");
        $stmt->execute([$viewed_username]);
        return false; // Crescent removed
    } else {
        // Add crescent
        $stmt = $pdo->prepare("INSERT INTO user_crescents (giver_username, receiver_username, created_at) VALUES (?, ?, NOW())");
        $stmt->execute([$viewer_username, $viewed_username]);
        $stmt = $pdo->prepare("UPDATE user_fame SET cresents = cresents + 1 WHERE username = ?");
        $stmt->execute([$viewed_username]);
        return true; // Crescent added
    }
}

function hasGivenCrescent($pdo, $viewer_username, $viewed_username) {
    $stmt = $pdo->prepare("SELECT id FROM user_crescents WHERE giver_username = ? AND receiver_username = ?");
    $stmt->execute([$viewer_username, $viewed_username]);
    return $stmt->fetch() !== false;
}

// Create user_crescents table if it doesn't exist
$pdo->exec("CREATE TABLE IF NOT EXISTS user_crescents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    giver_username VARCHAR(255) NOT NULL,
    receiver_username VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_crescent (giver_username, receiver_username)
)");

// Increment view count (only if viewer is not the same as viewed user)
if ($current_user['username'] !== $viewed_user['username']) {
    incrementView($pdo, $viewed_user['username']);
}

// Get user fame stats
$user_fame = getUserFame($pdo, $viewed_user['username']);
$views_count = $user_fame ? $user_fame['views'] : 0;
$crescents_count = $user_fame ? $user_fame['cresents'] : 0;

// Check if current user has given crescent to viewed user
$has_given_crescent = false;
if ($current_user['username'] !== $viewed_user['username']) {
    $has_given_crescent = hasGivenCrescent($pdo, $current_user['username'], $viewed_user['username']);
}

// Handle crescent toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_crescent') {
    // Clear any output buffers
    if (ob_get_length()) ob_clean();
    
    if ($current_user['username'] !== $viewed_user['username']) {
        try {
            $added = toggleCrescent($pdo, $current_user['username'], $viewed_user['username']);
            $user_fame = getUserFame($pdo, $viewed_user['username']);
            $views_count = $user_fame ? $user_fame['views'] : 0;
            $crescents_count = $user_fame ? $user_fame['cresents'] : 0;
            $has_given_crescent = $added;
            
            // Return JSON response for AJAX
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'cresents' => $crescents_count,
                'has_given' => $has_given_crescent
            ]);
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
        exit();
    } else {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => 'Cannot give crescent to yourself'
        ]);
        exit();
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
                    
                    <!-- User Fame Section -->
                    <div class="user-fame-section">
                        <div class="fame-stats">
                            <div class="fame-item">
                                <div class="tooltip">Profile Views: <?php echo number_format($views_count); ?></div>
                                <img src="../../assets/pixels/pubviews.png" alt="Views" class="fame-icon">
                                <span class="fame-value"><?php echo number_format($views_count); ?></span>
                            </div>
                            <span class="fame-separator">●</span>
                            <div class="fame-item">
                                <?php if ($current_user['username'] !== $viewed_user['username']): ?>
                                    <form method="POST" class="crescent-form" style="display: inline;">
                                        <input type="hidden" name="action" value="toggle_crescent">
                                        <input type="hidden" name="viewed_user_id" value="<?php echo $viewed_user_id; ?>">
                                        <button type="submit" class="crescent-btn <?php echo $has_given_crescent ? 'given' : ''; ?>" onclick="toggleCrescent(event, this)">
                                            <img src="../../assets/pixels/cresent.png" alt="Crescents" class="fame-icon">
                                            <span class="fame-value"><?php echo number_format($crescents_count); ?></span>
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <img src="../../assets/pixels/cresent.png" alt="Crescents" class="fame-icon">
                                    <span class="fame-value"><?php echo number_format($crescents_count); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
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
                        <div class="info-icon">
                            <i class="fas fa-layer-group"></i>
                        </div>
                        <div class="info-content">
                            <span class="info-label">Grade Level</span>
                            <span class="info-value"><?php echo htmlspecialchars($viewed_user['grade_level']); ?></span>
                        </div>
                    </div>
                    <div class="section-info">
                        <div class="info-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="info-content">
                            <span class="info-label">Section</span>
                            <span class="info-value"><?php echo !empty($viewed_user['section']) ? htmlspecialchars($viewed_user['section']) : 'Not specified'; ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Game Stats Section -->
            <div class="game-stats-section">
                <h2><i class="fas fa-gamepad"></i> Game Stats</h2>
                <div class="game-stats-content">
                    <div class="game-logo-small" onclick="showGameStatsModal()">
                        <img src="../../assets/selection/vocablogo.webp" alt="Vocabworld" class="game-logo-small-img">
                    </div>
                </div>
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
                            <div class="favorite-card" title="<?php echo $game_name; ?>">
                                <div class="game-logo-container">
                                    <img src="<?php echo $game_logo; ?>" alt="<?php echo $game_name; ?>" class="game-logo">
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

        </div>
        
        <!-- Back Button -->
        <div class="back-button-container">
            <a href="friends.php" class="back-button">
                <i class="fas fa-arrow-left"></i>
                Back
            </a>
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

    <!-- Game Stats Modal -->
    <div class="modal-overlay" id="gameStatsModal">
        <div class="game-stats-modal-content" id="gameStatsContent">
            <!-- Modal Header with Close Button -->
            <div class="game-stats-modal-header">
                <div class="modal-header-content">
                    <img src="../../MainGame/vocabworld/assets/menu/vocab_new.png" alt="Vocabworld" class="game-logo-header-img">
                </div>
                <button class="modal-close-btn" id="closeGameStatsBtn">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="game-stats-modal-body">
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
                                    <span class="stat-card-value gwa-value-display"><?php echo number_format($overall_gwa, 2); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
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
             
             // Set message and type (use innerHTML to render HTML content)
             toast.innerHTML = message;
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

         // Game Stats Modal functions
         function showGameStatsModal() {
             const modal = document.getElementById('gameStatsModal');
             const content = document.getElementById('gameStatsContent');
             
             if (modal && content) {
                 modal.classList.add('show');
                 content.classList.remove('hide');
                 content.classList.add('show');
             }
         }

         function hideGameStatsModal() {
             const modal = document.getElementById('gameStatsModal');
             const content = document.getElementById('gameStatsContent');
             
             if (modal && content) {
                 content.classList.remove('show');
                 content.classList.add('hide');
                 modal.classList.remove('show');
             }
         }

         // Add event listeners for the Game Stats modal
         document.addEventListener('DOMContentLoaded', function() {
             const closeGameStatsBtn = document.getElementById('closeGameStatsBtn');
             
             if (closeGameStatsBtn) {
                 closeGameStatsBtn.addEventListener('click', function(e) {
                     e.preventDefault();
                     hideGameStatsModal();
                 });
             }
             
             // Close modal when clicking outside
             const gameStatsModal = document.getElementById('gameStatsModal');
             if (gameStatsModal) {
                 gameStatsModal.addEventListener('click', function(e) {
                     if (e.target === gameStatsModal) {
                         hideGameStatsModal();
                     }
                 });
             }
             
             // Close modal with Escape key
             document.addEventListener('keydown', function(e) {
                 if (e.key === 'Escape') {
                     const modal = document.getElementById('gameStatsModal');
                     if (modal && modal.classList.contains('show')) {
                         hideGameStatsModal();
                     }
                 }
             });
         });

        // Crescent toggle functionality
        function toggleCrescent(event, button) {
            event.preventDefault();
            
            const form = button.closest('form');
            const formData = new FormData(form);
            const valueSpan = button.querySelector('.fame-value');
            const originalCount = parseInt(valueSpan.textContent.replace(/,/g, ''));
            const isCurrentlyGiven = button.classList.contains('given');
            
            // Disable button during request
            button.disabled = true;
            const originalHTML = button.innerHTML;
            button.innerHTML = '<span style="opacity: 0.5;">...</span>';
            
            fetch('crescent_ajax.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                // Check if response is actually JSON
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    throw new Error('Server returned non-JSON response');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Update the crescent count and button state
                    valueSpan.textContent = data.cresents.toLocaleString();
                    
                    // Toggle the 'given' class
                    if (data.has_given) {
                        button.classList.add('given');
                        showToast('<img src="../../assets/pixels/cresent.png" alt="Crescent" style="width: 24px; height: 24px; vertical-align: middle; margin-right: 8px;"> You gave a Crescent!', 'success');
                        
                        // Reload page after toast disappears (1.5 seconds)
                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);
                    } else {
                        button.classList.remove('given');
                        // No toast, just reload immediately
                        window.location.reload();
                    }
                } else {
                    // Show specific error message from server if available
                    const errorMsg = data.error || 'Failed to update crescent. Please try again.';
                    showToast(errorMsg, 'error');
                    
                    // Revert the UI changes since the operation failed
                    valueSpan.textContent = originalCount.toLocaleString();
                    if (isCurrentlyGiven) {
                        button.classList.add('given');
                    } else {
                        button.classList.remove('given');
                    }
                }
            })
            .catch(error => {
                console.error('Error toggling crescent:', error);
                
                // If the operation likely succeeded (based on database behavior) but response failed
                // Show optimistic UI update
                if (error.message.includes('non-JSON')) {
                    // Optimistically update the UI since the database operation likely worked
                    if (isCurrentlyGiven) {
                        button.classList.remove('given');
                        valueSpan.textContent = Math.max(0, originalCount - 1).toLocaleString();
                        // No toast, just reload immediately
                        window.location.reload();
                    } else {
                        button.classList.add('given');
                        valueSpan.textContent = (originalCount + 1).toLocaleString();
                        showToast('<img src="../../assets/pixels/cresent.png" alt="Crescent" style="width: 24px; height: 24px; vertical-align: middle; margin-right: 8px;"> You gave a Crescent!', 'success');
                        
                        // Reload page after toast disappears (1.5 seconds)
                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);
                    }
                } else {
                    // Show network error message
                    showToast('🌙 Crescent magic failed! Try again.', 'error');
                    
                    // Revert the UI changes
                    valueSpan.textContent = originalCount.toLocaleString();
                    if (isCurrentlyGiven) {
                        button.classList.add('given');
                    } else {
                        button.classList.remove('given');
                    }
                }
            })
            .finally(() => {
                // Re-enable button and restore original content
                button.disabled = false;
                button.innerHTML = originalHTML;
            });
        }
    </script>
</body>
</html>
