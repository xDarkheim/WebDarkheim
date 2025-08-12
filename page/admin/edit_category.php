<?php

/**
 * Edit Category Page
 *
 * This page allows admins to edit an existing category.
 *
 * @author Dmytro Hovenko
 */

declare(strict_types=1);

use App\Domain\Models\Category;
use App\Infrastructure\Lib\SlugGenerator;

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
    error_log("Critical: FlashMessageService not available in edit_category.php");
    die("A critical system error occurred. Please try again later.");
}

if (!isset($database_handler)) {
    error_log("Critical: Database handler not available in edit_category.php");
    $flashMessageService->addError("Database connection error. Please try again later.");
    header('Location: /index.php?page=manage_categories');
    exit();
}

$page_title = "Edit Category";
$category = null;
$category_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$category_id) {
    $flashMessageService->addError("Invalid category ID specified.");
    header('Location: /index.php?page=manage_categories');
    exit();
}

// --- Handle POST request (Update Category) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token_edit_category_' . $category_id] ?? '', $_POST['csrf_token'])) {
        $flashMessageService->addError("Invalid CSRF token. Action aborted.");
        header('Location: /index.php?page=edit_category&id=' . $category_id);
        exit();
    }

    $posted_category_id = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);
    if ($posted_category_id !== $category_id) {
        $flashMessageService->addError("Category ID mismatch. Action aborted.");
        header('Location: /index.php?page=edit_category&id=' . $category_id);
        exit();
    }

    $updated_name = trim(filter_input(INPUT_POST, 'category_name', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '');
    $updated_slug = trim(filter_input(INPUT_POST, 'category_slug', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '');

    // Validation
    $validation_passed = true;

    if (empty($updated_name)) {
        $flashMessageService->addError("Category name cannot be empty.");
        $validation_passed = false;
    }

    if (empty($updated_slug)) {
        $updated_slug = SlugGenerator::generate($updated_name);
    } else {
        if (!SlugGenerator::isValid($updated_slug)) {
            $flashMessageService->addError("Slug can only contain lowercase letters, numbers, and hyphens, and cannot start or end with a hyphen.");
            $validation_passed = false;
        }
    }

    if ($validation_passed) {
        // Use Category model to check existence
        if (Category::existsByNameOrSlugExcludingId($database_handler, $updated_name, $updated_slug, $category_id)) {
            $flashMessageService->addError("Another category with this name or slug already exists.");
        } else {
            try {
                // Use Category model to update
                $success = Category::updateById($database_handler, $category_id, $updated_name, $updated_slug);

                if ($success) {
                    $flashMessageService->addSuccess("Category '$updated_name' updated successfully.");
                    unset($_SESSION['csrf_token_edit_category_' . $category_id]);
                    header('Location: /index.php?page=manage_categories');
                    exit();
                } else {
                    $flashMessageService->addError("Failed to update category. Database error.");
                    error_log("Edit Category - Update failed for category ID: $category_id");
                }
            } catch (Exception $e) {
                $flashMessageService->addError("Database error updating category: " . $e->getMessage());
                error_log("Edit Category - Update Exception: " . $e->getMessage());
            }
        }
    }

    // To repopulate form with submitted (but erroneous) data
    $category = new Category(
        $category_id,
        $_POST['category_name'] ?? '',
        $_POST['category_slug'] ?? ''
    );
}

// --- Fetch category data for editing if not already set by POST error ---
if (!$category) {
    try {
        $category = Category::findById($database_handler, $category_id);

        if (!$category) {
            $flashMessageService->addError("Category not found.");
            header('Location: /index.php?page=manage_categories');
            exit();
        }
    } catch (Exception $e) {
        $flashMessageService->addError("Database error fetching category: " . $e->getMessage());
        error_log("Edit Category - Fetch Exception: " . $e->getMessage());
        header('Location: /index.php?page=manage_categories');
        exit();
    }
}

// --- Generate CSRF token for the form ---
$csrf_token_key = 'csrf_token_edit_category_' . $category_id;
if (empty($_SESSION[$csrf_token_key])) {
    $_SESSION[$csrf_token_key] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION[$csrf_token_key];

?>
<div class="edit-category-isolated">
    <div class="admin-layout">
        <!-- Page Header -->
        <header class="page-header">
            <div class="page-header-content">
                <div class="page-header-main">
                    <h1 class="page-title">
                        <i class="fas fa-edit"></i>
                        <?php echo htmlspecialchars($page_title); ?>
                        <?php if ($category): ?>
                            <span class="category-name-badge">"<?php echo htmlspecialchars($category->name); ?>"</span>
                        <?php endif; ?>
                    </h1>
                    <div class="page-header-description">
                        <p>Update category information and settings</p>
                    </div>

                    <?php if ($category): ?>
                    <div class="page-header-meta">
                        <div class="page-header-meta-item">
                            <i class="fas fa-hashtag"></i>
                            <span>ID: <?php echo $category_id; ?></span>
                        </div>
                        <div class="page-header-meta-item">
                            <i class="fas fa-link"></i>
                            <span>Slug: <?php echo htmlspecialchars($category->slug ?? 'N/A'); ?></span>
                        </div>
                        <div class="page-header-status">
                            <i class="fas fa-check-circle"></i>
                            <span>Active</span>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="page-header-actions">
                    <a href="/index.php?page=manage_categories" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Categories
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
            <main class="main-content" style="max-width: 800px;">
                <?php if ($category): ?>
                    <div class="form-container">
                        <div class="card card-primary">
                            <div class="card-header">
                                <div class="card-header-content">
                                    <h2 class="card-title">
                                        <i class="fas fa-pencil-alt"></i> Category Details
                                    </h2>
                                    <div class="card-header-description">
                                        <small>Update the category name and URL slug</small>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <form action="/index.php?page=edit_category&id=<?php echo htmlspecialchars((string)$category_id); ?>" method="POST" class="styled-form">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                    <input type="hidden" name="category_id" value="<?php echo htmlspecialchars((string)$category->id); ?>">

                                    <div class="form-section">
                                        <div class="form-grid">
                                            <div class="form-group form-group-full">
                                                <label for="category_name" class="form-label form-label-prominent">
                                                    Category Name <span class="required-asterisk">*</span>
                                                </label>
                                                <input type="text" id="category_name" name="category_name"
                                                       class="form-control form-control-large"
                                                       value="<?php echo htmlspecialchars($category->name ?? ''); ?>"
                                                       required>
                                                <div class="form-help-text">
                                                    <i class="fas fa-lightbulb"></i>
                                                    Choose a clear, descriptive name that helps users understand the content type
                                                </div>
                                            </div>

                                            <div class="form-group">
                                                <label for="category_slug" class="form-label">
                                                    Category Slug
                                                </label>
                                                <input type="text" id="category_slug" name="category_slug"
                                                       class="form-control"
                                                       value="<?php echo htmlspecialchars($category->slug ?? ''); ?>"
                                                       placeholder="e.g., php-frameworks">
                                                <div class="form-help-text">
                                                    <i class="fas fa-link"></i>
                                                    URL-friendly version using lowercase letters, numbers, and hyphens
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-actions">
                                        <div class="form-actions-primary">
                                            <button type="submit" class="button button-primary button-large">
                                                <i class="fas fa-save"></i> Update Category
                                            </button>
                                        </div>
                                        <div class="form-actions-secondary">
                                            <a href="/index.php?page=manage_categories" class="button button-secondary">
                                                <i class="fas fa-times"></i> Cancel
                                            </a>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="card">
                        <div class="card-body">
                            <div class="empty-state">
                                <div class="empty-state-icon">
                                    <i class="fas fa-exclamation-triangle"></i>
                                </div>
                                <div class="empty-state-content">
                                    <h3 class="empty-state-title">Category Not Found</h3>
                                    <p class="empty-state-description">
                                        The category you're trying to edit could not be found or loaded.
                                    </p>
                                    <div class="empty-state-actions">
                                        <a href="/index.php?page=manage_categories" class="button button-primary">
                                            <i class="fas fa-arrow-left"></i> Back to Categories
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </main>

            <!-- Compact Sidebar -->
            <aside class="sidebar-content" style="min-width: 280px; max-width: 320px;">
                <div class="card card-compact sidebar-card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-info-circle"></i> Category Info
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="category-meta-info">
                            <?php if ($category): ?>
                                <div class="meta-item">
                                    <span class="meta-label">Category ID:</span>
                                    <span class="meta-value">#<?php echo $category_id; ?></span>
                                </div>
                                <div class="meta-item">
                                    <span class="meta-label">Current Name:</span>
                                    <span class="meta-value"><?php echo htmlspecialchars($category->name); ?></span>
                                </div>
                                <div class="meta-item">
                                    <span class="meta-label">Current Slug:</span>
                                    <span class="meta-value"><code><?php echo htmlspecialchars($category->slug ?? 'N/A'); ?></code></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="card card-compact sidebar-card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-lightbulb"></i> Editing Tips
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="tips-list">
                            <div class="tip-item">
                                <h4>Careful with Changes</h4>
                                <p>Changing the category name or slug will affect how it appears throughout your site</p>
                            </div>
                            <div class="tip-item">
                                <h4>URL Impact</h4>
                                <p>Changing the slug will change URLs that use this category</p>
                            </div>
                            <div class="tip-item">
                                <h4>Keep it Simple</h4>
                                <p>Use clear, concise names that users will easily understand</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card card-compact sidebar-card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-link"></i> Quick Actions
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="quick-links-list">
                            <a href="/index.php?page=manage_categories" class="quick-link">
                                <i class="fas fa-tags"></i> All Categories
                            </a>
                            <a href="/index.php?page=manage_articles" class="quick-link">
                                <i class="fas fa-newspaper"></i> Manage Articles
                            </a>
                            <a href="/index.php?page=create_article" class="quick-link">
                                <i class="fas fa-plus"></i> New Article
                            </a>
                            <a href="/index.php?page=dashboard" class="quick-link">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                        </div>
                    </div>
                </div>
            </aside>
        </div>
    </div>
</div>

<script>
    // Auto-generate slug from the category name if needed
    document.addEventListener('DOMContentLoaded', function() {
        const nameInput = document.getElementById('category_name');
        const slugInput = document.getElementById('category_slug');

        if (nameInput && slugInput) {
            let isSlugManuallyEdited = false;

            // Check if the slug was manually edited
            slugInput.addEventListener('input', function() {
                isSlugManuallyEdited = true;
            });

            // Auto-generate slug only if not manually edited
            nameInput.addEventListener('input', function() {
                if (!isSlugManuallyEdited) {
                    slugInput.value = generateSlug(this.value);
                }
            });
        }
    });

    // Generate URL-friendly slug
    function generateSlug(text) {
        return text
            .toLowerCase()
            .trim()
            .replace(/[^\w\s-]/g, '') // Remove special characters
            .replace(/[\s_-]+/g, '-') // Replace spaces and underscores with hyphens
            .replace(/^-+|-+$/g, ''); // Remove leading/trailing hyphens
    }
    </script>
