/**
 * User Menu specific functionality for Darkheim Studio
 * Handles user profile interactions and menu behaviors
 */

    class UserMenuManager {
        constructor() {
            this.isOpen = false;
            this.focusableElements = [];
            this.currentFocusIndex = -1;

            this.init();
        }

        init() {
            this.setupUserMenu();
            this.setupProfileInteractions();
            this.setupQuickActions();
            this.setupKeyboardShortcuts();
        }

        /**
         * Setup user menu functionality
         */
        setupUserMenu() {
            const userMenu = document.querySelector('.user-menu');
            const userToggle = document.querySelector('.user-menu-toggle');
            const userDropdown = document.querySelector('.user-dropdown');

            if (!userMenu || !userToggle || !userDropdown) return;

            // Cache focusable elements
            this.focusableElements = userDropdown.querySelectorAll(
                'a, button, [tabindex]:not([tabindex="-1"])'
            );

            // Setup click handlers
            userToggle.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                this.toggle();
            });

            // Setup hover effects
            userToggle.addEventListener('mouseenter', () => {
                this.addHoverEffect(userToggle);
            });

            userToggle.addEventListener('mouseleave', () => {
                this.removeHoverEffect(userToggle);
            });

            // Setup dropdown item interactions
            this.setupDropdownItems(userDropdown);
        }

        /**
         * Setup dropdown item interactions
         */
        setupDropdownItems(dropdown) {
            const items = dropdown.querySelectorAll('.dropdown-item');

            items.forEach((item, index) => {
                // Add hover effects
                item.addEventListener('mouseenter', () => {
                    this.highlightItem(item, index);
                });

                item.addEventListener('mouseleave', () => {
                    this.removeHighlight(item);
                });

                // Add click handlers for special actions
                if (item.href && item.href.includes('logout')) {
                    item.addEventListener('click', (e) => {
                        this.handleLogout(e, item);
                    });
                }

                if (item.href && item.href.includes('settings')) {
                    item.addEventListener('click', (e) => {
                        this.handleSettings(e, item);
                    });
                }
            });
        }

        /**
         * Toggle user menu
         */
        toggle() {
            if (this.isOpen) {
                this.close();
            } else {
                this.open();
            }
        }

        /**
         * Open user menu
         */
        open() {
            const userToggle = document.querySelector('.user-menu-toggle');
            const userDropdown = document.querySelector('.user-dropdown');

            if (!userToggle || !userDropdown) return;

            // Close other dropdowns first
            if (window.navigationManager) {
                window.navigationManager.closeAllDropdowns();
            }

            this.isOpen = true;
            userToggle.setAttribute('aria-expanded', 'true');
            userDropdown.classList.add('show');

            // Add animation class
            userDropdown.classList.add('dropdown-enter');

            // Focus first item
            if (this.focusableElements.length > 0) {
                this.currentFocusIndex = 0;
                this.focusableElements[0].focus();
            }

            // Remove animation class after animation
            setTimeout(() => {
                userDropdown.classList.remove('dropdown-enter');
            }, 300);
        }

        /**
         * Close user menu
         */
        close() {
            const userToggle = document.querySelector('.user-menu-toggle');
            const userDropdown = document.querySelector('.user-dropdown');

            if (!userToggle || !userDropdown) return;

            this.isOpen = false;
            userToggle.setAttribute('aria-expanded', 'false');

            // Add exit animation
            userDropdown.classList.add('dropdown-exit');

            setTimeout(() => {
                userDropdown.classList.remove('show', 'dropdown-exit');
            }, 200);

            this.currentFocusIndex = -1;
        }

        /**
         * Handle keyboard navigation
         */
        handleKeyNavigation(e) {
            if (!this.isOpen) return;

            switch (e.key) {
                case 'ArrowDown':
                    e.preventDefault();
                    this.focusNext();
                    break;

                case 'ArrowUp':
                    e.preventDefault();
                    this.focusPrevious();
                    break;

                case 'Home':
                    e.preventDefault();
                    this.focusFirst();
                    break;

                case 'End':
                    e.preventDefault();
                    this.focusLast();
                    break;

                case 'Escape':
                    e.preventDefault();
                    this.close();
                    document.querySelector('.user-menu-toggle').focus();
                    break;
            }
        }

        /**
         * Focus navigation methods
         */
        focusNext() {
            if (this.currentFocusIndex < this.focusableElements.length - 1) {
                this.currentFocusIndex++;
            } else {
                this.currentFocusIndex = 0;
            }
            this.focusableElements[this.currentFocusIndex].focus();
        }

        focusPrevious() {
            if (this.currentFocusIndex > 0) {
                this.currentFocusIndex--;
            } else {
                this.currentFocusIndex = this.focusableElements.length - 1;
            }
            this.focusableElements[this.currentFocusIndex].focus();
        }

        focusFirst() {
            this.currentFocusIndex = 0;
            this.focusableElements[0].focus();
        }

        focusLast() {
            this.currentFocusIndex = this.focusableElements.length - 1;
            this.focusableElements[this.currentFocusIndex].focus();
        }

        /**
         * Visual effects
         */
        addHoverEffect(element) {
            element.classList.add('user-menu-hover');
        }

        removeHoverEffect(element) {
            element.classList.remove('user-menu-hover');
        }

        highlightItem(item, index) {
            this.currentFocusIndex = index;
            item.classList.add('highlighted');
        }

        removeHighlight(item) {
            item.classList.remove('highlighted');
        }

        /**
         * Setup profile interactions
         */
        setupProfileInteractions() {
            const userAvatar = document.querySelector('.user-avatar');
            const userInfo = document.querySelector('.user-info');

            if (userAvatar) {
                // Add ripple effect on click
                userAvatar.addEventListener('click', (e) => {
                    this.createRippleEffect(e, userAvatar);
                });
            }

            if (userInfo) {
                // Add subtle animations
                userInfo.addEventListener('mouseenter', () => {
                    userInfo.style.transform = 'translateY(-1px)';
                });

                userInfo.addEventListener('mouseleave', () => {
                    userInfo.style.transform = 'translateY(0)';
                });
            }
        }

        /**
         * Create ripple effect
         */
        createRippleEffect(e, element) {
            const ripple = document.createElement('span');
            const rect = element.getBoundingClientRect();
            const size = Math.max(rect.width, rect.height);
            const x = e.clientX - rect.left - size / 2;
            const y = e.clientY - rect.top - size / 2;

            ripple.style.width = ripple.style.height = size + 'px';
            ripple.style.left = x + 'px';
            ripple.style.top = y + 'px';
            ripple.classList.add('ripple-effect');

            element.appendChild(ripple);

            setTimeout(() => {
                ripple.remove();
            }, 600);
        }

        /**
         * Setup quick actions
         */
        setupQuickActions() {
            // Quick keyboard shortcuts
            document.addEventListener('keydown', (e) => {
                // Ctrl/Cmd + U for user menu
                if ((e.ctrlKey || e.metaKey) && e.key === 'u') {
                    e.preventDefault();
                    this.toggle();
                }

                // Handle navigation when menu is open
                if (this.isOpen) {
                    this.handleKeyNavigation(e);
                }
            });
        }

        /**
         * Setup keyboard shortcuts
         */
        setupKeyboardShortcuts() {
            // Alt + L for logout
            document.addEventListener('keydown', (e) => {
                if (e.altKey && e.key === 'l') {
                    e.preventDefault();
                    const logoutLink = document.querySelector('a[href*="logout"]');
                    if (logoutLink) {
                        this.handleLogout(e, logoutLink);
                    }
                }

                // Alt + S for settings
                if (e.altKey && e.key === 's') {
                    e.preventDefault();
                    const settingsLink = document.querySelector('a[href*="settings"]');
                    if (settingsLink) {
                        this.handleSettings(e, settingsLink);
                    }
                }
            });
        }

        /**
         * Handle logout action
         */
        handleLogout(e, link) {
            e.preventDefault();

            if (window.DarkheimUtils) {
                window.DarkheimUtils.confirm(
                    'Are you sure you want to sign out?',
                    (confirmed) => {
                        if (confirmed) {
                            window.DarkheimUtils.showLoading();
                            window.location.href = link.href;
                        }
                    }
                );
            } else {
                if (confirm('Are you sure you want to sign out?')) {
                    window.location.href = link.href;
                }
            }
        }

        /**
         * Handle settings action
         */
        handleSettings(e, link) {
            e.preventDefault();

            if (window.DarkheimUtils) {
                window.DarkheimUtils.safeNavigate(link.href, e);
            } else {
                window.location.href = link.href;
            }
        }

        /**
         * Public API
         */
        getState() {
            return {
                isOpen: this.isOpen,
                currentFocusIndex: this.currentFocusIndex
            };
        }

        forceClose() {
            this.close();
        }

        forceOpen() {
            this.open();
        }
    }

// Initialize when DOM is ready
    document.addEventListener('DOMContentLoaded', function () {
        window.userMenuManager = new UserMenuManager();
    });

// Export for module systems
    if (typeof module !== 'undefined' && module.exports) {
        module.exports = UserMenuManager;
    }
