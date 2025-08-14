<?php

/**
 * Navigation Helper
 * Centralized site navigation management
 * Provides methods for generating navigation menus, breadcrumbs, and URLs
 *
 * @author Dmytro Hovenko
 */

declare(strict_types=1);

namespace App\Application\Helpers;


class NavigationHelper
{
    /**
     * Get main navigation configuration
     */
    public static function getMainNavigation(): array
    {
        return [
            ['key' => 'home', 'url' => '/index.php?page=home', 'text' => 'Home'],
            ['key' => 'news', 'url' => '/index.php?page=news', 'text' => 'News'],
            [
                'key' => 'projects',
                'url' => '/index.php?page=projects',
                'text' => 'Projects',
                'dropdown' => [
                    [
                        'key' => 'projects-all',
                        'url' => '/index.php?page=projects',
                        'text' => 'All Projects'
                    ],
                    [
                        'key' => 'web-development',
                        'url' => '/index.php?page=projects&category=web',
                        'text' => 'Web Development'
                    ],
                    [
                        'key' => 'mobile-apps',
                        'url' => '/index.php?page=projects&category=mobile',
                        'text' => 'Mobile Apps'
                    ],
                    [
                        'key' => 'desktop-software',
                        'url' => '/index.php?page=projects&category=desktop',
                        'text' => 'Desktop Software'
                    ],
                    [
                        'key' => 'open-source',
                        'url' => '/index.php?page=projects&category=opensource',
                        'text' => 'Open Source'
                    ],
                ]
            ],
            [
                'key' => 'services',
                'url' => '/index.php?page=services',
                'text' => 'Services',
                'dropdown' => [
                    [
                        'key' => 'services-all',
                        'url' => '/index.php?page=services',
                        'text' => 'All Services'
                    ],
                    [
                        'key' => 'consulting',
                        'url' => '/index.php?page=services&type=consulting',
                        'text' => 'Consulting'
                    ],
                    [
                        'key' => 'development',
                        'url' => '/index.php?page=services&type=development',
                        'text' => 'Development'
                    ],
                    [
                        'key' => 'support',
                        'url' => '/index.php?page=services&type=support',
                        'text' => 'Support'
                    ],
                ]
            ],
            ['key' => 'about', 'url' => '/index.php?page=about', 'text' => 'About'],
            ['key' => 'team', 'url' => '/index.php?page=team', 'text' => 'Team'],
            ['key' => 'contact', 'url' => '/index.php?page=contact', 'text' => 'Contact'],
        ];
    }

