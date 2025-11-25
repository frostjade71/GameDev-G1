document.addEventListener('DOMContentLoaded', () => {
    // Mobile menu is now handled by CSS-only approach

    // Greeting update is handled by script.js

    // Navigation links are handled by script.js

    // Notification and profile handling is done by script.js

    // Handle back button
    const backButton = document.getElementById('backToMenu');
    
    if (backButton) {
        backButton.addEventListener('click', (e) => {
            e.preventDefault();
            
            // Play click sound
            const clickSound = new Audio('assets/sounds/clicks/mixkit-stapling-paper-2995.wav');
            clickSound.play().catch(error => {
                console.log('Error playing click sound:', error);
            });

            // Add visual feedback
            backButton.style.transform = 'scale(0.95)';
            setTimeout(() => {
                backButton.style.transform = 'scale(1)';
                // Navigate back to menu with from parameter
                window.location.href = 'index.php?from=credits';
            }, 100);
        });
    }

    // Gravatar Profile Card functionality
    const developerName = document.getElementById('developerName');
    const gravatarContainer = document.getElementById('gravatarCardContainer');
    const gravatarOverlay = document.getElementById('gravatarOverlay');
    const closeGravatar = document.getElementById('closeGravatar');

    // Show Gravatar card when clicking developer name
    if (developerName) {
        developerName.addEventListener('click', () => {
            if (gravatarContainer && gravatarOverlay) {
                gravatarOverlay.classList.add('show');
                gravatarContainer.classList.add('show');
                document.body.style.overflow = 'hidden'; // Prevent background scrolling
            }
        });
    }

    // Close Gravatar card when clicking close button
    if (closeGravatar) {
        closeGravatar.addEventListener('click', () => {
            closeGravatarCard();
        });
    }

    // Close Gravatar card when clicking overlay
    if (gravatarOverlay) {
        gravatarOverlay.addEventListener('click', () => {
            closeGravatarCard();
        });
    }

    // Close Gravatar card when pressing Escape key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && gravatarContainer && gravatarContainer.classList.contains('show')) {
            closeGravatarCard();
        }
    });

    // Function to close Gravatar card
    function closeGravatarCard() {
        if (gravatarContainer && gravatarOverlay) {
            gravatarOverlay.classList.remove('show');
            gravatarContainer.classList.remove('show');
            document.body.style.overflow = ''; // Restore scrolling
        }
    }
});

