/**
 * Toast Notification System
 * Modern popup notifications with auto-hide functionality
 */

class ToastManager
{
    constructor()
    {
        this.container = null;
        this.toasts = new Set();
        this.defaultDuration = 5000; // 5 seconds
        this.init();
    }

    init()
    {
        // Create toast container if it doesn't exist
        if (!document.querySelector('.toast-container')) {
            this.container = document.createElement('div');
            this.container.className = 'toast-container';
            this.container.setAttribute('aria-live', 'polite');
            document.body.appendChild(this.container);
        } else {
            this.container = document.querySelector('.toast-container');
        }

        // Convert existing alerts to toasts
        this.convertExistingAlerts();

        // Set up MutationObserver to catch dynamically added alerts
        this.observeAlerts();
    }

    /**
     * Show a toast notification
     * @param {string} message - The message to display
     * @param {string} type - Type: 'success', 'error', 'warning', 'info'
     * @param {number} duration - Auto-hide duration in ms (0 = no auto-hide)
     */
    show(message, type = 'info', duration = this.defaultDuration)
    {
        const toast = this.createToast(message, type, duration);
        this.container.appendChild(toast);
        this.toasts.add(toast);

        // Trigger animation
        setTimeout(() => {
            toast.classList.add('toast--show');
        }, 10);

        // Auto-hide after duration
        if (duration > 0) {
            setTimeout(() => {
                this.hide(toast);
            }, duration);
        }

        // Limit number of toasts (max 5)
        if (this.toasts.size > 5) {
            const oldestToast = this.toasts.values().next().value;
            this.hide(oldestToast);
        }

        return toast;
    }

    /**
     * Create toast element
     */
    createToast(message, type, duration)
    {
        const toast = document.createElement('div');
        toast.className = `toast toast--${type}`;

        const content = document.createElement('div');
        content.className = 'toast__content';

        const icon = document.createElement('div');
        icon.className = 'toast__icon';
        // Remove innerHTML - let CSS handle icons via ::before pseudo-elements

        const messageEl = document.createElement('div');
        messageEl.className = 'toast__message';
        messageEl.textContent = message;

        content.appendChild(icon);
        content.appendChild(messageEl);
        toast.appendChild(content);

        // Add progress bar if duration > 0
        if (duration > 0) {
            const progress = document.createElement('div');
            progress.className = 'toast__progress';

            const progressBar = document.createElement('div');
            progressBar.className = 'toast__progress-bar';
            progressBar.style.animationDuration = `${duration}ms`;

            progress.appendChild(progressBar);
            toast.appendChild(progress);
        }

        return toast;
    }

    /**
     * Get icon for toast type
     */
    getIcon(type)
    {
        const icons = {
            success: '✓',
            error: '✕',
            warning: '⚠',
            info: 'ℹ'
        };
        return icons[type] || icons.info;
    }

    /**
     * Hide a toast
     */
    hide(toast)
    {
        if (!toast || !this.toasts.has(toast)) {
            return;
        }

        toast.classList.add('toast--removing');
        this.toasts.delete(toast);

        setTimeout(() => {
            if (toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
        }, 300);
    }

    /**
     * Hide all toasts
     */
    hideAll()
    {
        this.toasts.forEach(toast => this.hide(toast));
    }

    /**
     * Clean message text from existing icons and extra formatting
     */
    cleanMessage(message)
    {
        // Remove common icons that might be duplicated
        return message
            .replace(/^[✓✕⚠ℹ]\s*/, '') // Remove leading icons
            .replace(/^\s*[✓✕⚠ℹ]\s*/, '') // Remove leading icons with spaces
            .replace(/\s*[✓✕⚠ℹ]\s*$/, '') // Remove trailing icons
            .trim();
    }

    /**
     * Convert existing alert elements to toasts
     */
    convertExistingAlerts()
    {
        const alerts = document.querySelectorAll('.alert, .message--error, .message--success, .message--warning, .message--info');

        alerts.forEach(alert => {
            const rawMessage = alert.textContent.trim();
            if (!rawMessage) {
                return;
            }

            // Clean the message from existing icons
            const message = this.cleanMessage(rawMessage);

            let type = 'info';
            if (alert.classList.contains('alert-success') || alert.classList.contains('message--success')) {
                type = 'success';
            } else if (alert.classList.contains('alert-danger') || alert.classList.contains('alert-error') || alert.classList.contains('message--error')) {
                type = 'error';
            } else if (alert.classList.contains('alert-warning') || alert.classList.contains('message--warning')) {
                type = 'warning';
            }

            // Show toast
            this.show(message, type);

            // Hide original alert
            alert.style.display = 'none';
        });
    }

    /**
     * Set up observer for dynamically added alerts
     */
    observeAlerts()
    {
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                mutation.addedNodes.forEach((node) => {
                    if (node.nodeType === Node.ELEMENT_NODE) {
                        const alerts = node.querySelectorAll ?
                            node.querySelectorAll('.alert, .message--error, .message--success, .message--warning, .message--info') : [];

                        alerts.forEach(alert => {
                            const rawMessage = alert.textContent.trim();
                            if (!rawMessage) {
                                return;
                            }

                            // Clean the message from existing icons
                            const message = this.cleanMessage(rawMessage);

                            let type = 'info';
                            if (alert.classList.contains('alert-success') || alert.classList.contains('message--success')) {
                                type = 'success';
                            } else if (alert.classList.contains('alert-danger') || alert.classList.contains('alert-error') || alert.classList.contains('message--error')) {
                                type = 'error';
                            } else if (alert.classList.contains('alert-warning') || alert.classList.contains('message--warning')) {
                                type = 'warning';
                            }

                            this.show(message, type);
                            alert.style.display = 'none';
                        });
                    }
                });
            });
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }
}

// Create global instance
window.toastManager = new ToastManager();

// Expose as global for backwards compatibility
window.showToast = function (message, type = 'info', duration = 5000) {
    return window.toastManager.show(message, type, duration);
};
