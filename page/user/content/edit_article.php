<?php

/**
 * Edit Article Page
 *
 * This page allows users to edit existing articles.
 *
 * @author Dmytro Hovenko
 */

declare(strict_types=1);

use App\Domain\Models\Category;
use App\Application\Middleware\CSRFMiddleware;
use App\Domain\Repositories\ArticleRepository;

// Use global services from the new DI architecture
global $flashMessageService, $database_handler, $container, $serviceProvider;

// Get AuthenticationService instead of direct SessionManager access
try {
    $authService = $serviceProvider->getAuth();
    $textEditorComponent = $serviceProvider->getTextEditorComponent();
} catch (Exception $e) {
    error_log("Critical: Failed to get services from ServiceProvider: " . $e->getMessage());
    die("A critical system error occurred. Please try again later.");
}

// Set page title
$page_title = 'Edit Article';

// Check for required services
if (!isset($database_handler)) {
    error_log("Critical: Database handler not available in edit_article.php");
    die("A critical system error occurred. Please try again later.");
}
if (!isset($flashMessageService)) {
    error_log("Critical: FlashMessageService not available in edit_article.php");
    die("A critical system error occurred. Please try again later.");
}
if (!isset($container)) {
    error_log("Critical: Container not available in edit_article.php");
    die("A critical system error occurred. Please try again later.");
}

// Check authorization via new AuthenticationService
if (!$authService->isAuthenticated()) {
    $flashMessageService->addError('Please log in to edit articles.');
    header("Location: /index.php?page=login");
    exit();
}

// Use CSRF token from session (created in bootstrap.php)
$csrf_token = CSRFMiddleware::getToken();

// Get user data via AuthenticationService
$current_user_id = $authService->getCurrentUserId();
$user_role = $authService->getCurrentUserRole();
$currentUser = $authService->getCurrentUser();

$all_categories = Category::findAll($database_handler);

// Initialize variable for current article status
$current_article_status = 'published'; // default

// Variables to restore form on errors
$form_data = [
    'title' => '',
    'short_description' => '',
    'full_text' => '',
    'date' => date('Y-m-d'),
    'categories' => []
];

$article_id = null;

