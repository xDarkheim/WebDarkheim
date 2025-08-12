<?php

/**
 * Controller for deleting comments
 * This controller handles the deletion of comments.
 * Users can delete their own comments, administrators can delete any comment.
 *
 * @author Dmytro Hovenko
 */

declare(strict_types=1);

namespace App\Application\Controllers;

use App\Domain\Models\Comments;
use Exception;
use ReflectionException;

class DeleteCommentController extends BaseFormController
{
    /**
     * @throws ReflectionException
     */
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
            $this->flashMessage->addError('Please log in to delete comments.');
            $this->redirect('/index.php?page=login');
            return;
        }

        // Validate CSRF token
        if (!$this->validateCSRF()) {
            $this->flashMessage->addError('Invalid security token.');
            $this->redirectBack();
            return;
        }

        $commentId = filter_input(INPUT_POST, 'comment_id', FILTER_VALIDATE_INT);
        if (!$commentId) {
            $this->flashMessage->addError('Invalid comment ID.');
            $this->redirectBack();
            return;
        }

        try {
            // Get comment details to check ownership
            $comment = Comments::getById($this->services->getDatabase(), $commentId);
            if (!$comment) {
                $this->flashMessage->addError('Comment not found.');
                $this->redirectBack();
                return;
            }

            // Check permissions
            $currentUserId = $this->services->getAuth()->getCurrentUserId();
            $isOwner = (int)$comment['user_id'] === (int)$currentUserId;
            $isAdmin = $this->services->getAuth()->isAdmin();

            if (!$isOwner && !$isAdmin) {
                $this->flashMessage->addError('You do not have permission to delete this comment.');
                $this->redirectBack();
                return;
            }

            // Delete the comment
            if (Comments::deleteById($this->services->getDatabase(), $commentId)) {
                $this->flashMessage->addSuccess('Comment deleted successfully.');
            } else {
                $this->flashMessage->addError('Failed to delete comment. Please try again.');
            }

        } catch (Exception $e) {
            error_log("Error deleting comment: " . $e->getMessage());
            $this->flashMessage->addError('An error occurred while deleting the comment.');
        }

        $this->redirectBack();
    }

    private function redirectBack(): void
    {
        $returnUrl = $_POST['return_url'] ?? '/index.php?page=news';
        
        // Validate return URL to prevent open redirects
        if (!str_starts_with($returnUrl, '/') || str_contains($returnUrl, '//')) {
            $returnUrl = '/index.php?page=news';
        }

        $this->redirect($returnUrl);
    }
}
