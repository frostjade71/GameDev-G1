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

// Get user's current character selection from database (Reused from character.php)
$current_character = 'boy'; 
$character_name = 'Ethan';
$character_image_path = '../assets/characters/boy_char/character_ethan.png';

$stmt = $pdo->prepare("SELECT * FROM character_selections WHERE user_id = ? AND game_type = 'vocabworld'");
$stmt->execute([$user_id]);
$character_selection = $stmt->fetch();

if ($character_selection) {
    $character_name = $character_selection['selected_character'];
    $character_image_path = $character_selection['character_image_path'];
    if ($character_name === 'Ethan') $current_character = 'boy';
    elseif ($character_name === 'Emma') $current_character = 'girl';
    elseif ($character_name === 'Amber') $current_character = 'amber';
}

// Get balances
require_once '../shard_manager.php';
$shardManager = new ShardManager($pdo);
$shard_result = $shardManager->ensureShardAccount($user_id);
$user_shards = $shard_result['success'] ? $shard_result['shard_balance'] : 0;

require_once '../api/essence_manager.php';
$essenceManager = new EssenceManager($pdo);
$current_essence = $essenceManager->getEssence($user_id);

// Get user's vocabworld progress for level
$stmt = $pdo->prepare("SELECT * FROM game_progress WHERE user_id = ? AND game_type = 'vocabworld'");
$stmt->execute([$user_id]);
$progress = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/webp" href="../assets/menu/vv_logo.webp">
    <title>Convert Essence - VocabWorld</title>
    <link rel="stylesheet" href="../style.css?v=3">
    <link rel="stylesheet" href="charactermenu.css?v=6">
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
                        <span class="shard-count" id="shard-count"><?php echo $user_shards; ?></span>
                    </div>
                    <div class="currency-item essence-item">
                        <img src="../assets/currency/essence.png" alt="Essence" class="shard-icon">
                        <span class="shard-count" id="essence-count"><?php echo $current_essence; ?></span>
                    </div>
                </div>
                <!-- Profile dropdown (Simplified for brevity, or include same as others) -->
                 <div class="user-profile">
                    <div class="user-info">
                        <span class="greeting"><?php echo getGreeting(); ?></span>
                        <span class="username"><?php echo htmlspecialchars(explode(' ', $user['username'])[0]); ?></span>
                    </div>
                    <!-- Reuse the standard profile dropdown structure -->
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

        <!-- Convert Screen -->
        <div id="convert-screen" class="screen active">
            <div class="character-profile-layout">
                <!-- Left Side: Character Preview -->
                <div class="character-preview-section">
                    <div class="character-preview-card transparent-card">
                        <div class="character-preview-header">
                            <h3>Character Preview</h3>
                        </div>
                        <div class="character-preview-display">
                            <div class="character-sprite" id="character-sprite">
                                <img src="<?php echo $character_image_path; ?>" alt="<?php echo $character_name; ?>" style="width: 100%; height: 100%; object-fit: contain;">
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

                <!-- Right Side: Conversion Options -->
                <div class="progress-section shop-characters-container essence-exchange-container">
                    <div class="shop-characters-card transparent-card slide-in-right">
                        <h3><img src="assets/fc131.png" alt="Convert" class="title-icon"> Essence Exchange</h3>
                        
                        <div class="shop-characters-grid">
                            <!-- 1 Shard Card -->
                            <div class="shop-character-card">
                                <div class="character-card-preview">
                                    <div class="character-card-sprite">
                                        <img src="../assets/currency/shard1.png" alt="1 Shard">
                                    </div>
                                </div>
                                <div class="character-card-info">
                                    <h4>1 Shard</h4>
                                    <p>Small Pack</p>
                                    <div class="character-price">
                                        <span class="price-label">Cost:</span>
                                        <span class="price-value">
                                            <img src="../assets/currency/essence.png" alt="Essence" style="width: 16px; vertical-align: middle;"> 20
                                        </span>
                                    </div>
                                </div>
                                <button class="purchase-btn convert-action-btn" onclick="convertEssence(1, 20)">
                                    Exchange
                                </button>
                            </div>

                            <!-- 5 Shards Card -->
                            <div class="shop-character-card">
                                <div class="character-card-preview">
                                    <div class="character-card-sprite">
                                        <img src="../assets/currency/shard1.png" alt="5 Shards">
                                    </div>
                                </div>
                                <div class="character-card-info">
                                    <h4>5 Shards</h4>
                                    <p>Medium Pack</p>
                                    <div class="character-price">
                                        <span class="price-label">Cost:</span>
                                        <span class="price-value">
                                            <img src="../assets/currency/essence.png" alt="Essence" style="width: 16px; vertical-align: middle;"> 100
                                        </span>
                                    </div>
                                </div>
                                <button class="purchase-btn convert-action-btn" onclick="convertEssence(5, 100)">
                                    Exchange
                                </button>
                            </div>

                            <!-- 10 Shards Card -->
                            <div class="shop-character-card">
                                <div class="character-card-preview">
                                    <div class="character-card-sprite">
                                        <img src="../assets/currency/shard1.png" alt="10 Shards">
                                    </div>
                                </div>
                                <div class="character-card-info">
                                    <h4>10 Shards</h4>
                                    <p>Large Pack</p>
                                    <div class="character-price">
                                        <span class="price-label">Cost:</span>
                                        <span class="price-value">
                                            <img src="../assets/currency/essence.png" alt="Essence" style="width: 16px; vertical-align: middle;"> 200
                                        </span>
                                    </div>
                                </div>
                                <button class="purchase-btn convert-action-btn" onclick="convertEssence(10, 200)">
                                    Exchange
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Purchase Confirmation Modal -->
    <div id="purchaseModal" class="purchase-modal-overlay">
        <div class="purchase-modal-content">
            <div class="purchase-modal-header">
                <h3>Confirmation</h3>
            </div>
            <div class="purchase-modal-body">
                <p id="purchaseMessage"></p>
            </div>
            <div class="purchase-modal-footer">
                <button onclick="executeConversion()" class="modal-btn confirm-btn">Yes, Exchange</button>
                <button onclick="hidePurchaseModal()" class="modal-btn cancel-btn">Cancel</button>
            </div>
        </div>
    </div>
    
    <!-- Toast Container (Dynamic) -->
    
    <script>
        // Store user data
        window.userData = {
            essence: <?php echo $current_essence; ?>,
            shards: <?php echo $user_shards; ?>
        };

        // Use a more specific variable name to avoid global conflicts
        let pendingConversion = null;

        function goToCharacterProfile() {
            window.location.href = 'character.php';
        }

        function convertEssence(shards, essenceCost) {
            console.log('Initiating conversion:', shards, essenceCost);
            // Pre-validation
            if (window.userData.essence < essenceCost) {
                showToast(`Not enough Essence! Need ${essenceCost}.`, 'error');
                return;
            }

            // Show Confirmation Modal
            const modal = document.getElementById('purchaseModal');
            const message = document.getElementById('purchaseMessage');
            
            if (!modal || !message) {
                console.error('Modal elements not found');
                return;
            }
            
            message.innerHTML = `Exchange <img src="../assets/currency/essence.png" alt="Essence" style="width: 18px; height: 18px; vertical-align: middle;"> <b>${essenceCost}</b> for <img src="../assets/currency/shard1.png" alt="Shards" style="width: 18px; height: 18px; vertical-align: middle;"> <b>${shards}</b>?`;
            
            pendingConversion = { shards, essenceCost };
            modal.classList.add('show');
        }

        function hidePurchaseModal() {
            const modal = document.getElementById('purchaseModal');
            if (modal) modal.classList.remove('show');
            pendingConversion = null;
        }

        // Renamed to avoid potential conflict with other scripts
        function executeConversion() {
            console.log('Executing conversion...', pendingConversion);
            if (!pendingConversion) {
                console.error('No pending conversion data');
                hidePurchaseModal();
                return;
            }
            
            const { shards, essenceCost } = pendingConversion;
            
            // Close modal first
            const modal = document.getElementById('purchaseModal');
            if (modal) modal.classList.remove('show');
            // Don't nullify pendingConversion yet, we might need it? No, destructured is safe.
            pendingConversion = null;

            // Disable buttons
            const buttons = document.querySelectorAll('.convert-action-btn');
            buttons.forEach(btn => btn.disabled = true);

            console.log('Sending fetch request to process_conversion.php');
            fetch('process_conversion.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    shards_to_buy: shards
                })
            })
            .then(response => {
                console.log('Response received', response);
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.text().then(text => {
                    console.log('Raw response:', text);
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                         console.error('JSON Parse Error:', e);
                         throw new Error('Invalid server response');
                    }
                });
            })
            .then(data => {
                console.log('Parsed data:', data);
                if (data.success) {
                    showToast(`Successfully Converted! +${shards} Shards`, 'success');
                    updateCurrencyDisplay(data.new_essence, data.new_shards);
                } else {
                    showToast(data.error || 'Conversion failed', 'error');
                }
            })
            .catch(err => {
                console.error('Conversion Error:', err);
                showToast('Network error: ' + err.message, 'error');
            })
            .finally(() => {
                buttons.forEach(btn => btn.disabled = false);
            });
        }

        function updateCurrencyDisplay(essence, shards) {
            window.userData.essence = essence;
            window.userData.shards = shards;
            
            const essenceEl = document.getElementById('essence-count');
            const shardEl = document.getElementById('shard-count');
            
            if (essenceEl) essenceEl.textContent = essence;
            if (shardEl) shardEl.textContent = shards;
            
            // Animate
            const shardIcon = document.querySelector('.shard-item img');
            if (shardIcon) {
                shardIcon.style.transform = 'scale(1.5)';
                setTimeout(() => shardIcon.style.transform = 'scale(1)', 300);
            }
        }

        // Toast System from grade-access.js
        function initToastSystem() {
            if (!document.getElementById('toast-container')) {
                const toastContainer = document.createElement('div');
                toastContainer.id = 'toast-container';
                document.body.appendChild(toastContainer);
            }
            
            if (!document.querySelector('link[href*="Press+Start+2P"]')) {
                const link = document.createElement('link');
                link.href = 'https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap';
                link.rel = 'stylesheet';
                document.head.appendChild(link);
            }
            
            if (!document.querySelector('style#toast-styles')) {
                const style = document.createElement('style');
                style.id = 'toast-styles';
                style.textContent = `
                    #toast-container {
                        position: fixed;
                        top: 100px;
                        right: -68px;
                        z-index: 9999;
                        display: flex;
                        flex-direction: column;
                        align-items: flex-end;
                        pointer-events: none;
                    }
                    
                    @media (min-width: 768px) {
                        #toast-container {
                            right: 40px;
                        }
                    }
                    
                    @media (min-width: 1024px) {
                        #toast-container {
                            right: 270px;
                        }
                    }
                    
                    .toast {
                        background: rgba(0, 0, 0, 0.9);
                        color: white;
                        padding: 12px 20px;
                        border-radius: 4px;
                        margin: 5px 0;
                        box-shadow: 0 0 10px rgba(96, 239, 255, 0.3);
                        opacity: 0;
                        transform: translateX(100%);
                        transition: all 0.3s cubic-bezier(0.68, -0.55, 0.27, 1.55);
                        max-width: 300px;
                        min-width: 250px;
                        text-align: center;
                        word-wrap: break-word;
                        position: relative;
                        overflow: hidden;
                        font-family: 'Press Start 2P', cursive;
                        font-size: 0.45rem;
                        line-height: 1.5;
                    }
                    
                    .toast.show {
                        opacity: 1;
                        transform: translateX(0);
                    }
                    
                    .toast::after {
                        content: '';
                        position: absolute;
                        bottom: 0;
                        left: 0;
                        width: 100%;
                        height: 4px;
                        background: #e74c3c;
                        animation: progress 3s linear forwards;
                    }
                    
                    .toast-success::after {
                        background: #2ecc71;
                    }

                    @keyframes progress {
                        from { width: 100%; }
                        to { width: 0%; }
                    }
                    
                    .toast-error {
                        border-left: 4px solid #e74c3c;
                    }
                    
                    .toast-success {
                        border-left: 4px solid #2ecc71;
                    }
                `;
                document.head.appendChild(style);
            }
        }

        function showToast(message, type = 'info') {
            console.log('Toast:', message, type);
            initToastSystem();
            
            const container = document.getElementById('toast-container');
            
            // Remove any existing toasts (optional, or allow stacking)
            // grade-access.js removes them, I'll allow stacking for better UX if multiple errors, 
            // but sticking to grade-access behavior (remove existing) for consistency if user wants EXACT match.
            // grade-access.js:
            /*
            while (container.firstChild) {
                container.removeChild(container.firstChild);
            }
            */
            // I'll stick to clearing it to avoid screen clutter on mobile
            while (container.firstChild) {
                container.removeChild(container.firstChild);
            }
            
            const toast = document.createElement('div');
            toast.className = `toast toast-${type}`;
            toast.innerHTML = message; // Using innerHTML to support potential bold tags
            
            container.appendChild(toast);
            
            // Trigger reflow
            void toast.offsetWidth;
            
            // Show toast
            toast.classList.add('show');
            
            // Remove toast after delay
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => {
                    if (toast.parentNode === container) {
                        container.removeChild(toast);
                    }
                }, 300);
            }, 3000);
        }
        // Toggle currency dropdown on mobile
        function toggleCurrencyDropdown(element) {
            if (window.innerWidth <= 768) {
                element.classList.toggle('show-dropdown');
            }
        }
    </script>
    <script src="../../../navigation/shared/profile-dropdown.js"></script>
</body>
</html>
