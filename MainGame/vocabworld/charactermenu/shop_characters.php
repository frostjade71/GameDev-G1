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
    <title>Shop Characters - VocabWorld</title>
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

        <!-- Shop Characters Screen -->
        <div id="shop-characters-screen" class="screen active">
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
                
                <!-- Right Side: Shop Characters (Sliding container) -->
                <div class="progress-section shop-characters-container">
                    <div class="shop-characters-card transparent-card slide-in-right">
                        <h3><img src="assets/fc1839.png" alt="Shop Icon" class="title-icon"> Character Shop</h3>
                        <div class="shop-characters-grid">
                            <!-- Ethan Character Card -->
                            <div class="shop-character-card" data-character="boy">
                                <div class="character-card-preview">
                                    <div class="character-card-sprite">
                                        <img src="../assets/characters/boy_char/character_ethan.png" alt="Ethan Character">
                                    </div>
                                </div>
                                <div class="character-card-info">
                                    <h4>Ethan</h4>
                                    <p>Adventurous and curious</p>
                                    <div class="character-price">
                                        <span class="price-label">Price:</span>
                                        <span class="price-value">FREE</span>
                                    </div>
                                </div>
                                <button class="purchase-btn" data-character="boy">
                                    <i class="fas fa-check"></i>
                                    Owned
                                </button>
                            </div>
                            
                            <!-- Emma Character Card -->
                            <div class="shop-character-card" data-character="girl">
                                <div class="character-card-preview">
                                    <div class="character-card-sprite">
                                        <img src="../assets/characters/girl_char/character_emma.png" alt="Emma Character">
                                    </div>
                                </div>
                                <div class="character-card-info">
                                    <h4>Emma</h4>
                                    <p>Smart and determined</p>
                                    <div class="character-price">
                                        <span class="price-label">Price:</span>
                                        <span class="price-value">FREE</span>
                                    </div>
                                </div>
                                <button class="purchase-btn" data-character="girl">
                                    <i class="fas fa-check"></i>
                                    Owned
                                </button>
                            </div>
                            
                            <!-- Amber Character Card -->
                            <div class="shop-character-card" data-character="amber">
                                <div class="character-card-preview">
                                    <div class="character-card-sprite">
                                        <img src="../assets/characters/amber_char/amber.png" alt="Amber Character">
                                    </div>
                                </div>
                                <div class="character-card-info">
                                    <h4>Amber</h4>
                                    <p>Mysterious and magical</p>
                                    <div class="character-price">
                                        <span class="price-label">Price:</span>
                                        <span class="price-value"><img src="../assets/currency/shard1.png" alt="Shards" style="width: 20px; height: 20px; vertical-align: middle;"> 20</span>
                                    </div>
                                </div>
                                <?php if (in_array('amber', $owned_characters)): ?>
                                    <button class="purchase-btn owned" data-character="amber">
                                        <i class="fas fa-check"></i>
                                        Owned
                                    </button>
                                <?php else: ?>
                                    <button class="purchase-btn" data-character="amber" onclick="purchaseCharacter('amber')">
                                        <i class="fas fa-shopping-cart"></i>
                                        Buy
                                    </button>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Future Character Card (Locked) -->
                            <div class="shop-character-card locked" data-character="future1">
                                <div class="character-card-preview">
                                    <div class="character-card-sprite locked-sprite">
                                        <i class="fas fa-lock"></i>
                                    </div>
                                </div>
                                <div class="character-card-info">
                                    <h4>Coming Soon</h4>
                                    <p>New character arriving soon!</p>
                                    <div class="character-price">
                                        <span class="price-label">Price:</span>
                                        <span class="price-value">TBA</span>
                                    </div>
                                </div>
                                <button class="purchase-btn locked" disabled>
                                    <i class="fas fa-lock"></i>
                                    Locked
                                </button>
                            </div>
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

    <!-- Purchase Confirmation Modal -->
    <div id="purchaseModal" class="purchase-modal-overlay">
        <div class="purchase-modal-content">
            <div class="purchase-modal-header">
                <h3>Purchase Confirmation</h3>
            </div>
            <div class="purchase-modal-body">
                <p id="purchaseMessage"></p>
            </div>
            <div class="purchase-modal-footer">
                <button onclick="confirmPurchase()" class="modal-btn confirm-btn">Yes, Buy</button>
                <button onclick="hidePurchaseModal()" class="modal-btn cancel-btn">Cancel</button>
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

        // Purchase modal functions
        let currentPurchaseData = null;

        function showPurchaseModal(characterType, price) {
            const modal = document.getElementById('purchaseModal');
            const message = document.getElementById('purchaseMessage');
            
            console.log('Showing purchase modal for:', characterType, price);
            
            // Get character name
            let characterName = '';
            if (characterType === 'amber') {
                characterName = 'Amber';
            }
            
            // Set the message with shard icon
            message.innerHTML = `Buy ${characterName} for <img src="../assets/currency/shard1.png" alt="Shards" style="width: 20px; height: 20px; vertical-align: middle;"> ${price}?`;
            
            // Store purchase data
            currentPurchaseData = { characterType, price };
            console.log('Purchase data stored:', currentPurchaseData);
            
            // Show modal
            modal.classList.add('show');
            console.log('Modal display set to show');
        }

        function hidePurchaseModal() {
            const modal = document.getElementById('purchaseModal');
            if (modal) {
                modal.classList.remove('show');
            }
            // Don't clear currentPurchaseData here - it's needed for the purchase
        }

        function confirmPurchase() {
            console.log('Confirm purchase clicked!');
            console.log('Current purchase data:', currentPurchaseData);
            
            if (currentPurchaseData) {
                console.log('Processing purchase for:', currentPurchaseData.characterType, currentPurchaseData.price);
                hidePurchaseModal();
                processPurchase(currentPurchaseData.characterType, currentPurchaseData.price);
            } else {
                console.error('No purchase data found!');
            }
        }

        function processPurchase(characterType, price) {
            console.log('Making purchase request...');
            // Make purchase request
            fetch('purchase_character_fixed.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    characterType: characterType,
                    price: price
                })
            })
            .then(response => {
                console.log('Response status:', response.status);
                return response.json();
            })
            .then(data => {
                console.log('Purchase response:', data);
                if (data.success) {
                    // Show success message
                    showToast(`Successfully bought ${characterType}!`, 'success');
                    
                    // Clear purchase data
                    currentPurchaseData = null;
                    
                    // Refresh the page after a short delay to show the toast
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    showToast(`Error: ${data.error}`, 'error');
                    // Clear purchase data on error too
                    currentPurchaseData = null;
                }
            })
            .catch(error => {
                console.error('Error purchasing character:', error);
                showToast('Error buying character. Please try again.', 'error');
            });
        }

        // Go back to character profile
        function goToCharacterProfile() {
            window.location.href = 'character.php';
        }

        // Shop characters functionality
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
            
            // Set initial owned state
            document.querySelectorAll('.purchase-btn').forEach(btn => {
                const character = btn.dataset.character;
                const isOwned = window.userData.ownedCharacters.includes(character) || character === 'boy' || character === 'girl';
                
                if (isOwned) {
                    btn.classList.add('owned');
                    btn.innerHTML = '<i class="fas fa-check"></i> Owned';
                    btn.disabled = true;
                    btn.style.pointerEvents = 'none';
                    btn.style.cursor = 'default';
                } else if (character === 'amber') {
                    // Set up buy button for Amber if not owned
                    btn.innerHTML = '<i class="fas fa-shopping-cart"></i> Buy';
                    btn.onclick = function(e) {
                        e.stopPropagation();
                        purchaseCharacter(character);
                    };
                }
            });
            
            // Add click handlers for character cards (preview only)
            document.querySelectorAll('.shop-character-card:not(.locked)').forEach(card => {
                card.addEventListener('click', function() {
                    const character = this.dataset.character;
                    if (character !== 'future1') {
                        updateCharacterPreview(character);
                    }
                });
            });
            
            // Add click handlers for purchase buttons (only for non-owned characters)
            document.querySelectorAll('.purchase-btn:not(.locked):not(.owned)').forEach(btn => {
                // Remove any existing click handlers to prevent duplicates
                btn.onclick = null;
                btn.addEventListener('click', function(e) {
                    e.stopPropagation(); // Prevent card click
                    const character = this.dataset.character;
                    if (character && character !== 'boy' && character !== 'girl') {
                        purchaseCharacter(character);
                    }
                });
            });
            
            // Add page load animation
            setTimeout(() => {
                document.querySelector('.shop-characters-container').classList.add('slide-in-right');
            }, 100);
        });


        function updateCharacterPreview(characterType) {
            const previewSprite = document.getElementById('character-sprite');
            if (characterType === 'boy') {
                previewSprite.innerHTML = '<img src="../assets/characters/boy_char/character_ethan.png" alt="Ethan Character">';
            } else if (characterType === 'girl') {
                previewSprite.innerHTML = '<img src="../assets/characters/girl_char/character_emma.png" alt="Emma Character">';
            } else if (characterType === 'amber') {
                previewSprite.innerHTML = '<img src="../assets/characters/amber_char/amber.png" alt="Amber Character">';
            }
        }

        // Purchase character functionality
        function purchaseCharacter(characterType) {
            console.log('Purchase function called for:', characterType);
            console.log('User data:', window.userData);
            
            const characterPrices = {
                'amber': 20
            };
            
            const price = characterPrices[characterType];
            const userShards = window.userData.shards || 0;
            
            console.log('Price:', price, 'User shards:', userShards);
            
            if (userShards < price) {
                // Make button red for insufficient shards
                const button = document.querySelector(`[data-character="${characterType}"] .purchase-btn`);
                if (button) {
                    button.style.backgroundColor = '#ff4444';
                    button.style.borderColor = '#ff4444';
                    button.style.color = 'white';
                    
                    // Revert back after 1 second
                    setTimeout(() => {
                        button.style.backgroundColor = '';
                        button.style.borderColor = '';
                        button.style.color = '';
                    }, 1000);
                }
                
                showToast(`Not enough shards! You need ${price} shards to buy this character.`, 'error');
                return;
            }
            
            // Show purchase confirmation modal
            showPurchaseModal(characterType, price);
        }
        
        function updateCharacterOwnership(characterType) {
            const characterCard = document.querySelector(`[data-character="${characterType}"]`);
            if (characterCard) {
                const purchaseBtn = characterCard.querySelector('.purchase-btn');
                purchaseBtn.innerHTML = '<i class="fas fa-check"></i> Owned';
                purchaseBtn.classList.add('owned');
                purchaseBtn.disabled = true;
                purchaseBtn.style.pointerEvents = 'none';
                purchaseBtn.style.cursor = 'default';
                purchaseBtn.onclick = null;
            }
        }
        
        function updateShardDisplay(newShardCount) {
            const shardCountEl = document.getElementById('shard-count');
            if (shardCountEl) {
                shardCountEl.textContent = newShardCount;
            }
            // Update userData for consistency
            if (window.userData) {
                window.userData.shards = newShardCount;
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
