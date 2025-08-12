<?php

/**
 * Main Application class
 * Handles application initialization, routing, and template rendering
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
use RuntimeException;
use Throwable;


class Application
{
    private ServiceProvider $services;
    private Router $router;
    private array $siteSettings = [];

    public function __construct(ServiceProvider $services)
    {
        $this->services = $services;
        $this->initializeApplication();
    }

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

    private function initializeSession(): void
    {
        // Initialize SessionManager immediately, before any output
        try {
            // Get ConfigurationManager from services
            $configManager = $this->services->getConfigurationManager();
            $sessionManager = SessionManager::getInstance($this->services->getLogger(), [], $configManager);
            $started = $sessionManager->start();

            if (!$started) {
                $this->services->getLogger()->warning('Failed to start session manager');
                // Fallback to a simple session if SessionManager fails
                if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
                    session_start();
                }
            }
        } catch (Throwable $e) {
            $this->services->getLogger()->error('SessionManager initialization failed', [
                'error' => $e->getMessage()
            ]);

            // Fallback to a simple session
            if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
                session_start();
            }
        }
    }

    private function checkDatabaseConnection(): void
    {
        try {
            $db = $this->services->getDatabase()->getConnection();
            // Check that the connection is active by running a simple query
            $db->query('SELECT 1');
        } catch (Throwable $e) {
            $this->services->getLogger()->critical("Database connection failed", [
                'error' => $e->getMessage()
            ]);

            if (($_ENV['APP_ENV'] ?? 'production') === 'development') {
                throw new RuntimeException("Database connection failed. Check your database configuration.");
            } else {
                throw new RuntimeException("Service temporarily unavailable. Please try again later.");
            }
        }
    }

    private function loadSiteSettings(): void
    {
        try {
            $this->siteSettings = $this->services->getCache()->remember('site_settings', function() {
                $siteSettingsModel = new SiteSettings($this->services->getDatabase());
                return $siteSettingsModel->getAllSettings();
            }, 1800); // Cache for 30 minutes

            // Stricter AJAX request check
            $is_ajax = (
                (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
                 strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
                (isset($_GET['action']) && $_GET['action'] === 'ajax') ||
                (str_contains($_SERVER['REQUEST_URI'] ?? '', 'action=ajax')) ||
                (str_contains($_SERVER['REQUEST_URI'] ?? '', 'system_monitor') &&
                    str_contains($_SERVER['REQUEST_URI'] ?? '', 'type='))
            );

            // Log only for regular requests, not AJAX/API
            if (!$is_ajax) {
                $totalSettings = 0;
                foreach ($this->siteSettings as $category) {
                    $totalSettings += count($category);
                }

                $this->services->getLogger()->info("Site settings loaded from database", [
                    'total_settings' => $totalSettings
                ]);
            }
        } catch (Throwable $e) {
            $this->services->getLogger()->warning("Failed to load site settings", [
                'error' => $e->getMessage()
            ]);
            $this->siteSettings = [];
        }
    }

    private function initializeRouter(): void
    {
        $routes_config = require_once ROOT_PATH . DS . 'config' . DS . 'routes_config.php';
        $this->router = new Router(ROOT_PATH . DS . 'page', $routes_config);
    }

    /**
     * Initialize and run system middleware
     */
    private function initializeMiddleware(): void
    {
        try {
            // Get SiteSettingsService for settings access
            $siteSettingsService = $this->services->getSiteSettingsService();
            $logger = $this->services->getLogger();
            $sessionManager = $this->services->getSessionManager();

            // Check AJAX requests to reduce logging
            $is_ajax = (
                (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
                 strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
                (isset($_GET['action']) && $_GET['action'] === 'ajax') ||
                (str_contains($_SERVER['REQUEST_URI'] ?? '', 'action=ajax')) ||
                (str_contains($_SERVER['REQUEST_URI'] ?? '', 'system_monitor') &&
                    str_contains($_SERVER['REQUEST_URI'] ?? '', 'type='))
            );

            // 1. First, initialize Debug Middleware
            // This should be the first to set up an error display
            $debugMiddleware = new DebugMiddleware(
                $siteSettingsService,
                $logger,
                $sessionManager
            );

            if (!$debugMiddleware->handle()) {
                if (!$is_ajax) {
                    $logger->error('Debug middleware failed to initialize');
                }
                return;
            }

            // 2. Then check Maintenance Mode
            // If maintenance mode is enabled - block access
            $maintenanceMiddleware = new MaintenanceMiddleware(
                $siteSettingsService,
                $logger,
                $this->services->getDatabase()
            );

            if (!$maintenanceMiddleware->handle()) {
                // If middleware returned false, it means the maintenance page was shown
                // and execution should stop
                exit;
            }

            // 3. UPDATED: Use modern CSRF protection via CSRFMiddleware
            // Instead of old CSRFProtectionMiddleware, use new architecture
            if (!$is_ajax) {
                $logger->debug('Initializing modern CSRF protection middleware');
            }

            try {
                $csrfMiddleware = new CSRFMiddleware();
                if (!$csrfMiddleware->handleLegacy()) {
                    $logger->warning('CSRF protection failed', [
                        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
                        'uri' => $_SERVER['REQUEST_URI'] ?? 'unknown'
                    ]);
                    // CSRFMiddleware already handled the error via FlashMessage
                    return;
                }
            } catch (Throwable $csrfError) {
                $logger->error('CSRF Middleware failed', [
                    'error' => $csrfError->getMessage(),
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                    'uri' => $_SERVER['REQUEST_URI'] ?? 'unknown'
                ]);
                return;
            }

            // 4. ADDED: Rate-Limiting protection
            //  against spam and brute force attacks
            try {
                $rateLimitMiddleware = new RateLimitMiddleware();
                if (!$rateLimitMiddleware->handle()) {
                    if (!$is_ajax) {
                        $logger->warning('Request blocked by rate limiter', [
                            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                            'uri' => $_SERVER['REQUEST_URI'] ?? 'unknown'
                        ]);
                    }
                    return;
                }
            } catch (Throwable $rateLimitError) {
                $logger->error('Rate limit middleware failed', [
                    'error' => $rateLimitError->getMessage()
                ]);
                // Continue execution without rate limiting
            }

            // Log only for regular requests
            if (!$is_ajax) {
                $logger->debug('System middleware initialized successfully');
            }

        } catch (Throwable $e) {
            $this->services->getLogger()->error('Failed to initialize middleware', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            // In case of middleware error, continue the application work
            // but without special modes
        }
    }

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
