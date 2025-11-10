// Menu Page JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Initialize menu functionality
    initializeMenu();
    initializeAnimations();
    initializeSoundEffects();
});

// Initialize menu functionality
function initializeMenu() {
    // Add click sound to all menu buttons
    const menuButtons = document.querySelectorAll('.menu-button');
    menuButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            // Play click sound
            playClickSound();
            
            // Add loading state
            this.style.opacity = '0.7';
            this.style.pointerEvents = 'none';
            
            // Reset after a short delay
            setTimeout(() => {
                this.style.opacity = '1';
                this.style.pointerEvents = 'auto';
            }, 300);
        });
    });

    // Add hover effects to banner CTA
    const bannerCta = document.querySelector('.banner-cta');
    if (bannerCta) {
        bannerCta.addEventListener('click', function(e) {
            playClickSound();
        });
    }

    // Add hover effects to feature items
    const featureItems = document.querySelectorAll('.feature-item');
    featureItems.forEach(item => {
        item.addEventListener('mouseenter', function() {
            this.style.transform = 'scale(1.05)';
            this.style.transition = 'transform 0.2s ease';
        });
        
        item.addEventListener('mouseleave', function() {
            this.style.transform = 'scale(1)';
        });
    });
}

// Initialize animations
function initializeAnimations() {
    // Animate banner on load
    const banner = document.querySelector('.whats-new-banner');
    if (banner) {
        banner.style.opacity = '0';
        banner.style.transform = 'translateY(-30px)';
        
        setTimeout(() => {
            banner.style.transition = 'all 0.8s ease-out';
            banner.style.opacity = '1';
            banner.style.transform = 'translateY(0)';
        }, 100);
    }

    // Animate menu buttons on load
    const menuButtons = document.querySelectorAll('.menu-button');
    menuButtons.forEach((button, index) => {
        button.style.opacity = '0';
        button.style.transform = 'translateY(20px)';
        
        setTimeout(() => {
            button.style.transition = 'all 0.6s ease-out';
            button.style.opacity = '1';
            button.style.transform = 'translateY(0)';
        }, 200 + (index * 100));
    });

    // Add parallax effect to banner background
    const bannerBg = document.querySelector('.banner-bg-image');
    if (bannerBg) {
        window.addEventListener('scroll', function() {
            const scrolled = window.pageYOffset;
            const parallax = scrolled * 0.5;
            bannerBg.style.transform = `translateY(${parallax}px)`;
        });
    }
}

// Initialize sound effects
function initializeSoundEffects() {
    // Sound effects initialization if needed
    // Hover sounds removed
}

// Play click sound function (if not already defined)
function playClickSound() {
    try {
        // Try to play click sound from existing sound system
        if (typeof window.playClickSound === 'function') {
            window.playClickSound();
            return;
        }
        
        // Fallback: create and play audio element
        const audio = new Audio('assets/sounds/clicks/mixkit-stapling-paper-2995.wav');
        audio.volume = 0.3;
        audio.play().catch(e => {
            console.log('Could not play click sound:', e);
        });
    } catch (error) {
        console.log('Click sound not available:', error);
    }
}

// Add keyboard navigation support
document.addEventListener('keydown', function(e) {
    const menuButtons = document.querySelectorAll('.menu-button');
    const activeElement = document.activeElement;
    let currentIndex = -1;
    
    // Find current focused element
    menuButtons.forEach((button, index) => {
        if (button === activeElement) {
            currentIndex = index;
        }
    });
    
    switch(e.key) {
        case 'ArrowRight':
        case 'ArrowDown':
            e.preventDefault();
            if (currentIndex < menuButtons.length - 1) {
                menuButtons[currentIndex + 1].focus();
            }
            break;
        case 'ArrowLeft':
        case 'ArrowUp':
            e.preventDefault();
            if (currentIndex > 0) {
                menuButtons[currentIndex - 1].focus();
            }
            break;
        case 'Enter':
        case ' ':
            e.preventDefault();
            if (activeElement && activeElement.classList.contains('menu-button')) {
                activeElement.click();
            }
            break;
    }
});

// Add focus styles for accessibility
const style = document.createElement('style');
style.textContent = `
    .menu-button:focus {
        outline: 2px solid rgba(96, 239, 255, 0.8);
        outline-offset: 2px;
        box-shadow: 0 0 0 4px rgba(96, 239, 255, 0.2);
    }
    
    .banner-cta:focus {
        outline: 2px solid rgba(0, 255, 135, 0.8);
        outline-offset: 2px;
        box-shadow: 0 0 0 4px rgba(0, 255, 135, 0.2);
    }
`;
document.head.appendChild(style);

// Add loading states for better UX
function showLoadingState(element) {
    const originalContent = element.innerHTML;
    element.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
    element.style.pointerEvents = 'none';
    
    return function hideLoadingState() {
        element.innerHTML = originalContent;
        element.style.pointerEvents = 'auto';
    };
}

// Add error handling for failed navigation
function handleNavigationError(error) {
    console.error('Navigation error:', error);
    
    // Show error toast if available
    if (typeof showToast === 'function') {
        showToast('Navigation failed. Please try again.');
    } else {
        alert('Navigation failed. Please try again.');
    }
}

// Add performance monitoring
function trackMenuPerformance() {
    const startTime = performance.now();
    
    window.addEventListener('load', function() {
        const endTime = performance.now();
        const loadTime = endTime - startTime;
        
        console.log(`Menu page loaded in ${loadTime.toFixed(2)}ms`);
        
        // Track user engagement
        trackUserEngagement();
    });
}

// Track user engagement
function trackUserEngagement() {
    let engagementTime = 0;
    let isActive = true;
    
    const startTime = Date.now();
    
    // Track time spent on page
    const engagementInterval = setInterval(() => {
        if (isActive) {
            engagementTime += 1000;
        }
    }, 1000);
    
    // Track when user becomes inactive
    document.addEventListener('visibilitychange', function() {
        isActive = !document.hidden;
    });
    
    // Track when user leaves page
    window.addEventListener('beforeunload', function() {
        clearInterval(engagementInterval);
        console.log(`User spent ${engagementTime / 1000} seconds on menu page`);
    });
}

// Initialize performance tracking
trackMenuPerformance();

// Add smooth scrolling for better UX
function smoothScrollTo(element) {
    element.scrollIntoView({
        behavior: 'smooth',
        block: 'center'
    });
}

// Add intersection observer for animations
function initializeIntersectionObserver() {
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate-in');
            }
        });
    }, {
        threshold: 0.1
    });
    
    // Observe menu buttons
    const menuButtons = document.querySelectorAll('.menu-button');
    menuButtons.forEach(button => {
        observer.observe(button);
    });
}

// Initialize intersection observer
initializeIntersectionObserver();

// Add CSS for intersection observer animations
const animationStyle = document.createElement('style');
animationStyle.textContent = `
    .menu-button {
        transition: all 0.3s ease;
    }
    
    .menu-button.animate-in {
        animation: slideInUp 0.6s ease-out;
    }
    
    @keyframes slideInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
`;
document.head.appendChild(animationStyle);





