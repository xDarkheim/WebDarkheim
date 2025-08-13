/**
 * Admin Panel JavaScript
 * Common functionality for administrative interface
 */

class AdminPanel {
    constructor() {
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.initializeComponents();
        this.setupTableFeatures();
        this.setupFormValidation();
        this.setupModals();
    }

    setupEventListeners() {
        // Auto-dismiss flash messages
        setTimeout(() => {
            this.dismissFlashMessages();
        }, 5000);

        // Confirm delete actions
        document.addEventListener('click', (e) => {
            if (e.target.closest('[data-confirm]')) {
                const message = e.target.closest('[data-confirm]').dataset.confirm;
                if (!confirm(message)) {
                    e.preventDefault();
                    return false;
                }
            }
        });

        // Handle loading states
        document.addEventListener('submit', (e) => {
            const form = e.target;
            if (form.tagName === 'FORM') {
                this.setLoadingState(form, true);
            }
        });
    }

    initializeComponents() {
        // Initialize search functionality
        this.initializeSearch();

        // Initialize slug generation
        this.initializeSlugGeneration();

        // Initialize tooltips
        this.initializeTooltips();

        // Initialize copy to clipboard
        this.initializeCopyButtons();
    }

    setupTableFeatures() {
        // Sortable table headers
        document.querySelectorAll('.admin-table th[data-sort]').forEach(header => {
            header.style.cursor = 'pointer';
            header.addEventListener('click', () => {
                this.sortTable(header);
            });
        });

        // Row selection
        this.setupRowSelection();
    }

