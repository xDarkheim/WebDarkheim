<?php

/**
 * Create Article Page
 *
 * This page allows users to create a new article.
 * It includes a form for article details, categories, and content.
 * The form is validated and saved to the database.
 *
 * @author Dmytro Hovenko
 */

declare(strict_types=1);

use App\Domain\Models\Article;
use App\Domain\Models\Category;
use App\Domain\Models\SiteSettings;
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
$page_title = 'Create Article';

// Check for required services
if (!isset($database_handler)) {
    error_log("Critical: Database handler not available in create_article.php");
    die("A critical system error occurred. Please try again later.");
}
if (!isset($flashMessageService)) {
    error_log("Critical: FlashMessageService not available in create_article.php");
    die("A critical system error occurred. Please try again later.");
}
if (!isset($container)) {
    error_log("Critical: Container not available in create_article.php");
    die("A critical system error occurred. Please try again later.");
}

// Check authorization via new AuthenticationService
if (!$authService->isAuthenticated()) {
    $flashMessageService->addError('Please log in to create articles.');
    header("Location: /index.php?page=login");
    exit();
}

// Get CSRF token via global system
$csrf_token = CSRFMiddleware::getToken();

// Get user data via AuthenticationService
$current_user_id = $authService->getCurrentUserId();
$user_role = $authService->getCurrentUserRole();
$currentUser = $authService->getCurrentUser();

// Get moderation settings
$moderation_settings = SiteSettings::getAll($database_handler);

// Function to check if moderation is required

$all_categories = Category::findAll($database_handler);

