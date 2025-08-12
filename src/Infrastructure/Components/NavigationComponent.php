<?php

/**
 * Navigation component
 * Handles navigation generation and rendering
 *
 * @author Dmytro Hovenko
 */

declare(strict_types=1);

namespace App\Infrastructure\Components;

use App\Application\Helpers\NavigationHelper;
use Throwable;

class NavigationComponent {
    private string $currentPageKey;
    private array $navConfig;
    private array $siteSettings;

    public function __construct(string $currentPageKey) {
        $this->currentPageKey = $currentPageKey;
        
        // Get site settings from global variable
        global $site_settings_from_db;
        $this->siteSettings = $site_settings_from_db ?? [];

        // Use a new NavigationHelper instead of an old config file
        try {
            $this->navConfig = [
                'main' => NavigationHelper::getMainNavigation(),
                'user_specific' => [
                    'guest' => NavigationHelper::getUserNavigation(),
                    'auth' => NavigationHelper::getUserNavigation(true)
                ]
            ];
        } catch (Throwable $e) {
            error_log("Failed to load navigation from NavigationHelper: " . $e->getMessage());
            $this->navConfig = ['main' => [], 'user_specific' => ['guest' => [], 'auth' => []]];
        }
    }

    public function render(): string {
        $navItems = [];
        $isLoggedIn = isset($_SESSION['user_id']);

        // Get the site name from settings

        $navItems = $this->getItems($navItems);

        $userSpecificItem = null;
        if ($isLoggedIn && !empty($this->navConfig['user_specific']['auth'])) {
            $userSpecificItem = $this->navConfig['user_specific']['auth'];
        } elseif (!$isLoggedIn && !empty($this->navConfig['user_specific']['guest'])) {
            $userSpecificItem = $this->navConfig['user_specific']['guest'];
        }

        if ($userSpecificItem) {
            $navItems[] = [
                'url' => $userSpecificItem['url'],
                'text' => $userSpecificItem['text'],
                'is_active' => $this->currentPageKey === $userSpecificItem['key'],
                'class' => $userSpecificItem['class'] ?? ''
            ];
        }

        // Generate navigation HTML using site settings
        return $this->generateNavigationHtml($navItems);
    }

    private function generateNavigationHtml(array $navItems): string {
        ob_start();
        ?>
        <ul class="nav-list">
            <?php foreach ($navItems as $item): ?>
                <li class="nav-item <?php echo isset($item['dropdown']) ? 'has-dropdown' : ''; ?>">
                    <a href="<?php echo htmlspecialchars($item['url']); ?>"
                       class="nav-link <?php echo ($item['is_active'] ? 'active' : ''); ?> <?php echo htmlspecialchars($item['class'] ?? ''); ?>"
                       <?php echo isset($item['dropdown']) ? 'aria-haspopup="true" aria-expanded="false"' : ''; ?>
                       <?php if (str_starts_with($item['url'], 'http')): ?>target="_blank" rel="noopener"<?php endif; ?>>
                        <?php echo htmlspecialchars($item['text']); ?>
                        <?php if (isset($item['dropdown'])): ?>
                            <span class="dropdown-icon">▼</span>
                        <?php endif; ?>
                    </a>
                    <?php if (!empty($item['dropdown'])): ?>
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
        <?php
        return ob_get_clean();
    }

    /**
     * Get the site name from settings for use in navigation
     */
    public function getSiteName(): string {
        return $this->siteSettings['general']['site_name']['value'] ?? 'Darkheim';
    }

    /**
     * Check if a specific page is active
     */
    public function isPageActive(string $pageKey): bool {
        return $this->currentPageKey === $pageKey;
    }

    /**
     * Get navigation items array for use in templates
     */
    public function getNavItems(): array {
        $navItems = [];

        return $this->getItems($navItems);
    }

    /**
     * @param array $navItems
     * @return array
     */
    public function getItems(array $navItems): array
    {
        if (!empty($this->navConfig['main'])) {
            foreach ($this->navConfig['main'] as $item) {
                $navItem = [
                        'url' => $item['url'],
                        'text' => $item['text'],
                        'is_active' => $this->currentPageKey === $item['key'],
                        'class' => $item['class'] ?? ''
                ];

                // Add dropdown menu support
                if (!empty($item['dropdown'])) {
                    $navItem['dropdown'] = [];
                    foreach ($item['dropdown'] as $subItem) {
                        $navItem['dropdown'][] = [
                                'url' => $subItem['url'],
                                'text' => $subItem['text'],
                                'is_active' => $this->currentPageKey === $subItem['key'],
                                'class' => $subItem['class'] ?? ''
                        ];
                    }
                }

                $navItems[] = $navItem;
            }
        }
        return $navItems;
    }
}