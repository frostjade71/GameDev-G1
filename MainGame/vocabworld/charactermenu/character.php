<?php
require_once '../../../onboarding/config.php';
require_once '../../../includes/greeting.php';

// Check if user is logged in
requireLogin();

// Get user information
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Get user's vocabworld progress
$stmt = $pdo->prepare("SELECT * FROM game_progress WHERE user_id = ? AND game_type = 'vocabworld'");
$stmt->execute([$user_id]);
$progress = $stmt->fetch();

// Note: game_scores table has been removed
$total_sessions = 0;
$average_percentage = 0;

// Get character customization data
$character_data = null;
$user_shards = 0;
if ($progress && $progress['unlocked_levels']) {
    $character_data = json_decode($progress['unlocked_levels'], true);
    $user_shards = $character_data['current_points'] ?? 0;
}

// Get shard balance from new shard system - automatically create account if needed
require_once '../shard_manager.php';
$shardManager = new ShardManager($pdo);
$shard_result = $shardManager->ensureShardAccount($user_id);

if ($shard_result['success']) {
    $user_shards = $shard_result['shard_balance'];
    
    // Log if a new account was created (for debugging)
    if (!$shard_result['account_exists']) {
        error_log("VocabWorld: Created new shard account for user ID: $user_id with initial shards: $user_shards");
    }
} else {
    // Fallback to old system if new system fails
    $shard_balance = $shardManager->getShardBalance($user_id);
    if ($shard_balance) {
        $user_shards = $shard_balance['current_shards'];
    }
    error_log("VocabWorld: Shard account creation failed for user ID: $user_id - " . $shard_result['error']);
}

// Get user's current character selection from database
$current_character = 'boy'; // Default fallback
$character_name = 'Ethan';
$character_image_path = '../assets/characters/boy_char/character_ethan.png';

$stmt = $pdo->prepare("SELECT * FROM character_selections WHERE user_id = ? AND game_type = 'vocabworld'");
$stmt->execute([$user_id]);
$character_selection = $stmt->fetch();

if ($character_selection) {
    $character_name = $character_selection['selected_character']; // Now stores character name
    $character_image_path = $character_selection['character_image_path'];
    
    // Determine character type from character name
    if ($character_name === 'Ethan') {
        $current_character = 'boy';
    } elseif ($character_name === 'Emma') {
        $current_character = 'girl';
    } elseif ($character_name === 'Amber') {
        $current_character = 'amber';
    }
}

