<?php

/**
 * Edit Category Page - MODERN DARK ADMIN INTERFACE
 *
 * Modern dark administrative interface for editing categories
 * with improved UX and consistent styling
 *
 * @author Dmytro Hovenko
 */

declare(strict_types=1);

use App\Domain\Models\Category;
use App\Infrastructure\Lib\SlugGenerator;
use App\Application\Components\AdminNavigation;
use Random\RandomException;

// Use global services from bootstrap.php
global $flashMessageService, $database_handler, $serviceProvider;

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Get AuthenticationService
try {
    $authService = $serviceProvider->getAuth();
} catch (Exception $e) {
    error_log('Critical: Failed to get AuthenticationService instance: ' . $e->getMessage());
    die('A critical system error occurred. Please try again later.');
}

// Check authentication and admin rights
if (!$authService->isAuthenticated() || !$authService->hasRole('admin')) {
    $flashMessageService->addError('Access Denied. You do not have permission to view this page.');
    header('Location: /index.php?page=login');
    exit();
}

// Check required services
if (!isset($flashMessageService)) {
    error_log('Critical: FlashMessageService not available in edit_category.php');
    die('A critical system error occurred. Please try again later.');
}

if (!isset($database_handler)) {
    error_log('Critical: Database handler not available in edit_category.php');
    $flashMessageService->addError('Database connection error. Please try again later.');
    header('Location: /index.php?page=manage_categories');
    exit();
}

$page_title = 'Edit Category';

// Get category ID from request
$category_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($category_id <= 0) {
    $flashMessageService->addError('Invalid category ID.');
    header('Location: /index.php?page=manage_categories');
    exit();
}

// Create unified navigation
$adminNavigation = new AdminNavigation($authService);

