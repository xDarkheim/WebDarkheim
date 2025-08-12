<?php

/**
 * Controller for comment form submission
 * This controller handles the submission of comment forms.
 * It validates the form data, creates a comment, and redirects back to the article page.
 *
 * @author Dmytro Hovenko
 */

declare(strict_types=1);

namespace App\Application\Controllers;

use App\Domain\Models\Comments;
use Exception;


class CommentFormController extends BaseFormController
{
    public function handle(): void
    {
        // Check request method
        if (!$this->requirePostMethod()) {
            $this->flashMessage->addError('Invalid request method.');
            $this->redirect('/index.php?page=news');
            return;
        }

        // Check authentication
        if (!$this->services->getAuth()->isAuthenticated()) {
            $this->flashMessage->addError('Please log in to leave a comment.');
            $this->redirect('/index.php?page=login');
            return;
        }

        $articleId = (int)($_POST['article_id'] ?? 0);

        // Validate CSRF
        if (!$this->validateCSRF()) {
            $this->logger->warning('Comment form: CSRF validation failed', [
                'user_id' => $this->services->getAuth()->getCurrentUserId(),
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
            $this->flashMessage->addError('Security token invalid. Please try again.');
            $this->redirect("/index.php?page=news&id=$articleId");
            return;
        }

        $content = trim($_POST['comment_text'] ?? $_POST['content'] ?? '');
        $parentId = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;

        // Validate input
        if (empty($content)) {
            $this->flashMessage->addError('Comment content is required.');
            $this->redirect("/index.php?page=news&id=$articleId");
            return;
        }

        if ($articleId <= 0) {
            $this->flashMessage->addError('Invalid article ID.');
            $this->redirect('/index.php?page=news');
            return;
        }

        if (strlen($content) > 2000) {
            $this->flashMessage->addError('Comment is too long (max 2000 characters).');
            $this->redirect("/index.php?page=news&id=$articleId");
            return;
        }

        // Check if an article exists
        if (!$this->validateArticleExists($articleId)) {
            $this->flashMessage->addError('Article not found.');
            $this->redirect('/index.php?page=news');
            return;
        }

        // Check if the parent comment exists and is approved
        if ($parentId && !$this->validateParentComment($parentId, $articleId)) {
            $this->flashMessage->addError('Invalid parent comment.');
            $this->redirect("/index.php?page=news&id=$articleId");
            return;
        }

        try {
            // Make sure to use the correct database connection
            $commentModel = new Comments($this->services->getDatabase());
            $commentId = $commentModel->createComment([
                'article_id' => $articleId,
                'user_id' => $this->services->getAuth()->getCurrentUserId(),
                'content' => $content,
                'parent_id' => $parentId
            ]);

            if ($commentId) {
                $this->logger->info('Comment added via form', [
                    'comment_id' => $commentId,
                    'user_id' => $this->services->getAuth()->getCurrentUserId(),
                    'article_id' => $articleId,
                    'parent_id' => $parentId
                ]);

                // Using FlashMessage for a success message
                $this->flashMessage->addSuccess('Comment added successfully!');

                // Redirect back to the article page
                $this->redirect("/index.php?page=news&id=$articleId");
            } else {
                throw new Exception('Failed to create comment');
            }
        } catch (Exception $e) {
            $this->logger->error('Comment form submission failed', [
                'error' => $e->getMessage(),
                'user_id' => $this->services->getAuth()->getCurrentUserId(),
                'article_id' => $articleId
            ]);

            // Using FlashMessage for an error message
            $this->flashMessage->addError('Failed to add comment. Please try again.');

            // Redirect back to the article page
            $this->redirect("/index.php?page=news&id=$articleId");
        }
    }

    /**
     * Check if the article exists
     */
    private function validateArticleExists(int $articleId): bool
    {
        try {
            $conn = $this->services->getDatabase()->getConnection();

            // Check if the article exists in the database
            $stmt = $conn->prepare("SELECT id FROM articles WHERE id = ? LIMIT 1");
            $stmt->execute([$articleId]);
            return $stmt->fetch() !== false;

        } catch (Exception $e) {
            $this->logger->error('Article validation failed', [
                'error' => $e->getMessage(),
                'article_id' => $articleId
            ]);
            return false;
        }
    }

    /**
     * Check if the parent comment exists and is approved
     */
    private function validateParentComment(int $parentId, int $articleId): bool
    {
        try {
            $commentModel = new Comments($this->services->getDatabase());
            $parentComment = $commentModel->findById($parentId);
            
            return $parentComment && 
                   (int)$parentComment['article_id'] === $articleId &&
                   (int)$parentComment['is_approved'] === 1; // Используем is_approved вместо status

        } catch (Exception $e) {
            $this->logger->error('Parent comment validation failed', [
                'error' => $e->getMessage(),
                'parent_id' => $parentId,
                'article_id' => $articleId
            ]);
            return false;
        }
    }
}