    /**
     * Get navigation for users with role-based access control
     */
    public static function getUserNavigation(bool $isAuthenticated = false): array
    {
        if (!$isAuthenticated) {
            return [
                ['key' => 'login', 'url' => '/index.php?page=login', 'text' => 'Login'],
                ['key' => 'register', 'url' => '/index.php?page=register', 'text' => 'Register'],
            ];
        }

        // Get current user role from session
        $userRole = $_SESSION['user_role'] ?? 'client';

        // Base dropdown items for all authenticated users
        $baseDropdown = [];

        // Role-specific dropdown items with hierarchical access control
        // Role hierarchy: guest < client < employee < admin
        // Each higher role inherits access from lower roles plus additional permissions
        switch ($userRole) {
            case 'admin':
                // Admin gets full system access - all administrative functions
                $baseDropdown = [
                    ['key' => 'dashboard', 'url' => '/index.php?page=dashboard', 'text' => 'Admin Dashboard', 'icon' => 'fas fa-tachometer-alt'],
                    // Content Management
                    ['key' => 'manage_articles', 'url' => '/index.php?page=manage_articles', 'text' => 'Manage Articles', 'icon' => 'fas fa-newspaper'],
                    ['key' => 'manage_categories', 'url' => '/index.php?page=manage_categories', 'text' => 'Manage Categories', 'icon' => 'fas fa-tags'],
                    ['key' => 'admin_moderation_dashboard', 'url' => '/index.php?page=admin_moderation_dashboard', 'text' => 'Content Moderation', 'icon' => 'fas fa-shield-check'],
                    // User & System Management
                    ['key' => 'manage_users', 'url' => '/index.php?page=manage_users', 'text' => 'Manage Users', 'icon' => 'fas fa-users-cog'],
                    ['key' => 'system_monitor', 'url' => '/index.php?page=system_monitor', 'text' => 'System Monitor', 'icon' => 'fas fa-server'],
                    ['key' => 'backup_monitor', 'url' => '/index.php?page=backup_monitor', 'text' => 'Backup Monitor', 'icon' => 'fas fa-database'],
                    ['key' => 'site_settings', 'url' => '/index.php?page=site_settings', 'text' => 'Site Settings', 'icon' => 'fas fa-cog'],
                ];
                break;

            case 'employee':
                // Employee gets only moderation rights, not content management
                $baseDropdown = [
                    ['key' => 'dashboard', 'url' => '/index.php?page=dashboard', 'text' => 'Employee Dashboard', 'icon' => 'fas fa-briefcase'],
                    // Moderation Only
                    ['key' => 'admin_moderation_dashboard', 'url' => '/index.php?page=admin_moderation_dashboard', 'text' => 'Moderation Dashboard', 'icon' => 'fas fa-shield-alt'],
                    ['key' => 'moderate_projects', 'url' => '/index.php?page=moderate_projects', 'text' => 'Moderate Projects', 'icon' => 'fas fa-project-diagram'],
                    ['key' => 'moderate_comments', 'url' => '/index.php?page=moderate_comments', 'text' => 'Moderate Comments', 'icon' => 'fas fa-comments'],
                    // Personal
                    ['key' => 'user_profile', 'url' => '/index.php?page=user_profile', 'text' => 'My Profile', 'icon' => 'fas fa-user-edit'],
                ];
                break;

            case 'client':
                // Client gets portfolio creation, project management and support access
                $baseDropdown = [
                    ['key' => 'dashboard', 'url' => '/index.php?page=dashboard', 'text' => 'Client Dashboard', 'icon' => 'fas fa-chart-line'],
                    // Portfolio & Projects
                    ['key' => 'user_portfolio', 'url' => '/index.php?page=user_portfolio', 'text' => 'My Portfolio', 'icon' => 'fas fa-folder-open'],
                    ['key' => 'portfolio_create', 'url' => '/index.php?page=portfolio_create', 'text' => 'Create Project', 'icon' => 'fas fa-plus-circle'],
                    // PHASE 8: Client Portal - Support & Communication
                    ['key' => 'user_tickets', 'url' => '/index.php?page=user_tickets', 'text' => 'Support Tickets', 'icon' => 'fas fa-ticket-alt'],
                    ['key' => 'user_projects', 'url' => '/index.php?page=user_projects', 'text' => 'My Projects', 'icon' => 'fas fa-project-diagram'],
                    ['key' => 'user_invoices', 'url' => '/index.php?page=user_invoices', 'text' => 'Invoices & Billing', 'icon' => 'fas fa-file-invoice-dollar'],
                    ['key' => 'user_documents', 'url' => '/index.php?page=user_documents', 'text' => 'Documents', 'icon' => 'fas fa-file-alt'],
                    ['key' => 'user_meetings', 'url' => '/index.php?page=user_meetings', 'text' => 'Meetings', 'icon' => 'fas fa-calendar-alt'],
                    // Personal
                    ['key' => 'user_profile', 'url' => '/index.php?page=user_profile', 'text' => 'My Profile', 'icon' => 'fas fa-user-edit'],
                ];
                break;

            case 'guest':
            default:
                // Guest users get minimal access
                $baseDropdown = [
                    ['key' => 'dashboard', 'url' => '/index.php?page=dashboard', 'text' => 'Dashboard'],
                    ['key' => 'user_profile', 'url' => '/index.php?page=user_profile', 'text' => 'My Profile'],
                ];
                break;
        }

        // Add logout for all authenticated users
        $baseDropdown[] = ['key' => 'logout', 'url' => '/index.php?page=api_auth_logout', 'text' => 'Logout', 'class' => 'logout-link'];

        return [
            [
                'key' => 'user-menu',
                'url' => '#',
                'text' => 'User Menu',
                'class' => 'user-dropdown-trigger',
                'dropdown' => $baseDropdown
            ]
        ];
    }

    /**
     * Check if user has access to specific page based on role
     */
    public static function canAccessPage(string $page, string $userRole): bool
    {
        // Admin pages - only for admin
        $adminPages = ['manage_users', 'admin_users', 'site_settings', 'admin_settings', 'system_monitor', 'backup_monitor'];
        if (in_array($page, $adminPages) && $userRole !== 'admin') {
            return false;
        }

        // Staff pages - for admin and employee
        $staffPages = ['manage_articles', 'manage_categories', 'admin_moderation_dashboard', 'moderate_projects', 'moderate_comments'];
        if (in_array($page, $staffPages) && !in_array($userRole, ['admin', 'employee'])) {
            return false;
        }

        // Client pages - for admin, employee, and client
        $clientPages = [
            'user_portfolio', 'portfolio_create', 'user_portfolio_edit', 'portfolio_settings',
            'user_tickets', 'user_tickets_create', 'user_tickets_view',
            'user_projects', 'user_projects_details', 'user_projects_timeline',
            'user_invoices', 'user_invoices_download',
            'user_documents', 'user_documents_download',
            'user_meetings', 'user_meetings_schedule'
        ];
        if (in_array($page, $clientPages) && !in_array($userRole, ['admin', 'employee', 'client'])) {
            return false;
        }

        // Dashboard access - only for admin, employee, and client (NOT guest)
        if ($page === 'dashboard') {
            return in_array($userRole, ['admin', 'employee', 'client']);
        }

        // Profile pages - all authenticated users (including guest)
        $profilePages = ['user_profile', 'profile_edit', 'user_profile_settings'];
        if (in_array($page, $profilePages)) {
            return true;
        }

        return true;
    }

