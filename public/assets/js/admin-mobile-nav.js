/**
 * Admin Mobile Navigation Controller
 * Handles mobile hamburger menu functionality for admin panel
 */

document.addEventListener('DOMContentLoaded', function() {
    const mobileToggle = document.getElementById('admin-mobile-toggle');
    const mobileNav = document.getElementById('admin-nav-mobile');
    const body = document.body;
    
    if (!mobileToggle || !mobileNav) {
        return; // Elements not found, exit
    }

    // Toggle mobile navigation
    mobileToggle.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const isActive = mobileToggle.classList.contains('active');
        
        if (isActive) {
            closeMobileNav();
        } else {
            openMobileNav();
        }
    });

    // Close mobile nav when clicking outside
    document.addEventListener('click', function(e) {
        if (mobileNav.classList.contains('active')) {
            // Check if click is outside mobile nav and toggle button
            if (!mobileNav.contains(e.target) && !mobileToggle.contains(e.target)) {
                closeMobileNav();
            }
        }
    });

    // Close mobile nav when clicking on navigation links
    const mobileLinks = mobileNav.querySelectorAll('.admin-nav-mobile-link');
    mobileLinks.forEach(link => {
        link.addEventListener('click', function() {
            // Small delay to allow navigation to start before closing menu
            setTimeout(closeMobileNav, 100);
        });
    });

    // Handle escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && mobileNav.classList.contains('active')) {
            closeMobileNav();
        }
    });

    // Handle window resize - close mobile nav on larger screens
    let resizeTimer;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
            if (window.innerWidth > 768 && mobileNav.classList.contains('active')) {
                closeMobileNav();
            }
        }, 250);
    });

    function openMobileNav() {
        mobileToggle.classList.add('active');
        mobileNav.classList.add('active');
        body.classList.add('admin-mobile-menu-open');
        
        // Update aria attributes
        mobileToggle.setAttribute('aria-expanded', 'true');
        
        // Focus management for accessibility
        const firstLink = mobileNav.querySelector('.admin-nav-mobile-link');
        if (firstLink) {
            firstLink.focus();
        }
    }

    function closeMobileNav() {
        mobileToggle.classList.remove('active');
        mobileNav.classList.remove('active');
        body.classList.remove('admin-mobile-menu-open');
        
        // Update aria attributes
        mobileToggle.setAttribute('aria-expanded', 'false');
        
        // Return focus to toggle button for accessibility
        mobileToggle.focus();
    }

    // Touch gesture support for mobile devices
    let touchStartX = 0;
    let touchStartY = 0;
    
    mobileNav.addEventListener('touchstart', function(e) {
        touchStartX = e.touches[0].clientX;
        touchStartY = e.touches[0].clientY;
    }, { passive: true });

    mobileNav.addEventListener('touchmove', function(e) {
        if (!mobileNav.classList.contains('active')) return;
        
        const touchX = e.touches[0].clientX;
        const touchY = e.touches[0].clientY;
        const deltaX = touchX - touchStartX;
        const deltaY = touchY - touchStartY;
        
        // Close menu on swipe up gesture (if significant vertical movement)
        if (Math.abs(deltaY) > Math.abs(deltaX) && deltaY < -50) {
            closeMobileNav();
        }
    }, { passive: true });

    // Animation support check
    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    
    if (prefersReducedMotion) {
        // Disable animations for users who prefer reduced motion
        const style = document.createElement('style');
        style.textContent = `
            .admin-nav-mobile.active {
                animation: none !important;
            }
            .admin-hamburger-line,
            .admin-mobile-toggle {
                transition: none !important;
            }
        `;
        document.head.appendChild(style);
    }
});

// Export functions for potential external use
window.AdminMobileNav = {
    close: function() {
        const event = new CustomEvent('closeMobileNav');
        document.dispatchEvent(event);
    },
    
    open: function() {
        const event = new CustomEvent('openMobileNav');
        document.dispatchEvent(event);
    }
};
