// Initialize toast container and styles if they don't exist
function initToastSystem() {
    if (!document.getElementById('toast-container')) {
        const toastContainer = document.createElement('div');
        toastContainer.id = 'toast-container';
        document.body.appendChild(toastContainer);
    }
    
    if (!document.querySelector('link[href*="Press+Start+2P"]')) {
        const link = document.createElement('link');
        link.href = 'https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap';
        link.rel = 'stylesheet';
        document.head.appendChild(link);
    }
    
    if (!document.querySelector('style#toast-styles')) {
        const style = document.createElement('style');
        style.id = 'toast-styles';
        style.textContent = `
            #toast-container {
                position: fixed;
                top: 100px;
                right: 120px;
                z-index: 9999;
                display: flex;
                flex-direction: column;
                align-items: flex-end;
                pointer-events: none;
            }
            
            @media (min-width: 768px) {
                #toast-container {
                    right: 40px;
                }
            }
            
            @media (min-width: 1024px) {
                #toast-container {
                    right: 270px;
                }
            }
            
            .toast {
                background: rgba(0, 0, 0, 0.9);
                color: white;
                padding: 12px 20px;
                border-radius: 4px;
                margin: 5px 0;
                box-shadow: 0 0 10px rgba(96, 239, 255, 0.3);
                opacity: 0;
                transform: translateX(100%);
                transition: all 0.3s cubic-bezier(0.68, -0.55, 0.27, 1.55);
                max-width: 300px;
                min-width: 250px;
                text-align: center;
                word-wrap: break-word;
                position: relative;
                overflow: hidden;
                font-family: 'Press Start 2P', cursive;
                font-size: 0.45rem;
                line-height: 1.5;
            }
            
            .toast.show {
                opacity: 1;
                transform: translateX(0);
            }
            
            .toast::after {
                content: '';
                position: absolute;
                bottom: 0;
                left: 0;
                width: 100%;
                height: 4px;
                background: #e74c3c;
                animation: progress 3s linear forwards;
            }
            
            @keyframes progress {
                from { width: 100%; }
                to { width: 0%; }
            }
            
            .toast-error {
                border-left: 4px solid #e74c3c;
            }
        `;
        document.head.appendChild(style);
    }
}

// Global toast notification function
window.showToast = function(message, type = 'info') {
    initToastSystem();
    
    const container = document.getElementById('toast-container');
    
    // Remove any existing toasts
    while (container.firstChild) {
        container.removeChild(container.firstChild);
    }
    
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.textContent = message;
    
    container.appendChild(toast);
    
    // Trigger reflow
    void toast.offsetWidth;
    
    // Show toast
    toast.classList.add('show');
    
    // Remove toast after delay
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => {
            if (toast.parentNode === container) {
                container.removeChild(toast);
            }
        }, 300);
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
