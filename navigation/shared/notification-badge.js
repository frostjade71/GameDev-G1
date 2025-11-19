// Notification Badge Update Functionality
// This file should be included in all pages that have a notification icon

function updateNotificationBadge() {
    const badge = document.querySelector('.notification-badge');
    if (!badge) return;
    
    // Make API call to get notification count
    // Use a base path that works from any location
    // Try different possible paths until we find the right one
    const possiblePaths = [
        'navigation/get_notification_count.php',
        '../navigation/get_notification_count.php',
        '../../navigation/get_notification_count.php'
    ];
    
    // Use the first path that matches the current directory structure
    let apiPath = possiblePaths[0]; // default
    const currentPath = window.location.pathname;
    
    if (currentPath.includes('/play/') || currentPath.includes('/settings/') || currentPath.includes('/credits/') || currentPath.includes('/overview/')) {
        apiPath = '../navigation/get_notification_count.php';
    } else if (currentPath.includes('/navigation/') && !currentPath.includes('/navigation/shared/')) {
        apiPath = '../get_notification_count.php';
    } else if (currentPath.includes('/navigation/friends/')) {
        apiPath = '../../navigation/get_notification_count.php';
    }
    
    console.log('Notification badge API path:', apiPath);
    
    fetch(apiPath)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
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
