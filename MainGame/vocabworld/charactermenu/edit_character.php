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
    } elseif ($character_name === 'Kael') {
        $current_character = 'kael';
    } elseif ($character_name === 'Rex') {
        $current_character = 'rex';
    } elseif ($character_name === 'Orion') {
        $current_character = 'orion';
    } elseif ($character_name === 'Ember') {
        $current_character = 'ember';
    } elseif ($character_name === 'Astra') {
        $current_character = 'astra';
    } elseif ($character_name === 'Sylvi') {
        $current_character = 'sylvi';
    }
}

// Get user's vocabworld progress for shards
$stmt = $pdo->prepare("SELECT * FROM game_progress WHERE user_id = ? AND game_type = 'vocabworld'");
$stmt->execute([$user_id]);
$progress = $stmt->fetch();

// Get character customization data and shards
$character_data = null;
$user_shards = 0;
if ($progress && $progress['unlocked_levels']) {
    $character_data = json_decode($progress['unlocked_levels'], true);
    $user_shards = $character_data['current_points'] ?? 0;
}

// Get shard balance from new shard system
require_once '../shard_manager.php';
$shardManager = new ShardManager($pdo);
$shard_balance = $shardManager->getShardBalance($user_id);
if ($shard_balance) {
    $user_shards = $shard_balance['current_shards'];
}

