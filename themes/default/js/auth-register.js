/**
 * Registration page specific functionality
 * Extends PasswordManager for registration-specific features
 */

class RegistrationFormHandler extends PasswordManager {
    constructor() {
        super();
        this.initRegistrationFeatures();
    }

    initRegistrationFeatures() {
        this.setupUsernameValidation();
        this.setupEmailValidation();
        this.setupLegalAgreements();
        this.setupRegistrationForm();
        this.setupSocialRegistration();
    }

    setupUsernameValidation() {
        const usernameInput = document.getElementById('username');
        const usernameValidation = document.getElementById('username_validation');

        if (!usernameInput || !usernameValidation) return;

        usernameInput.addEventListener('input', (e) => {
            this.validateUsername(e.target.value);
        });

        usernameInput.addEventListener('blur', (e) => {
            this.validateUsernameOnBlur(e.target.value);
        });
    }

    validateUsername(username) {
        const usernameValidation = document.getElementById('username_validation');
        const inputGroup = document.getElementById('username').closest('.input-group');

        if (!usernameValidation || !inputGroup) return;

        if (username.length === 0) {
            this.resetUsernameValidation();
            return;
        }

        // Check length
        if (username.length < 3) {
            this.showUsernameError('Username must be at least 3 characters');
            return;
        }

        if (username.length > 50) {
            this.showUsernameError('Username must be less than 50 characters');
            return;
        }

        // Check pattern
        if (!/^[a-zA-Z0-9_]+$/.test(username)) {
            this.showUsernameError('Username can only contain letters, numbers, and underscores');
            return;
        }

        // Check for reserved words
        const reserved = ['admin', 'root', 'user', 'test', 'guest', 'anonymous', 'system', 'null', 'undefined'];
        if (reserved.includes(username.toLowerCase())) {
            this.showUsernameError('This username is reserved');
            return;
        }

        // Show success state
        inputGroup.classList.add('valid');
        inputGroup.classList.remove('invalid');
        usernameValidation.textContent = '✓ Username looks good!';
        usernameValidation.style.color = '#10b981';
    }

    validateUsernameOnBlur(username) {
        if (username.trim()) {
            this.checkUsernameAvailability(username);
        }
    }

    async checkUsernameAvailability(username) {
        const usernameValidation = document.getElementById('username_validation');
        if (!usernameValidation) return;

        // Simulate API call (replace with actual endpoint)
        usernameValidation.textContent = '⏳ Checking availability...';
        usernameValidation.style.color = '#3b82f6';

        // Mock delay
        await new Promise(resolve => setTimeout(resolve, 1000));

        // Mock availability check (replace with actual API call)
        const isAvailable = Math.random() > 0.3; // 70% chance available

        if (isAvailable) {
            usernameValidation.textContent = '✓ Username is available!';
            usernameValidation.style.color = '#10b981';
        } else {
            this.showUsernameError('Username is already taken');
        }
    }

    showUsernameError(message) {
        const usernameValidation = document.getElementById('username_validation');
        const inputGroup = document.getElementById('username').closest('.input-group');

        if (inputGroup) {
            inputGroup.classList.add('invalid');
            inputGroup.classList.remove('valid');
        }

        if (usernameValidation) {
            usernameValidation.textContent = `✗ ${message}`;
            usernameValidation.style.color = '#ef4444';
        }
    }

    resetUsernameValidation() {
        const usernameValidation = document.getElementById('username_validation');
        const inputGroup = document.getElementById('username').closest('.input-group');

        if (inputGroup) {
            inputGroup.classList.remove('valid', 'invalid');
        }

        if (usernameValidation) {
            usernameValidation.textContent = 'Username must be 3-50 characters (letters, numbers, underscores only)';
            usernameValidation.style.color = '#64748b';
        }
    }

