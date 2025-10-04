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
            
            // Navigate back to index
            window.location.replace('../index.php');
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

// High scores are now loaded from PHP/database, no need for localStorage
// The scores are displayed directly in the PHP template

// Helper function to save a new high score (for use by games)
window.saveHighScore = function(gameType, score, level = 1) {
    const formData = new FormData();
    formData.append('action', 'save_score');
    formData.append('game_type', gameType);
    formData.append('score', score);
    formData.append('level', level);
    
    fetch('../api/save_score.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            console.log('Score saved successfully');
        } else {
            console.error('Error saving score:', data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}
