/**
 * Enhanced Navigation functionality for Darkheim Studio
 * Includes dropdown menus, user menu, and mobile navigation
 */

// Prevent duplicate loading
if (typeof window.NavigationManager === 'undefined') {

class NavigationManager {
    constructor() {
        this.isInitialized = false;
        this.activeDropdown = null;
        this.mobileNavOpen = false;
        this.touchStartY = 0;
        this.touchEndY = 0;

        this.init();
    }

    init() {
        if (this.isInitialized) return;

        this.setupMobileNavigation();
        this.setupUserMenu();
        this.setupDropdownMenus();
        this.setupClickOutside();
        this.setupKeyboardNavigation();
        this.setupTouchHandling();
        this.setupAccessibility();

        this.isInitialized = true;
    }

    /**
     * Mobile Navigation Setup
     */
    setupMobileNavigation() {
        const mobileToggle = document.getElementById('mobile-nav-toggle');
        const mobileNav = document.getElementById('mobile-navigation');
        const body = document.body;

        if (!mobileToggle || !mobileNav) return;

        mobileToggle.addEventListener('click', (e) => {
            e.preventDefault();
            this.toggleMobileNav();
        });

        // Close mobile nav when clicking on a link
        mobileNav.addEventListener('click', (e) => {
            if (e.target.matches('a:not(.has-dropdown)')) {
                this.closeMobileNav();
            }
        });

        // Handle mobile dropdown toggles
        const mobileDropdownToggles = mobileNav.querySelectorAll('.has-dropdown > .mobile-nav-link');
        mobileDropdownToggles.forEach(toggle => {
            toggle.addEventListener('click', (e) => {
                e.preventDefault();
                this.toggleMobileDropdown(toggle.parentElement);
            });
        });
    }

    toggleMobileNav() {
        const mobileToggle = document.getElementById('mobile-nav-toggle');
        const mobileNav = document.getElementById('mobile-navigation');
        const body = document.body;

        this.mobileNavOpen = !this.mobileNavOpen;

        mobileToggle.setAttribute('aria-expanded', this.mobileNavOpen);
        mobileToggle.classList.toggle('active', this.mobileNavOpen);
        mobileNav.classList.toggle('active', this.mobileNavOpen);
        body.classList.toggle('mobile-nav-open', this.mobileNavOpen);

        if (this.mobileNavOpen) {
            // Focus first link when opening
            const firstLink = mobileNav.querySelector('a');
            if (firstLink) firstLink.focus();
        }
    }

    closeMobileNav() {
        if (!this.mobileNavOpen) return;

        const mobileToggle = document.getElementById('mobile-nav-toggle');
        const mobileNav = document.getElementById('mobile-navigation');
        const body = document.body;

        this.mobileNavOpen = false;

        mobileToggle.setAttribute('aria-expanded', 'false');
        mobileToggle.classList.remove('active');
        mobileNav.classList.remove('active');
        body.classList.remove('mobile-nav-open');

        // Close all mobile dropdowns
        const openDropdowns = mobileNav.querySelectorAll('.mobile-nav-item.open');
        openDropdowns.forEach(dropdown => {
            dropdown.classList.remove('open');
        });
    }

    toggleMobileDropdown(dropdownItem) {
        const isOpen = dropdownItem.classList.contains('open');

        // Close other mobile dropdowns
        const allDropdowns = document.querySelectorAll('.mobile-nav-item.has-dropdown');
        allDropdowns.forEach(item => {
            if (item !== dropdownItem) {
                item.classList.remove('open');
            }
        });

        // Toggle current dropdown
        dropdownItem.classList.toggle('open', !isOpen);
    }

    /**
     * User Menu Setup
     */
    setupUserMenu() {
        const userMenuToggle = document.querySelector('.user-menu-toggle');
        const userDropdown = document.querySelector('.user-dropdown');

        if (!userMenuToggle || !userDropdown) return;

        userMenuToggle.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            this.toggleUserMenu();
        });

        // Handle keyboard navigation in user menu
        userDropdown.addEventListener('keydown', (e) => {
            this.handleDropdownKeyNavigation(e, userDropdown);
        });
    }

    toggleUserMenu() {
        const userMenuToggle = document.querySelector('.user-menu-toggle');
        const userDropdown = document.querySelector('.user-dropdown');

        if (!userMenuToggle || !userDropdown) return;

        const isExpanded = userMenuToggle.getAttribute('aria-expanded') === 'true';

        // Close other dropdowns first
        this.closeAllDropdowns();

        if (!isExpanded) {
            userMenuToggle.setAttribute('aria-expanded', 'true');
            userDropdown.classList.add('show');
            this.activeDropdown = userDropdown;

            // Focus first menu item
            const firstItem = userDropdown.querySelector('.dropdown-item');
            if (firstItem) firstItem.focus();
        } else {
            this.closeUserMenu();
        }
    }

    closeUserMenu() {
        const userMenuToggle = document.querySelector('.user-menu-toggle');
        const userDropdown = document.querySelector('.user-dropdown');

        if (!userMenuToggle || !userDropdown) return;

        userMenuToggle.setAttribute('aria-expanded', 'false');
        userDropdown.classList.remove('show');
        this.activeDropdown = null;
    }

    /**
     * Desktop Dropdown Menus Setup
     */
    setupDropdownMenus() {
        const dropdownItems = document.querySelectorAll('.nav-item.has-dropdown');

        dropdownItems.forEach(item => {
            const link = item.querySelector('.nav-link');
            const menu = item.querySelector('.dropdown-menu');

            if (!link || !menu) return;

            // Mouse events
            item.addEventListener('mouseenter', () => {
                this.showDropdown(item, link, menu);
            });

            item.addEventListener('mouseleave', () => {
                this.hideDropdown(item, link, menu);
            });

            // Keyboard events
            link.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    this.toggleDropdown(item, link, menu);
                } else if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    this.showDropdown(item, link, menu);
                    const firstItem = menu.querySelector('.dropdown-item');
                    if (firstItem) firstItem.focus();
                }
            });

            // Handle keyboard navigation within dropdown
            menu.addEventListener('keydown', (e) => {
                this.handleDropdownKeyNavigation(e, menu);
            });
        });
    }

    showDropdown(item, link, menu) {
        // Close other dropdowns first
        this.closeAllDropdowns();

        link.setAttribute('aria-expanded', 'true');
        menu.classList.add('show');
        this.activeDropdown = menu;
    }

    hideDropdown(item, link, menu) {
        link.setAttribute('aria-expanded', 'false');
        menu.classList.remove('show');
        if (this.activeDropdown === menu) {
            this.activeDropdown = null;
        }
    }

    toggleDropdown(item, link, menu) {
        const isExpanded = link.getAttribute('aria-expanded') === 'true';

        if (isExpanded) {
            this.hideDropdown(item, link, menu);
        } else {
            this.showDropdown(item, link, menu);
        }
    }

    /**
     * Keyboard Navigation Handler
     */
    handleDropdownKeyNavigation(e, dropdown) {
        const items = dropdown.querySelectorAll('.dropdown-item, .dropdown-link');
        const currentIndex = Array.from(items).indexOf(document.activeElement);

        switch (e.key) {
            case 'ArrowDown':
                e.preventDefault();
                const nextIndex = (currentIndex + 1) % items.length;
                items[nextIndex].focus();
                break;

            case 'ArrowUp':
                e.preventDefault();
                const prevIndex = currentIndex === 0 ? items.length - 1 : currentIndex - 1;
                items[prevIndex].focus();
                break;

            case 'Escape':
                e.preventDefault();
                this.closeAllDropdowns();

                // Return focus to trigger element
                const userToggle = document.querySelector('.user-menu-toggle');
                const navLinks = document.querySelectorAll('.nav-link[aria-expanded="true"]');

                if (dropdown.closest('.user-dropdown') && userToggle) {
                    userToggle.focus();
                } else if (navLinks.length > 0) {
                    navLinks[0].focus();
                }
                break;

            case 'Home':
                e.preventDefault();
                items[0].focus();
                break;

            case 'End':
                e.preventDefault();
                items[items.length - 1].focus();
                break;
        }
    }

    /**
     * Global Keyboard Navigation
     */
    setupKeyboardNavigation() {
        document.addEventListener('keydown', (e) => {
            // Escape key closes mobile nav and dropdowns
            if (e.key === 'Escape') {
                if (this.mobileNavOpen) {
                    this.closeMobileNav();
                } else {
                    this.closeAllDropdowns();
                }
            }
        });
    }

    /**
     * Click Outside Handler
     */
    setupClickOutside() {
        document.addEventListener('click', (e) => {
            // Close dropdowns when clicking outside
            if (!e.target.closest('.nav-item.has-dropdown') &&
                !e.target.closest('.user-menu')) {
                this.closeAllDropdowns();
            }

            // Close mobile nav when clicking outside
            if (this.mobileNavOpen &&
                !e.target.closest('.mobile-nav') &&
                !e.target.closest('.mobile-nav-toggle')) {
                this.closeMobileNav();
            }
        });
    }

    /**
     * Touch Handling for Mobile
     */
    setupTouchHandling() {
        const mobileNav = document.getElementById('mobile-navigation');
        if (!mobileNav) return;

        mobileNav.addEventListener('touchstart', (e) => {
            this.touchStartY = e.touches[0].clientY;
        }, { passive: true });

        mobileNav.addEventListener('touchend', (e) => {
            this.touchEndY = e.changedTouches[0].clientY;

            // Close mobile nav on swipe up
            if (this.touchStartY - this.touchEndY > 100) {
                this.closeMobileNav();
            }
        }, { passive: true });
    }

    /**
     * Accessibility Enhancements
     */
    setupAccessibility() {
        // Add proper ARIA labels and roles
        const mobileNav = document.getElementById('mobile-navigation');
        if (mobileNav) {
            mobileNav.setAttribute('role', 'navigation');
            mobileNav.setAttribute('aria-label', 'Mobile navigation');
        }

        const userDropdown = document.querySelector('.user-dropdown');
        if (userDropdown) {
            userDropdown.setAttribute('role', 'menu');
        }

        // Add aria-current to active navigation items
        const currentPage = window.location.pathname;
        const navLinks = document.querySelectorAll('.nav-link, .mobile-nav-link');

        navLinks.forEach(link => {
            if (link.getAttribute('href') === currentPage) {
                link.setAttribute('aria-current', 'page');
            }
        });
    }

    /**
     * Close All Dropdowns
     */
    closeAllDropdowns() {
        // Close desktop dropdowns
        const expandedLinks = document.querySelectorAll('.nav-link[aria-expanded="true"]');
        expandedLinks.forEach(link => {
            link.setAttribute('aria-expanded', 'false');
            const dropdown = link.nextElementSibling;
            if (dropdown) dropdown.classList.remove('show');
        });

        // Close user menu
        this.closeUserMenu();

        this.activeDropdown = null;
    }

    /**
     * Public API for external access
     */
    toggle(type) {
        switch (type) {
            case 'mobile':
                this.toggleMobileNav();
                break;
            case 'user':
                this.toggleUserMenu();
                break;
        }
    }

    close(type) {
        switch (type) {
            case 'mobile':
                this.closeMobileNav();
                break;
            case 'user':
                this.closeUserMenu();
                break;
            case 'all':
                this.closeAllDropdowns();
                this.closeMobileNav();
                break;
        }
    }
}

// Initialize navigation when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    window.navigationManager = new NavigationManager();
});

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = NavigationManager;
}
}
