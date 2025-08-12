<?php

/**
 * Manage Articles Page
 *
 * @author Dmytro Hovenko
 */

declare(strict_types=1);

use App\Domain\Models\Article;
use App\Domain\Models\Category;
use App\Domain\Models\User;
use App\Application\Middleware\CSRFMiddleware;
use App\Domain\Repositories\ArticleRepository;

// Use global services from bootstrap.php
global $flashMessageService, $database_handler, $auth;

// Check for required services
if (!isset($flashMessageService)) {
    error_log("Critical: FlashMessageService not available in manage_articles.php");
    die("A critical system error occurred. Please try again later.");
}

if (!isset($database_handler)) {
    error_log("Critical: Database handler not available in manage_articles.php");
    echo "<p class='message message--error'>Database connection error. Please try again later.</p>";
    return;
}

// Check authorization
if (!$auth || !$auth->isAuthenticated()) {
    $flashMessageService->addError('You must be logged in to manage articles.');
    $redirect_url = urlencode('/index.php?page=manage_articles');
    header('Location: /index.php?page=login&redirect=' . $redirect_url);
    exit;
}

$currentUser = $auth->getCurrentUser();
$current_user_id = (int)$currentUser['id'];
$user_role = $currentUser['role'] ?? 'user';

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
$page_title = "Manage Articles";

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
    echo "<p class='message message--error'>Error loading articles. Please try again later.</p>";
    return;
}
?>
    <div class="admin-layout page-manage-articles">
        <!-- Enhanced Main Header Section -->
        <header class="page-header">
            <div class="page-header-content">
                <div class="page-header-main">
                    <h1 class="page-title">
                        <i class="fas fa-newspaper"></i>
                        <?php echo htmlspecialchars($page_title); ?>
                    </h1>
                    <div class="page-header-description">
                        <p>Manage and organize your published articles</p>
                    </div>
                </div>
                <div class="page-header-actions">
                    <a href="/index.php?page=create_article" class="btn btn-create">
                        <i class="fas fa-plus"></i>
                        <span>Create Article</span>
                    </a>
                    <?php if ($user_role === 'admin'): ?>
                        <a href="/index.php?page=manage_categories" class="btn btn-categories">
                            <i class="fas fa-tags"></i>
                            <span>Categories</span>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </header>

        <!-- Flash Messages -->
        <?php
        $flashMessages = $flashMessageService->getMessages();
        if (!empty($flashMessages)):
        ?>
            <div class="flash-messages-container">
                <?php foreach ($flashMessages as $type => $messages): ?>
                    <?php foreach ($messages as $message): ?>
                        <div class="message message--<?php echo htmlspecialchars($type); ?>">
                            <p><?php echo $message['is_html'] ? $message['text'] : htmlspecialchars($message['text']); ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Main Content Layout -->
        <div class="content-layout">
            <!-- Primary Content Area -->
            <main class="main-content" style="flex: 1; max-width: none;">
                <?php if (empty($articles_view_data)): ?>
                    <!-- Enhanced Empty State -->
                    <div class="empty-state-wrapper">
                        <div class="card card-large">
                            <div class="card-body">
                                <div class="empty-state">
                                    <div class="empty-state-visual">
                                        <div class="empty-state-icon">
                                            <i class="fas fa-file-alt"></i>
                                        </div>
                                    </div>
                                    <div class="empty-state-content">
                                        <h2 class="empty-state-title">No Articles Found</h2>
                                        <p class="empty-state-description">
                                            <?php if ($user_role === 'admin'): ?>
                                                No articles have been created yet. Start building your content library and engage with your audience.
                                            <?php else: ?>
                                                You haven't created any articles yet. Share your thoughts, insights, and stories with the world!
                                            <?php endif; ?>
                                        </p>
                                        <div class="empty-state-actions">
                                            <a href="/index.php?page=create_article" class="btn btn-create btn-large">
                                                <i class="fas fa-plus"></i>
                                                <span>Create Your First Article</span>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Articles Management Section -->
                    <div class="articles-section">
                        <div class="card">
                            <div class="card-header card-header-enhanced">
                                <div class="card-header-content">
                                    <h2 class="card-title">
                                        <i class="fas fa-list"></i>
                                        Your Articles
                                        <span class="articles-count">(<?php echo count($articles_view_data); ?>)</span>
                                    </h2>
                                </div>
                                <!-- Search and Filter Bar -->
                                <div class="articles-controls">
                                    <div class="search-section">
                                        <div class="search-box">
                                            <label for="articleSearch"></label><input type="text" id="articleSearch" placeholder="Search articles..." class="search-input">
                                            <i class="fas fa-search search-icon"></i>
                                        </div>
                                        <div class="filter-options">
                                            <label for="categoryFilter"></label><select id="categoryFilter" class="filter-select">
                                                <option value="">All Categories</option>
                                                <?php
                                                try {
                                                    $all_categories = Category::findAll($database_handler);
                                                    foreach ($all_categories as $category) {
                                                        echo '<option value="' . htmlspecialchars($category->name) . '">' . htmlspecialchars($category->name) . '</option>';
                                                    }
                                                } catch (Exception $e) {
                                                    // Silently handle error
                                                }
                                                ?>
                                            </select>
                                            <?php if ($user_role === 'admin'): ?>
                                                <label for="authorFilter"></label><select id="authorFilter" class="filter-select">
                                                    <option value="">All Authors</option>
                                                    <?php
                                                    $authors = array_unique(array_column($articles_view_data, 'author_name'));
                                                    foreach ($authors as $author) {
                                                        echo '<option value="' . htmlspecialchars($author) . '">' . htmlspecialchars($author) . '</option>';
                                                    }
                                                    ?>
                                                </select>
                                            <?php endif; ?>
                                            <label for="statusFilter"></label><select id="statusFilter" class="filter-select">
                                                <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Statuses</option>
                                                <option value="published" <?php echo $status_filter === 'published' ? 'selected' : ''; ?>>Published</option>
                                                <option value="draft" <?php echo $status_filter === 'draft' ? 'selected' : ''; ?>>Draft</option>
                                                <option value="pending_review" <?php echo $status_filter === 'pending_review' ? 'selected' : ''; ?>>Pending Review</option>
                                                <option value="rejected" <?php echo $status_filter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="table-container">
                                    <div class="table-responsive">
                                        <table class="styled-table articles-table">
                                            <thead>
                                                <tr>
                                                    <th class="col-id sortable" data-sort="id">
                                                        <span class="th-content">
                                                            <i class="fas fa-hashtag"></i> ID
                                                            <i class="fas fa-sort sort-icon"></i>
                                                        </span>
                                                    </th>
                                                    <th class="col-title sortable" data-sort="title">
                                                        <span class="th-content">
                                                            <i class="fas fa-heading"></i> Title
                                                            <i class="fas fa-sort sort-icon"></i>
                                                        </span>
                                                    </th>
                                                    <?php if ($user_role === 'admin'): ?>
                                                        <th class="col-author sortable" data-sort="author">
                                                            <span class="th-content">
                                                                <i class="fas fa-user"></i> Author
                                                                <i class="fas fa-sort sort-icon"></i>
                                                            </span>
                                                        </th>
                                                    <?php endif; ?>
                                                    <th class="col-categories">
                                                        <span class="th-content">
                                                            <i class="fas fa-tags"></i> Categories
                                                        </span>
                                                    </th>
                                                    <th class="col-date sortable" data-sort="date">
                                                        <span class="th-content">
                                                            <i class="fas fa-calendar"></i> Published
                                                            <i class="fas fa-sort sort-icon"></i>
                                                        </span>
                                                    </th>
                                                    <th class="col-status sortable" data-sort="status">
                                                        <span class="th-content">
                                                            <i class="fas fa-check-circle"></i> Status
                                                            <i class="fas fa-sort sort-icon"></i>
                                                        </span>
                                                    </th>
                                                    <th class="col-actions">
                                                        <span class="th-content">
                                                            <i class="fas fa-cogs"></i> Actions
                                                        </span>
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody id="articlesTableBody">
                                                <?php foreach ($articles_view_data as $article_item): ?>
                                                    <tr class="article-row"
                                                        data-id="<?php echo $article_item['id']; ?>"
                                                        data-title="<?php echo strtolower(htmlspecialchars($article_item['title'])); ?>"
                                                        data-author="<?php echo strtolower(htmlspecialchars($article_item['author_name'])); ?>"
                                                        data-date="<?php echo $article_item['date']; ?>"
                                                        data-status="<?php echo strtolower(htmlspecialchars($article_item['status'])); ?>"
                                                        data-categories="<?php echo strtolower(implode(',', array_map(fn($cat) => $cat->name, $article_item['categories']))); ?>">
                                                        <td class="article-id">
                                                            <span class="id-badge"><?php echo htmlspecialchars((string)$article_item['id']); ?></span>
                                                        </td>
                                                        <td class="article-title">
                                                            <div class="title-container">
                                                                <a href="/index.php?page=news&id=<?php echo $article_item['id']; ?>"
                                                                   class="article-title-link"
                                                                   title="View Article">
                                                                    <?php echo htmlspecialchars(mb_strimwidth($article_item['title'], 0, 60, "...")); ?>
                                                                </a>
                                                            </div>
                                                        </td>
                                                        <?php if ($user_role === 'admin'): ?>
                                                            <td class="article-author">
                                                                <div class="author-info">
                                                                    <i class="fas fa-user-circle"></i>
                                                                    <span class="author-name"><?php echo htmlspecialchars($article_item['author_name']); ?></span>
                                                                </div>
                                                            </td>
                                                        <?php endif; ?>
                                                        <td class="article-categories">
                                                            <?php if (!empty($article_item['categories'])): ?>
                                                                <div class="categories-container">
                                                                    <?php foreach (array_slice($article_item['categories'], 0, 2) as $category): ?>
                                                                        <span class="category-tag">
                                                                            <i class="fas fa-tag"></i>
                                                                            <?php echo htmlspecialchars($category->name); ?>
                                                                        </span>
                                                                    <?php endforeach; ?>
                                                                    <?php if (count($article_item['categories']) > 2): ?>
                                                                        <span class="category-more">+<?php echo count($article_item['categories']) - 2; ?></span>
                                                                    <?php endif; ?>
                                                                </div>
                                                            <?php else: ?>
                                                                <span class="no-categories">
                                                                    <i class="fas fa-minus"></i> None
                                                                </span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td class="article-date">
                                                            <time datetime="<?php echo htmlspecialchars($article_item['date']); ?>">
                                                                <?php echo htmlspecialchars(date('M j, Y', strtotime($article_item['date']))); ?>
                                                            </time>
                                                        </td>
                                                        <td class="article-status">
                                                            <span class="status-badge status-<?php echo strtolower(htmlspecialchars($article_item['status'])); ?>">
                                                                <?php echo htmlspecialchars(ucfirst($article_item['status'])); ?>
                                                            </span>
                                                        </td>
                                                        <td class="article-actions">
                                                            <?php if ($user_role === 'admin' || $article_item['user_id'] == $current_user_id): ?>
                                                                <div class="action-buttons-group">
                                                                    <a href="/index.php?page=edit_article&id=<?php echo $article_item['id']; ?>"
                                                                       class="btn-action btn-edit"
                                                                       title="Edit Article">
                                                                        <i class="fas fa-edit"></i>
                                                                    </a>

                                                                    <!-- Moderation buttons for admins/editors -->
                                                                    <?php if (in_array($user_role, ['admin', 'editor']) && $article_item['status'] === 'pending_review'): ?>
                                                                        <button type="button"
                                                                                class="btn-action btn-approve"
                                                                                onclick="approveArticle(<?php echo $article_item['id']; ?>)"
                                                                                title="Approve Article">
                                                                            <i class="fas fa-check"></i>
                                                                        </button>
                                                                        <button type="button"
                                                                                class="btn-action btn-reject"
                                                                                onclick="showRejectModal(<?php echo $article_item['id']; ?>, '<?php echo htmlspecialchars($article_item['title']); ?>')"
                                                                                title="Reject Article">
                                                                            <i class="fas fa-times"></i>
                                                                        </button>
                                                                    <?php endif; ?>

                                                                    <!-- Revoke the approval button for published articles -->
                                                                    <?php if (in_array($user_role, ['admin', 'editor']) && $article_item['status'] === 'published'): ?>
                                                                        <form action="/index.php?page=manage_articles" method="POST" class="revoke-approval-form" style="display: inline;">
                                                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                                                            <input type="hidden" name="article_id" value="<?php echo $article_item['id']; ?>">
                                                                            <input type="hidden" name="action" value="revoke_approval">
                                                                            <input type="hidden" name="review_notes" value="Publication revoked by moderator">
                                                                            <button type="submit"
                                                                                    class="btn-action btn-revoke"
                                                                                    title="Revoke Approval"
                                                                                    onclick="return confirm('Are you sure you want to revoke approval for this article? It will be moved back to draft status.')">
                                                                                <i class="fas fa-undo"></i>
                                                                            </button>
                                                                        </form>
                                                                    <?php endif; ?>

                                                                    <!-- Submit for a review button for authors -->
                                                                    <?php if ($article_item['status'] === 'draft' || $article_item['status'] === 'rejected'): ?>
                                                                        <form action="/index.php?page=manage_articles" method="POST" class="submit-for-review-form" style="display: inline;">
                                                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                                                            <input type="hidden" name="article_id" value="<?php echo $article_item['id']; ?>">
                                                                            <input type="hidden" name="action" value="submit_for_review">
                                                                            <button type="submit" class="btn-action btn-submit-review" title="Submit for Review">
                                                                                <i class="fas fa-paper-plane"></i>
                                                                            </button>
                                                                        </form>
                                                                    <?php endif; ?>

                                                                    <form action="/index.php?page=delete_article"
                                                                          method="POST"
                                                                          class="delete-form"
                                                                          style="display: inline;"
                                                                          onsubmit="return confirm('Are you sure you want to delete this article?');">
                                                                        <input type="hidden" name="article_id" value="<?php echo $article_item['id']; ?>">
                                                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                                                        <button type="submit"
                                                                                class="btn-action btn-delete"
                                                                                title="Delete Article">
                                                                            <i class="fas fa-trash"></i>
                                                                        </button>
                                                                    </form>
                                                                </div>
                                                            <?php else: ?>
                                                                <span class="view-only-badge">
                                                                    <i class="fas fa-eye"></i> View Only
                                                                </span>
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <!-- Table Footer -->
                                <div class="table-footer">
                                    <div class="results-info">
                                        <span id="resultsInfo">Showing <?php echo count($articles_view_data); ?> articles</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </main>

            <!-- Compact Sidebar -->
            <aside class="sidebar-content" style="min-width: 280px; max-width: 320px;">
                <!-- Quick Actions Card -->
                <div class="card card-compact sidebar-card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-bolt"></i> Quick Actions
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="quick-actions-grid">
                            <a href="/index.php?page=create_article" class="quick-action-item">
                                <div class="action-icon">
                                    <i class="fas fa-plus"></i>
                                </div>
                                <div class="action-content">
                                    <span class="action-title">New Article</span>
                                    <span class="action-description">Create content</span>
                                </div>
                            </a>

                            <?php if ($user_role === 'admin'): ?>
                                <a href="/index.php?page=manage_categories" class="quick-action-item">
                                    <div class="action-icon">
                                        <i class="fas fa-tags"></i>
                                    </div>
                                    <div class="action-content">
                                        <span class="action-title">Categories</span>
                                        <span class="action-description">Organize content</span>
                                    </div>
                                </a>
                            <?php endif; ?>

                            <a href="/index.php?page=dashboard" class="quick-action-item">
                                <div class="action-icon">
                                    <i class="fas fa-tachometer-alt"></i>
                                </div>
                                <div class="action-content">
                                    <span class="action-title">Dashboard</span>
                                    <span class="action-description">Overview</span>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Statistics Card -->
                <div class="card card-compact sidebar-card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-chart-line"></i> Stats
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="stats-grid">
                            <div class="stat-item-enhanced">
                                <div class="stat-icon">
                                    <i class="fas fa-file-alt"></i>
                                </div>
                                <div class="stat-info">
                                    <span class="stat-number"><?php echo count($articles_view_data); ?></span>
                                    <span class="stat-label">Total</span>
                                </div>
                            </div>

                            <div class="stat-item-enhanced">
                                <div class="stat-icon">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <div class="stat-info">
                                    <span class="stat-number">
                                        <?php
                                        $recent_articles = array_filter($articles_view_data, function($article) {
                                            return strtotime($article['date']) > strtotime('-7 days');
                                        });
                                        echo count($recent_articles);
                                        ?>
                                    </span>
                                    <span class="stat-label">This Week</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </aside>
        </div>
    </div>

    <!-- Reject Modal -->
    <div id="rejectModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Reject Article</h3>
                <button type="button" class="modal-close" onclick="closeRejectModal()">&times;</button>
            </div>
            <div class="modal-body">
                <p>You are about to reject the article: <strong id="rejectArticleTitle"></strong></p>

                <div class="form-group">
                    <label for="rejectNotes">Reason for rejection (required):</label>
                    <textarea id="rejectNotes" rows="4" class="form-control"
                              placeholder="Please provide a clear explanation for why this article is being rejected..." required></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeRejectModal()">Cancel</button>
                <button type="button" class="btn btn-danger" onclick="submitReject()">Reject Article</button>
            </div>
        </div>
    </div>

    <!-- Load external JavaScript -->
    <script src="/themes/default/js/pages/manage-articles.js"></script>

    <script>
    // Global variables for moderation
    let currentArticleId = null;
    const csrfToken = '<?php echo htmlspecialchars($csrf_token); ?>';

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
            .then(response => response.text())
            .then(() => {
                // Show success message and reload page
                location.reload();
            })
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
        document.getElementById('rejectModal').style.display = 'block';
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

        if (!currentArticleId) {
            alert('No article selected');
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
        .then(response => response.text())
        .then(() => {
            closeRejectModal();
            // Show success message and reload page
            location.reload();
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while rejecting the article');
        });
    }

    // Close modal when clicking outside
    window.onclick = function(event) {
        const rejectModal = document.getElementById('rejectModal');
        if (event.target === rejectModal) {
            closeRejectModal();
        }
    }

    // Update status filter on change
    document.getElementById('statusFilter').addEventListener('change', function() {
        const selectedStatus = this.value;
        window.location.href = '/index.php?page=manage_articles&filter=' + selectedStatus;
    });
    </script>
