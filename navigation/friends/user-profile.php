<?php
require_once '../../onboarding/config.php';
require_once '../../includes/greeting.php';

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

// Get viewed user's game statistics
$stmt = $pdo->prepare("SELECT 
    game_type,
    AVG(score) as gwa_score,
    COUNT(*) as play_count,
    MAX(score) as best_score
    FROM game_scores 
    WHERE user_id = ?
    GROUP BY game_type");
$stmt->execute([$viewed_user_id]);
$game_stats = $stmt->fetchAll();

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
            // Create friend request
            $stmt = $pdo->prepare("INSERT INTO friend_requests (requester_id, receiver_id, status, created_at) VALUES (?, ?, 'pending', NOW())");
            $stmt->execute([$current_user_id, $viewed_user_id]);
            
            // Create notification for the receiver
            $stmt = $pdo->prepare("INSERT INTO notifications (user_id, type, message, data, created_at) VALUES (?, 'friend_request', ?, ?, NOW())");
            $message = $current_user['username'] . ' sent you a friend request';
            $data = json_encode(['requester_id' => $current_user_id, 'requester_name' => $current_user['username']]);
            $stmt->execute([$viewed_user_id, $message, $data]);
            
            // Refresh friend request status
            $stmt = $pdo->prepare("SELECT id, status FROM friend_requests WHERE requester_id = ? AND receiver_id = ?");
            $stmt->execute([$current_user_id, $viewed_user_id]);
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
    <title><?php echo htmlspecialchars($viewed_user['username']); ?> - Word Weavers</title>
    <link rel="stylesheet" href="../../navigation/shared/navigation.css">
    <link rel="stylesheet" href="../../notif/toast.css">
    <link rel="stylesheet" href="../../styles.css">
    <link rel="stylesheet" href="user-profile.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
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
            <!-- Back Button -->
            <div class="back-button-container">
                <a href="friends.php" class="back-button">
                    <i class="fas fa-arrow-left"></i>
                    Back to Friends
                </a>
            </div>

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
                    <p class="member-since">Member since <?php echo date('F j, Y', strtotime($viewed_user['created_at'])); ?></p>
                    <?php if (strtolower($viewed_user['username']) === 'jaderby garcia peñaranda'): ?>
                        <div class="badge-container">
                            <img src="../../assets/badges/developer.png" alt="Developer Badge" class="user-badge" title="Developer">
                        </div>
                    <?php endif; ?>
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

            <!-- User Stats -->
            <div class="user-stats-section">
                <h2><i class="fas fa-chart-bar"></i> Game Statistics</h2>
                <?php if (!empty($game_stats)): ?>
                    <div class="stats-grid">
                        <?php foreach ($game_stats as $stat): ?>
                            <div class="stat-card">
                                <div class="stat-icon">
                                    <i class="fas fa-gamepad"></i>
                                </div>
                                <div class="stat-info">
                                    <h3><?php echo htmlspecialchars(ucfirst($stat['game_type'])); ?></h3>
                                    <p>GWA: <?php echo number_format($stat['gwa_score'], 1); ?></p>
                                    <p>Best: <?php echo $stat['best_score']; ?></p>
                                    <p>Games: <?php echo $stat['play_count']; ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="no-data">
                        <i class="fas fa-chart-bar"></i>
                        <p>No game statistics available</p>
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

