<?php
/**
 * Comments Section Component - Professional and clean design
 */

// Get text editor component through ServiceProvider
global $container;
$serviceProvider = \App\Application\Core\ServiceProvider::getInstance($container);
$textEditorComponent = $serviceProvider->getTextEditorComponent();
?>

<!-- Comments List -->
<div class="comments-wrapper">
    <?php if (!empty($data['comments'])) : ?>
        <?php foreach ($data['comments'] as $comment) : ?>
            <article class="comment-item">
                <header class="comment-header">
                    <div class="comment-info">
                        <span class="comment-author"><?php echo htmlspecialchars($comment['author_name']); ?></span>
                        <time class="comment-date"><?php echo date('M j, Y \a\t g:i A', strtotime($comment['created_at'])); ?></time>
                    </div>

                    <?php if ((isset($_SESSION['user_id']) && $comment['user_id'] == $_SESSION['user_id']) ||
                              (isset($_SESSION['user_id']) && isset($data['is_admin']) && $data['is_admin'])): ?>
                        <div class="comment-actions">
                            <?php if (isset($_SESSION['user_id']) && $comment['user_id'] == $_SESSION['user_id']): ?>
                                <button type="button" class="action-link edit-action comment-edit-btn"
                                        data-comment-id="<?php echo $comment['id']; ?>"
                                        data-comment-content="<?php echo htmlspecialchars($comment['content']); ?>">
                                    Edit
                                </button>
                                <form method="POST" action="/index.php?page=delete_comment" class="action-form">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($data['csrf_token']); ?>">
                                    <input type="hidden" name="comment_id" value="<?php echo $comment['id']; ?>">
                                    <input type="hidden" name="return_url" value="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>">
                                    <button type="submit" class="action-link delete-action"
                                            onclick="return confirm('Are you sure you want to delete this comment?')">
                                        Delete
                                    </button>
                                </form>
                            <?php endif; ?>

                            <?php if (isset($_SESSION['user_id']) && isset($data['is_admin']) && $data['is_admin'] && $comment['user_id'] != $_SESSION['user_id']): ?>
                                <form method="POST" action="/index.php?page=delete_comment" class="action-form">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($data['csrf_token']); ?>">
                                    <input type="hidden" name="comment_id" value="<?php echo $comment['id']; ?>">
                                    <input type="hidden" name="return_url" value="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>">
                                    <button type="submit" class="action-link delete-action admin-delete"
                                            onclick="return confirm('Are you sure you want to delete this comment as an administrator?')">
                                        Admin Delete
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </header>

                <div class="comment-body" id="comment-content-<?php echo $comment['id']; ?>">
                    <?php
                    $commentContent = $comment['content'] ?? $comment['comment_text'] ?? '';
                    echo nl2br(htmlspecialchars($commentContent));
                    ?>
                </div>

                <!-- Edit Form -->
                <div class="comment-edit-section" id="edit-form-<?php echo $comment['id']; ?>" style="display: none;">
                    <form action="/index.php?page=edit_comment" method="post" class="edit-form">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($data['csrf_token']); ?>">
                        <input type="hidden" name="comment_id" value="<?php echo $comment['id']; ?>">
                        <input type="hidden" name="return_url" value="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>">

                        <div class="form-field">
                            <?php echo $textEditorComponent->renderCommentEditor("edit_comment_text_{$comment['id']}", $comment['content']); ?>
                        </div>

                        <div class="form-buttons">
                            <button type="submit" class="btn btn-primary btn-sm">Save Changes</button>
                            <button type="button" class="btn btn-secondary btn-sm cancel-edit-btn"
                                    data-comment-id="<?php echo $comment['id']; ?>">Cancel</button>
                        </div>
                    </form>
                </div>
            </article>
        <?php endforeach; ?>
    <?php else : ?>
        <div class="empty-state">
            <p class="empty-message">No comments yet. Be the first to share your thoughts!</p>
        </div>
    <?php endif; ?>
</div>

