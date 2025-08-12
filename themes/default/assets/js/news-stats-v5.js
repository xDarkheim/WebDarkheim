/**
 * News Statistics Module v5.0 - Refactored
 * Advanced statistics and performance monitoring for news system
 *
 * @author Darkheim Studio
 * @version 5.0.0
 * @since 2025-08-10
 */

(function() {
    'use strict';

    /**
     * Advanced News Statistics Module
     * Handles counters, animations, performance metrics, and analytics
     */
    class NewsStats {
        /**
         * @param {NewsCore} core - Reference to news core system
         */
        constructor(core) {
            this.core = core;
            this.version = '5.0.0';
            this.namespace = 'NewsStats';

            this.config = {
                selectors: {
                    statCounter: '.stat-counter',
                    tabCount: '.tab-count',
                    resultsCount: '.results-count',
                    articleCard: '.article-card',
                    loadTime: '.load-time-stat'
                },
                animation: {
                    duration: 1500,
                    easing: 'easeOutCubic',
                    threshold: 0.3,
                    steps: 60
                },
                performance: {
                    trackLoadTimes: true,
                    trackUserInteractions: true,
                    trackScrollDepth: true
                }
            };

            this.state = {
                observers: new Map(),
                animatedElements: new Set(),
                performanceMetrics: new Map(),
                userInteractions: [],
                scrollDepth: 0,
                sessionStartTime: Date.now()
            };

            this.logger = this.core.logger || console;
        }

        /**
         * Initialize statistics module
         */
        async init() {
            try {
                this.logger.info('Initializing statistics module...');

                await this.initializeCounters();
                await this.initializePerformanceTracking();
                this.setupEventListeners();
                this.startPerformanceMonitoring();

                this.logger.success('Statistics module initialized');
                return true;
            } catch (error) {
                this.logger.error('Statistics initialization failed:', error);
                throw error;
            }
        }

        /**
         * Initialize counter elements
         */
        async initializeCounters() {
            const statElements = document.querySelectorAll(`
                ${this.config.selectors.statCounter},
                ${this.config.selectors.tabCount},
                ${this.config.selectors.resultsCount}
            `);

            if (statElements.length > 0) {
                this.setupIntersectionObserver(statElements);
                this.logger.debug(`Found ${statElements.length} stat elements to animate`);
            }

            // Initialize immediate stats
            this.updatePageStats();
        }

        /**
         * Initialize performance tracking
         */
        async initializePerformanceTracking() {
            if (!this.config.performance.trackLoadTimes) return;

            // Track initial page load
            this.trackPageLoadPerformance();

            // Track navigation performance
            this.trackNavigationPerformance();

            // Setup performance observer if available
            this.setupPerformanceObserver();
        }

        /**
         * Setup event listeners
         */
        setupEventListeners() {
            // Listen to core events
            this.core.eventBus?.on('navigation:filters:changed', this.handleFiltersChanged.bind(this));
            this.core.eventBus?.on('admin:mode:changed', this.handleAdminModeChanged.bind(this));
            this.core.eventBus?.on('admin:selection:changed', this.handleSelectionChanged.bind(this));

            // Track user interactions
            if (this.config.performance.trackUserInteractions) {
                this.setupInteractionTracking();
            }

            // Track scroll depth
            if (this.config.performance.trackScrollDepth) {
                this.setupScrollTracking();
            }
        }

        /**
         * Setup intersection observer for counter animations
         */
        setupIntersectionObserver(elements) {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting && !this.state.animatedElements.has(entry.target)) {
                        this.animateCounter(entry.target);
                    }
                });
            }, {
                threshold: this.config.animation.threshold,
                rootMargin: '50px'
            });

            elements.forEach(el => observer.observe(el));
            this.state.observers.set('counter', observer);
        }

        /**
         * Animate counter with modern easing
         */
        async animateCounter(element) {
            if (this.state.animatedElements.has(element)) return;

            const text = element.textContent.trim();
            const match = text.match(/(\d+)/);

            if (!match) return;

            const targetNumber = parseInt(match[0]);
            if (targetNumber === 0) return;

            this.state.animatedElements.add(element);

            const startTime = performance.now();
            const duration = this.config.animation.duration;

            const animate = (currentTime) => {
                const elapsed = currentTime - startTime;
                const progress = Math.min(elapsed / duration, 1);

                // Cubic ease-out easing
                const easedProgress = 1 - Math.pow(1 - progress, 3);
                const currentValue = Math.floor(targetNumber * easedProgress);

                // Update element text
                element.textContent = text.replace(/\d+/, currentValue.toLocaleString());

                // Add visual effects
                if (progress < 1) {
                    requestAnimationFrame(animate);
                } else {
                    element.classList.add('stat-animated');
                    this.addCounterCompleteEffect(element);
                }
            };

            requestAnimationFrame(animate);
            this.logger.debug(`Animating counter: ${targetNumber}`);
        }

        /**
         * Add completion effect to counter
         */
        addCounterCompleteEffect(element) {
            element.style.transform = 'scale(1.05)';
            element.style.transition = 'transform 0.2s ease';

            setTimeout(() => {
                element.style.transform = 'scale(1)';
            }, 200);
        }

        /**
         * Track page load performance
         */
        trackPageLoadPerformance() {
            if (typeof performance === 'undefined') return;

            // Wait for page to fully load
            window.addEventListener('load', () => {
                const loadTime = performance.now();
                this.state.performanceMetrics.set('pageLoadTime', loadTime);

                // Update load time display if element exists
                const loadTimeElement = document.querySelector(this.config.selectors.loadTime);
                if (loadTimeElement) {
                    loadTimeElement.textContent = `${Math.round(loadTime)}ms`;
                }

                this.logger.debug(`Page load time: ${Math.round(loadTime)}ms`);
            });
        }

        /**
         * Track navigation performance
         */
        trackNavigationPerformance() {
            this.core.eventBus?.on('navigation:start', (data) => {
                this.state.performanceMetrics.set('navigationStartTime', performance.now());
            });

            this.core.eventBus?.on('navigation:complete', (data) => {
                const startTime = this.state.performanceMetrics.get('navigationStartTime');
                if (startTime) {
                    const navigationTime = performance.now() - startTime;
                    this.state.performanceMetrics.set('lastNavigationTime', navigationTime);
                    this.logger.debug(`Navigation time: ${Math.round(navigationTime)}ms`);
                }
            });
        }

        /**
         * Setup performance observer
         */
        setupPerformanceObserver() {
            if (typeof PerformanceObserver === 'undefined') return;

            try {
                const observer = new PerformanceObserver((list) => {
                    for (const entry of list.getEntries()) {
                        this.processPerformanceEntry(entry);
                    }
                });

                observer.observe({ entryTypes: ['navigation', 'resource', 'measure'] });
                this.state.observers.set('performance', observer);
            } catch (error) {
                this.logger.warn('Performance observer not supported:', error);
            }
        }

        /**
         * Process performance entry
         */
        processPerformanceEntry(entry) {
            switch (entry.entryType) {
                case 'navigation':
                    this.state.performanceMetrics.set('domContentLoaded', entry.domContentLoadedEventEnd);
                    this.state.performanceMetrics.set('loadComplete', entry.loadEventEnd);
                    break;

                case 'resource':
                    if (entry.name.includes('news-')) {
                        this.state.performanceMetrics.set(`resource_${entry.name}`, entry.duration);
                    }
                    break;

                case 'measure':
                    this.state.performanceMetrics.set(`measure_${entry.name}`, entry.duration);
                    break;
            }
        }

        /**
         * Setup interaction tracking
         */
        setupInteractionTracking() {
            const interactionEvents = ['click', 'scroll', 'keydown'];

            interactionEvents.forEach(eventType => {
                document.addEventListener(eventType, (event) => {
                    this.trackInteraction(eventType, event);
                }, { passive: true });
            });
        }

        /**
         * Track user interaction
         */
        trackInteraction(type, event) {
            const interaction = {
                type: type,
                timestamp: Date.now(),
                target: event.target.tagName,
                className: event.target.className
            };

            this.state.userInteractions.push(interaction);

            // Keep only last 100 interactions
            if (this.state.userInteractions.length > 100) {
                this.state.userInteractions.shift();
            }
        }

        /**
         * Setup scroll depth tracking
         */
        setupScrollTracking() {
            let ticking = false;

            const updateScrollDepth = () => {
                const scrollTop = window.pageYOffset;
                const documentHeight = document.documentElement.scrollHeight - window.innerHeight;
                const scrollDepth = Math.round((scrollTop / documentHeight) * 100);

                if (scrollDepth > this.state.scrollDepth) {
                    this.state.scrollDepth = scrollDepth;

                    // Emit scroll depth milestones
                    if (scrollDepth % 25 === 0 && scrollDepth > 0) {
                        this.core.eventBus?.emit('stats:scroll:milestone', {
                            depth: scrollDepth
                        });
                    }
                }

                ticking = false;
            };

            const requestTick = () => {
                if (!ticking) {
                    requestAnimationFrame(updateScrollDepth);
                    ticking = true;
                }
            };

            window.addEventListener('scroll', requestTick, { passive: true });
        }

        /**
         * Start performance monitoring
         */
        startPerformanceMonitoring() {
            // Monitor memory usage if available
            if (performance.memory) {
                setInterval(() => {
                    this.state.performanceMetrics.set('memoryUsed', performance.memory.usedJSHeapSize);
                }, 30000); // Every 30 seconds
            }
        }

        /**
         * Update page statistics
         */
        updatePageStats() {
            const stats = {
                articleCount: document.querySelectorAll(this.config.selectors.articleCard).length,
                sessionDuration: Date.now() - this.state.sessionStartTime,
                scrollDepth: this.state.scrollDepth,
                interactionCount: this.state.userInteractions.length
            };

            // Update stat elements
            Object.entries(stats).forEach(([key, value]) => {
                const element = document.querySelector(`[data-stat="${key}"]`);
                if (element) {
                    element.textContent = this.formatStatValue(key, value);
                }
            });

            this.core.eventBus?.emit('stats:updated', stats);
        }

        /**
         * Format statistic value
         */
        formatStatValue(key, value) {
            switch (key) {
                case 'sessionDuration':
                    return this.formatDuration(value);
                case 'scrollDepth':
                    return `${value}%`;
                case 'articleCount':
                case 'interactionCount':
                    return value.toLocaleString();
                default:
                    return value;
            }
        }

        /**
         * Format duration in human readable format
         */
        formatDuration(milliseconds) {
            const seconds = Math.floor(milliseconds / 1000);
            const minutes = Math.floor(seconds / 60);
            const hours = Math.floor(minutes / 60);

            if (hours > 0) {
                return `${hours}h ${minutes % 60}m`;
            } else if (minutes > 0) {
                return `${minutes}m ${seconds % 60}s`;
            } else {
                return `${seconds}s`;
            }
        }

        /**
         * Handle filters changed event
         */
        handleFiltersChanged(data) {
            // Reset animated elements for new content
            this.state.animatedElements.clear();

            // Update stats after a short delay to allow content to load
            setTimeout(() => {
                this.updatePageStats();
            }, 500);
        }

        /**
         * Handle admin mode changed event
         */
        handleAdminModeChanged(data) {
            this.trackInteraction('admin_mode_toggle', {
                target: { tagName: 'BUTTON', className: 'admin-toggle' }
            });
        }

        /**
         * Handle selection changed event
         */
        handleSelectionChanged(data) {
            // Update selection stats
            const selectionElement = document.querySelector('[data-stat="selectedCount"]');
            if (selectionElement) {
                selectionElement.textContent = data.selectedCount;
            }
        }

        /**
         * Manually trigger counter animation
         */
        triggerCounterAnimation(selector) {
            const element = document.querySelector(selector);
            if (element) {
                this.state.animatedElements.delete(element);
                this.animateCounter(element);
            }
        }

        /**
         * Get performance summary
         */
        getPerformanceSummary() {
            return {
                pageLoadTime: this.state.performanceMetrics.get('pageLoadTime'),
                lastNavigationTime: this.state.performanceMetrics.get('lastNavigationTime'),
                memoryUsed: this.state.performanceMetrics.get('memoryUsed'),
                domContentLoaded: this.state.performanceMetrics.get('domContentLoaded'),
                loadComplete: this.state.performanceMetrics.get('loadComplete'),
                sessionDuration: Date.now() - this.state.sessionStartTime,
                scrollDepth: this.state.scrollDepth,
                interactionCount: this.state.userInteractions.length
            };
        }

        /**
         * Export statistics for analytics
         */
        exportStats() {
            const stats = {
                performance: Object.fromEntries(this.state.performanceMetrics),
                userBehavior: {
                    sessionDuration: Date.now() - this.state.sessionStartTime,
                    scrollDepth: this.state.scrollDepth,
                    interactionCount: this.state.userInteractions.length,
                    interactions: this.state.userInteractions.slice(-10) // Last 10 interactions
                },
                pageStats: {
                    articleCount: document.querySelectorAll(this.config.selectors.articleCard).length,
                    url: window.location.href,
                    timestamp: Date.now()
                }
            };

            this.logger.debug('Statistics exported:', stats);
            return stats;
        }

        /**
         * Clean up module
         */
        async destroy() {
            this.logger.debug('Destroying statistics module...');

            // Disconnect observers
            this.state.observers.forEach((observer, key) => {
                if (observer && typeof observer.disconnect === 'function') {
                    observer.disconnect();
                }
            });

            // Clear state
            this.state.observers.clear();
            this.state.animatedElements.clear();
            this.state.performanceMetrics.clear();
            this.state.userInteractions.length = 0;

            this.logger.debug('Statistics module destroyed');
        }

        /**
         * Get module API
         */
        getAPI() {
            return {
                version: this.version,
                triggerAnimation: (selector) => this.triggerCounterAnimation(selector),
                updateStats: () => this.updatePageStats(),
                getPerformance: () => this.getPerformanceSummary(),
                exportStats: () => this.exportStats(),
                getScrollDepth: () => this.state.scrollDepth,
                getInteractionCount: () => this.state.userInteractions.length
            };
        }
    }

    // Export to global scope
    window.NewsStats = NewsStats;

    console.log('[NewsStats] v5.0 module loaded - advanced statistics and performance monitoring');

})();
