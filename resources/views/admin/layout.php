<?php
/**
 * Admin Layout Template
 * Common layout for all administrative pages
 */

declare(strict_types=1);

if (!function_exists('renderAdminLayout')) {
    /**
     * Render admin page layout
     *
     * @param string $title Page title
     * @param string $content Main content HTML
     * @param array $config Configuration options
     * @return void
     */
    function renderAdminLayout(string $title, string $content, array $config = []): void {
        global $flashMessageService, $serviceProvider;

        // Get current user info
        $authService = $serviceProvider->getAuth();
        $currentUser = $authService->getCurrentUser();

        // Default configuration
        $defaultConfig = [
            'icon' => 'fas fa-cog',
            'subtitle' => '',
            'actions' => [],
            'breadcrumbs' => [],
            'sidebar' => null,
            'scripts' => [],
            'styles' => []
        ];

        $config = array_merge($defaultConfig, $config);

        // Get flash messages
        $flashMessages = $flashMessageService->getAllMessages();

        ?>
<!DOCTYPE html>
<html lang="en" class="admin-panel">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?> - Admin Panel</title>

    <!-- Admin Styles -->
    <link rel="stylesheet" href="/public/assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <!-- Additional Styles -->
    <?php foreach ($config['styles'] as $style): ?>
    <link rel="stylesheet" href="<?= htmlspecialchars($style) ?>">
    <?php endforeach; ?>
</head>
<body class="admin-container">

    <!-- Navigation -->
    <nav class="admin-nav">
        <div class="admin-nav-container">
            <a href="/index.php?page=dashboard" class="admin-nav-brand">
                <i class="fas fa-shield-alt"></i>
                <span>Admin Panel</span>
            </a>

            <div class="admin-nav-links">
                <a href="/index.php?page=manage_articles" class="admin-nav-link">
                    <i class="fas fa-newspaper"></i>
                    <span>Articles</span>
                </a>
                <a href="/index.php?page=manage_categories" class="admin-nav-link">
                    <i class="fas fa-tags"></i>
                    <span>Categories</span>
                </a>
                <?php if ($currentUser['role'] === 'admin'): ?>
                <a href="/index.php?page=manage_users" class="admin-nav-link">
                    <i class="fas fa-users"></i>
                    <span>Users</span>
                </a>
                <a href="/index.php?page=site_settings" class="admin-nav-link">
                    <i class="fas fa-cogs"></i>
                    <span>Settings</span>
                </a>
                <?php endif; ?>
                <a href="/index.php?page=dashboard" class="admin-nav-link">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </div>
        </div>
    </nav>

    <!-- Header -->
    <header class="admin-header">
        <div class="admin-header-container">
            <div class="admin-header-content">
                <div class="admin-header-title">
                    <i class="admin-header-icon <?= htmlspecialchars($config['icon']) ?>"></i>
                    <div class="admin-header-text">
                        <h1><?= htmlspecialchars($title) ?></h1>
                        <?php if ($config['subtitle']): ?>
                        <p><?= htmlspecialchars($config['subtitle']) ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if (!empty($config['actions'])): ?>
                <div class="admin-header-actions">
                    <?php foreach ($config['actions'] as $action): ?>
                    <a href="<?= htmlspecialchars($action['url']) ?>"
                       class="admin-btn admin-btn-<?= htmlspecialchars($action['type'] ?? 'primary') ?>">
                        <?php if (!empty($action['icon'])): ?>
                        <i class="<?= htmlspecialchars($action['icon']) ?>"></i>
                        <?php endif; ?>
                        <?= htmlspecialchars($action['text']) ?>
                    </a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- Flash Messages -->
    <?php if (!empty($flashMessages)): ?>
    <div class="admin-flash-messages">
        <?php foreach ($flashMessages as $type => $messages): ?>
            <?php foreach ($messages as $message): ?>
            <div class="admin-flash-message admin-flash-<?= $type ?>">
                <i class="fas fa-<?= $type === 'error' ? 'exclamation-circle' : ($type === 'success' ? 'check-circle' : ($type === 'warning' ? 'exclamation-triangle' : 'info-circle')) ?>"></i>
                <div>
                    <?= $message['is_html'] ? $message['text'] : htmlspecialchars($message['text']) ?>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Main Content -->
    <main>
        <?php if ($config['sidebar']): ?>
        <div class="admin-layout-main">
            <div class="admin-content">
                <?= $content ?>
            </div>
            <aside class="admin-sidebar">
                <?= $config['sidebar'] ?>
            </aside>
        </div>
        <?php else: ?>
        <div style="max-width: 1280px; margin: 0 auto; padding: 0 1rem 2rem 1rem;">
            <?= $content ?>
        </div>
        <?php endif; ?>
    </main>

    <!-- Admin Scripts -->
    <script src="/public/assets/js/admin.js"></script>

    <!-- Additional Scripts -->
    <?php foreach ($config['scripts'] as $script): ?>
    <script src="<?= htmlspecialchars($script) ?>"></script>
    <?php endforeach; ?>

</body>
</html>
        <?php
    }
}

