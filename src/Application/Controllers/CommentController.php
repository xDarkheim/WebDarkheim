<?php

/**
 * Comment controller handling comment operations
 * Following Single Responsibility Principle and modern PHP practices
 *
 * @author Dmytro Hovenko
 */

declare(strict_types=1);

namespace App\Application\Controllers;

use App\Application\Core\ServiceProvider;
use App\Domain\Interfaces\DatabaseInterface;
use App\Domain\Interfaces\LoggerInterface;
use App\Domain\Interfaces\AuthenticationInterface;
use App\Domain\Models\Comments;
use Exception;

class CommentController
{
    private ServiceProvider $services;
    private AuthenticationInterface $auth;
    private DatabaseInterface $database;
    private LoggerInterface $logger;

    public function __construct()
    {
        $this->services = ServiceProvider::getInstance();
        $this->auth = $this->services->getAuth();
        $this->database = $this->services->getDatabase();
        $this->logger = $this->services->getLogger();
    }

    /**
     * Add a new comment
     */
    public function add(): void
    {
        // Validate request method
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'error' => 'Invalid request method']);
            return;
        }

        // Check authentication
        if (!$this->auth->isAuthenticated()) {
            $this->jsonResponse(['success' => false, 'error' => 'Authentication required']);
            return;
        }

        // CSRF protection
        if (!$this->validateCSRF()) {
            $this->logger->warning('Comment add: CSRF validation failed', [
                'user_id' => $this->auth->getCurrentUserId(),
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
            $this->jsonResponse(['success' => false, 'error' => 'Security token invalid']);
            return;
        }

        $articleId = (int)($_POST['article_id'] ?? 0);
        $content = trim($_POST['content'] ?? '');
        $parentId = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;

        // Validate input
        if (empty($content)) {
            $this->jsonResponse(['success' => false, 'error' => 'Comment content is required']);
            return;
        }

        if ($articleId <= 0) {
            $this->jsonResponse(['success' => false, 'error' => 'Invalid article ID']);
            return;
        }

        try {
            // Create a comment using proper dependency injection
            $commentModel = new Comments($this->database);

            // Use instance method instead of static
            $commentId = $commentModel->createComment([
                'article_id' => $articleId,
                'user_id' => $this->auth->getCurrentUserId(),
                'content' => $content,
                'parent_id' => $parentId
            ]);

            if ($commentId) {
                $this->logger->info('Comment added successfully', [
                    'comment_id' => $commentId,
                    'user_id' => $this->auth->getCurrentUserId(),
                    'article_id' => $articleId
                ]);

                // Get the created comment with user information
                $comment = $commentModel->findByIdWithUser($commentId);

                $this->jsonResponse([
                    'success' => true,
                    'message' => 'Comment added successfully',
                    'comment' => $comment
                ]);
            } else {
                throw new Exception('Failed to create comment');
            }
        } catch (Exception $e) {
            $this->logger->error('Comment creation failed', [
                'error' => $e->getMessage(),
                'user_id' => $this->auth->getCurrentUserId(),
                'article_id' => $articleId
            ]);

            $this->jsonResponse(['success' => false, 'error' => 'Failed to add comment']);
        }
    }

    /**
     * Edit a comment
     */

    public function edit(): void
    {
        // Validate request method
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'error' => 'Invalid request method']);
            return;
        }

        // Check authentication
        if (!$this->auth->isAuthenticated()) {
            $this->jsonResponse(['success' => false, 'error' => 'Authentication required']);
            return;
        }

        // CSRF protection
        if (!$this->validateCSRF()) {
            $this->jsonResponse(['success' => false, 'error' => 'Security token invalid']);
            return;
        }

        $commentId = (int)($_POST['comment_id'] ?? 0);
        $content = trim($_POST['content'] ?? '');

        // Validate input
        if ($commentId <= 0) {
            $this->jsonResponse(['success' => false, 'error' => 'Invalid comment ID']);
            return;
        }

        if (empty($content)) {
            $this->jsonResponse(['success' => false, 'error' => 'Comment content is required']);
            return;
        }

        if (strlen($content) > 2000) {
            $this->jsonResponse(['success' => false, 'error' => 'Comment is too long (max 2000 characters)']);
            return;
        }

        try {
            $commentModel = new Comments($this->database);
            $comment = $commentModel->findById($commentId);

            if (!$comment) {
                $this->jsonResponse(['success' => false, 'error' => 'Comment not found']);
                return;
            }

            // Check ownership or admin privileges
            $currentUserId = $this->auth->getCurrentUserId();
            $isAdmin = $this->auth->getCurrentUserRole() === 'admin';

            if ($comment['user_id'] !== $currentUserId && !$isAdmin) {
                $this->jsonResponse(['success' => false, 'error' => 'Permission denied']);
                return;
            }

            // Update comment using an instance method
            $success = $this->updateComment($commentId, ['content' => $content]);

            if ($success) {
                $this->logger->info('Comment updated successfully', [
                    'comment_id' => $commentId,
                    'user_id' => $currentUserId,
                    'is_admin' => $isAdmin
                ]);

                $this->jsonResponse([
                    'success' => true,
                    'message' => 'Comment updated successfully'
                ]);
            } else {
                throw new Exception('Failed to update comment');
            }

        } catch (Exception $e) {
            $this->logger->error('Comment update failed', [
                'error' => $e->getMessage(),
                'comment_id' => $commentId,
                'user_id' => $this->auth->getCurrentUserId()
            ]);

            $this->jsonResponse(['success' => false, 'error' => 'Failed to update comment. Please try again.']);
        }
    }

    /**
     * Delete a comment
     */

    public function delete(): void
    {
        // Validate request method
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'error' => 'Invalid request method']);
            return;
        }

        // Check authentication
        if (!$this->auth->isAuthenticated()) {
            $this->jsonResponse(['success' => false, 'error' => 'Authentication required']);
            return;
        }

        // CSRF protection
        if (!$this->validateCSRF()) {
            $this->jsonResponse(['success' => false, 'error' => 'Security token invalid']);
            return;
        }

        $commentId = (int)($_POST['comment_id'] ?? 0);

        // Validate input
        if ($commentId <= 0) {
            $this->jsonResponse(['success' => false, 'error' => 'Invalid comment ID']);
            return;
        }

        try {
            $commentModel = new Comments($this->database);
            $comment = $commentModel->findById($commentId);

            if (!$comment) {
                $this->jsonResponse(['success' => false, 'error' => 'Comment not found']);
                return;
            }

            // Check ownership or admin privileges
            $currentUserId = $this->auth->getCurrentUserId();
            $isAdmin = $this->auth->getCurrentUserRole() === 'admin';

            if ($comment['user_id'] !== $currentUserId && !$isAdmin) {
                $this->jsonResponse(['success' => false, 'error' => 'Permission denied']);
                return;
            }

            // Softly delete comment using an instance method
            $success = $this->softDeleteComment($commentId);

            if ($success) {
                $this->logger->info('Comment deleted successfully', [
                    'comment_id' => $commentId,
                    'user_id' => $currentUserId,
                    'is_admin' => $isAdmin
                ]);

                $this->jsonResponse([
                    'success' => true,
                    'message' => 'Comment deleted successfully'
                ]);
            } else {
                throw new Exception('Failed to delete comment');
            }

        } catch (Exception $e) {
            $this->logger->error('Comment deletion failed', [
                'error' => $e->getMessage(),
                'comment_id' => $commentId,
                'user_id' => $this->auth->getCurrentUserId()
            ]);

            $this->jsonResponse(['success' => false, 'error' => 'Failed to delete comment. Please try again.']);
        }
    }

    /**
     * Update comment content
     */

    private function updateComment(int $commentId, array $data): bool
    {
        try {
            $conn = $this->database->getConnection();

            $sql = "UPDATE comments SET content = :content, updated_at = NOW() WHERE id = :id";
            $stmt = $conn->prepare($sql);

            return $stmt->execute([
                ':content' => $data['content'],
                ':id' => $commentId
            ]);
        } catch (Exception $e) {
            $this->logger->error('Comment update failed', [
                'error' => $e->getMessage(),
                'comment_id' => $commentId
            ]);
            return false;
        }
    }

    /**
     * Soft delete comment
     */

    private function softDeleteComment(int $commentId): bool
    {
        try {
            $conn = $this->database->getConnection();

            $sql = "UPDATE comments SET status = 'deleted', deleted_at = NOW() WHERE id = :id";
            $stmt = $conn->prepare($sql);

            return $stmt->execute([':id' => $commentId]);
        } catch (Exception $e) {
            $this->logger->error('Comment soft delete failed', [
                'error' => $e->getMessage(),
                'comment_id' => $commentId
            ]);
            return false;
        }
    }

    /**
     * Validate CSRF token
     */

    private function validateCSRF(): bool
    {
        $token = $_POST['csrf_token'] ?? '';
        return !empty($token) && hash_equals($_SESSION['csrf_token'] ?? '', $token);
    }

    /**
     * Send JSON response
     */

    private function jsonResponse(array $data): void
    {
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}
