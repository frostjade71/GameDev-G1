/**
 * User Management JavaScript
 * Handles user management functionality for admin users
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

// Current state
let userManagementState = {
    sort: 'id',
    order: 'asc',
    grade: 'all',
    isLoading: false
};

document.addEventListener('DOMContentLoaded', function () {
    // Initialize event listeners
    initializeEventListeners();

    // Initialize sortable headers (tri-state sorting)
    initializeSortingHeaders();

    // Initialize current state from DOM
    initializeUserManagementState();
});

function initializeUserManagementState() {
    // Get initial state from URL or defaults
    const urlParams = new URLSearchParams(window.location.search);
    userManagementState.sort = urlParams.get('sort') || 'id';
    userManagementState.order = urlParams.get('order') || 'asc';
    userManagementState.grade = urlParams.get('grade') || 'all';
    updateHeaderIndicators();
}

function setLoading(loading) {
    const overlay = document.getElementById('loadingIndicator');
    if (!overlay) return;
    userManagementState.isLoading = loading;
    overlay.style.display = loading ? 'flex' : 'none';
}

function loadUsersAjax() {
    setLoading(true);
    const params = new URLSearchParams();
    if (userManagementState.sort) params.set('sort', userManagementState.sort);
    if (userManagementState.order) params.set('order', userManagementState.order);
    if (userManagementState.grade && userManagementState.grade !== 'all') params.set('grade', userManagementState.grade);

    fetch(`api/get_users.php?${params.toString()}`, {
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
        if (userManagementState.sort === col) {
            label = `${baseText} ${userManagementState.order === 'asc' ? '↑' : '↓'}`;
        }
        th.textContent = label;
    });
}

/**
 * Initialize event listeners for the user management panel
 */
function initializeEventListeners() {
    // Search functionality
    const searchInput = document.getElementById('userSearch');
    if (searchInput) {
        searchInput.addEventListener('input', debounce((e) => {
            const searchTerm = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('.user-table tbody tr');

            rows.forEach(row => {
                const username = row.cells[1]?.textContent?.toLowerCase() || '';
                const email = row.cells[2]?.textContent?.toLowerCase() || '';
                const grade = row.cells[3]?.textContent?.toLowerCase() || '';
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

    // Intercept grade filter to use AJAX instead of reload
    const gradeSelect = document.getElementById('gradeFilter');
    if (gradeSelect) {
        gradeSelect.addEventListener('change', (e) => {
            const value = e.target.value || 'all';
            userManagementState.grade = value;
            loadUsersAjax();
        });
    }
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
            const currentSort = userManagementState.sort;
            const currentOrder = userManagementState.order;

            let isDefault = false;

            if (currentSort === column) {
                if (currentOrder === 'asc') {
                    userManagementState.order = 'desc';
                } else if (currentOrder === 'desc') {
                    // Third click -> default: remove params
                    isDefault = true;
                } else {
                    // Was default for this column but explicitly selected -> asc
                    userManagementState.order = 'asc';
                }
            } else {
                // Switching to a new column -> start with asc
                userManagementState.sort = column;
                userManagementState.order = 'asc';
            }

            if (isDefault) {
                // Reset to server default
                userManagementState.sort = 'id';
                userManagementState.order = 'asc';
            } else {
                userManagementState.sort = userManagementState.sort || column;
            }

            loadUsersAjax();
        });
    });
}

/**
 * Update the URL with the selected grade filter and reload the page
 * @param {string} grade - The selected grade filter value
 */
function updateGradeFilter(grade) {
    const value = grade || 'all';
    const select = document.getElementById('gradeFilter');
    if (select && select.value !== value) {
        select.value = value;
    }
    userManagementState.grade = value;
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
    showToast('Warning feature not yet implemented', 'info');
}

/**
 * Delete a user
 * @param {number} userId - ID of the user to delete
 * @param {string} username - Username of the user to delete
 */
function deleteUser(userId, username) {
    // Show custom confirmation modal
    showDeleteConfirmation(userId, username);
}

/**
 * Show custom delete confirmation modal
 * @param {number} userId - ID of the user to delete
 * @param {string} username - Username of the user to delete
 */
function showDeleteConfirmation(userId, username) {
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
                <p>Are you sure you want to permanently delete user <strong>${username}</strong> (ID: ${userId})?</p>
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
                // Reload the user list
                loadUsersAjax();
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
 * Export users to CSV
 */
function exportUsers() {
    // Log admin action
    logAdminAction('Exported user data');
    
    // Create CSV export
    const table = document.querySelector('.user-table');
    let csv = [];
    const rows = table.querySelectorAll('tr');
    
    rows.forEach(row => {
        const cols = row.querySelectorAll('td, th');
        const rowData = [];
        cols.forEach((col, index) => {
            if (index < cols.length - 1) { // Skip actions column
                rowData.push('"' + col.textContent.trim().replace(/"/g, '""') + '"');
            }
        });
        csv.push(rowData.join(','));
    });
    
    const csvContent = csv.join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'users_export_' + new Date().toISOString().split('T')[0] + '.csv';
    a.click();
    window.URL.revokeObjectURL(url);
    
    showToast('User data exported successfully', 'success');
}

function logAdminAction(action) {
    fetch('../../api/admin-log.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ action: action })
    }).catch(err => console.error('Failed to log action:', err));
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
            if (toast.parentNode) {
                document.body.removeChild(toast);
            }
        }, 300);
    }, 5000);
}
