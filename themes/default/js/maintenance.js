/**
 * Maintenance Page JavaScript
 * Handles admin login modal and form interactions with mobile support
 */

// DOM Elements
let adminModal;
let adminLoginForm;
let passwordInput;
let passwordIcon;

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    initializeElements();
    initializeEventListeners();
    initializeProgressAnimation();
    initializeMobileOptimizations();
    initializeNetworkStatus();
    initializeTouchSupport();
    initializeAccessibility();
    initializeAllFeatures();
});

/**
 * Initialize DOM elements
 */
function initializeElements() {
    adminModal = document.getElementById('adminModal');
    adminLoginForm = document.getElementById('adminLoginForm');
    passwordInput = document.getElementById('password');
    passwordIcon = document.getElementById('passwordIcon');
}

/**
 * Initialize event listeners
 */
function initializeEventListeners() {
    // Close modal when clicking overlay
    if (adminModal) {
        adminModal.addEventListener('click', function(e) {
            if (e.target === adminModal) {
                closeAdminModal();
            }
        });
    }

    // Handle form submission
    if (adminLoginForm) {
        adminLoginForm.addEventListener('submit', handleFormSubmission);
    }

    // Handle escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && adminModal && adminModal.classList.contains('active')) {
            closeAdminModal();
        }
    });

    // Touch event listeners for mobile
    let touchStartY = 0;
    let touchEndY = 0;

    if (adminModal) {
        adminModal.addEventListener('touchstart', function(e) {
            touchStartY = e.changedTouches[0].screenY;
        });

        adminModal.addEventListener('touchend', function(e) {
            touchEndY = e.changedTouches[0].screenY;
            // Close modal on swipe down (mobile gesture)
            if (touchEndY - touchStartY > 100) {
                closeAdminModal();
            }
        });
    }
}

/**
 * Initialize mobile optimizations
 */
function initializeMobileOptimizations() {
    // Prevent zoom on input focus for iOS
    const inputs = document.querySelectorAll('input, select, textarea');
    inputs.forEach(input => {
        input.addEventListener('focus', function() {
            if (window.innerWidth <= 768) {
                const viewport = document.querySelector('meta[name=viewport]');
                if (viewport) {
                    viewport.setAttribute('content',
                        'width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no');
                }
            }
        });

        input.addEventListener('blur', function() {
            if (window.innerWidth <= 768) {
                const viewport = document.querySelector('meta[name=viewport]');
                if (viewport) {
                    viewport.setAttribute('content', 'width=device-width, initial-scale=1.0');
                }
            }
        });
    });

    // Handle orientation change
    window.addEventListener('orientationchange', function() {
        setTimeout(function() {
            if (adminModal && adminModal.classList.contains('active')) {
                adjustModalForOrientation();
            }
        }, 100);
    });

    // Handle window resize
    window.addEventListener('resize', debounce(function() {
        if (adminModal && adminModal.classList.contains('active')) {
            adjustModalForOrientation();
        }
    }, 250));
}

/**
 * Initialize progress bar animation
 */
function initializeProgressAnimation() {
    const progressFill = document.querySelector('.progress-fill');
    if (progressFill) {
        // Animate progress bar from 0 to 60%
        progressFill.style.width = '0%';
        setTimeout(() => {
            progressFill.style.transition = 'width 2s ease-out';
            progressFill.style.width = '60%';
        }, 500);

        // Add some randomness to the animation
        setInterval(() => {
            const randomDuration = 2000 + Math.random() * 2000;
            progressFill.style.animationDuration = `${randomDuration}ms`;
        }, 5000);
    }
}

/**
 * Open admin login modal
 */
function openAdminModal() {
    if (adminModal) {
        adminModal.classList.add('active');
        document.body.style.overflow = 'hidden';

        // Focus on username field for better UX
        const usernameInput = document.getElementById('username');
        if (usernameInput) {
            setTimeout(() => {
                usernameInput.focus();
            }, 300);
        }

        // Add mobile-specific modal adjustments
        if (window.innerWidth <= 768) {
            adjustModalForMobile();
        }
    }
}

/**
 * Close admin login modal
 */
