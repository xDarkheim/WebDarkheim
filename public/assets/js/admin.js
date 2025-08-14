/**
 * Admin Panel JavaScript
 * Common functionality for administrative interface
 * NOTE: Flash messages and error handling are managed by separate JS system
 */

class AdminPanel {
    constructor() {
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.initializeComponents();
        this.setupTableFeatures();
        this.setupFormValidation();
        this.setupModals();
        this.initializeDashboard();
        this.initializeContactForm();
        this.initializeProgressCircles();
        this.initializeMilestones();
        this.initializeImageUpload();
        this.initializeTechBadges();
        this.initializeSystemMonitor();
    }

    setupEventListeners() {
        // Confirm delete actions
        document.addEventListener('click', (e) => {
            if (e.target.closest('[data-confirm]')) {
                const message = e.target.closest('[data-confirm]').dataset.confirm;
                if (!confirm(message)) {
                    e.preventDefault();
                    return false;
                }
            }
        });

        // Handle loading states
        document.addEventListener('submit', (e) => {
            const form = e.target;
            if (form.tagName === 'FORM') {
                this.setLoadingState(form, true);
            }
        });
    }

    initializeComponents() {
        // Initialize search functionality
        this.initializeSearch();

        // Initialize slug generation
        this.initializeSlugGeneration();

        // Initialize tooltips
        this.initializeTooltips();

        // Initialize copy to clipboard
        this.initializeCopyButtons();
    }

    setupTableFeatures() {
        // Sortable table headers
        document.querySelectorAll('.admin-table th[data-sort]').forEach(header => {
            header.style.cursor = 'pointer';
            header.addEventListener('click', () => {
                this.sortTable(header);
            });
        });

        // Row selection
        this.setupRowSelection();
    }

