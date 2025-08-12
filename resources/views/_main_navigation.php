<?php
// Используем новый NavigationHelper вместо старого shared_links_helper

// Получаем текущую страницу и категорию для определения активных ссылок
$current_page = $_GET['page'] ?? 'home';
$current_category = $_GET['category'] ?? $_GET['type'] ?? null;

// Получаем навигационные элементы из нового NavigationHelper
$navigation_items = \App\Application\Helpers\NavigationHelper::getMainNavigation();

// Обрабатываем каждый элемент навигации для добавления информации об активности
$navItems = [];
foreach ($navigation_items as $item) {
    $processed_item = $item;
    $processed_item['is_active'] = \App\Application\Helpers\NavigationHelper::isActive($item['key'], $current_page);

    // Обрабатываем dropdown элементы
    if (isset($item['dropdown'])) {
        foreach ($item['dropdown'] as $index => $subItem) {
            $processed_item['dropdown'][$index] = $subItem;
            $processed_item['dropdown'][$index]['is_active'] = \App\Application\Helpers\NavigationHelper::isActive($subItem['key'], $current_page);
        }
    }

    $navItems[] = $processed_item;
}
?>

<nav class="main-navigation">
    <ul class="nav-list">
        <?php foreach ($navItems as $item): ?>
            <li class="nav-item <?php echo isset($item['dropdown']) ? 'has-dropdown' : ''; ?>">
                <a href="<?php echo htmlspecialchars($item['url']); ?>"
                   class="nav-link <?php echo ($item['is_active'] ? 'active' : ''); ?> <?php echo htmlspecialchars($item['class'] ?? ''); ?>"
                   <?php echo isset($item['dropdown']) ? 'aria-haspopup="true" aria-expanded="false"' : ''; ?>>
                    <?php echo htmlspecialchars($item['text']); ?>
                    <?php if (isset($item['dropdown'])): ?>
                        <span class="dropdown-icon">▼</span>
                    <?php endif; ?>
                </a>
                <?php if (isset($item['dropdown']) && !empty($item['dropdown'])): ?>
                    <ul class="dropdown-menu">
                        <?php foreach ($item['dropdown'] as $subItem): ?>
                            <li class="dropdown-item">
                                <a href="<?php echo htmlspecialchars($subItem['url']); ?>"
                                   class="dropdown-link <?php echo ($subItem['is_active'] ?? false) ? 'active' : ''; ?>">
                                    <?php echo htmlspecialchars($subItem['text']); ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>
</nav>