<!-- Comment Form Section -->
<div class="comment-form-wrapper">
    <?php if (isset($_SESSION['user_id'])) : ?>
            <h3 class="section-heading">Leave a Comment</h3>
            <form action="/index.php?page=form_comment" method="post" class="comment-submission-form">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($data['csrf_token']); ?>">
                <input type="hidden" name="article_id" value="<?php echo $data['article']->id; ?>">
                <input type="hidden" name="author_name" value="<?php echo htmlspecialchars($data['author_name']); ?>">

                <div class="form-field">
                    <label for="comment_text" class="field-label">Your Comment</label>
                    <?php echo $textEditorComponent->renderCommentEditor('comment_text', ''); ?>
                </div>

                <div class="form-submit">
                    <button type="submit" class="btn btn-primary">Post Comment</button>
                </div>
            </form>
    <?php else : ?>
        <div class="auth-prompt">
            <p class="prompt-text">
                Please <a href="/index.php?page=login" class="auth-link">log in</a> to participate in the discussion.
            </p>
        </div>
    <?php endif; ?>
</div>

<style>
/* Comments Section - Minimal and Strict Design */
.comments-wrapper {
    margin: 1rem 0 0 0;
    border-top: 1px solid var(--color-dark-border-light);
    padding-top: 1rem;
}

.comment-item {
    padding: 1rem;
    margin-bottom: 1rem;
    background: var(--color-dark-surface);
    border: 1px solid var(--color-dark-border-light);
    border-radius: 6px;
    transition: background-color 0.15s ease, border-color 0.15s ease;
}

.comment-item:last-child {
    margin-bottom: 0;
}

.comment-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.75rem;
    padding-bottom: 0.5rem;
    border-bottom: 1px solid var(--color-dark-border-light);
}

.comment-info {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.comment-author {
    font-weight: var(--font-weight-semibold);
    color: var(--color-text-primary);
    font-size: var(--font-size-sm);
    font-family: var(--font-family-sans), serif;
}

.comment-date {
    font-size: var(--font-size-xs);
    color: var(--color-text-muted);
    font-weight: var(--font-weight-normal);
}

.comment-actions {
    display: flex;
    gap: 0.5rem;
    align-items: center;
}

.comment-actions .action-link {
    background: #2a2d31 !important;
    border: 1px solid #3a3d41 !important;
    color: #9ca3af !important;
    font-size: 12px !important;
    cursor: pointer !important;
    text-decoration: none !important;
    padding: 0.375rem 0.75rem !important;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif !important;
    font-weight: 500 !important;
    border-radius: 4px !important;
    transition: all 0.15s ease !important;
    display: inline-block !important;
    min-width: auto !important;
    height: auto !important;
    line-height: normal !important;
    box-sizing: border-box !important;
    margin: 0 !important;
    outline: none !important;
}

.comment-actions .action-link:hover {
    border-color: #4a4d51 !important;
    text-decoration: none !important;
    transform: none !important;
    outline: none !important;
}

.comment-actions .edit-action {
    background: #4A90E2 !important;
    border: 1px solid #4A90E2 !important;
    color: #ffffff !important;
    border-radius: 6px !important;
    padding: 0.5rem 1rem !important;
    font-weight: 500 !important;
    font-size: 14px !important;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1) !important;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif !important;
    margin: 0 !important;
    outline: none !important;
}

.comment-actions .edit-action:hover {
    background: #357ABD !important;
    border-color: #357ABD !important;
    color: #ffffff !important;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15) !important;
    outline: none !important;
}

.comment-actions .delete-action {
    background: #6b7280 !important;
    border: 1px solid #6b7280 !important;
    color: #ffffff !important;
    border-radius: 6px !important;
    padding: 0.5rem 1rem !important;
    font-weight: 500 !important;
    font-size: 14px !important;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1) !important;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif !important;
    margin: 0 !important;
    outline: none !important;
}

.comment-actions .delete-action:hover {
    background: #dc2626 !important;
    border-color: #dc2626 !important;
    color: #ffffff !important;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15) !important;
    outline: none !important;
}