// Get essence balance
require_once '../api/essence_manager.php';
$essenceManager = new EssenceManager($pdo);
$current_essence = $essenceManager->getEssence($user_id);

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
    <title>Equip Character - VocabWorld</title>
    <link rel="stylesheet" href="../style.css?v=3">
    <link rel="stylesheet" href="charactermenu.css?v=3">
    <link rel="stylesheet" href="../navigation/navigation.css?v=3">
    <link rel="stylesheet" href="../../../notif/toast.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <?php include '../loaders/loader-component.php'; ?>
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
                <div class="shard-currency" onclick="toggleCurrencyDropdown(this)">
                    <div class="currency-item shard-item">
                        <img src="../assets/currency/shard1.png" alt="Shards" class="shard-icon">
                        <span class="shard-count" id="shard-count">0</span>
                        <i class="fas fa-chevron-down mobile-only dropdown-arrow" style="font-size: 0.8rem; margin-left: 5px;"></i>
                    </div>
                    <div class="currency-item essence-item">
                        <img src="../assets/currency/essence.png" alt="Essence" class="shard-icon">
                        <span class="shard-count"><?php echo $current_essence; ?></span>
                    </div>
                </div>
                <div class="user-profile">
                    <div class="user-info">
                        <span class="greeting"><?php echo getGreeting(); ?></span>
                        <span class="username"><?php echo htmlspecialchars(explode(' ', $user['username'])[0]); ?></span>
                    </div>
                    <div class="profile-dropdown">
                        <a href="#" class="profile-icon">
                            <img src="<?php echo !empty($user['profile_image']) ? '../../../' . htmlspecialchars($user['profile_image']) : '../../../assets/menu/defaultuser.png'; ?>" alt="Profile" class="profile-img">
                        </a>
                        <div class="profile-dropdown-content">
                            <div class="profile-dropdown-header">
                                <img src="<?php echo !empty($user['profile_image']) ? '../../../' . htmlspecialchars($user['profile_image']) : '../../../assets/menu/defaultuser.png'; ?>" alt="Profile" class="profile-dropdown-avatar">
                                <div class="profile-dropdown-info">
                                    <div class="profile-dropdown-name"><?php echo htmlspecialchars($user['username']); ?></div>
                                    <div class="profile-dropdown-level">
                                        <img src="../assets/stats/level.png" class="level-icon-mini">
                                        <span>Level <?php echo htmlspecialchars($progress['player_level'] ?? 1); ?></span>
                                    </div>
                                </div>
                            </div>
                            <div class="profile-dropdown-menu">
                                <a href="character.php" class="profile-dropdown-item">
                                    <img src="assets/fc1089.png" class="dropdown-item-icon">
                                    <span>View Character</span>
                                </a>
                                <a href="../learnvocabmenu/learn.php" class="profile-dropdown-item">
                                    <img src="../assets/menu/vocabsys.png" class="dropdown-item-icon">
                                    <span>Study & Learn</span>
                                </a>
                             </div>
                            <div class="profile-dropdown-footer">
                                <button class="profile-dropdown-item sign-out" onclick="showLogoutModal()">
                                    <img src="../assets/menu/exit.png" class="dropdown-item-icon">
                                    <span>Sign Out</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Edit Character Screen -->
        <div id="edit-character-screen" class="screen active">
            <!-- Character Profile Layout (Same as character.php) -->
            <div class="character-profile-layout">
                <!-- Left Side: Character Preview (Same styling as character.php) -->
                <div class="character-preview-section">
                    <div class="character-preview-card transparent-card">
                        <div class="character-preview-header">
                            <h3>Character Preview</h3>
                        </div>
                        <div class="character-preview-display">
                            <div class="character-sprite" id="character-sprite">
                                <!-- Character sprite will be displayed here -->
                            </div>
                        </div>
                        
                        <div class="character-actions">
                            <button class="action-btn back-to-character-btn" onclick="goToCharacterProfile()">
                                <i class="fas fa-arrow-left"></i>
                                Back to Character
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Right Side: Character Selection (Sliding container) -->
                <div class="progress-section character-selection-container">
                    <div class="character-selection-card transparent-card slide-in-right">
                        <h3><img src="assets/fc5.png" alt="Select Icon" class="title-icon"> Select Your Character</h3>
                        <div class="character-cards-grid">
                            <!-- Ethan Character Card -->
                            <div class="character-card" data-character="boy">
                                <div class="character-card-preview">
                                    <div class="character-card-sprite">
                                        <img src="../assets/characters/boy_char/character_ethan.png" alt="Ethan Character">
                                    </div>
                                </div>
                                <div class="character-card-info">
                                    <h4>Ethan</h4>
                                    <p>Rising Adventurer</p>
                                </div>
                                <button class="equip-btn" data-character="boy">
                                    <i class="fas fa-check"></i>
                                    Equipped
                                </button>
                            </div>
                            
                            <!-- Emma Character Card -->
                            <div class="character-card" data-character="girl">
                                <div class="character-card-preview">
                                    <div class="character-card-sprite">
                                        <img src="../assets/characters/girl_char/character_emma.png" alt="Emma Character">
                                    </div>
                                </div>
                                <div class="character-card-info">
                                    <h4>Emma</h4>
                                    <p>Eager Explorer</p>
                                </div>
                                <button class="equip-btn" data-character="girl">
                                    <i class="fas fa-check"></i>
                                    Equipped
                                </button>
                            </div>
                            
                            <?php if (in_array('amber', $owned_characters)): ?>
                            <div class="character-card" data-character="amber">
                                <div class="character-card-preview">
                                    <div class="character-card-sprite">
                                        <img src="../assets/characters/amber_char/amber.png" alt="Amber Character">
                                    </div>
                                </div>
                                <div class="character-card-info">
                                    <h4>Amber</h4>
                                    <p>Twin of Warmth</p>
                                </div>
                                <button class="equip-btn" data-character="amber">
                                    <i class="fas fa-check"></i>
                                    Equipped
                                </button>
                            </div>
                            <?php endif; ?>

                            <!-- Kael -->
                            <?php if (in_array('kael', $owned_characters)): ?>
                            <div class="character-card" data-character="kael">
                                <div class="character-card-preview">
                                    <div class="character-card-sprite">
                                        <img src="../assets/characters/kael_char/kael.png" alt="Kael Character">
                                    </div>
                                </div>
                                <div class="character-card-info">
                                    <h4>Kael</h4>
                                    <p>Cosmic Warlord</p>
                                </div>
                                <button class="equip-btn" data-character="kael">
                                    <i class="fas fa-check"></i>
                                    Equipped
                                </button>
                            </div>
                            <?php endif; ?>

                            <!-- Rex -->
                            <?php if (in_array('rex', $owned_characters)): ?>
                            <div class="character-card" data-character="rex">
                                <div class="character-card-preview">
                                    <div class="character-card-sprite">
                                        <img src="../assets/characters/rex_char/rex.png" alt="Rex Character">
                                    </div>
                                </div>
                                <div class="character-card-info">
                                    <h4>Rex</h4>
                                    <p>Nimble Warrior</p>
                                </div>
                                <button class="equip-btn" data-character="rex">
                                    <i class="fas fa-check"></i>
                                    Equipped
                                </button>
                            </div>
                            <?php endif; ?>

                            <!-- Orion -->
                            <?php if (in_array('orion', $owned_characters)): ?>
                            <div class="character-card" data-character="orion">
                                <div class="character-card-preview">
                                    <div class="character-card-sprite">
                                        <img src="../assets/characters/orion_char/orion.png" alt="Orion Character">
                                    </div>
                                </div>
                                <div class="character-card-info">
                                    <h4>Orion</h4>
                                    <p>Cosmic traveler</p>
                                </div>
                                <button class="equip-btn" data-character="orion">
                                    <i class="fas fa-check"></i>
                                    Equipped
                                </button>
                            </div>
                            <?php endif; ?>

                            <!-- Ember -->
                            <?php if (in_array('ember', $owned_characters)): ?>
                            <div class="character-card" data-character="ember">
                                <div class="character-card-preview">
                                    <div class="character-card-sprite">
                                        <img src="../assets/characters/ember_char/ember.png" alt="Ember Character">
                                    </div>
                                </div>
                                <div class="character-card-info">
                                    <h4>Ember</h4>
                                    <p>Twin of Passion</p>
                                </div>
                                <button class="equip-btn" data-character="ember">
                                    <i class="fas fa-check"></i>
                                    Equipped
                                </button>
                            </div>
                            <?php endif; ?>

                            <!-- Astra -->
                            <?php if (in_array('astra', $owned_characters)): ?>
                            <div class="character-card" data-character="astra">
                                <div class="character-card-preview">
                                    <div class="character-card-sprite">
                                        <img src="../assets/characters/astra_char/astra.png" alt="Astra Character">
                                    </div>
                                </div>
                                <div class="character-card-info">
                                    <h4>Astra</h4>
                                    <p>Star walker</p>
                                </div>
                                <button class="equip-btn" data-character="astra">
                                    <i class="fas fa-check"></i>
                                    Equipped
                                </button>
                            </div>
                            <?php endif; ?>

                            <!-- Sylvi -->
                            <?php if (in_array('sylvi', $owned_characters)): ?>
                            <div class="character-card" data-character="sylvi">
                                <div class="character-card-preview">
                                    <div class="character-card-sprite">
                                        <img src="../assets/characters/sylvi_char/sylvi.png" alt="Sylvi Character">
                                    </div>
                                </div>
                                <div class="character-card-info">
                                    <h4>Sylvi</h4>
                                    <p>Nature's Heart</p>
                                </div>
                                <button class="equip-btn" data-character="sylvi">
                                    <i class="fas fa-check"></i>
                                    Equipped
                                </button>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

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

    <script src="charactermenu.js"></script>
    <script src="../../../navigation/shared/profile-dropdown.js"></script>
    <script>
        // Pass PHP data to JavaScript
        window.userData = {
            userId: <?php echo $user_id; ?>,
            username: '<?php echo addslashes($user['username']); ?>',
            shards: <?php echo $user_shards; ?>,
            currentCharacter: '<?php echo $current_character; ?>',
            characterName: '<?php echo $character_name; ?>',
            characterImagePath: '<?php echo $character_image_path; ?>',
            ownedCharacters: <?php echo json_encode($owned_characters); ?>
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
            window.location.href = '../../../onboarding/logout.php';
        }

        // Go back to character profile
        function goToCharacterProfile() {
            window.location.href = 'character.php';
        }

        // Character selection functionality
        // Initialize shard count display
        function initializeShardDisplay() {
            const shardCountEl = document.getElementById('shard-count');
            if (shardCountEl && window.userData) {
                shardCountEl.textContent = window.userData.shards || 0;
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            initializeShardDisplay();
            const currentCharacter = window.userData.currentCharacter || 'boy';
            
            // Initialize character preview
            updateCharacterPreview(currentCharacter);
            
            // Set initial equipped state
            document.querySelectorAll('.equip-btn').forEach(btn => {
                const character = btn.dataset.character;
                if (character === currentCharacter) {
                    btn.classList.add('equipped');
                    btn.innerHTML = '<i class="fas fa-check"></i> Equipped';
                } else {
                    btn.classList.remove('equipped');
                    btn.innerHTML = '<i class="fas fa-plus"></i> Equip';
                }
            });
            
            // Add click handlers for character cards
            document.querySelectorAll('.character-card').forEach(card => {
                card.addEventListener('click', function() {
                    const character = this.dataset.character;
                    selectCharacter(character);
                });
            });
            
            // Add page load animation
            setTimeout(() => {
                document.querySelector('.character-selection-container').classList.add('slide-in-right');
            }, 100);
        });

        function selectCharacter(characterType) {
            // Update character preview
            updateCharacterPreview(characterType);
            
            // Update equipped buttons
            document.querySelectorAll('.equip-btn').forEach(btn => {
                const character = btn.dataset.character;
                if (character === characterType) {
                    btn.classList.add('equipped');
                    btn.innerHTML = '<i class="fas fa-check"></i> Equipped';
                } else {
                    btn.classList.remove('equipped');
                    btn.innerHTML = '<i class="fas fa-plus"></i> Equip';
                }
            });
            
            // Save selection to localStorage
            localStorage.setItem('selectedCharacter', characterType);
            
            // Save selection to database
            saveCharacterSelection(characterType);
            
            // Show success message
            let characterName = '';
            if (characterType === 'boy') {
                characterName = 'Ethan';
            } else if (characterType === 'girl') {
                characterName = 'Emma';
            } else if (characterType === 'amber') {
                characterName = 'Amber';
            } else if (characterType === 'kael') {
                characterName = 'Kael';
            } else if (characterType === 'rex') {
                characterName = 'Rex';
            } else if (characterType === 'orion') {
                characterName = 'Orion';
            } else if (characterType === 'ember') {
                characterName = 'Ember';
            } else if (characterType === 'astra') {
                characterName = 'Astra';
            } else if (characterType === 'sylvi') {
                characterName = 'Sylvi';
            }
            showToast(`Character changed to ${characterName}!`, 'success');
        }

        // Save character selection to database
        function saveCharacterSelection(characterType) {
            fetch('../save_character.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    selectedCharacter: characterType,
                    characterData: window.userData.characterData || {}
                })
            })
            .then(response => response.json())
            .then(data => {
                console.log('Save character response:', data);
                if (data.success) {
                    console.log('Character selection saved successfully');
                    console.log('Debug info:', data.debug);
                } else {
                    console.error('Error saving character selection:', data.error);
                }
            })
            .catch(error => {
                console.error('Error saving character selection:', error);
            });
        }

        function updateCharacterPreview(characterType) {
            const previewSprite = document.getElementById('character-sprite');
            if (characterType === 'boy') {
                previewSprite.innerHTML = '<img src="../assets/characters/boy_char/character_ethan.png" alt="Ethan Character">';
            } else if (characterType === 'girl') {
                previewSprite.innerHTML = '<img src="../assets/characters/girl_char/character_emma.png" alt="Emma Character">';
            } else if (characterType === 'amber') {
                previewSprite.innerHTML = '<img src="../assets/characters/amber_char/amber.png" alt="Amber Character">';
            } else if (characterType === 'kael') {
                previewSprite.innerHTML = '<img src="../assets/characters/kael_char/kael.png" alt="Kael Character">';
            } else if (characterType === 'rex') {
                previewSprite.innerHTML = '<img src="../assets/characters/rex_char/rex.png" alt="Rex Character">';
            } else if (characterType === 'orion') {
                previewSprite.innerHTML = '<img src="../assets/characters/orion_char/orion.png" alt="Orion Character">';
            } else if (characterType === 'ember') {
                previewSprite.innerHTML = '<img src="../assets/characters/ember_char/ember.png" alt="Ember Character">';
            } else if (characterType === 'astra') {
                previewSprite.innerHTML = '<img src="../assets/characters/astra_char/astra.png" alt="Astra Character">';
            } else if (characterType === 'sylvi') {
                previewSprite.innerHTML = '<img src="../assets/characters/sylvi_char/sylvi.png" alt="Sylvi Character">';
            }
        }

        function showToast(message, type = 'info') {
            const toast = document.createElement('div');
            toast.className = `toast toast-${type}`;
            toast.textContent = message;
            toast.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: var(--royal-blue);
                color: var(--white);
                padding: 1rem 2rem;
                border-radius: 8px;
                box-shadow: var(--shadow-lg);
                z-index: 1000;
                animation: slideIn 0.3s ease-out;
            `;
            
            document.body.appendChild(toast);
            
            setTimeout(() => {
                toast.remove();
            }, 3000);
        }
        // Toggle currency dropdown on mobile
        function toggleCurrencyDropdown(element) {
            if (window.innerWidth <= 768) {
                element.classList.toggle('show-dropdown');
            }
        }
    </script>
</body>
</html>
