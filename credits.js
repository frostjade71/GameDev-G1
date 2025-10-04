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
            playClickSound();
            
            // Add visual feedback
            backButton.style.transform = 'scale(0.95)';
            setTimeout(() => {
                backButton.style.transform = 'scale(1)';
            }, 100);
            
            // Navigate back to index
            window.location.replace('index.php');
        });
    }
});

