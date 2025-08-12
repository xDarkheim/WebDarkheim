<?php

/**
 * Manage Categories Page
 *
 * This page allows admins to create, edit, and delete categories.
 *
 * @author Dmytro Hovenko
 */

declare(strict_types=1);

use App\Domain\Models\Category;

// Use global services from bootstrap.php
global $flashMessageService, $database_handler, $auth;

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check authentication and admin rights
if (!$auth || !$auth->isAuthenticated() || !$auth->hasRole('admin')) {
    if (isset($flashMessageService)) {
        $flashMessageService->addError("Access Denied. You do not have permission to view this page.");
    }
    header('Location: /index.php?page=login');
    exit();
}

// Check required services
if (!isset($flashMessageService)) {
    error_log("Critical: FlashMessageService not available in manage_categories.php");
    die("A critical system error occurred. Please try again later.");
}

if (!isset($database_handler)) {
    error_log("Critical: Database handler not available in manage_categories.php");
    $flashMessageService->addError("Database connection error. Please try again later.");
    header('Location: /index.php?page=dashboard');
    exit();
}

$page_title = "Manage Categories";

// --- Helper function to generate slugs ---
function generateSlug(string $text): string {
    // Remove unwanted characters
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    // Transliterate
    $text = iconv('utf-8', 'us-ascii//TRANSIT', $text);
    // Remove unwanted characters
    $text = preg_replace('~[^-\w]+~', '', $text);
    // Trim
    $text = trim($text, '-');
    // Remove duplicate -
    $text = preg_replace('~-+~', '-', $text);
    // Lowercase
    $text = strtolower($text);
    if (empty($text)) {
        return 'n-a-' . substr(md5((string)time()), 0, 6);
    }
    return $text;
}

// --- Handle POST requests (Add/Delete Category) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token_manage_categories'] ?? '', $_POST['csrf_token'])) {
        $flashMessageService->addError("Invalid CSRF token. Action aborted.");
        header('Location: /index.php?page=manage_categories');
        exit();
    }

    // --- Add New Category ---
    if (isset($_POST['action']) && $_POST['action'] === 'add_category') {
        $category_name = trim(filter_input(INPUT_POST, 'category_name', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '');
        $category_slug = trim(filter_input(INPUT_POST, 'category_slug', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '');

        // Validation
        $validation_passed = true;

        if (empty($category_name)) {
            $flashMessageService->addError("Category name cannot be empty.");
            $validation_passed = false;
        }

        if ($validation_passed) {
            if (empty($category_slug)) {
                $category_slug = generateSlug($category_name);
            } else {
                if (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $category_slug)) {
                    $flashMessageService->addError("Slug can only contain lowercase letters, numbers, and hyphens, and cannot start or end with a hyphen.");
                    $validation_passed = false;
                }
            }
        }

        if ($validation_passed) {
            // Use Category model to check existence
            if (Category::existsByNameOrSlugExcludingId($database_handler, $category_name, $category_slug, 0)) {
                $flashMessageService->addError("A category with this name or slug already exists.");
            } else {
                try {
                    // Use Category model to create
                    $newCategory = Category::create($database_handler, $category_name, $category_slug);
                    if ($newCategory) {
                        $flashMessageService->addSuccess("Category '$category_name' added successfully.");
                        header('Location: /index.php?page=manage_categories');
                        exit();
                    } else {
                        $flashMessageService->addError("Failed to add category. Database error.");
                        error_log("Manage Categories - Add: Failed to create category");
                    }
                } catch (Exception $e) {
                    $flashMessageService->addError("Database error adding category: " . $e->getMessage());
                    error_log("Manage Categories - Add Exception: " . $e->getMessage());
                }
            }
        }
    }
    // --- Delete Category ---
    elseif (isset($_POST['action']) && $_POST['action'] === 'delete_category' && isset($_POST['category_id_to_delete'])) {
        $category_id_to_delete = filter_var($_POST['category_id_to_delete'], FILTER_VALIDATE_INT);
        if ($category_id_to_delete) {
            try {
                $success = Category::deleteById($database_handler, $category_id_to_delete);
                if ($success) {
                    $flashMessageService->addSuccess("Category (ID: $category_id_to_delete) deleted successfully.");
                    header('Location: /index.php?page=manage_categories');
                    exit();
                } else {
                    $flashMessageService->addError("Failed to delete category or category not found.");
                    error_log("Manage Categories - Delete: Failed for ID $category_id_to_delete");
                }
            } catch (Exception $e) {
                $flashMessageService->addError("Database error deleting category: " . $e->getMessage());
                error_log("Manage Categories - Delete Exception: " . $e->getMessage());
            }
        } else {
            $flashMessageService->addError("Invalid category ID for deletion.");
        }
    }

    // Redirect after POST to prevent resubmission
    header('Location: /index.php?page=manage_categories');
    exit();
}

