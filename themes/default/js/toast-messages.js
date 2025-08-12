/**
 * Toast Messages Handler
 * Handles displaying toast messages from PHP flash messages
 */

class ToastMessagesHandler {
    constructor() {
        this.toastManager = null;
        this.init();
    }

    init() {
        // Wait for toast manager to be available
        this.waitForToastManager().then(() => {
            this.processPhpMessages();
        });
    }

    /**
     * Wait for ToastManager to be available
     * @returns {Promise}
     */
    waitForToastManager() {
        return new Promise((resolve) => {
            const checkManager = () => {
                if (window.toastManager || window.ToastManager) {
                    this.toastManager = window.toastManager || new window.ToastManager();
                    resolve();
                } else {
                    setTimeout(checkManager, 100);
                }
            };
            checkManager();
        });
    }

    /**
     * Process PHP flash messages and display as toasts
     */
    processPhpMessages() {
        // This will be populated by PHP via data attributes or global variable
        const messagesElement = document.querySelector('[data-php-messages]');
        if (!messagesElement) return;

        try {
            const messages = JSON.parse(messagesElement.dataset.phpMessages);
            this.displayMessages(messages);
        } catch (error) {
            console.warn('Failed to parse PHP messages:', error);
        }
    }

    /**
     * Display messages as toasts
     * @param {Object} messages - Messages object from PHP
     */
    displayMessages(messages) {
        if (!this.toastManager || !messages) return;

        Object.keys(messages).forEach(type => {
            messages[type].forEach(messageData => {
                const text = messageData.text || messageData;
                const isHtml = messageData.is_html || false;

                // Convert type to toast manager format
                const toastType = this.convertMessageType(type);

                // Show toast
                if (typeof this.toastManager.show === 'function') {
                    this.toastManager.show(text, toastType, {
                        duration: this.getDurationByType(toastType),
                        allowHtml: isHtml
                    });
                }
            });
        });
    }

    /**
     * Convert PHP message type to toast type
     * @param {string} phpType - PHP message type
     * @returns {string} Toast type
     */
    convertMessageType(phpType) {
        const typeMap = {
            'success': 'success',
            'error': 'error',
            'warning': 'warning',
            'info': 'info',
            'notice': 'info'
        };
        return typeMap[phpType] || 'info';
    }

    /**
     * Get duration based on message type
     * @param {string} type - Toast type
     * @returns {number} Duration in milliseconds
     */
    getDurationByType(type) {
        const durations = {
            'success': 4000,
            'error': 6000,
            'warning': 5000,
            'info': 4000
        };
        return durations[type] || 4000;
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.toastMessagesHandler = new ToastMessagesHandler();
});
