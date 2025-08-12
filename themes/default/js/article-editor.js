/**
 * Article Editor - Content Management JavaScript
 * Provides advanced editing features for article creation
 */

class ArticleEditor {
    constructor() {
        this.editor = document.getElementById('full_text');
        this.previewPane = document.getElementById('previewPane');
        this.livePreview = document.getElementById('livePreview');
        this.currentMode = 'write';
        this.focusMode = false;
        this.autoSaveInterval = null;
        this.wordCountInterval = null;
        this.writingStartTime = Date.now();

        this.init();
    }

    init() {
        this.setupEventListeners();
        this.initializeCounters();
        this.initializeProgressTracking();
        this.initializeTips();
        this.setupAutoSave();
        this.updatePreview();
        this.updateWritingStats();

        // Start word count updates
        this.wordCountInterval = setInterval(() => {
            this.updateWritingStats();
        }, 1000);
    }

    setupEventListeners() {
        // Editor mode toggle
        document.querySelectorAll('.btn-mode').forEach(btn => {
            btn.addEventListener('click', (e) => {
                this.switchMode(e.target.closest('.btn-mode').dataset.mode);
            });
        });

        // Live preview updates
        if (this.editor) {
            this.editor.addEventListener('input', () => {
                this.updatePreview();
                this.updateCounters();
                this.updateProgress();
            });
        }

        // Form field updates
        document.querySelectorAll('#title, #short_description, #date').forEach(field => {
            field.addEventListener('input', () => {
                this.updatePreview();
                this.updateProgress();
            });
        });

        // Category selection
        document.querySelectorAll('.category-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', () => {
                this.updateSelectedCategories();
                this.updatePreview();
                this.updateProgress();
            });
        });

        // Category search
        const categorySearch = document.getElementById('categorySearch');
        if (categorySearch) {
            categorySearch.addEventListener('input', (e) => {
                this.filterCategories(e.target.value);
            });
        }

        // Keyboard shortcuts
        document.addEventListener('keydown', (e) => {
            this.handleKeyboardShortcuts(e);
        });

        // Font size controls
        document.addEventListener('click', (e) => {
            if (e.target.closest('[onclick*="changeFontSize"]')) {
                const direction = e.target.closest('button').getAttribute('onclick').includes('-1') ? -1 : 1;
                this.changeFontSize(direction);
            }
        });
    }

    // Formatting functions
    insertFormatting(before, after) {
        if (!this.editor) return;

        const start = this.editor.selectionStart;
        const end = this.editor.selectionEnd;
        const selectedText = this.editor.value.substring(start, end);
        const replacement = before + selectedText + after;

        this.editor.value = this.editor.value.substring(0, start) + replacement + this.editor.value.substring(end);

        // Set cursor position
        const newCursorPos = start + before.length + selectedText.length + after.length;
        this.editor.setSelectionRange(newCursorPos, newCursorPos);
        this.editor.focus();

        this.updatePreview();
        this.updateCounters();
    }

    insertTable() {
        const tableMarkdown = '\n| Header 1 | Header 2 | Header 3 |\n|----------|----------|----------|\n| Cell 1   | Cell 2   | Cell 3   |\n| Cell 4   | Cell 5   | Cell 6   |\n';
        this.insertFormatting(tableMarkdown, '');
    }

    // Mode switching
    switchMode(mode) {
        const editorPane = document.getElementById('editorPane');
        const previewPane = document.getElementById('previewPane');
        const editorLayout = document.getElementById('editorLayout');

        // Update button states
        document.querySelectorAll('.btn-mode').forEach(btn => {
            btn.classList.remove('active');
        });
        document.querySelector(`[data-mode="${mode}"]`).classList.add('active');

        this.currentMode = mode;

        switch(mode) {
            case 'write':
                editorPane.style.display = 'block';
                previewPane.style.display = 'none';
                editorLayout.classList.remove('split-view');
                break;
            case 'split':
                editorPane.style.display = 'block';
                previewPane.style.display = 'block';
                editorLayout.classList.add('split-view');
                this.updatePreview();
                break;
            case 'preview':
                editorPane.style.display = 'none';
                previewPane.style.display = 'block';
                editorLayout.classList.remove('split-view');
                this.updatePreview();
                break;
        }
    }

    // Preview functionality
    updatePreview() {
        if (!this.livePreview || !this.editor) return;

        const content = this.editor.value;
        let html = this.markdownToHtml(content);

        if (!html.trim()) {
            html = '<p class="preview-placeholder">Start typing to see live preview...</p>';
        }

        this.livePreview.innerHTML = html;
        this.updateArticlePreview();
    }

    markdownToHtml(markdown) {
        // Simple markdown parser - in production, consider using a library like marked.js
        let html = markdown
            // Headers
            .replace(/^### (.*$)/gim, '<h3>$1</h3>')
            .replace(/^## (.*$)/gim, '<h2>$1</h2>')
            .replace(/^# (.*$)/gim, '<h1>$1</h1>')
            // Bold
            .replace(/\*\*(.*)\*\*/gim, '<strong>$1</strong>')
            // Italic
            .replace(/\*(.*)\*/gim, '<em>$1</em>')
            // Strikethrough
            .replace(/~~(.*)~~/gim, '<del>$1</del>')
            // Code
            .replace(/`([^`]*)`/gim, '<code>$1</code>')
            // Links
            .replace(/\[([^\]]*)\]\(([^\)]*)\)/gim, '<a href="$2">$1</a>')
            // Line breaks
            .replace(/\n/gim, '<br>');

        return html;
    }

    updateArticlePreview() {
        const title = document.getElementById('title')?.value || 'Your article title will appear here';
        const date = document.getElementById('date')?.value || 'Publication date';
        const description = document.getElementById('short_description')?.value || 'Article preview will appear here when you add a short description';

        // Update preview elements
        const previewTitle = document.getElementById('previewTitle');
        const previewDate = document.getElementById('previewDate');
        const previewDescription = document.getElementById('previewDescription');
        const previewCategories = document.getElementById('previewCategories');
        const previewReadTime = document.getElementById('previewReadTime');

        if (previewTitle) previewTitle.textContent = title;
        if (previewDate) previewDate.textContent = new Date(date).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
        if (previewDescription) previewDescription.textContent = description;
        if (previewReadTime) previewReadTime.textContent = this.calculateReadingTime() + ' min read';

        // Update categories
        if (previewCategories) {
            const selectedCategories = Array.from(document.querySelectorAll('.category-checkbox:checked'))
                .map(cb => cb.closest('.category-option').querySelector('.category-name').textContent);
            previewCategories.textContent = selectedCategories.length > 0 ? selectedCategories.join(', ') : 'No categories';
        }
    }

    // Counters and statistics
    updateCounters() {
        this.updateCharacterCounters();
        this.updateWritingStats();
    }

    updateCharacterCounters() {
        const titleInput = document.getElementById('title');
        const descriptionInput = document.getElementById('short_description');

        if (titleInput) {
            const titleCounter = document.getElementById('titleCounter');
            if (titleCounter) {
                titleCounter.textContent = titleInput.value.length;
            }
        }

        if (descriptionInput) {
            const descriptionCounter = document.getElementById('descriptionCounter');
            if (descriptionCounter) {
                descriptionCounter.textContent = descriptionInput.value.length;
            }
        }
    }

    updateWritingStats() {
        if (!this.editor) return;

        const text = this.editor.value;
        const words = this.countWords(text);
        const characters = text.length;
        const paragraphs = text.split('\n\n').filter(p => p.trim().length > 0).length;
        const readingTime = this.calculateReadingTime();
        const writingTime = Math.floor((Date.now() - this.writingStartTime) / 60000);

        // Update main stats
        const wordCount = document.getElementById('wordCount');
        const readingTimeEl = document.getElementById('readingTime');
        const characterCount = document.getElementById('characterCount');
        const paragraphCount = document.getElementById('paragraphCount');

        if (wordCount) wordCount.textContent = words + ' words';
        if (readingTimeEl) readingTimeEl.textContent = '~' + readingTime + ' min read';
        if (characterCount) characterCount.textContent = characters + ' characters';
        if (paragraphCount) paragraphCount.textContent = paragraphs + ' paragraphs';

        // Update sidebar stats
        const sidebarWordCount = document.getElementById('sidebarWordCount');
        const sidebarReadTime = document.getElementById('sidebarReadTime');
        const writingTimeEl = document.getElementById('writingTime');

        if (sidebarWordCount) sidebarWordCount.textContent = words;
        if (sidebarReadTime) sidebarReadTime.textContent = readingTime;
        if (writingTimeEl) writingTimeEl.textContent = writingTime;
    }

    countWords(text) {
        return text.trim().split(/\s+/).filter(word => word.length > 0).length;
    }

    calculateReadingTime() {
        const words = this.countWords(this.editor ? this.editor.value : '');
        return Math.max(1, Math.ceil(words / 200)); // Average reading speed: 200 words per minute
    }

    // Progress tracking
    initializeProgressTracking() {
        this.updateProgress();
    }

    updateProgress() {
        const title = document.getElementById('title')?.value.trim() || '';
        const content = document.getElementById('full_text')?.value.trim() || '';
        const description = document.getElementById('short_description')?.value.trim() || '';
        const categories = document.querySelectorAll('.category-checkbox:checked').length;

        const checks = [
            { target: 'title', completed: title.length > 0 },
            { target: 'full_text', completed: content.length > 0 },
            { target: 'short_description', completed: description.length > 0 },
            { target: 'categories', completed: categories > 0 }
        ];

        let completedCount = 0;

        checks.forEach(check => {
            const item = document.querySelector(`[data-target="${check.target}"]`);
            if (item) {
                const icon = item.querySelector('.progress-icon');
                if (check.completed) {
                    icon.className = 'fas fa-check-circle progress-icon completed';
                    completedCount++;
                } else {
                    icon.className = 'fas fa-circle progress-icon';
                }
            }
        });

        const percentage = Math.round((completedCount / checks.length) * 100);
        const progressFill = document.querySelector('.progress-fill');
        const progressPercentage = document.getElementById('progressPercentage');

        if (progressFill) {
            progressFill.style.width = percentage + '%';
        }
        if (progressPercentage) {
            progressPercentage.textContent = percentage + '%';
        }
    }

    // Category management
    updateSelectedCategories() {
        const selectedCategories = Array.from(document.querySelectorAll('.category-checkbox:checked'))
            .map(cb => cb.closest('.category-option').querySelector('.category-name').textContent);

        const selectedCategoriesText = document.getElementById('selectedCategoriesText');
        if (selectedCategoriesText) {
            selectedCategoriesText.textContent = selectedCategories.length > 0 ? selectedCategories.join(', ') : 'None';
        }
    }

    filterCategories(searchTerm) {
        const categories = document.querySelectorAll('.category-option');
        const term = searchTerm.toLowerCase();

        categories.forEach(category => {
            const name = category.dataset.category;
            if (name.includes(term)) {
                category.style.display = 'block';
            } else {
                category.style.display = 'none';
            }
        });
    }

    // Auto-save functionality
    setupAutoSave() {
        this.autoSaveInterval = setInterval(() => {
            this.autoSave();
        }, 30000); // Auto-save every 30 seconds
    }

    autoSave() {
        const formData = new FormData();
        formData.append('action', 'auto_save');
        formData.append('title', document.getElementById('title')?.value || '');
        formData.append('short_description', document.getElementById('short_description')?.value || '');
        formData.append('full_text', document.getElementById('full_text')?.value || '');
        formData.append('csrf_token', document.querySelector('input[name="csrf_token"]')?.value || '');

        // Show auto-save indicator
        const autoSaveStatus = document.getElementById('autoSaveStatus');
        if (autoSaveStatus) {
            autoSaveStatus.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            autoSaveStatus.title = 'Saving...';
        }

        // In a real implementation, you would send this to the server
        // For now, we'll simulate the save and store in localStorage
        localStorage.setItem('article_draft', JSON.stringify({
            title: document.getElementById('title')?.value || '',
            short_description: document.getElementById('short_description')?.value || '',
            full_text: document.getElementById('full_text')?.value || '',
            timestamp: Date.now()
        }));

        // Update auto-save status
        setTimeout(() => {
            if (autoSaveStatus) {
                autoSaveStatus.innerHTML = '<i class="fas fa-check"></i>';
                autoSaveStatus.title = 'Auto-saved';
            }
        }, 1000);
    }

    saveDraftManually() {
        this.autoSave();
        // Show success message
        this.showNotification('Draft saved successfully!', 'success');
    }

    // Focus mode
    focusMode() {
        const overlay = document.getElementById('focusOverlay');
        const focusEditor = document.getElementById('focusEditor');

        if (overlay && focusEditor) {
            this.focusMode = true;
            overlay.style.display = 'flex';
            focusEditor.value = this.editor ? this.editor.value : '';
            focusEditor.focus();

            // Update focus stats
            this.updateFocusStats();

            // Set up focus editor updates
            focusEditor.addEventListener('input', () => {
                if (this.editor) {
                    this.editor.value = focusEditor.value;
                    this.updatePreview();
                    this.updateCounters();
                }
                this.updateFocusStats();
            });
        }
    }

    exitFocusMode() {
        const overlay = document.getElementById('focusOverlay');
        if (overlay) {
            this.focusMode = false;
            overlay.style.display = 'none';
        }
    }

    updateFocusStats() {
        const focusEditor = document.getElementById('focusEditor');
        if (!focusEditor) return;

        const words = this.countWords(focusEditor.value);
        const time = Math.floor((Date.now() - this.writingStartTime) / 60000);

        const focusWordCount = document.getElementById('focusWordCount');
        const focusTime = document.getElementById('focusTime');

        if (focusWordCount) focusWordCount.textContent = words + ' words';
        if (focusTime) focusTime.textContent = time + ' min';
    }

    // Font size control
    changeFontSize(direction) {
        if (!this.editor) return;

        const currentSize = parseInt(getComputedStyle(this.editor).fontSize);
        const newSize = Math.max(10, Math.min(24, currentSize + direction));

        this.editor.style.fontSize = newSize + 'px';

        const fontSizeDisplay = document.querySelector('.font-size-display');
        if (fontSizeDisplay) {
            fontSizeDisplay.textContent = newSize + 'px';
        }
    }

    // Tips carousel
    initializeTips() {
        this.currentTip = 0;
        this.tips = document.querySelectorAll('.tip-item');
        this.setupTipNavigation();
    }

    setupTipNavigation() {
        // Auto-rotate tips every 15 seconds
        setInterval(() => {
            this.nextTip();
        }, 15000);
    }

    nextTip() {
        if (this.tips.length === 0) return;

        this.tips[this.currentTip].classList.remove('active');
        this.currentTip = (this.currentTip + 1) % this.tips.length;
        this.tips[this.currentTip].classList.add('active');
    }

    previousTip() {
        if (this.tips.length === 0) return;

        this.tips[this.currentTip].classList.remove('active');
        this.currentTip = (this.currentTip - 1 + this.tips.length) % this.tips.length;
        this.tips[this.currentTip].classList.add('active');
    }

    // Keyboard shortcuts
    handleKeyboardShortcuts(e) {
        if (e.ctrlKey || e.metaKey) {
            switch(e.key) {
                case 's':
                    e.preventDefault();
                    this.saveDraftManually();
                    break;
                case 'Enter':
                    e.preventDefault();
                    document.querySelector('button[type="submit"]')?.click();
                    break;
                case 'b':
                    e.preventDefault();
                    this.insertFormatting('**', '**');
                    break;
                case 'i':
                    e.preventDefault();
                    this.insertFormatting('*', '*');
                    break;
            }
        }

        if (e.key === 'F11') {
            e.preventDefault();
            this.focusMode();
        }

        if (e.key === 'Escape' && this.focusMode) {
            this.exitFocusMode();
        }
    }

    // Utility functions
    showNotification(message, type = 'info') {
        // Simple notification system
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.textContent = message;

        document.body.appendChild(notification);

        setTimeout(() => {
            notification.classList.add('show');
        }, 100);

        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => {
                document.body.removeChild(notification);
            }, 300);
        }, 3000);
    }

    // Cleanup
    destroy() {
        if (this.autoSaveInterval) {
            clearInterval(this.autoSaveInterval);
        }
        if (this.wordCountInterval) {
            clearInterval(this.wordCountInterval);
        }
    }
}

// Global functions for backwards compatibility
function insertFormatting(before, after) {
    if (window.articleEditor) {
        window.articleEditor.insertFormatting(before, after);
    }
}

function insertTable() {
    if (window.articleEditor) {
        window.articleEditor.insertTable();
    }
}

function focusMode() {
    if (window.articleEditor) {
        window.articleEditor.focusMode();
    }
}

function exitFocusMode() {
    if (window.articleEditor) {
        window.articleEditor.exitFocusMode();
    }
}

function toggleFullscreen() {
    if (window.articleEditor) {
        window.articleEditor.switchMode('preview');
    }
}

function saveDraftManually() {
    if (window.articleEditor) {
        window.articleEditor.saveDraftManually();
    }
}

function changeFontSize(direction) {
    if (window.articleEditor) {
        window.articleEditor.changeFontSize(direction);
    }
}

function nextTip() {
    if (window.articleEditor) {
        window.articleEditor.nextTip();
    }
}

function previousTip() {
    if (window.articleEditor) {
        window.articleEditor.previousTip();
    }
}

function toggleHelp() {
    // Toggle help functionality
    const helpElements = document.querySelectorAll('.form-help-text');
    const isVisible = helpElements[0]?.style.display !== 'none';

    helpElements.forEach(el => {
        el.style.display = isVisible ? 'none' : 'block';
    });
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('full_text')) {
        window.articleEditor = new ArticleEditor();
    }
});

// Clean up on page unload
window.addEventListener('beforeunload', function() {
    if (window.articleEditor) {
        window.articleEditor.destroy();
    }
});
