/**
 * Moderation Panel JavaScript
 * Handles user management functionality for moderators
 */

/**
 * Debounce function to limit the rate at which a function can fire
 * @param {Function} func - The function to debounce
 * @param {number} wait - The time to wait in milliseconds
 * @returns {Function} - The debounced function
 */
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

document.addEventListener('DOMContentLoaded', function () {
    // Initialize tooltips
    initializeTooltips();

    // Initialize event listeners
    initializeEventListeners();

    // Initialize sortable headers (tri-state sorting)
    initializeSortingHeaders();

    // Initialize current state from DOM
    initializeModerationState();
});

// Current state
let moderationState = {
    sort: 'id',
    order: 'asc',
    grade: 'all',
    isLoading: false
};

function initializeModerationState() {
    const container = document.getElementById('moderationContainer');
    if (!container) return;
    moderationState.sort = container.getAttribute('data-initial-sort') || 'id';
    moderationState.order = container.getAttribute('data-initial-order') || 'asc';
    moderationState.grade = container.getAttribute('data-initial-grade') || 'all';
    updateHeaderIndicators();
}

function setLoading(loading) {
    const overlay = document.getElementById('loadingIndicator');
    if (!overlay) return;
    moderationState.isLoading = loading;
    overlay.style.display = loading ? 'flex' : 'none';
}

