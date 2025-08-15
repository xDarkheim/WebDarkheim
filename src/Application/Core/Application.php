<?php

/**
 * Main Application class
 * Handles application initialization, routing, and template rendering
 * Refactored to follow SOLID principles with simplified architecture
 *
 * @author Dmytro Hovenko
*/

declare(strict_types=1);

namespace App\Application\Core;

use App\Application\Helpers\NavigationHelper;
use App\Application\Middleware\CSRFMiddleware;
use App\Application\Middleware\DebugMiddleware;
use App\Application\Middleware\MaintenanceMiddleware;
use App\Application\Middleware\RateLimitMiddleware;
use App\Infrastructure\Lib\Router;
use App\Domain\Models\SiteSettings;
use ReflectionException;
use RuntimeException;
use Throwable;

class Application
{
    private ServiceProvider $services;
    private Router $router;
    private array $siteSettings = [];

    /**
     * @throws ReflectionException
     */
    public function __construct(ServiceProvider $services)
    {
        $this->services = $services;
        $this->initializeApplication();
    }

    /**
     * @throws ReflectionException
     */
    private function initializeApplication(): void
    {
        // Session initialization
        $this->initializeSession();

        // Check database connection
        $this->checkDatabaseConnection();

        // Load site settings
        $this->loadSiteSettings();

        // Initialize and run system middleware
        $this->initializeMiddleware();

        // Initialize router
        $this->initializeRouter();
    }

    /**
     * @throws ReflectionException
     */
    private function initializeSession(): void
    {
        try {
            $configManager = $this->services->getConfigurationManager();
            $sessionManager = SessionManager::getInstance($this->services->getLogger(), [], $configManager);
            $started = $sessionManager->start();

            if (!$started) {
                $this->services->getLogger()->warning('Failed to start session manager');
                $this->fallbackToSimpleSession();
            }
        } catch (Throwable $e) {
            $this->services->getLogger()->error('SessionManager initialization failed', [
                'error' => $e->getMessage()
            ]);
            $this->fallbackToSimpleSession();
        }
    }

