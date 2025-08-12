/**
 * Management Pages JavaScript - Clean Version
 * Только необходимый функционал без автосохранения и предупреждений
 */

(function() {
    'use strict';

    // Глобальный объект для управления страницами
    window.ManagePages = {

        // Инициализация фильтрации и поиска в таблицах
        initTableFiltering: function() {
            const searchInput = document.getElementById('articleSearch');
            const categoryFilter = document.getElementById('categoryFilter');
            const authorFilter = document.getElementById('authorFilter');
            const statusFilter = document.getElementById('statusFilter');

            if (!searchInput) return;

            const debounce = (func, wait) => {
                let timeout;
                return function executedFunction(...args) {
                    const later = () => {
                        clearTimeout(timeout);
                        func(...args);
                    };
                    clearTimeout(timeout);
                    timeout = setTimeout(later, wait);
                };
            };

            const filterArticles = () => {
                const searchTerm = searchInput.value.toLowerCase();
                const categoryFilter_value = categoryFilter?.value.toLowerCase() || '';
                const authorFilter_value = authorFilter?.value.toLowerCase() || '';
                const statusFilter_value = statusFilter?.value.toLowerCase() || '';

                const rows = document.querySelectorAll('.article-row');
                let visibleCount = 0;

                rows.forEach(row => {
                    const title = row.dataset.title || '';
                    const author = row.dataset.author || '';
                    const categories = row.dataset.categories || '';
                    const status = row.dataset.status || '';

                    let showRow = true;

                    if (searchTerm && !title.includes(searchTerm)) showRow = false;
                    if (categoryFilter_value && !categories.includes(categoryFilter_value)) showRow = false;
                    if (authorFilter_value && !author.includes(authorFilter_value)) showRow = false;
                    if (statusFilter_value && statusFilter_value !== 'all' && !status.includes(statusFilter_value)) showRow = false;

                    row.style.display = showRow ? '' : 'none';
                    if (showRow) visibleCount++;
                });

                this.updateResultsInfo(visibleCount);
            };

            // Обработчик изменения фильтра статуса с обновлением URL
            if (statusFilter) {
                statusFilter.addEventListener('change', function() {
                    const selectedStatus = this.value;

                    // Обновляем URL для отражения состояния фильтра
                    const url = new URL(window.location);
                    if (selectedStatus === 'all') {
                        url.searchParams.delete('filter');
                    } else {
                        url.searchParams.set('filter', selectedStatus);
                    }

                    // Переходим к новому URL для перезагрузки с серверной фильтрацией
                    window.location.href = url.toString();
                });
            }

            // Обработчики для других фильтров
            searchInput.addEventListener('input', debounce(filterArticles, 300));
            categoryFilter?.addEventListener('change', filterArticles);
            authorFilter?.addEventListener('change', filterArticles);
        },

        updateResultsInfo: function(visibleCount = null) {
            const resultsInfo = document.getElementById('resultsInfo');
            if (!resultsInfo) return;

            if (visibleCount === null) {
                const totalRows = document.querySelectorAll('.article-row').length;
                resultsInfo.textContent = `Showing ${totalRows} articles`;
            } else {
                const totalRows = document.querySelectorAll('.article-row').length;
                resultsInfo.textContent = `Showing ${visibleCount} of ${totalRows} articles`;
            }
        },

        // Сортировка таблиц
        initTableSorting: function() {
            let sortDirection = {};

            document.querySelectorAll('.sortable').forEach(header => {
                header.addEventListener('click', () => {
                    const column = header.dataset.sort;
                    this.sortTable(column, sortDirection);
                });
            });
        },

        sortTable: function(column, sortDirection) {
            const tbody = document.getElementById('articlesTableBody');
            if (!tbody) return;

            const rows = Array.from(tbody.querySelectorAll('.article-row'));

            // Переключаем направление сортировки
            sortDirection[column] = sortDirection[column] === 'asc' ? 'desc' : 'asc';
            const isAsc = sortDirection[column] === 'asc';

            // Обновляем иконки сортировки
            document.querySelectorAll('.sort-icon').forEach(icon => {
                icon.className = 'fas fa-sort sort-icon';
            });

            const currentHeader = document.querySelector(`[data-sort="${column}"] .sort-icon`);
            if (currentHeader) {
                currentHeader.className = `fas fa-sort-${isAsc ? 'up' : 'down'} sort-icon active`;
            }

            // Сортируем строки
            rows.sort((a, b) => {
                let aVal, bVal;

                switch (column) {
                    case 'id':
                        aVal = parseInt(a.dataset.id) || 0;
                        bVal = parseInt(b.dataset.id) || 0;
                        break;
                    case 'title':
                        aVal = a.dataset.title || '';
                        bVal = b.dataset.title || '';
                        break;
                    case 'author':
                        aVal = a.dataset.author || '';
                        bVal = b.dataset.author || '';
                        break;
                    case 'date':
                        aVal = new Date(a.dataset.date || '1970-01-01');
                        bVal = new Date(b.dataset.date || '1970-01-01');
                        break;
                    case 'status':
                        aVal = a.dataset.status || '';
                        bVal = b.dataset.status || '';
                        break;
                    default:
                        return 0;
                }

                if (aVal < bVal) return isAsc ? -1 : 1;
                if (aVal > bVal) return isAsc ? 1 : -1;
                return 0;
            });

            // Перестраиваем tbody
            rows.forEach(row => tbody.appendChild(row));
        },

        // Управление категориями
        initCategoryManagement: function() {
            const nameInput = document.getElementById('category_name');
            const slugInput = document.getElementById('category_slug');

            if (!nameInput || !slugInput) return;

            let isSlugManuallyEdited = false;

            slugInput.addEventListener('input', () => {
                isSlugManuallyEdited = true;
            });

            nameInput.addEventListener('input', () => {
                if (!isSlugManuallyEdited) {
                    const slug = this.generateSlug(nameInput.value);
                    slugInput.value = slug;
                }
            });
        },

        generateSlug: function(text) {
            return text
                .toLowerCase()
                .trim()
                .replace(/[^\w\s-]/g, '')
                .replace(/[\s_-]+/g, '-')
                .replace(/^-+|-+$/g, '');
        },

        // Базовая валидация форм (без автосохранения)
        initFormValidation: function() {
            const forms = document.querySelectorAll('.article-creation-form, .edit-article-form, .category-form');

            forms.forEach(form => {
                form.addEventListener('submit', (e) => {
                    if (!this.validateForm(form)) {
                        e.preventDefault();
                    }
                });
            });
        },

        validateForm: function(form) {
            const errors = [];

            // Валидация формы статьи
            if (form.classList.contains('article-creation-form') || form.classList.contains('edit-article-form')) {
                const title = form.querySelector('#title')?.value.trim() || '';

                if (!title) {
                    errors.push('Article title is required');
                }
                if (title.length > 200) {
                    errors.push('Title is too long (max 200 characters)');
                }
            }

            // Валидация формы категории
            if (form.classList.contains('category-form')) {
                const name = form.querySelector('#category_name')?.value.trim() || '';
                const slug = form.querySelector('#category_slug')?.value || '';

                if (!name) {
                    errors.push('Category name is required');
                }
                if (name.length > 100) {
                    errors.push('Category name is too long (max 100 characters)');
                }
                if (slug && !slug.match(/^[a-z0-9]+(?:-[a-z0-9]+)*$/)) {
                    errors.push('Slug can only contain lowercase letters, numbers, and hyphens');
                }
            }

            if (errors.length > 0) {
                alert('Please fix the following errors:\n\n' + errors.join('\n'));
                return false;
            }

            return true;
        },

        // Показ уведомлений
        showNotification: function(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `notification notification-${type}`;
            notification.innerHTML = `
                <div class="notification-content">
                    <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
                    <span>${message}</span>
                </div>
                <button class="notification-close" onclick="this.parentElement.remove()">
                    <i class="fas fa-times"></i>
                </button>
            `;

            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: var(--color-${type === 'success' ? 'success' : type === 'error' ? 'error' : 'info'});
                color: white;
                padding: 1rem;
                border-radius: 6px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.3);
                z-index: 1000;
                max-width: 400px;
                animation: slideInRight 0.3s ease;
            `;

            document.body.appendChild(notification);

            // Автоматическое удаление через 5 секунд
            setTimeout(() => {
                if (notification.parentElement) {
                    notification.remove();
                }
            }, 5000);
        },

        // Основная инициализация
        init: function() {
            // Ждем полной загрузки DOM
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', () => this.init());
                return;
            }

            // Инициализируем все компоненты
            this.initTableFiltering();
            this.initTableSorting();
            this.initCategoryManagement();
            this.initFormValidation();

            console.log('ManagePages initialized - no auto-save or unload warnings');
        }
    };

    // Автоинициализация при загрузке
    document.addEventListener('DOMContentLoaded', function() {
        window.ManagePages.init();
    });

})();