$category = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle form submission
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token_edit_category_' . $category_id] ?? '', $_POST['csrf_token'])) {
        $flashMessageService->addError('Invalid CSRF token. Action aborted.');
        header('Location: /index.php?page=edit_category&id=' . $category_id);
        exit();
    }

    $posted_category_id = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);
    if ($posted_category_id !== $category_id) {
        $flashMessageService->addError('Category ID mismatch. Action aborted.');
        header('Location: /index.php?page=edit_category&id=' . $category_id);
        exit();
    }

    $updated_name = trim(filter_input(INPUT_POST, 'category_name', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '');
    $updated_slug = trim(filter_input(INPUT_POST, 'category_slug', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '');

    // Validation
    $validation_passed = true;

    if (empty($updated_name)) {
        $flashMessageService->addError('Category name cannot be empty.');
        $validation_passed = false;
    }

    if (empty($updated_slug)) {
        $updated_slug = SlugGenerator::generate($updated_name);
    } else {
        if (!SlugGenerator::isValid($updated_slug)) {
            $flashMessageService->addError('Slug can only contain lowercase letters, numbers, and hyphens, and cannot start or end with a hyphen.');
            $validation_passed = false;
        }
    }

    if ($validation_passed) {
        if (Category::existsByNameOrSlugExcludingId($database_handler, $updated_name, $updated_slug, $category_id)) {
            $flashMessageService->addError('Another category with this name or slug already exists.');
        } else {
            try {
                $success = Category::updateById($database_handler, $category_id, $updated_name, $updated_slug);

                if ($success) {
                    $flashMessageService->addSuccess("Category '$updated_name' updated successfully.");
                    unset($_SESSION['csrf_token_edit_category_' . $category_id]);
                    header('Location: /index.php?page=manage_categories');
                    exit();
                } else {
                    $flashMessageService->addError('Failed to update category. Database error.');
                    error_log("Edit Category - Update failed for category ID: $category_id");
                }
            } catch (Exception $e) {
                $flashMessageService->addError('Database error updating category: ' . $e->getMessage());
                error_log('Edit Category - Update Exception: ' . $e->getMessage());
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

// Fetch category data for editing if not already set by POST error
if (!$category) {
    try {
        $category = Category::findById($database_handler, $category_id);

        if (!$category) {
            $flashMessageService->addError('Category not found.');
            header('Location: /index.php?page=manage_categories');
            exit();
        }
    } catch (Exception $e) {
        $flashMessageService->addError('Database error fetching category: ' . $e->getMessage());
        error_log('Edit Category - Fetch Exception: ' . $e->getMessage());
        header('Location: /index.php?page=manage_categories');
        exit();
    }
}

// Generate CSRF token for the form
$csrf_token_key = 'csrf_token_edit_category_' . $category_id;
if (empty($_SESSION[$csrf_token_key])) {
    try {
        $_SESSION[$csrf_token_key] = bin2hex(random_bytes(32));
    } catch (RandomException $e) {
        $flashMessageService->addError('Failed to generate CSRF token. Please try again later.');
    }
}
$csrf_token = $_SESSION[$csrf_token_key];

// Get flash messages
$flashMessages = $flashMessageService->getAllMessages();

?>

    <!-- Admin Dark Theme Styles -->
    <link rel="stylesheet" href="/public/assets/css/admin.css">

    <!-- Navigation -->
    <?= $adminNavigation->render() ?>

    <!-- Header -->
    <header class="admin-header">
        <div class="admin-header-container">
            <div class="admin-header-content">
                <div class="admin-header-title">
                    <i class="admin-header-icon fas fa-edit"></i>
                    <div class="admin-header-text">
                        <h1>Edit Category</h1>
                        <?php if ($category): ?>
                        <p>Editing: <strong><?= htmlspecialchars($category->name) ?></strong> (ID: <?= $category_id ?>)</p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="admin-header-actions">
                    <a href="/index.php?page=manage_categories" class="admin-btn admin-btn-secondary">
                        <i class="fas fa-arrow-left"></i>
                        Back to Categorize
                    </a>
                    <a href="/index.php?page=dashboard" class="admin-btn admin-btn-secondary">
                        <i class="fas fa-tachometer-alt"></i>
                        Dashboard
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
                <?php if ($category): ?>
                <!-- Edit Form -->
                <div class="admin-card admin-glow-primary">
                    <div class="admin-card-header">
                        <h3 class="admin-card-title">
                            <i class="fas fa-edit"></i>Category Details
                        </h3>
                    </div>
                    <div class="admin-card-body">
                        <form action="/index.php?page=edit_category&id=<?= htmlspecialchars((string)$category_id) ?>" method="POST" class="admin-grid admin-grid-cols-1">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                            <input type="hidden" name="category_id" value="<?= htmlspecialchars((string)$category->id) ?>">

                            <div class="admin-form-group">
                                <label for="category_name" class="admin-label admin-label-required">
                                    Category Name
                                </label>
                                <input type="text" 
                                       id="category_name" 
                                       name="category_name"
                                       class="admin-input"
                                       value="<?= htmlspecialchars($category->name ?? '') ?>"
                                       data-slug-source="true"
                                       data-slug-target="#category_slug"
                                       required>
                                <div class="admin-help-text">
                                    Choose a clear, descriptive name that helps users understand the content type
                                </div>
                            </div>

                            <div class="admin-form-group">
                                <label for="category_slug" class="admin-label">
                                    Category Slug
                                </label>
                                <input type="text" 
                                       id="category_slug" 
                                       name="category_slug"
                                       class="admin-input"
                                       value="<?= htmlspecialchars($category->slug ?? '') ?>"
                                       placeholder="e.g., php-frameworks">
                                <div class="admin-help-text">
                                    URL-friendly version using lowercase letters, numbers, and hyphens
                                </div>
                            </div>

                            <div style="display: flex; justify-content: flex-end; gap: 0.75rem; margin-top: 2rem;">
                                <a href="/index.php?page=manage_categories" class="admin-btn admin-btn-secondary">
                                    <i class="fas fa-times"></i>Cancel
                                </a>
                                <button type="submit" class="admin-btn admin-btn-primary">
                                    <i class="fas fa-save"></i>Update Category
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                <?php else: ?>
                <!-- Error State -->
                <div class="admin-card">
                    <div class="admin-card-body admin-text-center">
                        <i class="fas fa-exclamation-triangle" style="font-size: 3rem; color: var(--admin-warning); margin-bottom: 1rem;"></i>
                        <h3 style="color: var(--admin-text-primary); margin-bottom: 0.5rem;">Category Not Found</h3>
                        <p style="color: var(--admin-text-muted); margin-bottom: 1.5rem;">
                            The category you're trying to edit could not be found or loaded.
                        </p>
                        <a href="/index.php?page=manage_categories" class="admin-btn admin-btn-primary">
                            <i class="fas fa-arrow-left"></i>Back to Categories
                        </a>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Sidebar -->
            <aside class="admin-sidebar">
                <!-- Category Info -->
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h3 class="admin-card-title">
                            <i class="fas fa-info-circle"></i>Category Info
                        </h3>
                    </div>
                    <div class="admin-card-body">
                        <?php if ($category): ?>
                        <div style="display: flex; flex-direction: column; gap: 1rem;">
                            <div>
                                <h4 style="color: var(--admin-text-muted); font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.1em; margin: 0 0 0.25rem 0;">Category ID</h4>
                                <span class="admin-badge admin-badge-primary">#<?= $category_id ?></span>
                            </div>
                            <div>
                                <h4 style="color: var(--admin-text-muted); font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.1em; margin: 0 0 0.25rem 0;">Current Name</h4>
                                <p style="color: var(--admin-text-primary); margin: 0; font-weight: 600;"><?= htmlspecialchars($category->name) ?></p>
                            </div>
                            <div>
                                <h4 style="color: var(--admin-text-muted); font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.1em; margin: 0 0 0.25rem 0;">Current Slug</h4>
                                <code style="background: var(--admin-bg-secondary); color: var(--admin-text-secondary); padding: 0.5rem; border-radius: 0.5rem; font-size: 0.875rem; display: block;"><?= htmlspecialchars($category->slug ?? 'N/A') ?></code>
                            </div>
                            <?php if ($category->created_at): ?>
                            <div>
                                <h4 style="color: var(--admin-text-muted); font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.1em; margin: 0 0 0.25rem 0;">Created</h4>
                                <p style="color: var(--admin-text-primary); margin: 0;"><?= date('M j, Y \a\t g:i A', strtotime($category->created_at)) ?></p>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Editing Tips -->
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h3 class="admin-card-title">
                            <i class="fas fa-lightbulb"></i>Editing Tips
                        </h3>
                    </div>
                    <div class="admin-card-body">
                        <div style="display: flex; flex-direction: column; gap: 1rem;">
                            <div>
                                <h4 style="display: flex; align-items: center; margin: 0 0 0.5rem 0; font-size: 0.875rem; font-weight: 600; color: var(--admin-text-primary);">
                                    <i class="fas fa-exclamation-triangle" style="color: var(--admin-warning); margin-right: 0.5rem;"></i>Careful with Changes
                                </h4>
                                <p style="font-size: 0.875rem; color: var(--admin-text-muted); margin: 0;">Changing the category name or slug will affect how it appears throughout your site</p>
                            </div>
                            <div>
                                <h4 style="display: flex; align-items: center; margin: 0 0 0.5rem 0; font-size: 0.875rem; font-weight: 600; color: var(--admin-text-primary);">
                                    <i class="fas fa-link" style="color: var(--admin-info); margin-right: 0.5rem;"></i>URL Impact
                                </h4>
                                <p style="font-size: 0.875rem; color: var(--admin-text-muted); margin: 0;">Changing the slug will change URLs that use this category</p>
                            </div>
                            <div>
                                <h4 style="display: flex; align-items: center; margin: 0 0 0.5rem 0; font-size: 0.875rem; font-weight: 600; color: var(--admin-text-primary);">
                                    <i class="fas fa-check-circle" style="color: var(--admin-success); margin-right: 0.5rem;"></i>Keep it Simple
                                </h4>
                                <p style="font-size: 0.875rem; color: var(--admin-text-muted); margin: 0;">Use clear, concise names that users will easily understand</p>
                            </div>
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
                        <a href="/index.php?page=manage_categories" class="admin-btn admin-btn-secondary" style="width: 100%; margin-bottom: 0.5rem; justify-content: flex-start;">
                            <i class="fas fa-tags"></i>All Categories
                        </a>
                        <a href="/index.php?page=manage_articles" class="admin-btn admin-btn-secondary" style="width: 100%; margin-bottom: 0.5rem; justify-content: flex-start;">
                            <i class="fas fa-newspaper"></i>Manage Articles
                        </a>
                        <a href="/index.php?page=create_article" class="admin-btn admin-btn-secondary" style="width: 100%; margin-bottom: 0.5rem; justify-content: flex-start;">
                            <i class="fas fa-plus"></i>New Article
                        </a>
                        <a href="/index.php?page=dashboard" class="admin-btn admin-btn-secondary" style="width: 100%; justify-content: flex-start;">
                            <i class="fas fa-tachometer-alt"></i>Dashboard
                        </a>
                    </div>
                </div>
            </aside>
        </div>
    </main>

    <!-- Admin Scripts -->
    <script type="module" src="/public/assets/js/admin.js"></script>
