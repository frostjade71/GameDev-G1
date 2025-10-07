// Notification Page JavaScript Functionality

document.addEventListener('DOMContentLoaded', function() {
    initializeNotificationPage();
});

function initializeNotificationPage() {
    // Mobile menu functionality is handled by script.js globally
    // No need to initialize it here to avoid conflicts
    
    // Initialize notification actions
    initializeNotificationActions();
    
    // Set up real-time updates (mock for now)
    setupRealTimeUpdates();
    
    // Initialize notification badge
    updateNotificationBadge();
}

function initializeNotificationActions() {
    // Add click handlers for accept/decline buttons
    const acceptBtns = document.querySelectorAll('.accept-btn');
    const declineBtns = document.querySelectorAll('.decline-btn');
    
    acceptBtns.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const requestCard = this.closest('.friend-request-card');
            const requestId = requestCard.dataset.requestId;
            const requesterId = this.getAttribute('onclick').match(/\d+/)[0];
            const username = this.getAttribute('onclick').match(/'([^']+)'/)[1];
            
            acceptFriendRequest(requestId, requesterId, username, this);
        });
    });
    
    declineBtns.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const requestCard = this.closest('.friend-request-card');
            const requestId = requestCard.dataset.requestId;
            const username = this.getAttribute('onclick').match(/'([^']+)'/)[1];
            
            declineFriendRequest(requestId, username, this);
        });
    });
}

function acceptFriendRequest(requestId, requesterId, username, buttonElement) {
    const requestCard = buttonElement.closest('.friend-request-card');
    const declineBtn = requestCard.querySelector('.decline-btn');
    
    // Show loading state
    buttonElement.disabled = true;
    declineBtn.disabled = true;
    buttonElement.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Accepting...';
    
    // Make API call to accept friend request
    fetch('accept_friend_request.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            request_id: requestId,
            requester_id: requesterId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Show success state
            buttonElement.innerHTML = '<i class="fas fa-check"></i> Accepted';
            buttonElement.style.background = '#00ff87';
            declineBtn.style.display = 'none';
            
            // Show toast message
            showToast(`You are now friends with ${username}!`, 'success');
            
            // Refresh after a short delay
            setTimeout(() => {
                document.location.reload();
            }, 1500);
        } else {
            // Show error state
            buttonElement.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Error';
            buttonElement.style.background = '#ff6b6b';
            buttonElement.disabled = false;
            declineBtn.disabled = false;
            
            // Show error toast
            showToast(data.message || 'Failed to accept friend request. Please try again.', 'error');
            
            // Reset button after delay
            setTimeout(() => {
                buttonElement.disabled = false;
                buttonElement.innerHTML = '<i class="fas fa-check"></i> Accept';
                buttonElement.style.background = '';
                requestCard.classList.remove('error');
            }, 3000);
        }
    })
    .catch(error => {
        console.error('Error accepting friend request:', error);
        
        // Show error state
        requestCard.classList.remove('loading');
        requestCard.classList.add('error');
        buttonElement.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Error';
        buttonElement.style.background = '#ff6b6b';
        declineBtn.disabled = false;
        
        // Show error toast
        showToast('Network error. Please check your connection and try again.', 'error');
        
        // Reset button after delay
        setTimeout(() => {
            buttonElement.disabled = false;
            buttonElement.innerHTML = '<i class="fas fa-check"></i> Accept';
            buttonElement.style.background = '';
            requestCard.classList.remove('error');
        }, 3000);
    });
}

function declineFriendRequest(requestId, username, buttonElement) {
    // Get the request card and other elements
    const requestCard = buttonElement.closest('.friend-request-card');
    const acceptBtn = requestCard.querySelector('.accept-btn');
    
    // Disable both buttons and show loading state
    buttonElement.disabled = true;
    acceptBtn.disabled = true;
    buttonElement.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Declining...';
    
    // Make API call to decline friend request
    fetch('decline_friend_request.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
            request_id: requestId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Show decline success state
            buttonElement.innerHTML = '<i class="fas fa-times"></i> Declined';
            buttonElement.style.background = '#ff6b6b';
            acceptBtn.style.display = 'none';
            
            // Show appropriate toast message
            const toastMessage = data.data?.already_processed 
                ? 'Friend request already processed'
                : data.message || 'Friend request declined successfully';
            showToast(toastMessage, 'info');
            
            // Force immediate page refresh
            window.location = window.location.href;
        } else {
            // Show error state
            buttonElement.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Error';
            buttonElement.style.background = '#ff6b6b';
            buttonElement.disabled = false;
            acceptBtn.disabled = false;
            
            // Show error toast
            showToast(data.message || 'Failed to decline friend request', 'error');
            
            // Show error toast
            showToast(data.message || 'Failed to decline friend request. Please try again.', 'error');
            
            // Reset button after delay
            setTimeout(() => {
                buttonElement.disabled = false;
                buttonElement.innerHTML = '<i class="fas fa-times"></i> Decline';
                buttonElement.style.background = '';
                requestCard.classList.remove('error');
            }, 3000);
        }
    })
    .catch(error => {
        console.error('Error declining friend request:', error);
        
        // Show error state
        requestCard.classList.remove('loading');
        requestCard.classList.add('error');
        buttonElement.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Error';
        acceptBtn.disabled = false;
        
        // Show error toast
        showToast('Network error. Please check your connection and try again.', 'error');
        
        // Reset button after delay
        setTimeout(() => {
            buttonElement.disabled = false;
            buttonElement.innerHTML = '<i class="fas fa-times"></i> Decline';
            buttonElement.style.background = '';
            requestCard.classList.remove('error');
        }, 3000);
    });
}

