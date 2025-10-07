<?php
require_once '../onboarding/config.php';
require_once '../includes/greeting.php';

// Check if user is logged in, redirect to login if not
if (!isLoggedIn()) {
    header('Location: ../onboarding/login.php');
    exit();
}

// Get user information
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT username, email, grade_level FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    // User not found, destroy session and redirect to login
    session_destroy();
    header('Location: ../onboarding/login.php');
    exit();
}



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

// Get notification count for badge
$notification_count = count($friend_requests);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Word Weavers - Notifications</title>
    <link rel="stylesheet" href="shared/navigation.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="notification.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../notif/toast.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../styles.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
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
            <img src="../assets/menu/Word-Weavers.png" alt="Word Weavers" class="sidebar-logo-img">
        </div>
        <nav class="sidebar-nav">
            <a href="../menu.php" class="nav-link">
                <i class="fas fa-house"></i>
                <span>Menu</span>
            </a>
            <a href="../navigation/favorites/favorites.php" class="nav-link">
                <i class="fas fa-star"></i>
                <span>Favorites</span>
            </a>
            <a href="../navigation/friends/friends.php" class="nav-link">
                <i class="fas fa-users"></i>
                <span>Friends</span>
            </a>
            <a href="../navigation/profile/profile.php" class="nav-link">
                <i class="fas fa-user"></i>
                <span>Profile</span>
            </a>
        </nav>
    </div>

    <!-- Header -->
    <header class="top-header">
        <div class="header-right">
            <div class="notification-icon">
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
                        <img src="../assets/menu/defaultuser.png" alt="Profile" class="profile-img">
                    </a>
                    <div class="profile-dropdown-content">
                        <div class="profile-dropdown-header">
                            <img src="../assets/menu/defaultuser.png" alt="Profile" class="profile-dropdown-avatar">
                            <div class="profile-dropdown-info">
                                <div class="profile-dropdown-name"><?php echo htmlspecialchars($user['username']); ?></div>
                                <div class="profile-dropdown-email"><?php echo htmlspecialchars($user['email']); ?></div>
                            </div>
                        </div>
                        <div class="profile-dropdown-menu">
                            <a href="../navigation/profile/profile.php" class="profile-dropdown-item">
                                <i class="fas fa-user"></i>
                                <span>View Profile</span>
                            </a>
                            <a href="../navigation/favorites/favorites.php" class="profile-dropdown-item">
                                <i class="fas fa-star"></i>
                                <span>My Favorites</span>
                            </a>
                            <a href="../settings/settings.php" class="profile-dropdown-item">
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
        <div class="notification-container">

            <!-- Friend Requests Section -->
            <div class="friend-requests-section">
                <div class="section-header">
                    <h2><i class="fas fa-user-plus"></i> Friend Requests (<?php echo count($friend_requests); ?>)</h2>
                    <?php if (!empty($friend_requests)): ?>
                    <button class="mark-all-read-btn" onclick="markAllAsRead()">
                        <i class="fas fa-check-double"></i>
                        Mark All as Read
                    </button>
                    <?php endif; ?>
                </div>
                
                <?php if (empty($friend_requests)): ?>
                <div class="empty-state">
                    <i class="fas fa-bell-slash"></i>
                    <h3>No New Notifications</h3>
                    <p>You're all caught up! Check back later for new friend requests.</p>
                </div>
                <?php else: ?>
                <div class="friend-requests-list">
                    <?php foreach ($friend_requests as $request): ?>
                    <div class="friend-request-card" data-request-id="<?php echo $request['id']; ?>">
                        <div class="request-avatar" onclick="viewProfile(<?php echo $request['requester_id']; ?>)">
                            <img src="../assets/menu/defaultuser.png" alt="<?php echo htmlspecialchars($request['username']); ?>">
                        </div>
                        <div class="request-info">
                            <h3 onclick="viewProfile(<?php echo $request['requester_id']; ?>)" style="cursor: pointer;"><?php echo htmlspecialchars($request['username']); ?></h3>
                            <p class="request-details">
                                <span class="grade-level"><?php echo htmlspecialchars($request['grade_level']); ?></span>
                                <span class="request-time"><?php echo timeAgo($request['created_at']); ?></span>
                            </p>
                            <p class="request-message">wants to be your friend</p>
                        </div>
                        <div class="request-actions">
                            <button class="accept-btn" onclick="acceptFriendRequest(<?php echo $request['id']; ?>, <?php echo $request['requester_id']; ?>, '<?php echo htmlspecialchars($request['username']); ?>', this)">
                                <i class="fas fa-check"></i>
                                Accept
                            </button>
                            <button class="decline-btn" onclick="declineFriendRequest(<?php echo $request['id']; ?>, '<?php echo htmlspecialchars($request['username']); ?>', this)">
                                <i class="fas fa-times"></i>
                                Decline
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
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
            <h3>Logout Confirmation</h3>
            <p>Are you sure you want to logout?</p>
            <div class="modal-buttons">
                <button class="logout-btn" onclick="confirmLogout()">Yes, Logout</button>
                <button class="cancel-btn" onclick="hideLogoutModal()">Cancel</button>
            </div>
        </div>
    </div>

    <script src="../script.js"></script>
    <script src="shared/profile-dropdown.js"></script>
    <script src="notification.js"></script>
    <script>
        // Pass user data to JavaScript
        window.userData = {
            id: <?php echo $user_id; ?>,
            username: '<?php echo htmlspecialchars($user['username']); ?>',
            email: '<?php echo htmlspecialchars($user['email']); ?>',
            gradeLevel: '<?php echo htmlspecialchars($user['grade_level']); ?>'
        };

        // View profile functionality
        function viewProfile(userId) {
            // Navigate to user profile page
            window.location.href = `friends/user-profile.php?id=${userId}`;
        }

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
            window.location.href = '../onboarding/logout.php';
        }

        // Friend Request Functions
        function acceptFriendRequest(requestId, requesterId, username, buttonElement) {
            // Play click sound
            playClickSound();

            // Disable button and show loading state
            buttonElement.disabled = true;
            const declineBtn = buttonElement.nextElementSibling;
            declineBtn.disabled = true;
            buttonElement.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Accepting...';
            
            // Make API call to accept friend request
            fetch('accept_friend_request.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    request_id: requestId,
                    requester_id: requesterId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success state
                    buttonElement.innerHTML = '<i class="fas fa-check"></i> Accepted';
                    buttonElement.style.background = '#00ff87';
                    declineBtn.style.display = 'none';
                    
                    // Show success message
                    showToast(`You are now friends with ${username}!`, 'success');
                    
                    // Reload page after a short delay
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    // Reset button state
                    buttonElement.disabled = false;
                    declineBtn.disabled = false;
                    buttonElement.innerHTML = '<i class="fas fa-check"></i> Accept';
                    
                    // Show error message
                    showToast(data.message || 'Failed to accept friend request', 'error');
                }
            })
            .catch(error => {
                // Reset button state
                buttonElement.disabled = false;
                declineBtn.disabled = false;
                buttonElement.innerHTML = '<i class="fas fa-check"></i> Accept';
                
                // Show error message
                showToast('Network error. Please try again.', 'error');
            });
        }

        function declineFriendRequest(requestId, username, buttonElement) {
            // Play click sound
            playClickSound();

            // Disable button and show loading state
            buttonElement.disabled = true;
            const acceptBtn = buttonElement.previousElementSibling;
            acceptBtn.disabled = true;
            buttonElement.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Declining...';
            
            // Make API call to decline friend request
            fetch('decline_friend_request.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    request_id: requestId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show decline state
                    buttonElement.innerHTML = '<i class="fas fa-times"></i> Declined';
                    buttonElement.style.background = '#ff6b6b';
                    acceptBtn.style.display = 'none';
                    
                    // Show success message
                    showToast(`Friend request from ${username} has been declined`, 'info');
                    
                    // Reload page after a short delay
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    // Reset button state
                    buttonElement.disabled = false;
                    acceptBtn.disabled = false;
                    buttonElement.innerHTML = '<i class="fas fa-times"></i> Decline';
                    
                    // Show error message
                    showToast(data.message || 'Failed to decline friend request', 'error');
                }
            })
            .catch(error => {
                // Reset button state
                buttonElement.disabled = false;
                acceptBtn.disabled = false;
                buttonElement.innerHTML = '<i class="fas fa-times"></i> Decline';
                
                // Show error message
                showToast('Network error. Please try again.', 'error');
            });
        }

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

<?php
// Helper function to format time ago
function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'just now';
    if ($time < 3600) return floor($time/60) . ' minutes ago';
    if ($time < 86400) return floor($time/3600) . ' hours ago';
    if ($time < 2592000) return floor($time/86400) . ' days ago';
    if ($time < 31536000) return floor($time/2592000) . ' months ago';
    return floor($time/31536000) . ' years ago';
}
?>
