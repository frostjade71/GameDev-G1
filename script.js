document.addEventListener('DOMContentLoaded', () => {
    // Add entry animation for game selection page
    const menuContainer = document.querySelector('.menu-container');
    if (menuContainer) {
        // Force a reflow before adding the slide-in class
        void menuContainer.offsetWidth;
        
        // Add slide-in class to trigger the animation
        menuContainer.classList.add('slide-in');
    }

    const buttons = document.querySelectorAll('.menu-button, .play-button, .back-button');
    
    buttons.forEach(button => {
        // Add click sound and visual effect
        button.addEventListener('click', () => {
            playClickSound();
            addClickEffect(button);
            
            // Handle navigation
            if (button.textContent === 'Play') {
                const menuContainer = document.querySelector('.menu-container');
                menuContainer.classList.add('slide-out');
                
                // Wait for the slide-out animation to complete, then navigate
                setTimeout(() => {
                    window.location.href = 'game-selection.html';
                }, 500); // Match this with your CSS transition duration
                
                event.preventDefault(); // Prevent immediate navigation
            }
        });
    });

    // Handle back button on game selection page
    const backButton = document.querySelector('.back-button');
    if (backButton) {
        backButton.addEventListener('click', (event) => {
            const container = document.querySelector('.menu-container');
            
            // Add slide-out class to trigger animation
            container.classList.remove('slide-in');
            container.classList.add('slide-out');
            
            // Wait for the slide-out animation to complete, then navigate
            setTimeout(() => {
                window.location.href = 'index.html';
            }, 500);
            
            event.preventDefault(); // Prevent immediate navigation
        });
    }

    // Handle game cards
    const gameCards = document.querySelectorAll('.game-card');

    if (gameCards) {
        let currentBackground = null;
        
        gameCards.forEach(card => {
            card.addEventListener('mouseenter', () => {
                const gameType = card.dataset.game;
                const body = document.body;
                
                // Remove previous background class if exists
                if (currentBackground) {
                    body.classList.remove('hover-' + currentBackground);
                }
                
                // Set new background
                body.classList.add('hover-' + gameType);
                currentBackground = gameType;
            });

            card.addEventListener('click', () => {
                const gameType = card.dataset.game;
                const menuContainer = document.querySelector('.menu-container');
                
                if (gameType === 'grow-word' || gameType === 'letterscapes') {
                    showToast('Under Development');
                } else if (gameType === 'spell-quest') {
                    menuContainer.classList.add('slide-out');
                    setTimeout(() => {
                        window.location.href = './spellbee/spellbeemenu.html';
                    }, 500);
                }
                playClickSound(); // Play click sound instead of game open sound
            });
        });
    }

    // Toast notification function with sound and blur overlay
    window.showToast = function(message) {
        const toast = document.getElementById('toast');
        const overlay = document.querySelector('.toast-overlay');
        
        if (toast && overlay) {
            // Play toast notification sound
            const toastSound = new Audio('assets/sounds/toast/toastnotifwarn.mp3');
            toastSound.volume = 0.5; // Set volume to 50%
            toastSound.play().catch(error => {
                console.log('Error playing toast sound:', error);
            });
            
            // Show overlay and toast
            overlay.classList.add('show');
            toast.textContent = message;
            toast.classList.remove('hide');
            toast.classList.add('show');
            
            // Hide the toast and overlay after 3 seconds
            setTimeout(() => {
                toast.classList.remove('show');
                toast.classList.add('hide');
                overlay.classList.remove('show');
            }, 1500);
        } else {
            console.error('Toast or overlay elements not found');
        }
    }
});

function playClickSound() {
    const clickSound = new Audio('assets/sounds/clicks/mixkit-stapling-paper-2995.wav');
    clickSound.play();
}

function addClickEffect(button) {
    button.style.transform = 'scale(0.95)';
    setTimeout(() => {
        button.style.transform = 'scale(1)';
    }, 100);
}
