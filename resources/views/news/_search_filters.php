<?php
/**
 * Search & Filters Component - ИСПРАВЛЕНО для работы с Navigation v5.0.1-FIXED
 * Упрощенная версия без дублирующего JavaScript
 */

// Проверяем, что переменная $data доступна
if (!isset($data)) {
    $data = ['filters' => ['search' => '', 'category' => '', 'sort' => 'date_desc']];
}

// Получаем текущие параметры из фильтров
$currentSearch = $data['filters']['search'] ?? '';
$currentCategory = $data['filters']['category'] ?? '';
$currentSort = $data['filters']['sort'] ?? 'date_desc';
?>

<section class="search-filters">
    <!-- ИСПРАВЛЕНО: Упрощенная форма для работы с Navigation v5.0.1-FIXED -->
    <form method="GET" action="/index.php" class="search-form news-search-form">
        <input type="hidden" name="page" value="news">

        <!-- Сохраняем категорию если она есть -->
        <?php if (!empty($currentCategory)) : ?>
            <input type="hidden" name="category" value="<?php echo htmlspecialchars($currentCategory); ?>">
        <?php endif; ?>

        <div class="search-input-wrapper">
            <i class="fas fa-search search-icon"></i>
            <input type="text"
                   id="search"
                   name="search"
                   value="<?php echo htmlspecialchars($currentSearch); ?>"
                   placeholder="Search articles by title, content, or keywords..."
                   class="search-input"
                   aria-label="Search articles">
            <button type="button" class="search-button search-btn" title="Search">
                <i class="fas fa-search"></i>
            </button>
        </div>

        <div class="search-controls">
            <label for="sort" class="sr-only">Sort articles by</label>
            <select name="sort" id="sort" class="sort-select">
                <option value="date_desc" <?php echo $currentSort === 'date_desc' ? 'selected' : ''; ?>>
                    Latest First
                </option>
                <option value="date_asc" <?php echo $currentSort === 'date_asc' ? 'selected' : ''; ?>>
                    Oldest First
                </option>
                <option value="title_asc" <?php echo $currentSort === 'title_asc' ? 'selected' : ''; ?>>
                    Title A-Z
                </option>
                <option value="title_desc" <?php echo $currentSort === 'title_desc' ? 'selected' : ''; ?>>
                    Title Z-A
                </option>
            </select>

            <button type="submit" class="search-submit-btn">
                <i class="fas fa-filter"></i>
                Apply Filters
            </button>
        </div>
    </form>
</section>

<!-- ИСПРАВЛЕНО: Убран весь JavaScript - обработка делегируется системе Navigation v5.0.1-FIXED -->
