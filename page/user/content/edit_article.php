<?php
/**
 * Edit Article Page - MODERN DARK ADMIN INTERFACE
 *
 * Modern dark administrative interface for editing articles
 * with improved UX and consistent styling
 */

declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/includes/bootstrap.php';

global $serviceProvider, $flashMessageService;

try {
    $authService = $serviceProvider->getAuth();
} catch (Exception $e) {
    error_log("Critical: Failed to get AuthenticationService: " . $e->getMessage());
    die("System error occurred.");
}

// Check authentication and permissions
if (!$authService->isAuthenticated()) {
    $flashMessageService->addError('Please log in to access this area.');
    header("Location: /index.php?page=login");
    exit();
}

$userRole = $authService->getCurrentUserRole();
if (!in_array($userRole, ['admin', 'employee'])) {
    $flashMessageService->addError('Access denied. Insufficient permissions.');
    header("Location: /index.php?page=dashboard");
    exit();
}

$pageTitle = 'Edit Article';
$articleId = $_GET['id'] ?? null;

// Get flash messages
$flashMessages = $flashMessageService->getAllMessages();
?>

    <!-- Admin Dark Theme Styles -->
    <link rel="stylesheet" href="/public/assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <!-- Navigation -->
    <nav class="admin-nav">
        <div class="admin-nav-container">
            <a href="/index.php?page=dashboard" class="admin-nav-brand">
                <i class="fas fa-shield-alt"></i>
                <span>Admin Panel</span>
            </a>

            <div class="admin-nav-links">
                <a href="/index.php?page=manage_articles" class="admin-nav-link" style="background-color: var(--admin-primary-bg); color: var(--admin-primary-light); border-color: var(--admin-primary-border);">
                    <i class="fas fa-newspaper"></i>
                    <span>Articles</span>
                </a>
                <a href="/index.php?page=manage_categories" class="admin-nav-link">
                    <i class="fas fa-tags"></i>
                    <span>Categories</span>
                </a>
                <a href="/index.php?page=manage_users" class="admin-nav-link">
                    <i class="fas fa-users"></i>
                    <span>Users</span>
                </a>
                <a href="/index.php?page=site_settings" class="admin-nav-link">
                    <i class="fas fa-cogs"></i>
                    <span>Settings</span>
                </a>
                <a href="/index.php?page=dashboard" class="admin-nav-link">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </div>
        </div>
    </nav>

    <!-- Header -->
    <header class="admin-header">
        <div class="admin-header-container">
            <div class="admin-header-content">
                <div class="admin-header-title">
                    <i class="admin-header-icon fas fa-edit"></i>
                    <div class="admin-header-text">
                        <h1>Edit Article <?= $articleId ? '#' . htmlspecialchars($articleId) : '' ?></h1>
                        <p>Update and manage your article content</p>
                    </div>
                </div>

                <div class="admin-header-actions">
                    <a href="/index.php?page=manage_articles" class="admin-btn admin-btn-secondary">
                        <i class="fas fa-arrow-left"></i>Back to Articles
                    </a>
                    <a href="/index.php?page=create_article" class="admin-btn admin-btn-primary">
                        <i class="fas fa-plus"></i>New Article
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

                <!-- Admin Notice -->
                <div class="admin-card admin-glow-warning">
                    <div class="admin-card-body">
                        <div style="display: flex; align-items: center; gap: 1rem;">
                            <div style="color: var(--admin-warning); font-size: 2rem;">
                                <i class="fas fa-shield-alt"></i>
                            </div>
                            <div>
                                <h3 style="margin: 0; color: var(--admin-text-primary);">Administrative Content Management</h3>
                                <p style="margin: 0.5rem 0 0 0; color: var(--admin-text-secondary);">
                                    This feature is restricted to administrative users only. Article editing is limited to admin and employee roles.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Edit Article Form -->
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h3 class="admin-card-title">
                            <i class="fas fa-edit"></i>Edit Article Content
                        </h3>
                    </div>
                    <div class="admin-card-body">
                        <form method="POST" action="/index.php?page=api_update_article">
                            <input type="hidden" name="article_id" value="<?= htmlspecialchars($articleId ?? '') ?>">

                            <div class="admin-grid admin-grid-cols-1">
                                <!-- Title Field -->
                                <div class="admin-form-group">
                                    <label for="title" class="admin-label admin-label-required">
                                        <i class="fas fa-heading"></i>Article Title
                                    </label>
                                    <input type="text"
                                           id="title"
                                           name="title"
                                           class="admin-input"
                                           required
                                           placeholder="Enter article title"
                                           value="">
                                    <div class="admin-help-text">
                                        Update the title to better reflect your content
                                    </div>
                                </div>

                                <!-- Slug Field -->
                                <div class="admin-form-group">
                                    <label for="slug" class="admin-label">
                                        <i class="fas fa-link"></i>URL Slug
                                    </label>
                                    <input type="text"
                                           id="slug"
                                           name="slug"
                                           class="admin-input"
                                           placeholder="article-url-slug"
                                           value="">
                                    <div class="admin-help-text">
                                        SEO-friendly URL (leave empty to auto-generate from title)
                                    </div>
                                </div>

                                <!-- Content Field -->
                                <div class="admin-form-group">
                                    <label for="content" class="admin-label admin-label-required">
                                        <i class="fas fa-align-left"></i>Article Content
                                    </label>
                                    <textarea id="content"
                                              name="content"
                                              class="admin-input admin-textarea"
                                              rows="20"
                                              required
                                              placeholder="Article content goes here..."></textarea>
                                    <div class="admin-help-text">
                                        Update your article content with engaging, valuable information
                                    </div>
                                </div>

                                <!-- Excerpt Field -->
                                <div class="admin-form-group">
                                    <label for="excerpt" class="admin-label">
                                        <i class="fas fa-quote-left"></i>Article Excerpt
                                    </label>
                                    <textarea id="excerpt"
                                              name="excerpt"
                                              class="admin-input"
                                              rows="3"
                                              placeholder="Brief summary of the article"></textarea>
                                    <div class="admin-help-text">
                                        Short description for article previews and search results
                                    </div>
                                </div>

                                <!-- Categories Field -->
                                <div class="admin-form-group">
                                    <label for="categories" class="admin-label">
                                        <i class="fas fa-tags"></i>Categories
                                    </label>
                                    <select id="categories"
                                            name="categories[]"
                                            class="admin-input admin-select"
                                            multiple>
                                        <option value="1">Technology</option>
                                        <option value="2">Design</option>
                                        <option value="3">Development</option>
                                        <option value="4">Business</option>
                                    </select>
                                    <div class="admin-help-text">
                                        Update categories to better organize your content
                                    </div>
                                </div>

                                <!-- Publication Options -->
                                <div class="admin-form-group">
                                    <label class="admin-label">
                                        <i class="fas fa-cog"></i>Article Options
                                    </label>
                                    <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                                        <label style="display: flex; align-items: center; gap: 0.5rem; color: var(--admin-text-primary);">
                                            <input type="checkbox" name="featured" value="1" style="margin: 0;">
                                            <span>Featured article</span>
                                        </label>
                                        <label style="display: flex; align-items: center; gap: 0.5rem; color: var(--admin-text-primary);">
                                            <input type="checkbox" name="allow_comments" value="1" style="margin: 0;">
                                            <span>Allow comments</span>
                                        </label>
                                        <label style="display: flex; align-items: center; gap: 0.5rem; color: var(--admin-text-primary);">
                                            <input type="checkbox" name="send_newsletter" value="1" style="margin: 0;">
                                            <span>Include in newsletter</span>
                                        </label>
                                    </div>
                                </div>

                                <!-- Status Field -->
                                <div class="admin-form-group">
                                    <label for="status" class="admin-label">
                                        <i class="fas fa-flag"></i>Publication Status
                                    </label>
                                    <select id="status" name="status" class="admin-input admin-select">
                                        <option value="draft">Save as Draft</option>
                                        <option value="pending_review">Submit for Review</option>
                                        <?php if ($userRole === 'admin'): ?>
                                        <option value="published">Publish</option>
                                        <option value="archived">Archive</option>
                                        <?php endif; ?>
                                    </select>
                                    <div class="admin-help-text">
                                        Change the publication status of this article
                                    </div>
                                </div>
                            </div>

                            <!-- Form Actions -->
                            <div class="admin-card-footer">
                                <div style="display: flex; gap: 1rem; justify-content: space-between; align-items: center;">
                                    <div style="display: flex; gap: 1rem;">
                                        <button type="button" class="admin-btn admin-btn-secondary" onclick="saveDraft()">
                                            <i class="fas fa-save"></i>Save Draft
                                        </button>
                                        <button type="button" class="admin-btn admin-btn-warning" onclick="previewArticle()">
                                            <i class="fas fa-eye"></i>Preview
                                        </button>
                                    </div>
                                    <div style="display: flex; gap: 1rem;">
                                        <a href="/index.php?page=manage_articles" class="admin-btn admin-btn-secondary">
                                            <i class="fas fa-times"></i>Cancel
                                        </a>
                                        <button type="submit" class="admin-btn admin-btn-success">
                                            <i class="fas fa-save"></i>Update Article
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Article Information -->
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h3 class="admin-card-title">
                            <i class="fas fa-info-circle"></i>Article Information
                        </h3>
                    </div>
                    <div class="admin-card-body">
                        <div class="admin-grid admin-grid-cols-2">
                            <div>
                                <div style="margin-bottom: 1rem;">
                                    <div style="color: var(--admin-text-muted); font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 0.25rem;">
                                        Article ID
                                    </div>
                                    <div style="color: var(--admin-text-primary); font-weight: 600;">
                                        #<?= htmlspecialchars($articleId ?? 'New') ?>
                                    </div>
                                </div>
                                <div style="margin-bottom: 1rem;">
                                    <div style="color: var(--admin-text-muted); font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 0.25rem;">
                                        Author
                                    </div>
                                    <div style="color: var(--admin-text-primary); font-weight: 500;">
                                        <?= htmlspecialchars($authService->getCurrentUsername()) ?>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <div style="margin-bottom: 1rem;">
                                    <div style="color: var(--admin-text-muted); font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 0.25rem;">
                                        Created
                                    </div>
                                    <div style="color: var(--admin-text-primary); font-weight: 500;">
                                        <?= date('M j, Y') ?>
                                    </div>
                                </div>
                                <div style="margin-bottom: 1rem;">
                                    <div style="color: var(--admin-text-muted); font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 0.25rem;">
                                        Last Modified
                                    </div>
                                    <div style="color: var(--admin-text-primary); font-weight: 500;">
                                        <?= date('M j, Y g:i A') ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Revision History -->
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h3 class="admin-card-title">
                            <i class="fas fa-history"></i>Recent Changes
                        </h3>
                    </div>
                    <div class="admin-card-body">
                        <div style="color: var(--admin-text-muted); text-align: center; padding: 2rem;">
                            <i class="fas fa-clock" style="font-size: 2rem; margin-bottom: 1rem; display: block;"></i>
                            <p>Revision history will be displayed here when available.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <aside class="admin-sidebar">
                <!-- Edit Progress -->
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h3 class="admin-card-title">
                            <i class="fas fa-edit"></i>Edit Progress
                        </h3>
                    </div>
                    <div class="admin-card-body">
                        <div style="display: flex; flex-direction: column; gap: 1rem;">
                            <div>
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                                    <span style="font-size: 0.875rem; color: var(--admin-text-secondary);">Current Status</span>
                                    <span id="currentStatus" class="admin-badge admin-badge-warning" style="font-size: 0.625rem;">
                                        <i class="fas fa-edit"></i>Editing
                                    </span>
                                </div>
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                                    <span style="font-size: 0.875rem; color: var(--admin-text-secondary);">Word Count</span>
                                    <span id="wordCount" style="font-size: 0.875rem; color: var(--admin-text-primary); font-weight: 600;">0 words</span>
                                </div>
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                                    <span style="font-size: 0.875rem; color: var(--admin-text-secondary);">Reading Time</span>
                                    <span id="readTime" style="font-size: 0.875rem; color: var(--admin-text-primary); font-weight: 600;">0 min</span>
                                </div>
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <span style="font-size: 0.875rem; color: var(--admin-text-secondary);">Last Saved</span>
                                    <span id="lastSaved" style="font-size: 0.875rem; color: var(--admin-text-primary); font-weight: 600;">Never</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SEO Optimization -->
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h3 class="admin-card-title">
                            <i class="fas fa-search-plus"></i>SEO Health
                        </h3>
                    </div>
                    <div class="admin-card-body">
                        <div style="display: flex; flex-direction: column; gap: 1rem;">
                            <div>
                                <h4 style="display: flex; align-items: center; margin: 0 0 0.5rem 0; font-size: 0.875rem; font-weight: 600;">
                                    <i class="fas fa-heading" style="color: var(--admin-primary); margin-right: 0.5rem;"></i>Title Optimization
                                </h4>
                                <div id="titleOptimization" style="background: var(--admin-bg-secondary); height: 8px; border-radius: 4px; overflow: hidden;">
                                    <div id="titleOptimizationFill" style="background: var(--admin-success); height: 100%; width: 0%; transition: all 0.3s;"></div>
                                </div>
                                <p id="titleOptimizationText" style="font-size: 0.75rem; color: var(--admin-text-secondary); margin: 0.25rem 0 0 0;">Title analysis pending...</p>
                            </div>
                            <div>
                                <h4 style="display: flex; align-items: center; margin: 0 0 0.5rem 0; font-size: 0.875rem; font-weight: 600;">
                                    <i class="fas fa-quote-left" style="color: var(--admin-warning); margin-right: 0.5rem;"></i>Meta Description
                                </h4>
                                <div id="metaOptimization" style="background: var(--admin-bg-secondary); height: 8px; border-radius: 4px; overflow: hidden;">
                                    <div id="metaOptimizationFill" style="background: var(--admin-warning); height: 100%; width: 0%; transition: all 0.3s;"></div>
                                </div>
                                <p id="metaOptimizationText" style="font-size: 0.75rem; color: var(--admin-text-secondary); margin: 0.25rem 0 0 0;">Excerpt analysis pending...</p>
                            </div>
                            <div>
                                <h4 style="display: flex; align-items: center; margin: 0 0 0.5rem 0; font-size: 0.875rem; font-weight: 600;">
                                    <i class="fas fa-link" style="color: var(--admin-info); margin-right: 0.5rem;"></i>URL Structure
                                </h4>
                                <p id="urlPreview" style="font-size: 0.75rem; color: var(--admin-text-secondary); margin: 0; font-family: monospace; background: var(--admin-bg-secondary); padding: 0.5rem; border-radius: 4px;">example.com/news/<?= htmlspecialchars($articleId ?? 'article-slug') ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Content Analysis -->
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h3 class="admin-card-title">
                            <i class="fas fa-analytics"></i>Content Analysis
                        </h3>
                    </div>
                    <div class="admin-card-body">
                        <div style="display: flex; flex-direction: column; gap: 1rem;">
                            <div>
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                                    <span style="font-size: 0.875rem; color: var(--admin-text-secondary);">Readability</span>
                                    <span id="readabilityScore" class="admin-badge admin-badge-success" style="font-size: 0.625rem;">
                                        <i class="fas fa-check"></i>Good
                                    </span>
                                </div>
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                                    <span style="font-size: 0.875rem; color: var(--admin-text-secondary);">Paragraphs</span>
                                    <span id="paragraphCount" style="font-size: 0.875rem; color: var(--admin-text-primary); font-weight: 600;">0</span>
                                </div>
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                                    <span style="font-size: 0.875rem; color: var(--admin-text-secondary);">Sentences</span>
                                    <span id="sentenceCount" style="font-size: 0.875rem; color: var(--admin-text-primary); font-weight: 600;">0</span>
                                </div>
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <span style="font-size: 0.875rem; color: var(--admin-text-secondary);">Avg. Words/Sentence</span>
                                    <span id="avgWordsPerSentence" style="font-size: 0.875rem; color: var(--admin-text-primary); font-weight: 600;">0</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Update Actions -->
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h3 class="admin-card-title">
                            <i class="fas fa-bolt"></i>Quick Actions
                        </h3>
                    </div>
                    <div class="admin-card-body">
                        <button type="button" class="admin-btn admin-btn-warning" style="width: 100%; margin-bottom: 0.5rem; justify-content: flex-start;" onclick="autoSaveArticle()">
                            <i class="fas fa-save"></i>
                            Auto-Save Changes
                        </button>
                        <a href="/index.php?page=news&id=<?= htmlspecialchars($articleId ?? '') ?>" class="admin-btn admin-btn-primary" style="width: 100%; margin-bottom: 0.5rem; justify-content: flex-start;" target="_blank">
                            <i class="fas fa-external-link-alt"></i>
                            Preview Article
                        </a>
                        <a href="/index.php?page=manage_articles" class="admin-btn admin-btn-secondary" style="width: 100%; margin-bottom: 0.5rem; justify-content: flex-start;">
                            <i class="fas fa-list"></i>
                            All Articles
                        </a>
                        <a href="/index.php?page=create_article" class="admin-btn admin-btn-secondary" style="width: 100%; margin-bottom: 0.5rem; justify-content: flex-start;">
                            <i class="fas fa-plus"></i>
                            New Article
                        </a>
                        <a href="/index.php?page=dashboard" class="admin-btn admin-btn-secondary" style="width: 100%; justify-content: flex-start;">
                            <i class="fas fa-tachometer-alt"></i>
                            Dashboard
                        </a>
                    </div>
                </div>

                <!-- Version Control -->
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h3 class="admin-card-title">
                            <i class="fas fa-code-branch"></i>Version Control
                        </h3>
                    </div>
                    <div class="admin-card-body">
                        <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <span style="font-size: 0.875rem; color: var(--admin-text-secondary);">Current Version</span>
                                <span style="font-size: 0.875rem; color: var(--admin-text-primary); font-weight: 600;">v1.0</span>
                            </div>
                            <button type="button" class="admin-btn admin-btn-secondary admin-btn-sm" style="width: 100%; justify-content: flex-start;" onclick="createRevision()">
                                <i class="fas fa-tag"></i>
                                Create Revision
                            </button>
                            <button type="button" class="admin-btn admin-btn-secondary admin-btn-sm" style="width: 100%; justify-content: flex-start;" onclick="showRevisionHistory()">
                                <i class="fas fa-history"></i>
                                View History
                            </button>
                            <button type="button" class="admin-btn admin-btn-secondary admin-btn-sm" style="width: 100%; justify-content: flex-start;" onclick="compareVersions()">
                                <i class="fas fa-code-compare"></i>
                                Compare Changes
                            </button>
                        </div>
                    </div>
                </div>
            </aside>
        </div>
    </main>

    <!-- Admin Scripts -->
    <script src="/public/assets/js/admin.js"></script>

    <script>
        // Auto-generate slug from title (only if slug is empty)
        document.getElementById('title').addEventListener('input', function() {
            const slugField = document.getElementById('slug');

            if (!slugField.value) {
                const slug = this.value
                    .toLowerCase()
                    .trim()
                    .replace(/[^\w\s-]/g, '')
                    .replace(/[\s_-]+/g, '-')
                    .replace(/^-+|-+$/g, '');

                slugField.value = slug;
            }
        });

        // Save draft function
        function saveDraft() {
            document.getElementById('status').value = 'draft';
            document.querySelector('form').submit();
        }

        // Preview function
        function previewArticle() {
            const title = document.getElementById('title').value;
            const content = document.getElementById('content').value;

            if (!title || !content) {
                alert('Please fill in the title and content before previewing.');
                return;
            }

            // Implementation for article preview
            alert('Preview functionality would open the article in a new window/modal');
        }

        // Auto-save functionality
        let autoSaveTimeout;
        const formInputs = document.querySelectorAll('#title, #content, #excerpt');

        formInputs.forEach(input => {
            input.addEventListener('input', function() {
                clearTimeout(autoSaveTimeout);

                autoSaveTimeout = setTimeout(() => {
                    // Auto-save implementation
                    console.log('Auto-saving article...');
                }, 30000); // Auto-save every 30 seconds
            });
        });

        // Word count for content
        const contentField = document.getElementById('content');

        function updateWordCount() {
            const words = contentField.value.trim().split(/\s+/).filter(word => word.length > 0);
            const wordCount = words.length;
            const helpText = contentField.nextElementSibling;

            helpText.textContent = `Update your article content with engaging, valuable information (${wordCount} words)`;
        }

        contentField.addEventListener('input', updateWordCount);
        updateWordCount(); // Initial count
    </script>
