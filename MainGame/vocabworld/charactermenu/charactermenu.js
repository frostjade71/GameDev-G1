// VocabWorld Character Menu Logic
class CharacterMenu {
    constructor() {
        this.currentCharacter = localStorage.getItem('selectedCharacter') || 'boy';
        this.characterData = window.userData?.characterData || {
            hat: null,
            clothes: null,
            color: null,
            accessories: []
        };
    }

    // Initialize character menu
    async initializeCharacterMenu() {
        // Use database character data if available, otherwise fallback to localStorage
        if (window.userData && window.userData.currentCharacter) {
            this.currentCharacter = window.userData.currentCharacter;
            localStorage.setItem('selectedCharacter', this.currentCharacter);
        } else {
            this.currentCharacter = localStorage.getItem('selectedCharacter') || 'boy';
        }
        
        this.updateCharacterDisplay();
        this.updatePointsDisplay();
        this.updateShardDisplay();
    }

    // Character System
    updateCharacterDisplay() {
        const characterSprite = document.getElementById('character-sprite');
        const characterName = document.getElementById('character-name');
        if (!characterSprite) return;
        
        // Use database character data if available, otherwise fallback to localStorage
        let currentCharacter = this.currentCharacter;
        let characterNameText = '';
        let characterImagePath = '';
        
        if (window.userData && window.userData.currentCharacter) {
            currentCharacter = window.userData.currentCharacter;
            characterNameText = window.userData.characterName || '';
            characterImagePath = window.userData.characterImagePath || '';
        } else {
            currentCharacter = localStorage.getItem('selectedCharacter') || 'boy';
        }
        
        this.currentCharacter = currentCharacter;
        
        // Determine the correct path based on current location
        const isInCharactermenu = window.location.pathname.includes('/charactermenu/');
        const assetsPath = isInCharactermenu ? '../assets/' : 'assets/';
        
        // Display the selected character sprite and name
        if (characterImagePath && characterNameText) {
            // Use database character data
            characterSprite.innerHTML = `<img src="${characterImagePath}" alt="${characterNameText} Character">`;
            if (characterName) {
                characterName.textContent = characterNameText;
            }
        } else if (currentCharacter === 'boy') {
            characterSprite.innerHTML = `<img src="${assetsPath}characters/boy_char/character_ethan.png" alt="Ethan Character">`;
            if (characterName) {
                characterName.textContent = 'Ethan';
            }
        } else if (currentCharacter === 'girl') {
            characterSprite.innerHTML = `<img src="${assetsPath}characters/girl_char/character_emma.png" alt="Emma Character">`;
            if (characterName) {
                characterName.textContent = 'Emma';
            }
        } else if (currentCharacter === 'amber') {
            characterSprite.innerHTML = `<img src="${assetsPath}characters/amber_char/amber.png" alt="Amber Character">`;
            if (characterName) {
                characterName.textContent = 'Amber';
            }
        } else {
            characterSprite.innerHTML = '<div class="character-base">👤</div>';
            if (characterName) {
                characterName.textContent = 'Character';
            }
        }
    }

    updatePointsDisplay() {
        const currentPointsEl = document.getElementById('current-points');
        if (currentPointsEl) {
            currentPointsEl.textContent = this.characterData.current_points || 0;
        }
    }

    updateShardDisplay() {
        const shardCountEl = document.getElementById('shard-count');
        if (shardCountEl && window.userData) {
            shardCountEl.textContent = window.userData.shards || 0;
        }
    }

    // Character Selection Functions
    showCharacterSelection() {
        const modal = document.getElementById('character-selection-modal');
        if (modal) {
            modal.classList.add('show');
        }
    }

    hideCharacterSelection() {
        const modal = document.getElementById('character-selection-modal');
        if (modal) {
            modal.classList.remove('show');
        }
    }

    selectCharacter(characterType) {
        // Save character selection to localStorage
        localStorage.setItem('selectedCharacter', characterType);
        
        // Save character selection to database
        this.saveCharacterSelection(characterType);
        
        // Update character display
        this.currentCharacter = characterType;
        this.updateCharacterDisplay();
        
        // Update selection in modal
        document.querySelectorAll('.character-option').forEach(option => {
            option.classList.remove('selected');
        });
        document.querySelector(`[data-character="${characterType}"]`).classList.add('selected');
        
        // Hide modal
        this.hideCharacterSelection();
        
        // Show success message
        let characterName = '';
        if (characterType === 'boy') {
            characterName = 'Ethan';
        } else if (characterType === 'girl') {
            characterName = 'Emma';
        } else if (characterType === 'amber') {
            characterName = 'Amber';
        }
        this.showToast(`Character changed to ${characterName}!`, 'success');
    }

    showCharacterShop() {
        // Redirect to shop characters page
        window.location.href = 'shop_characters.php';
    }

