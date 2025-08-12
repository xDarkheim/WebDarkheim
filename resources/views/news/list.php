<?php
/**
 * News List View - Компонент для отображения списка статей
 * Обновлен для работы с новым официальным дизайном
 *
 * @var array $data Данные для отображения списка новостей
 * @var array $flashMessages Flash сообщения (опционально)
 */

// Устанавливаем значения по умолчанию для отсутствующих ключей
$data = array_merge([
    'page_title' => 'News Hub',
    'is_admin' => false,
    'articles' => [],
    'categories' => []
], $data ?? []);
?>

<!-- News Page Container with data-page attribute for JavaScript detection -->
<div class="news-page" data-page="news">

<!-- Professional News Header -->
<header class="news-header">
    <div class="container">
        <div class="news-header-content">
            <div>
                <h1 class="news-title">
                    <i class="fas fa-newspaper"></i>
                    <?php echo htmlspecialchars($data['page_title']); ?>
                </h1>
                <p class="news-subtitle">Stay informed with the latest updates and insights</p>
            </div>
            <?php if ($data['is_admin']) : ?>
                <div class="news-actions">
                    <a href="/index.php?page=create_article" class="btn btn-create">
                        <i class="fas fa-plus"></i>
                        <span>Create Article</span>
                    </a>
                    <a href="/index.php?page=manage_articles" class="btn btn-secondary">
                        <i class="fas fa-cogs"></i>
                        <span>Manage</span>
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</header>

<div class="container">
    <div class="news-content">
        <main class="news-main">
            <!-- Search & Filter Section -->
                <?php include __DIR__ . '/_search_filters.php'; ?>

            <!-- Articles Grid -->
            <?php if (!empty($data['articles'])) : ?>
                <?php include __DIR__ . '/_articles_grid.php'; ?>

                <!-- Pagination -->
                <?php include __DIR__ . '/_pagination.php'; ?>
            <?php else : ?>
                <?php include __DIR__ . '/_no_articles.php'; ?>
            <?php endif; ?>
        </main>

        <!-- Sidebar -->
        <aside class="news-sidebar">
            <!-- Category Filter -->
            <?php if (!empty($data['categories'])) : ?>
                <?php include __DIR__ . '/_category_filter.php'; ?>
            <?php endif; ?>

            <!-- Additional sidebar content can be added here -->
            <div class="sidebar-widget">
                <h3 class="widget-title">
                    <i class="fas fa-info-circle"></i>
                    About News
                </h3>
                <p class="widget-content">
                    Stay up to date with the latest news, insights, and updates from our team.
                </p>
            </div>
        </aside>
    </div>
</div>

<!-- News JavaScript Modules v5.0.1-FIXED - Simplified and reliable -->
<!-- Load CSS for loading states and search form -->
<link rel="stylesheet" href="/themes/default/assets/css/news-navigation-loading.css">
<link rel="stylesheet" href="/themes/default/css/components/_search-form.css">

<!-- Load simplified core system -->
<script src="/themes/default/assets/js/news-core-fixed.js"></script>

<!-- Load simplified navigation module -->
<script src="/themes/default/assets/js/news-navigation-v5-fixed.js"></script>

<!-- Load simplified search module -->
<script src="/themes/default/assets/js/news-search-v5-fixed.js"></script>

<!-- ИСПРАВЛЕНО: Упрощенная инициализация без конфликтов -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('[NewsSystem] Starting v5.0.1-FIXED initialization...');

    // Проверяем доступность исправленной системы
    if (window.NewsCoreAPI && window.NewsCoreAPI.isInitialized()) {
        console.log('[NewsSystem] v5.0.1-FIXED successfully loaded and initialized');

        // Подписываемся на события для отладки
        window.NewsCoreAPI.on('navigation:content:updated', function(data) {
            console.log('[NewsSystem] Content updated via AJAX v5.0.1-FIXED');
        });

        // Подписываемся на события поиска
        window.NewsCoreAPI.on('search:results:updated', function(data) {
            console.log('[NewsSystem] Search results updated:', data.query, 'Results:', data.results);
        });

        // Интеграция с main.js для внешних переходов
        if (window.PageTransitionManager) {
            console.log('[NewsSystem] Integration with PageTransitionManager detected');

            // Уведомляем main.js о завершении AJAX навигации
            window.NewsCoreAPI.on('navigation:content:updated', function(data) {
                const event = new CustomEvent('pageTransitionComplete', {
                    detail: {
                        source: 'news-system-v5-fixed',
                        url: window.location.href,
                        isAjax: true
                    },
                    bubbles: true
                });
                document.dispatchEvent(event);
            });
        }
    } else {
        console.warn('[NewsSystem] v5.0.1-FIXED not available, checking initialization status...');

        // Даем системе время на инициализацию
        setTimeout(() => {
            if (window.NewsCoreAPI && window.NewsCoreAPI.isInitialized()) {
                console.log('[NewsSystem] Late initialization successful');
            } else {
                console.error('[NewsSystem] Failed to initialize v5.0.1-FIXED');
            }
        }, 1000);
    }
});

// Обработка глобальных переходов от main.js
window.addEventListener('pageTransitionComplete', function(event) {
    if (document.querySelector('.news-page') && event.detail && !event.detail.isAjax) {
        console.log('[NewsSystem] Global page transition detected, system should auto-reinitialize');
    }
});
</script>

</div> <!-- Close news-page container -->