// Get user's owned characters
$stmt = $pdo->prepare("SELECT character_type FROM character_ownership WHERE user_id = ?");
$stmt->execute([$user_id]);
$owned_characters = $stmt->fetchAll(PDO::FETCH_COLUMN);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/webp" href="../assets/menu/vv_logo.webp">
    <title>Character - VocabWorld</title>
    <link rel="stylesheet" href="../style.css?v=3">
    <link rel="stylesheet" href="charactermenu.css?v=3">
    <link rel="stylesheet" href="../navigation/navigation.css?v=3">
    <link rel="stylesheet" href="../../../notif/toast.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="game-container">
        <!-- Background -->
        <div class="background-image"></div>
        
        <!-- Header -->
        <header class="top-header">
            <div class="header-left">
                <div class="game-logo-container">
                    <img src="../assets/vocabworldhead.png" alt="VocabWorld" class="game-header-logo">
                </div>
            </div>
            <div class="header-right">
                <div class="shard-currency">
                    <img src="../assets/currency/shard1.png" alt="Shards" class="shard-icon">
                    <span class="shard-count" id="shard-count">0</span>
                </div>
                <div class="user-profile">
                    <div class="user-info">
                        <span class="greeting"><?php echo getGreeting(); ?></span>
                        <span class="username"><?php echo htmlspecialchars(explode(' ', $user['username'])[0]); ?></span>
                    </div>
                    <div class="profile-dropdown">
                        <a href="#" class="profile-icon">
                            <img src="../../../assets/menu/defaultuser.png" alt="Profile" class="profile-img">
                        </a>
                        <div class="profile-dropdown-content">
                            <div class="profile-dropdown-header">
                                <img src="../../../assets/menu/defaultuser.png" alt="Profile" class="profile-dropdown-avatar">
                                <div class="profile-dropdown-info">
                                    <div class="profile-dropdown-name"><?php echo htmlspecialchars($user['username']); ?></div>
                                    <div class="profile-dropdown-email"><?php echo htmlspecialchars($user['email']); ?></div>
                                </div>
                            </div>
                            <div class="profile-dropdown-menu">
                                <a href="../../../navigation/profile/profile.php" class="profile-dropdown-item">
                                    <i class="fas fa-user"></i>
                                    <span>View Profile</span>
                                </a>
                                <a href="../../../navigation/favorites/favorites.php" class="profile-dropdown-item">
                                    <i class="fas fa-star"></i>
                                    <span>My Favorites</span>
                                </a>
                                <a href="../../../settings/settings.php" class="profile-dropdown-item">
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

        <!-- Character Screen -->
        <div id="character-screen" class="screen active">
            <!-- Character Profile Content -->
            <div class="character-profile-layout">
                <!-- Left Side: Character -->
                <div class="character-preview-section">
                    <div class="character-preview-container">
                        <div class="character-preview-background">
                            <img src="../assets/menu/characterprev.webp" alt="Character Preview Background" class="preview-bg-image">
                        </div>
                        <div class="character-preview-content">
                            <div class="character-preview-header">
                                <div class="character-logo">
                                    <img src="../assets/menu/vocabcharacterlogo.png" alt="VocabCharacter Logo" class="logo-image">
                                </div>
                                <div class="character-status">
                                    <span class="status-indicator active"></span>
                                    <span class="status-text">Active</span>
                                </div>
                            </div>
                            <div class="character-preview-display">
                                <div class="character-sprite-container">
                                    <div class="character-sprite" id="character-sprite">
                                        <!-- Character sprite will be displayed here -->
                                    </div>
                                    <div class="character-glow"></div>
                                </div>
                                <div class="character-info">
                                    <div class="character-name" id="character-name"><?php echo $character_name; ?></div>
                                    <div class="character-level">Level 1</div>
                                </div>
                            </div>
                            
                            <div class="character-actions">
                                <button class="action-btn edit-character-btn" onclick="goToEditCharacter()">
                                    <i class="fas fa-user-edit"></i>
                                    <span>Customize</span>
                                </button>
                                <button class="action-btn shop-characters-btn" onclick="showCharacterShop()">
                                    <i class="fas fa-store"></i>
                                    <span>Shop</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Right Side: Progress & Badges -->
                <div class="progress-section">
                    <div class="progress-card transparent-card">
                        <h3>Your Progress</h3>
                        <div class="stat-item">
                            <i class="fas fa-level-up-alt"></i>
                            <div class="stat-details">
                                <span class="stat-value">Level <?php echo htmlspecialchars($progress['player_level'] ?? 1); ?></span>
                                <span class="stat-label">Current Level</span>
                            </div>
                        </div>
                        <div class="stat-item">
                            <i class="fas fa-star"></i>
                            <div class="stat-details">
                                <span class="stat-value"><?php echo number_format($progress['total_experience_earned'] ?? 0); ?> XP</span>
                                <span class="stat-label">Total Experience</span>
                            </div>
                        </div>
                        <div class="stat-item">
                            <i class="fas fa-skull"></i>
                            <div class="stat-details">
                                <span class="stat-value"><?php echo number_format($progress['total_monsters_defeated'] ?? 0); ?></span>
                                <span class="stat-label">Monsters Defeated</span>
                            </div>
                        </div>
                        <div class="stat-item">
                            <i class="fas fa-award"></i>
                            <div class="stat-details">
                                <span class="stat-value"><?php echo number_format($progress['score'] ?? 0, 2); ?></span>
                                <span class="stat-label">GWA</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="badges-card transparent-card">
                        <h3>Achievements & Badges</h3>
                        <div class="soon-badge">
                            <div class="soon-icon">🚀</div>
                            <div class="soon-text">Coming Soon</div>
                            <div class="soon-description">New achievements and badges are being developed!</div>
                        </div>
                    </div>
                    
                    <!-- Back to Menu Button -->
                    <div class="back-button-container">
                        <button class="back-btn" onclick="goToMainMenu()">← Back to Menu</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Character Selection Modal -->
        <div class="modal-overlay" id="character-selection-modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3>Select Your Character</h3>
                    <button class="modal-close" onclick="hideCharacterSelection()">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="character-selection-grid">
                        <div class="character-option" data-character="boy" onclick="selectCharacter('boy')">
                            <div class="character-option-sprite">
                                <img src="../assets/characters/boy_char/character_ethan.png" alt="Ethan Character">
                            </div>
                            <div class="character-option-name">Ethan</div>
                            <div class="character-option-status">Owned</div>
                        </div>
                        <div class="character-option" data-character="girl" onclick="selectCharacter('girl')">
                            <div class="character-option-sprite">
                                <img src="../assets/characters/girl_char/character_emma.png" alt="Emma Character">
                            </div>
                            <div class="character-option-name">Emma</div>
                            <div class="character-option-status">Owned</div>
                        </div>
                        <?php if (in_array('amber', $owned_characters)): ?>
                        <div class="character-option" data-character="amber" onclick="selectCharacter('amber')">
                            <div class="character-option-sprite">
                                <img src="../assets/characters/amber_char/amber.png" alt="Amber Character">
                            </div>
                            <div class="character-option-name">Amber</div>
                            <div class="character-option-status">Owned</div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Logout Confirmation Modal -->
    <div class="toast-overlay" id="logoutModal" style="display: none;">
        <div class="toast" id="logoutConfirmation">
            <h3 style="margin-bottom: 1rem; color: #ff6b6b;">Logout Confirmation</h3>
            <p style="margin-bottom: 1.5rem; color: rgba(255, 255, 255, 0.8);">Are you sure you want to logout?</p>
            <div style="display: flex; gap: 1rem; justify-content: center;">
                <button onclick="confirmLogout()" style="background: #ff6b6b; color: white; border: none; padding: 0.5rem 1rem; border-radius: 8px; cursor: pointer; font-family: 'Press Start 2P', cursive; font-size: 0.8rem;">Yes, Logout</button>
                <button onclick="hideLogoutModal()" style="background: rgba(255, 255, 255, 0.2); color: white; border: 1px solid rgba(255, 255, 255, 0.3); padding: 0.5rem 1rem; border-radius: 8px; cursor: pointer; font-family: 'Press Start 2P', cursive; font-size: 0.8rem;">Cancel</button>
            </div>
        </div>
    </div>

    <script src="charactermenu.js"></script>
    <script src="../../../navigation/shared/profile-dropdown.js"></script>
    <script>
        // Pass PHP data to JavaScript
        window.userData = {
            userId: <?php echo $user_id; ?>,
            username: '<?php echo addslashes($user['username']); ?>',
            gradeLevel: '<?php echo addslashes($user['grade_level']); ?>',
            shards: <?php echo $user_shards; ?>,
            characterData: <?php echo $character_data ? json_encode($character_data) : 'null'; ?>,
            averagePercentage: <?php echo $average_percentage; ?>,
            totalSessions: <?php echo $total_sessions; ?>,
            currentCharacter: '<?php echo $current_character; ?>',
            characterName: '<?php echo $character_name; ?>',
            characterImagePath: '<?php echo $character_image_path; ?>'
        };

        // Logout functionality
        function showLogoutModal() {
            const modal = document.getElementById('logoutModal');
            const confirmation = document.getElementById('logoutConfirmation');
            
            if (modal && confirmation) {
                modal.style.display = 'block';
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
                modal.style.display = 'none';
            }
        }

        function confirmLogout() {
            // Redirect to logout endpoint
            window.location.href = '../../../onboarding/logout.php';
        }

        // Go to main menu
        function goToMainMenu() {
            window.location.href = '../index.php';
        }

        // Go to edit character page
        function goToEditCharacter() {
            window.location.href = 'edit_character.php';
        }

        // Shop characters function
        function showCharacterShop() {
            window.location.href = 'shop_characters.php';
        }

        // Initialize character customization
        // Initialize shard count display
        function initializeShardDisplay() {
            const shardCountEl = document.getElementById('shard-count');
            if (shardCountEl && window.userData) {
                shardCountEl.textContent = window.userData.shards || 0;
            }
        }

        document.addEventListener('DOMContentLoaded', async function() {
            initializeShardDisplay();
            
            // Initialize character display
            initializeCharacterDisplay();
            
            if (typeof game !== 'undefined' && game) {
                await game.initializeGame();
                game.showCharacter();
                
                // Initialize character selection from database
                const currentCharacter = window.userData.currentCharacter || 'boy';
                document.querySelectorAll('.character-option').forEach(option => {
                    option.classList.remove('selected');
                });
                const selectedOption = document.querySelector(`[data-character="${currentCharacter}"]`);
                if (selectedOption) {
                    selectedOption.classList.add('selected');
                }
            }
        });

        // Initialize character display
        function initializeCharacterDisplay() {
            const characterSprite = document.getElementById('character-sprite');
            const characterName = document.getElementById('character-name');
            
            if (characterSprite && window.userData) {
                const currentCharacter = window.userData.currentCharacter || 'boy';
                let characterNameText = '';
                let characterImagePath = '';
                
                if (currentCharacter === 'boy') {
                    characterNameText = 'Ethan';
                    characterImagePath = '../assets/characters/boy_char/character_ethan.png';
                } else if (currentCharacter === 'girl') {
                    characterNameText = 'Emma';
                    characterImagePath = '../assets/characters/girl_char/character_emma.png';
                } else if (currentCharacter === 'amber') {
                    characterNameText = 'Amber';
                    characterImagePath = '../assets/characters/amber_char/amber.png';
                }
                
                // Use database data if available
                if (window.userData.characterName) {
                    characterNameText = window.userData.characterName;
                }
                if (window.userData.characterImagePath) {
                    characterImagePath = window.userData.characterImagePath;
                }
                
                // Display the character sprite
                characterSprite.innerHTML = `<img src="${characterImagePath}" alt="${characterNameText} Character" style="width: 100%; height: 100%; object-fit: contain;">`;
                
                // Update character name
                if (characterName) {
                    characterName.textContent = characterNameText;
                }
            }
        }
    </script>
</body>
</html>
