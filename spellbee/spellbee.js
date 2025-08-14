document.addEventListener('DOMContentLoaded', () => {
    // Add entry animation for menu
    const menuContainer = document.querySelector('.menu-container');
    if (menuContainer) {
        // Force a reflow before adding the slide-in class
        void menuContainer.offsetWidth;
        
        // Add slide-in class to trigger the animation
        menuContainer.classList.add('slide-in');
    }

    // Handle button clicks
    const buttons = document.querySelectorAll('.menu-button, .back-button');
    
    buttons.forEach(button => {
        // Add click sound and visual effect
        button.addEventListener('click', () => {
            playClickSound();
            addClickEffect(button);

            // Handle back button
            if (button.classList.contains('back-button')) {
                menuContainer.classList.remove('slide-in');
                menuContainer.classList.add('slide-out');
                
                setTimeout(() => {
                    window.location.href = '../game-selection.html';
                }, 500);
            }

            // Handle other buttons
            if (button.id === 'select-levels') {
                // TODO: Implement levels selection
                showToast('Levels selection coming soon');
            } else if (button.id === 'scores') {
                // TODO: Implement scores display
                showToast('Scores feature coming soon');
            }
        });
    });

    // Toast notification function
    window.showToast = function(message) {
        const toast = document.getElementById('toast');
        toast.textContent = message;
        toast.classList.add('show');
        toast.classList.remove('hide');
        
        setTimeout(() => {
            toast.classList.remove('show');
            toast.classList.add('hide');
        }, 3000);
    }
});

function playClickSound() {
    const clickSound = new Audio('../assets/sounds/clicks/mixkit-stapling-paper-2995.wav');
    clickSound.play();
}

function addClickEffect(button) {
    button.style.transform = 'scale(0.95)';
    setTimeout(() => {
        button.style.transform = 'scale(1)';
    }, 100);
}
