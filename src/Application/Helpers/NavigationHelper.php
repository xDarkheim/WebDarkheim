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
     * Get navigation for users
     */
    public static function getUserNavigation(bool $isAuthenticated = false): array
    {
        if ($isAuthenticated) {
            return [
                [
                    'key' => 'user-menu',
                    'url' => '#',
                    'text' => 'User Menu',
                    'class' => 'user-dropdown-trigger',
                    'dropdown' => [
                        ['key' => 'dashboard', 'url' => '/index.php?page=dashboard', 'text' => 'Dashboard'],
                        ['key' => 'user_profile', 'url' => '/index.php?page=user_profile', 'text' => 'My Profile'],
                        ['key' => 'user_portfolio', 'url' => '/index.php?page=user_portfolio', 'text' => 'My Portfolio'],
                        ['key' => 'user_tickets', 'url' => '/index.php?page=user_tickets', 'text' => 'Support Tickets'],
                        ['key' => 'user_profile_settings', 'url' => '/index.php?page=user_profile_settings', 'text' => 'Settings'],
                        ['key' => 'logout', 'url' => '/index.php?page=api_auth_logout', 'text' => 'Logout', 'class' => 'logout-link']
                    ]
                ]
            ];
        }

        return [
            ['key' => 'login', 'url' => '/index.php?page=login', 'text' => 'Login'],
            ['key' => 'register', 'url' => '/index.php?page=register', 'text' => 'Register'],
        ];
    }

    /**
     * Get admin navigation
     */
    public static function getAdminNavigation(): array
    {
        return [
            ['key' => 'manage_users', 'url' => '/index.php?page=manage_users', 'text' => 'Manage Users'],
            ['key' => 'site_settings', 'url' => '/index.php?page=site_settings', 'text' => 'Site Settings'],
            ['key' => 'manage_categories', 'url' => '/index.php?page=manage_categories', 'text' => 'Manage Categories'],
            ['key' => 'backup_monitor', 'url' => '/index.php?page=backup_monitor', 'text' => 'Backup Monitor'],
            ['key' => 'system_monitor', 'url' => '/index.php?page=system_monitor', 'text' => 'System Monitor'],
        ];
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
