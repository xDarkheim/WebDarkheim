/**
 * News Filters Management
 * Handles clearing filters and other news-related actions
 */

class NewsFilters {
    constructor() {
        this.init();
    }

    init() {
        // Bind clear filters functionality
        this.bindClearFilters();
    }

    /**
     * Clear all active filters and redirect to news page
     */
    clearFilters() {
        window.location.href = '/index.php?page=news';
    }

    /**
     * Bind clear filters button events
     */
    bindClearFilters() {
        document.addEventListener('click', (e) => {
            if (e.target.matches('[data-action="clear-filters"]')) {
                e.preventDefault();
                this.clearFilters();
            }
        });
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.newsFilters = new NewsFilters();
});

// Global function for backward compatibility
function clearFilters() {
    if (window.newsFilters) {
        window.newsFilters.clearFilters();
    } else {
        window.location.href = '/index.php?page=news';
    }
}
