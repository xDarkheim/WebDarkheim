<?php
/**
 * Single Article View - Professional design in the style of the main news page
 *
 * @var array $data Data for displaying the article
 * @var array $flashMessages Flash messages (optional)
 */

// Check for the presence of the article, if not - show an error
if (!isset($data['article'])) {
    echo '<div class="container"><div class="message message--error"><p>Article not found.</p></div></div>';
    return;
}

// Get ServiceProvider for access to TextEditorComponent
global $container;
$serviceProvider = \App\Application\Core\ServiceProvider::getInstance($container);
?>

<!-- Professional Article Header -->
<header class="news-header">
    <div class="container">
        <div class="news-header-content">
            <div>
                <nav class="breadcrumb">
                    <a href="/index.php?page=news" class="breadcrumb-link">
                        <i class="fas fa-newspaper"></i>
                        News Hub
                    </a>
                    <span class="breadcrumb-separator">/</span>
                    <span class="breadcrumb-current">Article</span>
                </nav>
                <h1 class="news-title"><?php echo htmlspecialchars($data['article']->title); ?></h1>
                <div class="article-meta">
                    <time class="article-date">
                        <i class="fas fa-calendar-alt"></i>
                        <?php echo htmlspecialchars(date('F j, Y', strtotime($data['article']->date))); ?>
                    </time>
                    <span class="article-read-time">
                        <i class="fas fa-clock"></i>
                        <?php 
                        $wordCount = str_word_count(strip_tags($data['article']->full_text));
                        $readTime = max(1, ceil($wordCount / 200));
                        echo $readTime . ' min read';
                        ?>
                    </span>
                </div>
            </div>
        </div>
    </div>
</header>

<div class="container">
    <div class="news-content">
        <main class="article-main">
            <!-- Article Content -->
            <article class="article-content">
                <div class="article-body formatted-content">
                    <?php
                    // Use the text editor component for correct formatting of Article Content
                    echo $serviceProvider->getTextEditorComponent()->formatContent($data['article']->full_text);
                    ?>
                </div>
            </article>

            <!-- Article Navigation -->
            <?php include __DIR__ . '/_article_navigation.php'; ?>

            <!-- Comments Section -->
            <section class="comments-section">
                <div class="section-header">
                    <h2 class="section-title">
                        <i class="fas fa-comments"></i>
                        Comments
                        <span class="comments-count">(<?php echo count($data['comments']); ?>)</span>
                    </h2>
                </div>
                <?php include __DIR__ . '/_comments_section.php'; ?>
            </section>
        </main>

        <!-- Sidebar -->
        <aside class="news-sidebar">
            <!-- Article Info Widget -->
            <div class="sidebar-widget">
                <h3 class="widget-title">
                    <i class="fas fa-info-circle"></i>
                    Article Info
                </h3>
                <div class="widget-content">
                    <div class="article-stats">
                        <div class="stat-item">
                            <span class="stat-label">Published:</span>
                            <span class="stat-value"><?php echo date('M j, Y', strtotime($data['article']->date)); ?></span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">Reading time:</span>
                            <span class="stat-value"><?php echo $readTime; ?> min</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">Words:</span>
                            <span class="stat-value"><?php echo $wordCount; ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Back to News Widget -->
            <div class="sidebar-widget">
                <h3 class="widget-title">
                    <i class="fas fa-arrow-left"></i>
                    Navigation
                </h3>
                <div class="widget-content">
                    <a href="/index.php?page=news" class="btn btn-secondary btn-block">
                        <i class="fas fa-list"></i>
                        Back to All Articles
                    </a>
                </div>
            </div>
        </aside>
    </div>
</div>

<!-- Simple Comment Form JavaScript (no TinyMCE needed) -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('Initializing comment forms...');

    // Prevent TinyMCE initialization on comment textareas
    if (typeof tinymce !== 'undefined') {
        // Remove any existing TinyMCE instances from comment textareas
        document.querySelectorAll('.comment-textarea, .no-tinymce').forEach(function(textarea) {
            if (tinymce.get(textarea.id)) {
                tinymce.get(textarea.id).remove();
            }
        });

        // Override TinyMCE selector to exclude comment textareas
        const originalInit = tinymce.init;
        tinymce.init = function(config) {
            if (config.selector) {
                config.selector += ':not(.comment-textarea):not(.no-tinymce)';
            }
            return originalInit.call(this, config);
        };
    }

    // Comment editing functionality
    const editButtons = document.querySelectorAll('.comment-edit-btn');
    const cancelButtons = document.querySelectorAll('.cancel-edit-btn');

    editButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const commentId = this.getAttribute('data-comment-id');
            const editForm = document.getElementById('edit-form-' + commentId);
            const commentContent = document.getElementById('comment-content-' + commentId);

            if (editForm && commentContent) {
                editForm.style.display = 'block';
                commentContent.style.display = 'none';
                this.style.display = 'none';

                // Focus on the textarea and ensure it's NOT a TinyMCE editor
                const editTextarea = editForm.querySelector('textarea');
                if (editTextarea) {
                    // Make sure TinyMCE doesn't initialize on this textarea
                    editTextarea.classList.add('no-tinymce', 'comment-textarea');

                    // Remove any TinyMCE instance if it exists
                    if (typeof tinymce !== 'undefined' && tinymce.get(editTextarea.id)) {
                        tinymce.get(editTextarea.id).remove();
                    }

                    editTextarea.focus();
                }
            }
        });
    });

    cancelButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const commentId = this.getAttribute('data-comment-id');
            const editForm = document.getElementById('edit-form-' + commentId);
            const commentContent = document.getElementById('comment-content-' + commentId);
            const editButton = document.querySelector(`[data-comment-id="${commentId}"].comment-edit-btn`);

            if (editForm && commentContent && editButton) {
                editForm.style.display = 'none';
                commentContent.style.display = 'block';
                editButton.style.display = 'inline-block';

                // Make sure no TinyMCE instance remains
                const editTextarea = editForm.querySelector('textarea');
                if (editTextarea && typeof tinymce !== 'undefined' && tinymce.get(editTextarea.id)) {
                    tinymce.get(editTextarea.id).remove();
                }
            }
        });
    });

    // Auto-resize textareas
    const textareas = document.querySelectorAll('.comment-textarea');
    textareas.forEach(textarea => {
        textarea.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = Math.min(this.scrollHeight, 200) + 'px';
        });
    });
});
</script>
