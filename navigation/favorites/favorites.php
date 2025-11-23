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

// Handle add/remove favorite
if ($_POST && isset($_POST['action'])) {
    $game_type = $_POST['game_type'] ?? '';
    
    if ($_POST['action'] === 'add_favorite') {
        // Check if already favorited
        $stmt = $pdo->prepare("SELECT id FROM user_favorites WHERE user_id = ? AND game_type = ?");
        $stmt->execute([$user_id, $game_type]);
        
        if (!$stmt->fetch()) {
            $stmt = $pdo->prepare("INSERT INTO user_favorites (user_id, game_type) VALUES (?, ?)");
            $stmt->execute([$user_id, $game_type]);
        }
        echo json_encode(['success' => true, 'message' => 'Added to favorites!']);
    } elseif ($_POST['action'] === 'remove_favorite') {
        $stmt = $pdo->prepare("DELETE FROM user_favorites WHERE user_id = ? AND game_type = ?");
        $stmt->execute([$user_id, $game_type]);
        echo json_encode(['success' => true, 'message' => 'Removed from favorites!']);
    }
    exit();
}

// Get user's favorite games
$stmt = $pdo->prepare("SELECT game_type FROM user_favorites WHERE user_id = ?");
$stmt->execute([$user_id]);
$favorite_games = [];
while ($row = $stmt->fetch()) {
    $favorite_games[] = $row['game_type'];
}

// Get all available games information
$games_info = [
    'grammar-heroes' => [
        'name' => 'Grammar Heroes',
        'description' => 'Battle grammar challenges by correcting sentences, and unlock new levels.',
        'logo' => '../../assets/selection/Grammarlogo.webp',
        'bg' => '../../assets/selection/Grammarbg.webp'
    ],
    'vocabworld' => [
        'name' => 'Vocabworld',
        'description' => 'Practice word skills and earn points to customize your character.',
        'logo' => '../../assets/selection/vocablogo.webp',
        'bg' => '../../assets/selection/vocabbg.webp'
    ]
];