    /**
     * Fallback to simple PHP session if SessionManager fails
     */
    private function fallbackToSimpleSession(): void
    {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }
    }

    /**
     * @throws ReflectionException
     */
    private function checkDatabaseConnection(): void
    {
        try {
            $db = $this->services->getDatabase()->getConnection();
            $db->query('SELECT 1');
        } catch (Throwable $e) {
            $this->services->getLogger()->critical('Database connection failed', [
                'error' => $e->getMessage()
            ]);

            if (($_ENV['APP_ENV'] ?? 'production') === 'development') {
                throw new RuntimeException('Database connection failed. Check your database configuration.');
            } else {
                throw new RuntimeException('Service temporarily unavailable. Please try again later.');
            }
        }
    }

    /**
     * @throws ReflectionException
     */
    private function loadSiteSettings(): void
    {
        try {
            $this->siteSettings = $this->services->getCache()->remember('site_settings', function() {
                $siteSettingsModel = new SiteSettings($this->services->getDatabase());
                return $siteSettingsModel->getAllSettings();
            }, 1800); // Cache for 30 minutes

            // Log only for regular requests, not AJAX
            if (!$this->isAjaxRequest()) {
                $totalSettings = array_sum(array_map('count', $this->siteSettings));
                $this->services->getLogger()->info('Site settings loaded from database', [
                    'total_settings' => $totalSettings
                ]);
            }
        } catch (Throwable $e) {
            $this->services->getLogger()->warning('Failed to load site settings', [
                'error' => $e->getMessage()
            ]);
            $this->siteSettings = [];
        }
    }

    /**
     * Check if current request is AJAX
     */
    private function isAjaxRequest(): bool
    {
        return (
            (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
             strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
            (isset($_GET['action']) && $_GET['action'] === 'ajax') ||
            str_contains($_SERVER['REQUEST_URI'] ?? '', 'action=ajax') ||
            (str_contains($_SERVER['REQUEST_URI'] ?? '', 'system_monitor') &&
                str_contains($_SERVER['REQUEST_URI'] ?? '', 'type='))
        );
    }

    /**
     * Initialize middleware pipeline
     * @throws ReflectionException
     */
    private function initializeMiddleware(): void
    {
        // Пока отключаем middleware для стабильности
        // TODO: Реализовать позже после стабилизации основной архитектуры
        $this->services->getLogger()->info('Middleware pipeline skipped for now');
    }

    private function initializeRouter(): void
    {
        $routes_config = require_once ROOT_PATH . DS . 'config' . DS . 'routes_config.php';
        $this->router = new Router(ROOT_PATH . DS . 'page', $routes_config);
    }


    /**
     * @throws ReflectionException
     */
    public function run(): void
    {
        // Request logging
        $this->logRequest();

        // Get messages from FlashMessageService
        $this->processFlashMessages();

        // Get the current page
        $page_key = $this->getCurrentPageKey();

        // Enhanced security logging for authenticated users
        $this->logAuthenticatedAccess($page_key);

        // Route dispatching
        $this->dispatchRoute($page_key);

        // Prepare data for a template
        $this->prepareTemplateData();

        // Load main template
        $this->loadMainTemplate();
    }

    /**
     * @throws ReflectionException
     */
    private function logRequest(): void
    {
        // Improved AJAX request check
        $is_ajax = (
            (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
             strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
            (isset($_GET['action']) && $_GET['action'] === 'ajax') ||
            (str_contains($_SERVER['REQUEST_URI'] ?? '', 'action=ajax')) ||
            (str_contains($_SERVER['REQUEST_URI'] ?? '', 'system_monitor') &&
                str_contains($_SERVER['REQUEST_URI'] ?? '', 'type='))
        );

        // Do not log AJAX requests to reduce log volume
        if (!$is_ajax) {
            $this->services->getLogger()->info('Application started', [
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'request_uri' => $_SERVER['REQUEST_URI'] ?? 'unknown'
            ]);
        }
    }

    /**
     * @throws ReflectionException
     */
    private function processFlashMessages(): void
    {
        // Get StateManager for centralized state management
        $stateManager = StateManager::getInstance($this->services->getLogger());
        $configManager = $this->services->getConfigurationManager();
        $sessionManager = SessionManager::getInstance($this->services->getLogger(), [], $configManager);

        // Get messages from FlashMessageService
        $flashMessages = $this->services->getFlashMessage()->getMessages();

        // Save messages in state instead of global variables
        $pageMessages = array_filter($flashMessages, function ($messages) {
            return !empty($messages);
        });
        $stateManager->set('ui.page_messages', $pageMessages);

        // Handle sidebar success messages (legacy support)
        $sidebarSuccess = $sessionManager->getFlash('success_message_sidebar');
        if ($sidebarSuccess) {
            $stateManager->set('ui.sidebar_success_text', $sidebarSuccess);
        }

        // For backward compatibility, export to global variables
        global $page_messages, $sidebar_success_text;
        $page_messages = $pageMessages;
        $sidebar_success_text = $sidebarSuccess;
    }

    private function getCurrentPageKey(): string
    {
        return isset($_GET['page']) ? trim(strtolower($_GET['page'])) : 'home';
    }

    /**
     * @throws ReflectionException
     */
    private function logAuthenticatedAccess(string $page_key): void
    {
        // Improved AJAX request check (same as in other methods)
        $is_ajax = (
            (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
             strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
            (isset($_GET['action']) && $_GET['action'] === 'ajax') ||
            (str_contains($_SERVER['REQUEST_URI'] ?? '', 'action=ajax')) ||
            (str_contains($_SERVER['REQUEST_URI'] ?? '', 'system_monitor') &&
                str_contains($_SERVER['REQUEST_URI'] ?? '', 'type='))
        );

        if ($this->services->getAuth()->isAuthenticated() && !$is_ajax) {
            $this->services->getLogger()->debug('Authenticated user page access', [
                'user_id' => $this->services->getAuth()->getCurrentUserId(),
                'username' => $this->services->getAuth()->getCurrentUsername(),
                'page' => $page_key,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
        }
    }

    /**
     * @throws ReflectionException
     */
    private function dispatchRoute(string $page_key): void
    {
        try {
            $this->router->dispatch($page_key);
        } catch (Throwable $e) {
            $this->services->getLogger()->error('Router dispatch failed', [
                'page_key' => $page_key,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            // Set basic content on error
            global $page_content;
            $page_content = '<div class="alert alert-danger">Application error occurred. Please try again later.</div>';
        }
    }

    /**
     * @throws ReflectionException
     */
    private function prepareTemplateData(): void
    {
        $stateManager = StateManager::getInstance($this->services->getLogger());
        $configManager = $this->services->getConfigurationManager();
        $sessionManager = SessionManager::getInstance($this->services->getLogger(), [], $configManager);

        // Get data from the state
        $pageMessages = $stateManager->get('ui.page_messages', []);
        $siteName = $this->getSiteName();
        $pageTitle = $stateManager->get('ui.page_title', 'Default Title');

        // Update state with prepared data
        $stateManager->updateSection('ui', [
            'site_name' => $siteName,
            'page_title' => $pageTitle,
            'full_title' => $pageTitle . ' - ' . $siteName,
            'main_navigation_html' => $this->generateNavigationHtml(),
            'navigation_items' => $this->generateNavigationItems(),
        ]);

        // Update user state from a session
        $userState = $sessionManager->getUserState();
        if ($userState['authenticated']) {
            $stateManager->updateSection('user', $userState);
        }

        // For backward compatibility, support global variables
        global $template_data, $page_title, $page_messages, $navItems;

        $template_data = [
            'page_messages' => $pageMessages,
            'site_name' => $siteName,
            'page_title' => $pageTitle,
            'full_title' => $pageTitle . ' - ' . $siteName,
            'main_navigation_html' => $stateManager->get('ui.main_navigation_html'),
        ];

        $page_title = $pageTitle;
        $page_messages = $pageMessages;
        $navItems = $stateManager->get('ui.navigation_items', []);
    }

    private function getSiteName(): string
    {
        if (isset($this->siteSettings['general']['site_name']['value'])) {
            return $this->siteSettings['general']['site_name']['value'];
        } elseif (isset($this->siteSettings['site_name'])) {
            return $this->siteSettings['site_name'];
        }

        // Search in any settings structure
        foreach ($this->siteSettings as $settings) {
            if (is_array($settings) && isset($settings['site_name']['value'])) {
                return $settings['site_name']['value'];
            }
        }

        return 'WebEngine Darkheim';
    }

    /**
     * @throws ReflectionException
     */
    private function loadMainTemplate(): void
    {
        global $template_data, $page_content, $navItems;

        // Make sure navigation items are available
        if (!isset($navItems)) {
            $navItems = $this->generateNavigationItems();
        }

        // Include header
        $header_path = ROOT_PATH . DS . 'themes' . DS . 'default' . DS . 'header.php';
        if (file_exists($header_path)) {
            include $header_path;
        }

        // Output page content
        if (isset($page_content)) {
            echo $page_content;
        } elseif (isset($template_data['content'])) {
            echo $template_data['content'];
        }

        // Include footer
        $footer_path = ROOT_PATH . DS . 'themes' . DS . 'default' . DS . 'footer.php';
        if (file_exists($footer_path)) {
            include $footer_path;
        }
    }

    /**
     * Generate navigation HTML from configuration
     * @throws ReflectionException
     */
    private function generateNavigationHtml(): string
    {
        try {
            // Use a new NavigationHelper instead of an old config file
            $main_nav = NavigationHelper::getMainNavigation();

            if (empty($main_nav)) {
                return '<ul class="nav-list"><li class="nav-item"><a href="/index.php?page=home" class="nav-link">Home</a></li></ul>';
            }

            $current_page = $this->getCurrentPageKey();
            $html = '<ul class="nav-list">';

            foreach ($main_nav as $item) {
                $html .= $this->generateNavItem($item, $current_page);
            }

            $html .= '</ul>';
            return $html;

        } catch (Throwable $e) {
            $this->services->getLogger()->error('Navigation generation failed', [
                'error' => $e->getMessage()
            ]);
            return '<ul class="nav-list"><li class="nav-item"><a href="/index.php?page=home" class="nav-link">Home</a></li></ul>';
        }
    }

    /**
     * Generate individual navigation item HTML
     */
    private function generateNavItem(array $item, string $current_page): string
    {
        $key = $item['key'] ?? '';
        $url = $item['url'] ?? '#';
        $text = htmlspecialchars($item['text'] ?? '', ENT_QUOTES, 'UTF-8');
        $dropdown = $item['dropdown'] ?? [];

        $is_active = ($key === $current_page) ? ' active' : '';
        $has_dropdown = !empty($dropdown) ? ' has-dropdown' : '';

        $html = '<li class="nav-item' . $has_dropdown . '">';

        if (!empty($dropdown)) {
            // Dropdown menu
            $html .= '<a href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '" class="nav-link' . $is_active . '" aria-haspopup="true" aria-expanded="false">';
            $html .= $text . '<i class="fas fa-chevron-down dropdown-icon"></i>';
            $html .= '</a>';
            $html .= '<ul class="dropdown-menu">';

            foreach ($dropdown as $subitem) {
                $sub_url = htmlspecialchars($subitem['url'] ?? '#', ENT_QUOTES, 'UTF-8');
                $sub_text = htmlspecialchars($subitem['text'] ?? '', ENT_QUOTES, 'UTF-8');
                $html .= '<li class="dropdown-item"><a href="' . $sub_url . '" class="dropdown-link">' . $sub_text . '</a></li>';
            }

            $html .= '</ul>';
        } else {
            // Regular menu item
            $html .= '<a href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '" class="nav-link' . $is_active . '">' . $text . '</a>';
        }

        $html .= '</li>';
        return $html;
    }

    /**
     * Generate navigation items array for mobile menu compatibility
     * @throws ReflectionException
     */
    private function generateNavigationItems(): array
    {
        try {
            // Use a new NavigationHelper instead of an old config file
            $main_nav = NavigationHelper::getMainNavigation();

            $current_page = $this->getCurrentPageKey();
            $items = [];

            foreach ($main_nav as $item) {
                $key = $item['key'] ?? '';
                $url = $item['url'] ?? '#';
                $text = $item['text'] ?? '';
                $dropdown = $item['dropdown'] ?? [];

                $items[] = [
                    'key' => $key,
                    'url' => $url,
                    'text' => $text,
                    'dropdown' => $dropdown,
                    'is_active' => ($key === $current_page)
                ];
            }

            return $items;

        } catch (Throwable $e) {
            $this->services->getLogger()->error('Navigation items generation failed', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }
}
