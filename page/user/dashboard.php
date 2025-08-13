<?php

/**
 * Dashboard Page
 *
 * This page displays the user's dashboard with a variety of features and statistics.
 * It includes a personalized welcome message, a list of recent activities, and a
 * dashboard overview with key metrics.
 *
 * @author Dmytro Hovenko
 */

use App\Application\Controllers\ProfileController;
use App\Application\Helpers\NavigationHelper;
use App\Domain\Interfaces\LoggerInterface;
use App\Domain\Interfaces\TokenManagerInterface;
use App\Infrastructure\Components\DashboardBackupStatus;

// Use global services from the new DI architecture
global $flashMessageService, $database_handler, $container, $serviceProvider;

// Get AuthenticationService instead of direct SessionManager access
try {
    $authService = $serviceProvider->getAuth();
} catch (Exception $e) {
    error_log("Critical: Failed to get AuthenticationService instance: " . $e->getMessage());
    die("A critical system error occurred. Please try again later.");
}

// Check authentication via AuthenticationService
if (!$authService->isAuthenticated()) {
    $flashMessageService->addError('Please log in to access your dashboard.');
    header("Location: /index.php?page=login");
    exit();
}

// Get user data via AuthenticationService
$current_user_id = $authService->getCurrentUserId();
$current_user_role = $authService->getCurrentUserRole();
$current_username = $authService->getCurrentUsername();
$userData_from_auth = $authService->getCurrentUser();

// Check for required services
if (!isset($flashMessageService)) {
    error_log("Critical: FlashMessageService not available in dashboard.php");
    die("A critical system error occurred. Please try again later.");
}

if (!isset($database_handler)) {
    error_log("Critical: Database handler not available in dashboard.php");
    die("A critical system error occurred. Please try again later.");
}

if (!isset($container)) {
    error_log("Critical: Container not available in dashboard.php");
    die("A critical system error occurred. Please try again later.");
}

$userId = $current_user_id;

// Create ProfileController
try {
    $profileController = new ProfileController(
        $database_handler,
        $userId,
        $flashMessageService,
        $container->make(TokenManagerInterface::class)
    );
} catch (Exception $e) {
    error_log("Critical: Failed to create ProfileController in dashboard.php: " . $e->getMessage());
    die("A critical system error occurred. Please try again later.");
}

$userData = $profileController->getCurrentUserData();

// Common functions for all profile pages
// Function to calculate account completeness progress
function calculateAccountCompleteness($userData): array
{
    $fields = [
        'email' => !empty($userData['email']) && $userData['email'] !== 'N/A' ? 1 : 0,
        'location' => !empty($userData['location']) ? 1 : 0,
        'bio' => !empty($userData['bio']) ? 1 : 0,
        'user_status' => !empty($userData['user_status']) ? 1 : 0,
        'website_url' => !empty($userData['website_url']) ? 1 : 0
    ];

    $completed = array_sum($fields);
    $total = count($fields);
    $percentage = round(($completed / $total) * 100);

    return [
        'percentage' => $percentage,
        'completed' => $completed,
        'total' => $total,
        'missing_fields' => array_keys(array_filter($fields, function ($v) {
            return $v === 0;
        }))
    ];
}

