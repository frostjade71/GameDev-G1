<?php
require_once '../../onboarding/config.php';
require_once '../../includes/greeting.php';

// Check if user is logged in, redirect to login if not
if (!isLoggedIn()) {
    header('Location: ../../onboarding/login.php');
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
    header('Location: ../../onboarding/login.php');
    exit();
}

// Get user's friends from the friends table
$stmt = $pdo->prepare("
    SELECT u.id, u.username, u.email, u.grade_level, u.created_at, f.created_at as friendship_date
    FROM friends f
    JOIN users u ON (
        CASE 
            WHEN f.user1_id = ? THEN u.id = f.user2_id
            WHEN f.user2_id = ? THEN u.id = f.user1_id
        END
    )
    WHERE f.user1_id = ? OR f.user2_id = ?
    ORDER BY f.created_at DESC
");
$stmt->execute([$user_id, $user_id, $user_id, $user_id]);
$friends_data = $stmt->fetchAll();

// Format friends data for display
$friends = [];
foreach ($friends_data as $friend_data) {
    $friends[] = [
        'id' => $friend_data['id'],
        'username' => $friend_data['username'],
        'email' => $friend_data['email'],
        'profile_image' => '../../assets/menu/defaultuser.png',
        'grade_level' => $friend_data['grade_level'],
        'joined_date' => date('M j, Y', strtotime($friend_data['created_at'])),
        'friendship_date' => date('M j, Y', strtotime($friend_data['friendship_date'])),
        'is_online' => false, // TODO: Implement online status
        'last_seen' => 'Recently active'
    ];
}

// Get users from database (excluding current user, existing friends, and users who have sent friend requests) - limit to 4 for 1 row display
$stmt = $pdo->prepare("
    SELECT u.id, u.username, u.email, u.grade_level, u.created_at 
    FROM users u
    WHERE u.id != ? 
    AND u.id NOT IN (
        SELECT CASE 
            WHEN f.user1_id = ? THEN f.user2_id
            WHEN f.user2_id = ? THEN f.user1_id
        END
        FROM friends f
        WHERE f.user1_id = ? OR f.user2_id = ?
    )
    AND u.id NOT IN (
        SELECT requester_id 
        FROM friend_requests 
        WHERE receiver_id = ? AND status = 'pending'
    )
    ORDER BY RAND() 
    LIMIT 4
");
$stmt->execute([$user_id, $user_id, $user_id, $user_id, $user_id, $user_id]);
$all_users = $stmt->fetchAll();

// Get ALL users for dropdown search (excluding current user only)
$stmt_all = $pdo->prepare("
    SELECT u.id, u.username, u.email, u.grade_level, u.created_at 
    FROM users u
    WHERE u.id != ?
    ORDER BY u.username ASC
");
$stmt_all->execute([$user_id]);
$all_users_for_search = $stmt_all->fetchAll();

// Get all pending friend requests sent by current user
$stmt = $pdo->prepare("SELECT receiver_id FROM friend_requests WHERE requester_id = ? AND status = 'pending'");
$stmt->execute([$user_id]);
$pending_requests = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Format users data for display
$suggested_friends = [];
foreach ($all_users as $suggested_user) {
    $has_pending_request = in_array($suggested_user['id'], $pending_requests);
    $suggested_friends[] = [
        'id' => $suggested_user['id'],
        'username' => $suggested_user['username'],
        'email' => $suggested_user['email'],
        'profile_image' => '../../assets/menu/defaultuser.png',
        'grade_level' => $suggested_user['grade_level'],
        'joined_date' => date('M j, Y', strtotime($suggested_user['created_at'])),
        'has_pending_request' => $has_pending_request
    ];
}

// Get pending friend requests for the current user (for notification count)
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
    <title>Word Weavers - Friends</title>
    <link rel="stylesheet" href="../../styles.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../../navigation/shared/navigation.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../../notif/toast.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="friends.css?v=<?php echo time(); ?>">
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
                    <span class="username"><?php echo htmlspecialchars(explode(' ', $user['username'])[0]); ?></span>
                </div>
                <div class="profile-dropdown">
                    <a href="#" class="profile-icon">
                        <img src="../../assets/menu/defaultuser.png" alt="Profile" class="profile-img">
                    </a>
                    <div class="profile-dropdown-content">
                        <div class="profile-dropdown-header">
                            <img src="../../assets/menu/defaultuser.png" alt="Profile" class="profile-dropdown-avatar">
                            <div class="profile-dropdown-info">
                                <div class="profile-dropdown-name"><?php echo htmlspecialchars($user['username']); ?></div>
                                <div class="profile-dropdown-email"><?php echo htmlspecialchars($user['email']); ?></div>
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

    <!-- Main Content -->
    <div class="main-content">
        <div class="friends-container">
            <!-- My Friends Section -->
            <div class="friends-section">
                <div class="section-header">
                    <div class="header-content">
                        <h2><i class="fas fa-user-friends"></i> My Friends (<?php echo count($friends); ?>)</h2>
                    </div>
                </div>
                
                <?php if (empty($friends)): ?>
                <div class="empty-state">
                    <i class="fas fa-user-friends"></i>
                    <h3>No Friends Yet</h3>
                    <p>Start connecting with other players by adding them as friends below!</p>
                </div>
                <?php else: ?>
                <div class="friends-grid">
                    <?php foreach ($friends as $friend): ?>
                    <div class="friend-card" onclick="viewProfile(<?php echo $friend['id']; ?>)">
                        <div class="friend-avatar">
                            <img src="<?php echo htmlspecialchars($friend['profile_image']); ?>" alt="<?php echo htmlspecialchars($friend['username']); ?>">
                            <div class="online-status <?php echo $friend['is_online'] ? 'online' : 'offline'; ?>"></div>
                        </div>
                        <div class="friend-info">
                            <h3><?php echo htmlspecialchars($friend['username']); ?></h3>
                            <p class="friend-status"><?php echo htmlspecialchars($friend['last_seen']); ?></p>
                        </div>
                        <div class="friend-actions">
                            <button class="action-btn message-btn" title="Send Message" onclick="event.stopPropagation();">
                                <i class="fas fa-comment"></i>
                            </button>
                            <button class="action-btn play-btn" title="Challenge to Play" onclick="event.stopPropagation();">
                                <i class="fas fa-gamepad"></i>
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- People You May Know Section -->
            <div class="suggested-section">
                <div class="section-header">
                    <h2><i class="fas fa-user-plus"></i> People You May Know</h2>
                    <div class="search-actions">
                        <div class="search-container" style="position: relative; display: flex; align-items: center; background: rgba(255, 255, 255, 0.1); border: 1px solid rgba(96, 239, 255, 0.3); border-radius: 25px; padding: 0.5rem 1rem; transition: all 0.3s ease; min-width: 250px;" onmouseenter="this.style.background='rgba(255, 255, 255, 0.15)'; this.style.borderColor='rgba(96, 239, 255, 0.6)'" onmouseleave="this.style.background='rgba(255, 255, 255, 0.1)'; this.style.borderColor='rgba(96, 239, 255, 0.3)'">
                            <i class="fas fa-search search-icon" style="color: rgba(96, 239, 255, 0.7); margin-right: 0.5rem; font-size: 0.9rem;"></i>
                            <input type="text" class="search-input" placeholder="Search for users..." id="userSearch" style="background: transparent; border: none; outline: none; color: white; font-size: 0.9rem; width: 100%; padding: 0;" oninput="showDropdown(this.value)" onfocus="this.parentElement.style.background='rgba(255, 255, 255, 0.15)'; this.parentElement.style.borderColor='rgba(96, 239, 255, 0.6)'; this.parentElement.style.boxShadow='0 0 10px rgba(96, 239, 255, 0.3)'; showDropdown(this.value)" onblur="setTimeout(() => hideDropdown(), 200)" autocomplete="off">
                            <div id="searchDropdown" class="search-dropdown" style="position: absolute; top: 100%; left: 0; right: 0; background: rgba(0, 0, 0, 0.9); border: 1px solid rgba(96, 239, 255, 0.3); border-radius: 15px; margin-top: 0.5rem; max-height: 200px; overflow-y: auto; z-index: 1000; display: none; backdrop-filter: blur(10px);">
                                <!-- Dropdown items will be populated by JavaScript -->
                            </div>
                        </div>
                        <button class="refresh-button" onclick="refreshUsers()">
                            <i class="fas fa-sync-alt"></i>
                            Refresh
                        </button>
                    </div>
                </div>
                
                <?php if (empty($suggested_friends)): ?>
                <div class="empty-state">
                    <i class="fas fa-users"></i>
                    <h3>No Other Users Found</h3>
                    <p>You're the only user in the system right now!</p>
                </div>
                <?php else: ?>
                <div class="suggested-grid" id="suggestedGrid">
                        <?php foreach ($suggested_friends as $suggested): ?>
                        <div class="suggested-card">
                            <div class="suggested-avatar" onclick="viewProfile(<?php echo $suggested['id']; ?>)">
                                <img src="<?php echo htmlspecialchars($suggested['profile_image']); ?>" alt="<?php echo htmlspecialchars($suggested['username']); ?>">
                            </div>
                            <div class="suggested-info">
                                <h3 onclick="viewProfile(<?php echo $suggested['id']; ?>)" style="cursor: pointer;">
                                    <?php echo htmlspecialchars($suggested['username']); ?>
                                    <?php if ($suggested['grade_level'] === 'Developer'): ?>
                                        <img src="../../assets/badges/developer.png" alt="Developer Badge" class="user-badge" title="Developer">
                                    <?php endif; ?>
                                </h3>
                                <p class="user-details">
                                    <span class="grade-level"><?php echo htmlspecialchars($suggested['grade_level']); ?></span>
                                    <span class="joined-date">Joined <?php echo htmlspecialchars($suggested['joined_date']); ?></span>
                                </p>
                            </div>
                            <div class="suggested-actions">
                                <?php if ($suggested['has_pending_request']): ?>
                                    <button class="cancel-request-btn" onclick="cancelFriendRequest(<?php echo $suggested['id']; ?>, '<?php echo htmlspecialchars($suggested['username']); ?>', this)">
                                        <i class="fas fa-times"></i>
                                        Cancel Request
                                    </button>
                                <?php else: ?>
                                    <button class="add-friend-btn" onclick="addFriend(<?php echo $suggested['id']; ?>, '<?php echo htmlspecialchars($suggested['username']); ?>', this)">
                                        <i class="fas fa-user-plus"></i>
                                        Add Friend
                                    </button>
                                <?php endif; ?>
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
            <h3 style="margin-bottom: 1rem; color:rgb(255, 255, 255);">Logout Confirmation</h3>
            <p style="margin-bottom: 1.5rem; color: rgba(255, 255, 255, 0.8);">Are you sure you want to logout?</p>
            <div style="display: flex; gap: 1rem; justify-content: center;">
                <button onclick="confirmLogout()" style="background: #ff6b6b; color: white; border: none; padding: 0.5rem 1rem; border-radius: 8px; cursor: pointer; font-family: 'Press Start 2P', cursive; font-size: 0.8rem;">Yes, Logout</button>
                <button onclick="hideLogoutModal()" style="background: rgba(255, 255, 255, 0.2); color: white; border: 1px solid rgba(255, 255, 255, 0.3); padding: 0.5rem 1rem; border-radius: 8px; cursor: pointer; font-family: 'Press Start 2P', cursive; font-size: 0.8rem;">Cancel</button>
            </div>
        </div>
    </div>

    <script src="../../script.js"></script>
    <script src="../../navigation/shared/profile-dropdown.js"></script>
    <script src="../../navigation/shared/notification-badge.js"></script>
    <script src="friends.js"></script>
    <script>
        // Pass user data to JavaScript
        window.userData = {
            id: <?php echo $user_id; ?>,
            username: '<?php echo htmlspecialchars($user['username']); ?>',
            email: '<?php echo htmlspecialchars($user['email']); ?>',
            gradeLevel: '<?php echo htmlspecialchars($user['grade_level']); ?>'
        };
        
        // Pass search data to JavaScript
        window.searchUsers = <?php echo json_encode($all_users_for_search); ?>;
        console.log('Search users data:', window.searchUsers);
        
        // Define dropdown functions immediately
        window.showDropdown = function(query) {
            console.log('showDropdown called with:', query);
            const dropdown = document.getElementById('searchDropdown');
            if (!dropdown) {
                console.log('Dropdown element not found!');
                return;
            }
            
            const searchTerm = query.toLowerCase().trim();
            console.log('Search term:', searchTerm);
            
            if (searchTerm.length === 0) {
                window.hideDropdown();
                return;
            }
            
            // Check if searchUsers data is available
            if (!window.searchUsers) {
                console.log('searchUsers data not available');
                dropdown.innerHTML = `
                    <div style="padding: 1rem; text-align: center; color: rgba(255, 255, 255, 0.7);">
                        <p style="margin: 0; font-size: 0.9rem;">Loading users...</p>
                    </div>
                `;
                dropdown.style.display = 'block';
                return;
            }
            
            // Filter users based on search term
            const filteredUsers = window.searchUsers.filter(user => 
                user.username.toLowerCase().includes(searchTerm) ||
                user.grade_level.toLowerCase().includes(searchTerm)
            );
            
            console.log('Filtered users:', filteredUsers.length);
            
            if (filteredUsers.length === 0) {
                dropdown.innerHTML = `
                    <div style="padding: 1rem; text-align: center; color: rgba(255, 255, 255, 0.7);">
                        <i class="fas fa-search" style="font-size: 1.5rem; margin-bottom: 0.5rem; color: rgba(96, 239, 255, 0.5);"></i>
                        <p style="margin: 0; font-size: 0.9rem;">No users found</p>
                    </div>
                `;
            } else {
                dropdown.innerHTML = filteredUsers.map(user => `
                    <div class="dropdown-item" onclick="selectUser(${user.id}, '${user.username}')" style="padding: 0.8rem 1rem; cursor: pointer; border-bottom: 1px solid rgba(96, 239, 255, 0.1); transition: background 0.2s ease; display: flex; align-items: center; gap: 0.8rem;" onmouseenter="this.style.background='rgba(96, 239, 255, 0.1)'" onmouseleave="this.style.background='transparent'">
                        <img src="../../assets/menu/defaultuser.png" alt="${user.username}" style="width: 35px; height: 35px; border-radius: 50%; border: 2px solid rgba(96, 239, 255, 0.3);">
                        <div style="flex: 1;">
                            <div style="color: white; font-weight: 600; font-size: 0.9rem; margin-bottom: 0.2rem;">${user.username}</div>
                            <div style="color: rgba(96, 239, 255, 0.8); font-size: 0.8rem;">${user.grade_level}</div>
                        </div>
                        <i class="fas fa-user-plus" style="color: rgba(96, 239, 255, 0.6); font-size: 0.8rem;"></i>
                    </div>
                `).join('');
            }
            
            dropdown.style.display = 'block';
            console.log('Dropdown should be visible now');
        };
        
        window.hideDropdown = function() {
            const dropdown = document.getElementById('searchDropdown');
            if (dropdown) {
                dropdown.style.display = 'none';
            }
        };
        
        window.selectUser = function(userId, username) {
            // Hide dropdown
            window.hideDropdown();
            
            // Clear search input
            const searchInput = document.getElementById('userSearch');
            if (searchInput) {
                searchInput.value = '';
            }
            
            // Navigate to user profile
            window.location.href = `user-profile.php?id=${userId}`;
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

        // View profile functionality
        function viewProfile(userId) {
            // Navigate to user profile page
            window.location.href = `user-profile.php?id=${userId}`;
        }
    </script>
</body>
</html>
