// This file is no longer used - form handling moved to inline JavaScript in profile.php
// Keeping only the non-form functionality

// Debounce helper function
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

document.addEventListener('DOMContentLoaded', () => {
    // Optimize hover effects for stat cards
    const statCards = document.querySelectorAll('.stat-card');
    
    // Use passive event listeners for better scrolling performance
    const passiveOptions = { passive: true };
    
    // Throttle scroll and resize events
    const handleScroll = debounce(() => {
        // Any scroll-dependent updates can go here
    }, 100);
    
    // Add passive scroll event listener
    window.addEventListener('scroll', handleScroll, passiveOptions);
    
    // Optimize stat card hover effects
    const handleCardHover = (card) => {
        card.style.willChange = 'transform, border-color';
        card.style.transform = 'translateY(-3px)';
        card.style.borderColor = 'rgba(0, 255, 135, 0.8)';
    };
    
    const handleCardLeave = (card) => {
        card.style.transform = 'translateY(0)';
        card.style.borderColor = 'rgba(96, 239, 255, 0.2)';
    };
    
    // Use requestAnimationFrame for smoother animations
    const animateStatCards = () => {
        statCards.forEach(card => {
            card.addEventListener('mouseenter', () => {
                requestAnimationFrame(() => handleCardHover(card));
            });
            
            card.addEventListener('mouseleave', () => {
                requestAnimationFrame(() => handleCardLeave(card));
            });
        });
    };
    
    // Run animations on next frame
    requestAnimationFrame(animateStatCards);

    // Handle avatar change button with debounced click
    const changeAvatarBtn = document.querySelector('.change-avatar-btn');
    if (changeAvatarBtn) {
        changeAvatarBtn.addEventListener('click', debounce(() => {
            // This would typically open a file picker
            showToast('Avatar change feature coming soon!');
        }, 250));
    }
    
    // Optimize scroll events for mobile menu
    const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
    const sidebar = document.querySelector('.sidebar');
    
    if (mobileMenuBtn && sidebar) {
        const toggleMenu = () => {
            const isOpen = sidebar.classList.contains('active');
            sidebar.style.transition = 'transform 0.3s ease-in-out';
            sidebar.style.transform = isOpen ? 'translateX(-100%)' : 'translateX(0)';
            sidebar.classList.toggle('active', !isOpen);
            document.body.style.overflow = isOpen ? '' : 'hidden';
        };
        
        mobileMenuBtn.addEventListener('click', debounce(toggleMenu, 100));
        
        // Close menu when clicking outside
        document.addEventListener('click', (e) => {
            if (window.innerWidth <= 768 && 
                sidebar.classList.contains('active') && 
                !sidebar.contains(e.target) && 
                !mobileMenuBtn.contains(e.target)) {
                toggleMenu();
            }
        }, { passive: true });
    }
});