// Get article ID from GET parameter
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $article_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

    if (!$article_id) {
        $flashMessageService->addError('No article ID specified or invalid ID.');
        header('Location: /index.php?page=manage_articles');
        exit;
    }

    try {
        $articleRepository = new ArticleRepository($database_handler);
        $article_object = $articleRepository->findById($article_id);

        if (!$article_object) {
            $flashMessageService->addError('Article not found.');
            header('Location: /index.php?page=manage_articles');
            exit;
        }

        if ($article_object->user_id != $current_user_id && $user_role !== 'admin') {
            $flashMessageService->addError('You do not have permission to edit this article.');
            header('Location: /index.php?page=manage_articles');
            exit;
        }

        // Load article data into form
        $form_data = [
            'title' => $article_object->title,
            'short_description' => $article_object->short_description,
            'full_text' => $article_object->full_text,
            'date' => $article_object->date,
            'categories' => array_map(fn($cat) => $cat->id, $articleRepository->getCategories($article_object))
        ];

        // Update current article status
        $current_article_status = $article_object->status;

    } catch (PDOException $e) {
        $flashMessageService->addError('Database error while fetching article.');
        error_log("PDOException in edit_article.php (GET) for article ID $article_id: " . $e->getMessage());
        header('Location: /index.php?page=manage_articles');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token via global system
    if (!CSRFMiddleware::validateQuick()) {
        $flashMessageService->addError('Invalid CSRF token. Please try again.');
        header('Location: /index.php?page=edit_article&id=' . ($article_id ?? ''));
        exit;
    }

    $article_id = filter_input(INPUT_POST, 'article_id', FILTER_VALIDATE_INT);
    $title = trim($_POST['title'] ?? '');

    // Use TextEditorComponent for safe HTML content processing
    $short_description = $textEditorComponent->sanitizeContent($_POST['short_description'] ?? '');
    $full_text = $textEditorComponent->sanitizeContent($_POST['full_text'] ?? '');

    $date_input = trim(filter_input(INPUT_POST, 'date', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '');
    $selected_category_ids = filter_input(INPUT_POST, 'categories', FILTER_VALIDATE_INT, FILTER_REQUIRE_ARRAY) ?? [];

    // Determine user action
    $action = $_POST['action'] ?? 'update';
    $is_draft = ($action === 'save_draft');
    $is_publish = ($action === 'publish');

    // Determine new status
    if ($is_draft) {
        $status = 'draft';
    } elseif ($is_publish) {
        $status = 'published';
    } else {
        // Keep current article status
        $articleRepository = new ArticleRepository($database_handler);
        $current_article = $articleRepository->findById($article_id);
        $status = $current_article ? $current_article->status : 'published';
    }

    // Save form data for restoring on errors
    $form_data = [
        'title' => $title,
        'short_description' => $short_description,
        'full_text' => $full_text,
        'date' => $date_input,
        'categories' => $selected_category_ids
    ];

    // Validation via FlashMessageService
    $validation_passed = true;

    // Softer requirements for drafts
    if ($status === 'draft') {
        // Draft requires only a title
        if (empty($title)) {
            $flashMessageService->addError('Title is required even for drafts.');
            $validation_passed = false;
        }
        // For drafts, set today's date automatically if not specified
        if (empty($date_input)) {
            $date_input = date('Y-m-d');
            $form_data['date'] = $date_input;
        }
    } else {
        // Stricter requirements for publishing
        if (empty($title)) {
            $flashMessageService->addError('Title is required.');
            $validation_passed = false;
        }
        if (empty($full_text)) {
            $flashMessageService->addError('Full text is required.');
            $validation_passed = false;
        }
        if (empty($date_input)) {
            $flashMessageService->addError('Publication date is required.');
            $validation_passed = false;
        } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_input) || !strtotime($date_input)) {
            $flashMessageService->addError('Invalid date format. Please use YYYY-MM-DD.');
            $validation_passed = false;
        }
    }

    if ($validation_passed) {
        if (!$article_id) {
            $flashMessageService->addError('Invalid article ID for update.');
            header('Location: /index.php?page=manage_articles');
            exit;
        }

        try {
            // Check edit permissions
            $stmt_check_author = $database_handler->getConnection()->prepare("SELECT user_id, status FROM articles WHERE id = ?");
            $stmt_check_author->execute([$article_id]);
            $article_info = $stmt_check_author->fetch(PDO::FETCH_ASSOC);

            if ($article_info === false) {
                $flashMessageService->addError('Article not found for update.');
                header('Location: /index.php?page=manage_articles');
                exit;
            }
            if ($article_info['user_id'] != $current_user_id && $user_role !== 'admin') {
                $flashMessageService->addError('You do not have permission to edit this article.');
                header('Location: /index.php?page=manage_articles');
                exit;
            }

            // Update article
            $sql = "UPDATE articles SET title = ?, short_description = ?, full_text = ?, date = ?, status = ?, updated_at = NOW() WHERE id = ?";
            $params = [$title, $short_description, $full_text, $date_input, $status, $article_id];

            $stmt_update = $database_handler->getConnection()->prepare($sql);
            if ($stmt_update->execute($params)) {
                // Update categories
                $articleRepository = new ArticleRepository($database_handler);
                $article_to_update_categories = $articleRepository->findById((int)$article_id);
                if ($article_to_update_categories) {
                    $articleRepository->setCategories($article_to_update_categories, $selected_category_ids);
                }

                // Success messages
                if ($is_draft) {
                    $flashMessageService->addSuccess('Article saved as draft successfully!');
                    header('Location: /index.php?page=edit_article&id=' . $article_id);
                } elseif ($is_publish) {
                    $flashMessageService->addSuccess('Article published successfully!');
                    header('Location: /index.php?page=news&id=' . $article_id);
                } else {
                    $flashMessageService->addSuccess('Article updated successfully!');
                    // Redirect depending on status
                    if ($status === 'draft') {
                        header('Location: /index.php?page=edit_article&id=' . $article_id);
                    } else {
                        header('Location: /index.php?page=news&id=' . $article_id);
                    }
                }
                exit;
            } else {
                $flashMessageService->addError('Failed to update article. Please try again.');
                error_log("Failed to update article ID: $article_id. PDO Error: " . print_r($stmt_update->errorInfo(), true));
            }
        } catch (Exception $e) {
            $flashMessageService->addError('Database error while updating article: ' . $e->getMessage());
            error_log("Edit Article - Exception: " . $e->getMessage());
        }
    }
}
?>

    <div class="admin-layout page-create-article">
        <!-- Enhanced Main Header Section -->
        <header class="page-header">
            <div class="page-header-content">
                <div class="page-header-main">
                    <h1 class="page-title">
                        <i class="fas fa-edit"></i>
                        <?php echo htmlspecialchars($page_title); ?>
                        <?php if ($article_id): ?>
                            <span class="article-id-badge">#<?php echo $article_id; ?></span>
                        <?php endif; ?>
                    </h1>
                    <div class="page-header-description">
                        <p>Edit and update your article content</p>
                    </div>
                </div>
                <div class="page-header-actions">
                    <a href="/index.php?page=manage_articles" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Articles
                    </a>
                    <?php if ($article_id): ?>
                        <a href="/index.php?page=news&id=<?php echo $article_id; ?>" class="btn btn-outline">
                            <i class="fas fa-eye"></i> Preview
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

        <!-- Main Content Layout - Reorganized -->
        <div class="ca-content-layout">
            <!-- Primary Content Area - More focused -->
            <main class="main-content" style="max-width: 800px;">
                <div class="form-wrapper">
                    <div class="card card-primary">
                        <div class="card-header">
                            <h2 class="card-title">
                                <i class="fas fa-edit"></i> Edit Article Details
                            </h2>
                            <div class="card-header-meta">
                                <small class="creation-date">
                                    <i class="fas fa-calendar-edit"></i>
                                    Editing: <?php echo date('M j, Y \a\t g:i A'); ?>
                                </small>
                                <small class="author-info">
                                    <i class="fas fa-user"></i>
                                    Author: <?php echo htmlspecialchars($currentUser['username'] ?? 'Unknown'); ?>
                                </small>
                                <span class="status-badge status-draft">
                                    <i class="fas fa-edit"></i> Editing
                                </span>
                            </div>
                        </div>
                        <div class="card-body">
                            <form action="/index.php?page=edit_article" method="POST" enctype="multipart/form-data" class="article-creation-form" data-current-status="<?php echo htmlspecialchars($current_article_status); ?>">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                <input type="hidden" name="article_id" value="<?php echo htmlspecialchars((string)($article_id ?? '')); ?>">

                                <div class="form-section">
                                    <label for="title" class="form-label">
                                        Article Title <span class="required-indicator">*</span>
                                    </label>
                                    <input type="text" id="title" name="title" class="form-control" value="<?php echo htmlspecialchars($form_data['title']); ?>" maxlength="200" required>
                                    <div class="character-counter"><span id="titleCounter">0</span>/200</div>
                                </div>

                                <div class="form-section">
                                    <label for="date" class="form-label">
                                        Publication Date <span class="required-indicator">*</span>
                                    </label>
                                    <input type="date" id="date" name="date" class="form-control" value="<?php echo htmlspecialchars($form_data['date']); ?>" required>
                                </div>

                                <div class="form-section">
                                    <label for="short_description" class="form-label">
                                        Article Preview <span class="optional-indicator">(Optional)</span>
                                    </label>
                                    <?php echo $textEditorComponent->renderBasicEditor('short_description', $form_data['short_description']); ?>
                                    <small class="form-text">A short preview with basic formatting that will be displayed in article listings</small>
                                </div>

                                <div class="form-section">
                                    <label for="full_text" class="form-label">
                                        Article Content <span class="required-indicator">*</span>
                                    </label>
                                    <?php echo $textEditorComponent->renderNewsEditor('full_text', $form_data['full_text']); ?>
                                    <small class="form-text">Use the toolbar above to format your text</small>
                                </div>

                                <div class="form-section">
                                    <label class="form-label">Categories</label>
                                    <?php if (!empty($all_categories)): ?>
                                        <div class="ca-category-selection">
                                            <label for="categorySearch"></label><input type="text" id="categorySearch" class="form-control ca-category-search" placeholder="Search categories...">
                                            <div class="ca-category-grid" id="categoryGrid">
                                                <?php foreach ($all_categories as $category): ?>
                                                    <div class="ca-category-option" data-category="<?php echo strtolower($category->name); ?>">
                                                        <input type="checkbox" id="category_<?php echo htmlspecialchars((string)$category->id); ?>" name="categories[]" value="<?php echo htmlspecialchars((string)$category->id); ?>" class="ca-category-checkbox" <?php echo in_array($category->id, $form_data['categories']) ? 'checked' : ''; ?>>
                                                        <label for="category_<?php echo htmlspecialchars((string)$category->id); ?>" class="ca-category-label">
                                                            <span class="category-name"><?php echo htmlspecialchars($category->name); ?></span>
                                                            <i class="fas fa-check ca-category-check"></i>
                                                        </label>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                            <div class="ca-selected-categories" id="selectedCategories">
                                                <strong>Selected:</strong> <span id="selectedCategoriesText">None</span>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <div class="no-categories-state">
                                            <i class="fas fa-folder-open"></i>
                                            <h4>No Categories Available</h4>
                                            <p>Categories help organize content and make it easier for readers to find.</p>
                                            <?php if ($user_role === 'admin'): ?>
                                                <a href="/index.php?page=manage_categories" class="btn btn-primary btn-small">
                                                    <i class="fas fa-plus"></i> Create Categories
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="ca-form-actions-redesigned">
                                    <?php
                                    // Get current article status to show correct buttons
                                    $current_article_status = 'published'; // default
                                    if ($article_id) {
                                        try {
                                            $articleRepository = new ArticleRepository($database_handler);
                                            $current_article = $articleRepository->findById($article_id);
                                            if ($current_article) {
                                                $current_article_status = $current_article->status;
                                            }
                                        } catch (Exception $e) {
                                            error_log("Error getting article status: " . $e->getMessage());
                                        }
                                    }
                                    ?>

                                    <?php if ($current_article_status === 'draft'): ?>
                                        <!-- For drafts show "Publish" and "Save Draft" buttons -->
                                        <button type="submit" name="action" value="publish" class="btn ca-btn-publish">
                                            <i class="fas fa-rocket"></i> Publish Article
                                        </button>
                                        <button type="submit" name="action" value="save_draft" class="btn ca-btn-save-draft">
                                            <i class="fas fa-save"></i> Save as Draft
                                        </button>
                                    <?php else: ?>
                                        <!-- For published articles show "Update" and "Save as Draft" -->
                                        <button type="submit" name="action" value="update" class="btn ca-btn-publish">
                                            <i class="fas fa-save"></i> Update Article
                                        </button>
                                        <button type="submit" name="action" value="save_draft" class="btn ca-btn-save-draft">
                                            <i class="fas fa-file-alt"></i> Save as Draft
                                        </button>
                                    <?php endif; ?>

                                    <a href="/index.php?page=manage_articles" class="btn ca-btn-cancel">
                                        <i class="fas fa-times"></i> Cancel
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </main>

            <!-- Enhanced Compact Sidebar -->
            <aside class="ca-sidebar-content" style="min-width: 280px; max-width: 320px;">
                <!-- Writing Progress Card -->
                <div class="card card-compact sidebar-card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-tasks"></i> Progress
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="ca-progress-overview">
                            <div class="ca-progress-bar-wrapper">
                                <div class="ca-progress-bar" id="overallProgress">
                                    <div class="ca-progress-fill"></div>
                                </div>
                                <span class="ca-progress-percentage" id="progressPercentage">0%</span>
                            </div>
                        </div>
                        <div class="ca-progress-checklist">
                            <div class="ca-progress-item" data-target="title">
                                <i class="fas fa-circle ca-progress-icon"></i>
                                <span class="progress-text">Update title</span>
                            </div>
                            <div class="ca-progress-item" data-target="full_text">
                                <i class="fas fa-circle ca-progress-icon"></i>
                                <span class="progress-text">Edit content</span>
                            </div>
                            <div class="ca-progress-item" data-target="short_description">
                                <i class="fas fa-circle ca-progress-icon"></i>
                                <span class="progress-text">Update preview</span>
                            </div>
                            <div class="ca-progress-item" data-target="categories">
                                <i class="fas fa-circle ca-progress-icon"></i>
                                <span class="progress-text">Review categories</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Writing Stats Card -->
                <div class="card card-compact sidebar-card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-chart-line"></i> Writing Stats
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="ca-writing-stats">
                            <div class="ca-stat-item">
                                <div class="ca-stat-icon">
                                    <i class="fas fa-font"></i>
                                </div>
                                <div class="ca-stat-info">
                                    <span class="ca-stat-number" id="sidebarWordCount">0</span>
                                    <span class="ca-stat-label">Words</span>
                                </div>
                            </div>
                            <div class="ca-stat-item">
                                <div class="ca-stat-icon">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <div class="ca-stat-info">
                                    <span class="ca-stat-number" id="sidebarReadTime">0</span>
                                    <span class="ca-stat-label">Min read</span>
                                </div>
                            </div>
                            <div class="ca-stat-item">
                                <div class="ca-stat-icon">
                                    <i class="fas fa-calendar"></i>
                                </div>
                                <div class="ca-stat-info">
                                    <span class="ca-stat-number" id="editingTime">0</span>
                                    <span class="ca-stat-label">Min editing</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Article Info Card -->
                <div class="card card-compact sidebar-card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-info-circle"></i> Article Info
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="ca-article-meta-info">
                            <?php if ($article_id): ?>
                                <div class="ca-meta-item">
                                    <span class="ca-meta-label">Article ID:</span>
                                    <span class="ca-meta-value">#<?php echo $article_id; ?></span>
                                </div>
                            <?php endif; ?>
                            <div class="ca-meta-item">
                                <span class="ca-meta-label">Status:</span>
                                <span class="ca-meta-value">Editing</span>
                            </div>
                            <div class="ca-meta-item">
                                <span class="ca-meta-label">Author:</span>
                                <span class="ca-meta-value"><?php echo htmlspecialchars($currentUser['username'] ?? 'Unknown'); ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Navigation Card -->
                <div class="card card-compact sidebar-card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-compass"></i> Quick Links
                        </h3>
                    </div>
                    <div class="card-body">
                        <nav class="ca-quick-nav">
                            <a href="/index.php?page=manage_articles" class="ca-quick-nav-link">
                                <i class="fas fa-list"></i>
                                <span>All Articles</span>
                            </a>
                            <?php if ($article_id): ?>
                                <a href="/index.php?page=news&id=<?php echo $article_id; ?>" class="ca-quick-nav-link">
                                    <i class="fas fa-eye"></i>
                                    <span>View Article</span>
                                </a>
                            <?php endif; ?>
                            <a href="/index.php?page=create_article" class="ca-quick-nav-link">
                                <i class="fas fa-plus"></i>
                                <span>New Article</span>
                            </a>
                            <a href="/index.php?page=dashboard" class="ca-quick-nav-link">
                                <i class="fas fa-tachometer-alt"></i>
                                <span>Dashboard</span>
                            </a>
                            <?php if ($user_role === 'admin'): ?>
                                <a href="/index.php?page=manage_categories" class="ca-quick-nav-link">
                                    <i class="fas fa-tags"></i>
                                    <span>Categories</span>
                                </a>
                            <?php endif; ?>
                        </nav>
                    </div>
                </div>
            </aside>
        </div>
    </div>

    <!-- Connect text editor scripts -->
    <?php include ROOT_PATH . '/resources/views/_editor_scripts.php'; ?>

    <!-- Connect simplified JavaScript -->
    <script src="/themes/default/js/create-article.js"></script>
