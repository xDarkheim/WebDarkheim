<?php
/**
 * No Articles Found Component - Обновлен для официального дизайна
 */

// Проверяем, что переменная $data доступна
if (!isset($data)) {
    $data = ['filters' => ['search' => '', 'category' => '']];
}
?>

<div class="no-articles">
    <i class="fas fa-newspaper"></i>

    <h3>No Articles Found</h3>

    <?php if (!empty($data['filters']['search'])) : ?>
        <p>
            No articles found for "<strong><?php echo htmlspecialchars($data['filters']['search']); ?></strong>".
            Try adjusting your search terms or browse by category.
        </p>
    <?php elseif (!empty($data['filters']['category'])) : ?>
        <p>
            No articles found in the
            "<strong><?php echo htmlspecialchars($data['filters']['category']); ?></strong>" category.
            Try browsing other categories or search for specific content.
        </p>
    <?php else : ?>
        <p>
            No articles are currently available. Check back later for new content.
        </p>
    <?php endif; ?>
</div>
