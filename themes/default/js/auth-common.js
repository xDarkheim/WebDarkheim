/**
 * Common Auth JavaScript Functionality
 * Used across all authentication pages
 */

class AuthFormHandler {
    constructor() {
        this.init();
    }

    init() {
        this.setupFormValidation();
        this.setupPasswordToggles();
        this.setupFormSubmissions();
        this.setupEmailValidation();
    }

    // Password visibility toggle
    togglePassword(inputId) {
        const input = document.getElementById(inputId);
        const toggle = document.getElementById(inputId + '_toggle');

        if (!input || !toggle) return;

        if (input.type === 'password') {
            input.type = 'text';
            toggle.textContent = '🙈';
            toggle.setAttribute('aria-label', 'Hide password');
        } else {
            input.type = 'password';
            toggle.textContent = '👁️';
            toggle.setAttribute('aria-label', 'Show password');
        }
    }

    // Setup password toggles
    setupPasswordToggles() {
        window.togglePassword = this.togglePassword.bind(this);
    }

    // Email validation with visual feedback
    validateEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    // Setup email validation
    setupEmailValidation() {
        const emailInputs = document.querySelectorAll('input[type="email"]');

        emailInputs.forEach(input => {
            input.addEventListener('input', (e) => {
                this.handleEmailInput(e.target);
            });

            input.addEventListener('blur', (e) => {
                this.handleEmailBlur(e.target);
            });
        });
    }

    handleEmailInput(input) {
        const isValid = this.validateEmail(input.value);
        const inputGroup = input.closest('.input-group');

        if (input.value.length > 0) {
            if (isValid) {
                inputGroup?.classList.add('valid');
                inputGroup?.classList.remove('invalid');
            } else {
                inputGroup?.classList.add('invalid');
                inputGroup?.classList.remove('valid');
            }
        } else {
            inputGroup?.classList.remove('valid', 'invalid');
        }
    }

    handleEmailBlur(input) {
        if (input.value && !this.validateEmail(input.value)) {
            this.showFieldError(input, 'Please enter a valid email address');
        } else {
            this.clearFieldError(input);
        }
    }

    // Form validation
    setupFormValidation() {
        const forms = document.querySelectorAll('form[id$="Form"]');

        forms.forEach(form => {
            form.addEventListener('submit', (e) => {
                if (!this.validateForm(form)) {
                    e.preventDefault();
                }
            });
        });
    }

    validateForm(form) {
        const requiredFields = form.querySelectorAll('[required]');
        let isValid = true;

        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                this.showFieldError(field, 'This field is required');
                isValid = false;
            } else {
                this.clearFieldError(field);
            }
        });

        return isValid;
    }

    // Form submission with loading states
    setupFormSubmissions() {
        const forms = document.querySelectorAll('form[id$="Form"]');

        forms.forEach(form => {
            form.addEventListener('submit', (e) => {
                const submitBtn = form.querySelector('[type="submit"]');
                if (submitBtn) {
                    this.setLoadingState(submitBtn, true);
                }
            });
        });
    }

    setLoadingState(button, isLoading) {
        if (isLoading) {
            button.disabled = true;
            button.dataset.originalText = button.textContent;

            const icon = button.querySelector('.button-icon');
            const text = button.querySelector('.button-text');

            if (icon) icon.textContent = '⏳';
            if (text) {
                text.textContent = 'Processing...';
            } else {
                button.textContent = 'Processing...';
            }

            button.style.opacity = '0.7';
        } else {
            button.disabled = false;
            button.style.opacity = '1';

            if (button.dataset.originalText) {
                button.textContent = button.dataset.originalText;
            }
        }
    }

    // Field error handling
    showFieldError(field, message) {
        this.clearFieldError(field);

        const errorDiv = document.createElement('div');
        errorDiv.className = 'field-error';
        errorDiv.textContent = message;

        const inputGroup = field.closest('.input-group') || field.closest('.form-group');
        if (inputGroup) {
            inputGroup.appendChild(errorDiv);
            inputGroup.classList.add('has-error');
        }
    }

    clearFieldError(field) {
        const inputGroup = field.closest('.input-group') || field.closest('.form-group');
        if (inputGroup) {
            const existingError = inputGroup.querySelector('.field-error');
            if (existingError) {
                existingError.remove();
            }
            inputGroup.classList.remove('has-error');
        }
    }

    // Utility: Add floating animation to icons
    addFloatingAnimation(selector) {
        const elements = document.querySelectorAll(selector);
        elements.forEach(element => {
            element.style.animation = 'iconFloat 2s ease-in-out infinite';
        });
    }

    // Utility: Create status indicator
    createStatusIndicator(type, message, container) {
        const indicator = document.createElement('div');
        indicator.className = `status-indicator status-indicator--${type}`;
        indicator.innerHTML = `
            <div class="status-icon">
                ${type === 'success' ? '✅' : type === 'error' ? '❌' : 'ℹ️'}
            </div>
            <div class="status-message">${message}</div>
        `;

        if (container) {
            container.appendChild(indicator);
        }

        return indicator;
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    new AuthFormHandler();
});

// Export for other scripts
window.AuthFormHandler = AuthFormHandler;
