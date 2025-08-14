<?php

/**
 * Admin Navigation Component
 * Unified navigation system for admin panel with role-based access control
 * Updated with dynamic badge system for notifications
 *
 * @author GitHub Copilot
 */

declare(strict_types=1);

namespace App\Application\Components;

use App\Domain\Interfaces\AuthenticationInterface;
use App\Domain\Interfaces\DatabaseInterface;
use Exception;
use PDO;

class AdminNavigation
{
    private array $currentUser;
    private string $currentPage;
    private array $navigationItems;
    private array $badgeCounts = [];
    private readonly ?DatabaseInterface $database;

    public function __construct(
        private readonly AuthenticationInterface $authService,
        ?DatabaseInterface $database = null
    ) {
        $this->currentUser = $this->authService->getCurrentUser() ?? [];
        $this->currentPage = $_GET['page'] ?? '';

        // Auto-inject database from global container if not provided
        if ($database === null) {
            global $container;
            try {
                if (isset($container)) {
                    $serviceProvider = \App\Application\Core\ServiceProvider::getInstance($container);
                    $database = $serviceProvider->getDatabase();
                }
            } catch (Exception $e) {
                // Silently continue without database - badges just won't work
                error_log("AdminNavigation: Could not auto-inject database: " . $e->getMessage());
            }
        }

        $this->database = $database;
        $this->initializeNavigation();
    }

    /**
     * Initialize navigation structure with role-based access control
     */
    private function initializeNavigation(): void
    {
        $userRole = $this->currentUser['role'] ?? 'guest';

        // Different navigation for different roles
        if ($userRole === 'admin') {
            $this->navigationItems = [
                'dashboard' => [
                    'title' => 'Dashboard',
                    'icon' => 'fas fa-tachometer-alt',
                    'url' => '/index.php?page=dashboard',
                    'badge' => null
                ],
                'manage_articles' => [
                    'title' => 'Articles',
                    'icon' => 'fas fa-newspaper',
                    'url' => '/index.php?page=manage_articles',
                    'badge' => null
                ],
                'manage_categories' => [
                    'title' => 'Categories',
                    'icon' => 'fas fa-tags',
                    'url' => '/index.php?page=manage_categories',
                    'badge' => null
                ],
                'admin_moderation_dashboard' => [
                    'title' => 'Moderation',
                    'icon' => 'fas fa-shield-alt',
                    'url' => '/index.php?page=admin_moderation_dashboard',
                    'badge' => $this->getModerationBadgeCount()
                ],
                'manage_users' => [
                    'title' => 'Users',
                    'icon' => 'fas fa-users',
                    'url' => '/index.php?page=manage_users',
                    'badge' => null
                ],
                'system_monitor' => [
                    'title' => 'System',
                    'icon' => 'fas fa-server',
                    'url' => '/index.php?page=system_monitor',
                    'badge' => null
                ],
                'site_settings' => [
                    'title' => 'Settings',
                    'icon' => 'fas fa-cogs',
                    'url' => '/index.php?page=site_settings',
                    'badge' => null
                ]
            ];
        } elseif ($userRole === 'employee') {
            $this->navigationItems = [
                'dashboard' => [
                    'title' => 'Dashboard',
                    'icon' => 'fas fa-tachometer-alt',
                    'url' => '/index.php?page=dashboard',
                    'badge' => null
                ],
                'manage_articles' => [
                    'title' => 'Articles',
                    'icon' => 'fas fa-newspaper',
                    'url' => '/index.php?page=manage_articles',
                    'badge' => null
                ],
                'manage_categories' => [
                    'title' => 'Categories',
                    'icon' => 'fas fa-tags',
                    'url' => '/index.php?page=manage_categories',
                    'badge' => null
                ],
                'admin_moderation_dashboard' => [
                    'title' => 'Moderation',
                    'icon' => 'fas fa-shield-alt',
                    'url' => '/index.php?page=admin_moderation_dashboard',
                    'badge' => $this->getModerationBadgeCount()
                ]
            ];
        } elseif ($userRole === 'client') {
            $this->navigationItems = [
                'dashboard' => [
                    'title' => 'Dashboard',
                    'icon' => 'fas fa-tachometer-alt',
                    'url' => '/index.php?page=dashboard',
                    'badge' => null
                ],
                'user_tickets' => [
                    'title' => 'Support',
                    'icon' => 'fas fa-ticket-alt',
                    'url' => '/index.php?page=user_tickets',
                    'badge' => $this->getTicketsBadgeCount()
                ],
                'user_portfolio' => [
                    'title' => 'Portfolio',
                    'icon' => 'fas fa-briefcase',
                    'url' => '/index.php?page=user_portfolio',
                    'badge' => null
                ],
                'user_projects' => [
                    'title' => 'Projects',
                    'icon' => 'fas fa-code',
                    'url' => '/index.php?page=user_projects',
                    'badge' => null
                ],
                'user_profile' => [
                    'title' => 'Profile',
                    'icon' => 'fas fa-user',
                    'url' => '/index.php?page=user_profile',
                    'badge' => null
                ]
            ];
        }
    }