function loadUsersAjax() {
    setLoading(true);
    const params = new URLSearchParams();
    if (moderationState.sort) params.set('sort', moderationState.sort);
    if (moderationState.order) params.set('order', moderationState.order);
    if (moderationState.grade && moderationState.grade !== 'all') params.set('grade', moderationState.grade);

    fetch(`get_users.php?${params.toString()}`, {
        method: 'GET',
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
        .then(async (res) => {
            const text = await res.text();
            try {
                return JSON.parse(text);
            } catch (e) {
                console.error('Error loading users', e);
                console.error('Raw response:', text);
                throw e;
            }
        })
        .then(data => {
            if (data && data.success) {
                const tbody = document.getElementById('usersTbody');
                if (tbody) {
                    tbody.innerHTML = data.rows_html || '';
                }
                updateHeaderIndicators();
            } else {
                console.error('Failed to load users', data && data.message);
                showToast('Failed to load users', 'error');
            }
        })
        .catch(err => {
            console.error('Error loading users', err);
            showToast('Error loading users', 'error');
        })
        .finally(() => setLoading(false));
}

function updateHeaderIndicators() {
    const headers = document.querySelectorAll('.user-table thead th.sortable');
    headers.forEach(th => {
        const col = th.getAttribute('data-sort');
        const baseText = th.textContent.replace(/[↑↓]/g, '').trim();
        let label = baseText;
        if (moderationState.sort === col) {
            label = `${baseText} ${moderationState.order === 'asc' ? '↑' : '↓'}`;
        }
        th.textContent = label;
    });
}

/**
 * Initialize tooltips for action buttons
 */
function initializeTooltips() {
    // Tooltip functionality for action buttons
    const tooltipTriggers = document.querySelectorAll('[data-tooltip]');

    tooltipTriggers.forEach(trigger => {
        // Create tooltip element
        const tooltip = document.createElement('div');
        tooltip.className = 'tooltip';
        tooltip.textContent = trigger.getAttribute('data-tooltip');
        document.body.appendChild(tooltip);

        // Position tooltip on hover
        trigger.addEventListener('mouseenter', (e) => {
            const rect = trigger.getBoundingClientRect();
            tooltip.style.display = 'block';
            tooltip.style.top = `${rect.top - tooltip.offsetHeight - 5}px`;
            tooltip.style.left = `${rect.left + (rect.width / 2) - (tooltip.offsetWidth / 2)}px`;
        });

        trigger.addEventListener('mouseleave', () => {
            tooltip.style.display = 'none';
        });
    });
}

/**
 * Update the URL with the selected grade filter and reload the page
 * @param {string} grade - The selected grade filter value
 */
function updateGradeFilter(grade) {
    const url = new URL(window.location.href);
    if (grade === 'all') {
        url.searchParams.delete('grade');
    } else {
        url.searchParams.set('grade', grade);
    }
    // Reset to first page when changing filters
    url.searchParams.delete('page');
    window.location.href = url.toString();
}

/**
 * Initialize event listeners for the moderation panel
 */
function initializeEventListeners() {
    // Search functionality
    const searchInput = document.getElementById('userSearch');
    if (searchInput) {
        searchInput.addEventListener('input', debounce((e) => {
            const searchTerm = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('.user-table tbody tr');

            rows.forEach(row => {
                const username = row.cells[1].textContent.toLowerCase();
                const email = row.cells[2].textContent.toLowerCase();
                const grade = row.cells[3].textContent.toLowerCase();
                const section = row.cells[4]?.textContent?.toLowerCase() || '';

                if (username.includes(searchTerm) ||
                    email.includes(searchTerm) ||
                    grade.includes(searchTerm) ||
                    section.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }, 300));
    }

    // Header profile dropdown toggle (profile image click)
    const profileAnchor = document.querySelector('.user-profile .profile-icon');
    const profileDropdown = document.querySelector('.user-profile .profile-dropdown-content');
    if (profileAnchor && profileDropdown) {
        profileAnchor.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            profileDropdown.classList.toggle('show');
        });
        // Also allow clicking directly on img.profile-img to toggle
        const profileImg = profileAnchor.querySelector('.profile-img');
        if (profileImg) {
            profileImg.style.cursor = 'pointer';
        }
        // Close dropdown when clicking outside
        document.addEventListener('click', function (e) {
            if (!profileDropdown.contains(e.target) && !profileAnchor.contains(e.target)) {
                profileDropdown.classList.remove('show');
            }
        });
    }

    // Intercept grade filter to use AJAX instead of reload
    const gradeSelect = document.getElementById('gradeFilter');
    if (gradeSelect) {
        gradeSelect.addEventListener('change', (e) => {
            const value = e.target.value || 'all';
            moderationState.grade = value;
            // Reset to default sorting if you want default after filter change
            // keep current sort: do nothing
            loadUsersAjax();
        });
    }

    // Initialize any additional event listeners here
    document.addEventListener('click', function (e) {
        // Handle clicks on action buttons
        if (e.target.closest('.btn-view')) {
            const userId = e.target.closest('[data-user-id]').getAttribute('data-user-id');
            viewUser(userId);
        } else if (e.target.closest('.btn-warn')) {
            const userId = e.target.closest('[data-user-id]').getAttribute('data-user-id');
            warnUser(userId);
        } else if (e.target.closest('.btn-delete')) {
            const userId = e.target.closest('[data-user-id]').getAttribute('data-user-id');
            deleteUser(userId);
        }
    });
}

/**
 * Initialize tri-state sorting on table headers (asc → desc → default),
 * now using AJAX to update table without full reload.
 */
function initializeSortingHeaders() {
    const headers = document.querySelectorAll('.user-table thead th.sortable');
    if (!headers.length) return;

    headers.forEach(th => {
        th.addEventListener('click', () => {
            const column = th.getAttribute('data-sort');
            const currentSort = moderationState.sort;
            const currentOrder = moderationState.order;

            let isDefault = false;

            if (currentSort === column) {
                if (currentOrder === 'asc') {
                    moderationState.order = 'desc';
                } else if (currentOrder === 'desc') {
                    // Third click -> default: remove params
                    isDefault = true;
                } else {
                    // Was default for this column but explicitly selected -> asc
                    moderationState.order = 'asc';
                }
            } else {
                // Switching to a new column -> start with asc
                moderationState.sort = column;
                moderationState.order = 'asc';
            }

            if (isDefault) {
                // Reset to server default
                moderationState.sort = 'id';
                moderationState.order = 'asc';
            } else {
                moderationState.sort = moderationState.sort || column;
            }

            loadUsersAjax();
        });
    });
}

// Override grade filter function used in HTML to avoid reloads (kept for compatibility)
function updateGradeFilter(grade) {
    const value = grade || 'all';
    const select = document.getElementById('gradeFilter');
    if (select && select.value !== value) {
        select.value = value;
    }
    moderationState.grade = value;
    loadUsersAjax();
}

/**
 * View user profile
 * @param {number} userId - ID of the user to view
 */
function viewUser(userId) {
    // Redirect to user's profile page
    window.location.href = `../friends/user-profile.php?user_id=${userId}`;
}

/**
 * Warn a user
 * @param {number} userId - ID of the user to warn
 */
function warnUser(userId) {
    showToast('Warning feature not yet working', 'info');
}

/**
 * Delete a user
 * @param {number} userId - ID of the user to delete
 */
function deleteUser(userId) {
    // Show custom confirmation modal
    showDeleteConfirmation(userId);
}

/**
 * Show custom delete confirmation modal
 * @param {number} userId - ID of the user to delete
 */
function showDeleteConfirmation(userId) {
    // Create modal overlay
    const overlay = document.createElement('div');
    overlay.className = 'delete-modal-overlay';
    overlay.innerHTML = `
        <div class="delete-modal">
            <div class="delete-modal-header">
                <i class="fas fa-exclamation-triangle"></i>
                <h3>Delete User Account</h3>
            </div>
            <div class="delete-modal-body">
                <p><strong>WARNING:</strong> This action cannot be undone!</p>
                <p>Are you sure you want to permanently delete user #${userId}?</p>
                <p class="delete-warning">All user data, progress, and settings will be lost.</p>
            </div>
            <div class="delete-modal-footer">
                <button class="btn-cancel" onclick="closeDeleteModal()">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button class="btn-confirm-delete" onclick="confirmDeleteUser(${userId})">
                    <i class="fas fa-trash"></i> Delete User
                </button>
            </div>
        </div>
    `;

    document.body.appendChild(overlay);

    // Animate in
    setTimeout(() => {
        overlay.classList.add('show');
    }, 10);
}

/**
 * Close delete confirmation modal
 */
function closeDeleteModal() {
    const overlay = document.querySelector('.delete-modal-overlay');
    if (overlay) {
        overlay.classList.remove('show');
        setTimeout(() => {
            overlay.remove();
        }, 300);
    }
}

/**
 * Confirm and execute user deletion
 * @param {number} userId - ID of the user to delete
 */
function confirmDeleteUser(userId) {
    // Close modal
    closeDeleteModal();

    // Show loading state
    showToast('Deleting user...', 'info');

    // Send delete request to API
    fetch('../../api/delete-user.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            user_id: userId
        })
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('User deleted successfully', 'success');
                // Reload the page to refresh the user list
                setTimeout(() => {
                    location.reload();
                }, 1500);
            } else {
                throw new Error(data.message || 'Failed to delete user');
            }
        })
        .catch(error => {
            console.error('Error deleting user:', error);
            showToast(error.message || 'Failed to delete user', 'error');
        });
}

