document.addEventListener('DOMContentLoaded', () => {
    // Handle search functionality
    const searchInput = document.querySelector('.search-input');
    const searchBtn = document.querySelector('.search-btn');
    const favoritesGrid = document.querySelector('.favorites-grid');
    const emptyState = document.querySelector('.empty-state');

    if (searchBtn) {
        searchBtn.addEventListener('click', () => {
            performSearch();
        });
    }

    if (searchInput) {
        searchInput.addEventListener('keyup', (e) => {
            if (e.key === 'Enter') {
                performSearch();
            }
        });
    }

    function performSearch() {
        const searchTerm = searchInput.value.toLowerCase();
        const cards = document.querySelectorAll('.favorite-card');
        let hasVisibleCards = false;

        cards.forEach(card => {
            const title = card.querySelector('h3').textContent.toLowerCase();
            if (title.includes(searchTerm)) {
                card.style.display = 'block';
                hasVisibleCards = true;
            } else {
                card.style.display = 'none';
            }
        });

        // Show/hide empty state based on search results
        emptyState.style.display = hasVisibleCards ? 'none' : 'block';
    }

    // Handle remove favorite functionality
    const removeButtons = document.querySelectorAll('.remove-favorite');
    removeButtons.forEach(button => {
        button.addEventListener('click', (e) => {
            const card = e.target.closest('.favorite-card');
            if (card) {
                // Add fade-out animation
                card.style.opacity = '0';
                card.style.transform = 'scale(0.9)';
                card.style.transition = 'all 0.3s ease';

                // Remove card after animation
                setTimeout(() => {
                    card.remove();
                    // Check if there are any remaining cards
                    const remainingCards = document.querySelectorAll('.favorite-card');
                    if (remainingCards.length === 0) {
                        emptyState.style.display = 'block';
                    }
                }, 300);

                showToast('Game removed from favorites');
            }
        });
    });

    // Handle play button clicks
    const playButtons = document.querySelectorAll('.play-btn');
    playButtons.forEach(button => {
        button.addEventListener('click', () => {
            // This would typically navigate to the game
            showToast('Launching game...');
        });
    });

    // Check if there are any favorite cards
    const hasCards = document.querySelectorAll('.favorite-card').length > 0;
    emptyState.style.display = hasCards ? 'none' : 'block';
});
