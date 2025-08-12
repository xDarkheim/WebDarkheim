/**
 * News Core Module - Centralized foundation for all news functionality
 * Provides shared utilities, state management, and module orchestration
 *
 * @author Darkheim Studio
 * @version 5.0.0
 * @since 2025-08-10
 */

(function() {
    'use strict';

    // Prevent multiple instances
    if (window.NewsCore) {
        console.warn('[NewsCore] Already initialized');
        return;
    }

    /**
     * Central News Core System
     * Manages all news-related modules and provides shared functionality
     */
    class NewsCore {
        /**
         * @param {Object} config - Core configuration
         */
        constructor(config = {}) {
            this.version = '5.0.0';
            this.namespace = 'NewsCore';
            this.initialized = false;

            // Merge default configuration
            this.config = {
                debug: true,
                autoInit: true,
                modules: {
                    navigation: true,
                    search: true,
                    admin: true,
                    stats: true,
                    modals: true,
                    bulk: true
                },
                selectors: {
                    newsPage: '[data-page="news"]',
                    articleCard: '.article-card',
                    filterTab: '.filter-tab',
                    searchInput: '.search-input',
                    adminToggle: '.admin-toggle-btn'
                },
                classes: {
                    processed: 'news-processed',
                    loading: 'news-loading',
                    error: 'news-error',
                    hidden: 'news-hidden'
                },
                events: {
                    moduleLoaded: 'news:module:loaded',
                    moduleError: 'news:module:error',
                    stateChanged: 'news:state:changed'
                },
                ...config
            };

            // Centralized state management
            this.state = new Map();
            this.modules = new Map();
            this.eventListeners = new Map();

            // Initialize logger
            this.logger = new Logger(`[${this.namespace}]`, this.config.debug);

            // Initialize event system
            this.eventBus = new EventBus(this.logger);

            // Auto-initialize if enabled
            if (this.config.autoInit) {
                this.init();
            }
        }

        /**
         * Initialize the core system
         * @returns {Promise<boolean>}
         */
        async init() {
            if (this.initialized) {
                this.logger.warn('Already initialized');
                return false;
            }

            try {
                this.logger.info(`v${this.version} initializing...`);

                // Check if we're on news page
                if (!this.isNewsPage()) {
                    this.logger.info('Not on news page, skipping initialization');
                    return false;
                }

                // КРИТИЧЕСКИ ВАЖНО: Принудительная установка обработчиков событий
                await this.setupEmergencyEventHandlers();

                // Initialize core features
                await this.initializeCore();

                // Register available modules
                this.registerModules();

                // Initialize modules
                await this.initializeModules();

                this.initialized = true;
                this.logger.success(`v${this.version} initialized successfully`);

                // Emit initialization event
                this.eventBus.emit(this.config.events.moduleLoaded, {
                    module: 'core',
                    version: this.version
                });

                return true;
            } catch (error) {
                this.logger.error('Initialization failed:', error);
                this.handleError(error);
                return false;
            }
        }

        /**
         * Initialize core features
         */
        async initializeCore() {
            // КРИТИЧЕСКИ ВАЖНО: Инициализируем перехват событий ДО основных модулей
            this.setupEarlyEventCapture();

            // Setup scroll protection if navigation module is enabled
            if (this.config.modules.navigation) {
                this.setupScrollProtection();
            }

            // Setup error handling
            this.setupErrorHandling();

            // Setup performance monitoring
            this.setupPerformanceMonitoring();
        }

        /**
         * ДОБАВЛЕНО: Ранний перехват событий для предотвращения конфликтов с main.js
         */
        setupEarlyEventCapture() {
            console.log('[NewsCore] Setting up early event capture to prevent main.js conflicts');

            // Устанавливаем перехватчик с максимальным приоритетом (capture: true)
            document.addEventListener('click', (event) => {
                // Проверяем, что это новостной элемент
                const isNewsElement =
                    event.target.closest('.filter-tab') ||
                    event.target.closest('.category-link') ||
                    event.target.closest('.pagination-link') ||
                    event.target.closest('.news-page .pagination a') ||
                    (event.target.closest('.category-filter') && event.target.closest('a[href]')) ||
                    (event.target.href && event.target.href.includes('page=news') &&
                     (event.target.href.includes('category=') || event.target.href.includes('page_num=')));

                if (isNewsElement) {
                    console.log('[NewsCore] Early capture: Blocking main.js for news element:', event.target);
                    event.stopImmediatePropagation();
                    event.preventDefault();

                    // Помечаем событие как обработанное новостной системой
                    event._newsSystemHandled = true;

                    // ИСПРАВЛЕНО: Немедленно передаем событие навигационному модулю
                    this.delegateToNavigationModule(event);
                }
            }, { capture: true, passive: false }); // Максимальный приоритет

            console.log('[NewsCore] Early event capture initialized');
        }

        /**
         * ДОБАВЛЕНО: Надежная передача события навигационному модулю
         */
        delegateToNavigationModule(originalEvent) {
            // Попытка #1: Если модуль уже готов
            if (this.hasModule('navigation')) {
                const navModule = this.getModule('navigation');
                if (navModule && typeof navModule.handleDocumentClick === 'function') {
                    console.log('[NewsCore] Immediate delegation to navigation module');
                    this.executeNavigationHandler(navModule, originalEvent);
                    return;
                }
            }

            // Попытка #2: Ждем инициализации модуля (короткая задержка)
            setTimeout(() => {
                if (this.hasModule('navigation')) {
                    const navModule = this.getModule('navigation');
                    if (navModule && typeof navModule.handleDocumentClick === 'function') {
                        console.log('[NewsCore] Delayed delegation to navigation module');
                        this.executeNavigationHandler(navModule, originalEvent);
                        return;
                    }
                }

                // Попытка #3: Fallback - выполняем навигацию напрямую
                console.log('[NewsCore] Fallback: Direct navigation execution');
                this.executeDirectNavigation(originalEvent);
            }, 50);
        }

        /**
         * ДОБАВЛЕНО: Выполнение обработчика навигации
         */
        executeNavigationHandler(navModule, originalEvent) {
            try {
                // Создаем корректное синтетическое событие
                const syntheticEvent = new MouseEvent('click', {
                    bubbles: true,
                    cancelable: true,
                    view: window,
                    clientX: originalEvent.clientX || 0,
                    clientY: originalEvent.clientY || 0,
                    button: originalEvent.button || 0
                });

                // Копируем важные свойства
                Object.defineProperty(syntheticEvent, 'target', {
                    value: originalEvent.target,
                    writable: false
                });
                Object.defineProperty(syntheticEvent, 'currentTarget', {
                    value: originalEvent.currentTarget,
                    writable: false
                });

                // Выполняем обработчик навигации
                navModule.handleDocumentClick(syntheticEvent);
                console.log('[NewsCore] Navigation handler executed successfully');
            } catch (error) {
                console.error('[NewsCore] Navigation handler failed:', error);
                this.executeDirectNavigation(originalEvent);
            }
        }

        /**
         * ДОБАВЛЕНО: Прямая навигация как fallback
         */
        executeDirectNavigation(event) {
            try {
                const target = event.target.closest('a[href]');
                if (!target) return;

                const href = target.getAttribute('href');
                if (!href) return;

                console.log('[NewsCore] Executing direct navigation to:', href);

                // Проверяем, это внутренняя ссылка новостей
                if (href.includes('page=news')) {
                    // Сохраняем позицию скролла
                    const currentScrollPosition = window.pageYOffset || document.documentElement.scrollTop;

                    // Добавляем параметр для сохранения позиции
                    const url = new URL(href, window.location.origin);
                    url.searchParams.set('scroll_preserve', currentScrollPosition);

                    // Выполняем навигацию
                    window.location.href = url.toString();
                } else {
                    // Внешняя ссылка - обычная навигация
                    window.location.href = href;
                }
            } catch (error) {
                console.error('[NewsCore] Direct navigation failed:', error);
                // Последний fallback - просто следуем по ссылке
                if (event.target.closest('a[href]')) {
                    window.location.href = event.target.closest('a[href]').href;
                }
            }
        }

        /**
         * Register available modules
         */
        registerModules() {
            const availableModules = [
                { name: 'navigation', class: 'NewsNavigation', enabled: this.config.modules.navigation },
                { name: 'search', class: 'NewsSearch', enabled: this.config.modules.search },
                { name: 'admin', class: 'NewsAdmin', enabled: this.config.modules.admin },
                { name: 'stats', class: 'NewsStats', enabled: this.config.modules.stats },
                // ИСПРАВЛЕНО: Правильная ссылка на NewsBulk класс
                { name: 'bulk', class: 'NewsBulk', enabled: this.config.modules.bulk }
            ];

            availableModules.forEach(module => {
                if (module.enabled && window[module.class]) {
                    this.modules.set(module.name, {
                        class: module.class,
                        instance: null,
                        initialized: false
                    });
                    this.logger.debug(`Module ${module.name} registered with class ${module.class}`);
                } else if (module.enabled) {
                    this.logger.warn(`Module ${module.name} enabled but class ${module.class} not found`);
                }
            });

            this.logger.debug(`Registered ${this.modules.size} modules`);
        }

        /**
         * Initialize all registered modules
         */
        async initializeModules() {
            const initPromises = [];

            for (const [name, module] of this.modules) {
                initPromises.push(this.initializeModule(name, module));
            }

            const results = await Promise.allSettled(initPromises);

            results.forEach((result, index) => {
                const moduleName = Array.from(this.modules.keys())[index];
                if (result.status === 'rejected') {
                    this.logger.error(`Module ${moduleName} failed to initialize:`, result.reason);
                }
            });
        }

        /**
         * Initialize individual module
         * @param {string} name - Module name
         * @param {Object} module - Module configuration
         */
        async initializeModule(name, module) {
            try {
                const ModuleClass = window[module.class];
                if (!ModuleClass) {
                    throw new Error(`Module class ${module.class} not found`);
                }

                // Create module instance with core reference
                const instance = new ModuleClass(this);

                // ИСПРАВЛЕНО: Сохраняем экземпляр ПЕРЕД инициализацией
                module.instance = instance;
                module.initialized = false; // Помечаем как еще не инициализированный

                // Initialize module
                if (typeof instance.init === 'function') {
                    await instance.init();
                }

                // ИСПРАВЛЕНО: Помечаем как инициализированный ПОСЛЕ успешной инициализации
                module.initialized = true;

                this.logger.debug(`Module ${name} initialized successfully`);

                // Emit module loaded event
                this.eventBus.emit(this.config.events.moduleLoaded, {
                    module: name,
                    instance: instance
                });

            } catch (error) {
                this.logger.error(`Failed to initialize module ${name}:`, error);

                // ИСПРАВЛЕНО: Помечаем модуль как НЕ инициализированный при ошибке
                module.instance = null;
                module.initialized = false;

                this.eventBus.emit(this.config.events.moduleError, {
                    module: name,
                    error: error
                });
                throw error;
            }
        }

        /**
         * Setup scroll protection system - ИСПРАВЛЕНО для предотвращения конфликтов
         */
        setupScrollProtection() {
            const urlParams = new URLSearchParams(window.location.search);
            const scrollPreserve = urlParams.get('scroll_preserve');

            // ИСПРАВЛЕНО: Проверяем, что это не AJAX-навигация
            const isAjaxNavigation = window.history.state && window.history.state.preserveScroll;

            if (scrollPreserve && !isAjaxNavigation) {
                const targetPosition = parseInt(scrollPreserve);

                // Restore position immediately only for non-AJAX navigation
                window.scrollTo(0, targetPosition);

                // Clean URL
                const cleanUrl = new URL(window.location);
                cleanUrl.searchParams.delete('scroll_preserve');
                window.history.replaceState({}, '', cleanUrl.toString());

                this.logger.debug('Scroll position restored from URL:', targetPosition);
            } else if (isAjaxNavigation) {
                this.logger.debug('AJAX navigation detected, skipping URL scroll restore');
            }

            // Disable browser scroll restoration для полного контроля
            if ('scrollRestoration' in history) {
                history.scrollRestoration = 'manual';
            }
        }

        /**
         * Setup global error handling
         */
        setupErrorHandling() {
            window.addEventListener('error', (event) => {
                if (event.filename && event.filename.includes('news-')) {
                    this.handleError(event.error || event);
                }
            });

            window.addEventListener('unhandledrejection', (event) => {
                if (event.reason && event.reason.stack && event.reason.stack.includes('news-')) {
                    this.handleError(event.reason);
                }
            });
        }

        /**
         * Setup performance monitoring
         */
        setupPerformanceMonitoring() {
            if (typeof PerformanceObserver !== 'undefined') {
                const observer = new PerformanceObserver((list) => {
                    for (const entry of list.getEntries()) {
                        if (entry.name.includes('news-')) {
                            this.logger.debug(`Performance: ${entry.name} took ${entry.duration}ms`);
                        }
                    }
                });

                observer.observe({ entryTypes: ['measure', 'navigation'] });
            }
        }

        /**
         * Check if current page is news page with enhanced detection
         * @returns {boolean}
         */
        isNewsPage() {
            // Check URL parameter
            const urlParams = new URLSearchParams(window.location.search);
            const isNewsUrl = urlParams.get('page') === 'news';

            // Check for data-page attribute
            const hasNewsPageAttribute = document.querySelector(this.config.selectors.newsPage) !== null;

            // Check for news-specific elements
            const hasNewsElements = document.querySelector('.articles-grid, .news-grid, .articles-section') !== null;

            // Check URL path (fallback)
            const hasNewsInPath = window.location.pathname.includes('news') || window.location.href.includes('news');

            const isNews = isNewsUrl || hasNewsPageAttribute || hasNewsElements || hasNewsInPath;

            if (this.config.debug) {
                console.log('[NewsCore] Page detection:', {
                    isNewsUrl,
                    hasNewsPageAttribute,
                    hasNewsElements,
                    hasNewsInPath,
                    finalResult: isNews,
                    url: window.location.href
                });
            }

            // ИСПРАВЛЕНО: Регистрируем интегрированную систему в GlobalState
            if (isNews && window.Darkheim && window.Darkheim.GlobalState) {
                window.Darkheim.GlobalState.set('ui.newsPage.isolated', false);
                window.Darkheim.GlobalState.set('ui.newsPage.integrated', true);
                window.Darkheim.GlobalState.set('ui.newsPage.active', true);
                console.log('[NewsCore] Registered integrated news system in GlobalState');
            }

            return isNews;
        }

        /**
         * Get module instance by name
         * @param {string} name - Module name
         * @returns {Object|null}
         */
        getModule(name) {
            const module = this.modules.get(name);
            return module && module.initialized ? module.instance : null;
        }

        /**
         * Check if module exists and is initialized
         * @param {string} name - Module name
         * @returns {boolean}
         */
        hasModule(name) {
            const module = this.modules.get(name);
            return !!(module && module.initialized && module.instance);
        }

        /**
         * Get/Set state value - ИСПРАВЛЕНО для предотвращения конфликта имен
         * @param {string} key - State key
         * @param {*} value - State value (if setting)
         * @returns {*}
         */
        setState(key, value) {
            if (value !== undefined) {
                const oldValue = this.state.get(key);
                this.state.set(key, value);

                this.eventBus.emit(this.config.events.stateChanged, {
                    key: key,
                    oldValue: oldValue,
                    newValue: value
                });

                return value;
            }
            return this.state.get(key);
        }

        /**
         * Get state value - ДОБАВЛЕНО для обратной совместимости
         * @param {string} key - State key
         * @returns {*}
         */
        getState(key) {
            return this.state.get(key);
        }

        /**
         * Handle errors - ИСПРАВЛЕНО для использования setState()
         * @param {Error} error - Error object
         */
        handleError(error) {
            this.logger.error('Error occurred:', error);

            // Store error in state - ИСПРАВЛЕНО: используем this.state.set() вместо this.state()
            this.state.set('lastError', {
                message: error.message,
                stack: error.stack,
                timestamp: Date.now()
            });

            // Emit error event
            this.eventBus.emit(this.config.events.moduleError, { error });
        }

        /**
         * Cleanup and destroy
         */
        async destroy() {
            this.logger.debug('Destroying core...');

            // Cleanup modules
            for (const [name, module] of this.modules) {
                if (module.instance && typeof module.instance.destroy === 'function') {
                    try {
                        await module.instance.destroy();
                    } catch (error) {
                        this.logger.error(`Error destroying module ${name}:`, error);
                    }
                }
            }

            // Clear state
            this.state.clear();
            this.modules.clear();
            this.eventListeners.clear();

            // Cleanup event bus
            this.eventBus.destroy();

            this.initialized = false;
            this.logger.debug('Core destroyed');
        }

        /**
         * Get modules debug information - ДОБАВЛЕНО для диагностики
         * @returns {Object}
         */
        getModulesDebugInfo() {
            const debugInfo = {};
            for (const [name, module] of this.modules) {
                debugInfo[name] = {
                    registered: true,
                    hasInstance: !!module.instance,
                    initialized: module.initialized,
                    className: module.class,
                    classExists: !!(window[module.class])
                };
            }
            return debugInfo;
        }

        /**
         * Get public API - ИСПРАВЛЕНО для использования правильных методов
         * @returns {Object}
         */
        getAPI() {
            return {
                version: this.version,
                isInitialized: () => this.initialized,
                getModule: (name) => this.getModule(name),
                hasModule: (name) => this.hasModule(name), // ДОБАВЛЕНО: hasModule в API
                getModulesDebugInfo: () => this.getModulesDebugInfo(), // ДОБАВЛЕНО: debug info
                state: (key, value) => this.setState(key, value), // ИСПРАВЛЕНО: используем setState
                getState: (key) => this.getState(key), // ДОБАВЛЕНО: getState метод
                setState: (key, value) => this.setState(key, value), // ДОБАВЛЕНО: явный setState
                on: (event, callback) => this.eventBus.on(event, callback),
                off: (event, callback) => this.eventBus.off(event, callback),
                emit: (event, data) => this.eventBus.emit(event, data),
                destroy: () => this.destroy()
            };
        }
    }

    /**
     * Enhanced Logger with better formatting and filtering
     */
    class Logger {
        constructor(prefix = '', enabled = true) {
            this.prefix = prefix;
            this.enabled = enabled;
            this.levels = {
                debug: 0,
                info: 1,
                warn: 2,
                error: 3,
                success: 1
            };
            this.currentLevel = 0; // Show all by default
        }

        log(level, message, ...args) {
            if (!this.enabled || this.levels[level] < this.currentLevel) return;

            const timestamp = new Date().toISOString().substr(11, 12);
            const levelEmoji = {
                debug: '🔍',
                info: 'ℹ️',
                warn: '⚠️',
                error: '❌',
                success: '✅'
            };

            const fullMessage = `${timestamp} ${levelEmoji[level]} ${this.prefix} ${message}`;
            console[level === 'success' ? 'info' : level](fullMessage, ...args);
        }

        debug(msg, ...args) { this.log('debug', msg, ...args); }
        info(msg, ...args) { this.log('info', msg, ...args); }
        warn(msg, ...args) { this.log('warn', msg, ...args); }
        error(msg, ...args) { this.log('error', msg, ...args); }
        success(msg, ...args) { this.log('success', msg, ...args); }

        setLevel(level) {
            if (this.levels[level] !== undefined) {
                this.currentLevel = this.levels[level];
            }
        }
    }

    /**
     * Event Bus for inter-module communication
     */
    class EventBus {
        constructor(logger) {
            this.events = new Map();
            this.logger = logger;
        }

        on(event, callback) {
            if (!this.events.has(event)) {
                this.events.set(event, new Set());
            }
            this.events.get(event).add(callback);
            this.logger.debug(`Event listener added for: ${event}`);
        }

        off(event, callback) {
            if (this.events.has(event)) {
                this.events.get(event).delete(callback);
                this.logger.debug(`Event listener removed for: ${event}`);
            }
        }

        emit(event, data = {}) {
            if (this.events.has(event)) {
                this.events.get(event).forEach(callback => {
                    try {
                        callback(data);
                    } catch (error) {
                        this.logger.error(`Error in event callback for ${event}:`, error);
                    }
                });
                this.logger.debug(`Event emitted: ${event}`, data);
            }
        }

        destroy() {
            this.events.clear();
        }
    }

    /**
     * Utility functions
     */
    const Utils = {
        /**
         * Debounce function calls
         */
        debounce(func, wait, immediate = false) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    timeout = null;
                    if (!immediate) func(...args);
                };
                const callNow = immediate && !timeout;
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
                if (callNow) func(...args);
            };
        },

        /**
         * Throttle function calls
         */
        throttle(func, limit) {
            let inThrottle;
            return function(...args) {
                if (!inThrottle) {
                    func.apply(this, args);
                    inThrottle = true;
                    setTimeout(() => inThrottle = false, limit);
                }
            };
        },

        /**
         * Deep clone object
         */
        deepClone(obj) {
            if (obj === null || typeof obj !== 'object') return obj;
            if (obj instanceof Date) return new Date(obj.getTime());
            if (obj instanceof Array) return obj.map(item => this.deepClone(item));
            if (typeof obj === 'object') {
                const cloned = {};
                Object.keys(obj).forEach(key => {
                    cloned[key] = this.deepClone(obj[key]);
                });
                return cloned;
            }
        },

        /**
         * Get CSRF token
         */
        getCsrfToken() {
            const metaToken = document.querySelector('meta[name="csrf-token"]');
            if (metaToken) return metaToken.getAttribute('content');

            const formToken = document.querySelector('input[name="csrf_token"]');
            if (formToken) return formToken.value;

            return '';
        },

        /**
         * Extract article ID from URL
         */
        extractArticleId(url) {
            const match = url.match(/[?&]id=(\d+)/);
            return match ? match[1] : null;
        },

        /**
         * Show notification
         */
        showNotification(message, type = 'info', duration = 4000) {
            const notification = document.createElement('div');
            notification.className = `news-notification news-notification--${type}`;
            notification.innerHTML = `
                <div class="news-notification__content">
                    <span class="news-notification__message">${message}</span>
                    <button class="news-notification__close">&times;</button>
                </div>
            `;

            // Add styles
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 10000;
                max-width: 300px;
                padding: 12px 16px;
                border-radius: 4px;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
                background: white;
                border-left: 4px solid ${type === 'error' ? '#e74c3c' : type === 'success' ? '#27ae60' : '#3498db'};
                transform: translateX(100%);
                transition: transform 0.3s ease;
            `;

            document.body.appendChild(notification);

            // Show animation
            requestAnimationFrame(() => {
                notification.style.transform = 'translateX(0)';
            });

            // Auto hide
            const hideNotification = () => {
                notification.style.transform = 'translateX(100%)';
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.parentNode.removeChild(notification);
                    }
                }, 300);
            };

            // Close button
            const closeBtn = notification.querySelector('.news-notification__close');
            closeBtn.addEventListener('click', hideNotification);

            // Auto hide after duration
            setTimeout(hideNotification, duration);
        }
    };

    // Initialize core system
    const core = new NewsCore();

    // Export to global scope
    window.NewsCore = core;
    window.NewsCoreAPI = core.getAPI();
    window.NewsUtils = Utils;

    // Auto-cleanup on page unload
    window.addEventListener('beforeunload', () => {
        core.destroy();
    });

    console.log(`[NewsCore] v${core.version} loaded - modular architecture initialized`);

})();
