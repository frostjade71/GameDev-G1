document.addEventListener('DOMContentLoaded', () => {
    // Mobile menu is now handled by CSS-only approach

    // Greeting update is handled by script.js

    // Game Selection Carousel Logic
    const carouselTrack = document.querySelector('.carousel-track');
    const carouselContainer = document.querySelector('.carousel-container');
    if (carouselTrack && carouselContainer) {
        const cards = Array.from(carouselTrack.children);
        const prevButton = document.querySelector('.carousel-button.prev');
        const nextButton = document.querySelector('.carousel-button.next');
        const dotsContainer = document.querySelector('.carousel-dots');

        let currentIndex = 0;

        // Create dots (limited to 3)
        const maxDots = 3;
        for (let i = 0; i < maxDots; i++) {
            const dot = document.createElement('div');
            dot.classList.add('carousel-dot');
            if (i === 0) dot.classList.add('active');
            dotsContainer.appendChild(dot);

            dot.addEventListener('click', () => {
                moveToSlide(i);
            });
        }

        const dots = Array.from(dotsContainer.children);

        // Update dots
        const updateDots = (index) => {
            dots.forEach(dot => dot.classList.remove('active'));
            // Map the current index to the appropriate dot (0-2 for 3 dots)
            const dotIndex = Math.min(index, maxDots - 1);
            dots[dotIndex].classList.add('active');
        };

        // Move to specific slide
        const moveToSlide = (index) => {
            const slideWidth = cards[0].getBoundingClientRect().width;
            const gap = 32; // 2rem gap
            carouselTrack.style.transform = `translateX(-${index * (slideWidth + gap)}px)`;
            currentIndex = index;
            updateDots(index);

            // Update button states
            prevButton.style.opacity = index === 0 ? '0.5' : '1';
            nextButton.style.opacity = index === cards.length - 1 ? '0.5' : '1';
        };

        // Button click handlers
        prevButton.addEventListener('click', () => {
            if (currentIndex > 0) {
                moveToSlide(currentIndex - 1);
                playClickSound();
            }
        });

        nextButton.addEventListener('click', () => {
            if (currentIndex < cards.length - 1) {
                moveToSlide(currentIndex + 1);
                playClickSound();
            }
        });

        // Initialize button states
        prevButton.style.opacity = '0.5';
    }

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
                    // Redirect to VocabWorld game
                    console.log('Redirecting to VocabWorld...'); // Debug log
                    playClickSound();
                    // Try immediate redirect
                    setTimeout(() => {
                        window.location.href = '../MainGame/vocabworld/index.php';
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

