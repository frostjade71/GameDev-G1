<?php
require_once '../../onboarding/config.php';
require_once '../../includes/greeting.php';

// Check if user is logged in, redirect to login if not
if (!isLoggedIn()) {
    header('Location: ../../onboarding/login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Get user information
$stmt = $pdo->prepare("SELECT username, email, grade_level FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    // User not found, destroy session and redirect to login
    session_destroy();
    header('Location: ../../onboarding/login.php');
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

// Get user's game statistics for leaderboard
$stmt = $pdo->prepare("
    SELECT 
        u.id as user_id,
        u.username,
        COALESCE(ue.essence_amount, 0) as essence,
        COALESCE(us.current_shards, 0) as shards,
        COALESCE(gp.player_level, 1) as level,
        COALESCE(gp.total_monsters_defeated, 0) as monsters_defeated,
        COALESCE((
            SELECT COUNT(*) 
            FROM character_selections 
            WHERE user_id = u.id
        ), 0) as characters_owned,
        COALESCE(ug.gwa, 0) as gwa
    FROM users u
    LEFT JOIN user_essence ue ON u.id = ue.user_id
    LEFT JOIN user_shards us ON u.id = us.user_id
    LEFT JOIN game_progress gp ON u.id = gp.user_id AND gp.game_type = 'vocabworld'
    LEFT JOIN user_gwa ug ON u.id = ug.user_id AND ug.game_type = 'vocabworld'
    ORDER BY level DESC, monsters_defeated DESC, gwa DESC
    LIMIT 100
");
$stmt->execute();
$leaderboard_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap" rel="stylesheet">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/webp" href="../../assets/menu/ww_logo_main.webp">
    <title>Leaderboards - Word Weavers</title>
    <link rel="stylesheet" href="../../styles.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../shared/navigation.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="leaderboards.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../../notif/toast.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        /* Hide leaderboard content initially */
        .leaderboard-content {
            display: none;
        }
        
        /* Show loading indicator by default */
        #loadingIndicator {
            display: block;
            text-align: center;
            padding: 20px;
            font-size: 18px;
            color: #666;
        }
    </style>
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
            <a href="../favorites/favorites.php" class="nav-link">
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

    <div class="main-content">
        <div class="leaderboard-container leaderboard-content">
            <!-- Loading Indicator -->
            <div id="loadingIndicator">
                <div class="loading-content">
                    <i class="fas fa-spinner fa-spin"></i>
                    <div>Loading Leaderboard</div>
                </div>
            </div>
            <div class="settings-header">
                <h1 class="settings-title">Leaderboards</h1>
            </div>

            <div class="leaderboard-section">
                <!-- Game Selection - VocabWorld Only -->
                <div class="game-logo-container">
                    <img src="../../MainGame/vocabworld/assets/menu/vocab_new.png" alt="VocabWorld" class="game-logo">
                </div>
                <div class="section-separator"></div>
                
                <!-- Sort Dropdown -->
                <div class="leaderboard-filters">
                    <div class="filter-group">
                        <div class="dropdown">
                            <button class="dropdown-toggle" id="sortByDropdown" data-sort="level" data-sort-dir="desc" title="Sort by Level">
                                <img src="../../MainGame/vocabworld/assets/stats/level.png" alt="Level" class="dropdown-icon">
                                <i class="fas fa-chevron-down"></i>
                            </button>
                            <div class="dropdown-menu">
                                <a href="#" class="dropdown-item" data-sort="level" data-sort-dir="desc">
                                    <img src="../../MainGame/vocabworld/assets/stats/level.png" alt="Level">
                                    <span>Level</span>
                                </a>
                                <a href="#" class="dropdown-item" data-sort="monsters_defeated" data-sort-dir="desc">
                                    <img src="../../MainGame/vocabworld/assets/stats/sword1.png" alt="Monsters Defeated">
                                    <span>Monsters Defeated</span>
                                </a>
                                <a href="#" class="dropdown-item" data-sort="essence" data-sort-dir="desc">
                                    <img src="../../MainGame/vocabworld/assets/currency/essence.png" alt="Essence">
                                    <span>Essence</span>
                                </a>
                                <a href="#" class="dropdown-item" data-sort="shards" data-sort-dir="desc">
                                    <img src="../../MainGame/vocabworld/assets/currency/shard1.png" alt="Shards">
                                    <span>Shards</span>
                                </a>
                                <a href="#" class="dropdown-item" data-sort="characters_owned" data-sort-dir="desc">
                                    <img src="../../MainGame/vocabworld/assets/stats/characters_owned.png" alt="Characters Owned">
                                    <span>Characters Owned</span>
                                </a>
                                <a href="#" class="dropdown-item" data-sort="gwa" data-sort-dir="desc">
                                    <img src="../../MainGame/vocabworld/assets/stats/gwa.png" alt="GWA">
                                    <span>GWA</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Loading Indicator -->
                <div id="loadingIndicator" style="display: none; text-align: center; padding: 20px;">
                    <i class="fas fa-spinner fa-spin"></i> Loading leaderboard...
                </div>
                
                <!-- Leaderboard Content -->
                <!-- Podium for Top 3 -->
                <div class="podium-container">
                    <?php 
                    $top_players = array_slice($leaderboard_data, 0, 3);
                    $podium_order = [1 => $top_players[1] ?? null, 0 => $top_players[0] ?? null, 2 => $top_players[2] ?? null];
                    foreach ($podium_order as $pos => $player): 
                        if (!$player) continue;
                        $rank = array_search($player, $top_players) + 1;
                    ?>
                        <div class="podium-place rank-<?php echo $rank; ?> <?php echo $player['user_id'] == $user_id ? 'current-user' : ''; ?>" data-rank="<?php echo $rank; ?>" onclick="viewProfile(<?php echo $player['user_id']; ?>)" style="cursor: pointer;">
                            <div class="podium-avatar">
                                <img src="../../assets/menu/defaultuser.png" alt="<?php echo htmlspecialchars($player['username']); ?>" class="podium-img">
                            </div>
                            <div class="podium-rank">#<?php echo $rank; ?></div>
                            <div class="podium-name"><?php echo htmlspecialchars($player['username']); ?></div>
                            <div class="podium-score">
                                <span class="score-value"><?php echo number_format($player['gwa'] ?? 0, 2); ?></span>
                                <span class="score-label">GWA</span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Table for Ranks 4-10 -->
                <div class="leaderboard-table-container" style="margin-top: 30px;">
                    <table class="leaderboard-table">
                        <thead>
                            <tr>
                                <th>Rank</th>
                                <th>Player</th>
                                <th class="sortable" data-sort="gwa">GWA <i class="sort-icon"></i></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $other_players = array_slice($leaderboard_data, 3, 7); // Get ranks 4-10
                            foreach ($other_players as $index => $player): 
                                $rowClass = [];
                                if ($player['user_id'] == $user_id) {
                                    $rowClass[] = 'current-user';
                                }
                            ?>
                            <tr class="<?php echo implode(' ', $rowClass); ?>" onclick="viewProfile(<?php echo $player['user_id']; ?>)" style="cursor: pointer;">
                                <td class="rank"><?php echo $index + 4; ?></td>
                                <td class="player-name"><?php echo htmlspecialchars($player['username']); ?></td>
                                <td><?php echo number_format($player['gwa'] ?? 0, 2); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Separator Line -->
                <div class="leaderboard-separator"></div>
                
                <!-- Personal Rank Section -->
                <div class="personal-rank-section" style="margin: 10px auto 0; max-width: 600px; width: 95%; text-align: center;">
                    <div class="personal-rank-container" style="background: rgba(0, 0, 0, 0.3); border: 1px solid #4da6ff; border-radius: 4px; padding: 3px; margin: 0 auto; max-width: 200px; box-shadow: 0 0 4px rgba(77, 166, 255, 0.3); transition: all 0.3s ease;">
                        <div id="personal-rank-title" style="font-family: 'Press Start 2P', monospace; font-size: 0.7em; color: #fff; text-align: center; padding: 2px; letter-spacing: -0.5px; line-height: 1.2;">
                            Loading...
                        </div>
                    </div>
                            </tbody>
                        </table>
                    </div>
                </div>
                
            </div> <!-- Close leaderboard-section -->
        </div> <!-- Close leaderboard-container -->
    </div> <!-- Close main-content -->
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="../shared/navigation.js?v=<?php echo time(); ?>"></script>
    <script src="../../script.js?v=<?php echo time(); ?>"></script>
    <script></script>
    <script>
        // Global variables
        let currentSort = 'level';
        let currentSortDir = 'desc';
        const currentUserId = <?php echo json_encode($user_id); ?>;
        
        // Function to load leaderboard data
        function loadLeaderboard(sortBy = 'level', sortDir = 'desc') {
            // Show loading indicator
            $('#loadingIndicator').show();
            
            // Update current sort values
            currentSort = sortBy;
            currentSortDir = sortDir;
            
            // Make AJAX request to get sorted data
            $.ajax({
                url: 'get_leaderboard.php',
                method: 'GET',
                data: {
                    sort: sortBy,
                    sort_dir: sortDir,
                    user_id: currentUserId
                },
                dataType: 'json',
                success: function(response) {
                    console.log('Leaderboard response:', response); // Debug log
                    if (response && response.success) {
                        updateLeaderboardUI(response);
                    } else {
                        console.error('Error loading leaderboard:', response ? response.message : 'No response');
                        alert('Failed to load leaderboard. Please try again.');
                        // Hide loading indicator on error
                        $('#loadingIndicator').hide();
                        $('.leaderboard-content').show();
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', status, error);
                    alert('An error occurred while loading the leaderboard.');
                },
                complete: function() {
                    // Show the content
                    $('.leaderboard-content').fadeIn(300);
                    // The loading indicator will be hidden by updateLeaderboardUI
                }
            });
        }
        
        // Function to update the UI with new leaderboard data
        function updateLeaderboardUI(response) {
            console.log('Updating UI with data:', response); // Debug log
            if (!response || !response.data) {
                console.warn('No leaderboard data received');
                // Hide loading indicator
                $('#loadingIndicator').hide();
                $('.leaderboard-content').show();
                return;
            }
            
            const leaderboardData = response.data || [];
            
            // Update the podium (top 3)
            updatePodium(leaderboardData.slice(0, 3));
            
            // Update the table (ranks 4-10)
            updateLeaderboardTable(leaderboardData.slice(3, 10));
            
            // Update the personal rank
            updatePersonalRank(response.user_rank);
            
            // Hide loading indicator with fade out effect
            $('#loadingIndicator').addClass('hidden');
            
            // Show the content after the first data load
            if ($('.leaderboard-content').is(':hidden')) {
                $('.leaderboard-content').show();
            }
            
            // Remove loading indicator from DOM after animation completes
            setTimeout(() => {
                $('#loadingIndicator').remove();
            }, 500);
        }
        
        // Function to update the personal rank section
        function updatePersonalRank(userRank) {
            const titleElement = $('#personal-rank-title');
            
            if (!userRank) {
                titleElement.text('Rank not available');
                return;
            }
            
            // Get the current sort column name for display
            const sortNames = {
                'level': 'Level',
                'gwa': 'GWA',
                'essence': 'Essence',
                'shards': 'Shards',
                'monsters_defeated': 'Monsters Defeated',
                'characters_owned': 'Characters Owned'
            };
            
            const sortName = sortNames[currentSort] || currentSort;
            let rankColor, borderColor, shadowColor;
            
            // Set colors based on rank
            if (userRank.rank === 1) {
                rankColor = '#FFD700'; // Gold
                borderColor = 'rgba(255, 215, 0, 0.8)';
                shadowColor = 'rgba(255, 215, 0, 0.4)';
            } else if (userRank.rank === 2) {
                rankColor = '#C0C0C0'; // Silver
                borderColor = 'rgba(192, 192, 192, 0.8)';
                shadowColor = 'rgba(192, 192, 192, 0.4)';
            } else if (userRank.rank === 3) {
                rankColor = '#CD7F32'; // Bronze
                borderColor = 'rgba(205, 127, 50, 0.8)';
                shadowColor = 'rgba(205, 127, 50, 0.4)';
            } else {
                rankColor = '#fff'; // Default white
                borderColor = 'rgba(77, 166, 255, 0.8)';
                shadowColor = 'rgba(77, 166, 255, 0.3)';
            }
            
            // Update container styles
            $('.personal-rank-container').css({
                'border-color': borderColor,
                'box-shadow': `0 0 4px ${shadowColor}`
            });
            
            // Update rank text with color
            titleElement.html(`<span style="font-family: 'Press Start 2P', monospace; color: ${rankColor};">Rank #${userRank.rank}</span> in <span style="font-family: 'Press Start 2P', monospace;">${sortName}</span>`);
        }
        
        // Function to update the podium section
        function updatePodium(topPlayers) {
            const podiumContainer = $('.podium-container');
            podiumContainer.empty();
            
            // Podium order: 2nd, 1st, 3rd
            const podiumOrder = [1, 0, 2];
            
            podiumOrder.forEach(pos => {
                const player = topPlayers[pos];
                if (!player) return;
                
                const rank = pos + 1;
                const isCurrentUser = player.user_id == currentUserId;
                
                // Get first word of username for mobile
                const username = player.username.split(' ')[0];
                const podiumHtml = `
                    <div class="podium-place rank-${rank} ${isCurrentUser ? 'current-user' : ''}" data-rank="${rank}" style="cursor: pointer;" onclick="viewProfile(${player.user_id})">
                        <div class="podium-avatar">
                            <img src="../../assets/menu/defaultuser.png" alt="${username}" class="podium-img">
                        </div>
                        <div class="podium-rank">#${rank}</div>
                        <div class="podium-name" title="${player.username}">${username}</div>
                        <div class="podium-score">
                            <span class="score-value">${formatScore(player[currentSort])}</span>
                        </div>
                    </div>
                `;
                
                podiumContainer.append(podiumHtml);
            });
        }
        
        // Function to update the table header with current sort
        function updateTableHeader() {
            const $headerCell = $('.leaderboard-table thead th[data-sort]');
            const sortText = currentSort.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase());
            $headerCell.html(`${sortText} <i class="sort-icon"></i>`);
        }
        
        // Function to update the leaderboard table
        function updateLeaderboardTable(players) {
            const tbody = $('.leaderboard-table tbody');
            tbody.empty();
            
            // Update the table header to reflect current sort
            updateTableHeader();
            
            if (!players || players.length === 0) {
                tbody.append('<tr><td colspan="3" class="text-center">No data available</td></tr>');
                return;
            }
            
            // Only show up to 7 players (ranks 4-10)
            const playersToShow = players.slice(0, 7);
            
            playersToShow.forEach((player, index) => {
                const rank = index + 4; // Start from rank 4
                const isCurrentUser = player.user_id == currentUserId;
                
                const rowHtml = `
                    <tr class="rank-${rank} ${isCurrentUser ? 'current-user' : ''}" style="cursor: pointer;" onclick="viewProfile(${player.user_id})">
                        <td class="rank">${rank}</td>
                        <td class="player-name">${player.username}</td>
                        <td>${formatScore(player[currentSort])}</td>
                    </tr>
                `;
                
                tbody.append(rowHtml);
            });
        }
        
        // Helper function to format score based on type
        function formatScore(value) {
            if (value === undefined || value === null) return '0';
            
            // Format numbers with commas
            if (currentSort === 'gwa') {
                return parseFloat(value).toFixed(2);
            } else if (['level', 'monsters_defeated', 'essence', 'shards', 'characters_owned'].includes(currentSort)) {
                return parseInt(value).toLocaleString();
            }
            return value;
        }

        // Function to handle sort button click
        function sortBy(criteria) {
            // Don't do anything if already sorting
            if ($('#loadingIndicator').is(':visible')) return;
            
            // Show loading indicator
            $('.leaderboard-content').fadeOut(200, function() {
                $('#loadingIndicator').fadeIn(200);
                
                // Toggle sort direction if clicking the same criteria
                if (currentSort === criteria) {
                    currentSortDir = currentSortDir === 'asc' ? 'desc' : 'asc';
                } else {
                    // Default to descending for most sorts, ascending for GWA (lower is better)
                    currentSortDir = criteria === 'gwa' ? 'asc' : 'desc';
                    currentSort = criteria;
                }
                
                // Update the active state of sort buttons
                $('.sort-btn').removeClass('active');
                $(`.sort-btn[data-sort="${criteria}"]`).addClass('active');
                
                // Update sort direction indicator
                $('.sort-direction').remove();
                const directionIcon = currentSortDir === 'asc' ? '↑' : '↓';
                $(`.sort-btn[data-sort="${criteria}"]`).append(`<span class="sort-direction">${directionIcon}</span>`);
                
                // Reload leaderboard with new sort
                loadLeaderboard(criteria, currentSortDir);
            });
        }

        // Load initial data when the page loads
        $(document).ready(function() {
            // Initial load of leaderboard data
            loadLeaderboard(currentSort, currentSortDir);
            
            // Handle tab switching
            $('.game-tab').on('click', function() {
                const tabName = $(this).data('tab');
                
                // Update active tab
                $('.game-tab').removeClass('active');
                $(this).addClass('active');
                
                // Show loading indicator
                $('#loadingIndicator').show();
                
                // Hide all leaderboard sections
                $('.leaderboard-content > div').hide();
                
                if (tabName === 'vocabworld') {
                    // Show the vocabworld leaderboard content
                    $('.podium-container, .leaderboard-table-container').show();
                    loadLeaderboard(currentSort, currentSortDir);
                } else {
                    // No other tabs currently available
                }
            });

            // Handle dropdown toggle
            $(document).on('click', '.dropdown-toggle', function(e) {
                e.preventDefault();
                e.stopPropagation();
                $('.dropdown').not($(this).closest('.dropdown')).removeClass('open');
                $(this).closest('.dropdown').toggleClass('open');
            });

            // Close dropdown when clicking outside
            $(document).on('click', function(e) {
                if (!$(e.target).closest('.dropdown').length) {
                    $('.dropdown').removeClass('open');
                }
            });

            // Handle sort selection
            $(document).on('click', '.dropdown-item', function(e) {
                e.preventDefault();
                e.stopPropagation();

                const sortBy = $(this).data('sort');
                const sortDir = 'desc'; // Default to descending
                const columnName = $(this).text().trim();

                // Update dropdown button content
                const $dropdown = $(this).closest('.dropdown');
                const $dropdownToggle = $dropdown.find('.dropdown-toggle');
                const iconSrc = $(this).find('img').attr('src');
                const iconAlt = $(this).find('img').attr('alt');
                
                // Update toggle button content
                $dropdownToggle.attr('data-sort', sortBy);
                $dropdownToggle.attr('data-sort-dir', sortDir);
                $dropdownToggle.attr('title', 'Sort by ' + columnName);
                $dropdownToggle.find('img').attr('src', iconSrc).attr('alt', iconAlt);

                // Close dropdown
                $dropdown.removeClass('open');

                // Reload leaderboard with new sort
                loadLeaderboard(sortBy, sortDir);
            });

            // Initial load
            loadLeaderboard();
        });

        // Logout function
        function showLogoutModal() {
            if (confirm('Are you sure you want to log out?')) {
                window.location.href = '../../onboarding/logout.php';
            }
        }
    </script>

    <!-- Logout Confirmation Modal -->
    <div class="toast-overlay" id="logoutModal">
        <div class="toast" id="logoutConfirmation">
            <div class="toast-header">
                <h3>Sign Out</h3>
                <button class="close-btn" onclick="closeLogoutModal()">&times;</button>
            </div>
            <div class="toast-body">
                <p>Are you sure you want to sign out?</p>
                <div class="toast-actions">
                    <button class="btn btn-secondary" onclick="closeLogoutModal()">Cancel</button>
                    <button class="btn btn-primary" onclick="logout()">Sign Out</button>
                </div>
            </div>
        </div>
    </div>

    <div class="toast-overlay"></div>
    <div id="toast" class="toast"></div>

    <script>
        // Profile dropdown toggle
        document.querySelector('.profile-icon').addEventListener('click', function(e) {
            e.preventDefault();
            document.querySelector('.profile-dropdown-content').classList.toggle('show');
        });

        // Close dropdown when clicking outside
        window.addEventListener('click', function(e) {
            if (!e.target.matches('.profile-icon, .profile-icon *')) {
                const dropdowns = document.getElementsByClassName('profile-dropdown-content');
                for (let i = 0; i < dropdowns.length; i++) {
                    const openDropdown = dropdowns[i];
                    if (openDropdown.classList.contains('show')) {
                        openDropdown.classList.remove('show');
                    }
                }
            }
        });

        // Functions for friend requests
        function handleFriendRequest(action, requestId, element) {
            fetch('../../api/handle_friend_request.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=${action}&request_id=${requestId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    element.remove();
                    updateNotificationCount();
                    showToast(data.message, 'success');
                    
                    // If no more notifications, update the UI
                    const notificationItems = document.querySelectorAll('.notification-item');
                    if (notificationItems.length === 0) {
                        const notificationList = document.querySelector('.notification-list');
                        notificationList.innerHTML = `
                            <div class="no-notifications">
                                <i class="far fa-bell-slash"></i>
                                <p>No new notifications</p>
                            </div>
                        `;
                        const markAllReadBtn = document.getElementById('markAllRead');
                        if (markAllReadBtn) markAllReadBtn.style.display = 'none';
                    }
                } else {
                    showToast(data.message || 'An error occurred', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('An error occurred', 'error');
            });
        }

        function updateNotificationCount() {
            const badge = document.querySelector('.notification-badge');
            const count = document.querySelectorAll('.notification-item').length;
            
            if (count === 0 && badge) {
                badge.remove();
            } else if (badge) {
                badge.textContent = count > 9 ? '9+' : count;
            }
        }

        function markAllNotificationsAsRead() {
            // This is a simplified version - you'll need to implement the actual API call
            const notificationItems = document.querySelectorAll('.notification-item');
            notificationItems.forEach(item => {
                item.remove();
            });
            
            const notificationList = document.querySelector('.notification-list');
            notificationList.innerHTML = `
                <div class="no-notifications">
                    <i class="far fa-bell-slash"></i>
                    <p>No new notifications</p>
                </div>
            `;
            
            const badge = document.querySelector('.notification-badge');
            if (badge) badge.remove();
            
            const markAllReadBtn = document.getElementById('markAllRead');
            if (markAllReadBtn) markAllReadBtn.style.display = 'none';
            
            // Here you would typically make an API call to mark all as read
            fetch('../../api/mark_all_notifications_read.php', {
                method: 'POST'
            });
        }

        // Show toast message
        function showToast(message, type = 'info') {
            const toast = document.getElementById('toast');
            toast.textContent = message;
            toast.className = 'toast show ' + type;
            setTimeout(() => {
                toast.className = toast.className.replace('show', '');
            }, 3000);
        }

        // Logout functions
        function showLogoutModal() {
            document.getElementById('logoutModal').style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function closeLogoutModal() {
            document.getElementById('logoutModal').style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        function logout() {
            window.location.href = '../../onboarding/logout.php';
        }
    </script>
                </div>
            </div>
        </div>
    </div>
    <script src="../shared/navigation.js"></script>
    <script>
        // View profile function to match the one in friends.php
        function viewProfile(userId) {
            // Navigate to user profile page with relative path
            window.location.href = `../friends/user-profile.php?id=${userId}`;
        }
    </script>
</body>
</html>