    setupFormValidation() {
        // Real-time validation
        document.querySelectorAll('.admin-input[required]').forEach(input => {
            input.addEventListener('blur', () => {
                this.validateField(input);
            });
        });

        // Form submission validation
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', (e) => {
                if (!this.validateForm(form)) {
                    e.preventDefault();
                }
            });
        });
    }

    setupModals() {
        // Modal triggers
        document.addEventListener('click', (e) => {
            if (e.target.matches('[data-modal]')) {
                const modalId = e.target.dataset.modal;
                this.openModal(modalId);
            }

            if (e.target.matches('[data-modal-close]')) {
                this.closeModal(e.target.closest('.admin-modal'));
            }
        });

        // Close modal on backdrop click
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('admin-modal')) {
                this.closeModal(e.target);
            }
        });

        // Close modal on Escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                const openModal = document.querySelector('.admin-modal:not(.admin-hidden)');
                if (openModal) {
                    this.closeModal(openModal);
                }
            }
        });
    }

    // Flash Messages
    dismissFlashMessages() {
        document.querySelectorAll('.admin-flash-message').forEach(message => {
            message.style.opacity = '0';
            setTimeout(() => {
                message.remove();
            }, 300);
        });
    }

    showFlashMessage(type, text) {
        const container = document.querySelector('.admin-flash-messages') ||
                         document.querySelector('.admin-container');

        const icons = {
            success: 'fas fa-check-circle',
            error: 'fas fa-exclamation-circle',
            warning: 'fas fa-exclamation-triangle',
            info: 'fas fa-info-circle'
        };

        const message = document.createElement('div');
        message.className = `admin-flash-message admin-flash-${type}`;
        message.innerHTML = `
            <i class="${icons[type] || icons.info}"></i>
            <div>${text}</div>
        `;

        container.insertBefore(message, container.firstChild);

        // Auto-dismiss after 5 seconds
        setTimeout(() => {
            this.dismissFlashMessage(message);
        }, 5000);
    }

    dismissFlashMessage(message) {
        message.style.opacity = '0';
        setTimeout(() => {
            message.remove();
        }, 300);
    }

    // Search Functionality
    initializeSearch() {
        document.querySelectorAll('[data-search-target]').forEach(searchInput => {
            const targetSelector = searchInput.dataset.searchTarget;
            const searchColumns = searchInput.dataset.searchColumns?.split(',') || [];

            searchInput.addEventListener('input', () => {
                this.performSearch(searchInput.value, targetSelector, searchColumns);
            });
        });
    }

    performSearch(query, targetSelector, columns = []) {
        const rows = document.querySelectorAll(targetSelector);
        const searchTerm = query.toLowerCase().trim();

        rows.forEach(row => {
            if (!searchTerm) {
                row.style.display = '';
                return;
            }

            let matches = false;

            if (columns.length > 0) {
                // Search specific columns
                columns.forEach(columnIndex => {
                    const cell = row.children[parseInt(columnIndex)];
                    if (cell && cell.textContent.toLowerCase().includes(searchTerm)) {
                        matches = true;
                    }
                });
            } else {
                // Search all text content
                if (row.textContent.toLowerCase().includes(searchTerm)) {
                    matches = true;
                }
            }

            row.style.display = matches ? '' : 'none';
        });
    }

    // Slug Generation
    initializeSlugGeneration() {
        document.querySelectorAll('[data-slug-source]').forEach(sourceInput => {
            const targetSelector = sourceInput.dataset.slugTarget;
            const targetInput = document.querySelector(targetSelector);

            if (!targetInput) return;

            let isManuallyEdited = false;

            // Track manual edits
            targetInput.addEventListener('input', () => {
                isManuallyEdited = true;
            });

            // Auto-generate slug
            sourceInput.addEventListener('input', () => {
                if (!isManuallyEdited) {
                    targetInput.value = this.generateSlug(sourceInput.value);
                }
            });
        });
    }

    generateSlug(text) {
        return text
            .toLowerCase()
            .trim()
            .replace(/[^\w\s-]/g, '')
            .replace(/[\s_-]+/g, '-')
            .replace(/^-+|-+$/g, '');
    }

    // Table Sorting
    sortTable(header) {
        const table = header.closest('.admin-table');
        const tbody = table.querySelector('tbody');
        const rows = Array.from(tbody.querySelectorAll('tr'));
        const columnIndex = Array.from(header.parentNode.children).indexOf(header);
        const currentOrder = header.dataset.sortOrder || 'asc';
        const newOrder = currentOrder === 'asc' ? 'desc' : 'asc';

        // Clear other sort indicators
        table.querySelectorAll('th').forEach(th => {
            th.removeAttribute('data-sort-order');
            th.classList.remove('admin-sort-asc', 'admin-sort-desc');
        });

        // Set new sort order
        header.dataset.sortOrder = newOrder;
        header.classList.add(`admin-sort-${newOrder}`);

        // Sort rows
        rows.sort((a, b) => {
            const aValue = a.children[columnIndex]?.textContent.trim() || '';
            const bValue = b.children[columnIndex]?.textContent.trim() || '';

            let comparison = 0;
            if (!isNaN(aValue) && !isNaN(bValue)) {
                comparison = parseFloat(aValue) - parseFloat(bValue);
            } else {
                comparison = aValue.localeCompare(bValue);
            }

            return newOrder === 'asc' ? comparison : -comparison;
        });

        // Reappend sorted rows
        rows.forEach(row => tbody.appendChild(row));
    }

    // Row Selection
    setupRowSelection() {
        const selectAllCheckbox = document.querySelector('[data-select-all]');
        const rowCheckboxes = document.querySelectorAll('[data-select-row]');

        if (selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', () => {
                rowCheckboxes.forEach(checkbox => {
                    checkbox.checked = selectAllCheckbox.checked;
                });
                this.updateBulkActions();
            });
        }

        rowCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', () => {
                this.updateSelectAll();
                this.updateBulkActions();
            });
        });
    }

    updateSelectAll() {
        const selectAllCheckbox = document.querySelector('[data-select-all]');
        const rowCheckboxes = document.querySelectorAll('[data-select-row]');

        if (!selectAllCheckbox) return;

        const checkedCount = Array.from(rowCheckboxes).filter(cb => cb.checked).length;

        selectAllCheckbox.checked = checkedCount === rowCheckboxes.length;
        selectAllCheckbox.indeterminate = checkedCount > 0 && checkedCount < rowCheckboxes.length;
    }

    updateBulkActions() {
        const checkedRows = document.querySelectorAll('[data-select-row]:checked');
        const bulkActions = document.querySelector('[data-bulk-actions]');

        if (bulkActions) {
            bulkActions.style.display = checkedRows.length > 0 ? 'block' : 'none';
        }
    }

    // Form Validation
    validateField(field) {
        const value = field.value.trim();
        const rules = {
            required: field.hasAttribute('required'),
            minLength: field.getAttribute('minlength'),
            maxLength: field.getAttribute('maxlength'),
            pattern: field.getAttribute('pattern'),
            type: field.getAttribute('type')
        };

        let isValid = true;
        let message = '';

        if (rules.required && !value) {
            isValid = false;
            message = 'This field is required';
        } else if (value && rules.minLength && value.length < parseInt(rules.minLength)) {
            isValid = false;
            message = `Minimum length is ${rules.minLength} characters`;
        } else if (value && rules.maxLength && value.length > parseInt(rules.maxLength)) {
            isValid = false;
            message = `Maximum length is ${rules.maxLength} characters`;
        } else if (value && rules.pattern && !new RegExp(rules.pattern).test(value)) {
            isValid = false;
            message = 'Invalid format';
        } else if (value && rules.type === 'email' && !this.isValidEmail(value)) {
            isValid = false;
            message = 'Invalid email address';
        }

        this.showFieldValidation(field, isValid, message);
        return isValid;
    }

    validateForm(form) {
        const fields = form.querySelectorAll('.admin-input[required]');
        let isValid = true;

        fields.forEach(field => {
            if (!this.validateField(field)) {
                isValid = false;
            }
        });

        return isValid;
    }

    showFieldValidation(field, isValid, message) {
        // Remove existing validation
        field.classList.remove('admin-input-valid', 'admin-input-invalid');
        const existingError = field.parentNode.querySelector('.admin-field-error');
        if (existingError) {
            existingError.remove();
        }

        if (!isValid && message) {
            field.classList.add('admin-input-invalid');

            const errorDiv = document.createElement('div');
            errorDiv.className = 'admin-field-error';
            errorDiv.style.cssText = 'color: var(--admin-error); font-size: 0.75rem; margin-top: 0.25rem;';
            errorDiv.textContent = message;

            field.parentNode.appendChild(errorDiv);
        } else if (field.value.trim()) {
            field.classList.add('admin-input-valid');
        }
    }

    isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    // Modal Management
    openModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('admin-hidden');
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';

            // Focus first input
            const firstInput = modal.querySelector('.admin-input');
            if (firstInput) {
                setTimeout(() => firstInput.focus(), 100);
            }
        }
    }

    closeModal(modal) {
        if (modal) {
            modal.classList.add('admin-hidden');
            modal.style.display = 'none';
            document.body.style.overflow = '';
        }
    }

    // Loading States
    setLoadingState(element, isLoading) {
        if (isLoading) {
            element.classList.add('admin-loading');
            element.disabled = true;
        } else {
            element.classList.remove('admin-loading');
            element.disabled = false;
        }
    }

    // Tooltips
    initializeTooltips() {
        document.querySelectorAll('[data-tooltip]').forEach(element => {
            element.addEventListener('mouseenter', (e) => {
                this.showTooltip(e.target, e.target.dataset.tooltip);
            });

            element.addEventListener('mouseleave', () => {
                this.hideTooltip();
            });
        });
    }

    showTooltip(element, text) {
        const tooltip = document.createElement('div');
        tooltip.className = 'admin-tooltip';
        tooltip.textContent = text;
        tooltip.style.cssText = `
            position: absolute;
            background: var(--admin-gray-900);
            color: white;
            padding: 0.5rem;
            border-radius: var(--admin-border-radius);
            font-size: 0.75rem;
            z-index: 1000;
            pointer-events: none;
            opacity: 0;
            transition: opacity 0.2s;
        `;

        document.body.appendChild(tooltip);

        const rect = element.getBoundingClientRect();
        tooltip.style.left = `${rect.left + rect.width / 2 - tooltip.offsetWidth / 2}px`;
        tooltip.style.top = `${rect.top - tooltip.offsetHeight - 5}px`;

        setTimeout(() => {
            tooltip.style.opacity = '1';
        }, 10);

        this.currentTooltip = tooltip;
    }

    hideTooltip() {
        if (this.currentTooltip) {
            this.currentTooltip.style.opacity = '0';
            setTimeout(() => {
                if (this.currentTooltip) {
                    this.currentTooltip.remove();
                    this.currentTooltip = null;
                }
            }, 200);
        }
    }

    // Copy to Clipboard
    initializeCopyButtons() {
        document.querySelectorAll('[data-copy]').forEach(button => {
            button.addEventListener('click', () => {
                const text = button.dataset.copy;
                this.copyToClipboard(text);
                this.showFlashMessage('success', 'Copied to clipboard!');
            });
        });
    }

    copyToClipboard(text) {
        if (navigator.clipboard) {
            navigator.clipboard.writeText(text);
        } else {
            // Fallback for older browsers
            const textArea = document.createElement('textarea');
            textArea.value = text;
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();
            document.execCommand('copy');
            document.body.removeChild(textArea);
        }
    }

    // AJAX Helpers
    async request(url, options = {}) {
        const defaultOptions = {
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        };

        const config = { ...defaultOptions, ...options };

        try {
            const response = await fetch(url, config);

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            return await response.json();
        } catch (error) {
            console.error('Request failed:', error);
            this.showFlashMessage('error', 'An error occurred. Please try again.');
            throw error;
        }
    }

    // Status Badge Helper
    createStatusBadge(status) {
        const statusConfig = {
            'published': { class: 'admin-badge-success', icon: 'fas fa-check-circle' },
            'draft': { class: 'admin-badge-gray', icon: 'fas fa-edit' },
            'pending_review': { class: 'admin-badge-warning', icon: 'fas fa-clock' },
            'rejected': { class: 'admin-badge-error', icon: 'fas fa-times-circle' },
            'active': { class: 'admin-badge-success', icon: 'fas fa-check' },
            'inactive': { class: 'admin-badge-gray', icon: 'fas fa-pause' }
        };

        const config = statusConfig[status] || statusConfig['draft'];
        const statusText = status.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase());

        return `<span class="admin-badge ${config.class}">
            <i class="${config.icon}"></i>
            ${statusText}
        </span>`;
    }

    // Utility Methods
    formatDate(dateString, options = {}) {
        const defaultOptions = {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        };

        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', { ...defaultOptions, ...options });
    }

    formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';

        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));

        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
}

// Auto-initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.adminPanel = new AdminPanel();
});

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = AdminPanel;
}
