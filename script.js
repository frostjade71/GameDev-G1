// Shared JavaScript functions and utilities
// This file contains common functions used across multiple pages

// Toast notification function with sound and blur overlay
window.showToast = function(message) {
    const toast = document.getElementById('toast');
    const overlay = document.querySelector('.toast-overlay');
    
    if (toast && overlay) {
        // Play toast notification sound
        const toastSound = new Audio('assets/sounds/toast/toastnotifwarn.mp3');
        toastSound.volume = 0.5; // Set volume to 50%
        toastSound.play().catch(error => {
            console.log('Error playing toast sound:', error);
        });
        
        // Show overlay and toast
        overlay.classList.add('show');
        toast.textContent = message;
        toast.classList.remove('hide');
        toast.classList.add('show');
        
        // Hide the toast and overlay after 3 seconds
        setTimeout(() => {
            toast.classList.remove('show');
            toast.classList.add('hide');
            overlay.classList.remove('show');
        }, 1500);
    } else {
        console.error('Toast or overlay elements not found');
    }
}

// Click sound function
function playClickSound() {
    const clickSound = new Audio('assets/sounds/clicks/mixkit-stapling-paper-2995.wav');
    clickSound.play().catch(error => {
        console.log('Error playing click sound:', error);
    });
}

// Add click effect to buttons
function addClickEffect(button) {
    button.style.transform = 'scale(0.95)';
    setTimeout(() => {
        button.style.transform = 'scale(1)';
    }, 100);
}

// Mobile menu functionality
document.addEventListener('DOMContentLoaded', () => {
    const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
    const sidebar = document.querySelector('.sidebar');
    
    if (mobileMenuBtn && sidebar) {
        mobileMenuBtn.addEventListener('click', () => {
            // Play click sound
            playClickSound();
            
            // Add visual feedback
            addClickEffect(mobileMenuBtn);
            
            // Toggle sidebar
            sidebar.classList.toggle('active');
        });
        
        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', (e) => {
            if (window.innerWidth <= 768) {
                if (!sidebar.contains(e.target) && !mobileMenuBtn.contains(e.target)) {
                    sidebar.classList.remove('active');
                }
            }
        });
        
        // Close sidebar when clicking on nav links
        const navLinks = document.querySelectorAll('.nav-link');
        navLinks.forEach(link => {
            link.addEventListener('click', () => {
                if (window.innerWidth <= 768) {
                    sidebar.classList.remove('active');
                }
            });
        });
    }

    // Add click effects to dashboard cards
    const dashboardCards = document.querySelectorAll('.dashboard-card');
    dashboardCards.forEach(card => {
        card.addEventListener('click', (e) => {
            // Play click sound
            playClickSound();
            
            // Add visual feedback
            addClickEffect(card);
        });
    });

    // Add click effect to banner CTA
    const bannerCta = document.querySelector('.banner-cta');
    if (bannerCta) {
        bannerCta.addEventListener('click', (e) => {
            // Play click sound
            playClickSound();
            
            // Add visual feedback
            addClickEffect(bannerCta);
        });
    }

    // Add hover effects to feature items
    const featureItems = document.querySelectorAll('.feature-item');
    featureItems.forEach(item => {
        item.addEventListener('mouseenter', () => {
            item.style.transform = 'translateY(-2px)';
            item.style.transition = 'transform 0.2s ease';
        });
        
        item.addEventListener('mouseleave', () => {
            item.style.transform = 'translateY(0)';
        });
    });

    // Parallax effect for banner background
    const banner = document.querySelector('.whats-new-banner');
    if (banner) {
        window.addEventListener('scroll', () => {
            const scrolled = window.pageYOffset;
            const rate = scrolled * -0.5;
            const bgImage = banner.querySelector('.banner-bg-image');
            if (bgImage) {
                bgImage.style.transform = `translateY(${rate}px)`;
            }
        });
    }

    // Intersection Observer for animations
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, observerOptions);

    // Observe dashboard cards for animation
    dashboardCards.forEach(card => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        observer.observe(card);
    });

    // Add loading animation for banner
    const bannerContent = document.querySelector('.banner-content');
    if (bannerContent) {
        bannerContent.style.opacity = '0';
        bannerContent.style.transform = 'translateY(30px)';
        bannerContent.style.transition = 'opacity 0.8s ease, transform 0.8s ease';
        
        setTimeout(() => {
            bannerContent.style.opacity = '1';
            bannerContent.style.transform = 'translateY(0)';
        }, 200);
    }

    // Add ripple effect to buttons
    function createRipple(event) {
        const button = event.currentTarget;
        const circle = document.createElement('span');
        const diameter = Math.max(button.clientWidth, button.clientHeight);
        const radius = diameter / 2;

        circle.style.width = circle.style.height = `${diameter}px`;
        circle.style.left = `${event.clientX - button.offsetLeft - radius}px`;
        circle.style.top = `${event.clientY - button.offsetTop - radius}px`;
        circle.classList.add('ripple');

        const ripple = button.getElementsByClassName('ripple')[0];
        if (ripple) {
            ripple.remove();
        }

        button.appendChild(circle);
    }

    // Add ripple effect to dashboard cards and CTA
    const rippleElements = document.querySelectorAll('.dashboard-card, .banner-cta');
    rippleElements.forEach(element => {
        element.addEventListener('click', createRipple);
    });

    // Add CSS for ripple effect
    const style = document.createElement('style');
    style.textContent = `
        .ripple {
            position: absolute;
            border-radius: 50%;
            background-color: rgba(255, 255, 255, 0.3);
            transform: scale(0);
            animation: ripple-animation 0.6s linear;
            pointer-events: none;
        }

        @keyframes ripple-animation {
            to {
                transform: scale(4);
                opacity: 0;
            }
        }

        .dashboard-card, .banner-cta {
            position: relative;
            overflow: hidden;
        }
    `;
    document.head.appendChild(style);
});

// Utility function to check if element is in viewport
function isInViewport(element) {
    const rect = element.getBoundingClientRect();
    return (
        rect.top >= 0 &&
        rect.left >= 0 &&
        rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
        rect.right <= (window.innerWidth || document.documentElement.clientWidth)
    );
}

// Add scroll-based animations
window.addEventListener('scroll', () => {
    const banner = document.querySelector('.whats-new-banner');
    if (banner && isInViewport(banner)) {
        banner.style.transform = 'scale(1.02)';
        banner.style.transition = 'transform 0.3s ease';
    } else if (banner) {
        banner.style.transform = 'scale(1)';
    }
});

// Add keyboard navigation support
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        // Close any open modals or sidebars
        const sidebar = document.querySelector('.sidebar');
        if (sidebar && sidebar.classList.contains('active')) {
            sidebar.classList.remove('active');
        }
        
        const logoutModal = document.getElementById('logoutModal');
        if (logoutModal && logoutModal.classList.contains('show')) {
            hideLogoutModal();
        }
    }
});

// Add touch support for mobile
if ('ontouchstart' in window) {
    document.addEventListener('touchstart', (e) => {
        // Add touch feedback
        const target = e.target.closest('.dashboard-card, .banner-cta, .mobile-menu-btn');
        if (target) {
            target.style.transform = 'scale(0.98)';
        }
    });
    
    document.addEventListener('touchend', (e) => {
        const target = e.target.closest('.dashboard-card, .banner-cta, .mobile-menu-btn');
        if (target) {
            setTimeout(() => {
                target.style.transform = 'scale(1)';
            }, 100);
        }
    });
}