    setupEmailValidation() {
        const emailInput = document.getElementById('email');
        if (!emailInput) return;

        emailInput.addEventListener('blur', (e) => {
            if (e.target.value && this.validateEmail(e.target.value)) {
                this.checkEmailAvailability(e.target.value);
            }
        });
    }

    async checkEmailAvailability(email) {
        const emailHint = document.querySelector('.email-hint');
        if (!emailHint) return;

        const originalText = emailHint.textContent;
        emailHint.textContent = '⏳ Checking email availability...';
        emailHint.style.color = '#3b82f6';

        // Mock delay
        await new Promise(resolve => setTimeout(resolve, 800));

        // Mock availability check
        const isAvailable = Math.random() > 0.2; // 80% chance available

        if (isAvailable) {
            emailHint.textContent = '✓ Email is available for registration';
            emailHint.style.color = '#10b981';
        } else {
            emailHint.textContent = '❌ Email is already registered. Try logging in instead.';
            emailHint.style.color = '#ef4444';
        }

        // Reset after 3 seconds
        setTimeout(() => {
            emailHint.textContent = originalText;
            emailHint.style.color = '#64748b';
        }, 3000);
    }

    setupLegalAgreements() {
        const privacyCheckbox = document.getElementById('accept_privacy');
        const termsCheckbox = document.getElementById('accept_terms');

        [privacyCheckbox, termsCheckbox].forEach(checkbox => {
            if (checkbox) {
                checkbox.addEventListener('change', () => {
                    this.updateLegalState();
                    this.updateSubmitButton();
                });
            }
        });
    }

    updateLegalState() {
        const privacyCheckbox = document.getElementById('accept_privacy');
        const termsCheckbox = document.getElementById('accept_terms');

        const allChecked = privacyCheckbox?.checked && termsCheckbox?.checked;

        const legalSection = document.querySelector('.legal-agreements');
        if (legalSection) {
            if (allChecked) {
                legalSection.style.borderColor = 'rgba(16, 185, 129, 0.3)';
                legalSection.style.background = 'rgba(16, 185, 129, 0.05)';
            } else {
                legalSection.style.borderColor = 'rgba(139, 92, 246, 0.2)';
                legalSection.style.background = 'rgba(139, 92, 246, 0.05)';
            }
        }
    }

    setupRegistrationForm() {
        const form = document.getElementById('registerForm');
        if (!form) return;

        form.addEventListener('submit', (e) => {
            this.handleRegistrationSubmit(e);
        });
    }

    handleRegistrationSubmit(e) {
        const form = e.target;
        const submitBtn = document.getElementById('submitBtn');

        // Validate all fields
        if (!this.validateRegistrationForm(form)) {
            e.preventDefault();
            return;
        }

        // Set loading state
        this.setLoadingState(submitBtn, true);

        // The form will submit naturally
        // If there's an error, it will be handled by the server
    }

    validateRegistrationForm(form) {
        let isValid = true;
        const errors = [];

        // Username validation
        const username = form.querySelector('[name="username"]').value;
        if (username.length < 3 || username.length > 50 || !/^[a-zA-Z0-9_]+$/.test(username)) {
            errors.push('Invalid username format');
            isValid = false;
        }

        // Email validation
        const email = form.querySelector('[name="email"]').value;
        if (!this.validateEmail(email)) {
            errors.push('Invalid email address');
            isValid = false;
        }

        // Password validation
        const password = form.querySelector('[name="password"]').value;
        const passwordConfirm = form.querySelector('[name="password_confirm"]').value;

        if (password.length < 8) {
            errors.push('Password must be at least 8 characters');
            isValid = false;
        }

        if (password !== passwordConfirm) {
            errors.push('Passwords do not match');
            isValid = false;
        }

        // Legal agreements
        const acceptPrivacy = form.querySelector('[name="accept_privacy"]').checked;
        const acceptTerms = form.querySelector('[name="accept_terms"]').checked;

        if (!acceptPrivacy || !acceptTerms) {
            errors.push('You must accept the Privacy Policy and Terms of Service');
            isValid = false;
        }

        if (!isValid) {
            this.showValidationErrors(errors);
        }

        return isValid;
    }