// Function to get extended user statistics (for dashboard)
function getUserStatsExtended($database_handler, $userId): array
{
    $stats = [
        'articles' => ['count' => 0, 'recent' => 0],
        'comments' => ['count' => 0, 'recent' => 0],
        'notifications' => ['count' => 0, 'unread' => 0],
        'drafts' => 0,
        'profile_views' => 0,
        'last_login' => 'Active now',
        'member_since' => 'This year'
    ];

    if ($database_handler && $pdo = $database_handler->getConnection()) {
        try {
            // Article statistics
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM articles WHERE user_id = ?");
            $stmt->execute([$userId]);
            $stats['articles']['count'] = (int)$stmt->fetchColumn();

            // Articles in the last 7 days
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM articles WHERE user_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
            $stmt->execute([$userId]);
            $stats['articles']['recent'] = (int)$stmt->fetchColumn();

            // Drafts
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM articles WHERE user_id = ? AND status = 'draft'");
            $stmt->execute([$userId]);
            $stats['drafts'] = (int)$stmt->fetchColumn();

            // Comments
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM comments WHERE user_id = ?");
            $stmt->execute([$userId]);
            $stats['comments']['count'] = (int)$stmt->fetchColumn();

            // Comments in the last 7 days
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM comments WHERE user_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
            $stmt->execute([$userId]);
            $stats['comments']['recent'] = (int)$stmt->fetchColumn();

            // Notifications
            $table_exists_stmt = $pdo->query("SHOW TABLES LIKE 'notifications'");
            if ($table_exists_stmt && $table_exists_stmt->rowCount() > 0) {
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ?");
                $stmt->execute([$userId]);
                $stats['notifications']['count'] = (int)$stmt->fetchColumn();

                $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
                $stmt->execute([$userId]);
                $stats['notifications']['unread'] = (int)$stmt->fetchColumn();
            }
        } catch (PDOException $e) {
            error_log("Dashboard Stats Error for user ID $userId: " . $e->getMessage());
        }
    }

    return $stats;
}

// Use new common functions
$accountProgress = calculateAccountCompleteness($userData);
$user_stats = getUserStatsExtended($database_handler, $userId);

$recent_activities = [];

