<?php
/**
 * Create Article Page - MODERN DARK ADMIN INTERFACE
 *
 * Modern dark administrative interface for creating articles
 * with improved UX and consistent styling
 */

declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/includes/bootstrap.php';

global $serviceProvider, $flashMessageService;

use App\Application\Components\AdminNavigation;

try {
    $authService = $serviceProvider->getAuth();
} catch (Exception $e) {
    error_log("Critical: Failed to get AuthenticationService: " . $e->getMessage());
    die("System error occurred.");
}

// Check authentication
if (!$authService->isAuthenticated()) {
    $flashMessageService->addError('Please log in to access this area.');
    header("Location: /index.php?page=login");
    exit();
}

// Check role permissions - only admin and employee can create articles
$userRole = $authService->getCurrentUserRole();
if (!in_array($userRole, ['admin', 'employee'])) {
    $flashMessageService->addError('Access denied. Insufficient permissions.');
    header("Location: /index.php?page=dashboard");
    exit();
}

$pageTitle = 'Create New Article';

// Create unified navigation
$adminNavigation = new AdminNavigation($authService);

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
                    <i class="admin-header-icon fas fa-plus-circle"></i>
                    <div class="admin-header-text">
                        <h1>Create New Article</h1>
                        <p>Write and publish engaging content for your audience</p>
                    </div>
                </div>

                <div class="admin-header-actions">
                    <a href="/index.php?page=manage_articles" class="admin-btn admin-btn-secondary">
                        <i class="fas fa-arrow-left"></i>Back to Articles
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

                <!-- Permissions Notice -->
                <div class="admin-card admin-glow-primary">
                    <div class="admin-card-body">
                        <div style="display: flex; align-items: center; gap: 1rem;">
                            <div style="color: var(--admin-primary); font-size: 2rem;">
                                <i class="fas fa-shield-alt"></i>
                            </div>
                            <div>
                                <h3 style="margin: 0; color: var(--admin-text-primary);">Administrative Content Creation</h3>
                                <p style="margin: 0.5rem 0 0 0; color: var(--admin-text-secondary);">
                                    This feature is restricted to administrative users only.
                                    Regular users cannot create articles as per the new content policy.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Article Creation Form -->
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h3 class="admin-card-title">
                            <i class="fas fa-edit"></i>Article Details
                        </h3>
                    </div>
                    <div class="admin-card-body">
                        <form method="POST" action="/index.php?page=api_create_article">
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
                                           placeholder="Enter an engaging title for your article"
                                           data-slug-source="#title"
                                           data-slug-target="#slug">
                                    <div class="admin-help-text">
                                        Choose a compelling title that captures your reader's attention
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
                                           placeholder="article-url-slug">
                                    <div class="admin-help-text">
                                        SEO-friendly URL (auto-generated from title, or customize manually)
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
                                              rows="15"
                                              required
                                              placeholder="Write your article content here..."></textarea>
                                    <div class="admin-help-text">
                                        Write engaging, informative content that provides value to your readers
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
                                              placeholder="Brief summary of the article (optional)"></textarea>
                                    <div class="admin-help-text">
                                        Short description that appears in article previews and search results
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
                                        Select relevant categories to help organize your content
                                    </div>
                                </div>

                                <!-- Publication Options -->
                                <div class="admin-form-group">
                                    <label class="admin-label">
                                        <i class="fas fa-cog"></i>Publication Options
                                    </label>
                                    <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                                        <label style="display: flex; align-items: center; gap: 0.5rem; color: var(--admin-text-primary);">
                                            <input type="checkbox" name="featured" value="1" style="margin: 0;">
                                            <span>Feature this article</span>
                                        </label>
                                        <label style="display: flex; align-items: center; gap: 0.5rem; color: var(--admin-text-primary);">
                                            <input type="checkbox" name="allow_comments" value="1" checked style="margin: 0;">
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
                                        <option value="published">Publish Immediately</option>
                                        <?php endif; ?>
                                    </select>
                                    <div class="admin-help-text">
                                        Choose how you want to handle this article's publication
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
                                        <button type="submit" class="admin-btn admin-btn-primary">
                                            <i class="fas fa-paper-plane"></i>Create Article
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
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
                        <div class="admin-grid admin-grid-cols-2">
                            <div>
                                <h4 style="color: var(--admin-text-primary); margin-bottom: 0.5rem;">Content Guidelines</h4>
                                <ul style="color: var(--admin-text-secondary); margin: 0; padding-left: 1.5rem;">
                                    <li>Write clear, engaging headlines</li>
                                    <li>Use short paragraphs for readability</li>
                                    <li>Include relevant keywords naturally</li>
                                    <li>Add value for your readers</li>
                                </ul>
                            </div>
                            <div>
                                <h4 style="color: var(--admin-text-primary); margin-bottom: 0.5rem;">SEO Best Practices</h4>
                                <ul style="color: var(--admin-text-secondary); margin: 0; padding-left: 1.5rem;">
                                    <li>Optimize your title and excerpt</li>
                                    <li>Use descriptive URL slugs</li>
                                    <li>Choose relevant categories</li>
                                    <li>Write meta-friendly descriptions</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <aside class="admin-sidebar">
                <!-- Writing Progress -->
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h3 class="admin-card-title">
                            <i class="fas fa-chart-line"></i>Writing Progress
                        </h3>
                    </div>
                    <div class="admin-card-body">
                        <div style="display: flex; flex-direction: column; gap: 1rem;">
                            <div>
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                                    <span style="font-size: 0.875rem; color: var(--admin-text-secondary);">Title</span>
                                    <span id="titleStatus" class="admin-badge admin-badge-gray" style="font-size: 0.625rem;">
                                        <i class="fas fa-circle"></i>Empty
                                    </span>
                                </div>
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                                    <span style="font-size: 0.875rem; color: var(--admin-text-secondary);">Content</span>
                                    <span id="contentWords" style="font-size: 0.875rem; color: var(--admin-text-primary); font-weight: 600;">0 words</span>
                                </div>
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                                    <span style="font-size: 0.875rem; color: var(--admin-text-secondary);">Reading Time</span>
                                    <span id="readingTime" style="font-size: 0.875rem; color: var(--admin-text-primary); font-weight: 600;">0 min</span>
                                </div>
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <span style="font-size: 0.875rem; color: var(--admin-text-secondary);">Character Count</span>
                                    <span id="charCount" style="font-size: 0.875rem; color: var(--admin-text-primary); font-weight: 600;">0</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SEO Analysis -->
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h3 class="admin-card-title">
                            <i class="fas fa-search"></i>SEO Analysis
                        </h3>
                    </div>
                    <div class="admin-card-body">
                        <div style="display: flex; flex-direction: column; gap: 1rem;">
                            <div>
                                <h4 style="display: flex; align-items: center; margin: 0 0 0.5rem 0; font-size: 0.875rem; font-weight: 600;">
                                    <i class="fas fa-heading" style="color: var(--admin-primary); margin-right: 0.5rem;"></i>Title Length
                                </h4>
                                <div id="titleLengthBar" style="background: var(--admin-bg-secondary); height: 8px; border-radius: 4px; overflow: hidden;">
                                    <div id="titleLengthFill" style="background: var(--admin-success); height: 100%; width: 0%; transition: all 0.3s;"></div>
                                </div>
                                <p id="titleLengthText" style="font-size: 0.75rem; color: var(--admin-text-secondary); margin: 0.25rem 0 0 0;">0/60 characters (optimal: 30-60)</p>
                            </div>
                            <div>
                                <h4 style="display: flex; align-items: center; margin: 0 0 0.5rem 0; font-size: 0.875rem; font-weight: 600;">
                                    <i class="fas fa-quote-left" style="color: var(--admin-warning); margin-right: 0.5rem;"></i>Excerpt Length
                                </h4>
                                <div id="excerptLengthBar" style="background: var(--admin-bg-secondary); height: 8px; border-radius: 4px; overflow: hidden;">
                                    <div id="excerptLengthFill" style="background: var(--admin-warning); height: 100%; width: 0%; transition: all 0.3s;"></div>
                                </div>
                                <p id="excerptLengthText" style="font-size: 0.75rem; color: var(--admin-text-secondary); margin: 0.25rem 0 0 0;">0/160 characters (optimal: 120-160)</p>
                            </div>
                            <div>
                                <h4 style="display: flex; align-items: center; margin: 0 0 0.5rem 0; font-size: 0.875rem; font-weight: 600;">
                                    <i class="fas fa-link" style="color: var(--admin-info); margin-right: 0.5rem;"></i>URL Slug
                                </h4>
                                <p id="slugPreview" style="font-size: 0.75rem; color: var(--admin-text-secondary); margin: 0; font-family: monospace; background: var(--admin-bg-secondary); padding: 0.5rem; border-radius: 4px;">example.com/news/your-article-slug</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Publishing Checklist -->
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h3 class="admin-card-title">
                            <i class="fas fa-tasks"></i>Publishing Checklist
                        </h3>
                    </div>
                    <div class="admin-card-body">
                        <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                            <label style="display: flex; align-items: center; gap: 0.5rem; color: var(--admin-text-primary); cursor: pointer;">
                                <input type="checkbox" id="checkTitle" style="margin: 0;">
                                <span style="font-size: 0.875rem;">Compelling title written</span>
                            </label>
                            <label style="display: flex; align-items: center; gap: 0.5rem; color: var(--admin-text-primary); cursor: pointer;">
                                <input type="checkbox" id="checkContent" style="margin: 0;">
                                <span style="font-size: 0.875rem;">Content is complete (500+ words)</span>
                            </label>
                            <label style="display: flex; align-items: center; gap: 0.5rem; color: var(--admin-text-primary); cursor: pointer;">
                                <input type="checkbox" id="checkExcerpt" style="margin: 0;">
                                <span style="font-size: 0.875rem;">Excerpt summarizes article</span>
                            </label>
                            <label style="display: flex; align-items: center; gap: 0.5rem; color: var(--admin-text-primary); cursor: pointer;">
                                <input type="checkbox" id="checkCategories" style="margin: 0;">
                                <span style="font-size: 0.875rem;">Categories selected</span>
                            </label>
                            <label style="display: flex; align-items: center; gap: 0.5rem; color: var(--admin-text-primary); cursor: pointer;">
                                <input type="checkbox" id="checkSlug" style="margin: 0;">
                                <span style="font-size: 0.875rem;">SEO-friendly URL slug</span>
                            </label>
                            <label style="display: flex; align-items: center; gap: 0.5rem; color: var(--admin-text-primary); cursor: pointer;">
                                <input type="checkbox" id="checkProofread" style="margin: 0;">
                                <span style="font-size: 0.875rem;">Proofread for errors</span>
                            </label>
                        </div>
                        <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--admin-border);">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <span style="font-size: 0.875rem; color: var(--admin-text-secondary);">Ready to publish</span>
                                <span id="checklistProgress" style="font-size: 0.875rem; color: var(--admin-text-primary); font-weight: 600;">0/6</span>
                            </div>
                            <div style="background: var(--admin-bg-secondary); height: 8px; border-radius: 4px; overflow: hidden; margin-top: 0.5rem;">
                                <div id="checklistBar" style="background: var(--admin-success); height: 100%; width: 0%; transition: all 0.3s;"></div>
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
                        <a href="/index.php?page=manage_articles" class="admin-btn admin-btn-primary" style="width: 100%; margin-bottom: 0.5rem; justify-content: flex-start;">
                            <i class="fas fa-list"></i>
                            All Articles
                        </a>
                        <a href="/index.php?page=manage_categories" class="admin-btn admin-btn-secondary" style="width: 100%; margin-bottom: 0.5rem; justify-content: flex-start;">
                            <i class="fas fa-tags"></i>
                            Manage Categories
                        </a>
                        <button type="button" class="admin-btn admin-btn-warning" style="width: 100%; margin-bottom: 0.5rem; justify-content: flex-start;" onclick="autoSave()">
                            <i class="fas fa-save"></i>
                            Auto-Save Draft
                        </button>
                        <a href="/index.php?page=dashboard" class="admin-btn admin-btn-secondary" style="width: 100%; justify-content: flex-start;">
                            <i class="fas fa-tachometer-alt"></i>
                            Dashboard
                        </a>
                    </div>
                </div>
            </aside>
        </div>
    </main>

    <!-- Admin Scripts -->
    <script type="module" src="/public/assets/js/admin.js"></script>

    <script>
        // Auto-generate slug from title
        document.getElementById('title').addEventListener('input', function() {
            const slug = this.value
                .toLowerCase()
                .trim()
                .replace(/[^\w\s-]/g, '')
                .replace(/[\s_-]+/g, '-')
                .replace(/^-+|-+$/g, '');

            document.getElementById('slug').value = slug;
        });

        // Save draft function
        function saveDraft() {
            document.getElementById('status').value = 'draft';
            document.querySelector('form').submit();
        }

        // Preview function
        function previewArticle() {
            // Implementation for article preview
            alert('Preview functionality would open the article in a new window/modal');
        }

        // Character counter for excerpt
        const excerptField = document.getElementById('excerpt');
        const maxLength = 300;

        excerptField.addEventListener('input', function() {
            const remaining = maxLength - this.value.length;
            const helpText = this.nextElementSibling;

            if (remaining < 0) {
                helpText.style.color = 'var(--admin-error)';
                helpText.textContent = `Excerpt is ${Math.abs(remaining)} characters too long`;
            } else {
                helpText.style.color = 'var(--admin-text-muted)';
                helpText.textContent = `Short description that appears in article previews (${remaining} characters remaining)`;
            }
        });

        // Update writing progress
        function updateWritingProgress() {
            const title = document.getElementById('title').value.trim();
            const content = document.getElementById('content').value.trim();
            const excerpt = document.getElementById('excerpt').value.trim();

            // Title progress
            const titleStatus = document.getElementById('titleStatus');
            if (title.length === 0) {
                titleStatus.innerHTML = '<i class="fas fa-circle"></i>Empty';
                titleStatus.className = 'admin-badge admin-badge-gray';
            } else {
                titleStatus.innerHTML = '<i class="fas fa-check-circle"></i>Set';
                titleStatus.className = 'admin-badge admin-badge-success';
            }

            // Content words
            const contentWords = document.getElementById('contentWords');
            const wordCount = content.split(/\s+/).filter(word => word.length > 0).length;
            contentWords.textContent = `${wordCount} words`;

            // Reading time
            const readingTime = document.getElementById('readingTime');
            const estimatedTime = Math.ceil(wordCount / 200);
            readingTime.textContent = `${estimatedTime} min`;

            // Character count
            const charCount = document.getElementById('charCount');
            charCount.textContent = content.length;

            // Title length bar
            const titleLengthFill = document.getElementById('titleLengthFill');
            const titleLengthText = document.getElementById('titleLengthText');
            const titleLengthRatio = Math.min(1, title.length / 60);
            titleLengthFill.style.width = `${titleLengthRatio * 100}%`;
            titleLengthText.textContent = `${title.length}/60 characters (optimal: 30-60)`;

            // Excerpt length bar
            const excerptLengthFill = document.getElementById('excerptLengthFill');
            const excerptLengthText = document.getElementById('excerptLengthText');
            const excerptLengthRatio = Math.min(1, excerpt.length / 160);
            excerptLengthFill.style.width = `${excerptLengthRatio * 100}%`;
            excerptLengthText.textContent = `${excerpt.length}/160 characters (optimal: 120-160)`;

            // Checklist progress
            const checklistItems = [
                'checkTitle',
                'checkContent',
                'checkExcerpt',
                'checkCategories',
                'checkSlug',
                'checkProofread'
            ];
            const checklistProgress = document.getElementById('checklistProgress');
            const checklistBar = document.getElementById('checklistBar');
            const completedItems = checklistItems.filter(id => document.getElementById(id).checked).length;
            checklistProgress.textContent = `${completedItems}/6`;
            checklistBar.style.width = `${(completedItems / 6) * 100}%`;
        }

        // Event listeners for updating progress
        document.getElementById('title').addEventListener('input', updateWritingProgress);
        document.getElementById('content').addEventListener('input', updateWritingProgress);
        document.getElementById('excerpt').addEventListener('input', updateWritingProgress);
        document.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
            checkbox.addEventListener('change', updateWritingProgress);
        });

        // Initial update
        updateWritingProgress();
    </script>

