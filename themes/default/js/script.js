/**
 * Modern JavaScript for Development Studio
 * Handles navigation, animations, and user interactions
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize all components
    initMobileNavigation();
    initScrollEffects();
    initAnimations();
    initSmoothScrolling();
    initThemeToggle();
});

/**
 * Mobile Navigation Handler
 */
function initMobileNavigation() {
    const mobileToggle = document.getElementById('mobile-nav-toggle');
    const mobileNav = document.getElementById('mobile-navigation');
    const body = document.body;

    if (mobileToggle && mobileNav) {
        mobileToggle.addEventListener('click', function() {
            const isExpanded = mobileToggle.getAttribute('aria-expanded') === 'true';

            // Toggle states
            mobileToggle.setAttribute('aria-expanded', !isExpanded);
            mobileToggle.classList.toggle('active');
            mobileNav.classList.toggle('active');
            body.classList.toggle('mobile-nav-open');
        });

        // Close mobile nav when clicking on links
        const mobileLinks = mobileNav.querySelectorAll('.mobile-nav-link');
        mobileLinks.forEach(link => {
            link.addEventListener('click', function() {
                mobileToggle.setAttribute('aria-expanded', 'false');
                mobileToggle.classList.remove('active');
                mobileNav.classList.remove('active');
                body.classList.remove('mobile-nav-open');
            });
        });

        // Close mobile nav when clicking outside
        document.addEventListener('click', function(e) {
            if (!mobileToggle.contains(e.target) && !mobileNav.contains(e.target)) {
                mobileToggle.setAttribute('aria-expanded', 'false');
                mobileToggle.classList.remove('active');
                mobileNav.classList.remove('active');
                body.classList.remove('mobile-nav-open');
            }
        });
    }
}

/**
 * Scroll Effects Handler
 */
function initScrollEffects() {
    const header = document.getElementById('header');
    let lastScrollTop = 0;

    window.addEventListener('scroll', function() {
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;

        // Add scrolled class for header background
        if (scrollTop > 50) {
            header.classList.add('scrolled');
        } else {
            header.classList.remove('scrolled');
        }

        // Hide/show header on scroll (optional)
        if (scrollTop > lastScrollTop && scrollTop > 100) {
            header.style.transform = 'translateY(-100%)';
        } else {
            header.style.transform = 'translateY(0)';
        }

        lastScrollTop = scrollTop;
    });
}

/**
 * Intersection Observer for Animations
 */
function initAnimations() {
    // Fade in animation for elements
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate-in');
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);

    // Observe elements for animation
    const animateElements = document.querySelectorAll('.service-card, .project-card, .stat-item, .about-content');
    animateElements.forEach(el => {
        el.classList.add('animate-on-scroll');
        observer.observe(el);
    });

    // Parallax effect for hero section
    const heroSection = document.querySelector('.hero-section');
    if (heroSection) {
        window.addEventListener('scroll', function() {
            const scrolled = window.pageYOffset;
            const parallax = heroSection.querySelector('.hero-content');
            if (parallax) {
                parallax.style.transform = `translateY(${scrolled * 0.1}px)`;
            }
        });
    }
}

/**
 * Smooth Scrolling for Anchor Links
 */
function initSmoothScrolling() {
    const smoothLinks = document.querySelectorAll('a[href^="#"]');

    smoothLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();

            const targetId = this.getAttribute('href');
            const targetElement = document.querySelector(targetId);

            if (targetElement) {
                const headerHeight = document.getElementById('header').offsetHeight;
                const targetPosition = targetElement.offsetTop - headerHeight - 20;

                window.scrollTo({
                    top: targetPosition,
                    behavior: 'smooth'
                });
            }
        });
    });
}

/**
 * Theme Toggle (Dark/Light Mode)
 */
function initThemeToggle() {
    const themeToggle = document.getElementById('theme-toggle');
    const body = document.body;

    // Check for saved theme preference
    const savedTheme = localStorage.getItem('theme');
    if (savedTheme) {
        body.className = savedTheme;
    }

    if (themeToggle) {
        themeToggle.addEventListener('click', function() {
            if (body.classList.contains('theme-dark')) {
                body.className = 'theme-light';
                localStorage.setItem('theme', 'theme-light');
            } else {
                body.className = 'theme-dark';
                localStorage.setItem('theme', 'theme-dark');
            }
        });
    }
}