.comment-actions .admin-delete:hover {
    background: #f59e0b !important;
    border-color: #f59e0b !important;
    color: #ffffff !important;
    outline: none !important;
}

.action-form {
    display: inline;
    margin: 0;
}

.comment-body {
    color: #e5e7eb !important;
    line-height: 1.6 !important;
    font-size: 14px !important;
}

.comment-edit-section {
    margin-top: 0.75rem;
    padding: 0.75rem;
    background: var(--color-dark-bg);
    border: 1px solid var(--color-border);
    border-radius: 4px;
}

.edit-form .form-field {
    margin-bottom: 0.75rem;
}

.form-buttons {
    display: flex;
    gap: 0.5rem;
    margin-top: 0.75rem;
}

.form-buttons .btn {
    font-weight: var(--font-weight-medium);
    padding: 0.375rem 0.75rem;
    font-size: var(--font-size-xs);
    border-radius: 4px;
    transition: all 0.15s ease;
}

.empty-state {
    padding: 2rem 0;
    text-align: center;
    color: var(--color-text-muted);
}

.empty-message {
    color: var(--color-text-muted);
    font-size: var(--font-size-sm);
    margin: 0;
    font-weight: var(--font-weight-normal);
}

.comment-form-wrapper {
    margin-top: 2rem;
    border-top: 1px solid var(--color-border);
    padding-top: 1.5rem;
}

.section-heading {
    margin: 0 0 1rem 0;
    font-size: var(--font-size-md);
    font-weight: var(--font-weight-semibold);
    color: var(--color-text-primary);
    font-family: var(--font-heading);
}

.comment-submission-form .form-field {
    margin-bottom: 1rem;
}

.field-label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: var(--font-weight-medium);
    color: var(--color-text-primary);
    font-size: var(--font-size-sm);
}

.form-submit {
    margin-top: 1rem;
    display: flex;
    justify-content: flex-start;
}

.form-submit .btn {
    font-weight: var(--font-weight-medium);
    padding: 0.5rem 1rem;
    font-size: var(--font-size-sm);
    border-radius: 4px;
    transition: all 0.15s ease;
}

.auth-prompt {
    padding: 1.5rem;
    background: var(--color-dark-surface);
    border: 1px solid var(--color-border);
    border-radius: 6px;
    text-align: center;
}

.prompt-text {
    margin: 0;
    color: var(--color-text-muted);
    font-size: var(--font-size-sm);
    font-weight: var(--font-weight-normal);
}

.auth-link {
    color: var(--color-accent);
    text-decoration: none;
    font-weight: var(--font-weight-medium);
    transition: color 0.15s ease;
}

.auth-link:hover {
    color: var(--color-accent-hover);
    text-decoration: none;
}

/* Simple textarea styling */
.comment-editor-wrapper .comment-textarea {
    width: 100%;
    min-height: 80px;
    padding: 0.5rem;
    border: 1px solid var(--color-border);
    border-radius: 4px;
    font-family: var(--font-family-sans);
    font-size: var(--font-size-sm);
    line-height: var(--line-height-normal);
    resize: vertical;
    transition: border-color 0.15s ease;
    background: var(--color-dark-bg);
    color: var(--color-text-primary);
}

.comment-editor-wrapper .comment-textarea:focus {
    outline: none;
    border-color: var(--color-accent);
}

.comment-editor-wrapper .comment-textarea::placeholder {
    color: var(--color-text-muted);
    font-style: normal;
}

/* Mobile responsiveness */
@media (max-width: 768px) {
    .comment-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.25rem;
    }

    .comment-actions {
        margin-top: 0.25rem;
    }

    .form-buttons {
        flex-direction: column;
        gap: 0.5rem;
    }

    .form-buttons .btn {
        width: 100%;
    }

    .add-comment-section {
        padding: 0.75rem;
    }

    .form-submit .btn {
        width: 100%;
        justify-content: center;
    }
}
</style>