    // Character Customization Functions
    updateCharacterPreview(characterType) {
        const previewSprite = document.getElementById('character-sprite');
        if (characterType === 'boy') {
            previewSprite.innerHTML = '<img src="../assets/characters/boy_char/character_ethan.png" alt="Ethan Character">';
        } else if (characterType === 'girl') {
            previewSprite.innerHTML = '<img src="../assets/characters/girl_char/character_emma.png" alt="Emma Character">';
        }
    }

    // Character Data Management
    saveCharacterData() {
        // Save character customization to database
        fetch('save_character.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                userId: window.userData.userId,
                characterData: this.characterData
            })
        }).catch(error => {
            console.error('Error saving character data:', error);
        });
    }

    // Save character selection to database
    saveCharacterSelection(characterType) {
        fetch('../save_character.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                selectedCharacter: characterType,
                characterData: this.characterData
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log('Character selection saved successfully');
            } else {
                console.error('Error saving character selection:', data.error);
            }
        })
        .catch(error => {
            console.error('Error saving character selection:', error);
        });
    }

    // Utility Functions
    showToast(message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.textContent = message;
        
        // Add to page
        document.body.appendChild(toast);
        
        // Show toast
        setTimeout(() => {
            toast.classList.add('show');
        }, 100);
        
        // Hide toast after 3 seconds
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => {
                document.body.removeChild(toast);
            }, 300);
        }, 3000);
    }

    // Navigation Functions
    goToMainMenu() {
        window.location.href = '../index.php';
    }

    goToEditCharacter() {
        window.location.href = 'edit_character.php';
    }

    goToCharacterProfile() {
        window.location.href = 'character.php';
    }

    // Logout Functions
    showLogoutModal() {
        const modal = document.getElementById('logoutModal');
        const confirmation = document.getElementById('logoutConfirmation');
        
        if (modal && confirmation) {
            modal.classList.add('show');
            confirmation.classList.remove('hide');
            confirmation.classList.add('show');
        }
    }

    hideLogoutModal() {
        const modal = document.getElementById('logoutModal');
        const confirmation = document.getElementById('logoutConfirmation');
        
        if (modal && confirmation) {
            confirmation.classList.remove('show');
            confirmation.classList.add('hide');
            modal.classList.remove('show');
        }
    }

    confirmLogout() {
        // Play click sound
        this.playClickSound();
        
        // Redirect to logout endpoint
        window.location.href = '../../../onboarding/logout.php';
    }

    playClickSound() {
        // Play click sound if available
        try {
            const audio = new Audio('../assets/sounds/clicks/gameopens2.mp3');
            audio.volume = 0.3;
            audio.play().catch(() => {
                // Ignore audio play errors
            });
        } catch (error) {
            // Ignore audio errors
        }
    }
}

// Global character menu instance
let characterMenu = null;

// Initialize character menu when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    characterMenu = new CharacterMenu();
    characterMenu.initializeCharacterMenu();
    
    // Initialize character selection
    const currentCharacter = localStorage.getItem('selectedCharacter') || 'boy';
    document.querySelectorAll('.character-option').forEach(option => {
        option.classList.remove('selected');
    });
    const selectedOption = document.querySelector(`[data-character="${currentCharacter}"]`);
    if (selectedOption) {
        selectedOption.classList.add('selected');
    }
});

// Global functions for HTML onclick handlers
function selectCharacter(characterType) {
    if (characterMenu) {
        characterMenu.selectCharacter(characterType);
    }
}

function showCharacterSelection() {
    if (characterMenu) {
        characterMenu.showCharacterSelection();
    }
}

function hideCharacterSelection() {
    if (characterMenu) {
        characterMenu.hideCharacterSelection();
    }
}

function showCharacterShop() {
    if (characterMenu) {
        characterMenu.showCharacterShop();
    }
}

function updateCharacterPreview(characterType) {
    if (characterMenu) {
        characterMenu.updateCharacterPreview(characterType);
    }
}

function goToMainMenu() {
    if (characterMenu) {
        characterMenu.goToMainMenu();
    }
}

function goToEditCharacter() {
    if (characterMenu) {
        characterMenu.goToEditCharacter();
    }
}

function goToCharacterProfile() {
    if (characterMenu) {
        characterMenu.goToCharacterProfile();
    }
}

function showLogoutModal() {
    if (characterMenu) {
        characterMenu.showLogoutModal();
    }
}

function hideLogoutModal() {
    if (characterMenu) {
        characterMenu.hideLogoutModal();
    }
}

function confirmLogout() {
    if (characterMenu) {
        characterMenu.confirmLogout();
    }
}

function initializeShardDisplay() {
    if (characterMenu) {
        characterMenu.updateShardDisplay();
    }
}
