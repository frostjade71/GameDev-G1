document.addEventListener('DOMContentLoaded', () => {
    // Mobile menu is now handled by CSS-only approach

    // Greeting update is handled by script.js

    // Game Selection Carousel Logic
    // Carousel logic removed as we switched to card view

    // Navigation links are handled by script.js

    // Notification and profile handling is done by script.js

    // Handle back link/button (only for elements without specific onclick handlers)
    const backElements = document.querySelectorAll('.back-button, .back-link');
    backElements.forEach(backElement => {
        // Only add listener if the element doesn't have an onclick handler
        if (!backElement.onclick) {
            backElement.addEventListener('click', (event) => {
                playClickSound();
                window.location.href = '../menu.php?from=selection';
            });
        }
    });

    // Handle game cards
    const gameCards = document.querySelectorAll('.game-card');
    console.log('Found game cards:', gameCards.length); // Debug log

    if (gameCards) {
        let currentBackground = null;

        gameCards.forEach((card, index) => {
            const gameType = card.dataset.game;
            console.log(`Card ${index}: gameType = ${gameType}`); // Debug log

            card.addEventListener('mouseenter', () => {
                const gameType = card.dataset.game;
                const body = document.body;

                // Only proceed if the card has a valid game type
                if (!gameType) {
                    return;
                }

                // Remove previous background class if exists
                if (currentBackground) {
                    body.classList.remove('hover-' + currentBackground);
                }

                // Set new background
                body.classList.add('hover-' + gameType);
                currentBackground = gameType;
            });

            card.addEventListener('mouseleave', () => {
                const body = document.body;
                if (currentBackground) {
                    body.classList.remove('hover-' + currentBackground);
                    currentBackground = null;
                }
            });

            // Remove any existing click listeners first
            card.removeEventListener('click', card._clickHandler);

            // Create new click handler
            card._clickHandler = (event) => {
                event.preventDefault();
                event.stopPropagation();

                const gameType = card.dataset.game;
                console.log('Game card clicked, gameType:', gameType); // Debug log

                if (gameType === 'vocabbg') {
                    // Redirect to VocabWorld game loader
                    console.log('Redirecting to VocabWorld Loader...'); // Debug log
                    playClickSound();
                    // Try immediate redirect
                    setTimeout(() => {
                        window.location.href = '../MainGame/vocabworld/loading/entering.html';
                    }, 100);
                } else if (gameType === 'grammarbg') {
                    showToast('Grammar Heroes - Coming Soon!');
                } else if (!gameType) {
                    showToast('Coming Soon!');
                } else {
                    console.log('Unknown game type:', gameType);
                    showToast('more games soon', '../assets/pixels/hammer.png');
                }
            };

            card.addEventListener('click', card._clickHandler);
        });
    }

    // Handle back button
    const backButton = document.getElementById('backToMenu');

    if (backButton) {
        backButton.addEventListener('click', (e) => {
            e.preventDefault();

            // Play click sound
            playClickSound();

            // Add visual feedback
            backButton.style.transform = 'scale(0.95)';
            setTimeout(() => {
                backButton.style.transform = 'scale(1)';
            }, 100);

            // Navigate back to menu with from parameter
            window.location.href = '../menu.php?from=selection';
        });
    }
});

