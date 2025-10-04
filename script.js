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

function playClickSound() {
    const clickSound = new Audio('assets/sounds/clicks/mixkit-stapling-paper-2995.wav');
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
});