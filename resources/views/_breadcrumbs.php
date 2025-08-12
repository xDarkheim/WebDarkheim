<?php
// Используем новый NavigationHelper вместо старого shared_links_helper

// Получаем текущую страницу и категорию
$current_page = $_GET['page'] ?? 'home';
$current_category = $_GET['category'] ?? $_GET['type'] ?? null;

// Генерируем хлебные крошки с помощью нового NavigationHelper
$breadcrumbs = \App\Application\Helpers\NavigationHelper::getBreadcrumbs($current_page);

// Не показываем хлебные крошки на главной странице
if ($current_page === 'home' || empty($breadcrumbs) || count($breadcrumbs) <= 1) {
    return;
}
?>

<nav class="breadcrumb-navigation" aria-label="Breadcrumb">
    <ol class="breadcrumb">
        <?php foreach ($breadcrumbs as $index => $crumb): ?>
            <li class="breadcrumb-item <?php echo ($index === count($breadcrumbs) - 1) ? 'active' : ''; ?>">
                <?php if ($index === count($breadcrumbs) - 1): ?>
                    <span aria-current="page"><?php echo htmlspecialchars($crumb['text']); ?></span>
                <?php else: ?>
                    <a href="<?php echo htmlspecialchars($crumb['url']); ?>"><?php echo htmlspecialchars($crumb['text']); ?></a>
                <?php endif; ?>
                
                <?php if ($index < count($breadcrumbs) - 1): ?>
                    <span class="breadcrumb-separator" aria-hidden="true">›</span>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ol>
</nav>
