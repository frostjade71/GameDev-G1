// Friends Page JavaScript Functionality

document.addEventListener('DOMContentLoaded', function () {
    initializeFriendsPage();
});

function initializeFriendsPage() {
    // Mobile menu functionality is handled by script.js globally
    // No need to initialize it here to avoid conflicts

    // Initialize friend actions
    initializeFriendActions();

    // Initialize suggested friends
    initializeSuggestedFriends();

    // Initialize search functionality
    initializeSearch();

    // Carousel removed - using simple grid layout

    // Set up real-time updates (mock for now)
    setupRealTimeUpdates();
}

function initializeFriendActions() {
    // Message button functionality
    const messageBtns = document.querySelectorAll('.message-btn');
    messageBtns.forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            // Create a link element for the font
            const fontLink = document.createElement('link');
            fontLink.href = 'https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap';
            fontLink.rel = 'stylesheet';
            document.head.appendChild(fontLink);

            // Show toast with the styled message
            showToast('<div style="text-align: center;"><img src="../../assets/pixels/hammer.png" style="width: 24px; height: 24px; margin-bottom: 8px; display: block; margin-left: auto; margin-right: auto;"><span style="font-family: \'Press Start 2P\', monospace; font-size: 0.7rem;">Messaging feature coming soon!</span></div>', 'info');
        });
    });

    // Play button functionality
    const playBtns = document.querySelectorAll('.play-btn');
    playBtns.forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            const friendCard = this.closest('.friend-card');
            const friendName = friendCard.querySelector('.friend-info h3').textContent;

            showToast(`Challenge feature coming soon! You would challenge ${friendName} to a game here.`, 'info');
        });
    });
}

function initializeSuggestedFriends() {
    // Add friend button functionality
    const addFriendBtns = document.querySelectorAll('.add-friend-btn');
    addFriendBtns.forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            const suggestedCard = this.closest('.suggested-card');
            const friendName = suggestedCard.querySelector('.suggested-info h3').textContent;
            const friendId = this.getAttribute('onclick').match(/\d+/)[0];

            addFriend(friendId, friendName, this);
        });
    });
}

// Helper function to detect mobile devices
function isMobile() {
    return window.innerWidth <= 768;
}

function addFriend(friendId, friendName, buttonElement) {
    console.log('Adding friend:', friendId, friendName);

    // Disable button and show loading state
    buttonElement.disabled = true;
    if (isMobile()) {
        buttonElement.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    } else {
        buttonElement.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';
    }

    // Make API call to send friend request
    fetch('../send_friend_request.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            receiver_id: friendId
        })
    })
        .then(response => {
            console.log('Response status:', response.status);
            return response.json();
        })
        .then(data => {
            console.log('Response data:', data);
            if (data.success) {
                // Show success message
                showToast(`Friend request sent to ${friendName}!`, 'success');

                // Update button to "Cancel Request"
                if (isMobile()) {
                    buttonElement.innerHTML = '<i class="fas fa-times"></i>';
                } else {
                    buttonElement.innerHTML = '<i class="fas fa-times"></i> Cancel Request';
                }
                buttonElement.className = 'cancel-request-btn';
                buttonElement.onclick = function () {
                    cancelFriendRequest(friendId, friendName, this);
                };
                buttonElement.disabled = false;

                // Refresh the page after a short delay to ensure UI is in sync
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            } else {
                // Check if the error is because request already exists
                if (data.message && data.message.includes('already exists')) {
                    // Update button to "Cancel Request" since request already exists (no notification)
                    if (isMobile()) {
                        buttonElement.innerHTML = '<i class="fas fa-times"></i>';
                    } else {
                        buttonElement.innerHTML = '<i class="fas fa-times"></i> Cancel Request';
                    }
                    buttonElement.className = 'cancel-request-btn';
                    buttonElement.onclick = function () {
                        cancelFriendRequest(friendId, friendName, this);
                    };
                    buttonElement.disabled = false;

                    // Refresh the page after a short delay to ensure UI is in sync
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    // Show error message for other errors
                    showToast(data.message || 'Failed to send friend request. Please try again.', 'error');

                    // Reset button state
                    buttonElement.disabled = false;
                    if (isMobile()) {
                        buttonElement.innerHTML = '<i class="fas fa-user-plus"></i>';
                    } else {
                        buttonElement.innerHTML = '<i class="fas fa-user-plus"></i> Add Friend';
                    }
                }
            }
        })
        .catch(error => {
            console.error('Error sending friend request:', error);
            console.error('Error details:', error.message);

            // Show error message
            showToast('Network error. Please check your connection and try again.', 'error');

            // Reset button state
            buttonElement.disabled = false;
            if (isMobile()) {
                buttonElement.innerHTML = '<i class="fas fa-user-plus"></i>';
            } else {
                buttonElement.innerHTML = '<i class="fas fa-user-plus"></i> Add Friend';
            }
        });
}

