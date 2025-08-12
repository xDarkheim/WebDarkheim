/**
 * TinyMCE Editor Initialization
 * Handles initialization of TinyMCE editor with custom configuration
 */

class TinyMCEInitializer {
    constructor() {
        this.editor = null;
        this.config = {
            apiKey: '',
            cdnUrl: '',
            editorHeight: 400,
            editorPreset: 'default'
        };
    }

    /**
     * Initialize TinyMCE with provided configuration
     * @param {Object} config - Editor configuration
     */
    init(config = {}) {
        this.config = { ...this.config, ...config };

        if (typeof DarkheimTinyMCE !== 'undefined') {
            this.editor = new DarkheimTinyMCE();
            this.editor.init(this.config);

            // Make editor globally accessible for debugging
            window.darkheimEditor = this.editor;
        } else {
            console.warn('DarkheimTinyMCE class not found. Make sure tinymce-init.js is loaded.');
        }
    }

    /**
     * Get the current editor instance
     * @returns {Object|null} Editor instance
     */
    getEditor() {
        return this.editor;
    }

    /**
     * Destroy the editor instance
     */
    destroy() {
        if (this.editor && typeof this.editor.destroy === 'function') {
            this.editor.destroy();
        }
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.tinyMCEInitializer = new TinyMCEInitializer();
});
