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

        // Client portfolio public pages
        'client_catalog' => ['file' => 'public/client/catalog.php', 'title' => 'Client Portfolio Catalog'],
        'public_client_portfolio' => ['file' => 'public/client/portfolio.php', 'title' => 'Client Portfolio'],
        'client_project' => ['file' => 'public/client/project.php', 'title' => 'Project Details'],

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

        // New Admin moderation pages
        'moderate_projects' => ['file' => 'admin/moderate_projects.php', 'title' => 'Moderate Projects', 'auth' => true, 'admin' => true],
        'moderate_comments' => ['file' => 'admin/moderate_comments.php', 'title' => 'Moderate Comments', 'auth' => true, 'admin' => true],
        'client_management' => ['file' => 'admin/client_management.php', 'title' => 'Client Management', 'auth' => true, 'admin' => true],
        'portfolio_settings' => ['file' => 'admin/portfolio_settings.php', 'title' => 'Portfolio Settings', 'auth' => true, 'admin' => true],

        // Client Portfolio pages
        'client_portfolio' => ['file' => 'user/portfolio/index.php', 'title' => 'My Portfolio', 'auth' => true],
        'portfolio_create' => ['file' => 'user/portfolio/add_project.php', 'title' => 'Add Project', 'auth' => true],
        'portfolio_edit' => ['file' => 'user/portfolio/edit_project.php', 'title' => 'Edit Project', 'auth' => true],
        'portfolio_manage' => ['file' => 'user/portfolio/my_projects.php', 'title' => 'Manage Projects', 'auth' => true],
        'project_stats' => ['file' => 'user/portfolio/project_stats.php', 'title' => 'Project Statistics', 'auth' => true],
        'project_settings' => ['file' => 'user/portfolio/project_settings.php', 'title' => 'Portfolio Settings', 'auth' => true],

        // Client Support/Tickets pages
        'client_tickets' => ['file' => 'user/tickets/index.php', 'title' => 'Support Tickets', 'auth' => true],
        'ticket_create' => ['file' => 'user/tickets/create.php', 'title' => 'Create Ticket', 'auth' => true],
        'ticket_view' => ['file' => 'user/tickets/view.php', 'title' => 'View Ticket', 'auth' => true],

        // Client Projects (studio projects for clients)
        'client_projects' => ['file' => 'user/projects/index.php', 'title' => 'My Projects', 'auth' => true],
        'project_details' => ['file' => 'user/projects/details.php', 'title' => 'Project Details', 'auth' => true],
        'project_timeline' => ['file' => 'user/projects/timeline.php', 'title' => 'Project Timeline', 'auth' => true],

        // Client Invoices and Documents
        'client_invoices' => ['file' => 'user/invoices/index.php', 'title' => 'Invoices', 'auth' => true],
        'invoice_download' => ['file' => 'user/invoices/download.php', 'title' => 'Download Invoice', 'auth' => true],
        'client_documents' => ['file' => 'user/documents/index.php', 'title' => 'Documents', 'auth' => true],

        // Public client profiles and community
        'public_client_profile' => ['file' => 'public/client/profile.php', 'title' => 'Client Profile'],
        'public_client_portfolio' => ['file' => 'public/client/portfolio.php', 'title' => 'Client Portfolio'],
        'community_projects' => ['file' => 'public/community/projects.php', 'title' => 'Community Projects'],

        // Portfolio API endpoints
        'api_portfolio_create' => ['controller' => 'App\\Application\\Controllers\\ClientPortfolioController', 'method' => 'create'],
        'api_portfolio_update' => ['controller' => 'App\\Application\\Controllers\\ClientPortfolioController', 'method' => 'update'],
        'api_portfolio_delete' => ['controller' => 'App\\Application\\Controllers\\ClientPortfolioController', 'method' => 'delete'],
        'api_portfolio_upload_images' => ['controller' => 'App\\Application\\Controllers\\ClientPortfolioController', 'method' => 'uploadImages'],
        'api_portfolio_toggle_visibility' => ['controller' => 'App\\Application\\Controllers\\ClientPortfolioController', 'method' => 'toggleVisibility'],

        // Client Profile API endpoints
        'api_client_profile_update' => ['controller' => 'App\\Application\\Controllers\\ClientProfileController', 'method' => 'updateProfile'],
        'api_client_skills_update' => ['controller' => 'App\\Application\\Controllers\\ClientProfileController', 'method' => 'updateSkills'],
        'api_client_social_links' => ['controller' => 'App\\Application\\Controllers\\ClientProfileController', 'method' => 'updateSocialLinks'],

        // Comment API endpoints
        'api_comment_create' => ['controller' => 'App\\Application\\Controllers\\CommentController', 'method' => 'create'],
        'api_comment_update' => ['controller' => 'App\\Application\\Controllers\\CommentController', 'method' => 'update'],
        'api_comment_delete' => ['controller' => 'App\\Application\\Controllers\\CommentController', 'method' => 'delete'],
        'api_comment_moderate' => ['controller' => 'App\\Application\\Controllers\\CommentController', 'method' => 'moderate'],
        'api_get_comment_thread' => ['controller' => 'App\\Application\\Controllers\\CommentController', 'method' => 'getThread'],

        // Notification API endpoints
        'api_notifications_unread' => ['controller' => 'App\\Application\\Controllers\\NotificationController', 'method' => 'getUnread'],
        'api_notifications_mark_read' => ['controller' => 'App\\Application\\Controllers\\NotificationController', 'method' => 'markRead'],
        'api_notification_preferences' => ['controller' => 'App\\Application\\Controllers\\NotificationController', 'method' => 'updatePreferences'],

        // System pages
        '404' => ['file' => 'system/404.php', 'title' => 'Page Not Found'],
        'error' => ['file' => 'system/error.php', 'title' => 'Error'],
        'maintenance' => ['file' => 'system/maintenance.php', 'title' => 'Maintenance Mode'],


    ],

    // Middleware configuration
    'middleware' => [
        // Global middleware (applied to all routes)
        '*' => [
            // Add global middleware classes here if needed
        ],

        // Client area routes - require client or higher role
        'dashboard' => ['App\\Application\\Middleware\\ClientAreaMiddleware'],
        'account_dashboard' => ['App\\Application\\Middleware\\ClientAreaMiddleware'],
        'profile_edit' => ['App\\Application\\Middleware\\ClientAreaMiddleware'],
        'account_edit_profile' => ['App\\Application\\Middleware\\ClientAreaMiddleware'],
        'profile_settings' => ['App\\Application\\Middleware\\ClientAreaMiddleware'],
        'account_settings' => ['App\\Application\\Middleware\\ClientAreaMiddleware'],

        // Content management - require employee or admin (remove for regular clients)
        'articles_manage' => ['App\\Application\\Middleware\\RoleMiddleware'],
        'manage_articles' => ['App\\Application\\Middleware\\RoleMiddleware'],
        'article_create' => ['App\\Application\\Middleware\\RoleMiddleware'],
        'create_article' => ['App\\Application\\Middleware\\RoleMiddleware'],
        'article_edit' => ['App\\Application\\Middleware\\RoleMiddleware'],
        'edit_article' => ['App\\Application\\Middleware\\RoleMiddleware'],
        'article_delete' => ['App\\Application\\Middleware\\RoleMiddleware'],
        'delete_article' => ['App\\Application\\Middleware\\RoleMiddleware'],

        // Admin-only routes
        'manage_users' => ['App\\Application\\Middleware\\AdminOnlyMiddleware'],
        'edit_user' => ['App\\Application\\Middleware\\AdminOnlyMiddleware'],
        'manage_categories' => ['App\\Application\\Middleware\\AdminOnlyMiddleware'],
        'edit_category' => ['App\\Application\\Middleware\\AdminOnlyMiddleware'],
        'site_settings' => ['App\\Application\\Middleware\\AdminOnlyMiddleware'],
        'backup_monitor' => ['App\\Application\\Middleware\\AdminOnlyMiddleware'],
        'system_monitor' => ['App\\Application\\Middleware\\AdminOnlyMiddleware'],

        // New client portfolio routes
        'client_portfolio' => ['App\\Application\\Middleware\\ClientAreaMiddleware'],
        'portfolio_create' => ['App\\Application\\Middleware\\ClientAreaMiddleware'],
        'portfolio_edit' => ['App\\Application\\Middleware\\ClientAreaMiddleware'],
        'portfolio_manage' => ['App\\Application\\Middleware\\ClientAreaMiddleware'],
        'project_stats' => ['App\\Application\\Middleware\\ClientAreaMiddleware'],

        // Client support routes
        'client_tickets' => ['App\\Application\\Middleware\\ClientAreaMiddleware'],
        'ticket_create' => ['App\\Application\\Middleware\\ClientAreaMiddleware'],
        'ticket_view' => ['App\\Application\\Middleware\\ClientAreaMiddleware'],

        // Project moderation (employee/admin only)
        'moderate_projects' => ['App\\Application\\Middleware\\RoleMiddleware'],
        'moderate_comments' => ['App\\Application\\Middleware\\RoleMiddleware'],
        'client_management' => ['App\\Application\\Middleware\\AdminOnlyMiddleware'],

        // Additional client area routes
        'client_projects' => ['App\\Application\\Middleware\\ClientAreaMiddleware'],
        'project_details' => ['App\\Application\\Middleware\\ClientAreaMiddleware'],
        'project_timeline' => ['App\\Application\\Middleware\\ClientAreaMiddleware'],
        'project_settings' => ['App\\Application\\Middleware\\ClientAreaMiddleware'],
        'client_invoices' => ['App\\Application\\Middleware\\ClientAreaMiddleware'],
        'invoice_download' => ['App\\Application\\Middleware\\ClientAreaMiddleware'],
        'client_documents' => ['App\\Application\\Middleware\\ClientAreaMiddleware'],

        // Admin moderation pages
        'portfolio_settings' => ['App\\Application\\Middleware\\AdminOnlyMiddleware'],

        // API endpoints middleware
        'api_portfolio_create' => ['App\\Application\\Middleware\\ClientAreaMiddleware'],
        'api_portfolio_update' => ['App\\Application\\Middleware\\ClientAreaMiddleware'],
        'api_portfolio_delete' => ['App\\Application\\Middleware\\ClientAreaMiddleware'],
        'api_portfolio_upload_images' => ['App\\Application\\Middleware\\ClientAreaMiddleware'],
        'api_portfolio_toggle_visibility' => ['App\\Application\\Middleware\\ClientAreaMiddleware'],
        'api_client_profile_update' => ['App\\Application\\Middleware\\ClientAreaMiddleware'],
        'api_client_skills_update' => ['App\\Application\\Middleware\\ClientAreaMiddleware'],
        'api_client_social_links' => ['App\\Application\\Middleware\\ClientAreaMiddleware'],
        'api_comment_create' => ['App\\Application\\Middleware\\ClientAreaMiddleware'],
        'api_comment_update' => ['App\\Application\\Middleware\\ClientAreaMiddleware'],
        'api_comment_delete' => ['App\\Application\\Middleware\\ClientAreaMiddleware'],
        'api_comment_moderate' => ['App\\Application\\Middleware\\RoleMiddleware'],
        'api_get_comment_thread' => [], // Public access
        'api_notifications_unread' => ['App\\Application\\Middleware\\ClientAreaMiddleware'],
        'api_notifications_mark_read' => ['App\\Application\\Middleware\\ClientAreaMiddleware'],
        'api_notification_preferences' => ['App\\Application\\Middleware\\ClientAreaMiddleware'],
    ],

    // Role requirements for RoleMiddleware routes
    'role_requirements' => [
        'articles_manage' => ['admin', 'employee'],
        'manage_articles' => ['admin', 'employee'],
        'article_create' => ['admin', 'employee'],
        'create_article' => ['admin', 'employee'],
        'article_edit' => ['admin', 'employee'],
        'edit_article' => ['admin', 'employee'],
        'article_delete' => ['admin', 'employee'],
        'delete_article' => ['admin', 'employee'],
        'moderate_projects' => ['admin', 'employee'],
        'moderate_comments' => ['admin', 'employee'],
        'api_comment_moderate' => ['admin', 'employee'],
    ],

    // Permission requirements for specific actions
    'permission_requirements' => [
        'articles_manage' => ['resource' => 'content', 'action' => 'edit'],
        'article_create' => ['resource' => 'content', 'action' => 'create'],
        'article_edit' => ['resource' => 'content', 'action' => 'edit'],
        'article_delete' => ['resource' => 'content', 'action' => 'delete'],
        'moderate_projects' => ['resource' => 'portfolio', 'action' => 'moderate'],
        'moderate_comments' => ['resource' => 'comments', 'action' => 'moderate'],
        'manage_users' => ['resource' => 'users', 'action' => 'edit'],
        'site_settings' => ['resource' => 'settings', 'action' => 'edit'],
        'backup_monitor' => ['resource' => 'backups', 'action' => 'view'],
    ],

    ];
