/**
 * MANAGE ARTICLES PAGE JAVASCRIPT - DARKHEIM STUDIO
 * Enhanced Articles Management functionality
 * No animations for improved performance
 */

class ManageArticlesController {
    constructor() {
        this.sortDirection = {};
        this.init();
    }

    init() {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.initializeComponents());
        } else {
            this.initializeComponents();
        }
    }

    initializeComponents() {
        this.initializeSearch();
        this.initializeFilters();
        this.initializeSorting();
        this.initializeBulkActions();
        this.updateResultsInfo();
    }

    // Initialize search functionality
    initializeSearch() {
        const searchInput = document.getElementById('articleSearch');
        if (searchInput) {
            searchInput.addEventListener('input', this.debounce(this.filterArticles.bind(this), 300));
        }
    }

    // Initialize filter functionality
    initializeFilters() {
        const filters = ['categoryFilter', 'dateFilter', 'authorFilter', 'statusFilter'];

        filters.forEach(filterId => {
            const filter = document.getElementById(filterId);
            if (filter) {
                filter.addEventListener('change', this.filterArticles.bind(this));
            }
        });
    }

    // Initialize sorting functionality
    initializeSorting() {
        document.querySelectorAll('.sortable').forEach(header => {
            header.addEventListener('click', () => this.sortTable(header.dataset.sort));
        });
    }

    // Initialize bulk selection and actions
    initializeBulkActions() {
        const selectAllCheckbox = document.getElementById('selectAllCheckbox');
        if (selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', this.toggleSelectAll.bind(this));
        }

        document.querySelectorAll('.article-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', this.updateBulkActions.bind(this));
        });
    }

    // Debounce function for search input
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func.apply(this, args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    // Filter articles based on search and filters
    filterArticles() {
        const searchTerm = this.getElementValue('articleSearch').toLowerCase();
        const categoryFilter = this.getElementValue('categoryFilter').toLowerCase();
        const dateFilter = this.getElementValue('dateFilter');
        const authorFilter = this.getElementValue('authorFilter').toLowerCase();
        const statusFilter = this.getElementValue('statusFilter').toLowerCase();

        const rows = document.querySelectorAll('.article-row');
        let visibleCount = 0;

        rows.forEach(row => {
            const title = row.dataset.title || '';
            const author = row.dataset.author || '';
            const categories = row.dataset.categories || '';
            const date = row.dataset.date || '';
            const status = row.dataset.status || '';

            let showRow = true;

            // Search filter
            if (searchTerm && !title.includes(searchTerm)) {
                showRow = false;
            }

            // Category filter
            if (categoryFilter && !categories.includes(categoryFilter)) {
                showRow = false;
            }

            // Author filter
            if (authorFilter && !author.includes(authorFilter)) {
                showRow = false;
            }

            // Date filter
            if (dateFilter) {
                showRow = this.checkDateFilter(date, dateFilter);
            }

            // Status filter
            if (statusFilter && (statusFilter === 'all' || !status.includes(statusFilter))) {
                showRow = false;
            }

            row.style.display = showRow ? '' : 'none';
            if (showRow) visibleCount++;
        });

        this.updateResultsInfo(visibleCount);
    }

    // Check date filter
    checkDateFilter(dateString, filterType) {
        if (!dateString || !filterType) return true;

        const articleDate = new Date(dateString);
        const now = new Date();

        switch (filterType) {
            case 'today':
                return articleDate.toDateString() === now.toDateString();
            case 'week':
                const weekAgo = new Date(now.getTime() - 7 * 24 * 60 * 60 * 1000);
                return articleDate >= weekAgo;
            case 'month':
                const monthAgo = new Date(now.getTime() - 30 * 24 * 60 * 60 * 1000);
                return articleDate >= monthAgo;
            case 'year':
                return articleDate.getFullYear() === now.getFullYear();
            default:
                return true;
        }
    }

    // Helper function to get element value safely
    getElementValue(elementId) {
        const element = document.getElementById(elementId);
        return element ? element.value : '';
    }

    // Sort table by column
    sortTable(column) {
        const tbody = document.getElementById('articlesTableBody');
        if (!tbody) return;

        const rows = Array.from(tbody.querySelectorAll('.article-row'));

        // Toggle sort direction
        this.sortDirection[column] = this.sortDirection[column] === 'asc' ? 'desc' : 'asc';
        const isAsc = this.sortDirection[column] === 'asc';

        // Update sort icons
        this.updateSortIcons(column, isAsc);

        // Sort rows
        rows.sort((a, b) => this.compareRows(a, b, column, isAsc));

        // Rebuild tbody
        rows.forEach(row => tbody.appendChild(row));
    }

    // Update sort icons
    updateSortIcons(activeColumn, isAsc) {
        document.querySelectorAll('.sort-icon').forEach(icon => {
            icon.className = 'fas fa-sort sort-icon';
        });

        const currentHeader = document.querySelector(`[data-sort="${activeColumn}"] .sort-icon`);
        if (currentHeader) {
            currentHeader.className = `fas fa-sort-${isAsc ? 'up' : 'down'} sort-icon active`;
        }
    }

    // Compare rows for sorting
    compareRows(a, b, column, isAsc) {
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
    }

    // Toggle select all articles
    toggleSelectAll() {
        const selectAllCheckbox = document.getElementById('selectAllCheckbox');
        const articleCheckboxes = document.querySelectorAll('.article-checkbox');

        if (!selectAllCheckbox) return;

        const isChecked = selectAllCheckbox.checked;

        articleCheckboxes.forEach(checkbox => {
            // Only select visible rows
            const row = checkbox.closest('.article-row');
            if (row && row.style.display !== 'none') {
                checkbox.checked = isChecked;
            }
        });

        this.updateBulkActions();
    }

    // Update bulk action visibility
    updateBulkActions() {
        const checkedBoxes = document.querySelectorAll('.article-checkbox:checked');
        const bulkActions = document.getElementById('bulkActions');
        const selectAllBtn = document.getElementById('selectAllBtn');

        if (checkedBoxes.length > 0) {
            if (bulkActions) {
                bulkActions.style.display = 'flex';
            }
            if (selectAllBtn) {
                selectAllBtn.innerHTML = '<i class="fas fa-times"></i><span>Deselect All</span>';
                selectAllBtn.onclick = this.deselectAll.bind(this);
            }
        } else {
            if (bulkActions) {
                bulkActions.style.display = 'none';
            }
            if (selectAllBtn) {
                selectAllBtn.innerHTML = '<i class="fas fa-check-square"></i><span>Select All</span>';
                selectAllBtn.onclick = this.toggleSelectAll.bind(this);
            }
        }
    }

    // Deselect all articles
    deselectAll() {
        document.querySelectorAll('.article-checkbox').forEach(checkbox => {
            checkbox.checked = false;
        });

        const selectAllCheckbox = document.getElementById('selectAllCheckbox');
        if (selectAllCheckbox) {
            selectAllCheckbox.checked = false;
        }

        this.updateBulkActions();
    }

    // Bulk delete functionality
    bulkDelete() {
        const checkedBoxes = document.querySelectorAll('.article-checkbox:checked');
        const articleIds = Array.from(checkedBoxes).map(cb => cb.value);

        if (articleIds.length === 0) {
            alert('Please select articles to delete.');
            return;
        }

        const confirmMessage = `Are you sure you want to delete ${articleIds.length} article(s)? This action cannot be undone.`;
        if (!confirm(confirmMessage)) {
            return;
        }

        this.submitBulkAction('bulk_delete_articles', articleIds);
    }

    // Submit bulk action
    submitBulkAction(action, articleIds) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/index.php?page=${action}`;

        // Add CSRF token
        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = 'csrf_token';
        const existingCsrfToken = document.querySelector('input[name="csrf_token"]');
        if (existingCsrfToken) {
            csrfInput.value = existingCsrfToken.value;
        }
        form.appendChild(csrfInput);

        // Add article IDs
        articleIds.forEach(id => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'article_ids[]';
            input.value = id;
            form.appendChild(input);
        });

        document.body.appendChild(form);
        form.submit();
    }

    // Export articles to CSV
    exportArticles() {
        const visibleRows = document.querySelectorAll('.article-row:not([style*="display: none"])');
        const csvData = [];

        // Add header
        csvData.push(['ID', 'Title', 'Author', 'Categories', 'Published Date']);

        // Add data rows
        visibleRows.forEach(row => {
            const id = this.getTextContent(row, '.id-badge');
            const title = this.getTextContent(row, '.article-title-link');
            const author = this.getTextContent(row, '.author-name') || 'N/A';
            const categories = Array.from(row.querySelectorAll('.category-tag'))
                .map(tag => tag.textContent.replace(/\s*\w+\s*/, '').trim())
                .join('; ');
            const date = this.getTextContent(row, 'time');

            csvData.push([id, title, author, categories, date]);
        });

        this.downloadCSV(csvData, `articles_export_${new Date().toISOString().split('T')[0]}.csv`);
    }

    // Helper function to get text content safely
    getTextContent(parent, selector) {
        const element = parent.querySelector(selector);
        return element ? element.textContent.trim() : '';
    }

    // Download CSV file
    downloadCSV(data, filename) {
        const csvString = data.map(row =>
            row.map(field => `"${String(field).replace(/"/g, '""')}"`).join(',')
        ).join('\n');

        const blob = new Blob([csvString], { type: 'text/csv' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = filename;
        a.style.display = 'none';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(url);
    }

    // Update results info
    updateResultsInfo(visibleCount = null) {
        const resultsInfo = document.getElementById('resultsInfo');
        if (!resultsInfo) return;

        const totalRows = document.querySelectorAll('.article-row').length;

        if (visibleCount === null) {
            resultsInfo.textContent = `Showing ${totalRows} articles`;
        } else {
            resultsInfo.textContent = `Showing ${visibleCount} of ${totalRows} articles`;
        }
    }

    // Clear all filters
    clearAllFilters() {
        const filterIds = ['articleSearch', 'categoryFilter', 'dateFilter', 'authorFilter', 'statusFilter'];

        filterIds.forEach(id => {
            const element = document.getElementById(id);
            if (element) {
                element.value = '';
            }
        });

        this.filterArticles();
    }
}

