<?php

/**
 * Middleware for handling maintenance mode
 * Checks the maintenance_mode setting and blocks access if necessary
 * Uses LoggerInterface for logging
 * Uses SessionManager for session management
 * Uses ConfigurationManager for dynamic settings loading
 * Uses ServiceProvider for dependency injection
 * Provides static methods for quick validation and token generation
 *
 * @author Dmytro Hovenko
 */

declare(strict_types=1);

namespace App\Application\Middleware;

use App\Application\Services\SiteSettingsService;
use App\Domain\Interfaces\LoggerInterface;
use App\Domain\Interfaces\DatabaseInterface;
use Exception;
use Throwable;


readonly class MaintenanceMiddleware implements MiddlewareInterface
{
    public function __construct(
        private SiteSettingsService $siteSettings,
        private LoggerInterface     $logger,
        private DatabaseInterface   $database
    ) {
    }

    /**
     * @throws Exception
     */
    public function handle(): bool
    {
        // Ensure session is available for admin detection during maintenance
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        // Check if maintenance mode is enabled
        $maintenanceMode = $this->siteSettings->get('maintenance_mode', false);

        if ($maintenanceMode) {
            // Allow access to an admin login form during maintenance
            if ($this->isMaintenanceLoginRequest()) {
                $this->logger->info('Maintenance mode active - allowing admin login request');
                return true;
            }

            // Check if the user is an administrator
            if (!$this->isAdmin()) {
                $this->logger->info('Maintenance mode active - blocking user access');
                $this->displayMaintenancePage();
                return false;
            }

            $this->logger->info('Maintenance mode active - allowing admin access');
        }

        return true;
    }

    /**
     * Checks if the current user is an administrator
     */
    private function isAdmin(): bool
    {
        // 0) If the global auth service is available and confirms an admin role — trust it
        if (isset($GLOBALS['auth'])) {
            $auth = $GLOBALS['auth'];
            try {
                if (is_object($auth)
                    && method_exists($auth, 'isAuthenticated')
                    && method_exists($auth, 'hasRole')
                    && $auth->isAuthenticated()
                    && $auth->hasRole('admin')) {
                    return true;
                }
            } catch (Throwable $e) {
                // Log but do not fail — continue with additional checks
                $this->logger->error('Auth service check failed in maintenance admin detection', [
                    'error' => $e->getMessage()
                ]);
            }
        }

        // 1) Ensure session is available
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        // 2) If a session has standard user structures — use them
        // Try to hydrate user_id from common structures
        if (empty($_SESSION['user_id'])) {
            if (isset($_SESSION['current_user']['id']) && $_SESSION['current_user']['id']) {
                $_SESSION['user_id'] = (int) $_SESSION['current_user']['id'];
            } elseif (isset($_SESSION['user']['id']) && $_SESSION['user']['id']) {
                $_SESSION['user_id'] = (int) $_SESSION['user']['id'];
            } elseif (isset($_SESSION['auth_user']['id']) && $_SESSION['auth_user']['id']) {
                $_SESSION['user_id'] = (int) $_SESSION['auth_user']['id'];
            }
        }

        // If the authentication flag is explicitly set and false — not admin
        if (isset($_SESSION['user_authenticated']) && $_SESSION['user_authenticated'] !== true) {
            return false;
        }

        // 3) Check admin rights from various possible session keys
        if (
            (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') ||
            (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') ||
            (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true) ||
            (isset($_SESSION['current_user']['role']) && $_SESSION['current_user']['role'] === 'admin') ||
            (isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'admin') ||
            (isset($_SESSION['auth_user']['role']) && $_SESSION['auth_user']['role'] === 'admin')
        ) {
            return true;
        }

        // 4) If user_id is known — check a role in the database for reliability
        if (!empty($_SESSION['user_id'])) {
            try {
                $query = "SELECT role FROM users WHERE id = ? AND status = 'active'";
                $stmt = $this->database->getConnection()->prepare($query);
                $stmt->execute([$_SESSION['user_id']]);
                $user = $stmt->fetch();

                if ($user && isset($user['role']) && $user['role'] === 'admin') {
                    // Cache in session
                    $_SESSION['user_role'] = 'admin';
                    $_SESSION['role'] = 'admin';
                    $_SESSION['is_admin'] = true;
                    return true;
                }
            } catch (Exception $e) {
                $this->logger->error('Failed to check user role from database', [
                    'user_id' => $_SESSION['user_id'],
                    'error' => $e->getMessage()
                ]);
            }
        }

        // Administrator not recognized
        return false;
    }

    /**
     * Checks if the request is for the admin login form during maintenance
     */
    private function isMaintenanceLoginRequest(): bool
    {
        $currentPage = $_GET['page'] ?? '';
        $maintenance = $_GET['maintenance'] ?? '';

        // Allow access to an admin login form
        return ($currentPage === 'form_login' && $maintenance === '1') ||
            (isset($_POST['maintenance_login']) && $_POST['maintenance_login'] === '1');
    }

    /**
     * Displays the maintenance mode page
     * @throws Exception
     */
    private function displayMaintenancePage(): void
    {
        // Set HTTP status 503 Service Unavailable
        http_response_code(503);

        // Set Retry-After header (in 1 hour)
        header('Retry-After: 3600');

        // Get the site name from settings
        $siteName = $this->siteSettings->get('site_name', 'Website');

        // Generate CSRF token for login form
        $csrf_token = CSRFMiddleware::getToken();

        // Include the nice maintenance page
        include dirname(__DIR__, 3) . '/page/system/maintenance.php';
        exit;
    }
}
