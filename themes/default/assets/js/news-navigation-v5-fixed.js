/**
 * News Navigation Module v5.0 - SIMPLIFIED & FIXED
 * Упрощенная и надежная система навигации для категорий
 *
 * @author Darkheim Studio
 * @version 5.0.1-fixed
 * @since 2025-08-10
 */

(function() {
    'use strict';

    /**
     * Упрощенная система навигации по новостям
     */
    class NewsNavigationFixed {
        constructor(core) {
            this.core = core;
            this.version = '5.0.1-fixed';
            this.namespace = 'NewsNavigationFixed';

            this.config = {
                selectors: {
                    newsGrid: '.articles-grid, .news-grid',
                    categoryLink: '.category-link, .filter-tab',
                    paginationLink: '.pagination-link, .pagination a',
                    sortSelect: '.sort-select, select[name="sort"]',
                    searchForm: '.news-search-form'
                },
                api: {
                    filterUrl: '/page/api/filter_articles.php'
                }
            };

            this.state = {
                isLoading: false,
                currentFilters: this.parseCurrentFilters()
            };

            this.logger = this.core?.logger || console;
        }

        /**
         * Инициализация модуля
         */
        async init() {
            try {
                this.logger.info('[NewsNavigation] Initializing simplified navigation...');
                
                this.setupEventHandlers();
                this.setupPopstateHandler();
                
                this.logger.success('[NewsNavigation] Navigation module initialized');
                return true;
            } catch (error) {
                this.logger.error('[NewsNavigation] Initialization failed:', error);
                throw error;
            }
        }

        /**
         * Парсинг текущих фильтров из URL
         */
        parseCurrentFilters() {
            const params = new URLSearchParams(window.location.search);
            return {
                category: params.get('category') || '',
                search: params.get('search') || '',
                sort: params.get('sort') || 'date_desc',
                page_num: parseInt(params.get('page_num') || '1')
            };
        }

        /**
         * Установка обработчиков событий - ДОРАБОТАНО
         */
        setupEventHandlers() {
            // Используем делегирование событий только для новостной страницы
            const newsPage = document.querySelector('[data-page="news"]');
            if (!newsPage) {
                this.logger.warn('[NewsNavigation] News page container not found');
                return;
            }

            // ДОРАБОТАНО: Улучшенная обработка кликов с защитой от дублирования
            newsPage.addEventListener('click', (event) => {
                // Проверяем, что элемент существует и видим
                if (!event.target || !event.target.offsetParent) return;

                const categoryLink = event.target.closest(this.config.selectors.categoryLink);
                if (categoryLink && !categoryLink.classList.contains('processing')) {
                    event.preventDefault();
                    event.stopPropagation();

                    // ДОРАБОТАНО: Защита от множественных кликов
                    categoryLink.classList.add('processing');
                    setTimeout(() => categoryLink.classList.remove('processing'), 1000);

                    this.handleCategoryClick(categoryLink);
                    return;
                }

                const paginationLink = event.target.closest(this.config.selectors.paginationLink);
                if (paginationLink && !paginationLink.classList.contains('processing')) {
                    event.preventDefault();
                    event.stopPropagation();

                    // ДОРАБОТАНО: Защита от множественных кликов
                    paginationLink.classList.add('processing');
                    setTimeout(() => paginationLink.classList.remove('processing'), 1000);

                    this.handlePaginationClick(paginationLink);
                    return;
                }
            }, { passive: false });

            // ДОРАБОТАНО: Улучшенная обработка изменений с debounce
            let sortChangeTimeout;
            newsPage.addEventListener('change', (event) => {
                if (event.target.matches(this.config.selectors.sortSelect)) {
                    clearTimeout(sortChangeTimeout);
                    sortChangeTimeout = setTimeout(() => {
                        this.handleSortChange();
                    }, 300); // Debounce 300ms
                }
            });

            // ДОРАБОТАНО: Улучшенная обработка форм
            newsPage.addEventListener('submit', (event) => {
                if (event.target.matches(this.config.selectors.searchForm)) {
                    event.preventDefault();
                    event.stopPropagation();
                    this.handleSearchSubmit();
                }
            });

            this.logger.debug('[NewsNavigation] Event handlers setup completed');
        }

        /**
         * Обработка клика по категории - УПРОЩЕНО
         */
        async handleCategoryClick(link) {
            if (this.state.isLoading) return;

            const category = link.getAttribute('data-category') || '';
            
            // Показываем loading state
            this.setLoadingState(true);
            
            try {
                // Выполняем AJAX запрос
                const newFilters = {
                    ...this.state.currentFilters,
                    category: category,
                    page_num: 1 // Сбрасываем на первую страницу
                };

                const data = await this.performAjaxRequest(newFilters);
                
                // Обновляем контент
                this.updateContent(data);
                
                // Обновляем URL
                this.updateUrl(newFilters);
                
                // Обновляем состояние
                this.state.currentFilters = newFilters;
                this.updateActiveStates();

                this.logger.info('[NewsNavigation] Category switched successfully:', category);

            } catch (error) {
                this.logger.error('[NewsNavigation] Category switch failed:', error);
                this.showError('Failed to load articles. Please try again.');
            } finally {
                this.setLoadingState(false);
            }
        }

        /**
         * Обработка клика по пагинации
         */
        async handlePaginationClick(link) {
            if (this.state.isLoading) return;

            const href = link.getAttribute('href');
            const url = new URL(href, window.location.origin);
            const pageNum = parseInt(url.searchParams.get('page_num') || '1');

            this.setLoadingState(true);

            try {
                const newFilters = {
                    ...this.state.currentFilters,
                    page_num: pageNum
                };

                const data = await this.performAjaxRequest(newFilters);
                this.updateContent(data);
                this.updateUrl(newFilters);
                this.state.currentFilters = newFilters;

                // Плавный скролл к началу статей
                const articlesGrid = document.querySelector(this.config.selectors.newsGrid);
                if (articlesGrid) {
                    articlesGrid.scrollIntoView({ 
                        behavior: 'smooth', 
                        block: 'start' 
                    });
                }

            } catch (error) {
                this.logger.error('[NewsNavigation] Pagination failed:', error);
                this.showError('Failed to load page. Please try again.');
            } finally {
                this.setLoadingState(false);
            }
        }

        /**
         * Обработка изменения сортировки
         */
        async handleSortChange() {
            if (this.state.isLoading) return;

            const sortSelect = document.querySelector(this.config.selectors.sortSelect);
            if (!sortSelect) return;

            this.setLoadingState(true);

            try {
                const newFilters = {
                    ...this.state.currentFilters,
                    sort: sortSelect.value,
                    page_num: 1 // Сбрасываем на первую страницу
                };

                const data = await this.performAjaxRequest(newFilters);
                this.updateContent(data);
                this.updateUrl(newFilters);
                this.state.currentFilters = newFilters;
                this.updateActiveStates();

            } catch (error) {
                this.logger.error('[NewsNavigation] Sort change failed:', error);
                this.showError('Failed to sort articles. Please try again.');
            } finally {
                this.setLoadingState(false);
            }
        }

        /**
         * Обработка отправки формы поиска
         */
        async handleSearchSubmit() {
            if (this.state.isLoading) return;

            const searchInput = document.querySelector('.search-input');
            if (!searchInput) return;

            this.setLoadingState(true);

            try {
                const newFilters = {
                    ...this.state.currentFilters,
                    search: searchInput.value.trim(),
                    page_num: 1 // Сбрасываем на первую страницу
                };

                const data = await this.performAjaxRequest(newFilters);
                this.updateContent(data);
                this.updateUrl(newFilters);
                this.state.currentFilters = newFilters;
                this.updateActiveStates();

            } catch (error) {
                this.logger.error('[NewsNavigation] Search failed:', error);
                this.showError('Search failed. Please try again.');
            } finally {
                this.setLoadingState(false);
            }
        }

        /**
         * Выполнение AJAX запроса - ДОРАБОТАНО с улучшенной диагностикой
         */
        async performAjaxRequest(filters) {
            const params = new URLSearchParams();
            
            Object.entries(filters).forEach(([key, value]) => {
                if (value) {
                    params.append(key, value);
                }
            });

            const url = `${this.config.api.filterUrl}?${params.toString()}`;
            
            this.logger.debug('[NewsNavigation] AJAX request:', url);

            try {
                const response = await fetch(url, {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                // ДОРАБОТАНО: Более детальная диагностика ошибок
                this.logger.debug('[NewsNavigation] Response status:', response.status);

                if (!response.ok) {
                    const errorText = await response.text();
                    this.logger.error('[NewsNavigation] HTTP Error Response:', errorText);

                    let errorMessage = `HTTP ${response.status}: ${response.statusText}`;

                    try {
                        const errorData = JSON.parse(errorText);
                        if (errorData.error) {
                            errorMessage = errorData.error;
                        }
                    } catch (e) {
                        if (errorText && errorText.length < 500) {
                            errorMessage = errorText;
                        }
                    }

                    throw new Error(errorMessage);
                }

                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    const responseText = await response.text();
                    this.logger.error('[NewsNavigation] Invalid content type. Response:', responseText.substring(0, 200));
                    throw new Error('API returned invalid content type. Expected JSON.');
                }

                const data = await response.json();
                this.logger.debug('[NewsNavigation] API Response:', data);

                if (!data.success) {
                    throw new Error(data.error || 'Unknown server error');
                }

                return data;

            } catch (error) {
                this.logger.error('[NewsNavigation] AJAX request failed:', {
                    url: url,
                    error: error.message,
                    stack: error.stack
                });
                throw error;
            }
        }

        /**
         * Обновление контента - ДОРАБОТАНО для работы с реальным дизайном
         */
        updateContent(data) {
            // ДОРАБОТАНО: Обновляем только articles-grid, а не всю articles-section
            const articlesGrid = document.querySelector('.articles-grid');
            if (articlesGrid && data.articles_html) {
                // Сохраняем существующую структуру и обновляем только содержимое
                articlesGrid.innerHTML = data.articles_html;

                // ДОРАБОТАНО: Добавляем анимацию появления для новых карточек
                const newCards = articlesGrid.querySelectorAll('.article-card');
                newCards.forEach((card, index) => {
                    card.style.opacity = '0';
                    card.style.transform = 'translateY(20px)';
                    setTimeout(() => {
                        card.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                        card.style.opacity = '1';
                        card.style.transform = 'translateY(0)';
                    }, index * 50);
                });
            }

            // ДОРАБОТАНО: Обновляем пагинацию, сохраняя структуру
            const paginationContainer = document.querySelector('.pagination-section');
            if (paginationContainer) {
                if (data.pagination_html) {
                    paginationContainer.innerHTML = data.pagination_html;
                } else {
                    paginationContainer.innerHTML = '';
                }
            }

            // ДОРАБОТАНО: Обновляем счетчик статей в заголовке
            const articleCount = document.querySelector('.article-count');
            if (articleCount && data.summary) {
                articleCount.textContent = `(${data.summary.total_results} articles)`;
            }

            // ИСПРАВЛЕНО: Безопасный emit события
            if (this.core && this.core.emit) {
                this.core.emit('navigation:content:updated', {
                    data: data,
                    timestamp: Date.now()
                });
            }

            this.logger.debug('[NewsNavigation] Content updated with real design preserved');
        }

        /**
         * Обновление URL без перезагрузки
         */
        updateUrl(filters) {
            const params = new URLSearchParams();
            params.set('page', 'news');

            Object.entries(filters).forEach(([key, value]) => {
                if (value && key !== 'page_num' || (key === 'page_num' && value > 1)) {
                    params.set(key, value);
                }
            });

            const newUrl = `${window.location.pathname}?${params.toString()}`;
            history.pushState(filters, '', newUrl);
        }

        /**
         * Обновление активных состояний
         */
        updateActiveStates() {
            // Обновляем активную категорию
            document.querySelectorAll(this.config.selectors.categoryLink).forEach(link => {
                const category = link.getAttribute('data-category') || '';
                link.classList.toggle('active', category === this.state.currentFilters.category);
            });

            // Обновляем значение сортировки
            const sortSelect = document.querySelector(this.config.selectors.sortSelect);
            if (sortSelect) {
                sortSelect.value = this.state.currentFilters.sort;
            }
        }

        /**
         * Установка состояния загрузки
         */
        setLoadingState(loading) {
            this.state.isLoading = loading;
            
            const newsPage = document.querySelector('[data-page="news"]');
            if (newsPage) {
                newsPage.classList.toggle('loading', loading);
            }

            // Показываем/скрываем индикатор загрузки
            if (loading) {
                this.showLoadingIndicator();
            } else {
                this.hideLoadingIndicator();
            }
        }

        /**
         * Показ индикатора загрузки
         */
        showLoadingIndicator() {
            let indicator = document.querySelector('.news-loading-indicator');
            if (!indicator) {
                indicator = document.createElement('div');
                indicator.className = 'news-loading-indicator';
                indicator.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
                
                const newsGrid = document.querySelector(this.config.selectors.newsGrid);
                if (newsGrid) {
                    newsGrid.parentElement.appendChild(indicator);
                }
            }
            indicator.style.display = 'block';
        }

        /**
         * Скрытие индикатора загрузки
         */
        hideLoadingIndicator() {
            const indicator = document.querySelector('.news-loading-indicator');
            if (indicator) {
                indicator.style.display = 'none';
            }
        }

        /**
         * Показ ошибки
         */
        showError(message) {
            // Можно интегрировать с системой уведомлений
            console.error('[NewsNavigation] Error:', message);
            
            // Простое уведомление (можно заменить на toast)
            alert(message);
        }

        /**
         * Обработка browser back/forward
         */
        setupPopstateHandler() {
            window.addEventListener('popstate', (event) => {
                if (event.state) {
                    this.state.currentFilters = event.state;
                    this.performAjaxRequest(this.state.currentFilters)
                        .then(data => {
                            this.updateContent(data);
                            this.updateActiveStates();
                        })
                        .catch(error => {
                            this.logger.error('[NewsNavigation] Popstate navigation failed:', error);
                            window.location.reload();
                        });
                }
            });
        }

        /**
         * Публичные методы для интеграции
         */
        getCurrentFilters() {
            return { ...this.state.currentFilters };
        }

        isNavigationLoading() {
            return this.state.isLoading;
        }
    }

    // Регистрируем модуль в News Core
    if (window.NewsCore) {
        window.NewsCore.registerModule('navigation', NewsNavigationFixed);
    } else {
        // Fallback для прямого использования
        window.NewsNavigationFixed = NewsNavigationFixed;
    }

})();
