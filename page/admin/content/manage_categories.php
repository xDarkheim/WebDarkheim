<?php

/**
 * Manage Categories Page - MODERN ADMIN INTERFACE
 *
 * Modern administrative interface for managing categories
 * with improved UX and consistent styling
 *
 * @author Dmytro Hovenko
 */

declare(strict_types=1);

use App\Domain\Models\Category;
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
    error_log('Critical: FlashMessageService not available in manage_categories.php');
    die('A critical system error occurred. Please try again later.');
}

if (!isset($database_handler)) {
    error_log('Critical: Database handler not available in manage_categories.php');
    $flashMessageService->addError('Database connection error. Please try again later.');
    header('Location: /index.php?page=dashboard');
    exit();
}

$pageTitle = 'Manage Categories';

// Create unified navigation
$adminNavigation = new AdminNavigation($authService);

// --- Helper function to generate slugs ---
function generateSlug(string $text): string {
    // Remove unwanted characters
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    // Transliterate
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
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
        $flashMessageService->addError('Invalid CSRF token. Action aborted.');
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
            $flashMessageService->addError('Category name cannot be empty.');
            $validation_passed = false;
        }

        if ($validation_passed) {
            if (empty($category_slug)) {
                $category_slug = generateSlug($category_name);
            } else {
                if (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $category_slug)) {
                    $flashMessageService->addError('Slug can only contain lowercase letters, numbers, and hyphens, and cannot start or end with a hyphen.');
                    $validation_passed = false;
                }
            }
        }

        if ($validation_passed) {
            // Use Category model to check existence
            if (Category::existsByNameOrSlugExcludingId($database_handler, $category_name, $category_slug, 0)) {
                $flashMessageService->addError('A category with this name or slug already exists.');
            } else {
                try {
                    // Use Category model to create
                    $newCategory = Category::create($database_handler, $category_name, $category_slug);
                    if ($newCategory) {
                        $flashMessageService->addSuccess("Category '$category_name' added successfully.");
                        header('Location: /index.php?page=manage_categories');
                        exit();
                    } else {
                        $flashMessageService->addError('Failed to add category. Database error.');
                        error_log('Manage Categories - Add: Failed to create category');
                    }
                } catch (Exception $e) {
                    $flashMessageService->addError('Database error adding category: ' . $e->getMessage());
                    error_log('Manage Categories - Add Exception: ' . $e->getMessage());
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
                    $flashMessageService->addError('Failed to delete category or category not found.');
                    error_log("Manage Categories - Delete: Failed for ID $category_id_to_delete");
                }
            } catch (Exception $e) {
                $flashMessageService->addError('Database error deleting category: ' . $e->getMessage());
                error_log('Manage Categories - Delete Exception: ' . $e->getMessage());
            }
        } else {
            $flashMessageService->addError('Invalid category ID for deletion.');
        }
    }

    // Redirect after POST to prevent resubmission
    header('Location: /index.php?page=manage_categories');
    exit();
}

// --- Generate CSRF token for forms ---
if (empty($_SESSION['csrf_token_manage_categories'])) {
    try {
        $_SESSION['csrf_token_manage_categories'] = bin2hex(random_bytes(32));
    } catch (RandomException $e) {
        $flashMessageService->addError('Failed to generate CSRF token. Please try again later.');
    }
}
$csrf_token = $_SESSION['csrf_token_manage_categories'];

// --- Fetch all categories for display ---
$categories = [];
try {
    $categories = Category::findAll($database_handler);
} catch (Exception $e) {
    $flashMessageService->addError('Error fetching categories: ' . $e->getMessage());
    error_log('Manage Categories - Fetch Exception: ' . $e->getMessage());
}

// Get flash messages
$flashMessages = $flashMessageService->getAllMessages();

// Calculate statistics
$recent_categories = array_filter($categories, function($category) {
    return $category->created_at && strtotime($category->created_at) > strtotime('-7 days');
});

$monthly_categories = array_filter($categories, function($category) {
    return $category->created_at && strtotime($category->created_at) > strtotime('-30 days');
});

