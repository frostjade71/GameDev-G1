// Declare modal handling functions in global scope
function showLogoutModal() {
    const modalOverlay = document.getElementById('logoutModal');
    const logoutConfirmation = document.getElementById('logoutConfirmation');
    
    if (modalOverlay && logoutConfirmation) {
        modalOverlay.style.display = 'flex';
        setTimeout(() => {
            modalOverlay.classList.add('show');
            logoutConfirmation.classList.add('show');
        }, 10);
    }
}

function hideLogoutModal() {
    const modalOverlay = document.getElementById('logoutModal');
    const logoutConfirmation = document.getElementById('logoutConfirmation');
    
    if (modalOverlay && logoutConfirmation) {
        logoutConfirmation.classList.remove('show');
        logoutConfirmation.classList.add('hide');
        modalOverlay.classList.remove('show');
        setTimeout(() => {
            modalOverlay.style.display = 'none';
            logoutConfirmation.classList.remove('hide');
        }, 300);
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // Mobile menu toggle
    const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
    const sidebar = document.querySelector('.sidebar');

    if (mobileMenuBtn && sidebar) {
        mobileMenuBtn.addEventListener('click', function() {
            sidebar.classList.toggle('show');
            this.classList.toggle('active');
            document.body.classList.toggle('sidebar-open');
        });
    }

    // Logout modal click outside to close
    const modalOverlay = document.getElementById('logoutModal');
    const cancelBtn = document.querySelector('.cancel-btn');

    if (modalOverlay) {
        modalOverlay.addEventListener('click', function(e) {
            if (e.target === modalOverlay) {
                hideLogoutModal();
            }
        });
    }

    if (cancelBtn) {
        cancelBtn.addEventListener('click', hideLogoutModal);
    }

    // Close modal on escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modalOverlay && modalOverlay.classList.contains('show')) {
            hideLogoutModal();
        }
    });

    // Add smooth scrolling for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth'
                });
            }
        });
    });

    // Add animation on scroll for feature cards
    const observerOptions = {
        threshold: 0.1
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, observerOptions);

    document.querySelectorAll('.feature-card').forEach(card => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
        observer.observe(card);
    });
});