function cancelFriendRequest(friendId, friendName, buttonElement) {
    // Disable button and show loading state
    buttonElement.disabled = true;
    if (isMobile()) {
        buttonElement.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    } else {
        buttonElement.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Cancelling...';
    }

    // Make API call to cancel friend request
    fetch('../cancel_friend_request.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            receiver_id: friendId
        })
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show success message and reload the page after a short delay
                showToast(`Friend request to ${friendName} has been cancelled.`, 'success');

                // Reload the page after the toast is shown (toast duration is 3 seconds)
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            } else {
                // Show error message
                showToast(data.message || 'Failed to cancel friend request. Please try again.', 'error');

                // Reset button state
                buttonElement.disabled = false;
                if (isMobile()) {
                    buttonElement.innerHTML = '<i class="fas fa-times"></i>';
                } else {
                    buttonElement.innerHTML = '<i class="fas fa-times"></i> Cancel Request';
                }
            }
        })
        .catch(error => {
            console.error('Error cancelling friend request:', error);

            // Show error message
            showToast('Network error. Please check your connection and try again.', 'error');

            // Reset button state
            buttonElement.disabled = false;
            if (isMobile()) {
                buttonElement.innerHTML = '<i class="fas fa-times"></i>';
            } else {
                buttonElement.innerHTML = '<i class="fas fa-times"></i> Cancel Request';
            }
        });
}

function updateSuggestedCount() {
    const suggestedGrid = document.querySelector('.suggested-grid');
    const suggestedCards = suggestedGrid.querySelectorAll('.suggested-card');

    if (suggestedCards.length === 0) {
        // Show empty state
        const emptyState = document.createElement('div');
        emptyState.className = 'empty-state';
        emptyState.innerHTML = `
            <i class="fas fa-user-plus"></i>
            <h3>No More Suggestions</h3>
            <p>Check back later for more friend suggestions!</p>
        `;
        suggestedGrid.appendChild(emptyState);
    }
}

function setupRealTimeUpdates() {
    // Simulate real-time online status updates
    setInterval(() => {
        updateOnlineStatus();
    }, 30000); // Update every 30 seconds

    // Simulate friend activity updates
    setInterval(() => {
        simulateFriendActivity();
    }, 60000); // Update every minute
}

function updateOnlineStatus() {
    const onlineStatuses = document.querySelectorAll('.online-status');
    onlineStatuses.forEach(status => {
        // Randomly change online status (for demo purposes)
        if (Math.random() < 0.1) { // 10% chance to change status
            const isOnline = status.classList.contains('online');
            const friendCard = status.closest('.friend-card');
            const statusText = friendCard.querySelector('.friend-status');

            if (isOnline) {
                status.classList.remove('online');
                status.classList.add('offline');
                statusText.textContent = 'Just went offline';
            } else {
                status.classList.remove('offline');
                status.classList.add('online');
                statusText.textContent = 'Online now';
            }

            // Update online count
            updateOnlineCount();
        }
    });
}

function updateOnlineCount() {
    const onlineStatuses = document.querySelectorAll('.online-status.online');
    const onlineCountElement = document.querySelector('.online-count span:last-child');

    if (onlineCountElement) {
        onlineCountElement.textContent = `${onlineStatuses.length} online`;
    }
}

function simulateFriendActivity() {
    const friendCards = document.querySelectorAll('.friend-card');
    const randomCard = friendCards[Math.floor(Math.random() * friendCards.length)];

    if (randomCard) {
        const statusText = randomCard.querySelector('.friend-status');
        const activities = [
            'Playing Word Weavers',
            'Just finished a game',
            'Online now',
            'Browsing games',
            'In a game'
        ];

        const randomActivity = activities[Math.floor(Math.random() * activities.length)];
        statusText.textContent = randomActivity;

        // Reset after 10 seconds
        setTimeout(() => {
            if (statusText.textContent === randomActivity) {
                const isOnline = randomCard.querySelector('.online-status').classList.contains('online');
                statusText.textContent = isOnline ? 'Online now' : '2 hours ago';
            }
        }, 10000);
    }
}

