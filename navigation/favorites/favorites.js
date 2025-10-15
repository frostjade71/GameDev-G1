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
        const cards = document.querySelectorAll('.favorite-game-card');
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
        if (emptyState) {
            emptyState.style.display = hasVisibleCards ? 'none' : 'block';
        }
    }

    // Handle remove favorite functionality
    const removeButtons = document.querySelectorAll('.remove-favorite-btn');
    removeButtons.forEach(button => {
        button.addEventListener('click', (e) => {
            const card = e.target.closest('.favorite-game-card');
            if (card) {
                const gameType = card.dataset.game;
                removeFavorite(gameType);
            }
        });
    });

    // Play button functionality removed - no longer needed

    // Check if there are any favorite cards
    const hasCards = document.querySelectorAll('.favorite-game-card').length > 0;
    if (emptyState) {
        emptyState.style.display = hasCards ? 'none' : 'block';
    }
});