if ($database_handler && $pdo = $database_handler->getConnection()) {
    try {
        // Recent activities
        $stmt = $pdo->prepare("
            SELECT 'article' as type, title as name, created_at, id 
            FROM articles WHERE user_id = ? 
            UNION ALL
            SELECT 'comment' as type, CONCAT('Comment on: ', a.title) as name, c.created_at, c.article_id as id
            FROM comments c 
            JOIN articles a ON c.article_id = a.id 
            WHERE c.user_id = ?
            ORDER BY created_at DESC 
            LIMIT 5
        ");
        $stmt->execute([$userId, $userId]);
        $recent_activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Dashboard Activities Error for user ID $userId: " . $e->getMessage());
    }
}

if (!$userData) {
    $userData = [
        'username' => $current_username ?? 'User',
        'email' => 'N/A',
        'location' => 'Not set',
        'user_status' => 'Not set',
        'bio' => '',
        'website_url' => ''
    ];
}

// Smart quick actions based on user state and role
$smart_suggestions = [];

// Role-based dashboard sections
$roleDashboardData = [];

// Get role-specific data and suggestions based on user role
switch ($current_user_role) {
    case 'admin':
    case 'employee':
        // Administrative features
        try {
            // Get moderation statistics
            $moderationService = new \App\Application\Services\ModerationService($database_handler, $serviceProvider->getLogger());
            $moderationStats = $moderationService->getDashboardStatistics();
            $recentProjects = $moderationService->getRecentProjectsForModeration(3);

            // Get recent comments for moderation
            $commentsModel = new \App\Domain\Models\Comments($database_handler);
            $recentComments = $commentsModel->getPendingComments(3);

            $roleDashboardData = [
                'moderation_stats' => $moderationStats,
                'recent_projects' => $recentProjects,
                'recent_comments' => $recentComments,
                'is_moderator' => true
            ];

            // Add admin-specific suggestions
            if ($moderationStats['pending_projects'] > 0) {
                $smart_suggestions[] = [
                    'type' => 'moderation',
                    'title' => 'Projects Awaiting Moderation',
                    'description' => $moderationStats['pending_projects'] . ' project(s) need your review',
                    'action' => 'Review Projects',
                    'url' => '/index.php?page=admin_moderation_projects&status=pending',
                    'icon' => 'fas fa-tasks',
                    'priority' => 'high'
                ];
            }

            if ($moderationStats['pending_comments'] > 0) {
                $smart_suggestions[] = [
                    'type' => 'moderation',
                    'title' => 'Comments Awaiting Moderation',
                    'description' => $moderationStats['pending_comments'] . ' comment(s) need your review',
                    'action' => 'Review Comments',
                    'url' => '/index.php?page=admin_moderation_comments&status=pending',
                    'icon' => 'fas fa-comments',
                    'priority' => 'medium'
                ];
            }
        } catch (Exception $e) {
            error_log("Dashboard moderation data error: " . $e->getMessage());
            $roleDashboardData = ['is_moderator' => true, 'error' => 'Failed to load moderation data'];
        }
        break;

    case 'client':
        // Client-specific features
        try {
            // Get client portfolio data
            $clientPortfolioController = new \App\Application\Controllers\ClientPortfolioController(
                $database_handler, $serviceProvider->getAuth(), $serviceProvider->getFlashMessage(), $serviceProvider->getLogger()
            );

            $portfolioStats = $clientPortfolioController->getDashboardStats();

            $roleDashboardData = [
                'portfolio_stats' => $portfolioStats,
                'is_client' => true
            ];

            // Add client-specific suggestions
            if (($portfolioStats['total_projects'] ?? 0) === 0) {
                $smart_suggestions[] = [
                    'type' => 'portfolio',
                    'title' => 'Create Your First Project',
                    'description' => 'Start building your portfolio by adding your first project',
                    'action' => 'Add Project',
                    'url' => '/index.php?page=portfolio_create',
                    'icon' => 'fas fa-plus-circle',
                    'priority' => 'high'
                ];
            }

            if (($portfolioStats['pending_projects'] ?? 0) > 0) {
                $smart_suggestions[] = [
                    'type' => 'portfolio',
                    'title' => 'Projects Under Review',
                    'description' => $portfolioStats['pending_projects'] . ' project(s) are being reviewed',
                    'action' => 'View Projects',
                    'url' => '/index.php?page=portfolio_manage',
                    'icon' => 'fas fa-clock',
                    'priority' => 'medium'
                ];
            }
        } catch (Exception $e) {
            error_log("Dashboard client data error: " . $e->getMessage());
            $roleDashboardData = ['is_client' => true, 'error' => 'Failed to load portfolio data'];
        }
        break;

    default:
        // Guest or other roles
        $roleDashboardData = ['is_guest' => true];
        break;
}

// Primary actions - reorganized
$primary_actions = [
    [
        'url' => '/index.php?page=create_article',
        'text' => 'Create Article',
        'description' => 'Write and publish new content',
        'icon' => 'fas fa-pen-fancy',
        'roles' => ['user', 'editor', 'admin'],
        'type' => 'create'
    ],
    [
        'url' => '/index.php?page=manage_articles',
        'text' => 'My Content',
        'description' => 'Manage your articles and drafts',
        'icon' => 'fas fa-folder-open',
        'roles' => ['user', 'editor', 'admin'],
        'type' => 'manage'
    ]
];

// Quick links
$quick_links = [
    [
        'url' => '/index.php?page=news',
        'text' => 'Browse Articles',
        'icon' => 'fas fa-newspaper'
    ],
    [
        'url' => '/index.php?page=profile_settings',
        'text' => 'Settings',
        'icon' => 'fas fa-cog'
    ]
];

$profile_actions = [
    [
        'url' => '/index.php?page=profile_edit',
        'text' => 'Edit Profile',
        'description' => 'Update information and bio',
        'icon' => 'fas fa-user-edit',
        'roles' => ['user', 'editor', 'admin']
    ],
    [
        'url' => '/index.php?page=profile_settings',
        'text' => 'Account Settings',
        'description' => 'Security and preferences',
        'icon' => 'fas fa-shield-alt',
        'roles' => ['user', 'editor', 'admin']
    ]
];

$site_management_config = [];

// Use the new NavigationHelper to get admin functions
if ($current_user_role === 'admin' || $current_user_role === 'editor') {
    $adminNavigation = NavigationHelper::getAdminNavigation();

    $site_management_config = [
        'title' => 'Site Management',
        'links' => []
    ];

    // Convert navigation to the format expected by the dashboard
    foreach ($adminNavigation as $item) {
        // By default, only for administrators

        // Determine roles for different functions
        $roles = match ($item['key']) {
            'manage_categories', 'admin_dashboard' => ['admin', 'editor'],
            default => ['admin'],
        };

        $site_management_config['links'][] = [
            'url' => $item['url'],
            'text' => $item['text'],
            'description' => 'Manage ' . strtolower($item['text']),
            'roles' => $roles
        ];
    }
}

function can_user_access_action(array $action_roles, string $current_user_role): bool
{
    if (empty($action_roles)) {
        return true;
    }
    return in_array($current_user_role, $action_roles);
}

function time_ago($datetime): string
{
    $time = time() - strtotime($datetime);
    if ($time < 60) {
        return 'just now';
    }
    if ($time < 3600) {
        return floor($time / 60) . 'm ago';
    }
    if ($time < 86400) {
        return floor($time / 3600) . 'h ago';
    }
    if ($time < 2592000) {
        return floor($time / 86400) . 'd ago';
    }
    return date('M j', strtotime($datetime));
}

?>

<div class="page-dashboard">
    <!-- Enhanced Header with Smart Actions -->
    <header class="page-header">
        <div class="dashboard-header-content">
            <div class="dashboard-header-main">
                <h1 class="page-title dashboard-header">
                    <?php
                    $greeting = date('H') < 12 ? 'Good morning' : (date('H') < 18 ? 'Good afternoon' : 'Good evening');
                    echo $greeting . ', ' . htmlspecialchars($userData['username'] ?? 'User') . '!';
                    ?>
                </h1>
                <?php if (!empty($userData['user_status'])) : ?>
                    <p class="dashboard-user-status"><?php echo htmlspecialchars($userData['user_status']); ?></p>
                <?php endif; ?>
            </div>
            <div class="dashboard-header-quick">
                <?php if ($user_stats['drafts'] > 0) : ?>
                    <a href="/index.php?page=manage_articles&filter=drafts" class="button button-secondary">
                        <i class="fas fa-edit"></i> <?php echo $user_stats['drafts']; ?> Draft<?php echo $user_stats['drafts'] > 1 ? 's' : ''; ?>
                    </a>
                <?php endif; ?>
                <a href="/index.php?page=create_article" class="button button-primary">
                    <i class="fas fa-plus"></i> New Article
                </a>
            </div>
        </div>
        <p class="dashboard-intro">
            <?php if ($user_stats['articles']['count'] === 0) : ?>
                Welcome to your dashboard! Ready to share your first article with the world?
            <?php else : ?>
                Manage your content, track your progress, and explore new features.
            <?php endif; ?>
        </p>
    </header>

    <!-- Enhanced Stats Overview -->
    <section class="dashboard-overview-section">
        <div class="dashboard-overview">
            <div class="overview-card overview-card-articles">
                <div class="overview-card-header">
                    <span class="overview-card-value"><?php echo $user_stats['articles']['count']; ?></span>
                    <span class="overview-card-label">Articles Published</span>
                </div>
                <div class="overview-card-footer">
                    <?php if ($user_stats['articles']['recent'] > 0) : ?>
                        <span class="overview-card-trend positive">+<?php echo $user_stats['articles']['recent']; ?> this week</span>
                    <?php endif; ?>
                    <a href="/index.php?page=manage_articles" class="overview-card-action">Manage</a>
                </div>
            </div>

            <div class="overview-card overview-card-drafts">
                <div class="overview-card-header">
                    <span class="overview-card-value"><?php echo $user_stats['drafts']; ?></span>
                    <span class="overview-card-label">Drafts</span>
                </div>
                <div class="overview-card-footer">
                    <?php if ($user_stats['drafts'] > 0) : ?>
                        <a href="/index.php?page=manage_articles&filter=drafts" class="overview-card-action">Finish</a>
                    <?php else : ?>
                        <span class="overview-card-trend neutral">All published!</span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="overview-card overview-card-comments">
                <div class="overview-card-header">
                    <span class="overview-card-value"><?php echo $user_stats['comments']['count']; ?></span>
                    <span class="overview-card-label">Comments Made</span>
                </div>
                <div class="overview-card-footer">
                    <?php if ($user_stats['comments']['recent'] > 0) : ?>
                        <span class="overview-card-trend positive">+<?php echo $user_stats['comments']['recent']; ?> this week</span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="overview-card overview-card-notifications">
                <div class="overview-card-header">
                    <span class="overview-card-value"><?php echo $user_stats['notifications']['unread']; ?></span>
                    <span class="overview-card-label">Notifications</span>
                </div>
                <div class="overview-card-footer">
                    <?php if ($user_stats['notifications']['unread'] > 0) : ?>
                        <span class="overview-card-badge">New</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Smart Suggestions -->
    <?php if (!empty($smart_suggestions)) : ?>
    <section class="dashboard-suggestions-section">
        <h2 class="dashboard-section-title">
            <i class="fas fa-lightbulb"></i> Suggestions for You
        </h2>
        <div class="dashboard-suggestions">
            <?php foreach (array_slice($smart_suggestions, 0, 2) as $suggestion) : ?>
            <div class="suggestion-card suggestion-<?php echo $suggestion['priority']; ?>">
                <a href="<?php echo htmlspecialchars($suggestion['url']); ?>">
                    <div class="suggestion-content">
                        <div class="suggestion-icon">
                            <i class="<?php echo $suggestion['icon']; ?>"></i>
                        </div>
                        <div class="suggestion-text-content">
                            <span class="suggestion-title"><?php echo htmlspecialchars($suggestion['title']); ?></span>
                            <span class="suggestion-description"><?php echo htmlspecialchars($suggestion['description']); ?></span>
                        </div>
                    </div>
                    <div class="suggestion-action">
                        <span class="suggestion-action-text"><?php echo htmlspecialchars($suggestion['action']); ?></span>
                        <i class="fas fa-arrow-right suggestion-arrow"></i>
                    </div>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- Role-specific Dashboard Sections -->
    <?php if (!empty($roleDashboardData['is_moderator']) && !isset($roleDashboardData['error'])) : ?>
    <section class="dashboard-moderation-section">
        <h2 class="dashboard-section-title">
            <i class="fas fa-gavel"></i> Moderation Dashboard
            <a href="/index.php?page=admin_moderation_dashboard" class="section-link">View Full Dashboard</a>
        </h2>

        <div class="moderation-stats-grid">
            <div class="moderation-stat-card">
                <div class="stat-value"><?php echo $roleDashboardData['moderation_stats']['pending_projects'] ?? 0; ?></div>
                <div class="stat-label">Pending Projects</div>
                <a href="/index.php?page=admin_moderation_projects&status=pending" class="stat-action">Review</a>
            </div>
            <div class="moderation-stat-card">
                <div class="stat-value"><?php echo $roleDashboardData['moderation_stats']['pending_comments'] ?? 0; ?></div>
                <div class="stat-label">Pending Comments</div>
                <a href="/index.php?page=admin_moderation_comments&status=pending" class="stat-action">Review</a>
            </div>
            <div class="moderation-stat-card">
                <div class="stat-value"><?php echo $roleDashboardData['moderation_stats']['moderated_today'] ?? 0; ?></div>
                <div class="stat-label">Moderated Today</div>
            </div>
            <div class="moderation-stat-card">
                <div class="stat-value"><?php echo $roleDashboardData['moderation_stats']['total_published'] ?? 0; ?></div>
                <div class="stat-label">Total Published</div>
            </div>
        </div>

        <?php if (!empty($roleDashboardData['recent_projects'])) : ?>
        <div class="recent-moderation-items">
            <h4>Recent Projects for Review</h4>
            <div class="moderation-items-list">
                <?php foreach ($roleDashboardData['recent_projects'] as $project) : ?>
                <div class="moderation-item">
                    <div class="moderation-item-content">
                        <span class="moderation-item-title"><?php echo htmlspecialchars($project['title']); ?></span>
                        <span class="moderation-item-meta">
                            by <?php echo htmlspecialchars($project['client_username'] ?? 'Unknown'); ?> •
                            <?php echo time_ago($project['created_at']); ?>
                        </span>
                    </div>
                    <a href="/index.php?page=admin_moderation_project_details&id=<?php echo $project['id']; ?>"
                       class="moderation-item-action">Review</a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </section>
    <?php elseif (!empty($roleDashboardData['is_client']) && !isset($roleDashboardData['error'])) : ?>
    <section class="dashboard-portfolio-section">
        <h2 class="dashboard-section-title">
            <i class="fas fa-briefcase"></i> My Portfolio
            <a href="/index.php?page=client_portfolio" class="section-link">Manage Portfolio</a>
        </h2>

        <div class="portfolio-stats-grid">
            <div class="portfolio-stat-card">
                <div class="stat-value"><?php echo $roleDashboardData['portfolio_stats']['total_projects'] ?? 0; ?></div>
                <div class="stat-label">Total Projects</div>
                <a href="/index.php?page=portfolio_manage" class="stat-action">Manage</a>
            </div>
            <div class="portfolio-stat-card">
                <div class="stat-value"><?php echo $roleDashboardData['portfolio_stats']['pending_projects'] ?? 0; ?></div>
                <div class="stat-label">Under Review</div>
            </div>
            <div class="portfolio-stat-card">
                <div class="stat-value"><?php echo $roleDashboardData['portfolio_stats']['published_projects'] ?? 0; ?></div>
                <div class="stat-label">Published</div>
            </div>
            <div class="portfolio-stat-card">
                <div class="stat-value"><?php echo $roleDashboardData['portfolio_stats']['total_views'] ?? 0; ?></div>
                <div class="stat-label">Total Views</div>
            </div>
        </div>

        <div class="portfolio-quick-actions">
            <a href="/index.php?page=portfolio_create" class="portfolio-action-primary">
                <i class="fas fa-plus"></i> Add New Project
            </a>
            <a href="/index.php?page=portfolio_manage" class="portfolio-action-secondary">
                <i class="fas fa-edit"></i> Manage Projects
            </a>
            <a href="/index.php?page=project_stats" class="portfolio-action-secondary">
                <i class="fas fa-chart-line"></i> View Statistics
            </a>
        </div>
    </section>
    <?php endif; ?>

    <!-- Enhanced Main Dashboard Grid -->
    <div class="dashboard-main-grid">
        <!-- Left Column: Primary Actions & Activity -->
        <div class="dashboard-primary-column">
            <!-- Quick Actions -->
            <section class="dashboard-content-section">
                <h2 class="dashboard-section-title">Quick Actions</h2>
                <div class="dashboard-actions dashboard-actions-compact">
                    <?php foreach ($primary_actions as $action) : ?>
                        <?php if (can_user_access_action($action['roles'], $current_user_role)) : ?>
                        <div class="dashboard-action-card dashboard-action-primary action-<?php echo $action['type']; ?>">
                            <a href="<?php echo htmlspecialchars($action['url']); ?>">
                                <div class="action-icon">
                                    <i class="<?php echo $action['icon']; ?>"></i>
                                </div>
                                <div class="action-content">
                                    <span class="action-text"><?php echo htmlspecialchars($action['text']); ?></span>
                                    <span class="action-description"><?php echo htmlspecialchars($action['description']); ?></span>
                                </div>
                            </a>
                        </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </section>

            <!-- Recent Activity -->
            <?php if (!empty($recent_activities)) : ?>
            <section class="dashboard-activity-section">
                <h2 class="dashboard-section-title">Recent Activity</h2>
                <div class="activity-list">
                    <?php foreach ($recent_activities as $activity) : ?>
                    <div class="activity-item activity-<?php echo $activity['type']; ?>">
                        <div class="activity-icon">
                            <i class="fas fa-<?php echo $activity['type'] === 'article' ? 'file-alt' : 'comment'; ?>"></i>
                        </div>
                        <div class="activity-content">
                            <span class="activity-name"><?php echo htmlspecialchars($activity['name']); ?></span>
                            <span class="activity-time"><?php echo time_ago($activity['created_at']); ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endif; ?>

            <!-- Site Management for Admins -->
            <?php
            if (!empty($site_management_config['links'])) :
                $has_accessible_admin_links = false;
                foreach ($site_management_config['links'] as $link) {
                    if (can_user_access_action($link['roles'], $current_user_role)) {
                        $has_accessible_admin_links = true;
                        break;
                    }
                }

                if ($has_accessible_admin_links) :
                    ?>
            <section class="dashboard-admin-section">
                <h2 class="dashboard-section-title">
                    <i class="fas fa-cogs"></i> <?php echo htmlspecialchars($site_management_config['title']); ?>
                </h2>
                <div class="dashboard-actions dashboard-actions-compact">
                    <?php foreach (array_slice($site_management_config['links'], 0, 6) as $action) : ?>
                        <?php if (can_user_access_action($action['roles'], $current_user_role)) : ?>
                        <div class="dashboard-action-card dashboard-action-admin">
                            <a href="<?php echo htmlspecialchars($action['url']); ?>">
                                <div class="action-content">
                                    <span class="action-text"><?php echo htmlspecialchars($action['text']); ?></span>
                                    <span class="action-description"><?php echo htmlspecialchars($action['description'] ?? ''); ?></span>
                                </div>
                            </a>
                        </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </section>
                <?php endif;
            endif; ?>
        </div>

        <!-- Right Column: Profile & Quick Links -->
        <div class="dashboard-secondary-column">
            <!-- Enhanced Profile Snapshot -->
            <section class="dashboard-profile-snapshot dashboard-profile-compact">
                <div class="profile-header">
                    <h3 class="dashboard-section-title">Profile</h3>
                    <div class="profile-completion">
                        <!-- Используем данные из общей функции calculateAccountCompleteness -->
                        <div class="completion-bar">
                            <div class="completion-fill" style="width: <?php echo $accountProgress['percentage']; ?>%"></div>
                        </div>
                        <span class="completion-text"><?php echo $accountProgress['percentage']; ?>% complete</span>
                    </div>
                </div>

                <div class="profile-summary-grid">
                    <div class="profile-summary-item">
                        <span class="profile-summary-label">Email</span>
                        <span class="profile-summary-value"><?php echo htmlspecialchars($userData['email'] ?? 'N/A'); ?></span>
                    </div>
                    <?php if (!empty($userData['location'])) : ?>
                    <div class="profile-summary-item">
                        <span class="profile-summary-label">Location</span>
                        <span class="profile-summary-value"><?php echo htmlspecialchars($userData['location']); ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($userData['website_url'])) : ?>
                    <div class="profile-summary-item">
                        <span class="profile-summary-label">Website</span>
                        <span class="profile-summary-value">
                            <a href="<?php echo htmlspecialchars($userData['website_url']); ?>" target="_blank" rel="noopener noreferrer">
                                <?php echo htmlspecialchars(parse_url($userData['website_url'], PHP_URL_HOST) ?: $userData['website_url']); ?>
                            </a>
                        </span>
                    </div>
                    <?php endif; ?>
                </div>

                <?php if (!empty($userData['bio'])) : ?>
                <div class="profile-bio-summary">
                    <span class="profile-summary-label">About</span>
                    <p class="profile-bio-text"><?php echo nl2br(htmlspecialchars(mb_substr($userData['bio'], 0, 120) . (mb_strlen($userData['bio']) > 120 ? '...' : ''))); ?></p>
                </div>
                <?php endif; ?>

                <!-- Обновленные действия для завершения профиля -->
                <?php if ($accountProgress['percentage'] < 100) : ?>
                <div class="profile-completion-actions">
                    <a href="/index.php?page=profile_edit" class="completion-link">
                        <i class="fas fa-user-edit"></i>
                        Complete Profile
                        <small>Missing: <?php echo implode(', ', array_map('ucfirst', $accountProgress['missing_fields'])); ?></small>
                    </a>
                </div>
                <?php endif; ?>
            </section>

            <!-- Quick Links -->
            <section class="dashboard-quick-links">
                <h3 class="dashboard-section-title">Quick Links</h3>
                <div class="quick-links-grid">
                    <?php foreach ($quick_links as $link) : ?>
                    <a href="<?php echo htmlspecialchars($link['url']); ?>" class="quick-link">
                        <i class="<?php echo $link['icon']; ?>"></i>
                        <span><?php echo htmlspecialchars($link['text']); ?></span>
                    </a>
                    <?php endforeach; ?>
                </div>
            </section>

            <!-- Account Actions -->
            <section class="dashboard-account-section">
                <h3 class="dashboard-section-title">Account</h3>
                <div class="dashboard-actions dashboard-actions-vertical">
                    <?php foreach ($profile_actions as $action) : ?>
                        <?php if (can_user_access_action($action['roles'], $current_user_role)) : ?>
                        <div class="dashboard-action-card dashboard-action-account">
                            <a href="<?php echo htmlspecialchars($action['url']); ?>">
                                <div class="action-content">
                                    <span class="action-text"><?php echo htmlspecialchars($action['text']); ?></span>
                                    <span class="action-description"><?php echo htmlspecialchars($action['description']); ?></span>
                                </div>
                            </a>
                        </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </section>

            <!-- Backup System Status for Admins -->
            <?php if ($current_user_role === 'admin') : ?>
            <section class="dashboard-backup-section">
                <?php
                try {
                    // Создаем экземпляр DashboardBackupStatus
                    $backupStatus = new DashboardBackupStatus($authService, $database_handler, $container->make(LoggerInterface::class));
                    echo $backupStatus->renderBackupStatusWidget();
                } catch (Exception $e) {
                    error_log("Failed to load backup status widget: " . $e->getMessage());
                    // Показываем простой виджет с ошибкой для администраторов
                    ?>
                    <div class="dashboard-backup-widget dashboard-profile-compact">
                        <div class="profile-header">
                            <h3 class="dashboard-section-title">
                                <i class="fas fa-shield-alt"></i> Backup System
                            </h3>
                            <a href="/index.php?page=backup_monitor" class="overview-card-action">
                                <i class="fas fa-cog"></i> Manage
                            </a>
                        </div>
                        <div class="backup-status-indicator backup-status-danger">
                            <div class="backup-status-icon">
                                <i class="fas fa-exclamation-triangle"></i>
                            </div>
                            <div class="backup-status-text">
                                <strong>Unable to load backup status</strong>
                            </div>
                        </div>
                        <div class="profile-summary-grid">
                            <div class="profile-summary-item">
                                <span class="profile-summary-label">Status</span>
                                <span class="profile-summary-value">Error</span>
                            </div>
                        </div>
                    </div>
                    <?php
                }
                ?>
            </section>
            <?php endif; ?>
        </div>
    </div>
</div>
