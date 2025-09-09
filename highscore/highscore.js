document.addEventListener('DOMContentLoaded', () => {
    // Get the back button element
    const backButton = document.getElementById('backToMenu');

    // Add click event listener to the back button
    if (backButton) {
        backButton.addEventListener('click', () => {
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
            
            // Navigate back to index immediately
            window.location.replace('../index.html');
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

// Load highscores from localStorage when page loads
document.addEventListener('DOMContentLoaded', () => {
    loadHighScores();
});

function loadHighScores() {
    const games = ['grow-word', 'grammar'];
    
    games.forEach(game => {
        const highScore = localStorage.getItem(`${game}-highscore`) || '0';
        updateScoreDisplay(game, parseInt(highScore));
    });
}

function updateScoreDisplay(game, score) {
    // Find the corresponding score element
    const gameSection = document.querySelector(`.game-section:has(h2:contains('${game === 'grow-word' ? 'Grow a Word' : 'Grammar Game'})')`);
    if (gameSection) {
        const scoreElement = gameSection.querySelector('.score');
        if (scoreElement) {
            scoreElement.textContent = score;
        }
    }
}

// Helper function to save a new high score
window.saveHighScore = function(game, score) {
    const currentHighScore = parseInt(localStorage.getItem(`${game}-highscore`)) || 0;
    if (score > currentHighScore) {
        localStorage.setItem(`${game}-highscore`, score.toString());
        loadHighScores(); // Refresh the display
    }
}
