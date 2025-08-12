/**
 * Login page specific functionality
 * Extends AuthFormHandler for login-specific features
 */

class LoginFormHandler extends AuthFormHandler {
    constructor() {
        super();
        this.initLoginFeatures();
    }

    initLoginFeatures() {
        this.setupLoginForm();
        this.setupStatusIndicator();
        this.setupRememberMe();
        this.setupSocialAuth();
    }

    setupLoginForm() {
        const loginForm = document.getElementById('loginForm');
        if (!loginForm) return;

        loginForm.addEventListener('submit', (e) => {
            this.handleLoginSubmit(e);
        });
    }

    handleLoginSubmit(e) {
        const form = e.target;
        const submitBtn = document.getElementById('submitBtn');
        const statusDiv = document.getElementById('loginStatus');
        const statusText = document.getElementById('statusText');

        // Show status indicator
        if (statusDiv) {
            statusDiv.style.display = 'block';
            statusText.textContent = 'Verifying credentials...';
        }

        // Update button state
        this.setLoadingState(submitBtn, true);

        // Enhanced validation
        const usernameOrEmail = form.querySelector('[name="username_or_email"]').value;
        const password = form.querySelector('[name="password"]').value;

        if (!usernameOrEmail.trim()) {
            this.showError('Please enter your username or email');
            this.setLoadingState(submitBtn, false);
            statusDiv.style.display = 'none';
            e.preventDefault();
            return;
        }

        if (!password.trim()) {
            this.showError('Please enter your password');
            this.setLoadingState(submitBtn, false);
            statusDiv.style.display = 'none';
            e.preventDefault();
            return;
        }

        // Update status
        if (statusText) {
            statusText.textContent = 'Signing you in...';
        }

        // Set timeout to restore button if form doesn't submit within 10 seconds
        setTimeout(() => {
            this.setLoadingState(submitBtn, false);
            if (statusDiv) {
                statusDiv.style.display = 'none';
            }
        }, 10000);
    }

    setupStatusIndicator() {
        // Status indicator is handled in handleLoginSubmit
    }

    setupRememberMe() {
        const rememberMeCheckbox = document.getElementById('login_remember_me');
        if (!rememberMeCheckbox) return;

        // Add tooltip or additional info
        const label = rememberMeCheckbox.nextElementSibling;
        if (label) {
            label.addEventListener('click', () => {
                rememberMeCheckbox.checked = !rememberMeCheckbox.checked;
                this.updateRememberMeState(rememberMeCheckbox.checked);
            });
        }

        rememberMeCheckbox.addEventListener('change', (e) => {
            this.updateRememberMeState(e.target.checked);
        });
    }

    updateRememberMeState(isChecked) {
        const checkboxText = document.querySelector('.checkbox-text');
        if (checkboxText) {
            if (isChecked) {
                checkboxText.textContent = 'Remember me for 30 days ✓';
                checkboxText.style.color = '#10b981';
            } else {
                checkboxText.textContent = 'Remember me for 30 days';
                checkboxText.style.color = '';
            }
        }
    }

    setupSocialAuth() {
        const socialButtons = document.querySelectorAll('.social-btn');

        socialButtons.forEach(button => {
            button.addEventListener('click', (e) => {
                e.preventDefault();

                // Get platform name
                const platform = button.classList.contains('social-btn--google') ? 'Google' :
                                 button.classList.contains('social-btn--meta') ? 'Meta' :
                                 button.classList.contains('social-btn--telegram') ? 'Telegram' :
                                 button.classList.contains('social-btn--microsoft') ? 'Microsoft' : 'Social';

                // Show coming soon message or handle social auth
                this.showSocialAuthMessage(platform);
            });

            // Add hover effects
            button.addEventListener('mouseenter', () => {
                button.style.transform = 'translateY(-2px) scale(1.02)';
            });

            button.addEventListener('mouseleave', () => {
                button.style.transform = '';
            });
        });
    }

    showSocialAuthMessage(platform) {
        // Create temporary notification
        const notification = document.createElement('div');
        notification.className = 'social-auth-notification';
        notification.innerHTML = `
            <div style="background: rgba(59, 130, 246, 0.9); color: white; padding: 0.75rem 1rem; border-radius: 0.5rem; font-size: 0.9rem; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3); backdrop-filter: blur(10px);">
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <span>🚧</span>
                    <span>${platform} login coming soon!</span>
                </div>
            </div>
        `;

        // Position and show
        notification.style.position = 'fixed';
        notification.style.top = '20px';
        notification.style.right = '20px';
        notification.style.zIndex = '9999';
        notification.style.animation = 'slideInRight 0.3s ease-out';

        document.body.appendChild(notification);

        // Remove after 3 seconds
        setTimeout(() => {
            notification.style.animation = 'slideOutRight 0.3s ease-in';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        }, 3000);
    }

    showError(message) {
        // Create error notification
        const notification = document.createElement('div');
        notification.className = 'login-error-notification';
        notification.innerHTML = `
            <div style="background: rgba(239, 68, 68, 0.9); color: white; padding: 0.75rem 1rem; border-radius: 0.5rem; font-size: 0.9rem; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3); backdrop-filter: blur(10px);">
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <span>❌</span>
                    <span>${message}</span>
                </div>
            </div>
        `;

        notification.style.position = 'fixed';
        notification.style.top = '20px';
        notification.style.right = '20px';
        notification.style.zIndex = '9999';
        notification.style.animation = 'slideInRight 0.3s ease-out';

        document.body.appendChild(notification);

        setTimeout(() => {
            notification.style.animation = 'slideOutRight 0.3s ease-in';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        }, 4000);
    }

    setLoadingState(button, isLoading) {
        if (!button) return;

        if (isLoading) {
            button.disabled = true;
            button.dataset.originalText = button.querySelector('.button-text')?.textContent || button.textContent;

            const icon = button.querySelector('.button-icon');
            const text = button.querySelector('.button-text');

            if (icon) icon.textContent = '⏳';
            if (text) {
                text.textContent = 'Signing In...';
            } else {
                button.textContent = 'Signing In...';
            }

            button.style.opacity = '0.7';
            button.classList.add('button-loading');
        } else {
            button.disabled = false;
            button.style.opacity = '1';
            button.classList.remove('button-loading');

            const icon = button.querySelector('.button-icon');
            const text = button.querySelector('.button-text');

            if (icon) icon.textContent = '🚀';
            if (text) {
                text.textContent = 'Sign In';
            } else if (button.dataset.originalText) {
                button.textContent = button.dataset.originalText;
            }
        }
    }
}

// CSS animations for notifications
if (!document.getElementById('login-animations-style')) {
    const style = document.createElement('style');
    style.id = 'login-animations-style';
    style.textContent = `
        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(100%);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes slideOutRight {
            from {
                opacity: 1;
                transform: translateX(0);
            }
            to {
                opacity: 0;
                transform: translateX(100%);
            }
        }

        .button-loading {
            cursor: wait !important;
        }
    `;
    document.head.appendChild(style);
}

// Initialize login form when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('loginForm')) {
        new LoginFormHandler();
    }
});