// Variables to restore form on errors
$form_data = [
    'title' => '',
    'short_description' => '',
    'full_text' => '',
    'date' => date('Y-m-d'),
    'categories' => []
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token via global system
    if (!CSRFMiddleware::validateQuick()) {
        $flashMessageService->addError('Invalid CSRF token. Please try again.');
        header('Location: /index.php?page=create_article');
        exit;
    }

    $title = trim($_POST['title'] ?? '');

    // Use TextEditorComponent for safe HTML content processing
    $short_description = $textEditorComponent->sanitizeContent($_POST['short_description'] ?? '');
    $full_text = $textEditorComponent->sanitizeContent($_POST['full_text'] ?? '');

    $date_input = trim(filter_input(INPUT_POST, 'date', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '');
    $selected_category_ids = filter_input(INPUT_POST, 'categories', FILTER_VALIDATE_INT, FILTER_REQUIRE_ARRAY) ?? [];

    // Determine user action - save as draft or publish
    $action = $_POST['action'] ?? 'publish';
    $is_draft = ($action === 'save_draft');

    // Determine article status based on user action and moderation settings
    if ($is_draft) {
        $status = 'draft';
    } else {
        // Check if moderation is required for this user
        $requires_moderation = true; // Moderation required by default

        // Admins and editors do not require moderation
        if (in_array($user_role, ['admin', 'editor'])) {
            $requires_moderation = false;
        }

        // Check moderation settings from database
        $moderation_enabled = $moderation_settings['content_moderation_enabled'] ?? true;
        $auto_publish_roles = $moderation_settings['auto_publish_roles'] ?? ['admin', 'editor'];

        // If moderation is disabled, publish automatically
        if (!$moderation_enabled) {
            $requires_moderation = false;
        }

        // If a user role is in auto-publish list, no moderation needed
        if (in_array($user_role, $auto_publish_roles)) {
            $requires_moderation = false;
        }

        if ($requires_moderation) {
            $status = 'pending_review';
        } else {
            $status = 'published';
        }
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
    if ($is_draft) {
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
        $articleData = [
            'title' => $title,
            'short_description' => $short_description,
            'full_text' => $full_text,
            'date' => $date_input,
            'user_id' => $current_user_id,
            'status' => $status
        ];

        try {
            // Create an ArticleRepository instance
            $articleRepository = new ArticleRepository($database_handler);

            // Create a new Article
            $newArticle = Article::fromArray($articleData);

            // Save article via repository
            if ($newArticle->save($articleRepository)) {
                $newArticleId = $newArticle->id;

                // Set categories if selected
                if (!empty($selected_category_ids)) {
                    $articleRepository->setCategories($newArticle, $selected_category_ids);
                }

                if ($is_draft) {
                    $flashMessageService->addSuccess('Article saved as draft successfully! You can continue editing it later.');
                    header('Location: /index.php?page=edit_article&id=' . $newArticle->id);
                } elseif ($status === 'pending_review') {
                    $flashMessageService->addSuccess('Article submitted for review! It will be published after approval by a moderator.');
                    header('Location: /index.php?page=manage_articles');
                } else {
                    $flashMessageService->addSuccess('Article published successfully!');
                    header('Location: /index.php?page=news&id=' . $newArticle->id);
                }
                exit;
            } else {
                $flashMessageService->addError('Failed to create article. An internal error occurred or database issue.');
            }
        } catch (Exception $e) {
            $flashMessageService->addError('Database error while creating article: ' . $e->getMessage());
            error_log("Create Article - Exception: " . $e->getMessage());
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
                        <i class="fas fa-plus-circle"></i>
                        <?php echo htmlspecialchars($page_title); ?>
                    </h1>
                    <div class="page-header-description">
                        <p>Create a new article and share your content with readers</p>
                    </div>
                </div>
                <div class="page-header-actions">
                    <a href="/index.php?page=manage_articles" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Articles
                    </a>
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
                                <i class="fas fa-edit"></i> Article Details
                            </h2>
                            <div class="card-header-meta">
                                <small class="creation-date">
                                    <i class="fas fa-calendar-plus"></i>
                                    Started: <?php echo date('M j, Y \a\t g:i A'); ?>
                                </small>
                                <small class="author-info">
                                    <i class="fas fa-user"></i>
                                    Author: <?php echo htmlspecialchars($currentUser['username'] ?? 'Unknown'); ?>
                                </small>
                                <span class="status-badge status-draft">
                                    <i class="fas fa-file-alt"></i> Draft
                                </span>
                            </div>
                        </div>
                        <div class="card-body">
                            <form action="/index.php?page=create_article" method="POST" enctype="multipart/form-data" class="article-creation-form">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
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
                                    <input type="date" id="date" name="date" class="form-control" value="<?php echo htmlspecialchars($form_data['date']); ?>" min="<?php echo date('Y-m-d'); ?>" required>
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
                                    <button type="submit" name="action" value="publish" class="btn ca-btn-publish">
                                        <i class="fas fa-rocket"></i> Publish Article
                                    </button>
                                    <button type="submit" name="action" value="save_draft" class="btn ca-btn-save-draft">
                                        <i class="fas fa-save"></i> Save as Draft
                                    </button>
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
                                <span class="progress-text">Add title</span>
                            </div>
                            <div class="ca-progress-item" data-target="full_text">
                                <i class="fas fa-circle ca-progress-icon"></i>
                                <span class="progress-text">Write content</span>
                            </div>
                            <div class="ca-progress-item" data-target="short_description">
                                <i class="fas fa-circle ca-progress-icon"></i>
                                <span class="progress-text">Add preview</span>
                            </div>
                            <div class="ca-progress-item" data-target="categories">
                                <i class="fas fa-circle ca-progress-icon"></i>
                                <span class="progress-text">Select categories</span>
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
                                    <span class="ca-stat-number" id="writingTime">0</span>
                                    <span class="ca-stat-label">Min writing</span>
                                </div>
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

        <!-- Focus Mode Overlay -->
        <div id="focusOverlay" class="ca-focus-overlay">
            <div class="ca-focus-header">
                <h3>Focus Mode</h3>
                <button class="ca-focus-exit" onclick="exitFocusMode()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <label for="focusEditor"></label><textarea id="focusEditor" class="ca-focus-editor" placeholder="Focus on your writing..."></textarea>
            <div class="ca-focus-stats">
                <span id="focusWordCount">0 words</span>
                <span>•</span>
                <span id="focusTime">0 min</span>
            </div>
        </div>
    </div>

    <!-- Connect text editor scripts -->
    <?php include ROOT_PATH . '/resources/views/_editor_scripts.php'; ?>

    <!-- Connect simplified JavaScript -->
    <script src="/themes/default/js/create-article.js"></script>
