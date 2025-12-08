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

// Merge and sort all notifications
$all_notifications = [];

foreach ($friend_requests as $fr) {
    $all_notifications[] = [
        'type' => 'friend_request',
        'timestamp' => strtotime($fr['created_at']),
        'data' => $fr
    ];
}

foreach ($cresent_notifications as $cn) {
    $all_notifications[] = [
        'type' => 'cresent_received',
        'timestamp' => strtotime($cn['created_at']),
        'data' => $cn
    ];
}

// Sort by timestamp DESC
usort($all_notifications, function($a, $b) {
    return $b['timestamp'] - $a['timestamp'];
});

// Get notification count for badge
$notification_count = count($all_notifications);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/webp" href="../assets/menu/ww_logo_main.webp">
    <title>Word Weavers - Notifications</title>
    <link rel="stylesheet" href="shared/navigation.css?v=<?php echo filemtime('shared/navigation.css'); ?>">
    <link rel="stylesheet" href="notification.css?v=<?php echo filemtime('notification.css'); ?>">
    <link rel="stylesheet" href="../notif/toast.css?v=<?php echo filemtime('../notif/toast.css'); ?>">
    <link rel="stylesheet" href="../styles.css?v=<?php echo filemtime('../styles.css'); ?>">
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
            <?php if (in_array($user['grade_level'], ['Developer', 'Admin'])): ?>
            <a href="../navigation/moderation/moderation.php" class="nav-link">
                <i class="fas fa-shield-alt"></i>
                <span>Admin</span>
            </a>
            <?php endif; ?>
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

            <!-- Notifications Section -->
            <div class="friend-requests-section">
                <div class="section-header">
                    <div class="title-container">
                    <h2 class="friend-requests-title"><img src="../assets/pixels/redbook.png" alt="Notification" class="friend-request-icon"> NOTIFICATIONS <span class="request-count">(<?php echo $notification_count; ?>)</span></h2>
                </div>
                    <?php if (!empty($friend_requests)): ?>
                    <button class="decline-all-btn" onclick="showDeclineAllModal()" aria-label="Decline All Requests">
                        <i class="fas fa-ban"></i>
                        <span>Decline All Requests</span>
                    </button>
                    <?php endif; ?>
                </div>
                
                <?php if (empty($all_notifications)): ?>
                <div class="empty-state">
                    <i class="fas fa-bell-slash"></i>
                    <h3>No New Notifications</h3>
                    <p>You're all caught up! Check back later for new updates.</p>
                </div>
                <?php else: ?>
                <div class="friend-requests-list">
                    <?php foreach ($all_notifications as $notification): ?>
                        <?php if ($notification['type'] === 'friend_request'): ?>
                            <?php $request = $notification['data']; ?>
                            <div class="friend-request-card" data-request-id="<?php echo $request['id']; ?>">
                                <div class="request-avatar" onclick="viewProfile(<?php echo $request['requester_id']; ?>)">
                                    <img src="../assets/menu/defaultuser.png" alt="<?php echo htmlspecialchars($request['username']); ?>">
                                </div>
                                <div class="request-info">
                                    <h3 onclick="viewProfile(<?php echo $request['requester_id']; ?>)" style="cursor: pointer;"><img src="../assets/pixels/friendgem.png" alt="Friend Request" class="username-icon"> <?php echo htmlspecialchars($request['username']); ?> <span class="notification-text">has sent you a friend request</span></h3>
                                    <p class="request-details">
                                        <span class="grade-level"><?php echo htmlspecialchars($request['grade_level']); ?></span>
                                        <span class="request-time"><?php echo timeAgo($request['created_at']); ?></span>
                                    </p>
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
                        <?php elseif ($notification['type'] === 'cresent_received'): ?>
                            <?php 
                                $data = $notification['data']; 
                                $sender_data = json_decode($data['data'], true);
                                $sender_username = $sender_data['sender_username'] ?? 'Unknown';
                                $sender_id = $sender_data['sender_id'] ?? 0;
                            ?>
                            <div class="friend-request-card crescent-notification" data-notification-id="<?php echo $data['id']; ?>">
                                <div class="request-avatar" onclick="viewProfile(<?php echo $sender_id; ?>)">
                                    <img src="../assets/menu/defaultuser.png" alt="User">
                                </div>
                                <div class="request-info">
                                    <h3 onclick="viewProfile(<?php echo $sender_id; ?>)" style="cursor: pointer;"><img src="../assets/pixels/cresent.png" alt="Cresent" class="username-icon"> <?php echo htmlspecialchars($sender_username); ?> <span class="notification-text">has given you a Cresent</span></h3>
                                    <p class="request-details">
                                        <span class="request-time"><?php echo timeAgo($data['created_at']); ?></span>
                                    </p>
                                </div>
                                <div class="request-actions">
                                    <button class="decline-btn dismiss-only" onclick="dismissNotification(<?php echo $data['id']; ?>, this)">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Notification Modal -->
    <div class="toast-overlay" id="notificationModal">
        <div class="toast" id="notificationContent"></div>
    </div>
    
    <!-- Notification Toast -->
    <div class="toast-overlay" id="notificationModal" style="display: none;">
        <div class="toast" id="notificationContent"></div>
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

    <!-- Decline All Confirmation Modal -->
    <div class="toast-overlay" id="declineAllModal">
        <div class="toast" id="declineAllConfirmation">
            <h3>Decline All</h3>
            <p>Delete all pending friend requests?</p>
            <div class="modal-buttons">
                <button class="cancel-btn" onclick="hideDeclineAllModal()">Cancel</button>
                <button class="decline-all-btn" onclick="confirmDeclineAll()">Decline All</button>
            </div>
        </div>
    </div>
    <style>
    /* Decline All Modal Styles */
    #declineAllConfirmation {
        background: linear-gradient(135deg, rgba(17, 17, 17, 0.95), rgba(35, 35, 35, 0.98));
        max-width: 280px;
        border: 2px solid rgba(255, 107, 107, 0.3);
        box-shadow: 0 0 30px rgba(255, 107, 107, 0.2),
                    inset 0 0 20px rgba(255, 107, 107, 0.1);
        text-align: center;
        padding: 1.2rem;
        margin: 0 auto;
    }

    #declineAllConfirmation h3 {
        font-family: 'Press Start 2P', cursive;
        font-size: 0.9rem;
        color: #ff6b6b;
        margin: 0 0 0.8rem 0;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        text-shadow: 0 2px 4px rgba(255, 107, 107, 0.3);
    }

    #declineAllConfirmation p {
        color: rgba(255, 255, 255, 0.9);
        font-family: 'Press Start 2P', cursive;
        font-size: 0.6rem;
        margin: 0 0 1.2rem 0;
        line-height: 1.4;
        text-shadow: 0 1px 2px rgba(0, 0, 0, 0.5);
    }

    #declineAllConfirmation .modal-buttons {
        display: flex;
        justify-content: center;
        gap: 0.8rem;
        margin-top: 0.5rem;
    }

    #declineAllConfirmation .decline-all-btn,
    #declineAllConfirmation .cancel-btn {
        flex: 1;
        padding: 0.6rem 0.8rem;
        border-radius: 8px;
        font-family: 'Press Start 2P', cursive;
        font-size: 0.6rem;
        cursor: pointer;
        transition: all 0.2s ease;
        text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
        white-space: nowrap;
    }

    #declineAllConfirmation .decline-all-btn {
        background: linear-gradient(135deg, #ff6b6b, #ff4757);
        color: white;
        border: none;
        box-shadow: 0 2px 8px rgba(255, 107, 107, 0.3);
    }

    #declineAllConfirmation .decline-all-btn:active {
        transform: translateY(1px);
        box-shadow: 0 2px 4px rgba(255, 107, 107, 0.2);
    }

    #declineAllConfirmation .cancel-btn {
        background: rgba(255, 255, 255, 0.1);
        color: white;
        border: 1px solid rgba(255, 255, 255, 0.2);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
    }

    #declineAllConfirmation .cancel-btn:active {
        transform: translateY(1px);
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    /* Responsive Design */
    @media (max-width: 480px) {
        #declineAllConfirmation {
            width: 85%;
            max-width: 260px;
            padding: 1rem 0.8rem;
        }

        #declineAllConfirmation h3 {
            font-size: 0.75rem;
            margin-bottom: 0.6rem;
        }

        #declineAllConfirmation p {
            font-size: 0.55rem;
            margin-bottom: 1rem;
            padding: 0 0.5rem;
        }

        #declineAllConfirmation .modal-buttons {
            flex-direction: row;
            gap: 0.6rem;
            margin-top: 0.3rem;
        }

        #declineAllConfirmation .decline-all-btn,
        #declineAllConfirmation .cancel-btn {
            padding: 0.5rem 0.6rem;
            font-size: 0.5rem;
            min-width: 80px;
        }

        /* Mobile decline all button - circular with no sign (only header button) */
        .friend-requests-section .decline-all-btn {
            width: 30px !important;
            height: 30px !important;
            border-radius: 8px !important;
            padding: 0 !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            min-width: 30px !important;
            font-size: 0 !important;
        }

        .friend-requests-section .decline-all-btn i {
            font-size: 14px !important;
            margin: 0 !important;
        }

        .friend-requests-section .decline-all-btn span {
            display: none !important;
        }

        /* Mobile accept and decline buttons - icon only circular buttons */
        .friend-request-card {
            position: relative;
        }

        .request-actions {
            position: absolute !important;
            right: 0.5rem !important;
            top: 50% !important;
            transform: translateY(-50%) !important;
            margin: 0 !important;
            flex-direction: row !important;
            gap: 0.3rem !important;
        }

        .accept-btn, .decline-btn {
            width: 28px !important;
            height: 28px !important;
            border-radius: 6px !important;
            padding: 0 !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            min-width: 28px !important;
            font-size: 0 !important;
        }

        .accept-btn i, .decline-btn i {
            font-size: 12px !important;
            margin: 0 !important;
        }
    }

    .friend-request-icon {
        width: 32px;
        height: 32px;
        vertical-align: middle;
    }
    </style>

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
                modal.style.display = 'flex';
                setTimeout(() => {
                    modal.classList.add('show');
                    confirmation.classList.remove('hide');
                    confirmation.classList.add('show');
                }, 10);
            }
        }

        function hideLogoutModal() {
            const modal = document.getElementById('logoutModal');
            const confirmation = document.getElementById('logoutConfirmation');
            
            if (modal && confirmation) {
                confirmation.classList.remove('show');
                confirmation.classList.add('hide');
                modal.classList.remove('show');
                setTimeout(() => {
                    modal.style.display = 'none';
                    confirmation.classList.remove('hide');
                }, 300);
            }
        }

        function confirmLogout() {
            // Play click sound
            playClickSound();
            
            // Redirect to logout endpoint
            window.location.href = '../onboarding/logout.php';
        }

        // Close modals when clicking outside
        document.getElementById('logoutModal').addEventListener('click', function(e) {
            if (e.target === this) {
                hideLogoutModal();
            }
        });

        document.getElementById('declineAllModal').addEventListener('click', function(e) {
            if (e.target === this) {
                hideDeclineAllModal();
            }
        });

        // Close modals with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                if (document.getElementById('logoutModal').classList.contains('show')) {
                    hideLogoutModal();
                }
                if (document.getElementById('declineAllModal').classList.contains('show')) {
                    hideDeclineAllModal();
                }
            }
        });

        // Friend Request Functions
        function acceptFriendRequest(requestId, requesterId, username, buttonElement) {
            // Play click sound
            playClickSound();

            // Disable button and show loading state
            buttonElement.disabled = true;
            const declineBtn = buttonElement.nextElementSibling;
            if (declineBtn) declineBtn.disabled = true;
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
                    // Reload the page immediately
                    window.location.reload();
                } else {
                    throw new Error(data.message || 'Failed to accept friend request');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                // Reset button state
                buttonElement.disabled = false;
                if (declineBtn) declineBtn.disabled = false;
                buttonElement.innerHTML = '<i class="fas fa-check"></i> Accept';
                
                // Don't show error message for "Invalid requester ID" as it's likely a race condition
                // where the request was already processed successfully
                if (!error.message || !error.message.includes('Invalid requester ID')) {
                    // Show error message
                    alert('Error: ' + (error.message || 'Failed to accept friend request'));
                }
            });
        }

        function showDeclineAllModal() {
            // Play click sound
            playClickSound();
            
            const modal = document.getElementById('declineAllModal');
            const confirmation = document.getElementById('declineAllConfirmation');
            
            if (modal && confirmation) {
                modal.style.display = 'flex';
                setTimeout(() => {
                    modal.classList.add('show');
                    confirmation.classList.remove('hide');
                    confirmation.classList.add('show');
                }, 10);
            }
        }

        function hideDeclineAllModal() {
            const modal = document.getElementById('declineAllModal');
            const confirmation = document.getElementById('declineAllConfirmation');
            
            if (modal && confirmation) {
                confirmation.classList.remove('show');
                confirmation.classList.add('hide');
                modal.classList.remove('show');
                setTimeout(() => {
                    modal.style.display = 'none';
                    confirmation.classList.remove('hide');
                }, 300);
            }
        }

        function confirmDeclineAll() {
            // Hide the modal
            const modal = document.getElementById('declineAllModal');
            if (modal) {
                modal.style.display = 'none';
            }

            // Disable all buttons
            const declineAllBtn = document.querySelector('.decline-all-btn');
            const actionButtons = document.querySelectorAll('.accept-btn, .decline-btn');
            
            if (declineAllBtn) {
                declineAllBtn.disabled = true;
                declineAllBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Declining...';
            }
            
            actionButtons.forEach(btn => {
                if (btn) btn.disabled = true;
            });

            // Make API call to decline all requests
            fetch('decline_all_requests.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=decline_all'
            })
            .then(response => response.json())
            .then(data => {
                if (data && data.success) {
                    // Reload the page
                    window.location.reload();
                } else {
                    throw new Error(data ? data.message : 'Failed to process request');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                
                // Reset button states
                if (declineAllBtn) {
                    declineAllBtn.disabled = false;
                    declineAllBtn.innerHTML = 'Decline All';
                }
                
                actionButtons.forEach(btn => {
                    if (btn) btn.disabled = false;
                });
                
                // Show error message
                alert('Error: ' + (error.message || 'Failed to decline all requests. Please try again.'));
            });
        }

        function declineFriendRequest(requestId, username, buttonElement) {
            // Play click sound
            playClickSound();

            // Disable button and show loading state
            buttonElement.disabled = true;
            const acceptBtn = buttonElement.previousElementSibling;
            if (acceptBtn) acceptBtn.disabled = true;
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
                    // Reload the page immediately
                    window.location.reload();
                } else {
                    throw new Error(data.message || 'Failed to decline friend request');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                // Reset button state
                buttonElement.disabled = false;
                if (acceptBtn) acceptBtn.disabled = false;
                buttonElement.innerHTML = '<i class="fas fa-times"></i> Decline';
                
                // Show error message
                alert('Error: ' + (error.message || 'Failed to decline friend request'));
            });
        }

        function dismissNotification(notificationId, buttonElement) {
            // Play click sound
            playClickSound();

            // Disable button
            buttonElement.disabled = true;
            buttonElement.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Dismissing...';
            
            fetch('dismiss_notification.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    notification_id: notificationId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Reload the page immediately
                    window.location.reload();
                } else {
                    throw new Error(data.message || 'Failed to dismiss notification');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                buttonElement.disabled = false;
                buttonElement.innerHTML = '<i class="fas fa-times"></i> Dismiss';
                alert('Error: ' + (error.message || 'Failed to dismiss notification'));
            });
        }

        function showErrorModal(message) {
            const modal = document.getElementById('notificationModal');
            const content = document.getElementById('notificationContent');
            
            if (modal && content) {
                content.innerHTML = `
                    <h3>Error</h3>
                    <p>${message}</p>
                    <div class="modal-buttons">
                        <button class="confirm-btn" onclick="hideErrorModal()">OK</button>
                    </div>
                `;
                content.className = 'toast error';
                modal.style.display = 'flex';
                setTimeout(() => {
                    modal.classList.add('show');
                    content.classList.add('show');
                }, 10);
            }
        }

        function hideErrorModal() {
            const modal = document.getElementById('notificationModal');
            const content = document.getElementById('notificationContent');
            
            if (modal && content) {
                content.classList.remove('show');
                modal.classList.remove('show');
                setTimeout(() => {
                    modal.style.display = 'none';
                }, 300);
            }
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
