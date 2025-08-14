<?php

/**
 * Manage Articles Page - MODERN DARK ADMIN INTERFACE
 *
 * Modern dark administrative interface for managing articles
 * with improved UX and consistent styling
 *
 * @author Dmytro Hovenko
 */

declare(strict_types=1);

use App\Domain\Models\Article;
use App\Domain\Models\Category;
use App\Domain\Models\User;
use App\Application\Middleware\CSRFMiddleware;
use App\Domain\Repositories\ArticleRepository;
use App\Application\Components\AdminNavigation;

// Use global services from bootstrap.php
global $flashMessageService, $database_handler, $serviceProvider;

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}


// Get AuthenticationService
try {
    $authService = $serviceProvider->getAuth();
} catch (Exception $e) {
    error_log("Critical: Failed to get AuthenticationService instance: " . $e->getMessage());
    die("A critical system error occurred. Please try again later.");
}

// Check authentication and admin rights
if (!$authService->isAuthenticated() || !$authService->hasRole('admin')) {
    $flashMessageService->addError("Access Denied. You do not have permission to view this page.");
    header('Location: /index.php?page=login');
    exit();
}

// Check for required services
if (!isset($flashMessageService)) {
    error_log("Critical: FlashMessageService not available in manage_articles.php");
    die("A critical system error occurred. Please try again later.");
}

if (!isset($database_handler)) {
    error_log("Critical: Database handler not available in manage_articles.php");
    $flashMessageService->addError("Database connection error. Please try again later.");
    header('Location: /index.php?page=dashboard');
    exit();
}

$currentUser = $authService->getCurrentUser();
$current_user_id = (int)$currentUser['id'];
$user_role = $currentUser['role'] ?? 'user';

$page_title = "Manage Articles";

// Create unified navigation
$adminNavigation = new AdminNavigation($authService);

// Get CSRF token via global system
$csrf_token = CSRFMiddleware::getToken();

// Handle POST requests for moderation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token via global system
    if (!CSRFMiddleware::validateQuick()) {
        $flashMessageService->addError('Invalid CSRF token. Please try again.');
        header('Location: /index.php?page=manage_articles');
        exit;
    }

    $action = $_POST['action'] ?? '';
    $article_id = (int)($_POST['article_id'] ?? 0);

    if ($article_id > 0) {
        $articleRepository = new ArticleRepository($database_handler);
        $article = $articleRepository->findById($article_id);

        if (!$article) {
            $flashMessageService->addError('Article not found.');
            header('Location: /index.php?page=manage_articles');
            exit;
        }

        switch ($action) {
            case 'submit_for_review':
                if ($article->updateStatus('pending_review', $current_user_id, $articleRepository)) {
                    $flashMessageService->addSuccess('Article submitted for review successfully.');
                } else {
                    $flashMessageService->addError('Failed to submit article for review.');
                }
                break;

            case 'approve':
                // Check if a user has moderation permissions
                if (in_array($user_role, ['admin', 'editor'])) {
                    $reviewer_id = (int)$currentUser['id'];
                    $review_notes = $_POST['review_notes'] ?? null;

                    if ($article->updateStatus('published', $reviewer_id, $articleRepository, $review_notes)) {
                        $flashMessageService->addSuccess('Article approved successfully.');
                    } else {
                        $flashMessageService->addError('Failed to approve article.');
                    }
                } else {
                    $flashMessageService->addError('Access denied. You do not have moderation permissions.');
                }
                break;

            case 'reject':
                // Check if a user has moderation permissions
                if (in_array($user_role, ['admin', 'editor'])) {
                    $reviewer_id = (int)$currentUser['id'];
                    $review_notes = $_POST['review_notes'] ?? '';

                    if (empty($review_notes)) {
                        $flashMessageService->addError('Review notes are required for rejection.');
                    } else {
                        if ($article->updateStatus('rejected', $reviewer_id, $articleRepository, $review_notes)) {
                            $flashMessageService->addSuccess('Article rejected successfully.');
                        } else {
                            $flashMessageService->addError('Failed to reject article.');
                        }
                    }
                } else {
                    $flashMessageService->addError('Access denied. You do not have moderation permissions.');
                }
                break;

            case 'revoke_approval':
                // Check if a user has moderation permissions
                if (in_array($user_role, ['admin', 'editor'])) {
                    $reviewer_id = (int)$currentUser['id'];
                    $review_notes = $_POST['review_notes'] ?? '';

                    if (empty($review_notes)) {
                        $flashMessageService->addError('Review notes are required for revoking approval.');
                    } else {
                        if ($article->updateStatus('draft', $reviewer_id, $articleRepository, $review_notes)) {
                            $flashMessageService->addSuccess('Article approval revoked successfully. Article returned to draft status.');
                        } else {
                            $flashMessageService->addError('Failed to revoke article approval.');
                        }
                    }
                } else {
                    $flashMessageService->addError('Access denied. You do not have moderation permissions.');
                }
                break;
        }

        header('Location: /index.php?page=manage_articles');
        exit;
    }
}

