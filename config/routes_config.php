<?php

declare(strict_types=1);

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
        'user_profile' => ['file' => 'user/profile/index.php', 'title' => 'My Profile', 'auth' => true],
        'profile_edit' => ['file' => 'user/profile/profile_edit.php', 'title' => 'Edit Profile', 'auth' => true],
        'user_profile_settings' => ['file' => 'user/profile/settings.php', 'title' => 'Profile Settings', 'auth' => true],

        // User portfolio management
        'user_portfolio' => ['file' => 'user/portfolio/index.php', 'title' => 'My Portfolio', 'auth' => true],
        'portfolio_create' => ['file' => 'user/portfolio/add_project.php', 'title' => 'Create Project', 'auth' => true],
        'user_portfolio_edit' => ['file' => 'user/portfolio/edit_project.php', 'title' => 'Edit Project', 'auth' => true],
        'portfolio_settings' => ['file' => 'user/portfolio/project_settings.php', 'title' => 'Portfolio Settings', 'auth' => true],

        // User content management pages (restricted by middleware)
        'create_article' => ['file' => 'user/content/create_article.php', 'title' => 'Create Article', 'auth' => true],
        'edit_article' => ['file' => 'user/content/edit_article.php', 'title' => 'Edit Article', 'auth' => true],
        'manage_articles' => ['file' => 'user/content/manage_articles.php', 'title' => 'Manage Articles', 'auth' => true],

        // PHASE 8: Client Portal - Support Tickets
        'user_tickets' => ['file' => 'user/ticket/tickets.php', 'title' => 'Support Tickets', 'auth' => true],
        'user_tickets_create' => ['file' => 'user/ticket/tickets_create.php', 'title' => 'Create Ticket', 'auth' => true],
        'user_tickets_view' => ['file' => 'user/ticket/tickets_view.php', 'title' => 'View Ticket', 'auth' => true],

        // PHASE 8: Client Portal - Projects Management  
        'user_projects' => ['file' => 'user/projects/index.php', 'title' => 'My Projects', 'auth' => true],
        'user_projects_details' => ['file' => 'user/projects/details.php', 'title' => 'Project Details', 'auth' => true],
        'user_projects_timeline' => ['file' => 'user/projects/timeline.php', 'title' => 'Project Timeline', 'auth' => true],

        // PHASE 8: Client Portal - Invoices
        'user_invoices' => ['file' => 'user/invoices/index.php', 'title' => 'Invoices', 'auth' => true],
        'user_invoices_download' => ['file' => 'user/invoices/download.php', 'title' => 'Download Invoice', 'auth' => true],

        // PHASE 8: Client Portal - Documents
        'user_documents' => ['file' => 'user/documents/index.php', 'title' => 'Documents', 'auth' => true],
        'user_documents_download' => ['file' => 'user/documents/download.php', 'title' => 'Download Document', 'auth' => true],

        // PHASE 8: Client Portal - Meetings
        'user_meetings' => ['file' => 'user/meetings/index.php', 'title' => 'Meetings', 'auth' => true],
        'user_meetings_schedule' => ['file' => 'user/meetings/schedule.php', 'title' => 'Schedule Meeting', 'auth' => true],

        // Admin pages - updated paths after reorganization
        'admin_settings' => ['file' => 'admin/settings/site_settings.php', 'title' => 'Site Settings', 'auth' => true, 'admin' => true],
        'admin_users' => ['file' => 'admin/users/manage_users.php', 'title' => 'Manage Users', 'auth' => true, 'admin' => true],
        'edit_user' => ['file' => 'admin/users/edit_user.php', 'title' => 'Edit User', 'auth' => true, 'admin' => true],
        'manage_categories' => ['file' => 'admin/content/manage_categories.php', 'title' => 'Manage Categories', 'auth' => true, 'admin' => true],
        'edit_category' => ['file' => 'admin/content/edit_category.php', 'title' => 'Edit Category', 'auth' => true, 'admin' => true],
        'backup_monitor' => ['file' => 'admin/system/backup_monitor.php', 'title' => 'Database Backup Monitor', 'auth' => true, 'admin' => true],
        'system_monitor' => ['file' => 'admin/system/system_monitor.php', 'title' => 'System Monitor', 'auth' => true, 'admin' => true],

        // PHASE 7: Admin moderation interface pages
        'admin_moderation_dashboard' => ['file' => 'admin/moderation/dashboard.php', 'title' => 'Moderation Dashboard', 'auth' => true, 'admin' => true],
        'moderate_projects' => ['file' => 'admin/moderation/projects.php', 'title' => 'Projects Moderation', 'auth' => true, 'admin' => true],
        'moderation_project_details' => ['file' => 'admin/moderation/project_details.php', 'title' => 'Project Details', 'auth' => true, 'admin' => true],
        'moderate_comments' => ['file' => 'admin/moderation/comments.php', 'title' => 'Comments Moderation', 'auth' => true, 'admin' => true],

        // Public client profiles and community
        'public_client_profile' => ['file' => 'public/client/profile.php', 'title' => 'Client Profile'],
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

        // PHASE 8: Support Tickets API endpoints
        'api_tickets_create' => ['controller' => 'App\\Application\\Controllers\\TicketController', 'method' => 'create'],
        'api_tickets_add_message' => ['controller' => 'App\\Application\\Controllers\\TicketController', 'method' => 'addMessage'],
        'api_tickets_update_status' => ['controller' => 'App\\Application\\Controllers\\TicketController', 'method' => 'updateStatus'],
        'api_tickets_assign' => ['controller' => 'App\\Application\\Controllers\\TicketController', 'method' => 'assign'],
        'api_tickets_get_stats' => ['controller' => 'App\\Application\\Controllers\\TicketController', 'method' => 'getStats'],

        // System pages
        '404' => ['file' => 'system/404.php', 'title' => 'Page Not Found'],
        'error' => ['file' => 'system/error.php', 'title' => 'Error'],
        'maintenance' => ['file' => 'system/maintenance.php', 'title' => 'Maintenance Mode'],

        // Legacy compatibility routes
        'account_dashboard' => ['file' => 'user/dashboard.php', 'title' => 'My Dashboard', 'auth' => true],
        'account_edit_profile' => ['file' => 'user/profile/profile_edit.php', 'title' => 'Edit Profile', 'auth' => true],
        'account_settings' => ['file' => 'user/profile/settings.php', 'title' => 'Profile Settings', 'auth' => true],
        'site_settings' => ['file' => 'admin/settings/site_settings.php', 'title' => 'Site Settings', 'auth' => true, 'admin' => true],
        'manage_users' => ['file' => 'admin/users/manage_users.php', 'title' => 'Manage Users', 'auth' => true, 'admin' => true],
        'client_portfolio' => ['file' => 'user/portfolio/index.php', 'title' => 'My Portfolio', 'auth' => true],
    ],

    // Middleware configuration
    'middleware' => [
        'auth' => ['AdminOnlyMiddleware', 'RoleMiddleware', 'ClientAreaMiddleware'],
        'admin_only' => ['user/content/', 'admin/'],
        'client_area' => ['user/']
    ],

    // Route aliases for backward compatibility
    'aliases' => [
        'profile_edit' => 'user_profile',
        'profile_settings' => 'user_profile_settings',
        'articles_manage' => 'manage_articles',
        'article_create' => 'create_article',
        'article_edit' => 'edit_article',
        'portfolio_manage' => 'user_portfolio',
        'project_stats' => 'user_portfolio_settings',
        'client_tickets' => 'user_tickets',
        'ticket_create' => 'user_tickets_create',
        'ticket_view' => 'user_tickets_view',
        'client_projects' => 'user_projects',
        'project_details' => 'user_projects_details',
        'project_timeline' => 'user_projects_timeline',
        'client_invoices' => 'user_invoices',
        'invoice_download' => 'user_invoices_download',
        'client_documents' => 'user_documents'
    ]
];