function closeAdminModal() {
    if (adminModal) {
        adminModal.classList.remove('active');
        document.body.style.overflow = '';

        // Clear form on close
        if (adminLoginForm) {
            adminLoginForm.reset();
        }

        // Hide password if it was visible
        if (passwordInput && passwordInput.type === 'text') {
            togglePassword();
        }

        // Remove any error messages
        const existingError = document.querySelector('.form-error');
        if (existingError) {
            existingError.remove();
        }
    }
}

/**
 * Toggle password visibility
 */
function togglePassword() {
    if (passwordInput && passwordIcon) {
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            passwordIcon.className = 'fas fa-eye-slash';
        } else {
            passwordInput.type = 'password';
            passwordIcon.className = 'fas fa-eye';
        }
    }
}

/**
 * Handle form submission
 */
function handleFormSubmission(e) {
    const submitButton = adminLoginForm.querySelector('button[type="submit"]');
    const originalText = submitButton.innerHTML;

    // Validate form before submission
    const username = document.getElementById('username').value.trim();
    const password = document.getElementById('password').value;

    if (!username || !password) {
        e.preventDefault();
        showFormError('Please fill in all required fields.');
        return;
    }

    // Show loading state
    submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Logging in...';
    submitButton.disabled = true;
    submitButton.style.opacity = '0.7';

    // Note: Form will be submitted normally, this is just for UX feedback
    setTimeout(() => {
        if (submitButton) {
            submitButton.innerHTML = originalText;
            submitButton.disabled = false;
            submitButton.style.opacity = '1';
        }
    }, 3000);
}

/**
 * Show form error message
 */
function showFormError(message) {
    // Remove existing error messages
    const existingError = document.querySelector('.form-error');
    if (existingError) {
        existingError.remove();
    }

    // Create new error message
    const errorDiv = document.createElement('div');
    errorDiv.className = 'form-error';
    errorDiv.innerHTML = `
        <i class="fas fa-exclamation-triangle"></i>
        <span>${message}</span>
    `;

    // Add error styles
    errorDiv.style.cssText = `
        background: rgba(220, 53, 69, 0.1);
        color: #dc3545;
        padding: 0.75rem;
        border-radius: 6px;
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.9rem;
        border: 1px solid rgba(220, 53, 69, 0.3);
    `;

    // Insert before form actions
    const formActions = document.querySelector('.form-actions');
    if (formActions) {
        formActions.parentNode.insertBefore(errorDiv, formActions);
    }

    // Auto-remove after 5 seconds
    setTimeout(() => {
        if (errorDiv.parentNode) {
            errorDiv.remove();
        }
    }, 5000);
}

/**
 * Adjust modal for mobile devices
 */
function adjustModalForMobile() {
    const modalContainer = adminModal.querySelector('.modal-container');
    if (modalContainer) {
        // Ensure modal doesn't exceed viewport height
        const maxHeight = window.innerHeight - 40;
        modalContainer.style.maxHeight = maxHeight + 'px';

        // Center modal vertically for mobile
        if (window.innerHeight < 600) {
            adminModal.style.alignItems = 'flex-start';
            adminModal.style.paddingTop = '20px';
        }
    }
}

/**
 * Adjust modal for orientation changes
 */
function adjustModalForOrientation() {
    const modalContainer = adminModal.querySelector('.modal-container');
    if (modalContainer) {
        // Reset styles first
        modalContainer.style.maxHeight = '';
        adminModal.style.alignItems = '';
        adminModal.style.paddingTop = '';

        // Reapply mobile adjustments if needed
        if (window.innerWidth <= 768) {
            adjustModalForMobile();
        }
    }
}

/**
 * Debounce function for performance
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

/**
 * Check if device is mobile
 */
function isMobileDevice() {
    return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)
           || window.innerWidth <= 768;
}

/**
 * Handle network status for offline support
 */
function initializeNetworkStatus() {
    function updateNetworkStatus() {
        const isOnline = navigator.onLine;
        const submitButton = adminLoginForm?.querySelector('button[type="submit"]');

        if (submitButton) {
            if (!isOnline) {
                submitButton.disabled = true;
                submitButton.innerHTML = '<i class="fas fa-wifi"></i> Offline - Check Connection';
                submitButton.style.background = '#dc3545';
            } else {
                submitButton.disabled = false;
                submitButton.innerHTML = '<i class="fas fa-sign-in-alt"></i> Login to Admin Panel';
                submitButton.style.background = '';
            }
        }
    }

    window.addEventListener('online', updateNetworkStatus);
    window.addEventListener('offline', updateNetworkStatus);

    // Initial check
    updateNetworkStatus();
}

