// Get all the necessary elements
const bgmToggle = document.getElementById('bgmToggle');
const sfxToggle = document.getElementById('sfxToggle');
const difficultySelect = document.getElementById('difficulty');
const saveButton = document.getElementById('saveSettings');
const backButton = document.getElementById('backToMenu');

// Load saved settings from localStorage
function loadSettings() {
    const settings = JSON.parse(localStorage.getItem('gameSettings')) || {
        bgmEnabled: true,
        sfxEnabled: true,
        difficulty: 'medium'
    };

    bgmToggle.checked = settings.bgmEnabled;
    sfxToggle.checked = settings.sfxEnabled;
    difficultySelect.value = settings.difficulty;
}

// Save settings to localStorage
function saveSettings() {
    const settings = {
        bgmEnabled: bgmToggle.checked,
        sfxEnabled: sfxToggle.checked,
        difficulty: difficultySelect.value
    };

    localStorage.setItem('gameSettings', JSON.stringify(settings));
    
    // Play toast notification sound
    const toastSound = new Audio('../assets/sounds/toast/toastnotifwarn.mp3');
    toastSound.volume = 0.5;
    toastSound.play().catch(error => {
        console.log('Error playing toast sound:', error);
    });
    
    // Show toast with overlay
    const overlay = document.querySelector('.toast-overlay');
    overlay.classList.add('show');
    showToast('Settings Saved');
    
    // Hide the toast and overlay after 1.5 seconds
    setTimeout(() => {
        toast.classList.remove('show');
        toast.classList.add('hide');
        overlay.classList.remove('show');
    }, 1500);
}

// Show toast notification
function showToast(message) {
    const toast = document.getElementById('toast');
    toast.textContent = message;
    toast.classList.add('show');
    
    setTimeout(() => {
        toast.classList.remove('show');
    }, 3000);
}

document.addEventListener('DOMContentLoaded', () => {
    // Event listeners for save button
    if (saveButton) {
        saveButton.addEventListener('click', saveSettings);
    }

    // Back button handling
    const backButton = document.getElementById('backToMenu');
    if (backButton) {
        backButton.addEventListener('click', (e) => {
            e.preventDefault();
            
            // Play click sound
            const clickSound = new Audio('../assets/sounds/clicks/mixkit-stapling-paper-2995.wav');
            clickSound.play().catch(error => {
                console.log('Error playing click sound:', error);
            });
            
            // Add visual feedback
            backButton.style.transform = 'scale(0.95)';
            setTimeout(() => {
                backButton.style.transform = 'scale(1)';
            }, 100);
            
            // Navigate back to index immediately with parameter to skip loading screen
            window.location.replace('../index.html?from=selection');
        });
    }
});

function playClickSound() {
    const clickSound = new Audio('../assets/sounds/clicks/mixkit-stapling-paper-2995.wav');
    clickSound.play().catch(error => {
        console.log('Error playing click sound:', error);
    });
}

function addClickEffect(button) {
    button.style.transform = 'scale(0.95)';
    setTimeout(() => {
        button.style.transform = 'scale(1)';
    }, 100);
}

// Load settings when page loads
document.addEventListener('DOMContentLoaded', loadSettings);