if (!function_exists('renderAdminSidebar')) {
    /**
     * Render common admin sidebar components
     *
     * @param array $components Array of sidebar components
     * @return string
     */
    function renderAdminSidebar(array $components): string {
        $html = '';

        foreach ($components as $component) {
            switch ($component['type']) {
                case 'quick-actions':
                    $html .= renderQuickActions($component);
                    break;
                case 'statistics':
                    $html .= renderStatistics($component);
                    break;
                case 'info':
                    $html .= renderInfoCard($component);
                    break;
                default:
                    $html .= $component['html'] ?? '';
            }
        }

        return $html;
    }
}

if (!function_exists('renderQuickActions')) {
    function renderQuickActions(array $component): string {
        $title = $component['title'] ?? 'Quick Actions';
        $actions = $component['actions'] ?? [];

        $html = '<div class="admin-card">';
        $html .= '<div class="admin-card-header">';
        $html .= '<h3 class="admin-card-title"><i class="fas fa-bolt"></i>' . htmlspecialchars($title) . '</h3>';
        $html .= '</div>';
        $html .= '<div class="admin-card-body">';

        foreach ($actions as $action) {
            $html .= '<a href="' . htmlspecialchars($action['url']) . '" class="admin-btn admin-btn-secondary" style="width: 100%; margin-bottom: 0.5rem; justify-content: flex-start;">';
            if (!empty($action['icon'])) {
                $html .= '<i class="' . htmlspecialchars($action['icon']) . '"></i>';
            }
            $html .= htmlspecialchars($action['text']);
            $html .= '</a>';
        }

        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }
}

if (!function_exists('renderStatistics')) {
    function renderStatistics(array $component): string {
        $title = $component['title'] ?? 'Statistics';
        $stats = $component['stats'] ?? [];

        $html = '<div class="admin-card">';
        $html .= '<div class="admin-card-header">';
        $html .= '<h3 class="admin-card-title"><i class="fas fa-chart-bar"></i>' . htmlspecialchars($title) . '</h3>';
        $html .= '</div>';
        $html .= '<div class="admin-card-body">';

        foreach ($stats as $stat) {
            $html .= '<div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;">';
            $html .= '<div style="display: flex; align-items: center;">';
            if (!empty($stat['icon'])) {
                $html .= '<i class="' . htmlspecialchars($stat['icon']) . '" style="margin-right: 0.5rem; color: var(--admin-primary);"></i>';
            }
            $html .= '<span style="font-size: 0.875rem; color: var(--admin-gray-600);">' . htmlspecialchars($stat['label']) . '</span>';
            $html .= '</div>';
            $html .= '<span style="font-size: 1.125rem; font-weight: 600; color: var(--admin-gray-900);">' . htmlspecialchars($stat['value']) . '</span>';
            $html .= '</div>';
        }

        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }
}

if (!function_exists('renderInfoCard')) {
    function renderInfoCard(array $component): string {
        $title = $component['title'] ?? 'Information';
        $content = $component['content'] ?? '';
        $icon = $component['icon'] ?? 'fas fa-info-circle';

        $html = '<div class="admin-card">';
        $html .= '<div class="admin-card-header">';
        $html .= '<h3 class="admin-card-title"><i class="' . htmlspecialchars($icon) . '"></i>' . htmlspecialchars($title) . '</h3>';
        $html .= '</div>';
        $html .= '<div class="admin-card-body">';
        $html .= $content;
        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }
}

if (!function_exists('renderAdminTable')) {
    /**
     * Render admin table with consistent styling
     *
     * @param array $config Table configuration
     * @return string
     */
    function renderAdminTable(array $config): string {
        $headers = $config['headers'] ?? [];
        $rows = $config['rows'] ?? [];
        $searchable = $config['searchable'] ?? false;
        $sortable = $config['sortable'] ?? false;

        $html = '<div class="admin-card">';

        // Header with search
        if (!empty($config['title']) || $searchable) {
            $html .= '<div class="admin-card-header" style="display: flex; justify-content: space-between; align-items: center;">';

            if (!empty($config['title'])) {
                $html .= '<h3 class="admin-card-title">' . htmlspecialchars($config['title']) . '</h3>';
            }

            if ($searchable) {
                $html .= '<input type="text" class="admin-input" placeholder="Search..." data-search-target="tbody tr" style="width: 250px;">';
            }

            $html .= '</div>';
        }

        $html .= '<div class="admin-table-container">';
        $html .= '<table class="admin-table">';

        // Headers
        if (!empty($headers)) {
            $html .= '<thead><tr>';
            foreach ($headers as $header) {
                $sortAttr = ($sortable && !empty($header['sortable'])) ? ' data-sort="true"' : '';
                $html .= '<th' . $sortAttr . '>' . htmlspecialchars($header['text']) . '</th>';
            }
            $html .= '</tr></thead>';
        }

        // Body
        $html .= '<tbody>';
        foreach ($rows as $row) {
            $html .= '<tr>';
            foreach ($row as $cell) {
                $html .= '<td>' . $cell . '</td>';
            }
            $html .= '</tr>';
        }
        $html .= '</tbody>';

        $html .= '</table>';
        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }
}
?>
