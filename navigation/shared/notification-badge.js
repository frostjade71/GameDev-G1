// Notification Badge Update Functionality
// This file should be included in all pages that have a notification icon

function updateNotificationBadge() {
    const badge = document.querySelector('.notification-badge');
    if (!badge) return;
    
    // Make API call to get notification count
    fetch('navigation/get_notification_count.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const count = data.count;
                badge.textContent = count;
                
                // Add pulse animation if there are new notifications
                if (count > 0) {
                    badge.classList.add('pulse');
                } else {
                    badge.classList.remove('pulse');
                }
            }
        })
        .catch(error => {
            console.error('Error updating notification badge:', error);
        });
}

// Update notification badge on page load
document.addEventListener('DOMContentLoaded', function() {
    updateNotificationBadge();
    
    // Update every 30 seconds
    setInterval(updateNotificationBadge, 30000);
});

// Add pulse animation CSS if not already present
if (!document.getElementById('notification-badge-styles')) {
    const style = document.createElement('style');
    style.id = 'notification-badge-styles';
    style.textContent = `
        .notification-badge.pulse {
            animation: pulse 1s infinite;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
    `;
    document.head.appendChild(style);
}
