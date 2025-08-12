/**
 * Registration Form Handler
 * Handles form validation and submission for user registration
 */

class RegistrationForm
{
    constructor()
    {
        this.form = null;
        this.submitBtn = null;
        this.privacyCheckbox = null;
        this.termsCheckbox = null;
        this.init();
    }

    init()
    {
        this.form = document.getElementById('registerForm');
        this.submitBtn = document.getElementById('submitBtn');
        this.privacyCheckbox = document.getElementById('accept_privacy');
        this.termsCheckbox = document.getElementById('accept_terms');

        if (!this.form || !this.submitBtn || !this.privacyCheckbox || !this.termsCheckbox) {
            return; // Elements not found, exit gracefully
        }

        this.bindEvents();
        this.updateSubmitButton(); // Initial check
    }

    bindEvents()
    {
        // Listen for changes on checkboxes
        this.privacyCheckbox.addEventListener('change', () => this.updateSubmitButton());
        this.termsCheckbox.addEventListener('change', () => this.updateSubmitButton());

        // Form submission handler
        this.form.addEventListener('submit', (e) => this.handleSubmit(e));

        // Real-time validation
        this.setupRealTimeValidation();
    }

    updateSubmitButton()
    {
        const privacyAccepted = this.privacyCheckbox.checked;
        const termsAccepted = this.termsCheckbox.checked;

        if (privacyAccepted && termsAccepted) {
            this.submitBtn.disabled = false;
            this.submitBtn.classList.remove('disabled');
        } else {
            this.submitBtn.disabled = true;
            this.submitBtn.classList.add('disabled');
        }
    }

    handleSubmit(e)
    {
        e.preventDefault();

        // Clear previous errors
        this.clearAllErrors();

        // Validate form before submission
        if (!this.validateForm()) {
            this.setLoadingState(false);
            return false;
        }

        // Show loading state
        this.setLoadingState(true);

        // Submit form via regular form submission
        this.submitForm();
    }

    validateForm()
    {
        const formData = new FormData(this.form);
        let isValid = true;

        // Check required fields
        const requiredFields = ['username', 'email', 'password', 'password_confirm'];
        requiredFields.forEach(fieldName => {
            const value = formData.get(fieldName);
            if (!value || value.trim() === '') {
                this.showFieldError(fieldName, 'This field is required');
                isValid = false;
            }
        });

        // Check password match
        const password = formData.get('password');
        const confirmPassword = formData.get('password_confirm');
        if (password && confirmPassword && password !== confirmPassword) {
            this.showFieldError('password_confirm', 'Passwords do not match');
            isValid = false;
        }

        // Check email format
        const email = formData.get('email');
        if (email && !this.isValidEmail(email)) {
            this.showFieldError('email', 'Please enter a valid email address');
            isValid = false;
        }

        // Check checkboxes
        if (!this.privacyCheckbox.checked) {
            this.showFieldError('accept_privacy', 'You must accept the privacy policy');
            isValid = false;
        }

        if (!this.termsCheckbox.checked) {
            this.showFieldError('accept_terms', 'You must accept the terms of service');
            isValid = false;
        }

        return isValid;
    }

    submitForm()
    {
        // Regular form submission
        this.form.submit();
    }

    setLoadingState(loading)
    {
        if (loading) {
            this.submitBtn.disabled = true;
            this.submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating Account...';
        } else {
            this.submitBtn.disabled = false;
            this.submitBtn.innerHTML = '<i class="fas fa-user-plus"></i> Create Account';
        }
    }

    setupRealTimeValidation()
    {
        const inputs = this.form.querySelectorAll('input[required]');
        inputs.forEach(input => {
            input.addEventListener('blur', () => this.validateField(input));
            input.addEventListener('input', () => this.clearFieldError(input.name));
        });
    }

    validateField(input)
    {
        const value = input.value.trim();

        if (!value && input.required) {
            this.showFieldError(input.name, 'This field is required');
            return false;
        }

        // Email validation
        if (input.type === 'email' && value && !this.isValidEmail(value)) {
            this.showFieldError(input.name, 'Please enter a valid email address');
            return false;
        }

        // Password confirmation validation
        if (input.name === 'password_confirm') {
            const password = document.getElementById('password').value;
            if (value && password && value !== password) {
                this.showFieldError(input.name, 'Passwords do not match');
                return false;
            }
        }

        this.clearFieldError(input.name);
        return true;
    }

    showFieldError(fieldName, message)
    {
        const field = document.querySelector(`[name="${fieldName}"]`);
        if (!field) return;

        // Remove existing error
        this.clearFieldError(fieldName);

        // Add error class to field
        field.classList.add('error');

        // Create error message element
        const errorDiv = document.createElement('div');
        errorDiv.className = 'field-error';
        errorDiv.textContent = message;
        errorDiv.setAttribute('data-field', fieldName);

        // Insert error message after the field's parent group
        const fieldGroup = field.closest('.form-group') || field.closest('.form-check');
        if (fieldGroup) {
            fieldGroup.appendChild(errorDiv);
        } else {
            field.parentNode.insertBefore(errorDiv, field.nextSibling);
        }
    }

    clearFieldError(fieldName)
    {
        const field = document.querySelector(`[name="${fieldName}"]`);
        if (field) {
            field.classList.remove('error');
        }

        // Remove error message
        const errorDiv = document.querySelector(`.field-error[data-field="${fieldName}"]`);
        if (errorDiv) {
            errorDiv.remove();
        }
    }

    clearAllErrors()
    {
        // Remove all error classes
        const errorFields = this.form.querySelectorAll('.error');
        errorFields.forEach(field => field.classList.remove('error'));

        // Remove all error messages
        const errorMessages = this.form.querySelectorAll('.field-error');
        errorMessages.forEach(msg => msg.remove());
    }

    isValidEmail(email)
    {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }
}

// Initialize the registration form when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    new RegistrationForm();
});