// Toast notification system
function showToast(message, type = 'info') {
    const toast = document.getElementById('toast');
    const toastOverlay = document.querySelector('.toast-overlay');

    if (!toast || !toastOverlay) return;

    // Only show one toast at a time
    if (toast.classList.contains('show')) {
        toast.classList.remove('show');
        toastOverlay.classList.remove('show');
        setTimeout(() => {
            showToast(message, type);
        }, 300);
        return;
    }

    // Set message and type - using innerHTML to render HTML content
    toast.innerHTML = message;
    toast.className = 'toast';
    toast.classList.add(type);

    // Show toast
    toastOverlay.classList.add('show');
    toast.classList.add('show');

    // Hide after delay
    setTimeout(() => {
        hideToast();
    }, 3000);
}

function hideToast() {
    const toast = document.getElementById('toast');
    const toastOverlay = document.querySelector('.toast-overlay');

    toast.classList.remove('show');
    toastOverlay.classList.remove('show');
}

// Search functionality (for future implementation)
function searchFriends(query) {
    const friendCards = document.querySelectorAll('.friend-card');
    const suggestedCards = document.querySelectorAll('.suggested-card');

    const allCards = [...friendCards, ...suggestedCards];

    allCards.forEach(card => {
        const name = card.querySelector('h3').textContent.toLowerCase();
        const matches = name.includes(query.toLowerCase());

        card.style.display = matches ? 'block' : 'none';
    });
}

// Filter functionality (for future implementation)
function filterFriends(filter) {
    const friendCards = document.querySelectorAll('.friend-card');

    friendCards.forEach(card => {
        const isOnline = card.querySelector('.online-status').classList.contains('online');

        switch (filter) {
            case 'online':
                card.style.display = isOnline ? 'block' : 'none';
                break;
            case 'offline':
                card.style.display = !isOnline ? 'block' : 'none';
                break;
            case 'all':
            default:
                card.style.display = 'block';
                break;
        }
    });
}

// Refresh users functionality with AJAX and flip-card loader
function refreshUsers() {
    const refreshBtn = document.querySelector('.refresh-button');
    const refreshIcon = refreshBtn ? refreshBtn.querySelector('i') : null;
    const suggestedGrid = document.getElementById('suggestedGrid');
    const loader = document.getElementById('suggestedLoader');

    if (!suggestedGrid || !loader) {
        // Fallback for missing elements
        window.location.reload();
        return;
    }

    // Show loading state
    if (refreshBtn) refreshBtn.disabled = true;
    if (refreshIcon) refreshIcon.classList.add('fa-spin');
    
    suggestedGrid.classList.add('refreshing');
    loader.classList.add('active');

    // Make AJAX call to shuffle users
    const formData = new FormData();
    formData.append('action', 'shuffle_users');

    fetch('friends_ajax.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        // Add a small delay so the animation is visible
        setTimeout(() => {
            if (data.success && data.users) {
                // Clear and rebuild grid
                suggestedGrid.innerHTML = data.users.map(user => `
                    <div class="suggested-card" onclick="viewProfile(${user.id})" style="cursor: pointer;">
                        <div class="suggested-avatar">
                            <img src="${user.profile_image}" alt="${user.username}">
                        </div>
                        <div class="suggested-info">
                            <h3>
                                ${user.username}
                                ${user.highest_badge ? `
                                    <img src="${user.highest_badge.src}" 
                                         alt="${user.highest_badge.alt}" 
                                         class="user-badge" 
                                         title="${user.highest_badge.title}">
                                ` : ''}
                            </h3>
                            <p class="user-details">
                                <span class="grade-level">${user.grade_level}</span>
                                <span class="joined-date">Joined ${user.joined_date}</span>
                            </p>
                        </div>
                        <div class="suggested-actions">
                            ${user.has_pending_request ? `
                                <button class="cancel-request-btn" onclick="event.stopPropagation(); cancelFriendRequest(${user.id}, '${user.username.replace(/'/g, "\\'")}', this)">
                                    <i class="fas fa-times"></i>
                                    <span>Cancel Request</span>
                                </button>
                            ` : `
                                <button class="add-friend-btn" onclick="event.stopPropagation(); addFriend(${user.id}, '${user.username.replace(/'/g, "\\'")}', this)">
                                    <i class="fas fa-user-plus"></i>
                                    <span>Add Friend</span>
                                </button>
                            `}
                        </div>
                    </div>
                `).join('');
                
                // Re-initialize suggested friends listeners if needed
                initializeSuggestedFriends();
            } else {
                showToast(data.message || 'Error refreshing users', 'error');
            }

            // Hide loading state
            if (refreshBtn) refreshBtn.disabled = false;
            if (refreshIcon) refreshIcon.classList.remove('fa-spin');
            
            suggestedGrid.classList.remove('refreshing');
            loader.classList.remove('active');
        }, 1200); // 1.2 seconds delay for the loader animation
    })
    .catch(error => {
        console.error('Refresh error:', error);
        showToast('Failed to refresh users', 'error');
        
        // Reset UI on error
        if (refreshBtn) refreshBtn.disabled = false;
        if (refreshIcon) refreshIcon.classList.remove('fa-spin');
        suggestedGrid.classList.remove('refreshing');
        loader.classList.remove('active');
    });
}

