<?php
/**
 * Admin Layout Template
 * Common layout for all administrative pages with unified navigation
 */

declare(strict_types=1);

// Include the AdminNavigation component
require_once __DIR__ . '/../../../src/Application/Components/AdminNavigation.php';

use App\Application\Components\AdminNavigation;

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

        // Initialize AdminNavigation
        $adminNavigation = new AdminNavigation($authService);

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

        // Auto-generate breadcrumbs if not provided
        if (empty($config['breadcrumbs'])) {
            $config['breadcrumbs'] = $adminNavigation->getBreadcrumbs();
        }

        ?>
<!DOCTYPE html>
<html lang="en" class="admin-panel">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?> - Admin Panel</title>

    <!-- Admin Styles -->
    <link rel="stylesheet" href="/public/assets/css/admin.css">
    <link rel="stylesheet" href="/public/assets/css/admin-navigation.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <!-- Additional Styles -->
    <?php foreach ($config['styles'] as $style): ?>
    <link rel="stylesheet" href="<?= htmlspecialchars($style) ?>">
    <?php endforeach; ?>
</head>
<body class="admin-container">

    <!-- Unified Navigation -->
    <?= $adminNavigation->render() ?>

    <!-- Breadcrumbs -->
    <?php if (!empty($config['breadcrumbs'])): ?>
    <nav class="admin-breadcrumbs">
        <div class="admin-breadcrumbs-container">
            <ol class="admin-breadcrumb-list">
                <?php foreach ($config['breadcrumbs'] as $index => $breadcrumb): ?>
                <li class="admin-breadcrumb-item">
                    <?php if ($breadcrumb['url'] && $index < count($config['breadcrumbs']) - 1): ?>
                    <a href="<?= htmlspecialchars($breadcrumb['url']) ?>" class="admin-breadcrumb-link">
                        <?= htmlspecialchars($breadcrumb['title']) ?>
                    </a>
                    <?php else: ?>
                    <span class="admin-breadcrumb-current"><?= htmlspecialchars($breadcrumb['title']) ?></span>
                    <?php endif; ?>
                    <?php if ($index < count($config['breadcrumbs']) - 1): ?>
                    <i class="fas fa-chevron-right admin-breadcrumb-separator"></i>
                    <?php endif; ?>
                </li>
                <?php endforeach; ?>
            </ol>
        </div>
    </nav>
    <?php endif; ?>

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
        <div class="admin-content">
            <?= $content ?>
        </div>
        <?php endif; ?>
    </main>

    <!-- Admin Scripts -->
    <script src="/public/assets/js/admin-navigation.js"></script>

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
