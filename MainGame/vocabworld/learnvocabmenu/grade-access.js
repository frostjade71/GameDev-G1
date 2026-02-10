// Initialize toast/snackbar container and styles if they don't exist
function initToastSystem() {
    if (!document.getElementById('vocab-toast-container')) {
        const toastContainer = document.createElement('div');
        toastContainer.id = 'vocab-toast-container';
        document.body.appendChild(toastContainer);
    }
    
    if (!document.querySelector('style#vocab-toast-styles')) {
        const style = document.createElement('style');
        style.id = 'vocab-toast-styles';
        style.textContent = `
            #vocab-toast-container {
                position: fixed;
                bottom: 30px;
                left: 50%;
                transform: translateX(-50%);
                z-index: 99999;
                display: flex;
                flex-direction: column;
                align-items: center;
                width: auto;
                pointer-events: none;
            }
            
            .vocab-toast {
                background: rgba(15, 23, 42, 0.95);
                color: white;
                padding: 14px 28px;
                border-radius: 50px;
                margin-bottom: 10px;
                box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.4);
                opacity: 0;
                transform: translateY(20px);
                transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
                max-width: 90vw;
                min-width: 280px;
                word-wrap: break-word;
                border: 1px solid rgba(255, 255, 255, 0.15);
                backdrop-filter: blur(12px);
                font-family: 'Poppins', sans-serif;
                font-weight: 500;
                font-size: 0.95rem;
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 12px;
                pointer-events: auto;
            }
            
            .vocab-toast.show {
                opacity: 1;
                transform: translateY(0);
            }

            .vocab-toast-info i { color: #60a5fa; }
            .vocab-toast-success i { color: #4ade80; }
            .vocab-toast-error i { color: #f87171; }

            @media (max-width: 768px) {
                #vocab-toast-container {
                    bottom: 20px;
                }
                .vocab-toast {
                    padding: 12px 20px;
                    font-size: 0.85rem;
                    min-width: 250px;
                }
            }
        `;
        document.head.appendChild(style);
    }
}

// Global toast/snackbar notification function
window.showToast = function(message, type = 'info') {
    initToastSystem();
    
    const container = document.getElementById('vocab-toast-container');
    
    const toast = document.createElement('div');
    toast.className = `vocab-toast vocab-toast-${type}`;
    
    // Add icon based on type
    let icon = 'fa-info-circle';
    if (type === 'success') icon = 'fa-check-circle';
    if (type === 'error') icon = 'fa-exclamation-circle';
    
    toast.innerHTML = `<i class="fas ${icon}"></i><span>${message}</span>`;
    
    container.appendChild(toast);
    
    // Trigger animation
    setTimeout(() => toast.classList.add('show'), 10);
    
    // Remove toast after delay
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => {
            if (toast.parentNode === container) {
                container.removeChild(toast);
            }
        }, 400);
    }, 3000);
};

// Initialize grade access functionality
function initGradeAccess() {

    // Add click handlers to locked grade cards (as a fallback)
    document.addEventListener('click', function(e) {
        const cardContainer = e.target.closest('.grade-card-container.locked');
        if (cardContainer) {
            const cardLink = cardContainer.querySelector('a');
            if (cardLink) {
                e.preventDefault();
                e.stopPropagation();
                const gradeText = cardLink.querySelector('h2').textContent;
                const gradeNumber = gradeText.match(/\d+/)[0];
                if (window.showToast) {
                    window.showToast(`You are not a Grade ${gradeNumber} student`, 'error');
                }
            }
        }
    });
}

// Initialize when DOM is fully loaded
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initGradeAccess);
} else {
    initGradeAccess();
}