// Global functions for backward compatibility
let manageArticlesController;

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        manageArticlesController = new ManageArticlesController();
    });
} else {
    manageArticlesController = new ManageArticlesController();
}

// Export global functions
window.bulkDelete = function() {
    if (manageArticlesController) {
        manageArticlesController.bulkDelete();
    }
};

window.exportArticles = function() {
    if (manageArticlesController) {
        manageArticlesController.exportArticles();
    }
};

window.clearAllFilters = function() {
    if (manageArticlesController) {
        manageArticlesController.clearAllFilters();
    }
};

// Enhanced Article Management JavaScript with Draft Support
document.addEventListener('DOMContentLoaded', function() {
    const articleSearch = document.getElementById('articleSearch');
    const categoryFilter = document.getElementById('categoryFilter');
    const authorFilter = document.getElementById('authorFilter');
    const statusFilter = document.getElementById('statusFilter');
    const articleRows = document.querySelectorAll('.article-row');
    const resultsInfo = document.getElementById('resultsInfo');

    // Filter and search functionality
    function filterArticles() {
        const searchTerm = articleSearch ? articleSearch.value.toLowerCase() : '';
        const selectedCategory = categoryFilter ? categoryFilter.value.toLowerCase() : '';
        const selectedAuthor = authorFilter ? authorFilter.value.toLowerCase() : '';
        const selectedStatus = statusFilter ? statusFilter.value.toLowerCase() : '';

        let visibleCount = 0;

        articleRows.forEach(row => {
            const title = row.dataset.title || '';
            const author = row.dataset.author || '';
            const categories = row.dataset.categories || '';
            const status = row.dataset.status || '';

            const matchesSearch = title.includes(searchTerm);
            const matchesCategory = !selectedCategory || categories.includes(selectedCategory);
            const matchesAuthor = !selectedAuthor || author.includes(selectedAuthor);
            const matchesStatus = selectedStatus === 'all' || !selectedStatus || status === selectedStatus;

            const isVisible = matchesSearch && matchesCategory && matchesAuthor && matchesStatus;

            if (isVisible) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        // Update results info
        if (resultsInfo) {
            const totalCount = articleRows.length;
            if (visibleCount === totalCount) {
                resultsInfo.textContent = `Showing all ${totalCount} articles`;
            } else {
                resultsInfo.textContent = `Showing ${visibleCount} of ${totalCount} articles`;
            }
        }
    }

    // Status filter change handler with URL update
    if (statusFilter) {
        statusFilter.addEventListener('change', function() {
            const selectedStatus = this.value;

            // Update URL to reflect filter state
            const url = new URL(window.location);
            if (selectedStatus === 'all') {
                url.searchParams.delete('filter');
            } else {
                url.searchParams.set('filter', selectedStatus);
            }

            // Navigate to the new URL to reload with server-side filtering
            window.location.href = url.toString();
        });
    }

    // Event listeners for other filters
    if (articleSearch) {
        articleSearch.addEventListener('input', filterArticles);
    }

    if (categoryFilter) {
        categoryFilter.addEventListener('change', filterArticles);
    }

    if (authorFilter) {
        authorFilter.addEventListener('change', filterArticles);
    }

    // Table sorting functionality
    const sortableHeaders = document.querySelectorAll('.sortable');
    let currentSort = { column: null, direction: 'asc' };

    sortableHeaders.forEach(header => {
        header.addEventListener('click', function() {
            const sortColumn = this.dataset.sort;

            // Toggle direction if clicking the same column
            if (currentSort.column === sortColumn) {
                currentSort.direction = currentSort.direction === 'asc' ? 'desc' : 'asc';
            } else {
                currentSort.direction = 'asc';
            }
            currentSort.column = sortColumn;

            // Update header indicators
            sortableHeaders.forEach(h => {
                const sortIcon = h.querySelector('.sort-icon');
                if (sortIcon) {
                    sortIcon.className = 'fas fa-sort sort-icon';
                }
            });

            const currentSortIcon = this.querySelector('.sort-icon');
            if (currentSortIcon) {
                currentSortIcon.className = `fas fa-sort-${currentSort.direction === 'asc' ? 'up' : 'down'} sort-icon`;
            }

            // Sort the rows
            sortTable(sortColumn, currentSort.direction);
        });
    });

    function sortTable(column, direction) {
        const tbody = document.getElementById('articlesTableBody');
        if (!tbody) return;

        const rows = Array.from(tbody.querySelectorAll('.article-row'));

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

            if (aVal < bVal) return direction === 'asc' ? -1 : 1;
            if (aVal > bVal) return direction === 'asc' ? 1 : -1;
            return 0;
        });

        // Reorder the rows in the DOM
        rows.forEach(row => tbody.appendChild(row));
    }

    // Enhanced delete confirmation with draft/published context
    const deleteForms = document.querySelectorAll('.delete-form');
    deleteForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const row = this.closest('.article-row');
            const title = row ? row.querySelector('.article-title-link')?.textContent : 'this article';
            const status = row ? row.dataset.status : '';

            let confirmMessage = `Are you sure you want to delete "${title}"?`;
            if (status === 'draft') {
                confirmMessage += '\n\nThis is a draft and will be permanently lost.';
            } else {
                confirmMessage += '\n\nThis will permanently remove the published article.';
            }

            if (!confirm(confirmMessage)) {
                e.preventDefault();
            }
        });
    });

    // Status badge hover effects
    const statusBadges = document.querySelectorAll('.status-badge');
    statusBadges.forEach(badge => {
        badge.addEventListener('mouseenter', function() {
            const status = this.textContent.toLowerCase().trim();
            if (status === 'draft') {
                this.title = 'This article is saved as a draft and not visible to the public';
            } else if (status === 'published') {
                this.title = 'This article is published and visible to the public';
            }
        });
    });

    // Initial filter application (in case of page reload with filters)
    filterArticles();
});