    setupFormValidation() {
        // Real-time validation
        document.querySelectorAll('.admin-input[required]').forEach(input => {
            input.addEventListener('blur', () => {
                this.validateField(input);
            });
        });

        // Form submission validation
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', (e) => {
                if (!this.validateForm(form)) {
                    e.preventDefault();
                }
            });
        });
    }

    setupModals() {
        // Modal triggers
        document.addEventListener('click', (e) => {
            if (e.target.matches('[data-modal]')) {
                const modalId = e.target.dataset.modal;
                this.openModal(modalId);
            }

            if (e.target.matches('[data-modal-close]')) {
                this.closeModal(e.target.closest('.admin-modal'));
            }
        });

        // Close modal on backdrop click
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('admin-modal')) {
                this.closeModal(e.target);
            }
        });

        // Close modal on Escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                const openModal = document.querySelector('.admin-modal:not(.admin-hidden)');
                if (openModal) {
                    this.closeModal(openModal);
                }
            }
        });
    }

    // HTTP request helper
    async request(url, options = {}) {
        try {
            const response = await fetch(url, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                ...options
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();

            // Handle flash messages using global toast system only
            if (data.flash_messages && window.showToast) {
                Object.keys(data.flash_messages).forEach(type => {
                    const messages = data.flash_messages[type];
                    if (Array.isArray(messages)) {
                        messages.forEach(message => {
                            const text = message.text || message;
                            window.showToast(text, type);
                        });
                    }
                });
            }

            return data;
        } catch (error) {
            console.error('Request failed:', error);
            throw error;
        }
    }

    // Search Functionality
    initializeSearch() {
        document.querySelectorAll('[data-search-target]').forEach(searchInput => {
            const targetSelector = searchInput.dataset.searchTarget;
            const searchColumns = searchInput.dataset.searchColumns?.split(',') || [];

            searchInput.addEventListener('input', () => {
                this.performSearch(searchInput.value, targetSelector, searchColumns);
            });
        });
    }

    performSearch(query, targetSelector, columns = []) {
        const rows = document.querySelectorAll(targetSelector);
        const searchTerm = query.toLowerCase().trim();

        rows.forEach(row => {
            if (!searchTerm) {
                row.style.display = '';
                return;
            }

            let matches = false;

            if (columns.length > 0) {
                // Search specific columns
                columns.forEach(columnIndex => {
                    const cell = row.children[parseInt(columnIndex)];
                    if (cell && cell.textContent.toLowerCase().includes(searchTerm)) {
                        matches = true;
                    }
                });
            } else {
                // Search all text content
                if (row.textContent.toLowerCase().includes(searchTerm)) {
                    matches = true;
                }
            }

            row.style.display = matches ? '' : 'none';
        });
    }

    // Slug Generation
    initializeSlugGeneration() {
        document.querySelectorAll('[data-slug-source]').forEach(sourceInput => {
            const targetSelector = sourceInput.dataset.slugTarget;
            const targetInput = document.querySelector(targetSelector);

            if (!targetInput) return;

            let isManuallyEdited = false;

            // Track manual edits
            targetInput.addEventListener('input', () => {
                isManuallyEdited = true;
            });

            // Auto-generate slug
            sourceInput.addEventListener('input', () => {
                if (!isManuallyEdited) {
                    targetInput.value = this.generateSlug(sourceInput.value);
                }
            });
        });
    }

    generateSlug(text) {
        return text
            .toLowerCase()
            .trim()
            .replace(/[^\w\s-]/g, '')
            .replace(/[\s_-]+/g, '-')
            .replace(/^-+|-+$/g, '');
    }

    // Table Sorting
    sortTable(header) {
        const table = header.closest('.admin-table');
        const tbody = table.querySelector('tbody');
        const rows = Array.from(tbody.querySelectorAll('tr'));
        const columnIndex = Array.from(header.parentNode.children).indexOf(header);
        const currentOrder = header.dataset.sortOrder || 'asc';
        const newOrder = currentOrder === 'asc' ? 'desc' : 'asc';

        // Clear other sort indicators
        table.querySelectorAll('th').forEach(th => {
            th.removeAttribute('data-sort-order');
            th.classList.remove('admin-sort-asc', 'admin-sort-desc');
        });

        // Set new sort order
        header.dataset.sortOrder = newOrder;
        header.classList.add(`admin-sort-${newOrder}`);

        // Sort rows
        rows.sort((a, b) => {
            const aValue = a.children[columnIndex]?.textContent.trim() || '';
            const bValue = b.children[columnIndex]?.textContent.trim() || '';

            let comparison;
            if (!isNaN(aValue) && !isNaN(bValue)) {
                comparison = parseFloat(aValue) - parseFloat(bValue);
            } else {
                comparison = aValue.localeCompare(bValue);
            }

            return newOrder === 'asc' ? comparison : -comparison;
        });

        // Reappend sorted rows
        rows.forEach(row => tbody.appendChild(row));
    }

    // Row Selection
    setupRowSelection() {
        const selectAllCheckbox = document.querySelector('[data-select-all]');
        const rowCheckboxes = document.querySelectorAll('[data-select-row]');

        if (selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', () => {
                rowCheckboxes.forEach(checkbox => {
                    checkbox.checked = selectAllCheckbox.checked;
                });
                this.updateBulkActions();
            });
        }

        rowCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', () => {
                this.updateSelectAll();
                this.updateBulkActions();
            });
        });
    }

    updateSelectAll() {
        const selectAllCheckbox = document.querySelector('[data-select-all]');
        const rowCheckboxes = document.querySelectorAll('[data-select-row]');

        if (!selectAllCheckbox) return;

        const checkedCount = Array.from(rowCheckboxes).filter(cb => cb.checked).length;

        selectAllCheckbox.checked = checkedCount === rowCheckboxes.length;
        selectAllCheckbox.indeterminate = checkedCount > 0 && checkedCount < rowCheckboxes.length;
    }

    updateBulkActions() {
        const checkedRows = document.querySelectorAll('[data-select-row]:checked');
        const bulkActions = document.querySelector('[data-bulk-actions]');

        if (bulkActions) {
            bulkActions.style.display = checkedRows.length > 0 ? 'block' : 'none';
        }
    }

    // Form Validation
    validateField(field) {
        const value = field.value.trim();
        const rules = {
            required: field.hasAttribute('required'),
            minLength: field.getAttribute('minlength'),
            maxLength: field.getAttribute('maxlength'),
            pattern: field.getAttribute('pattern'),
            type: field.getAttribute('type')
        };

        let isValid = true;

        if (rules.required && !value) {
            isValid = false;
        } else if (value && rules.minLength && value.length < parseInt(rules.minLength)) {
            isValid = false;
        } else if (value && rules.maxLength && value.length > parseInt(rules.maxLength)) {
            isValid = false;
        } else if (value && rules.pattern && !new RegExp(rules.pattern).test(value)) {
            isValid = false;
        } else if (value && rules.type === 'email' && !this.isValidEmail(value)) {
            isValid = false;
        }

        this.updateFieldValidation(field, isValid);
        return isValid;
    }

    validateForm(form) {
        const fields = form.querySelectorAll('.admin-input[required]');
        let isValid = true;

        fields.forEach(field => {
            if (!this.validateField(field)) {
                isValid = false;
            }
        });

        return isValid;
    }

    updateFieldValidation(field, isValid) {
        field.classList.toggle('admin-input-error', !isValid);
        field.classList.toggle('admin-input-success', isValid);
    }

    isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    // Loading States
    setLoadingState(element, loading) {
        if (loading) {
            element.classList.add('admin-loading');
            element.disabled = true;
        } else {
            element.classList.remove('admin-loading');
            element.disabled = false;
        }
    }

    // Modal functionality
    openModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('admin-hidden');
            document.body.style.overflow = 'hidden';
        }
    }

    closeModal(modal) {
        if (modal) {
            modal.classList.add('admin-hidden');
            document.body.style.overflow = '';
        }
    }

    // Tooltips
    initializeTooltips() {
        document.querySelectorAll('[data-tooltip]').forEach(element => {
            element.addEventListener('mouseenter', (e) => {
                this.showTooltip(e.target, e.target.dataset.tooltip);
            });

            element.addEventListener('mouseleave', () => {
                this.hideTooltip();
            });
        });
    }

    showTooltip(element, text) {
        const tooltip = document.createElement('div');
        tooltip.className = 'admin-tooltip';
        tooltip.textContent = text;
        tooltip.style.cssText = `
            position: absolute;
            background: var(--admin-gray-900);
            color: white;
            padding: 0.5rem;
            border-radius: var(--admin-border-radius);
            font-size: 0.75rem;
            z-index: 1000;
            pointer-events: none;
            opacity: 0;
            transition: opacity 0.2s;
        `;

        document.body.appendChild(tooltip);

        const rect = element.getBoundingClientRect();
        tooltip.style.left = `${rect.left + rect.width / 2 - tooltip.offsetWidth / 2}px`;
        tooltip.style.top = `${rect.top - tooltip.offsetHeight - 5}px`;

        setTimeout(() => {
            tooltip.style.opacity = '1';
        }, 10);

        this.currentTooltip = tooltip;
    }

    hideTooltip() {
        if (this.currentTooltip) {
            this.currentTooltip.style.opacity = '0';
            setTimeout(() => {
                if (this.currentTooltip) {
                    this.currentTooltip.remove();
                    this.currentTooltip = null;
                }
            }, 200);
        }
    }

    // Copy to Clipboard
    initializeCopyButtons() {
        document.querySelectorAll('[data-copy]').forEach(button => {
            button.addEventListener('click', () => {
                const text = button.dataset.copy;
                this.copyToClipboard(text);
                console.log('Copied to clipboard:', text);
            });
        });
    }

    copyToClipboard(text) {
        if (navigator.clipboard) {
            navigator.clipboard.writeText(text);
        } else {
            // Fallback for older browsers
            const textArea = document.createElement('textarea');
            textArea.value = text;
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();
            document.execCommand('copy');
            document.body.removeChild(textArea);
        }
    }

    // Status Badge Helper
    createStatusBadge(status) {
        const statusConfig = {
            'published': { class: 'admin-badge-success', icon: 'fas fa-check-circle' },
            'draft': { class: 'admin-badge-gray', icon: 'fas fa-edit' },
            'pending_review': { class: 'admin-badge-warning', icon: 'fas fa-clock' },
            'rejected': { class: 'admin-badge-error', icon: 'fas fa-times-circle' },
            'active': { class: 'admin-badge-success', icon: 'fas fa-check' },
            'inactive': { class: 'admin-badge-gray', icon: 'fas fa-pause' }
        };

        const config = statusConfig[status] || statusConfig['draft'];
        const statusText = status.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase());

        return `<span class="admin-badge ${config.class}">
            <i class="${config.icon}"></i>
            ${statusText}
        </span>`;
    }

    // Utility Methods
    formatDate(dateString, options = {}) {
        const defaultOptions = {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        };

        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', { ...defaultOptions, ...options });
    }

    formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';

        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));

        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    // Dashboard-specific functionality
    initializeDashboard() {
        // Add click tracking for dashboard actions
        document.querySelectorAll('.admin-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                console.log('Dashboard action clicked:', this.textContent.trim());
            });
        });

        // Initialize dashboard charts if Chart.js is available
        if (typeof Chart !== 'undefined') {
            this.initializeCharts();
        }
    }

    // Chart initialization for dashboard
    initializeCharts() {
        // Projects Chart
        const projectsChartCtx = document.getElementById('projectsChart');
        if (projectsChartCtx) {
            new Chart(projectsChartCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Completed', 'In Progress', 'Pending'],
                    datasets: [{
                        data: [12, 8, 3],
                        backgroundColor: [
                            'var(--admin-success)',
                            'var(--admin-warning)',
                            'var(--admin-error)'
                        ],
                        borderColor: 'transparent',
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                color: 'var(--admin-text-primary)',
                                usePointStyle: true,
                                padding: 20
                            }
                        }
                    }
                }
            });
        }

        // Activity Chart
        const activityChartCtx = document.getElementById('activityChart');
        if (activityChartCtx) {
            new Chart(activityChartCtx, {
                type: 'line',
                data: {
                    labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                    datasets: [{
                        label: 'Activities',
                        data: [12, 19, 8, 15, 22, 8, 14],
                        borderColor: 'var(--admin-primary)',
                        backgroundColor: 'var(--admin-primary-bg)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'var(--admin-border)'
                            },
                            ticks: {
                                color: 'var(--admin-text-muted)'
                            }
                        },
                        x: {
                            grid: {
                                color: 'var(--admin-border)'
                            },
                            ticks: {
                                color: 'var(--admin-text-muted)'
                            }
                        }
                    }
                }
            });
        }
    }

    // Contact form functionality
    initializeContactForm() {
        const form = document.getElementById('contactForm');
        const submitBtn = document.getElementById('submitBtn');
        const btnText = document.getElementById('btnText');

        if (!form) return;

        form.addEventListener('submit', async (e) => {
            e.preventDefault();

            // Set loading state
            submitBtn.disabled = true;
            btnText.textContent = 'Sending...';

            const formData = new FormData(form);

            try {
                const response = await fetch('/api/contact', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    console.log('Message sent successfully');
                    form.reset();
                } else {
                    console.error('Failed to send message:', result.message);
                }
            } catch (error) {
                console.error('An error occurred while sending message:', error);
            } finally {
                submitBtn.disabled = false;
                btnText.textContent = 'Send Message';
            }
        });
    }

    // Project progress circle functionality
    initializeProgressCircles() {
        document.querySelectorAll('.progress-circle').forEach(circle => {
            const progress = circle.dataset.progress || 0;
            const svg = circle.querySelector('.progress-ring');
            const progressText = circle.querySelector('.progress-text');

            if (svg && progressText) {
                const radius = 35;
                const circumference = 2 * Math.PI * radius;
                const offset = circumference - (progress / 100) * circumference;

                svg.style.strokeDasharray = circumference;
                svg.style.strokeDashoffset = offset;
                progressText.textContent = `${progress}%`;
            }
        });
    }

    // Milestone timeline functionality
    initializeMilestones() {
        document.querySelectorAll('.milestone-item').forEach((item) => {
            const marker = item.querySelector('.milestone-marker');
            const status = item.dataset.status || 'pending';

            if (marker) {
                marker.classList.add(`milestone-${status}`);

                // Add icons based on status
                const icon = document.createElement('i');
                switch (status) {
                    case 'completed':
                        icon.className = 'fas fa-check';
                        break;
                    case 'in-progress':
                        icon.className = 'fas fa-play';
                        break;
                    default:
                        icon.className = 'fas fa-clock';
                }
                marker.appendChild(icon);
            }
        });
    }

    // Portfolio image upload functionality
    initializeImageUpload() {
        const dropZones = document.querySelectorAll('.image-drop-zone');

        dropZones.forEach(dropZone => {
            const input = dropZone.querySelector('input[type="file"]');
            const preview = dropZone.querySelector('.image-preview');

            // Drag and drop handlers
            dropZone.addEventListener('dragover', (e) => {
                e.preventDefault();
                dropZone.classList.add('dragover');
            });

            dropZone.addEventListener('dragleave', () => {
                dropZone.classList.remove('dragover');
            });

            dropZone.addEventListener('drop', (e) => {
                e.preventDefault();
                dropZone.classList.remove('dragover');

                const files = e.dataTransfer.files;
                if (files.length > 0) {
                    input.files = files;
                    this.handleImagePreview(files[0], preview);
                }
            });

            // File input change handler
            input.addEventListener('change', (e) => {
                const file = e.target.files[0];
                if (file) {
                    this.handleImagePreview(file, preview);
                }
            });
        });
    }

    // Handle image preview
    handleImagePreview(file, previewContainer) {
        if (file && file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = (e) => {
                previewContainer.innerHTML = `<img src="${e.target.result}" alt="Preview" style="max-width: 100%; height: auto; border-radius: var(--admin-border-radius);">`;
            };
            reader.readAsDataURL(file);
        }
    }

    // Tech stack badge management
    initializeTechBadges() {
        const techInputs = document.querySelectorAll('.tech-input');

        techInputs.forEach(input => {
            const container = input.nextElementSibling;

            input.addEventListener('keypress', (e) => {
                if (e.key === 'Enter' && input.value.trim()) {
                    e.preventDefault();
                    this.addTechBadge(input.value.trim(), container);
                    input.value = '';
                }
            });
        });

        // Initialize existing badges with remove functionality
        document.querySelectorAll('.tech-badge').forEach(badge => {
            this.addRemoveBadgeHandler(badge);
        });
    }

    addTechBadge(tech, container) {
        const badge = document.createElement('span');
        badge.className = 'admin-badge tech-badge';
        badge.innerHTML = `
            ${tech}
            <button type="button" class="badge-remove" style="margin-left: 0.5rem; background: none; border: none; color: inherit; cursor: pointer;">×</button>
            <input type="hidden" name="technologies[]" value="${tech}">
        `;

        container.appendChild(badge);
        this.addRemoveBadgeHandler(badge);
    }

    addRemoveBadgeHandler(badge) {
        const removeBtn = badge.querySelector('.badge-remove');
        if (removeBtn) {
            removeBtn.addEventListener('click', () => {
                badge.remove();
            });
        }
    }

    // System Monitor functionality
    initializeSystemMonitor() {
        this.initializeLogViewer();
        this.initializeSystemMetrics();
        this.initializeRealTimeUpdates();
        this.initializeLogSearch();
    }

    // Log viewer functionality
    initializeLogViewer() {
        const logContainer = document.getElementById('log-container');
        if (!logContainer) return;

        // Load logs on page load
        this.loadLogs();

        // Setup filter change handlers
        ['log-type', 'log-level', 'log-lines'].forEach(id => {
            const element = document.getElementById(id);
            if (element) {
                element.addEventListener('change', () => this.loadLogs());
            }
        });

        // Setup deprecated messages toggle
        const toggleBtn = document.getElementById('toggle-deprecated');
        if (toggleBtn) {
            toggleBtn.addEventListener('click', () => this.toggleDeprecatedMessages());
        }

        // Auto-refresh logs every 30 seconds
        setInterval(() => {
            this.loadLogs(true); // Silent refresh
        }, 30000);
    }

    // Load logs via AJAX
    async loadLogs(silent = false) {
        const logContainer = document.getElementById('log-container');
        const logType = document.getElementById('log-type')?.value || 'app';
        const logLevel = document.getElementById('log-level')?.value || '';
        const logLines = document.getElementById('log-lines')?.value || '100';

        if (!silent) {
            logContainer.innerHTML = '<div class="log-loading"><i class="fas fa-spinner fa-spin"></i> Loading logs...</div>';
        }

        try {
            const params = new URLSearchParams({
                page: 'system_monitor',
                action: 'ajax',
                type: 'logs',
                log_type: logType,
                level: logLevel,
                lines: logLines
            });

            const response = await fetch(`/index.php?${params}`);
            const data = await response.json();

            // Handle flash messages using global toast system
            if (data.flash_messages && window.showToast) {
                Object.keys(data.flash_messages).forEach(type => {
                    const messages = data.flash_messages[type];
                    if (Array.isArray(messages)) {
                        messages.forEach(message => {
                            const text = message.text || message;
                            window.showToast(text, type);
                        });
                    }
                });
            }

            if (data.error) {
                logContainer.innerHTML = `<div style="color: var(--admin-error); padding: 1rem; text-align: center;"><i class="fas fa-exclamation-triangle"></i> Error: ${data.error}</div>`;

                // Show error using global toast system
                if (window.showToast) {
                    window.showToast(`Failed to load logs: ${data.error}`, 'error');
                }
            } else if (data.logs && data.logs.length > 0) {
                logContainer.innerHTML = data.logs.map(log =>
                    `<div class="log-entry ${log.level ? 'log-' + log.level.toLowerCase() : ''}">${this.escapeHtml(log.message)}</div>`
                ).join('');

                // Apply deprecated filter if active
                if (this.hideDeprecated) {
                    this.applyDeprecatedFilter();
                }
            } else {
                logContainer.innerHTML = '<div style="color: var(--admin-text-muted); text-align: center; padding: 2rem;"><i class="fas fa-info-circle"></i> No logs found</div>';
            }
        } catch (error) {
            console.error('Error loading logs:', error);

            // Show error using global toast system
            if (window.showToast) {
                window.showToast(`Network error while loading logs: ${error.message}`, 'error');
            }

            if (!silent) {
                logContainer.innerHTML = '<div style="color: var(--admin-error); text-align: center; padding: 2rem;"><i class="fas fa-exclamation-triangle"></i> Failed to load logs - Network error</div>';
            }
        }
    }

    // Toggle deprecated messages visibility
    toggleDeprecatedMessages() {
        this.hideDeprecated = !this.hideDeprecated;
        const button = document.getElementById('toggle-deprecated');

        if (this.hideDeprecated) {
            button.innerHTML = '<i class="fas fa-eye"></i> Show Deprecated';
            button.classList.remove('admin-btn-secondary');
            button.classList.add('admin-btn-warning');
        } else {
            button.innerHTML = '<i class="fas fa-eye-slash"></i> Hide Deprecated';
            button.classList.remove('admin-btn-warning');
            button.classList.add('admin-btn-secondary');
        }

        this.applyDeprecatedFilter();
    }

    applyDeprecatedFilter() {
        const logEntries = document.querySelectorAll('.log-entry');
        logEntries.forEach(entry => {
            const isDeprecated = entry.textContent.includes('E_STRICT is deprecated') ||
                                 entry.textContent.includes('Constant E_STRICT is deprecated');
            entry.style.display = (this.hideDeprecated && isDeprecated) ? 'none' : '';
        });
    }

    // System metrics functionality
    initializeSystemMetrics() {
        // Check if user has admin role before initializing metrics
        const userRole = this.getUserRole();
        if (userRole !== 'admin') {
            console.log('System metrics disabled - user role:', userRole);
            return;
        }

        this.updateSystemMetrics();

        // Refresh metrics every 60 seconds
        setInterval(() => {
            this.updateSystemMetrics();
        }, 60000);
    }

    // Get user role from page or session data
    getUserRole() {
        // Try to get user role from a data attribute on body or html element
        const bodyElement = document.body;
        if (bodyElement && bodyElement.dataset.userRole) {
            return bodyElement.dataset.userRole;
        }

        // Try to get from a meta tag
        const roleMetaTag = document.querySelector('meta[name="user-role"]');
        if (roleMetaTag) {
            return roleMetaTag.getAttribute('content');
        }

        // Try to get from global JavaScript variable if it exists
        if (typeof window.userRole !== 'undefined') {
            return window.userRole;
        }

        // Default fallback - assume non-admin
        return 'guest';
    }

    async updateSystemMetrics() {
        // Double-check admin role before making AJAX request
        const userRole = this.getUserRole();
        if (userRole !== 'admin') {
            console.log('Skipping metrics update - insufficient permissions');
            return;
        }

        try {
            const response = await fetch('/index.php?page=system_monitor&action=ajax&type=status');

            // Check if response is actually JSON and not an HTML error page
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                console.warn('System metrics endpoint returned non-JSON response, likely access denied');
                return;
            }

            const data = await response.json();

            // Handle flash messages using global toast system
            if (data.flash_messages && window.showToast) {
                Object.keys(data.flash_messages).forEach(type => {
                    const messages = data.flash_messages[type];
                    if (Array.isArray(messages)) {
                        messages.forEach(message => {
                            const text = message.text || message;
                            window.showToast(text, type);
                        });
                    }
                });
            }

            if (!data.error) {
                this.updateMetricCards(data);
                this.updateSystemInfo(data);
            } else {
                // Show error for metrics update
                if (window.showToast) {
                    window.showToast(`Failed to update system metrics: ${data.error}`, 'error');
                }
                console.error('Error in system metrics response:', data.error);
            }
        } catch (error) {
            // Show network error for metrics update
            if (window.showToast) {
                window.showToast(`Network error while updating metrics: ${error.message}`, 'error');
            }
            console.error('Error updating system metrics:', error);
        }
    }

    updateMetricCards(data) {
        // Update database status
        const dbCard = document.querySelector('[data-metric="database"]');
        if (dbCard && data.database) {
            const statusBadge = dbCard.querySelector('.admin-badge');
            if (statusBadge) {
                statusBadge.className = `admin-badge admin-badge-${data.database.status === 'connected' ? 'success' : 'error'}`;
                statusBadge.innerHTML = `<i class="fas fa-${data.database.status === 'connected' ? 'check' : 'times'}"></i>`;
            }
        }

        // Update memory usage bar
        const memoryBar = document.querySelector('.memory-usage-fill');
        if (memoryBar && data.performance && data.performance.memory_usage) {
            const usage = data.performance.memory_usage.percentage || 0;
            memoryBar.style.width = `${usage}%`;
        }

        // Update disk space indicator
        const diskBar = document.querySelector('.disk-space-fill');
        if (diskBar && data.performance && data.performance.disk_space) {
            const usage = data.performance.disk_space.percentage || 0;
            diskBar.style.width = `${usage}%`;

            // Update color based on usage
            diskBar.className = 'disk-space-fill';
            if (usage > 90) diskBar.classList.add('disk-space-low');
            else if (usage > 70) diskBar.classList.add('disk-space-medium');
            else diskBar.classList.add('disk-space-good');
        }
    }

    updateSystemInfo(data) {
        // Update system info values
        const infoItems = document.querySelectorAll('.system-info-item');
        infoItems.forEach(item => {
            const label = item.querySelector('.system-info-label')?.textContent || '';
            const valueEl = item.querySelector('.system-info-value');

            if (valueEl) {
                if (label.includes('PHP Version') && data.performance) {
                    valueEl.textContent = data.performance.php_version || 'Unknown';
                } else if (label.includes('Memory') && data.performance) {
                    valueEl.textContent = data.performance.memory_usage?.current || 'Unknown';
                }
            }
        });
    }

    // Real-time updates indicator
    initializeRealTimeUpdates() {
        const indicator = document.querySelector('.realtime-indicator');
        if (indicator) {
            // Test connection periodically
            setInterval(() => {
                this.testConnection(indicator);
            }, 10000);
        }
    }

    async testConnection(indicator) {
        try {
            const response = await fetch('/index.php?page=system_monitor&action=ajax&type=status', {
                timeout: 5000
            });

            if (response.ok) {
                indicator.classList.remove('disconnected');
                indicator.textContent = 'Connected';
            } else {
                indicator.classList.add('disconnected');
                indicator.textContent = 'Connection Issues';
            }
        } catch (error) {
            indicator.classList.add('disconnected');
            indicator.textContent = 'Disconnected';
        }
    }

    // Clear logs functionality
    async clearSystemLogs() {
        if (!confirm('Are you sure you want to clear all log files? This action cannot be undone.')) {
            return;
        }

        try {
            const response = await fetch('/index.php?page=system_monitor&action=ajax&type=clear_logs', {
                method: 'POST'
            });

            const data = await response.json();

            if (data.error) {
                // Show error message
                if (window.showToast) {
                    window.showToast(`Error clearing logs: ${data.error}`, 'error');
                }
                console.error('Error clearing logs:', data.error);
                return;
            }

            // Handle successful response
            const filesCleared = data.files_cleared || 0;

            // Handle flash messages from server first (if any)
            if (data.flash_messages && window.showToast) {
                Object.keys(data.flash_messages).forEach(type => {
                    const messages = data.flash_messages[type];
                    if (Array.isArray(messages)) {
                        messages.forEach(message => {
                            const text = message.text || message;
                            window.showToast(text, type);
                        });
                    }
                });
            } else {
                // If no server flash messages, show our own success messages
                if (data.errors && data.errors.length > 0) {
                    if (window.showToast) {
                        window.showToast(`Logs partially cleared. ${filesCleared} files processed, but some errors occurred.`, 'warning', 6000);
                    }
                    console.warn(`Logs cleared with errors. Files processed: ${filesCleared}`, data.errors);
                } else {
                    if (window.showToast) {
                        window.showToast(`All logs cleared successfully! ${filesCleared} files processed.`, 'success');
                    }
                    console.log(`Logs cleared successfully! Files processed: ${filesCleared}`);
                }
            }

            // Refresh logs display
            setTimeout(() => {
                this.loadLogs();
            }, 1000);

        } catch (error) {
            if (window.showToast) {
                window.showToast(`Network error while clearing logs: ${error.message}`, 'error');
            }
            console.error('Error clearing logs:', error);
        }
    }

    // Refresh all system data
    refreshSystemData() {
        if (window.showToast) {
            window.showToast('Refreshing system data...', 'info', 2000);
        }
        console.log('Refreshing system data...');

        // Refresh logs
        this.loadLogs();

        // Refresh metrics
        this.updateSystemMetrics();

        // Show completion message
        setTimeout(() => {
            if (window.showToast) {
                window.showToast('System data refreshed successfully!', 'success');
            }
            console.log('System data refreshed successfully');
        }, 1500);
    }

    // Export system data
    async exportSystemData() {
        try {
            if (window.showToast) {
                window.showToast('Preparing system data export...', 'info', 3000);
            }

            const response = await fetch('/index.php?page=system_monitor&action=ajax&type=export');
            const data = await response.json();

            // Handle flash messages using global toast system
            if (data.flash_messages && window.showToast) {
                Object.keys(data.flash_messages).forEach(type => {
                    const messages = data.flash_messages[type];
                    if (Array.isArray(messages)) {
                        messages.forEach(message => {
                            const text = message.text || message;
                            window.showToast(text, type);
                        });
                    }
                });
            }

            if (data.error) {
                if (window.showToast) {
                    window.showToast(`Export failed: ${data.error}`, 'error');
                }
                console.error('Export failed:', data.error);
            } else {
                // Create and download file
                const blob = new Blob([JSON.stringify(data, null, 2)], {type: 'application/json'});
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `system-data-${new Date().toISOString().split('T')[0]}.json`;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                window.URL.revokeObjectURL(url);

                if (window.showToast) {
                    window.showToast('System data exported successfully! Download started.', 'success');
                }
                console.log('System data exported successfully');
            }
        } catch (error) {
            if (window.showToast) {
                window.showToast(`Export error: ${error.message}`, 'error');
            }
            console.error('Export error:', error);
        }
    }

    // Log search functionality
    initializeLogSearch() {
        const searchInput = document.getElementById('log-search');
        if (!searchInput) return;

        const debouncedSearch = this.debounce((query) => {
            this.searchLogs(query);
        }, 300);

        searchInput.addEventListener('input', (e) => {
            debouncedSearch(e.target.value);
        });
    }

    searchLogs(query) {
        const logEntries = document.querySelectorAll('.log-entry');
        const searchTerm = query.toLowerCase().trim();

        logEntries.forEach(entry => {
            if (!searchTerm) {
                entry.style.display = '';
                return;
            }

            const matches = entry.textContent.toLowerCase().includes(searchTerm);
            entry.style.display = matches ? '' : 'none';
        });
    }

    // Escape HTML for security
    escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }
}

// Auto-initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.adminPanel = new AdminPanel();
});

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = AdminPanel;
}