    showValidationErrors(errors) {
        // Remove existing error notifications
        document.querySelectorAll('.registration-error-notification').forEach(el => el.remove());

        errors.forEach((error, index) => {
            setTimeout(() => {
                const notification = document.createElement('div');
                notification.className = 'registration-error-notification';
                notification.innerHTML = `
                    <div style="background: rgba(239, 68, 68, 0.9); color: white; padding: 0.75rem 1rem; border-radius: 0.5rem; font-size: 0.9rem; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3); backdrop-filter: blur(10px);">
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <span>❌</span>
                            <span>${error}</span>
                        </div>
                    </div>
                `;

                notification.style.position = 'fixed';
                notification.style.top = (20 + index * 60) + 'px';
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
            }, index * 200);
        });
    }

    setupSocialRegistration() {
        const socialButtons = document.querySelectorAll('.social-btn');

        socialButtons.forEach(button => {
            button.addEventListener('click', (e) => {
                e.preventDefault();

                const platform = button.classList.contains('social-btn--google') ? 'Google' :
                                 button.classList.contains('social-btn--meta') ? 'Meta' :
                                 button.classList.contains('social-btn--telegram') ? 'Telegram' :
                                 button.classList.contains('social-btn--microsoft') ? 'Microsoft' : 'Social';

                this.showSocialRegisterMessage(platform);
            });
        });
    }

    showSocialRegisterMessage(platform) {
        const notification = document.createElement('div');
        notification.className = 'social-register-notification';
        notification.innerHTML = `
            <div style="background: rgba(139, 92, 246, 0.9); color: white; padding: 0.75rem 1rem; border-radius: 0.5rem; font-size: 0.9rem; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3); backdrop-filter: blur(10px);">
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <span>🚧</span>
                    <span>${platform} registration coming soon!</span>
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
        }, 3000);
    }

    // Override updateSubmitButton to include legal agreements
    updateSubmitButton() {
        const submitBtn = document.getElementById('submitBtn');
        if (!submitBtn) return;

        const passwordInput = document.getElementById('password');
        const usernameInput = document.getElementById('username');
        const emailInput = document.getElementById('email');
        const privacyCheckbox = document.getElementById('accept_privacy');
        const termsCheckbox = document.getElementById('accept_terms');

        if (!passwordInput) return;

        const isPasswordStrong = this.checkPasswordStrength(passwordInput.value);
        const isPasswordMatch = this.checkPasswordMatch();
        const isUsernameValid = usernameInput && usernameInput.value.length >= 3 && 
                                /^[a-zA-Z0-9_]{3,50}$/.test(usernameInput.value);
        const isEmailValid = emailInput && this.validateEmail(emailInput.value);
        const isLegalAccepted = privacyCheckbox?.checked && termsCheckbox?.checked;

        if (isPasswordStrong && isPasswordMatch && isUsernameValid && isEmailValid && isLegalAccepted) {
            submitBtn.disabled = false;
            submitBtn.style.opacity = '1';
            submitBtn.style.cursor = 'pointer';
        } else {
            submitBtn.disabled = true;
            submitBtn.style.opacity = '0.6';
            submitBtn.style.cursor = 'not-allowed';
        }
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
                text.textContent = 'Creating Account...';
            } else {
                button.textContent = 'Creating Account...';
            }

            button.style.opacity = '0.7';
        } else {
            button.disabled = false;
            button.style.opacity = '1';

            const icon = button.querySelector('.button-icon');
            const text = button.querySelector('.button-text');

            if (icon) icon.textContent = '✨';
            if (text) {
                text.textContent = 'Create Account';
            } else if (button.dataset.originalText) {
                button.textContent = button.dataset.originalText;
            }
        }
    }
}

// Initialize registration form when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('registerForm')) {
        new RegistrationFormHandler();
    }
});
