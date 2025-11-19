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
    console.log('showToast called with message:', message);
    
    // Create a simple toast element if needed
    let toast = document.getElementById('toast');
    console.log('Toast element found:', toast);
    
    if (toast) {
        toast.textContent = message;
        toast.style.position = 'fixed';
        toast.style.top = '20px';
        toast.style.left = '50%';
        toast.style.transform = 'translateX(-50%)';
        toast.style.background = 'rgba(0, 0, 0, 0.9)';
        toast.style.color = 'white';
        toast.style.padding = '15px 25px';
        toast.style.borderRadius = '10px';
        toast.style.zIndex = '9999';
        toast.style.fontSize = '14px';
        toast.style.fontFamily = 'Arial, sans-serif';
        toast.style.opacity = '0';
        toast.style.transition = 'opacity 0.3s ease';
        toast.style.display = 'block';
        toast.style.visibility = 'visible';
        
        // Show the toast
        setTimeout(() => {
            toast.style.opacity = '1';
        }, 100);
        
        // Hide after 3 seconds
        setTimeout(() => {
            toast.style.opacity = '0';
            setTimeout(() => {
                toast.style.display = 'none';
            }, 300);
        }, 3000);
        
        console.log('Toast should be visible now');
    } else {
        console.error('Toast element not found!');
        // Create a fallback toast
        const fallbackToast = document.createElement('div');
        fallbackToast.textContent = message;
        fallbackToast.style.cssText = `
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(0, 0, 0, 0.9);
            color: white;
            padding: 15px 25px;
            border-radius: 10px;
            z-index: 9999;
            font-size: 14px;
            font-family: Arial, sans-serif;
        `;
        document.body.appendChild(fallbackToast);
        
        setTimeout(() => {
            fallbackToast.remove();
        }, 3000);
    }
}

document.addEventListener('DOMContentLoaded', () => {
    // Event listeners for form submission
    if (settingsForm) {
        settingsForm.addEventListener('submit', saveSettings);
    }

    // Reset Game Progress button
    const resetProgressBtn = document.getElementById('resetProgressBtn');
    if (resetProgressBtn) {
        resetProgressBtn.addEventListener('click', resetGameProgress);
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
