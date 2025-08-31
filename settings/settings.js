// Get all the necessary elements
const bgmVolumeSlider = document.getElementById('bgmVolume');
const sfxVolumeSlider = document.getElementById('sfxVolume');
const difficultySelect = document.getElementById('difficulty');
const saveButton = document.getElementById('saveSettings');
const backButton = document.getElementById('backToMenu');
const volumeValues = document.querySelectorAll('.volume-value');

// Load saved settings from localStorage
function loadSettings() {
    const settings = JSON.parse(localStorage.getItem('gameSettings')) || {
        bgmVolume: 100,
        sfxVolume: 100,
        difficulty: 'medium'
    };

    bgmVolumeSlider.value = settings.bgmVolume;
    sfxVolumeSlider.value = settings.sfxVolume;
    difficultySelect.value = settings.difficulty;

    // Update volume value displays
    updateVolumeDisplays();
}

// Update volume displays
function updateVolumeDisplays() {
    bgmVolumeSlider.nextElementSibling.textContent = `${bgmVolumeSlider.value}%`;
    sfxVolumeSlider.nextElementSibling.textContent = `${sfxVolumeSlider.value}%`;
}

// Save settings to localStorage
function saveSettings() {
    const settings = {
        bgmVolume: parseInt(bgmVolumeSlider.value),
        sfxVolume: parseInt(sfxVolumeSlider.value),
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

// Event listeners
bgmVolumeSlider.addEventListener('input', updateVolumeDisplays);
sfxVolumeSlider.addEventListener('input', updateVolumeDisplays);
saveButton.addEventListener('click', saveSettings);
backButton.addEventListener('click', () => {
    playClickSound();
    addClickEffect(backButton);
    const container = document.querySelector('.settings-container');
    
    // Add slide-out animation
    container.classList.add('slide-out');
    
    // Wait for the slide-out animation to complete, then navigate
    setTimeout(() => {
        window.location.href = '../index.html?from=selection';
    }, 500);
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
