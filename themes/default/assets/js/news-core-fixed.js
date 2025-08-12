/**
 * News Core Module - SIMPLIFIED v5.0.1-fixed
 * Упрощенная версия основной системы для надежной работы категорий
 *
 * @author Darkheim Studio
 * @version 5.0.1-fixed
 * @since 2025-08-10
 */

(function() {
    'use strict';

    // Предотвращаем множественную инициализацию
    if (window.NewsCoreFixed) {
        console.warn('[NewsCoreFixed] Already initialized');
        return;
    }

    /**
     * Упрощенная система управления новостными модулями
     */
    class NewsCoreFixed {
        constructor(config = {}) {
            this.version = '5.0.1-fixed';
            this.namespace = 'NewsCoreFixed';
            this.initialized = false;

            this.config = {
                debug: true,
                modules: {
                    navigation: true,
                    search: true
                },
                ...config
            };

            // Простое управление состоянием
            this.state = new Map();
            this.modules = new Map();
            this.events = new Map();

            // Простой logger
            this.logger = {
                info: (...args) => this.config.debug && console.log('[NewsCoreFixed]', ...args),
                warn: (...args) => this.config.debug && console.warn('[NewsCoreFixed]', ...args),
                error: (...args) => console.error('[NewsCoreFixed]', ...args),
                debug: (...args) => this.config.debug && console.debug('[NewsCoreFixed]', ...args),
                success: (...args) => this.config.debug && console.log('[NewsCoreFixed] ✓', ...args)
            };

            // Автоинициализация
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', () => this.init());
            } else {
                this.init();
            }
        }

        /**
         * Инициализация системы
         */
        async init() {
            if (this.initialized) {
                this.logger.warn('Already initialized');
                return false;
            }

            try {
                this.logger.info(`v${this.version} initializing...`);

                // Проверяем, что мы на странице новостей
                if (!this.isNewsPage()) {
                    this.logger.info('Not on news page, skipping initialization');
                    return false;
                }

                // Инициализируем модули
                await this.initializeModules();

                this.initialized = true;
                this.logger.success(`v${this.version} initialized successfully`);

                // Создаем глобальный API
                this.createGlobalAPI();

                return true;
            } catch (error) {
                this.logger.error('Initialization failed:', error);
                return false;
            }
        }

        /**
         * Проверка, что мы на странице новостей
         */
        isNewsPage() {
            return document.querySelector('[data-page="news"]') !== null;
        }

        /**
         * Инициализация модулей
         */
        async initializeModules() {
            // ДОРАБОТАНО: Улучшенная последовательность загрузки модулей
            const moduleLoadResults = [];

            // Инициализируем навигацию
            if (this.config.modules.navigation && window.NewsNavigationFixed) {
                try {
                    const navigation = new window.NewsNavigationFixed(this);
                    await navigation.init();
                    this.modules.set('navigation', navigation);
                    moduleLoadResults.push({ name: 'navigation', status: 'success' });
                    this.logger.success('Navigation module loaded');
                } catch (error) {
                    moduleLoadResults.push({ name: 'navigation', status: 'error', error: error.message });
                    this.logger.error('Navigation module failed to load:', error);
                }
            }

            // ДОРАБОТАНО: Ждем инициализации навигации перед загрузкой поиска
            if (this.config.modules.search && window.NewsSearchFixed) {
                try {
                    // Даем навигации время на полную инициализацию
                    await new Promise(resolve => setTimeout(resolve, 100));

                    const search = new window.NewsSearchFixed(this);
                    await search.init();
                    this.modules.set('search', search);
                    moduleLoadResults.push({ name: 'search', status: 'success' });
                    this.logger.success('Search module loaded');
                } catch (error) {
                    moduleLoadResults.push({ name: 'search', status: 'error', error: error.message });
                    this.logger.error('Search module failed to load:', error);
                }
            }

            // ДОРАБОТАНО: Эмитим событие с результатами загрузки модулей
            this.emit('modules:loaded', {
                results: moduleLoadResults,
                successCount: moduleLoadResults.filter(r => r.status === 'success').length,
                totalCount: moduleLoadResults.length
            });
        }

        /**
         * Получение модуля
         */
        getModule(name) {
            return this.modules.get(name);
        }

        /**
         * Установка состояния
         */
        setState(key, value) {
            this.state.set(key, value);
            this.emit('state:changed', { key, value });
        }

        /**
         * Получение состояния
         */
        getState(key) {
            return this.state.get(key);
        }

        /**
         * Простая система событий
         */
        on(event, callback) {
            if (!this.events.has(event)) {
                this.events.set(event, []);
            }
            this.events.get(event).push(callback);
        }

        /**
         * Отправка события
         */
        emit(event, data) {
            if (this.events.has(event)) {
                this.events.get(event).forEach(callback => {
                    try {
                        callback(data);
                    } catch (error) {
                        this.logger.error(`Event handler error for ${event}:`, error);
                    }
                });
            }
        }

        /**
         * Создание глобального API для совместимости
         */
        createGlobalAPI() {
            window.NewsCoreAPI = {
                isInitialized: () => this.initialized,
                getModule: (name) => this.getModule(name),
                on: (event, callback) => this.on(event, callback),
                emit: (event, data) => this.emit(event, data),
                getState: (key) => this.getState(key),
                setState: (key, value) => this.setState(key, value),
                version: this.version
            };

            this.logger.success('Global API created');
        }

        /**
         * Проверка активности
         */
        isInitialized() {
            return this.initialized;
        }
    }

    // Создаем экземпляр системы
    window.NewsCoreFixed = new NewsCoreFixed();

})();
