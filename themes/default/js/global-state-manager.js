/**
 * Global State Manager для Darkheim Studio
 * Централизованное управление состоянием приложения в JavaScript
 * Заменяет прямое использование window.* переменных
 */

(function() {
    'use strict';

    /**
     * Главный класс управления состоянием
     */
    class GlobalStateManager {
        constructor() {
            this.state = new Map();
            this.listeners = new Map();
            this.modules = new Map();
            this.initialized = false;

            this.initializeDefaultState();
            this.setupNamespaces();
        }

        /**
         * Инициализация состояния по умолчанию
         */
        initializeDefaultState() {
            // Используем обычные объекты вместо Map для простоты
            const defaultState = {
                app: {
                    name: 'Darkheim Studio',
                    version: '1.0.0',
                    environment: 'production',
                    debug: false,
                    initialized: false
                },
                user: {
                    authenticated: false,
                    id: null,
                    username: null,
                    role: null,
                    permissions: []
                },
                ui: {
                    theme: 'default',
                    loading: false,
                    modal: null,
                    notifications: [],
                    navigation: {
                        currentPage: 'home',
                        breadcrumbs: []
                    }
                },
                api: {
                    baseUrl: '/api',
                    csrfToken: null,
                    pendingRequests: 0
                },
                editor: {
                    instances: [],
                    activeEditor: null,
                    unsavedChanges: false
                }
            };

            // Устанавливаем каждую секцию отдельно
            Object.keys(defaultState).forEach(key => {
                this.state.set(key, defaultState[key]);
            });
        }

        /**
         * Настройка пространств имен
         */
        setupNamespaces() {
            // Создаем основные пространства имен
            if (!window.Darkheim) {
                window.Darkheim = {};
            }

            // Регистрируем основные модули
            window.Darkheim.State = this;
            window.Darkheim.Utils = {};
            window.Darkheim.Components = {};
            window.Darkheim.Services = {};
        }

        /**
         * Получение значения из состояния
         */
        get(key, defaultValue = null) {
            const keys = key.split('.');
            let current = this.state;

            for (const k of keys) {
                if (current instanceof Map) {
                    current = current.get(k);
                } else if (current && typeof current === 'object') {
                    current = current[k];
                } else {
                    return defaultValue;
                }

                if (current === undefined || current === null) {
                    return defaultValue;
                }
            }

            return current !== undefined ? current : defaultValue;
        }

        /**
         * Установка значения в состояние
         */
        set(key, value) {
            const keys = key.split('.');
            const lastKey = keys.pop();
            let current = this.state;

            // Навигация до предпоследнего уровня
            for (const k of keys) {
                if (current instanceof Map) {
                    if (!current.has(k)) {
                        current.set(k, {});
                    }
                    current = current.get(k);
                } else if (typeof current === 'object' && current !== null) {
                    if (!current[k]) {
                        current[k] = {};
                    }
                    current = current[k];
                } else {
                    // Если current не объект и не Map, создаем новый объект
                    const newObj = {};
                    newObj[k] = {};

                    // Если мы на верхнем уровне (this.state)
                    if (current === this.state) {
                        current.set(k, newObj[k]);
                        current = current.get(k);
                    } else {
                        current[k] = newObj[k];
                        current = current[k];
                    }
                }
            }

            // Получаем старое значение
            let oldValue;
            if (current instanceof Map) {
                oldValue = current.get(lastKey);
                current.set(lastKey, value);
            } else if (typeof current === 'object' && current !== null) {
                oldValue = current[lastKey];
                current[lastKey] = value;
            } else {
                // Это не должно происходить, но на всякий случай
                oldValue = undefined;
                console.error('[GlobalState] Unable to set value, current is not an object or Map:', current);
                return;
            }

            // Уведомление слушателей
            this.notifyListeners(key, value, oldValue);

            if (this.get('app.debug')) {
                console.debug(`[GlobalState] ${key} updated:`, { oldValue, newValue: value });
            }
        }

        /**
         * Проверка существования ключа
         */
        has(key) {
            try {
                const value = this.get(key);
                return value !== null && value !== undefined;
            } catch (e) {
                return false;
            }
        }

        /**
         * Удаление ключа из состояния
         */
        unset(key) {
            const keys = key.split('.');
            const lastKey = keys.pop();
            let current = this.state;

            for (const k of keys) {
                current = current instanceof Map ? current.get(k) : current[k];
                if (!current) return false;
            }

            const deleted = current instanceof Map ? 
                current.delete(lastKey) : 
                delete current[lastKey];

            if (deleted && this.get('app.debug')) {
                console.debug(`[GlobalState] ${key} removed`);
            }

            return deleted;
        }

        /**
         * Добавление слушателя изменений
         */
        addListener(key, callback) {
            if (!this.listeners.has(key)) {
                this.listeners.set(key, []);
            }
            this.listeners.get(key).push(callback);

            // Возвращаем функцию для отписки
            return () => this.removeListener(key, callback);
        }

        /**
         * Удаление слушателя
         */
        removeListener(key, callback) {
            if (this.listeners.has(key)) {
                const callbacks = this.listeners.get(key);
                const index = callbacks.indexOf(callback);
                if (index > -1) {
                    callbacks.splice(index, 1);
                }
            }
        }

        /**
         * Уведомление слушателей
         */
        notifyListeners(key, newValue, oldValue) {
            if (this.listeners.has(key)) {
                const callbacks = this.listeners.get(key);
                callbacks.forEach(callback => {
                    try {
                        callback(newValue, oldValue, key);
                    } catch (error) {
                        console.error(`[GlobalState] Listener error for ${key}:`, error);
                    }
                });
            }
        }

        /**
         * Регистрация модуля в глобальном состоянии
         */
        registerModule(name, moduleInstance) {
            this.modules.set(name, moduleInstance);

            // Создаем пространство имен для модуля
            const namespace = name.split('.');
            let current = window.Darkheim;

            for (let i = 0; i < namespace.length - 1; i++) {
                const ns = namespace[i];
                if (!current[ns]) {
                    current[ns] = {};
                }
                current = current[ns];
            }

            current[namespace[namespace.length - 1]] = moduleInstance;

            if (this.get('app.debug')) {
                console.debug(`[GlobalState] Module registered: ${name}`);
            }
        }

        /**
         * Получение экземпляра модуля
         */
        getModule(name) {
            return this.modules.get(name);
        }

        /**
         * Массовое обновление состояния
         */
        merge(updates) {
            Object.keys(updates).forEach(key => {
                this.set(key, updates[key]);
            });
        }

        /**
         * Создание реактивного свойства
         */
        reactive(key, initialValue) {
            if (!this.has(key)) {
                this.set(key, initialValue);
            }

            return {
                get: () => this.get(key),
                set: (value) => this.set(key, value),
                subscribe: (callback) => this.addListener(key, callback)
            };
        }

        /**
         * Сохранение состояния в localStorage
         */
        persist(keys = []) {
            const persistedData = {};
            keys.forEach(key => {
                if (this.has(key)) {
                    persistedData[key] = this.get(key);
                }
            });

            try {
                localStorage.setItem('darkheim_state', JSON.stringify(persistedData));
                if (this.get('app.debug')) {
                    console.debug('[GlobalState] State persisted to localStorage');
                }
            } catch (error) {
                console.error('[GlobalState] Failed to persist state:', error);
            }
        }

        /**
         * Восстановление состояния из localStorage
         */
        restore() {
            try {
                const persistedData = localStorage.getItem('darkheim_state');
                if (persistedData) {
                    const data = JSON.parse(persistedData);
                    this.merge(data);
                    if (this.get('app.debug')) {
                        console.debug('[GlobalState] State restored from localStorage');
                    }
                }
            } catch (error) {
                console.error('[GlobalState] Failed to restore state:', error);
            }
        }

        /**
         * Очистка состояния
         */
        clear() {
            this.state.clear();
            this.listeners.clear();
            this.initializeDefaultState();

            if (this.get('app.debug')) {
                console.debug('[GlobalState] State cleared and reinitialized');
            }
        }

        /**
         * Экспорт состояния для отладки
         */
        export() {
            const exported = {};

            try {
                for (const [key, value] of this.state.entries()) {
                    if (value instanceof Map) {
                        exported[key] = this._mapToObject(value);
                    } else {
                        exported[key] = this._deepClone(value);
                    }
                }
            } catch (error) {
                console.error('[GlobalState] Error during export:', error);
                return { error: 'Failed to export state' };
            }

            return exported;
        }

        /**
         * Преобразование Map в объект рекурсивно
         */
        _mapToObject(map) {
            const obj = {};
            for (const [key, value] of map.entries()) {
                if (value instanceof Map) {
                    obj[key] = this._mapToObject(value);
                } else {
                    obj[key] = this._deepClone(value);
                }
            }
            return obj;
        }

        /**
         * Глубокое клонирование объекта
         */
        _deepClone(obj) {
            if (obj === null || typeof obj !== 'object') return obj;
            if (obj instanceof Date) return new Date(obj);
            if (obj instanceof Array) return obj.map(item => this._deepClone(item));

            if (typeof obj === 'object') {
                const cloned = {};
                Object.keys(obj).forEach(key => {
                    cloned[key] = this._deepClone(obj[key]);
                });
                return cloned;
            }

            return obj;
        }

        /**
         * Инициализация менеджера состояния
         */
        initialize(config = {}) {
            if (this.initialized) {
                return;
            }

            // Обновляем конфигурацию приложения
            this.merge({
                'app.debug': config.debug || false,
                'app.environment': config.environment || 'production',
                'api.baseUrl': config.apiBaseUrl || '/api',
                'api.csrfToken': config.csrfToken || null
            });

            // Восстанавливаем состояние из localStorage
            this.restore();

            this.initialized = true;
            this.set('app.initialized', true);

            if (this.get('app.debug')) {
                console.log('[GlobalState] Initialized with config:', config);
            }
        }
    }

    /**
     * Утилиты для работы с состоянием
     */
    class StateUtils {
        static deepClone(obj) {
            if (obj === null || typeof obj !== 'object') return obj;
            if (obj instanceof Date) return new Date(obj);
            if (obj instanceof Array) return obj.map(item => StateUtils.deepClone(item));
            if (obj instanceof Map) {
                const clonedMap = new Map();
                obj.forEach((value, key) => clonedMap.set(key, StateUtils.deepClone(value)));
                return clonedMap;
            }
            if (typeof obj === 'object') {
                const clonedObj = {};
                Object.keys(obj).forEach(key => {
                    clonedObj[key] = StateUtils.deepClone(obj[key]);
                });
                return clonedObj;
            }
        }

        static isEqual(a, b) {
            if (a === b) return true;
            if (a == null || b == null) return false;
            if (a.constructor !== b.constructor) return false;

            if (a instanceof Array && b instanceof Array) {
                return a.length === b.length && a.every((val, index) => StateUtils.isEqual(val, b[index]));
            }

            if (a instanceof Map && b instanceof Map) {
                if (a.size !== b.size) return false;
                for (let [key, value] of a) {
                    if (!b.has(key) || !StateUtils.isEqual(value, b.get(key))) {
                        return false;
                    }
                }
                return true;
            }

            if (typeof a === 'object' && typeof b === 'object') {
                const keysA = Object.keys(a);
                const keysB = Object.keys(b);
                return keysA.length === keysB.length && 
                       keysA.every(key => StateUtils.isEqual(a[key], b[key]));
            }

            return false;
        }
    }

    // Создаем глобальный экземпляр
    const globalStateManager = new GlobalStateManager();

    // Экспортируем в глобальное пространство имен
    window.Darkheim = window.Darkheim || {};
    window.Darkheim.GlobalState = globalStateManager;
    window.Darkheim.StateUtils = StateUtils;

    // Обратная совместимость - создаем глобальные функции
    window.setState = (key, value) => globalStateManager.set(key, value);
    window.getState = (key, defaultValue) => globalStateManager.get(key, defaultValue);
    window.hasState = (key) => globalStateManager.has(key);

    // Инициализация при загрузке DOM
    document.addEventListener('DOMContentLoaded', function() {
        // Получаем конфигурацию из мета-тегов или data-атрибутов
        const config = {
            debug: document.documentElement.hasAttribute('data-debug'),
            environment: document.documentElement.getAttribute('data-env') || 'production',
            csrfToken: document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
        };

        globalStateManager.initialize(config);
        console.log('[Darkheim] Global State Manager initialized');
    });

})();