/**
 * Enhanced touch support for better mobile experience
 */
function initializeTouchSupport() {
    // Add touch feedback to buttons
    const buttons = document.querySelectorAll('.admin-login-btn, .btn, .social-link, .modal-close');

    buttons.forEach(button => {
        button.addEventListener('touchstart', function() {
            this.style.transform = 'scale(0.95)';
        });

        button.addEventListener('touchend', function() {
            setTimeout(() => {
                this.style.transform = '';
            }, 150);
        });

        button.addEventListener('touchcancel', function() {
            this.style.transform = '';
        });
    });
}

/**
 * Accessibility improvements for mobile
 */
function initializeAccessibility() {
    // Add keyboard navigation support
    const focusableElements = document.querySelectorAll(
        'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
    );

    focusableElements.forEach(element => {
        element.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                if (element.tagName === 'BUTTON' || element.getAttribute('role') === 'button') {
                    e.preventDefault();
                    element.click();
                }
            }
        });
    });

    // Improve focus visibility on mobile
    focusableElements.forEach(element => {
        element.addEventListener('focus', function() {
            if (isMobileDevice()) {
                this.style.outline = '2px solid var(--color-primary, #007bff)';
                this.style.outlineOffset = '2px';
            }
        });

        element.addEventListener('blur', function() {
            this.style.outline = '';
            this.style.outlineOffset = '';
        });
    });
}

/**
 * Add smooth scrolling for any internal links
 */
function initializeSmoothScrolling() {
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
}

/**
 * Handle responsive behavior
 */
function handleResponsiveFeatures() {
    // Adjust modal size on small screens
    function adjustModalSize() {
        const modal = document.querySelector('.modal-container');
        if (modal && window.innerWidth < 480) {
            modal.style.margin = '0.5rem';
            modal.style.borderRadius = '10px';
        }
    }

    // Call on load and resize
    adjustModalSize();
    window.addEventListener('resize', adjustModalSize);
}

/**
 * Add keyboard navigation support for modal
 */
function initializeKeyboardNavigation() {
    document.addEventListener('keydown', function(e) {
        // Tab navigation in modal
        if (adminModal && adminModal.classList.contains('active')) {
            const focusableElements = adminModal.querySelectorAll(
                'input, button, textarea, select, a[href]'
            );

            if (focusableElements.length > 0) {
                const firstElement = focusableElements[0];
                const lastElement = focusableElements[focusableElements.length - 1];

                if (e.key === 'Tab') {
                    if (e.shiftKey && document.activeElement === firstElement) {
                        e.preventDefault();
                        lastElement.focus();
                    } else if (!e.shiftKey && document.activeElement === lastElement) {
                        e.preventDefault();
                        firstElement.focus();
                    }
                }
            }
        }
    });
}

/**
 * Auto-open modal and show errors based on query flags
 */
function initLoginFromQuery() {
    try {
        const params = new URLSearchParams(window.location.search);
        if (params.has('login') || params.has('login_error') || params.has('login_csrf') || params.has('login_invalid') || params.has('login_role')) {
            // Ensure modal opens
            openAdminModal();

            // Map known flags to messages
            let message = null;
            if (params.has('login_csrf')) {
                message = 'Security error: Invalid CSRF token.';
            } else if (params.has('login_role')) {
                message = 'Access denied. Only administrators can access the site during maintenance.';
            } else if (params.has('login_invalid')) {
                message = 'Invalid username or password.';
            } else if (params.has('login_error')) {
                message = 'Login failed. Please check the form and try again.';
            }

            if (message) {
                // Slight delay to ensure form is in DOM and modal is active
                setTimeout(() => {
                    showFormError(message);
                }, 150);
            }
        }
    } catch (e) {
        // no-op
    }
}

/**
 * Initialize all additional features
 */
function initializeAllFeatures() {
    initializeSmoothScrolling();
    handleResponsiveFeatures();
    initializeKeyboardNavigation();
    initLoginFromQuery();
    initLoginFromQuery();
}
