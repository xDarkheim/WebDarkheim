/**
 * Category Filter Functionality
 * Handles category filtering for news articles
 */

class CategoryFilter {
    constructor() {
        this.init();
    }

    init() {
        // Bind events for category filter buttons
        this.bindCategoryFilterEvents();
    }

    /**
     * Bind click events to category filter buttons
     */
    bindCategoryFilterEvents() {
        const categoryButtons = document.querySelectorAll('.category-pill');
        categoryButtons.forEach(button => {
            button.addEventListener('click', (e) => {
                const category = e.currentTarget.dataset.category || '';
                this.filterByCategory(category);
            });
        });
    }

    /**
     * Filter articles by category
     * @param {string} category - Category slug to filter by (empty string for all)
     */
    filterByCategory(category = '') {
        // Update URL with category filter
        const url = new URL(window.location);
        if (category) {
            url.searchParams.set('category', category);
        } else {
            url.searchParams.delete('category');
        }

        // Update page with filtered results
        window.location.href = url.toString();
    }

    /**
     * Clear all filters
     */
    clearFilters() {
        const url = new URL(window.location);
        const params = ['category', 'search', 'sort'];

        params.forEach(param => {
            url.searchParams.delete(param);
        });

        window.location.href = url.toString();
    }

    /**
     * Set active category button
     * @param {string} activeCategory - Currently active category slug
     */
    setActiveCategory(activeCategory = '') {
        const categoryButtons = document.querySelectorAll('.category-pill');
        categoryButtons.forEach(button => {
            const buttonCategory = button.dataset.category || '';
            if (buttonCategory === activeCategory) {
                button.classList.add('active');
            } else {
                button.classList.remove('active');
            }
        });
    }
}

// Global functions for backward compatibility
window.filterByCategory = function(category) {
    if (window.categoryFilterInstance) {
        window.categoryFilterInstance.filterByCategory(category);
    } else {
        // Fallback if instance not created
        const url = new URL(window.location);
        if (category) {
            url.searchParams.set('category', category);
        } else {
            url.searchParams.delete('category');
        }
        window.location.href = url.toString();
    }
};

window.clearFilters = function() {
    if (window.categoryFilterInstance) {
        window.categoryFilterInstance.clearFilters();
    } else {
        // Fallback if instance not created
        const url = new URL(window.location);
        const params = ['category', 'search', 'sort'];
        params.forEach(param => url.searchParams.delete(param));
        window.location.href = url.toString();
    }
};

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    window.categoryFilterInstance = new CategoryFilter();
});
