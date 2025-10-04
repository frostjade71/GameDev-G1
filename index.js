document.addEventListener('DOMContentLoaded', () => {
    // Mobile menu is now handled by CSS-only approach

    // Greeting update is handled by script.js


    // Menu container animation removed - element doesn't exist in current HTML

    // Button handling removed - these classes don't exist in current HTML

    // Handle dashboard cards
    const dashboardCards = document.querySelectorAll('.dashboard-card');
    dashboardCards.forEach(card => {
        card.addEventListener('click', (e) => {
            playClickSound();
            addClickEffect(card);
        });
    });

    // Navigation links are handled by script.js

    // Notification and profile handling is done by script.js
});