?>

    <link rel="stylesheet" href="/public/assets/css/admin.css">

    <!-- Navigation -->
    <?= $adminNavigation->render() ?>

    <!-- Header -->
    <header class="admin-header">
        <div class="admin-header-container">
            <div class="admin-header-content">
                <div class="admin-header-title">
                    <i class="admin-header-icon fas fa-tags"></i>
                    <div class="admin-header-text">
                        <h1>Manage Categories</h1>
                        <p>Organize and manage content categories</p>
                    </div>
                </div>
                
                <div class="admin-header-actions">
                    <a href="/index.php?page=manage_articles" class="admin-btn admin-btn-secondary">
                        <i class="fas fa-newspaper"></i>
                        Manage Articles
                    </a>
                    <a href="/index.php?page=create_article" class="admin-btn admin-btn-primary">
                        <i class="fas fa-plus"></i>
                        Create Article
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
                <div class="admin-grid admin-grid-cols-1">
                    <!-- Add Category Form -->
                    <div class="admin-card">
                        <div class="admin-card-header">
                            <h3 class="admin-card-title">
                                <i class="fas fa-plus-circle"></i>Add New Category
                            </h3>
                        </div>
                        <div class="admin-card-body">
                            <form action="/index.php?page=manage_categories" method="POST" class="admin-grid admin-grid-cols-2">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                                <input type="hidden" name="action" value="add_category">

                                <div class="admin-form-group">
                                    <label for="category_name" class="admin-label admin-label-required">
                                        Category Name
                                    </label>
                                    <input type="text" 
                                           id="category_name" 
                                           name="category_name"
                                           class="admin-input"
                                           placeholder="Enter a descriptive category name"
                                           data-slug-source="true"
                                           data-slug-target="#category_slug"
                                           required>
                                    <div class="admin-help-text">
                                        Choose a clear, descriptive name that helps users understand the content type
                                    </div>
                                </div>

                                <div class="admin-form-group">
                                    <label for="category_slug" class="admin-label">
                                        URL Slug <span style="color: var(--admin-gray-400);">(Optional)</span>
                                    </label>
                                    <input type="text" 
                                           id="category_slug" 
                                           name="category_slug"
                                           class="admin-input"
                                           placeholder="e.g., technology, programming">
                                    <div class="admin-help-text">
                                        URL-friendly version using lowercase letters, numbers, and hyphens
                                    </div>
                                </div>

                                <div style="grid-column: 1 / -1; display: flex; justify-content: flex-end; gap: 0.75rem; margin-top: 1rem;">
                                    <button type="reset" class="admin-btn admin-btn-secondary">
                                        <i class="fas fa-undo"></i>Clear
                                    </button>
                                    <button type="submit" class="admin-btn admin-btn-primary">
                                        <i class="fas fa-save"></i>Create Category
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Categories List -->
                    <?php if (!empty($categories)): ?>
                    <div class="admin-card">
                        <div class="admin-card-header" style="display: flex; justify-content: space-between; align-items: center;">
                            <h3 class="admin-card-title">
                                <i class="fas fa-list"></i>Existing Categories (<?= count($categories) ?>)
                            </h3>
                            <label>
                                <input type="text" class="admin-input" placeholder="Search categories..." data-search-target="tbody tr" style="width: 250px;">
                            </label>
                        </div>
                        
                        <div class="admin-table-container">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th data-sort="true">ID</th>
                                        <th data-sort="true">Name</th>
                                        <th data-sort="true">Slug</th>
                                        <th data-sort="true">Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($categories as $category): ?>
                                    <tr>
                                        <td>
                                            <span class="admin-badge admin-badge-primary"><?= htmlspecialchars((string)$category->id) ?></span>
                                        </td>
                                        <td>
                                            <strong><?= htmlspecialchars($category->name) ?></strong>
                                        </td>
                                        <td>
                                            <code style="background: var(--admin-gray-100); padding: 0.25rem 0.5rem; border-radius: 0.25rem; font-size: 0.75rem;"><?= htmlspecialchars($category->slug ?? 'N/A') ?></code>
                                        </td>
                                        <td>
                                            <?= htmlspecialchars($category->created_at ? date('M j, Y', strtotime($category->created_at)) : 'N/A') ?>
                                        </td>
                                        <td>
                                            <div style="display: flex; gap: 0.5rem;">
                                                <a href="/index.php?page=edit_category&id=<?= $category->id ?>" class="admin-btn admin-btn-sm admin-btn-secondary" data-tooltip="Edit Category">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <form method="POST" style="display: inline;" data-confirm="Are you sure you want to delete this category?">
                                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                                                    <input type="hidden" name="category_id_to_delete" value="<?= $category->id ?>">
                                                    <input type="hidden" name="action" value="delete_category">
                                                    <button type="submit" class="admin-btn admin-btn-sm admin-btn-danger" data-tooltip="Delete Category">
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
                    <?php else: ?>
                    <div class="admin-card">
                        <div class="admin-card-body admin-text-center">
                            <i class="fas fa-folder-open" style="font-size: 3rem; color: var(--admin-gray-400); margin-bottom: 1rem;"></i>
                            <h3 style="color: var(--admin-gray-900); margin-bottom: 0.5rem;">Ready to Create Categories</h3>
                            <p style="color: var(--admin-gray-500); margin-bottom: 1rem;">
                                Use the form above to create your first category. Categories help organize your content and make it easier for readers to find what they're looking for.
                            </p>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Sidebar -->
            <aside class="admin-sidebar">
                <!-- Statistics -->
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h3 class="admin-card-title">
                            <i class="fas fa-chart-bar"></i>Statistics
                        </h3>
                    </div>
                    <div class="admin-card-body">
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
                            <div style="display: flex; align-items: center;">
                                <i class="fas fa-tags" style="margin-right: 0.5rem; color: var(--admin-primary);"></i>
                                <span style="font-size: 0.875rem; color: var(--admin-gray-600);">Total Categories</span>
                            </div>
                            <span style="font-size: 1.125rem; font-weight: 600; color: var(--admin-gray-900);"><?= count($categories) ?></span>
                        </div>
                        
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
                            <div style="display: flex; align-items: center;">
                                <i class="fas fa-clock" style="margin-right: 0.5rem; color: var(--admin-success);"></i>
                                <span style="font-size: 0.875rem; color: var(--admin-gray-600);">This Week</span>
                            </div>
                            <span style="font-size: 1.125rem; font-weight: 600; color: var(--admin-gray-900);"><?= count($recent_categories) ?></span>
                        </div>
                        
                        <div style="display: flex; align-items: center; justify-content: space-between;">
                            <div style="display: flex; align-items: center;">
                                <i class="fas fa-calendar" style="margin-right: 0.5rem; color: var(--admin-warning);"></i>
                                <span style="font-size: 0.875rem; color: var(--admin-gray-600);">This Month</span>
                            </div>
                            <span style="font-size: 1.125rem; font-weight: 600; color: var(--admin-gray-900);"><?= count($monthly_categories) ?></span>
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
                        <a href="/index.php?page=manage_articles" class="admin-btn admin-btn-secondary" style="width: 100%; margin-bottom: 0.5rem; justify-content: flex-start;">
                            <i class="fas fa-newspaper"></i>
                            Manage Articles
                        </a>
                        <a href="/index.php?page=create_article" class="admin-btn admin-btn-secondary" style="width: 100%; margin-bottom: 0.5rem; justify-content: flex-start;">
                            <i class="fas fa-plus"></i>
                            Create Article
                        </a>
                        <a href="/index.php?page=dashboard" class="admin-btn admin-btn-secondary" style="width: 100%; justify-content: flex-start;">
                            <i class="fas fa-tachometer-alt"></i>
                            Dashboard
                        </a>
                    </div>
                </div>

                <!-- Tips -->
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h3 class="admin-card-title">
                            <i class="fas fa-lightbulb"></i>Tips
                        </h3>
                    </div>
                    <div class="admin-card-body">
                        <div style="display: flex; flex-direction: column; gap: 1rem;">
                            <div>
                                <h4 style="display: flex; align-items: center; margin: 0 0 0.5rem 0; font-size: 0.875rem; font-weight: 600;">
                                    <i class="fas fa-tag" style="color: var(--admin-primary); margin-right: 0.5rem;"></i>Clear Names
                                </h4>
                                <p style="font-size: 0.875rem; color: var(--admin-gray-600); margin: 0;">Use descriptive names that help users understand what content belongs in this category.</p>
                            </div>
                            <div>
                                <h4 style="display: flex; align-items: center; margin: 0 0 0.5rem 0; font-size: 0.875rem; font-weight: 600;">
                                    <i class="fas fa-link" style="color: var(--admin-success); margin-right: 0.5rem;"></i>URL Slugs
                                </h4>
                                <p style="font-size: 0.875rem; color: var(--admin-gray-600); margin: 0;">Slugs create clean URLs. They're automatically generated, but you can customize them.</p>
                            </div>
                            <div>
                                <h4 style="display: flex; align-items: center; margin: 0 0 0.5rem 0; font-size: 0.875rem; font-weight: 600;">
                                    <i class="fas fa-sitemap" style="color: var(--admin-warning); margin-right: 0.5rem;"></i>Organization
                                </h4>
                                <p style="font-size: 0.875rem; color: var(--admin-gray-600); margin: 0;">Think about how readers will browse your content when creating categories.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </aside>
        </div>
    </main>

    <!-- Admin Scripts -->
    <script type="module" src="/public/assets/js/admin.js"></script>
