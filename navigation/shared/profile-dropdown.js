/**
 * Profile Dropdown Functionality
 * Handles the profile dropdown toggle and interactions
 */

class ProfileDropdown {
    constructor() {
        this.dropdown = null;
        this.profileIcon = null;
        this.isOpen = false;
        this.init();
    }

    init() {
        // Wait for DOM to be ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.setupDropdown());
        } else {
            this.setupDropdown();
        }
    }

    setupDropdown() {
        this.profileIcon = document.querySelector('.profile-icon');
        this.dropdown = document.querySelector('.profile-dropdown-content');
        
        if (!this.profileIcon || !this.dropdown) {
            console.warn('Profile dropdown elements not found');
            return;
        }

        // Add click event to profile icon
        this.profileIcon.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            this.toggleDropdown();
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', (e) => {
            if (this.isOpen && !this.dropdown.contains(e.target) && !this.profileIcon.contains(e.target)) {
                this.closeDropdown();
            }
        });

        // Close dropdown on escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.isOpen) {
                this.closeDropdown();
            }
        });

        // Handle dropdown item clicks
        this.setupDropdownItems();
    }

    setupDropdownItems() {
        const dropdownItems = this.dropdown.querySelectorAll('.profile-dropdown-item');
        
        dropdownItems.forEach(item => {
            item.addEventListener('click', (e) => {
                // Play click sound if available
                if (typeof playClickSound === 'function') {
                    playClickSound();
                }

                // Handle sign out button
                if (item.classList.contains('sign-out')) {
                    e.preventDefault();
                    this.closeDropdown();
                    this.showLogoutModal();
                    return;
                }

                // For other items, let the default link behavior work
                // Close dropdown after a short delay to allow navigation
                setTimeout(() => {
                    this.closeDropdown();
                }, 100);
            });
        });
    }

    toggleDropdown() {
        if (this.isOpen) {
            this.closeDropdown();
        } else {
            this.openDropdown();
        }
    }

    openDropdown() {
        this.isOpen = true;
        this.dropdown.classList.add('show');
        
        // Play click sound if available
        if (typeof playClickSound === 'function') {
            playClickSound();
        }
    }

    closeDropdown() {
        this.isOpen = false;
        this.dropdown.classList.remove('show');
    }

    showLogoutModal() {
        // Use existing logout modal functionality if available
        if (typeof showLogoutModal === 'function') {
            showLogoutModal();
        } else {
            // Fallback: direct logout
            if (confirm('Are you sure you want to logout?')) {
                window.location.href = '../../onboarding/logout.php';
            }
        }
    }
}

// Initialize profile dropdown when script loads
const profileDropdown = new ProfileDropdown();