/**
 * Show a toast notification
 * @param {string} message - The message to display
 * @param {string} type - The type of notification (success, error, info, warning)
 */
function showToast(message, type = 'info') {
    // In a real implementation, you might want to use a more robust notification system
    const toast = document.createElement('div');
    toast.className = `toast-notification ${type}`;
    toast.textContent = message;

    // Add to the page
    document.body.appendChild(toast);

    // Show the toast
    setTimeout(() => {
        toast.classList.add('show');
    }, 10);

    // Remove the toast after a delay
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => {
            document.body.removeChild(toast);
        }, 300);
    }, 5000);
}

/**
 * Show a modal dialog
 * @param {string} title - The title of the modal
 * @param {string} content - The HTML content of the modal
 * @param {Object} options - Additional options (e.g., buttons, size, etc.)
 */
function showModal(title, content, options = {}) {
    // In a real implementation, you might want to use a more robust modal system
    const modal = document.createElement('div');
    modal.className = 'modal';

    const modalContent = document.createElement('div');
    modalContent.className = 'modal-content';

    // Add title
    const titleEl = document.createElement('h3');
    titleEl.textContent = title;
    modalContent.appendChild(titleEl);

    // Add content
    const contentEl = document.createElement('div');
    contentEl.className = 'modal-body';
    contentEl.innerHTML = content;
    modalContent.appendChild(contentEl);

    // Add buttons
    const actionsEl = document.createElement('div');
    actionsEl.className = 'modal-actions';

    if (options.buttons && options.buttons.length) {
        options.buttons.forEach(button => {
            const btn = document.createElement('button');
            btn.className = `btn ${button.className || ''}`;
            btn.textContent = button.text;
            btn.onclick = button.onclick || (() => closeModal(modal));
            actionsEl.appendChild(btn);
        });
    } else {
        // Default close button
        const closeBtn = document.createElement('button');
        closeBtn.className = 'btn btn-primary';
        closeBtn.textContent = 'Close';
        closeBtn.onclick = () => closeModal(modal);
        actionsEl.appendChild(closeBtn);
    }

    modalContent.appendChild(actionsEl);
    modal.appendChild(modalContent);

    // Add to the page
    document.body.appendChild(modal);

    // Show the modal
    setTimeout(() => {
        modal.classList.add('show');
    }, 10);

    // Close on click outside
    modal.onclick = (e) => {
        if (e.target === modal) {
            closeModal(modal);
        }
    };

    // Return the modal element in case you need to close it programmatically
    return modal;
}

/**
 * Close a modal
 * @param {HTMLElement} modal - The modal element to close
 */
function closeModal(modal) {
    if (modal) {
        modal.classList.remove('show');
        setTimeout(() => {
            if (modal.parentNode) {
                document.body.removeChild(modal);
            }
        }, 300);
    }
}