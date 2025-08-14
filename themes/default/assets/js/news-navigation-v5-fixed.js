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
                    filterUrl: '/page/api/system/filter_articles.php'
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
         * Установка обработчиков событий - ДОРАБОТАНО с отладкой
         */
        setupEventHandlers() {
            // Используем делегирование событий только для новостной страницы
            const newsPage = document.querySelector('[data-page="news"]');
            if (!newsPage) {
                // ДОБАВЛЕНО: Проверяем альтернативные селекторы
                const altContainers = [
                    'body',
                    'main',
                    '.news-page',
                    '.content',
                    '#content'
                ];

                let foundContainer = null;
                for (const selector of altContainers) {
                    foundContainer = document.querySelector(selector);
                    if (foundContainer) {
                        console.log(`[NewsNavigation] Using alternative container: ${selector}`);
                        break;
                    }
                }

                if (!foundContainer) {
                    this.logger.warn('[NewsNavigation] No suitable page container found');
                    return;
                }

                // Используем найденный контейнер
                foundContainer.setAttribute('data-page', 'news');
            }

            const pageContainer = document.querySelector('[data-page="news"]') || document.body;

            // ДОРАБОТАНО: Улучшенная обработка кликов с защитой от дублирования
            pageContainer.addEventListener('click', (event) => {
                // Проверяем, что элемент существует и видим
                if (!event.target || !event.target.offsetParent) return;

                const categoryLink = event.target.closest(this.config.selectors.categoryLink);
                if (categoryLink && !categoryLink.classList.contains('processing')) {
                    event.preventDefault();
                    event.stopPropagation();

                    console.log('[NewsNavigation] Category link clicked:', categoryLink);

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

                    console.log('[NewsNavigation] Pagination link clicked:', paginationLink);

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
         * Обновление контента - ИСПРАВЛЕНО для работы с новым API
         */
        updateContent(data) {
            try {
                // Получаем HTML из правильной структуры ответа API
                const articlesHtml = data.data?.html?.articles_grid || data.html?.articles_grid || data.articles_html;
                const paginationHtml = data.data?.html?.pagination || data.html?.pagination || data.pagination_html;

                console.log('[NewsNavigation] Updating content with data:', data);
                console.log('[NewsNavigation] Articles HTML available:', !!articlesHtml);
                console.log('[NewsNavigation] Pagination HTML available:', !!paginationHtml);

                // ИСПРАВЛЕНО: Ищем различные возможные контейнеры для статей
                let articlesContainer = document.querySelector('.articles-grid');
                if (!articlesContainer) {
                    articlesContainer = document.querySelector('.news-grid');
                }
                if (!articlesContainer) {
                    articlesContainer = document.querySelector('.articles-container');
                }
                if (!articlesContainer) {
                    articlesContainer = document.querySelector('.news-articles');
                }
                if (!articlesContainer) {
                    // Ищем секцию со статьями
                    const articlesSection = document.querySelector('.articles-section');
                    if (articlesSection) {
                        articlesContainer = articlesSection.querySelector('.articles-grid');
                    }
                }

                console.log('[NewsNavigation] Found articles container:', articlesContainer);

                if (articlesContainer && articlesHtml) {
                    // Создаем временный div для парсинга HTML
                    const tempDiv = document.createElement('div');
                    tempDiv.innerHTML = articlesHtml;

                    // Ищем статьи в полученном HTML
                    const newArticles = tempDiv.querySelectorAll('.article-card, .news-item, .post, article, .card');

                    if (newArticles.length > 0) {
                        // Очищаем контейнер и добавляем новые статьи
                        articlesContainer.innerHTML = articlesHtml;

                        // Анимация появления
                        const allArticles = articlesContainer.querySelectorAll('.article-card, .news-item, .post, article, .card');
                        allArticles.forEach((article, index) => {
                            article.style.opacity = '0';
                            article.style.transform = 'translateY(20px)';
                            setTimeout(() => {
                                article.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                                article.style.opacity = '1';
                                article.style.transform = 'translateY(0)';
                            }, index * 50);
                        });
                    } else {
                        // Если не найдены отдельные статьи, заменяем весь контент
                        articlesContainer.innerHTML = articlesHtml;
                    }

                    console.log('[NewsNavigation] Articles content updated successfully');
                } else {
                    console.warn('[NewsNavigation] Articles container not found or no articles HTML');
                    console.log('[NewsNavigation] Container:', articlesContainer);
                    console.log('[NewsNavigation] HTML:', articlesHtml ? 'available' : 'not available');
                }

                // ИСПРАВЛЕНО: Обновляем пагинацию с различными возможными селекторами
                let paginationContainer = document.querySelector('.pagination-section');
                if (!paginationContainer) {
                    paginationContainer = document.querySelector('.pagination-wrapper');
                }
                if (!paginationContainer) {
                    paginationContainer = document.querySelector('.pagination');
                }
                if (!paginationContainer) {
                    paginationContainer = document.querySelector('.page-navigation');
                }

                console.log('[NewsNavigation] Found pagination container:', paginationContainer);

                if (paginationContainer) {
                    if (paginationHtml && paginationHtml.trim() !== '') {
                        paginationContainer.innerHTML = paginationHtml;
                        console.log('[NewsNavigation] Pagination updated');
                    } else {
                        paginationContainer.innerHTML = '';
                        console.log('[NewsNavigation] Pagination cleared (no content)');
                    }
                }

                // ИСПРАВЛЕНО: Обновляем счетчик статей в основном заголовке страницы
                const articleCount = document.querySelector('.article-count, .results-count, .total-count');
                const totalArticles = data.data?.pagination?.total_articles || data.pagination?.total_articles;

                if (articleCount && totalArticles !== undefined) {
                    articleCount.textContent = `(${totalArticles} articles)`;
                }

                // ДОБАВЛЕНО: Обновляем заголовок раздела в зависимости от выбранной категории
                const sectionTitle = document.querySelector('.news-title, h1, .page-title');
                if (sectionTitle && data.data?.filters?.category) {
                    const categoryName = data.data.filters.category;
                    if (categoryName) {
                        // Находим название категории из данных
                        const categories = data.data?.categories || [];
                        const currentCategory = categories.find(cat => cat.slug === categoryName);
                        const categoryDisplayName = currentCategory ? currentCategory.name : categoryName.charAt(0).toUpperCase() + categoryName.slice(1);

                        const icon = sectionTitle.querySelector('i');
                        const iconHtml = icon ? icon.outerHTML : '<i class="fas fa-newspaper"></i>';
                        sectionTitle.innerHTML = `${iconHtml} ${categoryDisplayName} News`;
                    }
                } else if (sectionTitle && (!data.data?.filters?.category || data.data.filters.category === '')) {
                    // Возвращаем обратно заголовок "News Hub" для всех новостей
                    const icon = sectionTitle.querySelector('i');
                    const iconHtml = icon ? icon.outerHTML : '<i class="fas fa-newspaper"></i>';
                    sectionTitle.innerHTML = `${iconHtml} News Hub`;
                }

                // Emit событие о успешном обновлении
                if (this.core && this.core.emit) {
                    this.core.emit('navigation:content:updated', {
                        data: data,
                        timestamp: Date.now()
                    });
                }

                this.logger.debug('[NewsNavigation] Content updated successfully');

            } catch (error) {
                this.logger.error('[NewsNavigation] Content update failed:', error);
                throw error;
            }
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
