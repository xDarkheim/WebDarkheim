/**
 * News Search Module v5.0.1-FIXED - Simplified Search Integration
 * Упрощенный модуль поиска для работы с Navigation v5.0.1-FИКСИРОВАН
 *
 * @author Darkheim Studio
 * @version 5.0.1-fixed
 * @since 2025-08-10
 */

(function() {
    'use strict';

    /**
     * Упрощенный модуль поиска по новостям
     */
    class NewsSearchFixed {
        constructor(core) {
            this.core = core;
            this.version = '5.0.1-fixed';
            this.namespace = 'NewsSearchFixed';

            this.config = {
                selectors: {
                    searchForm: '.news-search-form',
                    searchInput: '.search-input',
                    searchButton: '.search-button, .search-btn',
                    searchSubmit: '.search-submit-btn',
                    sortSelect: '.sort-select'
                }
            };

            this.logger = this.core?.logger || console;
            this.isSearching = false;
        }

        /**
         * Инициализация модуля поиска
         */
        async init() {
            try {
                this.logger.info('[NewsSearch] Initializing search module...');
                
                this.setupSearchHandlers();
                
                this.logger.success('[NewsSearch] Search module initialized');
                return true;
            } catch (error) {
                this.logger.error('[NewsSearch] Initialization failed:', error.message);
                return false;
            }
        }

        /**
         * Установка обработчиков поиска - ИСПРАВЛЕНО
         */
        setupSearchHandlers() {
            const newsPage = document.querySelector('[data-page="news"]');
            if (!newsPage) {
                this.logger.warn('[NewsSearch] News page container not found');
                return;
            }

            // ИСПРАВЛЕНО: Обработка отправки формы поиска
            newsPage.addEventListener('submit', (event) => {
                if (event.target.matches(this.config.selectors.searchForm)) {
                    event.preventDefault();
                    event.stopPropagation();
                    this.handleSearchSubmit();
                }
            });

            // ИСПРАВЛЕНО: Обработка клика по кнопке поиска
            newsPage.addEventListener('click', (event) => {
                const searchButton = event.target.closest(`${this.config.selectors.searchButton}, .search-submit-btn`);
                if (searchButton) {
                    event.preventDefault();
                    event.stopPropagation();
                    this.handleSearchSubmit();
                }
            });

            // ИСПРАВЛЕНО: Обработка Enter в поле поиска
            newsPage.addEventListener('keypress', (event) => {
                if (event.target.matches(this.config.selectors.searchInput) && event.key === 'Enter') {
                    event.preventDefault();
                    this.handleSearchSubmit();
                }
            });

            // ДОБАВЛЕНО: Обработка изменения сортировки
            newsPage.addEventListener('change', (event) => {
                if (event.target.matches(this.config.selectors.sortSelect)) {
                    this.handleSearchSubmit();
                }
            });

            this.logger.debug('[NewsSearch] Search handlers setup completed');
        }

        /**
         * Обработка отправки поиска - ИСПРАВЛЕНО
         */
        async handleSearchSubmit() {
            if (this.isSearching) {
                this.logger.debug('[NewsSearch] Search already in progress, skipping');
                return;
            }

            this.isSearching = true;
            this.logger.debug('[NewsSearch] Search submit triggered');

            try {
                const searchInput = document.querySelector(this.config.selectors.searchInput);
                const sortSelect = document.querySelector(this.config.selectors.sortSelect);

                if (!searchInput) {
                    throw new Error('Search input not found');
                }

                const query = searchInput.value.trim();
                const sortValue = sortSelect ? sortSelect.value : 'date_desc';

                this.logger.info('[NewsSearch] Performing search:', { query, sort: sortValue });

                // Получаем текущие параметры из URL
                const urlParams = new URLSearchParams(window.location.search);
                const currentFilters = {
                    category: urlParams.get('category') || '',
                    search: query,
                    sort: sortValue,
                    page_num: 1 // Сбрасываем на первую страницу при поиске
                };

                // Показываем индикатор загрузки
                this.showSearchLoading();

                // ИСПРАВЛЕНО: Прямой AJAX запрос к API с лучшей обработкой ошибок
                const data = await this.performSearchRequest(currentFilters);

                // Проверяем успешность ответа
                if (!data || !data.success) {
                    throw new Error(data?.error || 'Invalid API response');
                }

                // Обновляем контент
                await this.updateSearchResults(data, currentFilters);

                // Уведомляем о результатах поиска
                if (this.core && this.core.emit) {
                    this.core.emit('search:results:updated', {
                        query: query,
                        results: data.summary?.total_results || 0,
                        filters: currentFilters
                    });
                }

                this.logger.success('[NewsSearch] Search completed:', { query, results: data.summary?.total_results || 0 });

            } catch (error) {
                this.logger.error('[NewsSearch] Search failed:', error.message);
                this.showSearchError(`Search failed: ${error.message}`);
            } finally {
                this.hideSearchLoading();
                this.isSearching = false;
            }
        }

        /**
         * ИСПРАВЛЕНО: Прямой AJAX запрос для поиска с улучшенной обработкой ошибок
         */
        async performSearchRequest(filters) {
            const params = new URLSearchParams();

            // Добавляем только непустые параметры
            Object.entries(filters).forEach(([key, value]) => {
                if (value && value !== '') {
                    params.append(key, value);
                }
            });

            const url = `/page/api/filter_articles.php?${params.toString()}`;
            this.logger.debug('[NewsSearch] Search API request:', url);

            try {
                const response = await fetch(url, {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    credentials: 'same-origin'
                });

                this.logger.debug('[NewsSearch] Response status:', response.status);

                if (!response.ok) {
                    const errorText = await response.text();
                    this.logger.error('[NewsSearch] HTTP Error:', {
                        status: response.status,
                        statusText: response.statusText,
                        response: errorText.substring(0, 500)
                    });
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }

                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    const responseText = await response.text();
                    this.logger.error('[NewsSearch] Invalid content type:', {
                        contentType,
                        response: responseText.substring(0, 500)
                    });
                    throw new Error('API returned invalid content type. Expected JSON.');
                }

                const data = await response.json();
                this.logger.debug('[NewsSearch] API Response:', data);

                if (!data.success) {
                    throw new Error(data.error || 'Unknown server error');
                }

                return data;

            } catch (error) {
                if (error.name === 'SyntaxError') {
                    this.logger.error('[NewsSearch] JSON Parse Error:', error.message);
                    throw new Error('Server returned invalid JSON response');
                } else if (error.name === 'TypeError') {
                    this.logger.error('[NewsSearch] Network Error:', error.message);
                    throw new Error('Network error. Please check your connection.');
                } else {
                    throw error;
                }
            }
        }

        /**
         * ИСПРАВЛЕНО: Обновление результатов поиска
         */
        async updateSearchResults(data, filters) {
            try {
                // Пытаемся использовать модуль навигации если доступен
                const navigationModule = this.core?.getModule?.('navigation');

                if (navigationModule && typeof navigationModule.updateContent === 'function') {
                    this.logger.debug('[NewsSearch] Using navigation module for content update');
                    navigationModule.updateContent(data);
                    navigationModule.updateUrl(filters);
                    if (navigationModule.state) {
                        navigationModule.state.currentFilters = filters;
                    }
                    navigationModule.updateActiveStates?.();
                } else {
                    this.logger.debug('[NewsSearch] Using direct content update');
                    this.updateContentDirect(data, filters);
                }
            } catch (error) {
                this.logger.error('[NewsSearch] Content update failed:', error.message);
                throw new Error('Failed to update search results');
            }
        }

        /**
         * ИСПРАВЛЕНО: Прямое обновление контента (fallback)
         */
        updateContentDirect(data, filters) {
            try {
                // Обновляем основной контейнер статей
                const mainContent = document.querySelector('.news-main');
                if (!mainContent) {
                    throw new Error('News main container not found');
                }

                // Ищем контейнер для статей
                let articlesContainer = mainContent.querySelector('.articles-grid');
                if (!articlesContainer) {
                    // Если нет grid, ищем любой контейнер статей
                    articlesContainer = mainContent.querySelector('.articles-container, .news-articles');
                }

                if (articlesContainer && data.articles_html) {
                    articlesContainer.innerHTML = data.articles_html;
                    this.logger.debug('[NewsSearch] Articles updated');
                } else if (data.articles_html) {
                    // Если контейнер не найден, обновляем весь main
                    const searchSection = mainContent.querySelector('.search-filters');
                    if (searchSection) {
                        // Сохраняем поиск и заменяем остальное
                        const afterSearch = searchSection.nextElementSibling;
                        if (afterSearch) {
                            afterSearch.outerHTML = data.articles_html;
                        }
                    }
                }

                // Обновляем пагинацию
                const paginationContainer = mainContent.querySelector('.pagination-section, .pagination-wrapper');
                if (paginationContainer) {
                    if (data.pagination_html && data.pagination_html.trim()) {
                        paginationContainer.innerHTML = data.pagination_html;
                    } else {
                        paginationContainer.innerHTML = '';
                    }
                    this.logger.debug('[NewsSearch] Pagination updated');
                }

                // Обновляем URL
                this.updateBrowserUrl(filters);

                this.logger.debug('[NewsSearch] Content updated directly');

            } catch (error) {
                this.logger.error('[NewsSearch] Direct content update failed:', error.message);
                throw error;
            }
        }

        /**
         * ДОБАВЛЕНО: Обновление URL в браузере
         */
        updateBrowserUrl(filters) {
            try {
                const params = new URLSearchParams();
                params.set('page', 'news');

                Object.entries(filters).forEach(([key, value]) => {
                    if (value && value !== '' && (key !== 'page_num' || value > 1)) {
                        params.set(key, value);
                    }
                });

                const newUrl = `${window.location.pathname}?${params.toString()}`;
                history.pushState(filters, '', newUrl);
                this.logger.debug('[NewsSearch] URL updated:', newUrl);
            } catch (error) {
                this.logger.warn('[NewsSearch] Failed to update URL:', error.message);
            }
        }

        /**
         * ИСПРАВЛЕНО: Показ индикатора загрузки
         */
        showSearchLoading() {
            const searchForm = document.querySelector(this.config.selectors.searchForm);
            if (searchForm) {
                searchForm.classList.add('loading');
            }

            const submitBtn = document.querySelector(this.config.selectors.searchSubmit);
            if (submitBtn) {
                submitBtn.disabled = true;
                const originalContent = submitBtn.innerHTML;
                submitBtn.dataset.originalContent = originalContent;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Searching...';
            }

            const searchInput = document.querySelector(this.config.selectors.searchInput);
            if (searchInput) {
                searchInput.disabled = true;
            }
        }

        /**
         * ИСПРАВЛЕНО: Скрытие индикатора загрузки
         */
        hideSearchLoading() {
            const searchForm = document.querySelector(this.config.selectors.searchForm);
            if (searchForm) {
                searchForm.classList.remove('loading');
            }

            const submitBtn = document.querySelector(this.config.selectors.searchSubmit);
            if (submitBtn) {
                submitBtn.disabled = false;
                const originalContent = submitBtn.dataset.originalContent || '<i class="fas fa-filter"></i> Apply Filters';
                submitBtn.innerHTML = originalContent;
            }

            const searchInput = document.querySelector(this.config.selectors.searchInput);
            if (searchInput) {
                searchInput.disabled = false;
            }
        }

        /**
         * ИСПРАВЛЕНО: Показ ошибки поиска через существующую FlashMessage систему
         */
        showSearchError(message) {
            this.logger.error('[NewsSearch] Error:', message);

            // Используем существующую систему FlashMessage (toast)
            if (window.FlashMessage && typeof window.FlashMessage.show === 'function') {
                window.FlashMessage.show(message, 'error');
            } else if (window.showFlashMessage && typeof window.showFlashMessage === 'function') {
                window.showFlashMessage(message, 'error');
            } else if (window.toast && typeof window.toast.error === 'function') {
                window.toast.error(message);
            } else {
                // Fallback: console error если система FlashMessage недоступна
                console.error('[NewsSearch] FlashMessage system not available:', message);
                
                // Простое alert как последний fallback
                alert('Search Error: ' + message);
            }
        }

        /**
         * ДОБАВЛЕНО: Публичные методы для внешнего использования
         */
        isInitialized() {
            return true;
        }

        getVersion() {
            return this.version;
        }
    }

    // Автоматическая регистрация в системе при загрузке
    if (window.NewsCoreAPI) {
        window.NewsCoreAPI.registerModule('search', NewsSearchFixed);
    }

    // Экспорт для прямого использования
    window.NewsSearchFixed = NewsSearchFixed;

})();