/**
 * Form Enhancements
 */
function initFormEnhancements() {
    const forms = document.querySelectorAll('form');

    forms.forEach(form => {
        const inputs = form.querySelectorAll('input, textarea');

        inputs.forEach(input => {
            // Add floating label effect
            input.addEventListener('focus', function() {
                this.parentElement.classList.add('focused');
            });

            input.addEventListener('blur', function() {
                if (!this.value) {
                    this.parentElement.classList.remove('focused');
                }
            });

            // Initialize state for pre-filled inputs
            if (input.value) {
                input.parentElement.classList.add('focused');
            }
        });
    });
}

/**
 * Tech Stack Animation
 */
function initTechStackAnimation() {
    const techIcons = document.querySelectorAll('.tech-icon');

    techIcons.forEach((icon, index) => {
        icon.style.animationDelay = `${index * 0.1}s`;

        icon.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-10px) scale(1.2) rotate(10deg)';
        });

        icon.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0) scale(1) rotate(0deg)';
        });
    });
}

/**
 * Typing Animation for Hero Title
 */
function initTypingAnimation() {
    const heroTitle = document.querySelector('.hero-title');
    if (!heroTitle) return;

    const text = heroTitle.textContent;
    heroTitle.textContent = '';

    let i = 0;
    const typeWriter = function() {
        if (i < text.length) {
            heroTitle.textContent += text.charAt(i);
            i++;
            setTimeout(typeWriter, 100);
        }
    };

    // Start typing animation after a delay
    setTimeout(typeWriter, 1000);
}

/**
 * Particle System Enhancement
 */
function enhanceParticleSystem() {
    const particleContainer = document.querySelector('.hero-particles');
    if (!particleContainer) return;

    // Add more particles dynamically
    for (let i = 0; i < 5; i++) {
        const particle = document.createElement('div');
        particle.className = 'particle';
        particle.style.left = Math.random() * 100 + '%';
        particle.style.animationDelay = Math.random() * 8 + 's';
        particle.style.animationDuration = (Math.random() * 3 + 5) + 's';
        particleContainer.appendChild(particle);
    }
}

/**
 * Performance Optimization
 */
function initPerformanceOptimizations() {
    // Lazy loading for images
    const images = document.querySelectorAll('img[data-src]');
    const imageObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                img.src = img.dataset.src;
                img.classList.remove('lazy');
                imageObserver.unobserve(img);
            }
        });
    });

    images.forEach(img => imageObserver.observe(img));

    // Debounced scroll handler
    let scrollTimeout;
    window.addEventListener('scroll', function() {
        if (scrollTimeout) {
            clearTimeout(scrollTimeout);
        }
        scrollTimeout = setTimeout(function() {
            // Optimized scroll handler
        }, 10);
    });
}

// CSS Animations Classes
const animationCSS = `
.animate-on-scroll {
    opacity: 0;
    transform: translateY(30px);
    transition: opacity 0.6s ease, transform 0.6s ease;
}

.animate-in {
    opacity: 1;
    transform: translateY(0);
}

.hamburger-line {
    display: block;
    width: 20px;
    height: 2px;
    background: currentColor;
    margin: 3px 0;
    transition: all 0.3s ease;
}

.mobile-nav-toggle.active .hamburger-line:nth-child(2) {
    transform: rotate(45deg) translate(5px, 5px);
}

.mobile-nav-toggle.active .hamburger-line:nth-child(3) {
    opacity: 0;
}

.mobile-nav-toggle.active .hamburger-line:nth-child(4) {
    transform: rotate(-45deg) translate(7px, -6px);
}
`;

// Inject CSS
const style = document.createElement('style');
style.textContent = animationCSS;
document.head.appendChild(style);

// Initialize additional features when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    initFormEnhancements();
    initTechStackAnimation();
    enhanceParticleSystem();
    initPerformanceOptimizations();

    // Add smooth transitions to all interactive elements
    document.body.classList.add('js-loaded');
});
