<?php
/**
 * Pagination Component - ИСПРАВЛЕНО для работы с Navigation v5.0.1-FIXED
 * Упрощенная версия без дублирующего JavaScript
 */
$pagination = $data['pagination'];
$currentPage = $pagination['current_page'];
$totalPages = $pagination['total_pages'];
$totalArticles = $pagination['total_articles'];

if ($totalPages <= 1) {
    return; // Не показываем пагинацию для одной страницы
}

// Строим URL с сохранением текущих параметров
$baseUrl = '/index.php?page=news';
$params = [];

if (!empty($data['filters']['search'])) {
    $params['search'] = $data['filters']['search'];
}
if (!empty($data['filters']['category'])) {
    $params['category'] = $data['filters']['category'];
}
if ($data['filters']['sort'] !== 'date_desc') {
    $params['sort'] = $data['filters']['sort'];
}

$queryString = !empty($params) ? '&' . http_build_query($params) : '';
?>

<nav class="pagination-section" aria-label="Articles pagination">
    <div class="pagination-info">
        <span class="pagination-text">
            Showing page <?php echo $currentPage; ?> of <?php echo $totalPages; ?>
            (<?php echo $totalArticles; ?> total articles)
        </span>
    </div>

    <div class="pagination-controls">
        <?php if ($currentPage > 1) : ?>
            <!-- ИСПРАВЛЕНО: Упрощенные ссылки для Navigation v5.0.1-FIXED -->
            <a href="<?php echo $baseUrl . $queryString . '&page_num=1'; ?>"
               class="pagination-link pagination-btn pagination-first">
                <i class="fas fa-angle-double-left"></i>
                First
            </a>

            <a href="<?php echo $baseUrl . $queryString . '&page_num=' . ($currentPage - 1); ?>"
               class="pagination-link pagination-btn pagination-prev">
                <i class="fas fa-angle-left"></i>
                Previous
            </a>
        <?php endif; ?>

        <!-- Page numbers -->
        <div class="pagination-numbers">
            <?php
            $startPage = max(1, $currentPage - 2);
            $endPage = min($totalPages, $currentPage + 2);

            for ($i = $startPage; $i <= $endPage; $i++) :
            ?>
                <?php if ($i === $currentPage) : ?>
                    <span class="pagination-btn pagination-current"><?php echo $i; ?></span>
                <?php else : ?>
                    <!-- ИСПРАВЛЕНО: Упрощенные ссылки -->
                    <a href="<?php echo $baseUrl . $queryString . '&page_num=' . $i; ?>"
                       class="pagination-link pagination-btn"><?php echo $i; ?></a>
                <?php endif; ?>
            <?php endfor; ?>
        </div>

        <?php if ($currentPage < $totalPages) : ?>
            <!-- ИСПРАВЛЕНО: Упрощенные ссылки -->
            <a href="<?php echo $baseUrl . $queryString . '&page_num=' . ($currentPage + 1); ?>"
               class="pagination-link pagination-btn pagination-next">
                Next
                <i class="fas fa-angle-right"></i>
            </a>

            <a href="<?php echo $baseUrl . $queryString . '&page_num=' . $totalPages; ?>"
               class="pagination-link pagination-btn pagination-last">
                Last
                <i class="fas fa-angle-double-right"></i>
            </a>
        <?php endif; ?>
    </div>
</nav>

<!-- ИСПРАВЛЕНО: Убран весь JavaScript - обработка делегируется системе Navigation v5.0.1-FIXED -->