// View profile functionality
function viewProfile(userId) {
    // Navigate to user profile page
    window.location.href = `user-profile.php?user_id=${userId}`;
}

// Dropdown search functionality
function initializeSearch() {
    const searchInput = document.getElementById('userSearch');
    if (!searchInput) return;

    // Hide dropdown when clicking outside
    document.addEventListener('click', function (e) {
        if (!e.target.closest('.search-container')) {
            hideDropdown();
        }
    });
}

function showDropdown(query) {
    console.log('showDropdown called with:', query);
    const dropdown = document.getElementById('searchDropdown');
    if (!dropdown) {
        console.log('Dropdown element not found!');
        return;
    }

    const searchTerm = query.toLowerCase().trim();
    console.log('Search term:', searchTerm);

    if (searchTerm.length === 0) {
        hideDropdown();
        return;
    }

    // Check if searchUsers data is available
    if (!window.searchUsers) {
        console.log('searchUsers data not available');
        dropdown.innerHTML = `
            <div style="padding: 1rem; text-align: center; color: rgba(255, 255, 255, 0.7);">
                <p style="margin: 0; font-size: 0.9rem;">Loading users...</p>
            </div>
        `;
        dropdown.style.display = 'block';
        return;
    }

    // Filter users based on search term
    const filteredUsers = window.searchUsers.filter(user =>
        user.username.toLowerCase().includes(searchTerm) ||
        user.grade_level.toLowerCase().includes(searchTerm)
    );

    console.log('Filtered users:', filteredUsers.length);

    if (filteredUsers.length === 0) {
        dropdown.innerHTML = `
            <div style="padding: 1rem; text-align: center; color: rgba(255, 255, 255, 0.7);">
                <i class="fas fa-search" style="font-size: 1.5rem; margin-bottom: 0.5rem; color: rgba(96, 239, 255, 0.5);"></i>
                <p style="margin: 0; font-size: 0.9rem;">No users found</p>
            </div>
        `;
    } else {
        dropdown.innerHTML = filteredUsers.map(user => `
            <div class="dropdown-item" onclick="selectUser(${user.id}, '${user.username}')" style="padding: 0.8rem 1rem; cursor: pointer; border-bottom: 1px solid rgba(96, 239, 255, 0.1); transition: background 0.2s ease; display: flex; align-items: center; gap: 0.8rem;" onmouseenter="this.style.background='rgba(96, 239, 255, 0.1)'" onmouseleave="this.style.background='transparent'">
                <img src="../../assets/menu/defaultuser.png" alt="${user.username}" style="width: 35px; height: 35px; border-radius: 50%; border: 2px solid rgba(96, 239, 255, 0.3);">
                <div style="flex: 1;">
                    <div style="color: white; font-weight: 600; font-size: 0.9rem; margin-bottom: 0.2rem;">${user.username}</div>
                    <div style="color: rgba(96, 239, 255, 0.8); font-size: 0.8rem;">${user.grade_level}</div>
                </div>
                <i class="fas fa-user-plus" style="color: rgba(96, 239, 255, 0.6); font-size: 0.8rem;"></i>
            </div>
        `).join('');
    }

    dropdown.style.display = 'block';
    console.log('Dropdown should be visible now');
}

function hideDropdown() {
    const dropdown = document.getElementById('searchDropdown');
    if (dropdown) {
        dropdown.style.display = 'none';
    }
}

function selectUser(userId, username) {
    // Hide dropdown
    hideDropdown();

    // Clear search input
    const searchInput = document.getElementById('userSearch');
    if (searchInput) {
        searchInput.value = '';
    }

    // Navigate to user profile
    window.location.href = `user-profile.php?user_id=${userId}`;
}

// Carousel functionality removed - using simple grid layout

// Export functions for global access
window.addFriend = addFriend;
window.cancelFriendRequest = cancelFriendRequest;
window.searchFriends = searchFriends;
window.filterFriends = filterFriends;
window.showToast = showToast;
window.refreshUsers = refreshUsers;
window.viewProfile = viewProfile;
window.showDropdown = showDropdown;
window.hideDropdown = hideDropdown;
window.selectUser = selectUser;
// Carousel functions removed
