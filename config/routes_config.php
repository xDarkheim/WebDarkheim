<?php

/**
 * Routes configuration for the reorganized page structure
 * Updated to reflect the new logical organization of pages
 * and to use the new route names
 *
 * @author Dmytro Hovenko
 */

return [
    'routes' => [
        // Public pages
        'home' => ['file' => 'public/home.php', 'title' => 'Homepage'],
        'news' => ['file' => 'public/news.php', 'title' => 'News'],
        'about' => ['file' => 'public/about.php', 'title' => 'About Us'],
        'contact' => ['file' => 'public/contact.php', 'title' => 'Contact'],
        'projects' => ['file' => 'public/projects.php', 'title' => 'Projects'],
        'services' => ['file' => 'public/services.php', 'title' => 'Services'],
        'team' => ['file' => 'public/team.php', 'title' => 'Our Team'],
        'careers' => ['file' => 'public/careers.php', 'title' => 'Careers'],
        'privacy' => ['file' => 'public/privacy.php', 'title' => 'Privacy Policy'],
        'terms' => ['file' => 'public/terms.php', 'title' => 'Terms of Service'],
        'cookies' => ['file' => 'public/cookies.php', 'title' => 'Cookie Policy'],
        // Authentication pages
        'login' => ['file' => 'auth/login.php', 'title' => 'Login'],
        'register' => ['file' => 'auth/register.php', 'title' => 'Register'],
        'forgot_password' => ['file' => 'auth/forgot_password.php', 'title' => 'Forgot Password'],
        'reset_password' => ['file' => 'auth/reset_password.php', 'title' => 'Reset Password'],
        'verify_email' => ['file' => 'auth/verify_email.php', 'title' => 'Verify Email'],
        'verify_email_change' => ['file' => 'auth/verify_email_change.php', 'title' => 'Verify Email Change'],
        'confirm_password_change' => ['file' => 'auth/confirm_password_change.php', 'title' => 'Confirm Password Change'],
        'confirm_email_change' => ['file' => 'auth/confirm_email_change_view.php', 'title' => 'Confirm Email Change'],
        'resend_verification' => ['file' => 'auth/resend_verification.php', 'title' => 'Resend Verification'],

        // API endpoints for controllers (POST requests)
        'api_auth_login' => ['controller' => 'App\\Application\\Controllers\\AuthController', 'method' => 'login'],
        'api_auth_logout' => ['controller' => 'App\\Application\\Controllers\\AuthController', 'method' => 'logout'],
        'api_auth_register' => ['controller' => 'App\\Application\\Controllers\\AuthController', 'method' => 'register'],
        'api_auth_resend_verification' => ['controller' => 'App\\Application\\Controllers\\AuthController', 'method' => 'resendVerification'],

        // Backup API endpoints
        'api_delete_old_backups' => ['controller' => 'App\\Application\\Controllers\\DatabaseBackupController', 'method' => 'cleanupOldBackupsApi'],
        'api_delete_backup' => ['controller' => 'App\\Application\\Controllers\\DatabaseBackupController', 'method' => 'deleteBackupApi'],
        'api_download_backup' => ['controller' => 'App\\Application\\Controllers\\DatabaseBackupController', 'method' => 'downloadBackupApi'],

        // New routes for form handling (replace modules)
        'form_login' => ['controller' => 'App\\Application\\Controllers\\LoginFormController', 'method' => 'handle'],
        'form_register' => ['controller' => 'App\\Application\\Controllers\\RegisterFormController', 'method' => 'handle'],
        'form_contact' => ['controller' => 'App\\Application\\Controllers\\ContactFormController', 'method' => 'handle'],
        'form_comment' => ['controller' => 'App\\Application\\Controllers\\CommentFormController', 'method' => 'handle'],
        'edit_comment' => ['controller' => 'App\\Application\\Controllers\\EditCommentController', 'method' => 'handle'],
        'delete_comment' => ['controller' => 'App\\Application\\Controllers\\DeleteCommentController', 'method' => 'handle'],

        // User dashboard and profile pages
        'dashboard' => ['file' => 'user/dashboard.php', 'title' => 'My Dashboard', 'auth' => true],
        'account_dashboard' => ['file' => 'user/dashboard.php', 'title' => 'My Dashboard', 'auth' => true], // Legacy compatibility
        'profile_edit' => ['file' => 'user/profile/profile_edit.php', 'title' => 'Edit Profile', 'auth' => true],
        'account_edit_profile' => ['file' => 'user/profile/profile_edit.php', 'title' => 'Edit Profile', 'auth' => true], // Legacy compatibility
        'profile_settings' => ['file' => 'user/profile/settings.php', 'title' => 'Profile Settings', 'auth' => true],
        'account_settings' => ['file' => 'user/profile/settings.php', 'title' => 'Profile Settings', 'auth' => true], // Legacy compatibility

        // User content management pages
        'articles_manage' => ['file' => 'user/content/manage_articles.php', 'title' => 'Manage Articles', 'auth' => true],
        'manage_articles' => ['file' => 'user/content/manage_articles.php', 'title' => 'Manage Articles', 'auth' => true], // Legacy compatibility
        'article_create' => ['file' => 'user/content/create_article.php', 'title' => 'Create Article', 'auth' => true],
        'create_article' => ['file' => 'user/content/create_article.php', 'title' => 'Create Article', 'auth' => true], // Legacy compatibility
        'article_edit' => ['file' => 'user/content/edit_article.php', 'title' => 'Edit Article', 'auth' => true],
        'edit_article' => ['file' => 'user/content/edit_article.php', 'title' => 'Edit Article', 'auth' => true], // Legacy compatibility
        'article_delete' => ['file' => 'user/content/delete_article.php', 'title' => 'Delete Article', 'auth' => true],
        'delete_article' => ['file' => 'user/content/delete_article.php', 'title' => 'Delete Article', 'auth' => true], // Legacy compatibility

        // Admin pages
        'site_settings' => ['file' => 'admin/site_settings.php', 'title' => 'Site Settings', 'auth' => true, 'admin' => true],
        'manage_users' => ['file' => 'admin/manage_users.php', 'title' => 'Manage Users', 'auth' => true, 'admin' => true],
        'manage_categories' => ['file' => 'admin/manage_categories.php', 'title' => 'Manage Categories', 'auth' => true, 'admin' => true],
        'backup_monitor' => ['file' => 'admin/backup_monitor.php', 'title' => 'Database Backup Monitor', 'auth' => true, 'admin' => true],
        'system_monitor' => ['file' => 'admin/system_monitor.php', 'title' => 'System Monitor', 'auth' => true, 'admin' => true],
        'edit_user' => ['file' => 'admin/edit_user.php', 'title' => 'Edit User', 'auth' => true, 'admin' => true],
        'edit_category' => ['file' => 'admin/edit_category.php', 'title' => 'Edit Category', 'auth' => true, 'admin' => true],

        // News API endpoints для Navigation v5.0
        'api_filter_articles' => ['file' => 'api/filter_articles.php', 'is_api' => true],

        // System pages
        '404' => ['file' => 'system/404.php', 'title' => 'Page Not Found'],
        'error' => ['file' => 'system/error.php', 'title' => 'Error'],
        'maintenance' => ['file' => 'system/maintenance.php', 'title' => 'Under Maintenance'],
    ],

    // Middleware configuration
    'middleware' => [
        // Global middleware (applied to all routes)
        '*' => [
            // Add global middleware classes here if needed
        ],

        // Route-specific middleware
        'dashboard' => ['App\\Infrastructure\\Middleware\\AuthMiddleware'],
        'account_dashboard' => ['App\\Infrastructure\\Middleware\\AuthMiddleware'],
        'profile_edit' => ['App\\Infrastructure\\Middleware\\AuthMiddleware'],
        'account_edit_profile' => ['App\\Infrastructure\\Middleware\\AuthMiddleware'],
        'profile_settings' => ['App\\Infrastructure\\Middleware\\AuthMiddleware'],
        'account_settings' => ['App\\Infrastructure\\Middleware\\AuthMiddleware'],
        'articles_manage' => ['App\\Infrastructure\\Middleware\\AuthMiddleware'],
        'manage_articles' => ['App\\Infrastructure\\Middleware\\AuthMiddleware'],
        'article_create' => ['App\\Infrastructure\\Middleware\\AuthMiddleware'],
        'create_article' => ['App\\Infrastructure\\Middleware\\AuthMiddleware'],
        'article_edit' => ['App\\Infrastructure\\Middleware\\AuthMiddleware'],
        'edit_article' => ['App\\Infrastructure\\Middleware\\AuthMiddleware'],
        'article_delete' => ['App\\Infrastructure\\Middleware\\AuthMiddleware'],
        'delete_article' => ['App\\Infrastructure\\Middleware\\AuthMiddleware'],
        'admin_manage_users' => ['App\\Infrastructure\\Middleware\\AdminMiddleware'],
        'manage_users' => ['App\\Infrastructure\\Middleware\\AdminMiddleware'],
        'admin_edit_user' => ['App\\Infrastructure\\Middleware\\AdminMiddleware'],
        'edit_user' => ['App\\Infrastructure\\Middleware\\AdminMiddleware'],
        'admin_manage_categories' => ['App\\Infrastructure\\Middleware\\AdminMiddleware'],
        'manage_categories' => ['App\\Infrastructure\\Middleware\\AdminMiddleware'],
        'admin_edit_category' => ['App\\Infrastructure\\Middleware\\AdminMiddleware'],
        'edit_category' => ['App\\Infrastructure\\Middleware\\AdminMiddleware'],
        'admin_site_settings' => ['App\\Infrastructure\\Middleware\\AdminMiddleware'],
        'site_settings' => ['App\\Infrastructure\\Middleware\\AdminMiddleware'],
        'backup_monitor' => ['App\\Infrastructure\\Middleware\\AdminMiddleware'],
        'system_monitor' => ['App\\Infrastructure\\Middleware\\AdminMiddleware'],
    ],

    // Redirect configuration
    'redirects' => [
        // Legacy redirects for old page names
        'index' => 'home',
        'main' => 'home',
        'homepage' => 'home',

        // Account page redirects to the new structure
        'account' => 'dashboard',
        'profile' => 'profile_edit',
        'my_profile' => 'profile_edit',
        'my_articles' => 'articles_manage',
        'my_account' => 'dashboard',

        // Admin redirects
        'admin' => [
            'target' => 'admin_manage_users',
            'status' => 301 // Permanent redirect
        ],
        'admin_panel' => [
            'target' => 'admin_manage_users',
            'status' => 301
        ],

        // External redirect example
        'old_blog' => [
            'target' => 'https://blog.darkheim.net',
            'status' => 301,
            'external' => true
        ],

        // Temporary redirect example
        'maintenance' => [
            'target' => 'home',
            'status' => 302
        ]
    ]
];