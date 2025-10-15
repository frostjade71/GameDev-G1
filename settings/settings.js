// Get all the necessary elements
const bgmToggle = document.getElementById('bgmToggle');
const sfxToggle = document.getElementById('sfxToggle');
const languageSelect = document.getElementById('language');
const saveButton = document.getElementById('saveSettings');
const backButton = document.getElementById('backToMenu');
const settingsForm = document.getElementById('settingsForm');

// Save settings to database
function saveSettings(event) {
    event.preventDefault();
    
    const formData = new FormData();
    formData.append('action', 'save_settings');
    formData.append('bgm_enabled', bgmToggle.checked ? '1' : '0');
    formData.append('sfx_enabled', sfxToggle.checked ? '1' : '0');
    formData.append('language', languageSelect.value);
    
    fetch('settings.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Play toast notification sound
            const toastSound = new Audio('../assets/sounds/toast/toastnotifwarn.mp3');
            toastSound.volume = 0.5;
            toastSound.play().catch(error => {
                console.log('Error playing toast sound:', error);
            });
            
            showToast(data.message);
        } else {
            showToast('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Error saving settings');
    });
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
    // Event listeners for form submission
    if (settingsForm) {
        settingsForm.addEventListener('submit', saveSettings);
    }

    // Back button handling
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
            
            // Navigate back to menu with from parameter
            window.location.href = '../menu.php?from=settings';
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

// Settings are loaded from PHP, no need to load from localStorage
