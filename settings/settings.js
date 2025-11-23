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
        showToast('not implemented yet...');
    });
}

// Show toast notification
function showToast(message) {
    console.log('showToast called with message:', message);
    
    // Create a new toast element to avoid CSS conflicts (like Reset Game Progress)
    const newToast = document.createElement('div');
    newToast.innerHTML = '<div style="text-align: center;"><img src="../assets/pixels/hammer.png" style="width: 20px; height: 20px; margin-bottom: 8px; display: block; margin-left: auto; margin-right: auto;">' + message + '</div>';
    newToast.style.cssText = `
        position: fixed !important;
        top: 50% !important;
        left: 50% !important;
        transform: translate(-50%, -50%) !important;
        background: rgba(0, 0, 0, 0.95) !important;
        color: white !important;
        padding: 15px 25px !important;
        border-radius: 10px !important;
        z-index: 999999 !important;
        font-size: 14px !important;
        font-family: Arial, sans-serif !important;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.5) !important;
        border: 1px solid rgba(255, 255, 255, 0.2) !important;
        opacity: 0 !important;
        transition: opacity 0.3s ease !important;
        pointer-events: none !important;
        text-align: center !important;
        max-width: 80% !important;
        word-wrap: break-word !important;
        margin: 0 !important;
        float: none !important;
        display: block !important;
    `;
    
    document.body.appendChild(newToast);
    console.log('Toast added to body:', newToast);
    
    // Force center positioning again after append
    setTimeout(() => {
        newToast.style.top = '50% !important';
        newToast.style.left = '50% !important';
        newToast.style.transform = 'translate(-50%, -50%) !important';
        newToast.style.opacity = '1';
        console.log('Toast positioned and shown');
    }, 10);
    
    // Remove after 3 seconds
    setTimeout(() => {
        newToast.style.opacity = '0';
        setTimeout(() => {
            if (newToast.parentNode) {
                newToast.parentNode.removeChild(newToast);
            }
        }, 300);
    }, 3000);
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
