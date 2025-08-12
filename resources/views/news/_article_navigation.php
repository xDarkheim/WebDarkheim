<?php
/**
 * Article Navigation Component - карточки соседних статей со ссылками
 */

$adjacentArticles = $data['adjacent_articles'] ?? ['previous' => null, 'next' => null];
$prevArticle = $adjacentArticles['previous'] ?? null;
$nextArticle = $adjacentArticles['next'] ?? null;

// Получаем сервис новостей для работы с категориями
global $container;
$serviceProvider = \App\Application\Core\ServiceProvider::getInstance($container);
$newsService = $serviceProvider->getNewsService();

// Получаем категории текущей статьи для контекста
$currentArticleCategories = [];
if (isset($data['article'])) {
    $currentArticleCategories = $newsService->getArticleCategories($data['article']->id);
}
?>

<nav class="article-navigation-wrapper">
    <div class="article-navigation-card">
        <h3 class="navigation-title">
            <i class="fas fa-compass"></i> Continue Reading
            <?php if (!empty($currentArticleCategories)): ?>
                <span class="navigation-context">
                    from <?php echo implode(', ', array_column($currentArticleCategories, 'name')); ?>
                </span>
            <?php endif; ?>
        </h3>

        <div class="article-navigation-grid">
            <?php if ($prevArticle) : ?>
                <a href="/index.php?page=news&id=<?php echo $prevArticle['id']; ?>" class="nav-article nav-prev">
                    <h4 class="nav-title"><?php echo htmlspecialchars(mb_strimwidth($prevArticle['title'], 0, 80, '...')); ?></h4>
                    <?php if (!empty($prevArticle['short_description'])) : ?>
                        <p class="nav-excerpt"><?php echo htmlspecialchars(mb_strimwidth($prevArticle['short_description'], 0, 120, '...')); ?></p>
                    <?php endif; ?>
                    <div class="nav-meta">
                        <time><?php echo htmlspecialchars(date('M j, Y', strtotime($prevArticle['created_at']))); ?></time>
                        <?php
                        $prevCategories = $newsService->getArticleCategories($prevArticle['id']);
                        if (!empty($prevCategories)):
                        ?>
                            <div class="nav-categories">
                                <?php foreach (array_slice($prevCategories, 0, 2) as $category): ?>
                                    <span class="nav-category-tag"><?php echo htmlspecialchars($category['name']); ?></span>
                                <?php endforeach; ?>
                                <?php if (count($prevCategories) > 2): ?>
                                    <span class="nav-category-more">+<?php echo count($prevCategories) - 2; ?></span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </a>
            <?php else : ?>
                <div class="nav-article nav-disabled">
                    <p class="nav-message">You've reached the beginning of our news archive.</p>
                </div>
            <?php endif; ?>

            <?php if ($nextArticle) : ?>
                <a href="/index.php?page=news&id=<?php echo $nextArticle['id']; ?>" class="nav-article nav-next">
                    <h4 class="nav-title"><?php echo htmlspecialchars(mb_strimwidth($nextArticle['title'], 0, 80, '...')); ?></h4>
                    <?php if (!empty($nextArticle['short_description'])) : ?>
                        <p class="nav-excerpt"><?php echo htmlspecialchars(mb_strimwidth($nextArticle['short_description'], 0, 120, '...')); ?></p>
                    <?php endif; ?>
                    <div class="nav-meta">
                        <time><?php echo htmlspecialchars(date('M j, Y', strtotime($nextArticle['created_at']))); ?></time>
                        <?php
                        $nextCategories = $newsService->getArticleCategories($nextArticle['id']);
                        if (!empty($nextCategories)):
                        ?>
                            <div class="nav-categories">
                                <?php foreach (array_slice($nextCategories, 0, 2) as $category): ?>
                                    <span class="nav-category-tag"><?php echo htmlspecialchars($category['name']); ?></span>
                                <?php endforeach; ?>
                                <?php if (count($nextCategories) > 2): ?>
                                    <span class="nav-category-more">+<?php echo count($nextCategories) - 2; ?></span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </a>
            <?php else : ?>
                <div class="nav-article nav-disabled">
                    <p class="nav-message">You've reached the latest article in our news archive.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</nav>

<style>
/* Article Navigation - Simple linked cards */
.article-navigation-wrapper {
    margin: 2rem 0;
}

.article-navigation-card {
    background: var(--color-dark-surface);
    border: 1px solid var(--color-dark-border-light);
    border-radius: 8px;
    padding: 1.5rem;
}

.navigation-title {
    margin: 0 0 1.5rem 0;
    font-size: var(--font-size-lg);
    font-weight: var(--font-weight-semibold);
    color: var(--color-text-primary);
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.navigation-title i {
    color: var(--color-accent);
}

.navigation-context {
    font-size: var(--font-size-sm);
    font-weight: var(--font-weight-normal);
    color: var(--color-text-muted);
    font-style: italic;
}

.article-navigation-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

.nav-article {
    display: block;
    padding: 1rem;
    background: var(--color-dark-bg);
    border: 1px solid var(--color-dark-border-light);
    border-radius: 6px;
    color: inherit;
    text-decoration: none;
    transition: all 0.15s ease;
}

.nav-article:hover {
    background: var(--color-dark-elevated);
    border-color: var(--color-accent);
    transform: translateY(-1px);
    text-decoration: none;
}

.nav-disabled {
    background: rgba(var(--color-dark-elevated), 0.3);
    border-color: var(--color-dark-border-light);
    opacity: 0.6;
    display: flex;
    align-items: center;
    justify-content: center;
}

.nav-title {
    margin: 0 0 0.5rem 0;
    font-size: var(--font-size-md);
    font-weight: var(--font-weight-semibold);
    color: var(--color-text-primary);
    line-height: var(--line-height-tight);
}

.nav-excerpt {
    margin: 0 0 0.75rem 0;
    font-size: var(--font-size-sm);
    color: var(--color-text-secondary);
    line-height: var(--line-height-normal);
}

.nav-meta {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    font-size: var(--font-size-xs);
    color: var(--color-text-muted);
}

.nav-meta time {
    font-weight: var(--font-weight-medium);
}

.nav-categories {
    display: flex;
    flex-wrap: wrap;
    gap: 0.25rem;
    align-items: center;
}

.nav-category-tag {
    display: inline-block;
    padding: 0.125rem 0.375rem;
    background: var(--color-accent);
    color: var(--color-white);
    font-size: var(--font-size-xs);
    font-weight: var(--font-weight-medium);
    border-radius: 3px;
    white-space: nowrap;
}

.nav-category-more {
    font-size: var(--font-size-xs);
    color: var(--color-text-muted);
    font-weight: var(--font-weight-medium);
}

.nav-message {
    margin: 0;
    font-size: var(--font-size-sm);
    color: var(--color-text-muted);
    font-style: italic;
    text-align: center;
}

/* Mobile responsiveness */
@media (max-width: 768px) {
    .article-navigation-grid {
        grid-template-columns: 1fr;
        gap: 1rem;
    }

    .navigation-title {
        font-size: var(--font-size-md);
        flex-direction: column;
        align-items: flex-start;
        gap: 0.25rem;
    }

    .nav-article {
        padding: 0.75rem;
    }

    .nav-title {
        font-size: var(--font-size-sm);
    }

    .nav-categories {
        flex-wrap: nowrap;
        overflow-x: auto;
    }
}
</style>