// --- Generate CSRF token for forms ---
if (empty($_SESSION['csrf_token_manage_categories'])) {
    $_SESSION['csrf_token_manage_categories'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token_manage_categories'];

// --- Fetch all categories for display ---
$categories = [];
try {
    $categories = Category::findAll($database_handler);
} catch (Exception $e) {
    $flashMessageService->addError("Error fetching categories: " . $e->getMessage());
    error_log("Manage Categories - Fetch Exception: " . $e->getMessage());
}
?>

    <div class="admin-layout page-manage-categories">
        <!-- Enhanced Main Header Section -->
        <header class="page-header">
            <div class="page-header-content">
                <div class="page-header-main">
                    <h1 class="page-title">
                        <i class="fas fa-tags"></i>
                        <?php echo htmlspecialchars($page_title); ?>
                    </h1>
                    <div class="page-header-description">
                        <p>Organize and manage content categories</p>
                    </div>
                </div>
                <div class="page-header-actions">
                    <a href="/index.php?page=manage_articles" class="btn btn-articles">
                        <i class="fas fa-newspaper"></i>
                        <span>Articles</span>
                    </a>
                    <a href="/index.php?page=create_article" class="btn btn-create-article">
                        <i class="fas fa-plus"></i>
                        <span>New Article</span>
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

        <!-- Main Content Layout -->
        <div class="content-layout">
            <!-- Primary Content Area -->
            <main class="main-content" style="flex: 1; max-width: none;">
                <!-- Category Creation Form First -->
                <div class="form-section" id="create-category-form">
                    <div class="card card-primary">
                        <div class="card-header card-header-enhanced">
                            <div class="card-header-content">
                                <h2 class="card-title">
                                    <i class="fas fa-plus-circle"></i> Add New Category
                                </h2>
                                <p class="card-subtitle">Create a new category to organize your articles</p>
                            </div>
                        </div>
                        <div class="card-body">
                            <form action="/index.php?page=manage_categories" method="POST" class="category-form">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                <input type="hidden" name="action" value="add_category">

                                <!-- Form Content -->
                                <div class="form-grid">
                                    <div class="form-group form-group-primary">
                                        <label for="category_name" class="form-label form-label-prominent">
                                            Category Name <span class="required-indicator">*</span>
                                        </label>
                                        <input type="text" id="category_name" name="category_name"
                                               class="form-control form-control-prominent"
                                               placeholder="Enter a descriptive category name (e.g., Technology, Programming)"
                                               required>
                                        <div class="form-help-text">
                                            <i class="fas fa-lightbulb"></i>
                                            Choose a clear, descriptive name that helps users understand the content type
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label for="category_slug" class="form-label">
                                            URL Slug <span class="optional-indicator">(Optional)</span>
                                        </label>
                                        <input type="text" id="category_slug" name="category_slug" class="form-control"
                                               placeholder="e.g., technology, programming (auto-generated if empty)">
                                        <div class="form-help-text">
                                            <i class="fas fa-link"></i>
                                            URL-friendly version using lowercase letters, numbers, and hyphens
                                        </div>
                                    </div>
                                </div>

                                <!-- Action Buttons -->
                                <div class="form-actions">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i>
                                        <span>Create Category</span>
                                    </button>
                                    <button type="reset" class="btn btn-secondary" onclick="clearForm()">
                                        <i class="fas fa-eraser"></i>
                                        <span>Clear</span>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <?php if (!empty($categories)): ?>
                    <!-- Categories List Section -->
                    <div class="categories-section" style="margin-top: 2rem;">
                        <div class="card">
                            <div class="card-header card-header-enhanced">
                                <div class="card-header-content">
                                    <h2 class="card-title">
                                        <i class="fas fa-list"></i>
                                        Existing Categories
                                        <span class="categories-count">(<?php echo count($categories); ?>)</span>
                                    </h2>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="table-container">
                                    <div class="table-responsive">
                                        <table class="categories-table">
                                            <thead>
                                                <tr>
                                                    <th class="col-id">
                                                        <span class="th-content">
                                                            <i class="fas fa-hashtag"></i> ID
                                                        </span>
                                                    </th>
                                                    <th class="col-name">
                                                        <span class="th-content">
                                                            <i class="fas fa-tag"></i> Name
                                                        </span>
                                                    </th>
                                                    <th class="col-slug">
                                                        <span class="th-content">
                                                            <i class="fas fa-link"></i> URL Slug
                                                        </span>
                                                    </th>
                                                    <th class="col-date">
                                                        <span class="th-content">
                                                            <i class="fas fa-calendar"></i> Created
                                                        </span>
                                                    </th>
                                                    <th class="col-actions">
                                                        <span class="th-content">
                                                            <i class="fas fa-cogs"></i> Actions
                                                        </span>
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($categories as $category): ?>
                                                    <tr class="category-row">
                                                        <td class="category-id">
                                                            <span class="id-badge"><?php echo htmlspecialchars((string)$category->id); ?></span>
                                                        </td>
                                                        <td class="category-name">
                                                            <div class="name-container">
                                                                <strong class="category-name-text"><?php echo htmlspecialchars($category->name); ?></strong>
                                                            </div>
                                                        </td>
                                                        <td class="category-slug">
                                                            <code class="slug-code"><?php echo htmlspecialchars($category->slug ?? 'N/A'); ?></code>
                                                        </td>
                                                        <td class="category-date">
                                                            <div class="date-container">
                                                                <time datetime="<?php echo htmlspecialchars($category->created_at ?? ''); ?>">
                                                                    <?php echo htmlspecialchars($category->created_at ? date('M j, Y', strtotime($category->created_at)) : 'N/A'); ?>
                                                                </time>
                                                            </div>
                                                        </td>
                                                        <td class="category-actions">
                                                            <div class="action-buttons-group">
                                                                <a href="/index.php?page=edit_category&id=<?php echo $category->id; ?>"
                                                                   class="btn-action btn-edit"
                                                                   title="Edit Category">
                                                                    <i class="fas fa-edit"></i>
                                                                </a>
                                                                <form action="/index.php?page=manage_categories" method="POST"
                                                                      class="delete-form"
                                                                      onsubmit="return confirm('Are you sure you want to delete this category?');">
                                                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                                                    <input type="hidden" name="category_id_to_delete" value="<?php echo $category->id; ?>">
                                                                    <input type="hidden" name="action" value="delete_category">
                                                                    <button type="submit" class="btn-action btn-delete" title="Delete Category">
                                                                        <i class="fas fa-trash"></i>
                                                                    </button>
                                                                </form>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Empty State for when no categories exist -->
                    <div class="empty-state-wrapper" style="margin-top: 2rem;">
                        <div class="card">
                            <div class="card-body">
                                <div class="empty-state">
                                    <div class="empty-state-visual">
                                        <div class="empty-state-icon">
                                            <i class="fas fa-folder-open"></i>
                                        </div>
                                    </div>
                                    <div class="empty-state-content">
                                        <h3 class="empty-state-title">Ready to Create Categories</h3>
                                        <p class="empty-state-description">
                                            Use the form above to create your first category. Categories help organize your content and make it easier for readers to find what they're looking for.
                                        </p>
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
                            <a href="/index.php?page=manage_articles" class="quick-action-item">
                                <div class="action-icon">
                                    <i class="fas fa-newspaper"></i>
                                </div>
                                <div class="action-content">
                                    <span class="action-title">Articles</span>
                                    <span class="action-description">Manage content</span>
                                </div>
                            </a>

                            <a href="/index.php?page=create_article" class="quick-action-item">
                                <div class="action-icon">
                                    <i class="fas fa-plus"></i>
                                </div>
                                <div class="action-content">
                                    <span class="action-title">New Article</span>
                                    <span class="action-description">Create content</span>
                                </div>
                            </a>

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

                <!-- Category Tips Card -->
                <div class="card card-compact sidebar-card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-lightbulb"></i> Tips
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="tips-list">
                            <div class="tip-item">
                                <h4><i class="fas fa-tag"></i> Clear Names</h4>
                                <p>Use descriptive names that help users understand what content belongs in this category.</p>
                            </div>
                            <div class="tip-item">
                                <h4><i class="fas fa-link"></i> URL Slugs</h4>
                                <p>Slugs create clean URLs. They're automatically generated, but you can customize them.</p>
                            </div>
                            <div class="tip-item">
                                <h4><i class="fas fa-sitemap"></i> Organization</h4>
                                <p>Think about how readers will browse your content when creating categories.</p>
                            </div>
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
                                    <i class="fas fa-tags"></i>
                                </div>
                                <div class="stat-info">
                                    <span class="stat-number"><?php echo count($categories); ?></span>
                                    <span class="stat-label">Categories</span>
                                </div>
                            </div>

                            <div class="stat-item-enhanced">
                                <div class="stat-icon">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <div class="stat-info">
                                    <span class="stat-number">
                                        <?php
                                        $recent_categories = array_filter($categories, function($category) {
                                            return $category->created_at && strtotime($category->created_at) > strtotime('-7 days');
                                        });
                                        echo count($recent_categories);
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

    <!-- Load external JavaScript -->
    <script src="/themes/default/js/pages/manage-categories.js"></script>
