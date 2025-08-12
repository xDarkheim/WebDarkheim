/**
 * OPTIMIZED HEADER FUNCTIONALITY
 * Fixed performance issues and mobile navigation
 */

document.addEventListener('DOMContentLoaded', function() {
    const header = document.getElementById('header');
    const mobileToggle = document.getElementById('mobile-nav-toggle');
    const mobileNav = document.getElementById('mobile-navigation');
    
    let isScrolling = false;
    let lastScrollY = window.scrollY;

    // Optimized scroll handler with throttling
    function handleScroll() {
        if (!isScrolling) {
            window.requestAnimationFrame(() => {
                const currentScrollY = window.scrollY;
                
                // Add/remove scrolled class based on scroll position
                if (currentScrollY > 50) {
                    header.classList.add('scrolled');
                } else {
                    header.classList.remove('scrolled');
                }
                
                lastScrollY = currentScrollY;
                isScrolling = false;
            });
            isScrolling = true;
        }
    }

    // Mobile menu toggle functionality
    function toggleMobileMenu() {
        const isActive = mobileToggle.classList.contains('active');
        
        if (isActive) {
            // Close menu
            mobileToggle.classList.remove('active');
            mobileNav.classList.remove('active');
            mobileToggle.setAttribute('aria-expanded', 'false');
            document.body.style.overflow = ''; // Re-enable scroll
        } else {
            // Open menu
            mobileToggle.classList.add('active');
            mobileNav.classList.add('active');
            mobileToggle.setAttribute('aria-expanded', 'true');
            document.body.style.overflow = 'hidden'; // Prevent scroll when menu is open
        }
    }

    // Close mobile menu when clicking on links
    function closeMobileMenu() {
        mobileToggle.classList.remove('active');
        mobileNav.classList.remove('active');
        mobileToggle.setAttribute('aria-expanded', 'false');
        document.body.style.overflow = '';
    }

    // Close mobile menu when clicking outside
    function handleOutsideClick(event) {
        if (mobileNav.classList.contains('active') && 
            !mobileNav.contains(event.target) && 
            !mobileToggle.contains(event.target)) {
            closeMobileMenu();
        }
    }

    // Set active navigation link
    function setActiveNavLink() {
        const currentPage = new URLSearchParams(window.location.search).get('page') || 'home';
        const navLinks = document.querySelectorAll('.nav-link, .mobile-nav-link');
        
        navLinks.forEach(link => {
            const href = link.getAttribute('href');
            if (href && href.includes(`page=${currentPage}`)) {
                link.classList.add('active');
            } else {
                link.classList.remove('active');
            }
        });
    }

    // Event listeners
    if (header) {
        // Throttled scroll event
        window.addEventListener('scroll', handleScroll, { passive: true });
        
        // Initial scroll check
        handleScroll();
    }

    if (mobileToggle && mobileNav) {
        // Mobile menu toggle
        mobileToggle.addEventListener('click', toggleMobileMenu);
        
        // Close menu on link clicks
        const mobileLinks = mobileNav.querySelectorAll('.mobile-nav-link');
        mobileLinks.forEach(link => {
            link.addEventListener('click', closeMobileMenu);
        });
        
        // Close menu on outside click
        document.addEventListener('click', handleOutsideClick);
        
        // Close menu on escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape' && mobileNav.classList.contains('active')) {
                closeMobileMenu();
            }
        });
    }

    // Set active navigation links
    setActiveNavLink();

    // Handle login button (if exists)
    const loginBtn = document.getElementById('header-login-btn');
    if (loginBtn) {
        loginBtn.addEventListener('click', function() {
            window.location.href = '/index.php?page=login';
        });
    }

    // Resize handler to close mobile menu on desktop
    window.addEventListener('resize', function() {
        if (window.innerWidth > 768 && mobileNav.classList.contains('active')) {
            closeMobileMenu();
        }
    });

    console.log('Header functionality initialized successfully');
});
