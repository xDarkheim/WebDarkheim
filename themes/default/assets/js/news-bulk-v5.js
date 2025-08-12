/**
 * News Bulk Operations & Modals Module v5.0 - Refactored
 * Combined module for bulk operations and modal management
 * 
 * @author Darkheim Studio
 * @version 5.0.0
 * @since 2025-08-10
 */

(function() {
    'use strict';

    /**
     * Advanced Bulk Operations & Modals Module
     * Handles bulk operations and modal dialogs with modern patterns
     */
    class NewsBulkOperations {
        /**
         * @param {NewsCore} core - Reference to news core system
         */
        constructor(core) {
            this.core = core;
            this.version = '5.0.0';
            this.namespace = 'NewsBulkOperations';

            this.config = {
                selectors: {
                    bulkActionsBar: '#bulkActionsBar',
                    bulkCountElement: '#bulkSelectedCount',
                    bulkCheckbox: '.bulk-select-checkbox'
                },
                classes: {
                    modalOverlay: 'news-modal-overlay',
                    modalContent: 'news-modal-content',
                    modalActive: 'news-modal-active'
                },
                actions: {
                    edit: { label: 'Edit', icon: 'fas fa-edit', color: '#3498db' },
                    publish: { label: 'Publish', icon: 'fas fa-eye', color: '#27ae60' },
                    hide: { label: 'Hide', icon: 'fas fa-eye-slash', color: '#f39c12' },
                    category: { label: 'Change Category', icon: 'fas fa-tags', color: '#9b59b6' },
                    delete: { label: 'Delete', icon: 'fas fa-trash', color: '#e74c3c' },
                    export: { label: 'Export', icon: 'fas fa-download', color: '#34495e' }
                }
            };

            this.state = {
                selectedArticles: new Set(),
                activeModal: null,
                bulkActionsVisible: false
            };

            this.logger = this.core.logger || console;
            this.setupGlobalFunctions();
        }

        /**
         * Initialize bulk operations module
         */
        async init() {
            try {
                this.logger.info('Initializing bulk operations module...');

                await this.bindBulkElements();
                this.setupEventListeners();
                this.createBulkActionsStyles();

                this.logger.success('Bulk operations module initialized');
                return true;
            } catch (error) {
                this.logger.error('Bulk operations initialization failed:', error);
                throw error;
            }
        }

        /**
         * Bind bulk operation elements
         */
        async bindBulkElements() {
            this.elements = {
                bulkActionsBar: document.querySelector(this.config.selectors.bulkActionsBar),
                bulkCountElement: document.querySelector(this.config.selectors.bulkCountElement)
            };

            // Create bulk actions bar if it doesn't exist
            if (!this.elements.bulkActionsBar) {
                this.elements.bulkActionsBar = this.createBulkActionsBar();
            }
        }

        /**
         * Setup event listeners
         */
        setupEventListeners() {
            // Listen to admin module events
            this.core.eventBus?.on('admin:selection:changed', this.handleSelectionChanged.bind(this));
            this.core.eventBus?.on('admin:mode:changed', this.handleAdminModeChanged.bind(this));

            // Listen to bulk checkbox changes
            document.addEventListener('change', this.handleCheckboxChange.bind(this));

            // Listen to escape key for modal closing
            document.addEventListener('keydown', this.handleKeyDown.bind(this));

            // Listen to modal overlay clicks
            document.addEventListener('click', this.handleModalOverlayClick.bind(this));
        }

        /**
         * Setup global functions for backward compatibility
         */
        setupGlobalFunctions() {
            window.selectAllVisibleArticles = () => this.selectAllVisibleArticles();
            window.deselectAllArticles = () => this.deselectAllArticles();
            window.closeBulkActions = () => this.closeBulkActions();
            window.bulkEditArticles = () => this.bulkEditArticles();
            window.bulkPublishArticles = () => this.bulkPublishArticles();
            window.bulkHideArticles = () => this.bulkHideArticles();
            window.bulkCategoryChange = () => this.bulkCategoryChange();
            window.bulkDeleteArticles = () => this.bulkDeleteArticles();
            window.bulkExportArticles = () => this.bulkExportArticles();
        }

        /**
         * Create bulk actions bar
         */
        createBulkActionsBar() {
            const bar = document.createElement('div');
            bar.id = 'bulkActionsBar';
            bar.className = 'bulk-actions-bar';
            bar.innerHTML = `
                <div class="bulk-actions-content">
                    <div class="bulk-actions-info">
                        <span class="bulk-selected-count">
                            <span id="bulkSelectedCount">0</span> articles selected
                        </span>
                    </div>
                    <div class="bulk-actions-buttons">
                        <button onclick="selectAllVisibleArticles()" class="bulk-btn bulk-btn-select">
                            <i class="fas fa-check-square"></i> Select All
                        </button>
                        <button onclick="deselectAllArticles()" class="bulk-btn bulk-btn-deselect">
                            <i class="fas fa-square"></i> Deselect All
                        </button>
                        <div class="bulk-actions-separator"></div>
                        <button onclick="bulkEditArticles()" class="bulk-btn bulk-btn-edit">
                            <i class="${this.config.actions.edit.icon}"></i> ${this.config.actions.edit.label}
                        </button>
                        <button onclick="bulkPublishArticles()" class="bulk-btn bulk-btn-publish">
                            <i class="${this.config.actions.publish.icon}"></i> ${this.config.actions.publish.label}
                        </button>
                        <button onclick="bulkHideArticles()" class="bulk-btn bulk-btn-hide">
                            <i class="${this.config.actions.hide.icon}"></i> ${this.config.actions.hide.label}
                        </button>
                        <button onclick="bulkCategoryChange()" class="bulk-btn bulk-btn-category">
                            <i class="${this.config.actions.category.icon}"></i> ${this.config.actions.category.label}
                        </button>
                        <button onclick="bulkExportArticles()" class="bulk-btn bulk-btn-export">
                            <i class="${this.config.actions.export.icon}"></i> ${this.config.actions.export.label}
                        </button>
                        <div class="bulk-actions-separator"></div>
                        <button onclick="bulkDeleteArticles()" class="bulk-btn bulk-btn-delete">
                            <i class="${this.config.actions.delete.icon}"></i> ${this.config.actions.delete.label}
                        </button>
                        <button onclick="closeBulkActions()" class="bulk-btn bulk-btn-close">
                            <i class="fas fa-times"></i> Close
                        </button>
                    </div>
                </div>
            `;

            document.body.appendChild(bar);
            this.elements.bulkCountElement = bar.querySelector('#bulkSelectedCount');

            return bar;
        }

        /**
         * Create bulk actions styles
         */
        createBulkActionsStyles() {
            if (document.getElementById('news-bulk-styles')) return;

            const style = document.createElement('style');
            style.id = 'news-bulk-styles';
            style.textContent = `
                .bulk-actions-bar {
                    position: fixed;
                    bottom: -100px;
                    left: 0;
                    right: 0;
                    background: #fff;
                    border-top: 1px solid #ddd;
                    box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
                    z-index: 1000;
                    transition: bottom 0.3s ease;
                    padding: 15px 20px;
                }

                .bulk-actions-bar.show {
                    bottom: 0;
                }

                .bulk-actions-content {
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    max-width: 1200px;
                    margin: 0 auto;
                }

                .bulk-actions-info {
                    font-weight: 600;
                    color: #333;
                }

                .bulk-actions-buttons {
                    display: flex;
                    align-items: center;
                    gap: 10px;
                }

                .bulk-btn {
                    padding: 8px 16px;
                    border: 1px solid #ddd;
                    background: #fff;
                    border-radius: 4px;
                    cursor: pointer;
                    font-size: 14px;
                    transition: all 0.2s ease;
                    display: flex;
                    align-items: center;
                    gap: 6px;
                }

                .bulk-btn:hover {
                    background: #f8f9fa;
                    border-color: #adb5bd;
                }

                .bulk-btn-delete {
                    background: #dc3545;
                    color: white;
                    border-color: #dc3545;
                }

                .bulk-btn-delete:hover {
                    background: #c82333;
                }

                .bulk-actions-separator {
                    width: 1px;
                    height: 20px;
                    background: #ddd;
                    margin: 0 5px;
                }

                .news-modal-overlay {
                    position: fixed;
                    top: 0;
                    left: 0;
                    right: 0;
                    bottom: 0;
                    background: rgba(0, 0, 0, 0.5);
                    z-index: 10000;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    opacity: 0;
                    transition: opacity 0.3s ease;
                }

                .news-modal-overlay.show {
                    opacity: 1;
                }

                .news-modal-content {
                    background: white;
                    border-radius: 8px;
                    box-shadow: 0 10px 30px rgba(0,0,0,0.3);
                    max-width: 600px;
                    width: 90%;
                    max-height: 80vh;
                    overflow-y: auto;
                    transform: scale(0.9);
                    transition: transform 0.3s ease;
                }

                .news-modal-overlay.show .news-modal-content {
                    transform: scale(1);
                }

                .modal-header {
                    padding: 20px 24px 0;
                    border-bottom: 1px solid #dee2e6;
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                }

                .modal-body {
                    padding: 24px;
                }

                .modal-footer {
                    padding: 0 24px 20px;
                    display: flex;
                    justify-content: flex-end;
                    gap: 12px;
                }
            `;

            document.head.appendChild(style);
        }

        /**
         * Handle selection changed event
         */
        handleSelectionChanged(data) {
            this.state.selectedArticles = new Set(data.selectedArticles);
            this.updateBulkActionsVisibility(data.selectedCount);
        }

        /**
         * Handle admin mode changed event
         */
        handleAdminModeChanged(data) {
            if (!data.isAdminMode) {
                this.closeBulkActions();
            }
        }

        /**
         * Handle checkbox change events
         */
        handleCheckboxChange(event) {
            if (!event.target.matches(this.config.selectors.bulkCheckbox)) return;

            // This will be handled by the admin module and propagated back to us
            // via the selection changed event
        }

        /**
         * Handle keyboard events
         */
        handleKeyDown(event) {
            if (event.key === 'Escape' && this.state.activeModal) {
                this.closeModal();
            }
        }

        /**
         * Handle modal overlay clicks
         */
        handleModalOverlayClick(event) {
            if (event.target.classList.contains(this.config.classes.modalOverlay)) {
                this.closeModal();
            }
        }

        /**
         * Update bulk actions visibility
         */
        updateBulkActionsVisibility(count) {
            if (count > 0) {
                this.showBulkActionsBar(count);
            } else {
                this.hideBulkActionsBar();
            }
        }

        /**
         * Show bulk actions bar
         */
        showBulkActionsBar(count) {
            if (!this.elements.bulkActionsBar) return;

            this.elements.bulkCountElement.textContent = count;
            this.elements.bulkActionsBar.classList.add('show');
            this.state.bulkActionsVisible = true;

            this.logger.debug(`Bulk actions shown for ${count} articles`);
        }

        /**
         * Hide bulk actions bar
         */
        hideBulkActionsBar() {
            if (!this.elements.bulkActionsBar) return;

            this.elements.bulkActionsBar.classList.remove('show');
            this.state.bulkActionsVisible = false;

            this.logger.debug('Bulk actions hidden');
        }

        /**
         * Select all visible articles
         */
        selectAllVisibleArticles() {
            const visibleCheckboxes = document.querySelectorAll(
                '.article-card:not([style*="display: none"]) .bulk-select-checkbox'
            );

            visibleCheckboxes.forEach(checkbox => {
                checkbox.checked = true;
                checkbox.dispatchEvent(new Event('change', { bubbles: true }));
            });

            this.showNotification(`Selected ${visibleCheckboxes.length} articles`, 'success');
        }

        /**
         * Deselect all articles
         */
        deselectAllArticles() {
            const checkboxes = document.querySelectorAll(this.config.selectors.bulkCheckbox);
            
            checkboxes.forEach(checkbox => {
                checkbox.checked = false;
                checkbox.dispatchEvent(new Event('change', { bubbles: true }));
            });

            this.showNotification('Deselected all articles', 'info');
        }

        /**
         * Close bulk actions
         */
        closeBulkActions() {
            this.deselectAllArticles();
        }

        /**
         * Bulk edit articles
         */
        bulkEditArticles() {
            if (this.state.selectedArticles.size === 0) {
                this.showNotification('Please select articles to edit', 'warning');
                return;
            }

            this.showBulkEditModal(Array.from(this.state.selectedArticles));
        }

        /**
         * Bulk publish articles
         */
        bulkPublishArticles() {
            if (this.state.selectedArticles.size === 0) {
                this.showNotification('Please select articles to publish', 'warning');
                return;
            }

            if (!confirm(`Are you sure you want to publish ${this.state.selectedArticles.size} article(s)?`)) {
                return;
            }

            this.performBulkAction('publish', Array.from(this.state.selectedArticles));
        }

        /**
         * Bulk hide articles
         */
        bulkHideArticles() {
            if (this.state.selectedArticles.size === 0) {
                this.showNotification('Please select articles to hide', 'warning');
                return;
            }

            if (!confirm(`Are you sure you want to hide ${this.state.selectedArticles.size} article(s)?`)) {
                return;
            }

            this.performBulkAction('hide', Array.from(this.state.selectedArticles));
        }

        /**
         * Bulk category change
         */
        bulkCategoryChange() {
            if (this.state.selectedArticles.size === 0) {
                this.showNotification('Please select articles to change category', 'warning');
                return;
            }

            this.showBulkCategoryModal(Array.from(this.state.selectedArticles));
        }

        /**
         * Bulk delete articles
         */
        bulkDeleteArticles() {
            if (this.state.selectedArticles.size === 0) {
                this.showNotification('Please select articles to delete', 'warning');
                return;
            }

            const count = this.state.selectedArticles.size;
            const confirmMessage = this.config.confirmations?.bulkDelete?.replace('{count}', count) ||
                `⚠️ WARNING: You are about to permanently delete ${count} article(s).\n\nThis action CANNOT be undone and will:\n• Remove all article content\n• Delete all associated comments\n• Remove all category associations\n\nAre you absolutely sure you want to continue?`;

            if (!confirm(confirmMessage)) {
                return;
            }

            if (!confirm('This is your final confirmation. Delete selected articles permanently?')) {
                return;
            }

            this.performBulkAction('delete', Array.from(this.state.selectedArticles));
        }

        /**
         * Bulk export articles
         */
        bulkExportArticles() {
            if (this.state.selectedArticles.size === 0) {
                this.showNotification('Please select articles to export', 'warning');
                return;
            }

            this.performBulkAction('export', Array.from(this.state.selectedArticles));
        }

        /**
         * Show bulk edit modal
         */
        showBulkEditModal(articleIds) {
            const modalContent = `
                <div class="modal-header">
                    <h3><i class="fas fa-edit"></i> Bulk Edit ${articleIds.length} Articles</h3>
                    <button class="modal-close" onclick="closeBulkEditModal()">&times;</button>
                </div>
                <div class="modal-body">
                    <form id="bulkEditForm">
                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="update_status" value="1"> Update Status
                            </label>
                            <select name="status" disabled>
                                <option value="published">Published</option>
                                <option value="draft">Draft</option>
                                <option value="hidden">Hidden</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="update_author" value="1"> Change Author
                            </label>
                            <input type="text" name="author" placeholder="New author name" disabled>
                        </div>
                        
                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="update_date" value="1"> Update Publication Date
                            </label>
                            <input type="date" name="publication_date" disabled>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" onclick="closeBulkEditModal()">Cancel</button>
                    <button class="btn btn-primary" onclick="applyBulkEdit([${articleIds.join(',')}])">Apply Changes</button>
                </div>
            `;

            this.showModal(modalContent, 'bulk-edit-modal');
        }

        /**
         * Show bulk category modal
         */
        showBulkCategoryModal(articleIds) {
            // Get categories from existing filter tabs
            const categoryTabs = document.querySelectorAll('.filter-tab');
            let categoriesOptions = '';

            categoryTabs.forEach((tab, index) => {
                if (index > 0) { // Skip "All News"
                    const categoryName = tab.textContent.replace(/\(\d+\)/, '').trim();
                    categoriesOptions += `<option value="${categoryName}">${categoryName}</option>`;
                }
            });

            const modalContent = `
                <div class="modal-header">
                    <h3><i class="fas fa-tags"></i> Change Category for ${articleIds.length} Articles</h3>
                    <button class="modal-close" onclick="closeBulkCategoryModal()">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="bulkCategorySelect">Select New Category:</label>
                        <select id="bulkCategorySelect" class="form-control">
                            <option value="">Choose category...</option>
                            ${categoriesOptions}
                        </select>
                    </div>
                    <div class="category-action-options">
                        <label>
                            <input type="radio" name="category_action" value="replace" checked>
                            Replace existing categories
                        </label>
                        <label>
                            <input type="radio" name="category_action" value="add">
                            Add to existing categories
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" onclick="closeBulkCategoryModal()">Cancel</button>
                    <button class="btn btn-primary" onclick="applyBulkCategoryChange([${articleIds.join(',')}])">Apply Changes</button>
                </div>
            `;

            this.showModal(modalContent, 'bulk-category-modal');
        }

        /**
         * Generic modal display
         */
        showModal(content, className = 'generic-modal') {
            this.closeModal(); // Close any existing modal

            const overlay = document.createElement('div');
            overlay.className = `${this.config.classes.modalOverlay} ${className}`;
            
            const modalDiv = document.createElement('div');
            modalDiv.className = this.config.classes.modalContent;
            modalDiv.innerHTML = content;
            
            overlay.appendChild(modalDiv);
            document.body.appendChild(overlay);

            // Show with animation
            requestAnimationFrame(() => {
                overlay.classList.add('show');
            });

            this.state.activeModal = overlay;

            // Setup checkbox handlers for form fields
            const checkboxes = overlay.querySelectorAll('input[type="checkbox"]');
            checkboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    const targetInput = this.parentNode.parentNode.querySelector('select, input:not([type="checkbox"])');
                    if (targetInput) {
                        targetInput.disabled = !this.checked;
                    }
                });
            });
        }

        /**
         * Close active modal
         */
        closeModal() {
            if (!this.state.activeModal) return;

            this.state.activeModal.classList.remove('show');
            
            setTimeout(() => {
                if (this.state.activeModal && this.state.activeModal.parentNode) {
                    this.state.activeModal.parentNode.removeChild(this.state.activeModal);
                }
                this.state.activeModal = null;
            }, 300);
        }

        /**
         * Perform bulk action
         */
        performBulkAction(action, articleIds) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = `/index.php?page=bulk_article_actions`;
            form.style.display = 'none';

            // Add CSRF token
            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = 'csrf_token';
            csrfInput.value = this.getCsrfToken();
            form.appendChild(csrfInput);

            // Add action
            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'bulk_action';
            actionInput.value = action;
            form.appendChild(actionInput);

            // Add article IDs
            articleIds.forEach(id => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'article_ids[]';
                input.value = id;
                form.appendChild(input);
            });

            document.body.appendChild(form);
            form.submit();

            // Show notification
            const actionLabels = {
                'publish': 'Publishing',
                'hide': 'Hiding',
                'delete': 'Deleting',
                'export': 'Exporting'
            };

            this.showNotification(`${actionLabels[action] || 'Processing'} ${articleIds.length} articles...`, 'info');
        }

        /**
         * Get CSRF token
         */
        getCsrfToken() {
            return this.core.utils?.getCsrfToken() || '';
        }

        /**
         * Show notification
         */
        showNotification(message, type = 'info') {
            if (this.core.utils?.showNotification) {
                this.core.utils.showNotification(message, type);
            } else {
                alert(message);
            }
        }

        /**
         * Get bulk operation statistics
         */
        getBulkStats() {
            return {
                selectedCount: this.state.selectedArticles.size,
                selectedArticles: Array.from(this.state.selectedArticles),
                bulkActionsVisible: this.state.bulkActionsVisible,
                hasActiveModal: !!this.state.activeModal
            };
        }

        /**
         * Clean up module
         */
        async destroy() {
            this.logger.debug('Destroying bulk operations module...');

            this.closeModal();
            this.hideBulkActionsBar();

            // Clear state
            this.state.selectedArticles.clear();
            this.state.activeModal = null;
            this.state.bulkActionsVisible = false;

            // Remove global functions
            const globalFunctions = [
                'selectAllVisibleArticles', 'deselectAllArticles', 'closeBulkActions',
                'bulkEditArticles', 'bulkPublishArticles', 'bulkHideArticles',
                'bulkCategoryChange', 'bulkDeleteArticles', 'bulkExportArticles'
            ];

            globalFunctions.forEach(funcName => {
                delete window[funcName];
            });

            this.logger.debug('Bulk operations module destroyed');
        }

        /**
         * Get module API
         */
        getAPI() {
            return {
                version: this.version,
                getStats: () => this.getBulkStats(),
                selectAll: () => this.selectAllVisibleArticles(),
                deselectAll: () => this.deselectAllArticles(),
                closeBulkActions: () => this.closeBulkActions(),
                showModal: (content, className) => this.showModal(content, className),
                closeModal: () => this.closeModal()
            };
        }
    }

    // Export to global scope
    window.NewsBulk = NewsBulkOperations;
    window.NewsBulkOperations = NewsBulkOperations; // ДОБАВЛЕНО: Дублируем экспорт
    window.NewsModals = NewsBulkOperations; // Alias for backward compatibility

    console.log('[NewsBulkOperations] v5.0 module loaded - unified bulk operations and modals');

})();
