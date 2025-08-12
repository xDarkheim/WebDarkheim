/**
 * News Admin Module v5.0 - Refactored
 * Modern admin functionality with improved UX and state management
 *
 * @author Darkheim Studio
 * @version 5.0.0
 * @since 2025-08-10
 */

(function() {
    'use strict';

    /**
     * Advanced News Admin Module
     * Handles administrative functionality with modern patterns
     */
    class NewsAdmin {
        /**
         * @param {NewsCore} core - Reference to news core system
         */
        constructor(core) {
            this.core = core;
            this.version = '5.0.0';
            this.namespace = 'NewsAdmin';

            this.config = {
                selectors: {
                    toggleButton: '.admin-toggle-btn',
                    articleCard: '.article-card',
                    adminControls: '.admin-controls',
                    bulkCheckbox: '.bulk-select-checkbox'
                },
                classes: {
                    adminMode: 'admin-mode',
                    adminActive: 'admin-active',
                    hasControls: 'has-admin-controls'
                },
                confirmations: {
                    delete: 'Are you sure you want to delete this article? This action cannot be undone.',
                    bulkDelete: 'Are you sure you want to delete {count} articles? This action cannot be undone.'
                }
            };

            this.state = {
                isAdminMode: false,
                selectedArticles: new Set(),
                adminControls: new Map()
            };

            this.logger = this.core.logger || console;
        }

        /**
         * Initialize admin module
         */
        async init() {
            try {
                this.logger.info('Initializing admin module...');

                // Check if user has admin privileges
                if (!this.hasAdminPrivileges()) {
                    this.logger.info('No admin privileges, skipping admin module');
                    return false;
                }

                await this.bindAdminElements();
                this.setupEventListeners();
                this.setupGlobalFunctions();

                this.logger.success('Admin module initialized');
                return true;
            } catch (error) {
                this.logger.error('Admin initialization failed:', error);
                throw error;
            }
        }

        /**
         * Check if user has admin privileges
         */
        hasAdminPrivileges() {
            return document.querySelector(this.config.selectors.toggleButton) !== null;
        }

        /**
         * Bind admin elements
         */
        async bindAdminElements() {
            this.elements = {
                toggleButton: document.querySelector(this.config.selectors.toggleButton),
                articleCards: document.querySelectorAll(this.config.selectors.articleCard)
            };

            if (!this.elements.toggleButton) {
                throw new Error('Admin toggle button not found');
            }

            this.logger.debug(`Found ${this.elements.articleCards.length} article cards`);
        }

        /**
         * Setup event listeners
         */
        setupEventListeners() {
            // Toggle button
            this.elements.toggleButton.addEventListener('click', this.toggleAdminMode.bind(this));

            // Listen to bulk selection changes
            document.addEventListener('change', this.handleBulkSelectionChange.bind(this));

            // Listen to core events
            this.core.eventBus?.on('navigation:filters:changed', this.handleFiltersChanged.bind(this));
            this.core.eventBus?.on('admin:article:deleted', this.handleArticleDeleted.bind(this));
        }

        /**
         * Setup global functions for backward compatibility
         */
        setupGlobalFunctions() {
            window.toggleAdminMode = () => this.toggleAdminMode();
            window.quickDeleteArticle = (articleId) => this.quickDeleteArticle(articleId);
        }

        /**
         * Toggle admin mode
         */
        async toggleAdminMode() {
            this.state.isAdminMode = !this.state.isAdminMode;

            try {
                if (this.state.isAdminMode) {
                    await this.enableAdminMode();
                } else {
                    await this.disableAdminMode();
                }

                this.updateToggleButton();
                this.toggleBulkCheckboxes();

                // Emit state change
                this.core.eventBus?.emit('admin:mode:changed', {
                    isAdminMode: this.state.isAdminMode
                });

            } catch (error) {
                this.logger.error('Failed to toggle admin mode:', error);
                this.core.handleError(error);
            }
        }

        /**
         * Enable admin mode
         */
        async enableAdminMode() {
            this.logger.debug('Enabling admin mode...');

            // Add admin controls to all articles
            const articleCards = document.querySelectorAll(this.config.selectors.articleCard);

            for (const card of articleCards) {
                await this.addAdminControls(card);
                card.classList.add(this.config.classes.adminMode);
            }

            // Add global admin styles
            document.body.classList.add(this.config.classes.adminActive);
        }

        /**
         * Disable admin mode
         */
        async disableAdminMode() {
            this.logger.debug('Disabling admin mode...');

            // Remove admin controls from all articles
            const articleCards = document.querySelectorAll(this.config.selectors.articleCard);

            articleCards.forEach(card => {
                this.removeAdminControls(card);
                card.classList.remove(this.config.classes.adminMode);
            });

            // Remove global admin styles
            document.body.classList.remove(this.config.classes.adminActive);

            // Clear selections
            this.clearAllSelections();

            // Hide bulk actions if module exists
            const bulkModule = this.core.getModule('bulk');
            if (bulkModule) {
                bulkModule.closeBulkActions();
            }
        }

        /**
         * Add admin controls to article card
         */
        async addAdminControls(articleCard) {
            if (articleCard.querySelector(this.config.selectors.adminControls)) {
                return; // Already has controls
            }

            const articleId = this.extractArticleId(articleCard);
            if (!articleId) {
                this.logger.warn('Could not extract article ID from card');
                return;
            }

            const adminControls = this.createAdminControls(articleId);
            articleCard.appendChild(adminControls);
            articleCard.classList.add(this.config.classes.hasControls);

            // Store reference for cleanup
            this.state.adminControls.set(articleCard, adminControls);
        }

        /**
         * Create admin controls HTML
         */
        createAdminControls(articleId) {
            const controls = document.createElement('div');
            controls.className = 'admin-controls';
            controls.innerHTML = `
                <div class="admin-controls-overlay">
                    <div class="admin-controls-buttons">
                        <a href="/index.php?page=edit_article&id=${articleId}" 
                           class="admin-btn admin-btn-edit" 
                           title="Edit Article"
                           data-article-id="${articleId}">
                            <i class="fas fa-edit"></i>
                            <span>Edit</span>
                        </a>
                        <button onclick="quickDeleteArticle(${articleId})" 
                                class="admin-btn admin-btn-delete" 
                                title="Delete Article"
                                data-article-id="${articleId}">
                            <i class="fas fa-trash"></i>
                            <span>Delete</span>
                        </button>
                        <a href="/index.php?page=news&id=${articleId}" 
                           class="admin-btn admin-btn-view" 
                           title="View Article"
                           data-article-id="${articleId}">
                            <i class="fas fa-eye"></i>
                            <span>View</span>
                        </a>
                        <button class="admin-btn admin-btn-stats" 
                                title="Article Statistics"
                                onclick="showArticleStats(${articleId})"
                                data-article-id="${articleId}">
                            <i class="fas fa-chart-bar"></i>
                            <span>Stats</span>
                        </button>
                    </div>
                </div>
            `;

            // Add modern styling
            controls.style.cssText = `
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0, 0, 0, 0.8);
                display: flex;
                align-items: center;
                justify-content: center;
                opacity: 0;
                transition: opacity 0.3s ease;
                border-radius: inherit;
                z-index: 10;
            `;

            // Add hover effects
            const parentCard = controls.closest(this.config.selectors.articleCard);
            if (parentCard) {
                parentCard.addEventListener('mouseenter', () => {
                    if (this.state.isAdminMode) {
                        controls.style.opacity = '1';
                    }
                });

                parentCard.addEventListener('mouseleave', () => {
                    controls.style.opacity = '0';
                });
            }

            return controls;
        }

        /**
         * Remove admin controls from article card
         */
        removeAdminControls(articleCard) {
            const adminControls = this.state.adminControls.get(articleCard);
            if (adminControls && adminControls.parentNode) {
                adminControls.parentNode.removeChild(adminControls);
                this.state.adminControls.delete(articleCard);
                articleCard.classList.remove(this.config.classes.hasControls);
            }
        }

        /**
         * Update toggle button appearance
         */
        updateToggleButton() {
            if (!this.elements.toggleButton) return;

            if (this.state.isAdminMode) {
                this.elements.toggleButton.innerHTML = '<i class="fas fa-times"></i> Exit Admin Mode';
                this.elements.toggleButton.classList.add('active');
                this.elements.toggleButton.title = 'Exit admin mode';
            } else {
                this.elements.toggleButton.innerHTML = '<i class="fas fa-cog"></i> Admin Mode';
                this.elements.toggleButton.classList.remove('active');
                this.elements.toggleButton.title = 'Enter admin mode';
            }
        }

        /**
         * Toggle bulk selection checkboxes
         */
        toggleBulkCheckboxes() {
            const checkboxes = document.querySelectorAll(this.config.selectors.bulkCheckbox);

            checkboxes.forEach(checkbox => {
                checkbox.style.display = this.state.isAdminMode ? 'block' : 'none';

                if (!this.state.isAdminMode) {
                    checkbox.checked = false;
                }
            });
        }

        /**
         * Handle bulk selection changes
         */
        handleBulkSelectionChange(event) {
            if (!event.target.matches(this.config.selectors.bulkCheckbox)) return;

            const articleId = event.target.value;

            if (event.target.checked) {
                this.state.selectedArticles.add(articleId);
            } else {
                this.state.selectedArticles.delete(articleId);
            }

            // Emit selection change event
            this.core.eventBus?.emit('admin:selection:changed', {
                selectedCount: this.state.selectedArticles.size,
                selectedArticles: Array.from(this.state.selectedArticles)
            });
        }

        /**
         * Quick delete article
         */
        async quickDeleteArticle(articleId) {
            if (!confirm(this.config.confirmations.delete)) {
                return;
            }

            try {
                await this.deleteArticle(articleId);
                this.showSuccessMessage(`Article ${articleId} deleted successfully`);

                // Emit deletion event
                this.core.eventBus?.emit('admin:article:deleted', { articleId });

            } catch (error) {
                this.logger.error(`Failed to delete article ${articleId}:`, error);
                this.showErrorMessage('Failed to delete article');
                this.core.handleError(error);
            }
        }

        /**
         * Delete article via form submission
         */
        async deleteArticle(articleId) {
            return new Promise((resolve, reject) => {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '/index.php?page=delete_article';
                form.style.display = 'none';

                form.innerHTML = `
                    <input type="hidden" name="article_id" value="${articleId}">
                    <input type="hidden" name="csrf_token" value="${this.getCsrfToken()}">
                `;

                document.body.appendChild(form);

                // Submit form
                form.submit();

                // Note: In a real implementation, this would be an AJAX call
                resolve();
            });
        }

        /**
         * Extract article ID from article card
         */
        extractArticleId(articleCard) {
            // Try multiple methods to extract article ID
            const titleLink = articleCard.querySelector('.article-title a');
            if (titleLink) {
                const match = titleLink.href.match(/[?&]id=(\d+)/);
                if (match) return match[1];
            }

            // Try data attribute
            const dataId = articleCard.dataset.articleId;
            if (dataId) return dataId;

            // Try checkbox value
            const checkbox = articleCard.querySelector(this.config.selectors.bulkCheckbox);
            if (checkbox) return checkbox.value;

            return null;
        }

        /**
         * Get CSRF token
         */
        getCsrfToken() {
            return this.core.utils?.getCsrfToken() || '';
        }

        /**
         * Show success message
         */
        showSuccessMessage(message) {
            if (this.core.utils?.showNotification) {
                this.core.utils.showNotification(message, 'success');
            } else {
                alert(message);
            }
        }

        /**
         * Show error message
         */
        showErrorMessage(message) {
            if (this.core.utils?.showNotification) {
                this.core.utils.showNotification(message, 'error');
            } else {
                alert(message);
            }
        }

        /**
         * Clear all selections
         */
        clearAllSelections() {
            this.state.selectedArticles.clear();

            const checkboxes = document.querySelectorAll(this.config.selectors.bulkCheckbox);
            checkboxes.forEach(checkbox => {
                checkbox.checked = false;
            });
        }

        /**
         * Handle filters changed from navigation
         */
        handleFiltersChanged(data) {
            // Clear selections when filters change
            this.clearAllSelections();
        }

        /**
         * Handle article deleted event
         */
        handleArticleDeleted(data) {
            const { articleId } = data;

            // Remove from selections
            this.state.selectedArticles.delete(articleId);

            // Find and remove article card
            const articleCards = document.querySelectorAll(this.config.selectors.articleCard);
            articleCards.forEach(card => {
                if (this.extractArticleId(card) === articleId) {
                    card.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                    card.style.opacity = '0';
                    card.style.transform = 'scale(0.95)';

                    setTimeout(() => {
                        if (card.parentNode) {
                            card.parentNode.removeChild(card);
                        }
                    }, 300);
                }
            });
        }

        /**
         * Get admin statistics
         */
        getAdminStats() {
            return {
                isAdminMode: this.state.isAdminMode,
                selectedCount: this.state.selectedArticles.size,
                totalArticles: document.querySelectorAll(this.config.selectors.articleCard).length,
                hasAdminPrivileges: this.hasAdminPrivileges()
            };
        }

        /**
         * Clean up module
         */
        async destroy() {
            this.logger.debug('Destroying admin module...');

            // Disable admin mode if active
            if (this.state.isAdminMode) {
                await this.disableAdminMode();
            }

            // Clear state
            this.state.selectedArticles.clear();
            this.state.adminControls.clear();

            // Remove global functions
            delete window.toggleAdminMode;
            delete window.quickDeleteArticle;

            this.logger.debug('Admin module destroyed');
        }

        /**
         * Get module API
         */
        getAPI() {
            return {
                version: this.version,
                isAdminMode: () => this.state.isAdminMode,
                toggleMode: () => this.toggleAdminMode(),
                getStats: () => this.getAdminStats(),
                getSelectedArticles: () => Array.from(this.state.selectedArticles),
                clearSelections: () => this.clearAllSelections()
            };
        }
    }

    // Export to global scope
    window.NewsAdmin = NewsAdmin;

    console.log('[NewsAdmin] v5.0 module loaded - enhanced admin functionality');

})();
