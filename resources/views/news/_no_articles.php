<?php
/**
 * No Articles Component - Центрированное сообщение для пустых результатов
 */
?>

<div class="no-articles-container">
    <div class="no-articles-content">
        <div class="no-articles-icon">
            <i class="fas fa-newspaper"></i>
        </div>

        <h3 class="no-articles-title">
            <?php if (isset($data['filters']['search']) && !empty($data['filters']['search'])): ?>
                No Search Results
            <?php elseif (isset($data['filters']['category']) && !empty($data['filters']['category'])): ?>
                No Articles in This Category
            <?php else: ?>
                No Articles Available
            <?php endif; ?>
        </h3>

        <p class="no-articles-message">
            <?php if (isset($data['filters']['search']) && !empty($data['filters']['search'])): ?>
                No articles found matching "<strong><?php echo htmlspecialchars($data['filters']['search']); ?></strong>".
                <br>Try different keywords or browse all articles.
            <?php elseif (isset($data['filters']['category']) && !empty($data['filters']['category'])): ?>
                This category doesn't have any articles yet.
                <br>Check back later or explore other categories.
            <?php else: ?>
                There are no articles available at the moment.
                <br>Please check back later for new content.
            <?php endif; ?>
        </p>

        <?php if (isset($data['filters']['search']) && !empty($data['filters']['search']) ||
                  isset($data['filters']['category']) && !empty($data['filters']['category'])): ?>
            <div class="no-articles-actions">
                <a href="/index.php?page=news" class="btn btn-primary">
                    <i class="fas fa-arrow-left"></i>
                    Back to All Articles
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>
