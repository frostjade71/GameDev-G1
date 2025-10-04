// User Profile Page JavaScript Functionality

document.addEventListener('DOMContentLoaded', function() {
    initializeUserProfilePage();
});

function initializeUserProfilePage() {
    // Initialize mobile menu functionality
    initializeMobileMenu();
    
    // Initialize notification system
    initializeNotificationSystem();
    
    // Load notifications
    loadNotifications();
    
    // Set up real-time updates
    setupRealTimeUpdates();
}

function initializeMobileMenu() {
    const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
    const sidebar = document.querySelector('.sidebar');
    
    if (mobileMenuBtn && sidebar) {
        mobileMenuBtn.addEventListener('click', function() {
            sidebar.classList.toggle('active');
        });
        
        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(event) {
            if (window.innerWidth <= 768) {
                if (!sidebar.contains(event.target) && !mobileMenuBtn.contains(event.target)) {
                    sidebar.classList.remove('active');
                }
            }
        });
        
        // Close sidebar when window is resized to desktop
        window.addEventListener('resize', function() {
            if (window.innerWidth > 768) {
                sidebar.classList.remove('active');
            }
        });
    }
}

function initializeNotificationSystem() {
    // Set up notification dropdown toggle
    const notificationIcon = document.querySelector('.notification-icon');
    const notificationDropdown = document.getElementById('notificationDropdown');
    
    if (notificationIcon && notificationDropdown) {
        notificationIcon.addEventListener('click', function(e) {
            e.stopPropagation();
            toggleNotificationDropdown();
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!notificationIcon.contains(e.target) && !notificationDropdown.contains(e.target)) {
                notificationDropdown.classList.remove('show');
            }
        });
    }
}

function toggleNotificationDropdown() {
    const dropdown = document.getElementById('notificationDropdown');
    if (dropdown) {
        dropdown.classList.toggle('show');
        
        // Load notifications when dropdown is opened
        if (dropdown.classList.contains('show')) {
            loadNotifications();
        }
    }
}

function loadNotifications() {
    // Simulate loading notifications from server
    const notificationList = document.getElementById('notificationList');
    const notificationBadge = document.getElementById('notificationBadge');
    
    if (!notificationList) return;
    
    // Mock notifications data (replace with actual API call)
    const notifications = [
        {
            id: 1,
            type: 'friend_request',
            message: 'Alex Johnson sent you a friend request',
            time: '2 minutes ago',
            unread: true
        },
        {
            id: 2,
            type: 'friend_request',
            message: 'Sarah Wilson sent you a friend request',
            time: '1 hour ago',
            unread: true
        },
        {
            id: 3,
            type: 'friend_request',
            message: 'Mike Chen sent you a friend request',
            time: '3 hours ago',
            unread: false
        }
    ];
    
    // Update notification badge
    const unreadCount = notifications.filter(n => n.unread).length;
    if (notificationBadge) {
        notificationBadge.textContent = unreadCount;
        notificationBadge.style.display = unreadCount > 0 ? 'flex' : 'none';
    }
    
    // Render notifications
    if (notifications.length === 0) {
        notificationList.innerHTML = `
            <div class="notification-item">
                <div class="notification-content">
                    <div class="notification-text">
                        <p class="notification-message">No notifications yet</p>
                    </div>
                </div>
            </div>
        `;
    } else {
        notificationList.innerHTML = notifications.map(notification => `
            <div class="notification-item ${notification.unread ? 'unread' : ''}" onclick="handleNotificationClick(${notification.id})">
                <div class="notification-content">
                    <div class="notification-icon">
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <div class="notification-text">
                        <p class="notification-message">${notification.message}</p>
                        <p class="notification-time">${notification.time}</p>
                    </div>
                </div>
            </div>
        `).join('');
    }
}

function handleNotificationClick(notificationId) {
    // Handle notification click (e.g., navigate to friend requests page)
    console.log('Notification clicked:', notificationId);
    
    // Mark as read
    markNotificationAsRead(notificationId);
    
    // Show toast
    showToast('Friend request notification clicked!', 'info');
}

function markNotificationAsRead(notificationId) {
    // Simulate marking notification as read
    const notificationItem = document.querySelector(`[onclick="handleNotificationClick(${notificationId})"]`);
    if (notificationItem) {
        notificationItem.classList.remove('unread');
    }
    
    // Update badge count
    const unreadNotifications = document.querySelectorAll('.notification-item.unread');
    const notificationBadge = document.getElementById('notificationBadge');
    if (notificationBadge) {
        notificationBadge.textContent = unreadNotifications.length;
        notificationBadge.style.display = unreadNotifications.length > 0 ? 'flex' : 'none';
    }
}

function markAllAsRead() {
    // Mark all notifications as read
    const unreadNotifications = document.querySelectorAll('.notification-item.unread');
    unreadNotifications.forEach(notification => {
        notification.classList.remove('unread');
    });
    
    // Update badge
    const notificationBadge = document.getElementById('notificationBadge');
    if (notificationBadge) {
        notificationBadge.textContent = '0';
        notificationBadge.style.display = 'none';
    }
    
    showToast('All notifications marked as read', 'success');
}

function setupRealTimeUpdates() {
    // Simulate real-time notification updates
    setInterval(() => {
        // Check for new notifications (replace with actual polling)
        checkForNewNotifications();
    }, 30000); // Check every 30 seconds
}

function checkForNewNotifications() {
    // Simulate checking for new notifications
    // In a real app, this would make an API call
    const notificationBadge = document.getElementById('notificationBadge');
    if (notificationBadge && Math.random() < 0.1) { // 10% chance of new notification
        const currentCount = parseInt(notificationBadge.textContent) || 0;
        notificationBadge.textContent = currentCount + 1;
        notificationBadge.style.display = 'flex';
        
        // Removed toast notification for new notifications
    }
}

// Toast notification system
function showToast(message, type = 'info') {
    const toast = document.getElementById('toast');
    const toastOverlay = document.querySelector('.toast-overlay');
    
    if (!toast || !toastOverlay) return;
    
    // Set message and type
    toast.textContent = message;
    toast.className = `toast ${type}`;
    
    // Show toast
    toastOverlay.classList.add('show');
    toast.classList.add('show');
    
    // Hide after 3 seconds
    setTimeout(() => {
        toast.classList.remove('show');
        toastOverlay.classList.remove('show');
    }, 3000);
}

// Export functions for global access
window.toggleNotificationDropdown = toggleNotificationDropdown;
window.markAllAsRead = markAllAsRead;
window.handleNotificationClick = handleNotificationClick;
window.showToast = showToast;

