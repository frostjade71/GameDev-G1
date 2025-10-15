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
});