function markAllAsRead() {
    const markAllBtn = document.querySelector('.mark-all-read-btn');
    const requestCards = document.querySelectorAll('.friend-request-card');
    
    if (requestCards.length === 0) {
        showToast('No notifications to mark as read.', 'info');
        return;
    }
    
    // Show loading state
    markAllBtn.disabled = true;
    markAllBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Removing...';
    
    // Make API call to mark all as read
    fetch('mark_all_notifications_read.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({})
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Show success toast with more specific message
            const deletedCount = data.data?.friend_requests_deleted || 0;
            showToast(`All friend requests marked as read and removed (${deletedCount} requests)!`, 'success');
            
            // Remove all request cards with animation
            requestCards.forEach((card, index) => {
                setTimeout(() => {
                    card.style.opacity = '0';
                    card.style.transform = 'translateY(-20px)';
                    setTimeout(() => {
                        card.remove();
                        updateNotificationBadge();
                        updateRequestCount();
                    }, 300);
                }, index * 100);
            });
            
            // Hide the mark all button
            setTimeout(() => {
                markAllBtn.style.display = 'none';
            }, requestCards.length * 100 + 500);
        } else {
            // Show error toast
            showToast(data.message || 'Failed to mark notifications as read.', 'error');
            
            // Reset button
            markAllBtn.disabled = false;
            markAllBtn.innerHTML = '<i class="fas fa-check-double"></i> Mark All as Read';
        }
    })
    .catch(error => {
        console.error('Error marking notifications as read:', error);
        
        // Show error toast
        showToast('Network error. Please check your connection and try again.', 'error');
        
        // Reset button
        markAllBtn.disabled = false;
        markAllBtn.innerHTML = '<i class="fas fa-check-double"></i> Mark All as Read';
    });
}

function updateNotificationBadge() {
    const badge = document.querySelector('.notification-badge');
    const requestCards = document.querySelectorAll('.friend-request-card');
    const count = requestCards.length;
    
    if (badge) {
        badge.textContent = count;
        
        // Add pulse animation if there are new notifications
        if (count > 0) {
            badge.classList.add('pulse');
        } else {
            badge.classList.remove('pulse');
        }
    }
}

function updateRequestCount() {
    const countElement = document.querySelector('.section-header h2');
    const requestCards = document.querySelectorAll('.friend-request-card');
    const count = requestCards.length;
    
    if (countElement) {
        countElement.innerHTML = `<i class="fas fa-user-plus"></i> Friend Requests (${count})`;
    }
    // We remove the empty state creation since we'll be reloading the page instead
}

function setupRealTimeUpdates() {
    // Simulate real-time notification updates
    setInterval(() => {
        checkForNewNotifications();
    }, 30000); // Check every 30 seconds
    
    // Simulate notification count updates
    setInterval(() => {
        updateNotificationBadge();
    }, 10000); // Update every 10 seconds
}

function checkForNewNotifications() {
    // This would make an API call to check for new notifications
    // For now, we'll just update the badge count
    updateNotificationBadge();
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

// Keyboard navigation support
document.addEventListener('keydown', function(e) {
    // ESC key to close any open modals
    if (e.key === 'Escape') {
        const modal = document.getElementById('logoutModal');
        if (modal && modal.classList.contains('show')) {
            hideLogoutModal();
        }
    }
    
    // Enter key to accept friend request (when focused on accept button)
    if (e.key === 'Enter') {
        const focusedElement = document.activeElement;
        if (focusedElement && focusedElement.classList.contains('accept-btn')) {
            focusedElement.click();
        }
    }
    
    // Space key to decline friend request (when focused on decline button)
    if (e.key === ' ') {
        const focusedElement = document.activeElement;
        if (focusedElement && focusedElement.classList.contains('decline-btn')) {
            e.preventDefault();
            focusedElement.click();
        }
    }
});

// Accessibility improvements
function improveAccessibility() {
    // Add ARIA labels to buttons
    const acceptBtns = document.querySelectorAll('.accept-btn');
    const declineBtns = document.querySelectorAll('.decline-btn');
    
    acceptBtns.forEach(btn => {
        btn.setAttribute('aria-label', 'Accept friend request');
    });
    
    declineBtns.forEach(btn => {
        btn.setAttribute('aria-label', 'Decline friend request');
    });
    
    // Add role attributes
    const requestCards = document.querySelectorAll('.friend-request-card');
    requestCards.forEach(card => {
        card.setAttribute('role', 'article');
        card.setAttribute('aria-label', 'Friend request notification');
    });
}

// Initialize accessibility improvements
document.addEventListener('DOMContentLoaded', improveAccessibility);

// Export functions for global access
window.acceptFriendRequest = acceptFriendRequest;
window.declineFriendRequest = declineFriendRequest;
window.markAllAsRead = markAllAsRead;
window.showToast = showToast;
