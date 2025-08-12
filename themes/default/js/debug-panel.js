/**
 * Debug Panel Functionality
 * Handles debug panel interactions
 */

class DebugPanel {
    constructor() {
        this.init();
    }

    init() {
        this.bindEvents();
    }

    /**
     * Bind events to debug panel elements
     */
    bindEvents() {
        const closeButton = document.querySelector('.debug-panel__close');
        if (closeButton) {
            closeButton.addEventListener('click', () => {
                this.closePanel();
            });
        }

        // Allow closing with Escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.closePanel();
            }
        });
    }

    /**
     * Close the debug panel
     */
    closePanel() {
        const panel = document.querySelector('.debug-panel');
        if (panel) {
            panel.classList.add('hidden');
        }
    }

    /**
     * Show the debug panel
     */
    showPanel() {
        const panel = document.querySelector('.debug-panel');
        if (panel) {
            panel.classList.remove('hidden');
        }
    }

    /**
     * Toggle debug panel visibility
     */
    togglePanel() {
        const panel = document.querySelector('.debug-panel');
        if (panel) {
            panel.classList.toggle('hidden');
        }
    }
}

// Global functions
window.closeDebugPanel = function() {
    if (window.debugPanelInstance) {
        window.debugPanelInstance.closePanel();
    }
};

window.toggleDebugPanel = function() {
    if (window.debugPanelInstance) {
        window.debugPanelInstance.togglePanel();
    }
};

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    window.debugPanelInstance = new DebugPanel();
});