$articles_view_data = [];

// Get status filter from GET parameters
$status_filter = filter_input(INPUT_GET, 'filter', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$valid_filters = ['all', 'published', 'draft', 'pending_review', 'rejected'];
if (!in_array($status_filter, $valid_filters)) {
    $status_filter = 'all';
}

// Get a list of articles depending on the user role and filter
try {
    $filter_status = ($status_filter === 'all') ? null : $status_filter;

    // Create an ArticleRepository instance
    $articleRepository = new ArticleRepository($database_handler);
    $articles_list = ($user_role === 'admin')
        ? $articleRepository->findAll($filter_status)
        : $articleRepository->findByUserId($current_user_id, $filter_status);

    if (!empty($articles_list)) {
        $user_ids_to_fetch = array_unique(array_filter(array_map(fn($article) => $article->user_id, $articles_list)));

        $authors_map = [];
        if (!empty($user_ids_to_fetch)) {
            foreach ($user_ids_to_fetch as $uid) {
                $author_user = User::findById($database_handler, $uid);
                $authors_map[$uid] = $author_user ? $author_user['username'] : 'Unknown User';
            }
        }

        foreach ($articles_list as $article_instance) {
            if (!$article_instance instanceof Article) {
                error_log("Manage Articles: Item in articles_list is not an Article object.");
                continue;
            }

            $categories = $articleRepository->getCategories($article_instance);

            $articles_view_data[] = [
                'id' => $article_instance->id,
                'title' => $article_instance->title,
                'date' => $article_instance->date,
                'user_id' => $article_instance->user_id,
                'status' => $article_instance->status,
                'author_name' => $authors_map[$article_instance->user_id] ?? ($article_instance->user_id ? 'User ID: ' . $article_instance->user_id : 'N/A'),
                'categories' => $categories,
            ];
        }
    }
} catch (Exception $e) {
    error_log("Error loading articles in manage_articles.php: " . $e->getMessage());
    $flashMessageService->addError("Error loading articles. Please try again later.");
}

// Get flash messages
$flashMessages = $flashMessageService->getAllMessages();

?>

    <!-- Admin Dark Theme Styles -->
    <link rel="stylesheet" href="/public/assets/css/admin.css">

    <!-- Unified Navigation -->
    <?= $adminNavigation->render() ?>

    <!-- Header -->
    <header class="admin-header">
        <div class="admin-header-container">
            <div class="admin-header-content">
                <div class="admin-header-title">
                    <i class="admin-header-icon fas fa-newspaper"></i>
                    <div class="admin-header-text">
                        <h1>Manage Articles</h1>
                        <p>Create, edit and manage your articles and content</p>
                    </div>
                </div>

                <div class="admin-header-actions">
                    <a href="/index.php?page=create_article" class="admin-btn admin-btn-primary">
                        <i class="fas fa-plus"></i>Create Article
                    </a>
                    <a href="/index.php?page=manage_categories" class="admin-btn admin-btn-secondary">
                        <i class="fas fa-tags"></i>Categories
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Flash Messages -->
    <?php if (!empty($flashMessages)): ?>
    <div class="admin-flash-messages">
        <?php foreach ($flashMessages as $type => $messages): ?>
            <?php foreach ($messages as $message): ?>
            <div class="admin-flash-message admin-flash-<?= $type ?>">
                <i class="fas fa-<?= $type === 'error' ? 'exclamation-circle' : ($type === 'success' ? 'check-circle' : ($type === 'warning' ? 'exclamation-triangle' : 'info-circle')) ?>"></i>
                <div>
                    <?= $message['is_html'] ? $message['text'] : htmlspecialchars($message['text']) ?>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Main Content -->
    <main>
        <div class="admin-layout-main">
            <div class="admin-content">

                <!-- Filter Bar -->
                <div class="admin-card">
                    <div class="admin-card-body">
                        <div style="display: flex; justify-content: between; align-items: center; gap: 1rem;">
                            <div>
                                <h3 style="margin: 0; color: var(--admin-text-primary);">
                                    <i class="fas fa-filter"></i> Filter Articles
                                </h3>
                            </div>
                            <div style="display: flex; gap: 1rem; align-items: center;">
                                <select id="statusFilter" class="admin-input admin-select" onchange="updateStatusFilter()" style="width: auto;">
                                    <option value="all" <?= $status_filter === 'all' ? 'selected' : '' ?>>All Statuses</option>
                                    <option value="published" <?= $status_filter === 'published' ? 'selected' : '' ?>>Published</option>
                                    <option value="draft" <?= $status_filter === 'draft' ? 'selected' : '' ?>>Draft</option>
                                    <option value="pending_review" <?= $status_filter === 'pending_review' ? 'selected' : '' ?>>Pending Review</option>
                                    <option value="rejected" <?= $status_filter === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                                </select>
                                <input type="text" id="searchInput" placeholder="Search articles..." class="admin-input" style="width: 300px;">
                            </div>
                        </div>
                    </div>
                </div>

                <?php if (empty($articles_view_data)): ?>
                    <!-- Empty State -->
                    <div class="admin-card admin-glow-primary">
                        <div class="admin-card-body" style="text-align: center; padding: 3rem;">
                            <div style="font-size: 4rem; color: var(--admin-text-muted); margin-bottom: 1rem;">
                                <i class="fas fa-file-alt"></i>
                            </div>
                            <h3 style="color: var(--admin-text-primary); margin-bottom: 1rem;">No Articles Found</h3>
                            <p style="color: var(--admin-text-muted); margin-bottom: 2rem;">
                                <?php if ($user_role === 'admin'): ?>
                                    No articles have been created yet. Start building your content library and engage with your audience.
                                <?php else: ?>
                                    You haven't created any articles yet. Share your thoughts, insights, and stories with the world!
                                <?php endif; ?>
                            </p>
                            <a href="/index.php?page=create_article" class="admin-btn admin-btn-primary admin-btn-lg">
                                <i class="fas fa-plus"></i>Create Your First Article
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Articles Statistics -->
                    <div class="admin-stats-grid">
                        <div class="admin-stat-card admin-glow-success">
                            <div class="admin-stat-content">
                                <div class="admin-stat-icon" style="background: var(--admin-success-bg); color: var(--admin-success);">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                                <div class="admin-stat-details">
                                    <h3>Published</h3>
                                    <p>
                                        <?php
                                        $published = array_filter($articles_view_data, fn($a) => $a['status'] === 'published');
                                        echo count($published);
                                        ?>
                                    </p>
                                    <span>Live articles</span>
                                </div>
                            </div>
                        </div>

                        <div class="admin-stat-card admin-glow-warning">
                            <div class="admin-stat-content">
                                <div class="admin-stat-icon" style="background: var(--admin-warning-bg); color: var(--admin-warning);">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <div class="admin-stat-details">
                                    <h3>Pending</h3>
                                    <p>
                                        <?php
                                        $pending = array_filter($articles_view_data, fn($a) => $a['status'] === 'pending_review');
                                        echo count($pending);
                                        ?>
                                    </p>
                                    <span>Awaiting review</span>
                                </div>
                            </div>
                        </div>

                        <div class="admin-stat-card">
                            <div class="admin-stat-content">
                                <div class="admin-stat-icon" style="background: var(--admin-bg-secondary); color: var(--admin-text-secondary);">
                                    <i class="fas fa-edit"></i>
                                </div>
                                <div class="admin-stat-details">
                                    <h3>Drafts</h3>
                                    <p>
                                        <?php
                                        $drafts = array_filter($articles_view_data, fn($a) => $a['status'] === 'draft');
                                        echo count($drafts);
                                        ?>
                                    </p>
                                    <span>Work in progress</span>
                                </div>
                            </div>
                        </div>

                        <div class="admin-stat-card admin-glow-primary">
                            <div class="admin-stat-content">
                                <div class="admin-stat-icon" style="background: var(--admin-primary-bg); color: var(--admin-primary);">
                                    <i class="fas fa-chart-line"></i>
                                </div>
                                <div class="admin-stat-details">
                                    <h3>Total</h3>
                                    <p><?= count($articles_view_data) ?></p>
                                    <span>All articles</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Articles Table -->
                    <div class="admin-card">
                        <div class="admin-card-header">
                            <h3 class="admin-card-title">
                                <i class="fas fa-list"></i>Articles
                                <span class="admin-badge admin-badge-primary">
                                    <?= count($articles_view_data) ?> Total
                                </span>
                            </h3>
                        </div>
                        <div class="admin-card-body" style="padding: 0;">
                            <div class="admin-table-container">
                                <table class="admin-table">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Title</th>
                                            <?php if ($user_role === 'admin'): ?>
                                            <th>Author</th>
                                            <?php endif; ?>
                                            <th>Categories</th>
                                            <th>Date</th>
                                            <th>Status</th>
                                            <th style="text-align: center;">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="articlesTableBody">
                                        <?php foreach ($articles_view_data as $article_item): ?>
                                        <tr class="article-row"
                                            data-title="<?= strtolower(htmlspecialchars($article_item['title'])) ?>"
                                            data-status="<?= strtolower(htmlspecialchars($article_item['status'])) ?>">
                                            <td>
                                                <span class="admin-badge admin-badge-gray">
                                                    #<?= htmlspecialchars((string)$article_item['id']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div>
                                                    <div style="font-weight: 600; color: var(--admin-text-primary); margin-bottom: 0.25rem;">
                                                        <a href="/index.php?page=news&id=<?= $article_item['id'] ?>"
                                                           style="color: var(--admin-primary); text-decoration: none;">
                                                            <?= htmlspecialchars(mb_strimwidth($article_item['title'], 0, 60, "...")) ?>
                                                        </a>
                                                    </div>
                                                </div>
                                            </td>
                                            <?php if ($user_role === 'admin'): ?>
                                            <td>
                                                <div style="display: flex; align-items: center;">
                                                    <div style="width: 32px; height: 32px; background: var(--admin-primary-bg); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 0.75rem;">
                                                        <i class="fas fa-user" style="color: var(--admin-primary);"></i>
                                                    </div>
                                                    <div style="color: var(--admin-text-primary);">
                                                        <?= htmlspecialchars($article_item['author_name']) ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <?php endif; ?>
                                            <td>
                                                <?php if (!empty($article_item['categories'])): ?>
                                                    <div style="display: flex; flex-wrap: wrap; gap: 0.25rem;">
                                                        <?php foreach (array_slice($article_item['categories'], 0, 2) as $category): ?>
                                                            <span class="admin-badge admin-badge-primary" style="font-size: 0.625rem;">
                                                                <i class="fas fa-tag"></i>
                                                                <?= htmlspecialchars($category->name) ?>
                                                            </span>
                                                        <?php endforeach; ?>
                                                        <?php if (count($article_item['categories']) > 2): ?>
                                                            <span class="admin-badge admin-badge-gray" style="font-size: 0.625rem;">
                                                                +<?= count($article_item['categories']) - 2 ?>
                                                            </span>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php else: ?>
                                                    <span style="color: var(--admin-text-muted); font-size: 0.875rem;">No categories</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div>
                                                    <div style="color: var(--admin-text-primary); font-size: 0.875rem;">
                                                        <?= htmlspecialchars(date('M j, Y', strtotime($article_item['date']))) ?>
                                                    </div>
                                                    <div style="color: var(--admin-text-muted); font-size: 0.75rem;">
                                                        <?= htmlspecialchars(date('g:i A', strtotime($article_item['date']))) ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <?php
                                                $status = $article_item['status'];
                                                $statusConfig = [
                                                    'published' => ['class' => 'success', 'icon' => 'check-circle'],
                                                    'draft' => ['class' => 'gray', 'icon' => 'edit'],
                                                    'pending_review' => ['class' => 'warning', 'icon' => 'clock'],
                                                    'rejected' => ['class' => 'error', 'icon' => 'times-circle']
                                                ];
                                                $config = $statusConfig[$status] ?? $statusConfig['draft'];
                                                ?>
                                                <span class="admin-badge admin-badge-<?= $config['class'] ?>">
                                                    <i class="fas fa-<?= $config['icon'] ?>"></i>
                                                    <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $status))) ?>
                                                </span>
                                            </td>
                                            <td style="text-align: center;">
                                                <?php if ($user_role === 'admin' || $article_item['user_id'] == $current_user_id): ?>
                                                <div style="display: flex; gap: 0.5rem; justify-content: center;">
                                                    <a href="/index.php?page=edit_article&id=<?= $article_item['id'] ?>"
                                                       class="admin-btn admin-btn-secondary admin-btn-sm" title="Edit Article">
                                                        <i class="fas fa-edit"></i>
                                                    </a>

                                                    <!-- Moderation buttons for admins/editors -->
                                                    <?php if (in_array($user_role, ['admin', 'editor']) && $article_item['status'] === 'pending_review'): ?>
                                                        <button type="button" class="admin-btn admin-btn-success admin-btn-sm"
                                                                onclick="approveArticle(<?= $article_item['id'] ?>)" title="Approve Article">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                        <button type="button" class="admin-btn admin-btn-danger admin-btn-sm"
                                                                onclick="showRejectModal(<?= $article_item['id'] ?>, '<?= htmlspecialchars($article_item['title']) ?>')" title="Reject Article">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    <?php endif; ?>

                                                    <!-- Submit for review button -->
                                                    <?php if ($article_item['status'] === 'draft' || $article_item['status'] === 'rejected'): ?>
                                                        <form method="POST" style="display: inline;">
                                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                                                            <input type="hidden" name="article_id" value="<?= $article_item['id'] ?>">
                                                            <input type="hidden" name="action" value="submit_for_review">
                                                            <button type="submit" class="admin-btn admin-btn-warning admin-btn-sm" title="Submit for Review">
                                                                <i class="fas fa-paper-plane"></i>
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>

                                                    <form method="POST" style="display: inline;"
                                                          onsubmit="return confirm('Are you sure you want to delete this article?');">
                                                        <input type="hidden" name="article_id" value="<?= $article_item['id'] ?>">
                                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                                                        <button type="submit" class="admin-btn admin-btn-danger admin-btn-sm" title="Delete Article">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                                <?php else: ?>
                                                <span style="color: var(--admin-text-muted); font-size: 0.75rem;">View Only</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Sidebar -->
            <aside class="admin-sidebar">
                <!-- Article Statistics -->
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h3 class="admin-card-title">
                            <i class="fas fa-chart-bar"></i>Content Analytics
                        </h3>
                    </div>
                    <div class="admin-card-body">
                        <?php
                        $recentArticles = array_filter($articles_view_data, fn($a) => strtotime($a['date']) > strtotime('-7 days'));
                        $monthlyArticles = array_filter($articles_view_data, fn($a) => strtotime($a['date']) > strtotime('-30 days'));
                        $totalWords = 0; // This would need actual content analysis
                        $avgWordsPerArticle = count($articles_view_data) > 0 ? 500 : 0; // Placeholder
                        ?>

                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
                            <div style="display: flex; align-items: center;">
                                <i class="fas fa-calendar-week" style="margin-right: 0.5rem; color: var(--admin-success);"></i>
                                <span style="font-size: 0.875rem; color: var(--admin-text-secondary);">This Week</span>
                            </div>
                            <span style="font-size: 1.125rem; font-weight: 600; color: var(--admin-text-primary);"><?= count($recentArticles) ?></span>
                        </div>

                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
                            <div style="display: flex; align-items: center;">
                                <i class="fas fa-calendar-alt" style="margin-right: 0.5rem; color: var(--admin-info);"></i>
                                <span style="font-size: 0.875rem; color: var(--admin-text-secondary);">This Month</span>
                            </div>
                            <span style="font-size: 1.125rem; font-weight: 600; color: var(--admin-text-primary);"><?= count($monthlyArticles) ?></span>
                        </div>

                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
                            <div style="display: flex; align-items: center;">
                                <i class="fas fa-file-word" style="margin-right: 0.5rem; color: var(--admin-warning);"></i>
                                <span style="font-size: 0.875rem; color: var(--admin-text-secondary);">Avg. Words</span>
                            </div>
                            <span style="font-size: 1.125rem; font-weight: 600; color: var(--admin-text-primary);"><?= $avgWordsPerArticle ?></span>
                        </div>

                        <hr style="border: none; border-top: 1px solid var(--admin-border); margin: 1rem 0;">

                        <div style="display: flex; align-items: center; justify-content: space-between;">
                            <div style="display: flex; align-items: center;">
                                <i class="fas fa-eye" style="margin-right: 0.5rem; color: var(--admin-primary);"></i>
                                <span style="font-size: 0.875rem; color: var(--admin-text-secondary);">Public Articles</span>
                            </div>
                            <span style="font-size: 1.125rem; font-weight: 600; color: var(--admin-text-primary);"><?= count($published) ?></span>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h3 class="admin-card-title">
                            <i class="fas fa-bolt"></i>Quick Actions
                        </h3>
                    </div>
                    <div class="admin-card-body">
                        <a href="/index.php?page=create_article" class="admin-btn admin-btn-primary" style="width: 100%; margin-bottom: 0.5rem; justify-content: flex-start;">
                            <i class="fas fa-plus"></i>
                            Create Article
                        </a>
                        <a href="/index.php?page=manage_categories" class="admin-btn admin-btn-secondary" style="width: 100%; margin-bottom: 0.5rem; justify-content: flex-start;">
                            <i class="fas fa-tags"></i>
                            Manage Categories
                        </a>
                        <a href="/page/admin/moderation/projects.php" class="admin-btn admin-btn-secondary" style="width: 100%; margin-bottom: 0.5rem; justify-content: flex-start;">
                            <i class="fas fa-gavel"></i>
                            Moderation
                        </a>
                        <a href="/index.php?page=dashboard" class="admin-btn admin-btn-secondary" style="width: 100%; justify-content: flex-start;">
                            <i class="fas fa-tachometer-alt"></i>
                            Dashboard
                        </a>
                    </div>
                </div>

                <!-- Content Status Guide -->
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h3 class="admin-card-title">
                            <i class="fas fa-info-circle"></i>Status Guide
                        </h3>
                    </div>
                    <div class="admin-card-body">
                        <div style="display: flex; flex-direction: column; gap: 1rem;">
                            <div>
                                <h4 style="display: flex; align-items: center; margin: 0 0 0.5rem 0; font-size: 0.875rem; font-weight: 600;">
                                    <i class="fas fa-check-circle" style="color: var(--admin-success); margin-right: 0.5rem;"></i>Published
                                </h4>
                                <p style="font-size: 0.875rem; color: var(--admin-text-secondary); margin: 0;">Article is live and visible to the public.</p>
                            </div>
                            <div>
                                <h4 style="display: flex; align-items: center; margin: 0 0 0.5rem 0; font-size: 0.875rem; font-weight: 600;">
                                    <i class="fas fa-clock" style="color: var(--admin-warning); margin-right: 0.5rem;"></i>Pending Review
                                </h4>
                                <p style="font-size: 0.875rem; color: var(--admin-text-secondary); margin: 0;">Article awaiting admin approval before publication.</p>
                            </div>
                            <div>
                                <h4 style="display: flex; align-items: center; margin: 0 0 0.5rem 0; font-size: 0.875rem; font-weight: 600;">
                                    <i class="fas fa-edit" style="color: var(--admin-text-muted); margin-right: 0.5rem;"></i>Draft
                                </h4>
                                <p style="font-size: 0.875rem; color: var(--admin-text-secondary); margin: 0;">Work in progress, not yet submitted for review.</p>
                            </div>
                            <div>
                                <h4 style="display: flex; align-items: center; margin: 0 0 0.5rem 0; font-size: 0.875rem; font-weight: 600;">
                                    <i class="fas fa-times-circle" style="color: var(--admin-error); margin-right: 0.5rem;"></i>Rejected
                                </h4>
                                <p style="font-size: 0.875rem; color: var(--admin-text-secondary); margin: 0;">Article needs revisions before resubmission.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Writing Tips -->
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h3 class="admin-card-title">
                            <i class="fas fa-lightbulb"></i>Writing Tips
                        </h3>
                    </div>
                    <div class="admin-card-body">
                        <div style="display: flex; flex-direction: column; gap: 1rem;">
                            <div>
                                <h4 style="display: flex; align-items: center; margin: 0 0 0.5rem 0; font-size: 0.875rem; font-weight: 600;">
                                    <i class="fas fa-bullseye" style="color: var(--admin-primary); margin-right: 0.5rem;"></i>Clear Headlines
                                </h4>
                                <p style="font-size: 0.875rem; color: var(--admin-text-secondary); margin: 0;">Use descriptive titles that tell readers exactly what to expect.</p>
                            </div>
                            <div>
                                <h4 style="display: flex; align-items: center; margin: 0 0 0.5rem 0; font-size: 0.875rem; font-weight: 600;">
                                    <i class="fas fa-search" style="color: var(--admin-success); margin-right: 0.5rem;"></i>SEO Optimization
                                </h4>
                                <p style="font-size: 0.875rem; color: var(--admin-text-secondary); margin: 0;">Include relevant keywords naturally in your content and excerpts.</p>
                            </div>
                            <div>
                                <h4 style="display: flex; align-items: center; margin: 0 0 0.5rem 0; font-size: 0.875rem; font-weight: 600;">
                                    <i class="fas fa-users" style="color: var(--admin-warning); margin-right: 0.5rem;"></i>Audience Focus
                                </h4>
                                <p style="font-size: 0.875rem; color: var(--admin-text-secondary); margin: 0;">Write with your target audience in mind and provide real value.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </aside>
        </div>
    </main>

    <!-- Reject Modal -->
    <div id="rejectModal" class="admin-modal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.8); z-index: 1000; padding: 2rem;">
        <div style="background: var(--admin-bg-card); border-radius: var(--admin-border-radius); max-width: 500px; margin: auto; border: 1px solid var(--admin-border);">
            <div class="admin-card-header">
                <h3 class="admin-card-title">
                    <i class="fas fa-times-circle"></i>Reject Article
                </h3>
            </div>
            <div class="admin-card-body">
                <p style="color: var(--admin-text-secondary); margin-bottom: 1rem;">
                    You are about to reject the article: <strong id="rejectArticleTitle" style="color: var(--admin-text-primary);"></strong>
                </p>
                <div class="admin-form-group">
                    <label for="rejectNotes" class="admin-label">Reason for rejection (required):</label>
                    <textarea id="rejectNotes" class="admin-input admin-textarea" placeholder="Please provide a clear explanation for why this article is being rejected..." required></textarea>
                </div>
                <div style="display: flex; justify-content: flex-end; gap: 1rem;">
                    <button type="button" class="admin-btn admin-btn-secondary" onclick="closeRejectModal()">Cancel</button>
                    <button type="button" class="admin-btn admin-btn-danger" onclick="submitReject()">Reject Article</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Admin Scripts -->
    <script src="/public/assets/js/admin.js"></script>

    <script>
    // Global variables for moderation
    let currentArticleId = null;
    const csrfToken = '<?= htmlspecialchars($csrf_token) ?>';

    function approveArticle(articleId) {
        if (confirm('Are you sure you want to approve this article?')) {
            const formData = new FormData();
            formData.append('action', 'approve');
            formData.append('article_id', articleId);
            formData.append('review_notes', '');
            formData.append('csrf_token', csrfToken);

            fetch('/index.php?page=manage_articles', {
                method: 'POST',
                body: formData
            })
            .then(() => location.reload())
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while approving the article');
            });
        }
    }

    function showRejectModal(articleId, articleTitle) {
        currentArticleId = articleId;
        document.getElementById('rejectArticleTitle').textContent = articleTitle;
        document.getElementById('rejectNotes').value = '';
        document.getElementById('rejectModal').style.display = 'flex';
    }

    function closeRejectModal() {
        document.getElementById('rejectModal').style.display = 'none';
        currentArticleId = null;
    }

    function submitReject() {
        const notes = document.getElementById('rejectNotes').value.trim();

        if (!notes) {
            alert('Please provide a reason for rejection');
            return;
        }

        const formData = new FormData();
        formData.append('action', 'reject');
        formData.append('article_id', currentArticleId);
        formData.append('review_notes', notes);
        formData.append('csrf_token', csrfToken);

        fetch('/index.php?page=manage_articles', {
            method: 'POST',
            body: formData
        })
        .then(() => {
            closeRejectModal();
            location.reload();
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while rejecting the article');
        });
    }

    // Update status filter
    function updateStatusFilter() {
        const selectedStatus = document.getElementById('statusFilter').value;
        window.location.href = '/index.php?page=manage_articles&filter=' + selectedStatus;
    }

    // Search functionality
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('searchInput');
        const tableBody = document.getElementById('articlesTableBody');
        const rows = tableBody.querySelectorAll('.article-row');

        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();

            rows.forEach(row => {
                const title = row.dataset.title;
                const status = row.dataset.status;
                
                if (title.includes(searchTerm) || status.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    });
    </script>
