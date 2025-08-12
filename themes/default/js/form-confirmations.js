/**
 * Form Confirmation Handlers
 * Manages confirmation dialogs for destructive actions
 */

class FormConfirmations {
    constructor() {
        this.init();
    }

    init() {
        this.bindConfirmationEvents();
    }

    /**
     * Bind confirmation events to forms
     */
    bindConfirmationEvents() {
        // Handle forms with data-confirm attribute
        const confirmForms = document.querySelectorAll('form[data-confirm]');
        confirmForms.forEach(form => {
            form.addEventListener('submit', (e) => {
                const message = form.dataset.confirm;
                if (!confirm(message)) {
                    e.preventDefault();
                    return false;
                }
            });
        });

        // Handle buttons/links with data-confirm attribute
        const confirmElements = document.querySelectorAll('[data-confirm]:not(form)');
        confirmElements.forEach(element => {
            element.addEventListener('click', (e) => {
                const message = element.dataset.confirm;
                if (!confirm(message)) {
                    e.preventDefault();
                    return false;
                }
            });
        });

        // Handle delete buttons specifically
        const deleteButtons = document.querySelectorAll('.btn-delete, .admin-btn-delete');
        deleteButtons.forEach(button => {
            if (!button.hasAttribute('data-confirm')) {
                button.addEventListener('click', (e) => {
                    const defaultMessage = 'Are you sure you want to delete this item? This action cannot be undone.';
                    if (!confirm(defaultMessage)) {
                        e.preventDefault();
                        return false;
                    }
                });
            }
        });
    }

    /**
     * Show custom confirmation dialog
     * @param {string} message - Confirmation message
     * @param {Function} onConfirm - Callback for confirmation
     * @param {Function} onCancel - Callback for cancellation
     */
    showConfirmation(message, onConfirm, onCancel) {
        // For now, use native confirm dialog
        // TODO: Implement custom modal in future
        if (confirm(message)) {
            if (typeof onConfirm === 'function') {
                onConfirm();
            }
        } else {
            if (typeof onCancel === 'function') {
                onCancel();
            }
        }
    }

    /**
     * Quick delete confirmation for articles
     * @param {number|string} articleId - Article ID to delete
     */
    quickDeleteArticle(articleId) {
        const message = 'Are you sure you want to delete this article? This action cannot be undone.';
        this.showConfirmation(message, () => {
            // TODO: Implement actual delete functionality
            console.log('Deleting article:', articleId);

            // Example of future implementation:
            // this.deleteArticleFromServer(articleId);
        });
    }

    /**
     * Delete article from server (placeholder)
     * @param {number|string} articleId - Article ID
     */
    async deleteArticleFromServer(articleId) {
        try {
            // TODO: Implement actual API call
            const response = await fetch(`/api/articles/${articleId}`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                }
            });

            if (response.ok) {
                // Remove article from DOM or redirect
                window.location.reload();
            } else {
                throw new Error('Failed to delete article');
            }
        } catch (error) {
            console.error('Delete error:', error);
            alert('Failed to delete article. Please try again.');
        }
    }
}

// Global functions for backward compatibility
window.quickDeleteArticle = function(articleId) {
    if (window.formConfirmationsInstance) {
        window.formConfirmationsInstance.quickDeleteArticle(articleId);
    }
};

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    window.formConfirmationsInstance = new FormConfirmations();
});
