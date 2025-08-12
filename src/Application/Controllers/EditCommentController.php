<?php

/**
 * Controller for editing comments
 * This controller handles the editing of comments.
 * It validates the form data, updates the comment in the database, and redirects back to the article page.
 * 
 * @author Dmytro Hovenko
 */

declare(strict_types=1);

namespace App\Application\Controllers;

use App\Domain\Models\Comments;
use Exception;

class EditCommentController extends BaseFormController
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
            $this->flashMessage->addError('Please log in to edit comments.');
            $this->redirect('/index.php?page=login');
            return;
        }

        // CSRF validation
        if (!$this->validateCSRF()) {
            $this->logger->warning('Edit comment form: CSRF validation failed', [
                'user_id' => $this->services->getAuth()->getCurrentUserId(),
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
            $this->flashMessage->addError('Security token invalid. Please try again.');
            $this->redirectBack();
            return;
        }

        $commentId = (int)($_POST['comment_id'] ?? 0);

        // Since TinyMCE uses dynamic IDs, look for field with correct name
        if (isset($_POST["edit_comment_text_$commentId"])) {
            $newContent = trim($_POST["edit_comment_text_$commentId"]);
        } else {
            // Fallback for other possible field names
            $newContent = trim($_POST['comment_text'] ?? $_POST['edit_comment_text'] ?? '');
        }

        // Input data validation
        if ($commentId <= 0) {
            $this->flashMessage->addError('Invalid comment ID.');
            $this->redirectBack();
            return;
        }

        if (empty($newContent)) {
            $this->flashMessage->addError('Comment content cannot be empty.');
            $this->redirectBack();
            return;
        }

        if (strlen($newContent) > 2000) {
            $this->flashMessage->addError('Comment is too long (max 2000 characters).');
            $this->redirectBack();
            return;
        }

        try {
            $commentModel = new Comments($this->services->getDatabase());
            $comment = $commentModel->findById($commentId);

            if (!$comment) {
                $this->flashMessage->addError('Comment not found.');
                $this->redirectBack();
                return;
            }

            // Check that the user can edit this comment
            $currentUserId = $this->services->getAuth()->getCurrentUserId();
            $isAdmin = $this->services->getAuth()->isAdmin();

            if ((int)$comment['user_id'] !== $currentUserId && !$isAdmin) {
                $this->flashMessage->addError('You can only edit your own comments.');
                $this->redirectBack();
                return;
            }

            // Update comment
            if ($this->updateComment($commentId, $newContent)) {
                $this->logger->info('Comment edited', [
                    'comment_id' => $commentId,
                    'user_id' => $currentUserId,
                    'article_id' => $comment['article_id']
                ]);

                $this->flashMessage->addSuccess('Comment updated successfully!');
            } else {
                $this->flashMessage->addError('Failed to update comment. Please try again.');
            }

        } catch (Exception $e) {
            $this->logger->error('Comment edit failed', [
                'error' => $e->getMessage(),
                'comment_id' => $commentId,
                'user_id' => $this->services->getAuth()->getCurrentUserId()
            ]);

            $this->flashMessage->addError('An error occurred while updating the comment.');
        }

        $this->redirectBack();
    }

    /**
     * Updates comment content
     */
    private function updateComment(int $commentId, string $newContent): bool
    {
        try {
            $conn = $this->services->getDatabase()->getConnection();

            $stmt = $conn->prepare("
                UPDATE comments 
                SET content = :content, updated_at = NOW() 
                WHERE id = :id
            ");

            return $stmt->execute([
                ':content' => $newContent,
                ':id' => $commentId
            ]);

        } catch (Exception $e) {
            $this->logger->error('Error updating comment in database', [
                'error' => $e->getMessage(),
                'comment_id' => $commentId
            ]);
            return false;
        }
    }

    /**
     * Redirect back to the page where the request came from
     */
    private function redirectBack(): void
    {
        $returnUrl = $_POST['return_url'] ?? '/index.php?page=news';
        $this->redirect($returnUrl);
    }
}
