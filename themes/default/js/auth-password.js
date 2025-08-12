/**
 * Password functionality for authentication pages
 * Handles password strength, validation, and matching
 */

class PasswordManager extends AuthFormHandler {
    constructor() {
        super();
        this.initPasswordFeatures();
    }

    initPasswordFeatures() {
        this.setupPasswordStrength();
        this.setupPasswordMatching();
        this.setupPasswordRequirements();
    }

    // Password strength checker
    setupPasswordStrength() {
        const passwordInput = document.getElementById('new_password') || document.getElementById('password');
        if (!passwordInput) return;

        passwordInput.addEventListener('input', (e) => {
            this.checkPasswordStrength(e.target.value);
        });
    }

    checkPasswordStrength(password) {
        const strengthFill = document.getElementById('strength_fill');
        const strengthText = document.getElementById('strength_text');

        if (!strengthFill || !strengthText) return;

        let score = 0;
        let feedback = [];

        // Update requirements
        this.updateRequirement('req_length', password.length >= 8, 'At least 8 characters long');
        if (password.length >= 8) score += 25; else feedback.push('Use at least 8 characters');

        this.updateRequirement('req_letter', /[a-zA-Z]/.test(password), 'Contains letters (a-z)');
        if (/[a-zA-Z]/.test(password)) score += 25; else feedback.push('Include letters');

        this.updateRequirement('req_number', /\d/.test(password), 'Contains at least one number');
        if (/\d/.test(password)) score += 25; else feedback.push('Include numbers');

        this.updateRequirement('req_special', /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(password), 'Contains special characters (!@#$%^&*)');
        if (/[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(password)) score += 25; else feedback.push('Include special characters');

        // Update strength bar
        strengthFill.style.width = score + '%';

        const strengthInfo = this.getStrengthInfo(score);
        strengthFill.style.backgroundColor = strengthInfo.color;
        strengthText.textContent = strengthInfo.text;
        strengthText.style.color = strengthInfo.color;

        return score >= 75;
    }

    getStrengthInfo(score) {
        if (score < 50) {
            return { color: '#ef4444', text: 'Weak password' };
        } else if (score < 75) {
            return { color: '#f59e0b', text: 'Fair password' };
        } else if (score < 100) {
            return { color: '#3b82f6', text: 'Good password' };
        } else {
            return { color: '#10b981', text: 'Strong password' };
        }
    }

    updateRequirement(reqId, met, text) {
        const element = document.getElementById(reqId);
        if (!element) return;

        if (met) {
            element.style.color = '#10b981';
            element.innerHTML = `✓ ${text}`;
        } else {
            element.style.color = '#ef4444';
            element.innerHTML = `✗ ${text}`;
        }
    }

    // Password matching
    setupPasswordMatching() {
        const newPasswordInput = document.getElementById('new_password') || document.getElementById('password');
        const confirmPasswordInput = document.getElementById('confirm_password') || document.getElementById('password_confirm');

        if (!newPasswordInput || !confirmPasswordInput) return;

        [newPasswordInput, confirmPasswordInput].forEach(input => {
            input.addEventListener('input', () => {
                this.checkPasswordMatch();
                this.updateSubmitButton();
            });
        });
    }

    checkPasswordMatch() {
        const newPasswordInput = document.getElementById('new_password') || document.getElementById('password');
        const confirmPasswordInput = document.getElementById('confirm_password') || document.getElementById('password_confirm');
        const passwordMatch = document.getElementById('password_match');

        if (!newPasswordInput || !confirmPasswordInput || !passwordMatch) return false;

        const password = newPasswordInput.value;
        const confirm = confirmPasswordInput.value;

        if (confirm === '') {
            passwordMatch.textContent = 'Passwords will be compared here';
            passwordMatch.style.color = '#94a3b8';
            return false;
        } else if (password === confirm) {
            passwordMatch.textContent = '✓ Passwords match';
            passwordMatch.style.color = '#10b981';
            return true;
        } else {
            passwordMatch.textContent = '✗ Passwords do not match';
            passwordMatch.style.color = '#ef4444';
            return false;
        }
    }

    // Password requirements setup
    setupPasswordRequirements() {
        // Requirements are handled in checkPasswordStrength
    }

    // Update submit button based on validation
    updateSubmitButton() {
        const submitBtn = document.getElementById('submitBtn');
        if (!submitBtn) return;

        const newPasswordInput = document.getElementById('new_password') || document.getElementById('password');
        if (!newPasswordInput) return;

        const isStrong = this.checkPasswordStrength(newPasswordInput.value);
        const isMatch = this.checkPasswordMatch();

        if (isStrong && isMatch && newPasswordInput.value.length > 0) {
            submitBtn.disabled = false;
            submitBtn.style.opacity = '1';
            submitBtn.style.cursor = 'pointer';
        } else {
            submitBtn.disabled = true;
            submitBtn.style.opacity = '0.6';
            submitBtn.style.cursor = 'not-allowed';
        }
    }
}

// Initialize for password pages
document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('new_password') || 
        (document.getElementById('password') && document.getElementById('password_confirm'))) {
        new PasswordManager();
    }
});
