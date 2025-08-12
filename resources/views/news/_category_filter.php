<?php
/**
 * Category Filter Component - ИСПРАВЛЕНО для работы с Navigation v5.0.1-FIXED
 * Упрощенная версия без конфликтов и дублирования JavaScript
 */

// Проверяем, что переменная $data доступна
if (!isset($data)) {
    $data = [
        'filters' => ['category' => '', 'search' => '', 'sort' => 'date_desc'],
        'categories' => [],
        'pagination' => ['total_articles' => 0]
    ];
}

// Получаем текущие параметры
$currentSearch = $data['filters']['search'] ?? '';
$currentSort = $data['filters']['sort'] ?? 'date_desc';
$currentCategory = $data['filters']['category'] ?? '';
?>

<div class="category-filter">
    <h3 class="category-filter-title">
        <i class="fas fa-tags"></i>
        Browse by Category
    </h3>

    <ul class="category-list">
        <li class="category-item">
            <a href="/index.php?page=news"
               class="filter-tab category-link <?php echo empty($currentCategory) ? 'active' : ''; ?>"
               data-category="">
                <span class="category-name">
                    <i class="fas fa-globe"></i>
                    All News
                </span>
                <span class="category-count"><?php echo $data['pagination']['total_articles'] ?? 0; ?></span>
            </a>
        </li>

        <?php if (isset($data['categories']) && !empty($data['categories'])) : ?>
            <?php foreach ($data['categories'] as $category) : ?>
                <li class="category-item">
                    <a href="/index.php?page=news&category=<?php echo urlencode($category->slug); ?>"
                       class="filter-tab category-link <?php echo $currentCategory === $category->slug ? 'active' : ''; ?>"
                       data-category="<?php echo htmlspecialchars($category->slug); ?>">
                        <span class="category-name">
                            <i class="fas fa-folder"></i>
                            <?php echo htmlspecialchars($category->name); ?>
                        </span>
                        <span class="category-count"><?php echo $category->article_count ?? 0; ?></span>
                    </a>
                </li>
            <?php endforeach; ?>
        <?php endif; ?>
    </ul>
</div>

<!-- ИСПРАВЛЕНО: Убран весь JavaScript - обработка делегируется системе Navigation v5.0.1-FIXED -->