    /**
     * Get moderation badge count for admin/employee
     */
    private function getModerationBadgeCount(): ?int
    {
        if (!$this->database) {
            return $this->badgeCounts['moderation'] ?? null;
        }

        try {
            // Count pending project moderation requests
            $pendingProjectsStmt = $this->database->prepare("
                SELECT COUNT(*) 
                FROM project_moderation 
                WHERE status = 'pending'
            ");
            $pendingProjectsStmt->execute();
            $pendingProjects = (int)$pendingProjectsStmt->fetchColumn();

            // Count pending comments (fix: use correct column name 'status' instead of 'moderation_status')
            $pendingCommentsStmt = $this->database->prepare("
                SELECT COUNT(*) 
                FROM comments 
                WHERE status = 'pending'
            ");
            $pendingCommentsStmt->execute();
            $pendingComments = (int)$pendingCommentsStmt->fetchColumn();

            $total = $pendingProjects + $pendingComments;
            return $total > 0 ? $total : null;
        } catch (Exception $e) {
            error_log("Error getting moderation badge count: " . $e->getMessage());
            return $this->badgeCounts['moderation'] ?? null;
        }
    }

    /**
     * Get tickets badge count for client
     */
    private function getTicketsBadgeCount(): ?int
    {
        if (!$this->database) {
            return $this->badgeCounts['tickets'] ?? null;
        }

        try {
            $userId = $this->currentUser['id'] ?? 0;

            $stmt = $this->database->prepare("
                SELECT COUNT(*) 
                FROM tickets 
                WHERE user_id = :user_id 
                AND status IN ('open', 'pending') 
                AND is_active = 1
            ");
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();

            $count = (int)$stmt->fetchColumn();
            return $count > 0 ? $count : null;
        } catch (Exception $e) {
            error_log("Error getting tickets badge count: " . $e->getMessage());
            return $this->badgeCounts['tickets'] ?? null;
        }
    }

    /**
     * Check if navigation item is currently active
     */
    private function isActive(string $page): bool
    {
        return $this->currentPage === $page;
    }

    /**
     * Get brand name and icon based on user role
     */
    private function getBrandInfo(): array
    {
        $userRole = $this->currentUser['role'] ?? 'guest';

        switch ($userRole) {
            case 'admin':
                return [
                    'name' => 'Admin Panel',
                    'icon' => 'fas fa-shield-alt'
                ];
            case 'employee':
                return [
                    'name' => 'Staff Panel',
                    'icon' => 'fas fa-users-cog'
                ];
            case 'client':
                return [
                    'name' => 'Client Portal',
                    'icon' => 'fas fa-shield-alt'
                ];
            default:
                return [
                    'name' => 'Portal',
                    'icon' => 'fas fa-home'
                ];
        }
    }

    /**
     * Generate navigation HTML matching existing dashboard style
     */
    public function render(): string
    {
        if (empty($this->currentUser) || empty($this->navigationItems)) {
            return '';
        }

        $brandInfo = $this->getBrandInfo();

        $html = '<nav class="admin-nav">';
        $html .= '<div class="admin-nav-container">';

        // Brand/Logo
        $html .= '<a href="/index.php?page=dashboard" class="admin-nav-brand">';
        $html .= '<i class="' . $brandInfo['icon'] . '"></i>';
        $html .= '<span>' . htmlspecialchars($brandInfo['name']) . '</span>';
        $html .= '</a>';

        // Navigation items
        $html .= '<div class="admin-nav-links">';

        foreach ($this->navigationItems as $page => $item) {
            $isActive = $this->isActive($page);
            $activeStyle = $isActive ?
                ' style="background-color: var(--admin-primary-bg); color: var(--admin-primary-light); border-color: var(--admin-primary-border);"' :
                '';

            $html .= '<a href="' . htmlspecialchars($item['url']) . '" class="admin-nav-link"' . $activeStyle . '>';
            $html .= '<i class="' . $item['icon'] . '"></i>';
            $html .= '<span>' . htmlspecialchars($item['title']) . '</span>';

            // Add badge if present with improved styling
            if ($item['badge'] && $item['badge'] > 0) {
                $badgeClass = $item['badge'] > 9 ? 'admin-badge-error' : 'admin-badge-warning';
                $badgeText = $item['badge'] > 99 ? '99+' : (string)$item['badge'];

                $html .= '<span class="admin-badge ' . $badgeClass . '" style="margin-left: 0.5rem; padding: 0.25rem 0.5rem; font-size: 0.6rem; font-weight: 600; border-radius: 0.75rem; line-height: 1.2; min-width: 1.2rem; text-align: center;">';
                $html .= $badgeText;
                $html .= '</span>';
            }

            $html .= '</a>';
        }

        $html .= '</div>'; // admin-nav-links
        $html .= '</div>'; // admin-nav-container
        $html .= '</nav>';

        return $html;
    }

    /**
     * Render JavaScript for real-time badge updates
     */
    public function renderBadgeUpdateScript(): string
    {
        $userRole = $this->currentUser['role'] ?? 'guest';

        return "
        <script>
        // Navigation Badge Update System
        class NavigationBadgeUpdater {
            constructor() {
                this.updateInterval = 30000; // 30 seconds
                this.init();
            }

            init() {
                this.startAutoUpdate();
                this.setupEventListeners();
            }

            startAutoUpdate() {
                setInterval(() => {
                    this.updateBadges();
                }, this.updateInterval);
            }

            async updateBadges() {
                try {
                    const response = await fetch('/index.php?page=api_navigation_stats', {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });

                    if (response.ok) {
                        const data = await response.json();
                        this.applyBadgeUpdates(data);
                    }
                } catch (error) {
                    console.log('Badge update failed:', error);
                }
            }

            applyBadgeUpdates(stats) {
                // Update moderation badge
                if (stats.admin_moderation_dashboard) {
                    this.updateNavigationBadge('admin_moderation_dashboard', stats.admin_moderation_dashboard.count);
                }

                // Update tickets badge
                if (stats.user_tickets) {
                    this.updateNavigationBadge('user_tickets', stats.user_tickets.count);
                }

                // Remove badges for zero counts
                Object.keys(stats).forEach(key => {
                    if (stats[key].count === 0) {
                        this.removeNavigationBadge(key);
                    }
                });
            }

            updateNavigationBadge(navigationKey, count) {
                const navLink = document.querySelector('a[href*=\"' + navigationKey + '\"], a[href*=\"' + navigationKey.replace('admin_', '') + '\"]');
                
                if (navLink && count > 0) {
                    let badge = navLink.querySelector('.admin-badge');
                    
                    if (!badge) {
                        badge = document.createElement('span');
                        badge.className = 'admin-badge admin-badge-error';
                        badge.style.cssText = 'margin-left: 0.5rem; padding: 0.25rem 0.5rem; font-size: 0.6rem; font-weight: 600; border-radius: 0.75rem; line-height: 1.2; min-width: 1.2rem; text-align: center;';
                        navLink.appendChild(badge);
                    }
                    
                    badge.textContent = count > 99 ? '99+' : count.toString();
                    badge.className = 'admin-badge ' + (count > 9 ? 'admin-badge-error' : 'admin-badge-warning');
                }
            }

            removeNavigationBadge(navigationKey) {
                const navLink = document.querySelector('a[href*=\"' + navigationKey + '\"], a[href*=\"' + navigationKey.replace('admin_', '') + '\"]');
                
                if (navLink) {
                    const badge = navLink.querySelector('.admin-badge');
                    if (badge) {
                        badge.remove();
                    }
                }
            }

            setupEventListeners() {
                // Update badges when returning to tab
                document.addEventListener('visibilitychange', () => {
                    if (!document.hidden) {
                        this.updateBadges();
                    }
                });
            }
        }

        // Initialize badge updater
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => {
                window.navigationBadgeUpdater = new NavigationBadgeUpdater();
            });
        } else {
            window.navigationBadgeUpdater = new NavigationBadgeUpdater();
        }
        </script>";
    }

    /**
     * Get breadcrumbs for current page
     */
    public function getBreadcrumbs(): array
    {
        $breadcrumbs = [
            ['title' => 'Dashboard', 'url' => '/index.php?page=dashboard']
        ];

        // Add current page if not dashboard
        if ($this->currentPage !== 'dashboard' && isset($this->navigationItems[$this->currentPage])) {
            $breadcrumbs[] = [
                'title' => $this->navigationItems[$this->currentPage]['title'],
                'url' => null
            ];
        }

        return $breadcrumbs;
    }

    /**
     * Get current user role
     */
    public function getCurrentUserRole(): string
    {
        return $this->currentUser['role'] ?? 'guest';
    }

    /**
     * Check if current user has specific role
     */
    public function hasRole(string $role): bool
    {
        return $this->getCurrentUserRole() === $role;
    }

    /**
     * Set badge count for specific navigation item (unified method)
     */
    public function setBadgeCount(string $navigationKey, int $count): void
    {
        if (isset($this->navigationItems[$navigationKey])) {
            $this->navigationItems[$navigationKey]['badge'] = $count > 0 ? $count : null;
        }

        // Store in internal cache for fallback
        switch ($navigationKey) {
            case 'admin_moderation_dashboard':
                $this->badgeCounts['moderation'] = $count;
                break;
            case 'user_tickets':
                $this->badgeCounts['tickets'] = $count;
                break;
        }
    }

    /**
     * Get current badge count for a navigation item
     */
    public function getBadgeCount(string $navigationKey): ?int
    {
        return $this->navigationItems[$navigationKey]['badge'] ?? null;
    }

    /**
     * Update all badge counts dynamically
     */
    public function updateBadgeCounts(): void
    {
        $userRole = $this->currentUser['role'] ?? 'guest';

        if (in_array($userRole, ['admin', 'employee'])) {
            $this->navigationItems['admin_moderation_dashboard']['badge'] = $this->getModerationBadgeCount();
        }

        if ($userRole === 'client') {
            $this->navigationItems['user_tickets']['badge'] = $this->getTicketsBadgeCount();
        }
    }
}