// Get all available games (not just favorites)
$all_games = array_keys($games_info);

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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../../assets/menu/ww_logo_main.webp">
    <title>My Favorites - Word Weavers</title>
    <link rel="stylesheet" href="../../styles.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../shared/navigation.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../../notif/toast.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="favorites.css?v=<?php echo time(); ?>">
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
            <a href="favorites.php" class="nav-link active">
                <i class="fas fa-star"></i>
                <span>Favorites</span>
            </a>
            <a href="../friends/friends.php" class="nav-link">
                <i class="fas fa-users"></i>
                <span>Friends</span>
            </a>
            <a href="../profile/profile.php" class="nav-link">
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
                            <a href="../profile/profile.php" class="profile-dropdown-item">
                                <i class="fas fa-user"></i>
                                <span>View Profile</span>
                            </a>
                            <a href="favorites.php" class="profile-dropdown-item">
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
        <div class="favorites-container">
            <div class="favorites-header">
                <div class="header-left">
                    <h1 class="favorites-title" id="section-title">
                        <img src="../../assets/pixels/star.png" alt="Star" style="width: 24px; height: 24px; vertical-align: middle;"> My Favorites
                    </h1>
                </div>
                <div class="header-right">
                    <div class="view-toggle">
                        <button class="toggle-btn active" id="favorites-view-btn" onclick="showFavoritesView()">
                            <img src="../../assets/pixels/star.png" alt="Star" style="width: 16px; height: 16px; vertical-align: middle;"> My Favorites
                        </button>
                        <button class="toggle-btn" id="browse-view-btn" onclick="showBrowseView()">
                            <img src="../../assets/pixels/diamondsword.png" alt="Diamond Sword" style="width: 16px; height: 16px; vertical-align: middle;"> Browse Games
                        </button>
                    </div>
                </div>
            </div>

            <!-- My Favorites Section -->
            <div id="favorites-section" class="content-section active">
                <div class="favorites-grid">
                    <?php if (empty($favorite_games)): ?>
                        <div class="empty-state">
                            <i class="fas fa-star"></i>
                            <h2>No Favorites Yet</h2>
                            <p>Start playing games and add them to your favorites!</p>
                            <button class="browse-games-btn" onclick="showBrowseView()">Browse Games</button>
                        </div>
                    <?php else: ?>
                        <?php foreach ($favorite_games as $game_type): ?>
                            <?php if (isset($games_info[$game_type])): ?>
                                <?php $game = $games_info[$game_type]; ?>
                                <div class="favorite-game-card" data-game="<?php echo $game_type; ?>">
                                    <div class="game-bg" style="background-image: url('<?php echo $game['bg']; ?>')"></div>
                                    <div class="game-content">
                                        <div class="game-logo">
                                            <img src="<?php echo $game['logo']; ?>" alt="<?php echo $game['name']; ?>">
                                        </div>
                                        <h3><?php echo $game['name']; ?></h3>
                                        <div class="game-actions">
                                            <button class="remove-favorite-btn" onclick="removeFavorite('<?php echo $game_type; ?>')">
                                                <i class="fas fa-heart-broken"></i> Remove
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Browse Games Section -->
            <div id="browse-section" class="content-section">
                <div class="browse-games-grid">
                    <?php foreach ($all_games as $game_type): ?>
                        <?php if (isset($games_info[$game_type])): ?>
                            <?php 
                            $game = $games_info[$game_type];
                            $is_favorited = in_array($game_type, $favorite_games);
                            ?>
                            <div class="browse-game-card" data-game="<?php echo $game_type; ?>">
                                <div class="game-bg" style="background-image: url('<?php echo $game['bg']; ?>')"></div>
                                <div class="game-content">
                                    <div class="game-logo">
                                        <img src="<?php echo $game['logo']; ?>" alt="<?php echo $game['name']; ?>">
                                    </div>
                                    <h3><?php echo $game['name']; ?></h3>
                                    <div class="game-actions">
                                        <?php if ($is_favorited): ?>
                                            <button class="remove-favorite-btn" onclick="removeFavorite('<?php echo $game_type; ?>')" data-favorited="true">
                                                <i class="fas fa-heart"></i> Favorited
                                            </button>
                                        <?php else: ?>
                                            <button class="add-favorite-btn" onclick="addFavorite('<?php echo $game_type; ?>')" data-favorited="false">
                                                <i class="far fa-heart"></i> Add to Favorites
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="toast-overlay"></div>
    <div id="toast" class="toast"></div>
    <script src="../../script.js"></script>
    <script src="favorites.js"></script>
    <script src="../shared/profile-dropdown.js"></script>
    <script src="../shared/notification-badge.js"></script>
    <script>
        // Pass user data to JavaScript
        window.userData = {
            id: <?php echo $user_id; ?>,
            username: '<?php echo htmlspecialchars($user['username']); ?>',
            email: '<?php echo htmlspecialchars($user['email']); ?>',
            gradeLevel: '<?php echo htmlspecialchars($user['grade_level']); ?>'
        };
        
        // Pass favorite games data
        window.favoriteGames = <?php echo json_encode($favorite_games); ?>;
        
        // Pass all games info for dynamic card creation
        window.gamesInfo = <?php echo json_encode($games_info); ?>;
        
        // Game functions removed - play functionality not needed
        
        function addFavorite(gameType) {
            fetch('favorites.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=add_favorite&game_type=' + encodeURIComponent(gameType)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast(data.message);
                    
                    // Update button state in browse view
                    const browseCard = document.querySelector(`#browse-section [data-game="${gameType}"]`);
                    if (browseCard) {
                        const button = browseCard.querySelector('.add-favorite-btn, .remove-favorite-btn');
                        if (button) {
                            button.className = 'remove-favorite-btn';
                            button.setAttribute('data-favorited', 'true');
                            button.setAttribute('onclick', `removeFavorite('${gameType}')`);
                            button.innerHTML = '<i class="fas fa-heart"></i> Favorited';
                        }
                    }
                    
                    // Add new card to favorites section instantly
                    addGameCardToFavorites(gameType);
                    
                    // Update window favorites data
                    if (!window.favoriteGames.includes(gameType)) {
                        window.favoriteGames.push(gameType);
                    }
                } else {
                    showToast('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Error adding favorite');
            });
        }
        
        // Function to dynamically add a game card to favorites section
        function addGameCardToFavorites(gameType) {
            const gameInfo = window.gamesInfo[gameType];
            if (!gameInfo) return;
            
            const favoritesGrid = document.querySelector('#favorites-section .favorites-grid');
            const emptyState = document.querySelector('#favorites-section .empty-state');
            
            // Check if card already exists to prevent duplicates
            const existingCard = document.querySelector(`#favorites-section [data-game="${gameType}"]`);
            if (existingCard) {
                return; // Card already exists, don't add duplicate
            }
            
            // Remove empty state if it exists
            if (emptyState) {
                emptyState.remove();
            }
            
            // Create the new favorite card
            const newCard = document.createElement('div');
            newCard.className = 'favorite-game-card';
            newCard.setAttribute('data-game', gameType);
            newCard.innerHTML = `
                <div class="game-bg" style="background-image: url('${gameInfo.bg}')"></div>
                <div class="game-content">
                    <div class="game-logo">
                        <img src="${gameInfo.logo}" alt="${gameInfo.name}">
                    </div>
                    <h3>${gameInfo.name}</h3>
                    <div class="game-actions">
                        <button class="remove-favorite-btn" onclick="removeFavorite('${gameType}')">
                            <i class="fas fa-heart-broken"></i> Remove
                        </button>
                    </div>
                </div>
            `;
            
            // Add the new card to the favorites grid
            favoritesGrid.appendChild(newCard);
        }

        function removeFavorite(gameType) {
            fetch('favorites.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=remove_favorite&game_type=' + encodeURIComponent(gameType)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast(data.message);
                    
                    // Remove from favorites view
                    const favCard = document.querySelector(`#favorites-section [data-game="${gameType}"]`);
                    if (favCard) {
                        favCard.remove();
                    }
                    
                    // Update button state in browse view
                    const browseCard = document.querySelector(`#browse-section [data-game="${gameType}"]`);
                    if (browseCard) {
                        const button = browseCard.querySelector('.add-favorite-btn, .remove-favorite-btn');
                        if (button) {
                            button.className = 'add-favorite-btn';
                            button.setAttribute('data-favorited', 'false');
                            button.setAttribute('onclick', `addFavorite('${gameType}')`);
                            button.innerHTML = '<i class="far fa-heart"></i> Add to Favorites';
                        }
                    }
                    
                    // Check if no favorites left and update favorites view
                    const remainingCards = document.querySelectorAll('#favorites-section .favorite-game-card');
                    if (remainingCards.length === 0) {
                        // Check if empty state already exists to prevent duplicates
                        const existingEmptyState = document.querySelector('#favorites-section .empty-state');
                        if (!existingEmptyState) {
                            // Add empty state to favorites section
                            const favoritesGrid = document.querySelector('#favorites-section .favorites-grid');
                            if (favoritesGrid) {
                                const emptyState = document.createElement('div');
                                emptyState.className = 'empty-state';
                                emptyState.innerHTML = `
                                    <i class="fas fa-star"></i>
                                    <h2>No Favorites Yet</h2>
                                    <p>Start playing games and add them to your favorites!</p>
                                    <button class="browse-games-btn" onclick="showBrowseView()">Browse Games</button>
                                `;
                                favoritesGrid.appendChild(emptyState);
                            }
                        }
                        
                        // Auto refresh page after a short delay to ensure clean state
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
                    }
                    
                    // Update window favorites data
                    const index = window.favoriteGames.indexOf(gameType);
                    if (index > -1) {
                        window.favoriteGames.splice(index, 1);
                    }
                } else {
                    showToast('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Error removing favorite');
            });
        }

        // View toggle functions
        function showFavoritesView() {
            document.getElementById('favorites-section').classList.add('active');
            document.getElementById('browse-section').classList.remove('active');
            document.getElementById('favorites-view-btn').classList.add('active');
            document.getElementById('browse-view-btn').classList.remove('active');
            document.getElementById('section-title').innerHTML = '<img src="../../assets/pixels/star.png" alt="Star" style="width: 24px; height: 24px; vertical-align: middle;"> My Favorites';
        }

        function showBrowseView() {
            document.getElementById('favorites-section').classList.remove('active');
            document.getElementById('browse-section').classList.add('active');
            document.getElementById('favorites-view-btn').classList.remove('active');
            document.getElementById('browse-view-btn').classList.add('active');
            document.getElementById('section-title').innerHTML = '<img src="../../assets/pixels/diamondsword.png" alt="Diamond Sword" style="width: 24px; height: 24px; vertical-align: middle;"> Browse Games';
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
            window.location.href = '../../onboarding/logout.php';
        }
    </script>
    
    <!-- Logout Confirmation Modal -->
    <div class="toast-overlay" id="logoutModal">
        <div class="toast" id="logoutConfirmation">
            <h3 style="margin-bottom: 1rem; color: #ff6b6b;">Logout Confirmation</h3>
            <p style="margin-bottom: 1.5rem; color: rgba(255, 255, 255, 0.8);">Are you sure you want to logout?</p>
            <div style="display: flex; gap: 1rem; justify-content: center;">
                <button onclick="confirmLogout()" style="background: #ff6b6b; color: white; border: none; padding: 0.5rem 1rem; border-radius: 8px; cursor: pointer; font-family: 'Press Start 2P', cursive; font-size: 0.8rem;">Yes, Logout</button>
                <button onclick="hideLogoutModal()" style="background: rgba(255, 255, 255, 0.2); color: white; border: 1px solid rgba(255, 255, 255, 0.3); padding: 0.5rem 1rem; border-radius: 8px; cursor: pointer; font-family: 'Press Start 2P', cursive; font-size: 0.8rem;">Cancel</button>
            </div>
        </div>
    </div>
</body>
</html>
