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
        // Add hover sound effect
        button.addEventListener('mouseenter', () => {
            playHoverSound();
        });
        
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

    // Handle game cards and play buttons
    const gameCards = document.querySelectorAll('.game-card');
    const playButtons = document.querySelectorAll('.play-button');
    
    if (playButtons) {
        playButtons.forEach(button => {
            button.addEventListener('click', (event) => {
                event.stopPropagation(); // Prevent card click
                const gameSelection = document.querySelector('.menu-container.game-selection');
                
                // Slide out game selection to the right
                gameSelection.classList.add('slide-out');
                
                // Get the game type from the parent card
                const gameType = button.closest('.game-card').dataset.game;
                
                // Here you can add logic to start each specific game
                setTimeout(() => {
                    console.log(`Starting ${gameType}`);
                    // Add your game start logic here
                }, 500);
            });
        });
    }

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
                console.log(`Selected ${gameType}`);
            });
        });
    }
});

function playHoverSound() {
    // Add hover sound implementation here
    // Example: const hoverSound = new Audio('hover.wav');
    // hoverSound.play();
}

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
