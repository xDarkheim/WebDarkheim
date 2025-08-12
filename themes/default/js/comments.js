/**
 * Comments Management JavaScript
 * Handles comment editing functionality
 */

class CommentsManager {
    constructor() {
        this.initEventListeners();
    }

    /**
     * Initialize event listeners for comment functionality
     */
    initEventListeners() {
        // Edit comment buttons
        document.addEventListener('click', (e) => {
            if (e.target.matches('.comment-edit-btn, .comment-edit-btn *')) {
                const button = e.target.closest('.comment-edit-btn');
                if (button) {
                    e.preventDefault();
                    this.showEditForm(button);
                }
            }
        });

        // Cancel edit buttons
        document.addEventListener('click', (e) => {
            if (e.target.matches('.cancel-edit-btn, .cancel-edit-btn *')) {
                const button = e.target.closest('.cancel-edit-btn');
                if (button) {
                    e.preventDefault();
                    this.hideEditForm(button);
                }
            }
        });

        // Handle edit form submissions
        document.addEventListener('submit', (e) => {
            if (e.target.matches('.edit-comment-form')) {
                this.handleEditSubmit(e);
            }
        });
    }

    /**
     * Show edit form for a comment
     */
    showEditForm(editButton) {
        const commentId = editButton.getAttribute('data-comment-id');
        const commentContent = editButton.getAttribute('data-comment-content');

        if (!commentId) {
            console.error('Comment ID not found');
            return;
        }

        // Hide the comment content
        const contentElement = document.getElementById(`comment-content-${commentId}`);
        if (contentElement) {
            contentElement.style.display = 'none';
        }

        // Show the edit form
        const editForm = document.getElementById(`edit-form-${commentId}`);
        if (editForm) {
            editForm.style.display = 'block';

            // Initialize TinyMCE for the edit form if available
            const textareaId = `edit_comment_text_${commentId}`;
            this.initEditorForEditForm(textareaId, commentContent);
        }

        // Hide the edit button temporarily
        editButton.style.display = 'none';
    }

    /**
     * Hide edit form for a comment
     */
    hideEditForm(cancelButton) {
        const commentId = cancelButton.getAttribute('data-comment-id');

        if (!commentId) {
            console.error('Comment ID not found');
            return;
        }

        // Show the comment content
        const contentElement = document.getElementById(`comment-content-${commentId}`);
        if (contentElement) {
            contentElement.style.display = 'block';
        }

        // Hide the edit form
        const editForm = document.getElementById(`edit-form-${commentId}`);
        if (editForm) {
            editForm.style.display = 'none';

            // Remove TinyMCE instance if it exists
            const textareaId = `edit_comment_text_${commentId}`;
            this.destroyEditor(textareaId);
        }

        // Show the edit button again
        const editButton = document.querySelector(`[data-comment-id="${commentId}"].comment-edit-btn`);
        if (editButton) {
            editButton.style.display = 'inline-block';
        }
    }

    /**
     * Handle edit form submission
     */
    handleEditSubmit(event) {
        const form = event.target;
        const commentId = form.querySelector('input[name="comment_id"]').value;
        const textareaId = `edit_comment_text_${commentId}`;

        // Update textarea content from TinyMCE if available
        if (window.tinymce && tinymce.get(textareaId)) {
            const editor = tinymce.get(textareaId);
            const content = editor.getContent();

            // Validate content
            if (!content.trim()) {
                event.preventDefault();
                alert('Comment content cannot be empty');
                return false;
            }

            // Set the content to the textarea
            const textarea = document.getElementById(textareaId);
            if (textarea) {
                textarea.value = content;
            }
        }

        // Form will submit normally
        return true;
    }

    /**
     * Initialize TinyMCE editor for edit form
     */
    initEditorForEditForm(textareaId, content) {
        // Wait a bit for the form to be visible
        setTimeout(() => {
            if (window.tinymce && window.tinyMCEInitializer) {
                // Check if editor already exists
                if (tinymce.get(textareaId)) {
                    tinymce.get(textareaId).remove();
                }

                // Initialize new editor instance
                tinymce.init({
                    selector: `#${textareaId}`,
                    height: 150,
                    menubar: false,
                    statusbar: false,
                    plugins: [
                        'advlist', 'autolink', 'lists', 'link', 'charmap',
                        'searchreplace', 'visualblocks', 'code', 'fullscreen',
                        'insertdatetime', 'table', 'wordcount'
                    ],
                    toolbar: 'undo redo | formatselect | bold italic underline | alignleft aligncenter alignright | bullist numlist | link | code',
                    content_style: `
                        body { 
                            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif; 
                            font-size: 14px; 
                            line-height: 1.6; 
                            color: #333; 
                        }
                    `,
                    skin: 'oxide-dark',
                    content_css: 'dark',
                    setup: function(editor) {
                        editor.on('init', function() {
                            // Set initial content
                            editor.setContent(content || '');
                        });
                    }
                });
            } else {
                // Fallback: set content directly to textarea
                const textarea = document.getElementById(textareaId);
                if (textarea && content) {
                    textarea.value = content;
                }
            }
        }, 100);
    }

    /**
     * Destroy TinyMCE editor instance
     */
    destroyEditor(textareaId) {
        if (window.tinymce && tinymce.get(textareaId)) {
            tinymce.get(textareaId).remove();
        }
    }
}

// Initialize comments manager when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    window.commentsManager = new CommentsManager();
});