    /**
     * Get role-specific dashboard title and description
     */
    public static function getRoleDashboardInfo(string $role): array
    {
        switch ($role) {
            case 'admin':
                return [
                    'title' => 'Admin Dashboard',
                    'description' => 'Full system administration and management',
                    'icon' => 'fas fa-shield-alt'
                ];
            case 'employee':
                return [
                    'title' => 'Employee Dashboard',
                    'description' => 'Content management and moderation tools',
                    'icon' => 'fas fa-user-tie'
                ];
            case 'client':
                return [
                    'title' => 'Client Dashboard',
                    'description' => 'Portfolio management and support tickets',
                    'icon' => 'fas fa-user'
                ];
            case 'guest':
            default:
                return [
                    'title' => 'Guest Dashboard',
                    'description' => 'Basic access and profile management',
                    'icon' => 'fas fa-user-circle'
                ];
        }
    }

    /**
     * Get footer navigation
     */
    public static function getFooterNavigation(): array
    {
        return [
            'company' => [
                'title' => 'Company',
                'links' => [
                    ['key' => 'about', 'url' => '/index.php?page=about', 'text' => 'About Us'],
                    ['key' => 'team', 'url' => '/index.php?page=team', 'text' => 'Our Team'],
                    ['key' => 'careers', 'url' => '/index.php?page=careers', 'text' => 'Careers'],
                    ['key' => 'contact', 'url' => '/index.php?page=contact', 'text' => 'Contact'],
                ]
            ],
            'services' => [
                'title' => 'Services',
                'links' => [
                    ['key' => 'consulting', 'url' => '/index.php?page=services&type=consulting', 'text' => 'Consulting'],
                    ['key' => 'development', 'url' => '/index.php?page=services&type=development', 'text' => 'Development'],
                    ['key' => 'support', 'url' => '/index.php?page=services&type=support', 'text' => 'Support'],
                    ['key' => 'projects', 'url' => '/index.php?page=projects', 'text' => 'View Projects'],
                ]
            ],
            'legal' => [
                'title' => 'Legal',
                'links' => [
                    ['key' => 'privacy', 'url' => '/index.php?page=privacy', 'text' => 'Privacy Policy'],
                    ['key' => 'terms', 'url' => '/index.php?page=terms', 'text' => 'Terms of Service'],
                    ['key' => 'cookies', 'url' => '/index.php?page=cookies', 'text' => 'Cookie Policy'],
                ]
            ]
        ];
    }

    /**
     * Get breadcrumbs for the page
     */
    public static function getBreadcrumbs(string $currentPage): array
    {
        $breadcrumbs = [
            ['text' => 'Home', 'url' => '/index.php?page=home']
        ];

        $pageMap = [
//          'news' => ['text' => 'News', 'url' => '/index.php?page=news'],
            'about' => ['text' => 'About', 'url' => '/index.php?page=about'],
            'contact' => ['text' => 'Contact', 'url' => '/index.php?page=contact'],
            'projects' => ['text' => 'Projects', 'url' => '/index.php?page=projects'],
            'services' => ['text' => 'Services', 'url' => '/index.php?page=services'],
            'login' => ['text' => 'Login', 'url' => null],
            'register' => ['text' => 'Register', 'url' => null],
            'dashboard' => ['text' => 'Dashboard', 'url' => null],
            'profile' => ['text' => 'Profile', 'url' => null],
        ];

        if (isset($pageMap[$currentPage])) {
            $breadcrumbs[] = $pageMap[$currentPage];
        }

        return $breadcrumbs;
    }

    /**
     * Check if the navigation item is active
     */
    public static function isActive(string $navKey, string $currentPage): bool
    {
        return $navKey === $currentPage;
    }

    /**
     * Build URL with the correct base URL
     */
    public static function buildUrl(string $path): string
    {
        $baseUrl = rtrim(getSiteUrl(), '/');
        return $baseUrl . $path;
    }
}
