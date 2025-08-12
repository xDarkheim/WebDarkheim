/**
 * Article Rating System
 * Handles like/dislike functionality for articles
 */


class ArticleRating {
    constructor() {
        this.init();
    }

    init() {
        this.bindRatingEvents();
    }

    /**
     * Bind events to rating buttons
     */
    bindRatingEvents() {
        const ratingButtons = document.querySelectorAll('.rating-btn');
        ratingButtons.forEach(button => {
            button.addEventListener('click', (e) => {
                const type = e.currentTarget.dataset.type;
                if (type) {
                    this.rateArticle(type);
                }
            });
        });
    }

    /**
     * Rate an article (like or dislike)
     * @param {string} type - 'like' or 'dislike'
     */
    rateArticle(type) {
        if (!['like', 'dislike'].includes(type)) {
            console.error('Invalid rating type:', type);
            return;
        }

        const countElement = document.getElementById(type === 'like' ? 'likeCount' : 'dislikeCount');
        if (!countElement) {
            console.error('Count element not found for type:', type);
            return;
        }

        const currentCount = parseInt(countElement.textContent) || 0;
        countElement.textContent = currentCount + 1;

        // Show visual feedback
        const button = event.target.closest('.rating-btn');
        this.showRatingFeedback(button, type);

        // TODO: Send rating to server
        this.sendRatingToServer(type);
    }

    /**
     * Show visual feedback for rating action
     * @param {Element} button - The rating button element
     * @param {string} type - Rating type ('like' or 'dislike')
     */
    showRatingFeedback(button, type) {
        if (!button) return;

        const originalBg = button.style.background;
        const originalColor = button.style.color;

        button.style.background = type === 'like' ? '#28a745' : '#dc3545';
        button.style.color = 'white';
        button.style.transform = 'scale(1.05)';

        setTimeout(() => {
            button.style.background = originalBg;
            button.style.color = originalColor;
            button.style.transform = 'scale(1)';
        }, 1000);
    }

    /**
     * Send rating to server (placeholder for future implementation)
     * @param {string} type - Rating type
     */
    async sendRatingToServer(type) {
        try {
            // TODO: Implement actual API call
            console.log(`Sending ${type} rating to server...`);

            // Example of future implementation:
            // const response = await fetch('/api/articles/rate', {
            //     method: 'POST',
            //     headers: {
            //         'Content-Type': 'application/json',
            //     },
            //     body: JSON.stringify({
            //         articleId: this.getArticleId(),
            //         type: type
            //     })
            // });

        } catch (error) {
            console.error('Failed to send rating:', error);
        }
    }

    /**
     * Get current article ID (placeholder)
     * @returns {string|null} Article ID
     */
    getArticleId() {
        // TODO: Implement proper article ID detection
        const urlParams = new URLSearchParams(window.location.search);
        return urlParams.get('id') || null;
    }
}

// Global function for backward compatibility
window.rateArticle = function(type) {
    if (window.articleRatingInstance) {
        window.articleRatingInstance.rateArticle(type);
    } else {
        console.error('Article rating instance not initialized');
    }
};

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    window.articleRatingInstance = new ArticleRating();
});
