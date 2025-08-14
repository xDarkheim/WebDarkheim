<?php

/**
 * Comment Service for handling comment business logic
 * Provides methods for comment CRUD operations, moderation, and threading
 *
 * @author GitHub Copilot
 */

declare(strict_types=1);

namespace App\Application\Services;

use App\Domain\Interfaces\DatabaseInterface;
use App\Domain\Interfaces\LoggerInterface;
use App\Domain\Models\Comments;
use App\Domain\Models\User;
use Exception;
use PDO;

class CommentService
{
    private DatabaseInterface $db_handler;
    private LoggerInterface $logger;
    private Comments $commentModel;

    public function __construct(DatabaseInterface $db_handler, LoggerInterface $logger)
    {
        $this->db_handler = $db_handler;
        $this->logger = $logger;
        $this->commentModel = new Comments($db_handler);
    }

    /**
     * Create a new comment
     */
    public function createComment(array $data): array
    {
        try {
            // Validate required fields
            $requiredFields = ['commentable_type', 'commentable_id', 'content', 'author_name', 'author_email'];
            foreach ($requiredFields as $field) {
                if (empty($data[$field])) {
                    return ['success' => false, 'error' => "Field {$field} is required"];
                }
            }

            // Validate commentable_type
            if (!in_array($data['commentable_type'], ['article', 'portfolio_project'])) {
                return ['success' => false, 'error' => 'Invalid commentable type'];
            }

            // Check if commentable item exists
            if (!$this->commentableExists($data['commentable_type'], (int)$data['commentable_id'])) {
                return ['success' => false, 'error' => 'Commentable item not found'];
            }

            // Sanitize content
            $data['content'] = htmlspecialchars(trim($data['content']), ENT_QUOTES, 'UTF-8');
            
            // Set default status based on user role
            $data['status'] = $this->getDefaultStatus($data['user_id'] ?? null);
            
            // Add metadata
            $data['ip_address'] = $_SERVER['REMOTE_ADDR'] ?? null;
            $data['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? null;

            $commentId = $this->commentModel->createComment($data);
            
            if ($commentId) {
                $this->logger->info("Comment created", ['comment_id' => $commentId, 'user_id' => $data['user_id'] ?? 'guest']);
                return ['success' => true, 'comment_id' => $commentId];
            } else {
                return ['success' => false, 'error' => 'Failed to create comment'];
            }

        } catch (Exception $e) {
            $this->logger->error("Error creating comment: " . $e->getMessage());
            return ['success' => false, 'error' => 'Database error occurred'];
        }
    }

    /**
     * Get comments for a specific item with threading support
     */
    public function getComments(string $commentableType, int $commentableId, bool $includeUnapproved = false): array
    {
        try {
            $db = $this->db_handler->getConnection();
            
            $statusCondition = $includeUnapproved ? "" : "AND status = 'approved'";
            
            $sql = "SELECT c.*, u.username as user_username 
                    FROM comments c 
                    LEFT JOIN users u ON c.user_id = u.id 
                    WHERE c.commentable_type = ? AND c.commentable_id = ? {$statusCondition}
                    ORDER BY c.created_at ASC";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([$commentableType, $commentableId]);
            $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Organize comments into threaded structure
            return $this->organizeCommentsIntoThreads($comments);
            
        } catch (Exception $e) {
            $this->logger->error("Error fetching comments: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Moderate a comment (approve/reject)
     */
    public function moderateComment(int $commentId, string $status, int $moderatorId, ?string $rejectionReason = null): array
    {
        try {
            if (!in_array($status, ['approved', 'rejected'])) {
                return ['success' => false, 'error' => 'Invalid status'];
            }

            $db = $this->db_handler->getConnection();
            
            $sql = "UPDATE comments 
                    SET status = ?, moderated_by = ?, moderated_at = NOW(), rejection_reason = ?
                    WHERE id = ?";
            
            $stmt = $db->prepare($sql);
            $result = $stmt->execute([$status, $moderatorId, $rejectionReason, $commentId]);
            
            if ($result) {
                $this->logger->info("Comment moderated", [
                    'comment_id' => $commentId, 
                    'status' => $status, 
                    'moderator_id' => $moderatorId
                ]);
                return ['success' => true];
            } else {
                return ['success' => false, 'error' => 'Failed to moderate comment'];
            }
            
        } catch (Exception $e) {
            $this->logger->error("Error moderating comment: " . $e->getMessage());
            return ['success' => false, 'error' => 'Database error occurred'];
        }
    }

    /**
     * Delete a comment (soft delete by marking as rejected)
     */
    public function deleteComment(int $commentId, int $userId): array
    {
        try {
            $db = $this->db_handler->getConnection();
            
            // Check if user owns the comment or is admin
            $stmt = $db->prepare("SELECT user_id FROM comments WHERE id = ?");
            $stmt->execute([$commentId]);
            $comment = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$comment) {
                return ['success' => false, 'error' => 'Comment not found'];
            }

            // Check permissions
            $user = User::findById($this->db_handler, $userId);
            if (!$user || ($comment['user_id'] != $userId && !in_array($user['role'] ?? 'user', ['admin', 'employee']))) {
                return ['success' => false, 'error' => 'Permission denied'];
            }

            // Mark as rejected (soft delete)
            $stmt = $db->prepare("UPDATE comments SET status = 'rejected' WHERE id = ?");
            $result = $stmt->execute([$commentId]);
            
            if ($result) {
                $this->logger->info("Comment deleted", ['comment_id' => $commentId, 'user_id' => $userId]);
                return ['success' => true];
            } else {
                return ['success' => false, 'error' => 'Failed to delete comment'];
            }
            
        } catch (Exception $e) {
            $this->logger->error("Error deleting comment: " . $e->getMessage());
            return ['success' => false, 'error' => 'Database error occurred'];
        }
    }

    /**
     * Get comments pending moderation
     */
    public function getPendingComments(int $limit = 50): array
    {
        try {
            $db = $this->db_handler->getConnection();
            
            $sql = "SELECT c.*, u.username as user_username,
                           CASE 
                               WHEN c.commentable_type = 'article' THEN a.title
                               WHEN c.commentable_type = 'portfolio_project' THEN cp.title
                           END as item_title
                    FROM comments c 
                    LEFT JOIN users u ON c.user_id = u.id
                    LEFT JOIN articles a ON c.commentable_type = 'article' AND c.commentable_id = a.id
                    LEFT JOIN client_portfolio cp ON c.commentable_type = 'portfolio_project' AND c.commentable_id = cp.id
                    WHERE c.status = 'pending' 
                    ORDER BY c.created_at DESC 
                    LIMIT ?";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([$limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            $this->logger->error("Error fetching pending comments: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Check if commentable item exists
     */
    private function commentableExists(string $type, int $id): bool
    {
        try {
            $db = $this->db_handler->getConnection();
            
            if ($type === 'article') {
                $stmt = $db->prepare("SELECT id FROM articles WHERE id = ? LIMIT 1");
            } elseif ($type === 'portfolio_project') {
                $stmt = $db->prepare("SELECT id FROM client_portfolio WHERE id = ? LIMIT 1");
            } else {
                return false;
            }
            
            $stmt->execute([$id]);
            return $stmt->fetch() !== false;
            
        } catch (Exception $e) {
            $this->logger->error("Error checking commentable existence: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get default comment status based on user role
     */
    private function getDefaultStatus(?int $userId): string
    {
        if (!$userId) {
            return 'pending'; // Guest comments need moderation
        }

        try {
            $user = User::findById($this->db_handler, $userId);
            if ($user && in_array($user['role'], ['admin', 'employee'])) {
                return 'approved'; // Staff comments auto-approved
            }
        } catch (Exception $e) {
            $this->logger->error("Error getting user role: " . $e->getMessage());
        }

        return 'pending'; // Default to pending for regular users
    }

    /**
     * Organize flat comments array into threaded structure
     */
    private function organizeCommentsIntoThreads(array $comments): array
    {
        $threaded = [];
        $lookup = [];

        foreach ($comments as $comment) {
            $comment['replies'] = [];
            $lookup[$comment['id']] = &$comment;

            if ($comment['parent_id'] === null) {
                $threaded[] = &$comment;
            } else {
                if (isset($lookup[$comment['parent_id']])) {
                    $lookup[$comment['parent_id']]['replies'][] = &$comment;
                }
            }
        }

        return $threaded;
    }
}
