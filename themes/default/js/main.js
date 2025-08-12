/**
 * Modern Page Transition System v2.0 for Darkheim Studio
 * Completely rewritten architecture with better performance and reliability
 */

(function() {
    'use strict';

    // Предотвращение множественной инициализации
    if (window.PageTransitionManager) {
        console.log('[PageTransitions] Already initialized');
        return;
    }

    /**
     * Основной класс управления переходами
     */
    class PageTransitionManager {
        constructor() {
            this.config = {
                animationDuration: 300,
                navigationTimeout: 8000,
                debounceTime: 300,
                progressBarDuration: 2500
            };

            // Состояние системы
            this.state = {
                isTransitioning: false,
                isPopstateHandling: false,
                lastNavigationTime: 0,
                currentUrl: window.location.href,
                scrollPositions: new Map()
            };

            // Элементы UI
            this.elements = {
                progressBar: null,
                loadingOverlay: null
            };

            // Обработчики событий
            this.handlers = new Map();

            // Инициализация
            this.init();
        }

        /**
         * Инициализация системы
         */
        init() {
            console.log('[PageTransitions] v2.0 initializing...');

            try {
                this.setupEventHandlers();
                this.initializeLinks();
                this.createUI();
                this.registerWithGlobalState();

                console.log('[PageTransitions] v2.0 initialized successfully');
            } catch (error) {
                console.error('[PageTransitions] Initialization failed:', error);
                this.handleCriticalError(error);
            }
        }

        /**
         * Настройка обработчиков событий
         */
        setupEventHandlers() {
            // Очищаем старые обработчики
            this.cleanupHandlers();

            // Popstate для навигации браузера
            this.handlers.set('popstate', (event) => this.handlePopstate(event));
            window.addEventListener('popstate', this.handlers.get('popstate'), { passive: true });

            // Beforeunload для сохранения состояния
            this.handlers.set('beforeunload', () => this.handleBeforeUnload());
            window.addEventListener('beforeunload', this.handlers.get('beforeunload'), { passive: true });

            // Load для завершения переходов
            this.handlers.set('load', () => this.handlePageLoad());
            window.addEventListener('load', this.handlers.get('load'), { once: true });

            // Visibility change для recovery
            this.handlers.set('visibilitychange', () => this.handleVisibilityChange());
            document.addEventListener('visibilitychange', this.handlers.get('visibilitychange'), { passive: true });
        }

        /**
         * Инициализация ссылок для плавных переходов
         */
        initializeLinks() {
            const links = document.querySelectorAll('a[href]');

            links.forEach(link => {
                if (this.shouldEnableTransition(link)) {
                    link.addEventListener('click', (event) => this.handleLinkClick(event, link));
                }
            });

            console.log(`[PageTransitions] Enabled transitions for ${links.length} links`);
        }

        /**
         * Проверка, нужно ли включать переход для ссылки
         */
        shouldEnableTransition(link) {
            const href = link.getAttribute('href');

            // КРИТИЧЕСКИ ВАЖНО: Проверяем атрибуты новостной системы ПЕРВЫМИ
            if (link.hasAttribute('data-no-transition')) {
                console.log('[PageTransitions] Link blocked by data-no-transition:', link.href);
                return false;
            }

            if (link.hasAttribute('data-news-ajax')) {
                console.log('[PageTransitions] Link blocked by data-news-ajax:', link.href);
                return false;
            }

            // ИСПРАВЛЕНО: Более точная проверка новостных ссылок
            if (href && href.includes('page=news')) {
                // Проверяем, это внутренняя навигация новостей или внешний переход
                const urlParams = new URLSearchParams(href.split('?')[1] || '');
                const hasNewsFilters = urlParams.has('category') ||
                                     urlParams.has('search') ||
                                     urlParams.has('sort') ||
                                     urlParams.has('page_num');

                if (hasNewsFilters) {
                    console.log('[PageTransitions] News internal navigation detected, blocking:', link.href);
                    return false;
                }
            }

            // ИСПРАВЛЕНО: Проверяем CSS классы новостных элементов
            if (link.classList.contains('filter-tab') ||
                link.classList.contains('pagination-link') ||
                link.classList.contains('category-link')) {
                console.log('[PageTransitions] News component link detected, blocking:', link.href);
                return false;
            }

            // ИСПРАВЛЕНО: Проверяем родительские элементы новостей
            if (link.closest('.search-form') ||
                link.closest('.pagination') ||
                link.closest('.category-filter') ||
                link.closest('.news-filters')) {
                console.log('[PageTransitions] Link inside news component, blocking:', link.href);
                return false;
            }

            // ОБНОВЛЕНО: Проверяем глобальное состояние изоляции новостей
            if (window.Darkheim && window.Darkheim.GlobalState) {
                const newsPageIntegrated = window.Darkheim.GlobalState.get('ui.newsPage.integrated');
                const isOnNewsPage = document.querySelector('.news-page');

                if (isOnNewsPage && newsPageIntegrated) {
                    console.log('[PageTransitions] On integrated news page, allowing external links only');
                    // На интегрированной странице новостей уже проверили выше
                }
            }

            // Пропускаем внешние ссылки
            if (!href || href.startsWith('http') || href.startsWith('#') || href.startsWith('mailto:')) {
                return false;
            }

            // Пропускаем ссылки с target="_blank"
            if (link.target === '_blank') {
                return false;
            }

            // Пропускаем ссылки внутри TinyMCE
            if (link.closest('.tox-tinymce, .mce-container, .text-editor-wrapper')) {
                return false;
            }

            console.log('[PageTransitions] Link approved for transition:', link.href);
            return true;
        }

        /**
         * Обработка клика по ссылке
         */
        handleLinkClick(event, link) {
            const url = link.getAttribute('href');

            if (this.canNavigate()) {
                event.preventDefault();
                this.navigate(url);
                this.addRippleEffect(event, link);
            }
        }

        /**
         * Проверка возможности навигации
         */
        canNavigate() {
            const now = Date.now();
            const timeSinceLastNav = now - this.state.lastNavigationTime;

            return !this.state.isTransitioning &&
                   !this.state.isPopstateHandling &&
                   timeSinceLastNav > this.config.debounceTime;
        }

        /**
         * Основной метод навигации
         */
        async navigate(url) {
            console.log('[PageTransitions] Navigating to:', url);

            try {
                this.state.isTransitioning = true;
                this.state.lastNavigationTime = Date.now();

                // Сохраняем текущую позицию прокрутки
                this.saveScrollPosition();

                // Очищаем временные сообщения
                this.clearTemporaryMessages();

                // Показываем анимацию загрузки
                await this.showLoadingAnimation();

                // Переходим на новую страницу
                window.location.href = url;

            } catch (error) {
                console.error('[PageTransitions] Navigation error:', error);
                this.resetState();
                // Fallback к обычной навигации
                window.location.href = url;
            }
        }

        /**
         * Показ анимации загрузки
         */
        async showLoadingAnimation() {
            // Показываем прогресс бар
            this.showProgressBar();

            // Анимация исчезновения контента
            await this.fadeOutContent();

            // Показываем overlay
            this.showLoadingOverlay();
        }

        /**
         * Показ прогресс бара
         */
        showProgressBar() {
            if (!this.elements.progressBar) {
                this.elements.progressBar = this.createProgressBar();
            }

            const bar = this.elements.progressBar;
            bar.style.width = '0%';
            bar.classList.add('active');

            // Анимированный прогресс
            let progress = 0;
            const animate = () => {
                if (progress < 80 && this.state.isTransitioning) {
                    progress += Math.random() * 15 + 5;
                    bar.style.width = Math.min(progress, 80) + '%';
                    requestAnimationFrame(animate);
                }
            };

            requestAnimationFrame(animate);

            // Автозавершение
            setTimeout(() => {
                if (bar.parentNode) {
                    bar.style.width = '100%';
                    setTimeout(() => this.hideProgressBar(), 300);
                }
            }, this.config.progressBarDuration);
        }

        /**
         * Создание прогресс бара
         */
        createProgressBar() {
            const bar = document.createElement('div');
            bar.id = 'page-transition-progress';
            bar.className = 'page-transition-progress';
            bar.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 0%;
                height: 3px;
                background: linear-gradient(90deg, #007bff, #00d4ff);
                z-index: 10000;
                transition: width 0.3s ease, opacity 0.3s ease;
                opacity: 0;
            `;
            document.body.appendChild(bar);

            // Показываем с анимацией
            requestAnimationFrame(() => {
                bar.style.opacity = '1';
            });

            return bar;
        }

        /**
         * Скрытие прогресс бара
         */
        hideProgressBar() {
            const bar = this.elements.progressBar;
            if (bar) {
                bar.style.opacity = '0';
                setTimeout(() => {
                    if (bar.parentNode) {
                        bar.parentNode.removeChild(bar);
                    }
                    this.elements.progressBar = null;
                }, 300);
            }
        }

        /**
         * Анимация исчезновения контента
         */
        async fadeOutContent() {
            return new Promise(resolve => {
                const content = document.querySelector('main, .main-content, #content') || document.body;

                content.style.transition = `opacity ${this.config.animationDuration}ms ease-out`;
                content.style.opacity = '0.7';

                setTimeout(resolve, this.config.animationDuration);
            });
        }

        /**
         * Показ loading overlay
         */
        showLoadingOverlay() {
            if (!this.elements.loadingOverlay) {
                this.elements.loadingOverlay = this.createLoadingOverlay();
            }

            const overlay = this.elements.loadingOverlay;
            overlay.style.display = 'flex';

            requestAnimationFrame(() => {
                overlay.style.opacity = '1';
            });
        }

        /**
         * Создание loading overlay
         */
        createLoadingOverlay() {
            const overlay = document.createElement('div');
            overlay.id = 'page-transition-overlay';
            overlay.className = 'page-transition-overlay';
            overlay.innerHTML = `
                <div class="loading-spinner">
                    <div class="spinner-ring"></div>
                    <span class="loading-text">Loading...</span>
                </div>
            `;
            overlay.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(255, 255, 255, 0.9);
                display: none;
                align-items: center;
                justify-content: center;
                z-index: 9999;
                opacity: 0;
                transition: opacity 0.3s ease;
            `;
            document.body.appendChild(overlay);
            return overlay;
        }

        /**
         * Обработка popstate (кнопка назад/вперед)
         */
        handlePopstate(event) {
            // Предотвращаем обработку во время переходов
            if (this.state.isTransitioning || this.state.isPopstateHandling) {
                console.log('[PageTransitions] Ignoring popstate during transition');
                return;
            }

            console.log('[PageTransitions] Handling popstate navigation');

            this.state.isPopstateHandling = true;
            this.state.currentUrl = window.location.href;

            // Быстрое восстановление без блокировки
            requestAnimationFrame(() => {
                try {
                    this.restoreScrollPosition();
                    this.reinitializePageFeatures();
                } catch (error) {
                    console.error('[PageTransitions] Popstate error:', error);
                } finally {
                    this.state.isPopstateHandling = false;
                }
            });
        }

        /**
         * Сохранение позиции прокрутки
         */
        saveScrollPosition() {
            const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            this.state.scrollPositions.set(this.state.currentUrl, scrollTop);
            console.log('[PageTransitions] Saved scroll position:', scrollTop);
        }

        /**
         * Восстановление позиции прокрутки
         */
        restoreScrollPosition() {
            // ИСПРАВЛЕНО: Пропускаем восстановление прокрутки на странице новостей
            // если активна изолированная система навигации новостей
            const urlParams = new URLSearchParams(window.location.search);
            const isNewsPage = urlParams.get('page') === 'news';

            if (isNewsPage && window.IsolatedNewsNavigation) {
                console.log('[PageTransitions] Skipping scroll restoration on news page (handled by news navigation)');
                return;
            }

            const savedPosition = this.state.scrollPositions.get(this.state.currentUrl);

            if (savedPosition !== undefined && savedPosition > 0) {
                window.scrollTo({
                    top: savedPosition,
                    behavior: 'smooth'
                });
                console.log('[PageTransitions] Restored scroll position:', savedPosition);
            }
        }

        /**
         * Очистка временных сообщений
         */
        clearTemporaryMessages() {
            const messages = document.querySelectorAll('.alert, .message, .flash-message');
            messages.forEach(message => {
                if (!message.hasAttribute('data-persistent')) {
                    message.style.opacity = '0';
                    setTimeout(() => {
                        if (message.parentNode) {
                            message.parentNode.removeChild(message);
                        }
                    }, 200);
                }
            });
        }

        /**
         * Переинициализация функций страницы
         */
        reinitializePageFeatures() {
            // Переинициализация FlashMessage
            if (typeof window.initializeFlashMessages === 'function') {
                try {
                    window.initializeFlashMessages();
                } catch (error) {
                    console.error('[PageTransitions] FlashMessage reinit error:', error);
                }
            }

            // Событие для других модулей
            try {
                const event = new CustomEvent('pageTransitionComplete', {
                    detail: { url: this.state.currentUrl }
                });
                document.dispatchEvent(event);
            } catch (error) {
                console.error('[PageTransitions] Event dispatch error:', error);
            }
        }

        /**
         * Добавление ripple эффекта
         */
        addRippleEffect(event, element) {
            try {
                const rect = element.getBoundingClientRect();
                const ripple = document.createElement('span');
                const size = Math.max(rect.width, rect.height);
                const x = event.clientX - rect.left - size / 2;
                const y = event.clientY - rect.top - size / 2;

                ripple.style.cssText = `
                    position: absolute;
                    border-radius: 50%;
                    background: rgba(255, 255, 255, 0.6);
                    transform: scale(0);
                    animation: ripple 0.6s linear;
                    width: ${size}px;
                    height: ${size}px;
                    left: ${x}px;
                    top: ${y}px;
                    pointer-events: none;
                `;

                element.style.position = 'relative';
                element.style.overflow = 'hidden';
                element.appendChild(ripple);

                setTimeout(() => ripple.remove(), 600);
            } catch (error) {
                console.error('[PageTransitions] Ripple effect error:', error);
            }
        }

        /**
         * Обработка загрузки страницы
         */
        handlePageLoad() {
            console.log('[PageTransitions] Page loaded');
            this.resetState();
            this.hideAllIndicators();
            this.restoreScrollPosition();
        }

        /**
         * Обработка beforeunload
         */
        handleBeforeUnload() {
            this.saveScrollPosition();
        }

        /**
         * Обработка изменения видимости
         */
        handleVisibilityChange() {
            if (document.visibilityState === 'visible') {
                // Сброс зависших состояний
                setTimeout(() => {
                    if (this.state.isTransitioning) {
                        console.log('[PageTransitions] Resetting stuck transition state');
                        this.resetState();
                        this.hideAllIndicators();
                    }
                }, 1000);
            }
        }

        /**
         * Сброс состояния
         */
        resetState() {
            this.state.isTransitioning = false;
            this.state.isPopstateHandling = false;
        }

        /**
         * Скрытие всех индикаторов
         */
        hideAllIndicators() {
            this.hideProgressBar();

            if (this.elements.loadingOverlay) {
                this.elements.loadingOverlay.style.opacity = '0';
                setTimeout(() => {
                    if (this.elements.loadingOverlay && this.elements.loadingOverlay.parentNode) {
                        this.elements.loadingOverlay.parentNode.removeChild(this.elements.loadingOverlay);
                        this.elements.loadingOverlay = null;
                    }
                }, 300);
            }
        }

        /**
         * Очистка обработчиков
         */
        cleanupHandlers() {
            this.handlers.forEach((handler, event) => {
                window.removeEventListener(event, handler);
            });
            this.handlers.clear();
        }

        /**
         * Создание UI элементов
         */
        createUI() {
            // Добавляем CSS для анимаций
            if (!document.getElementById('page-transition-styles')) {
                const style = document.createElement('style');
                style.id = 'page-transition-styles';
                style.textContent = `
                    @keyframes ripple {
                        to {
                            transform: scale(4);
                            opacity: 0;
                        }
                    }
                    
                    .spinner-ring {
                        display: inline-block;
                        width: 40px;
                        height: 40px;
                        border: 3px solid #f3f3f3;
                        border-top: 3px solid #007bff;
                        border-radius: 50%;
                        animation: spin 1s linear infinite;
                    }
                    
                    @keyframes spin {
                        0% { transform: rotate(0deg); }
                        100% { transform: rotate(360deg); }
                    }
                    
                    .loading-text {
                        margin-top: 10px;
                        color: #666;
                        font-size: 14px;
                    }
                `;
                document.head.appendChild(style);
            }
        }

        /**
         * Регистрация в GlobalState
         */
        registerWithGlobalState() {
            if (window.Darkheim && window.Darkheim.GlobalState) {
                try {
                    window.Darkheim.GlobalState.registerModule('PageTransitions', this);
                    window.Darkheim.GlobalState.set('ui.navigation.transitioning', false);
                    console.log('[PageTransitions] Registered with GlobalState');
                } catch (error) {
                    console.error('[PageTransitions] GlobalState registration error:', error);
                }
            }
        }

        /**
         * Обработка критических ошибок
         */
        handleCriticalError(error) {
            console.error('[PageTransitions] Critical error:', error);

            // Сброс состояния
            this.resetState();
            this.hideAllIndicators();

            // Отключение системы в случае критической ошибки
            this.cleanupHandlers();

            // Уведомление пользователя (опционально)
            if (window.Darkheim && window.Darkheim.GlobalState) {
                window.Darkheim.GlobalState.set('ui.pageTransitions.error', true);
            }
        }

        /**
         * API для внешнего использования
         */
        getAPI() {
            return {
                navigate: (url) => this.navigate(url),
                isTransitioning: () => this.state.isTransitioning,
                reset: () => this.resetState(),
                disable: () => this.cleanupHandlers(),
                enable: () => this.setupEventHandlers()
            };
        }
    }

    // Инициализация при готовности DOM
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            window.PageTransitionManager = new PageTransitionManager();
        }, { once: true });
    } else {
        window.PageTransitionManager = new PageTransitionManager();
    }

    // API для обратной совместимости
    window.PageTransitions = window.PageTransitionManager?.getAPI() || {};

})();
