<?php

/**
 * Comments model for the new commenting system
 * Supports comments on articles and client portfolio projects with threading
 *
 * @author GitHub Copilot
 */

declare(strict_types=1);

namespace App\Domain\Models;

use App\Domain\Interfaces\DatabaseInterface;
use PDO;
use PDOException;

class Comments
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    public const TYPE_ARTICLE = 'article';
    public const TYPE_PORTFOLIO = 'portfolio_project';

    private PDO $db;

    public function __construct(DatabaseInterface $database_handler)
    {
        $this->db = $database_handler->getConnection();
    }

    /**
     * Create a new comment
     */
    public function createComment(array $data): ?int
    {
        try {
            $sql = "INSERT INTO comments (
                        commentable_type, commentable_id, user_id, author_name, author_email, 
                        content, status, parent_id, thread_level, ip_address, user_agent, created_at
                    ) VALUES (
                        :commentable_type, :commentable_id, :user_id, :author_name, :author_email,
                        :content, :status, :parent_id, :thread_level, :ip_address, :user_agent, NOW()
                    )";

            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                ':commentable_type' => $data['commentable_type'],
                ':commentable_id' => $data['commentable_id'],
                ':user_id' => $data['user_id'] ?? null,
                ':author_name' => $data['author_name'],
                ':author_email' => $data['author_email'],
                ':content' => $data['content'],
                ':status' => $data['status'] ?? self::STATUS_PENDING,
                ':parent_id' => $data['parent_id'] ?? null,
                ':thread_level' => $this->calculateThreadLevel($data['parent_id'] ?? null),
                ':ip_address' => $data['ip_address'] ?? null,
                ':user_agent' => $data['user_agent'] ?? null
            ]);

            return $result ? (int)$this->db->lastInsertId() : null;

        } catch (PDOException $e) {
            error_log("Error creating comment: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get comments by commentable item
     */
    public function getCommentsByItem(string $commentableType, int $commentableId, bool $includeUnapproved = false): array
    {
        try {
            $statusCondition = $includeUnapproved ? "" : "AND c.status = 'approved'";

            $sql = "SELECT c.*, u.username as user_username, u.id as user_id_ref
                    FROM comments c 
                    LEFT JOIN users u ON c.user_id = u.id 
                    WHERE c.commentable_type = :commentable_type 
                    AND c.commentable_id = :commentable_id 
                    {$statusCondition}
                    ORDER BY c.created_at ASC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':commentable_type' => $commentableType,
                ':commentable_id' => $commentableId
            ]);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error fetching comments: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get comment by ID
     */
    public function getCommentById(int $commentId): ?array
    {
        try {
            $sql = "SELECT c.*, u.username as user_username 
                    FROM comments c 
                    LEFT JOIN users u ON c.user_id = u.id 
                    WHERE c.id = :id LIMIT 1";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $commentId]);

            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;

        } catch (PDOException $e) {
            error_log("Error fetching comment by ID: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Update comment content
     */
    public function updateComment(int $commentId, string $content): bool
    {
        try {
            $sql = "UPDATE comments SET content = :content, updated_at = NOW() WHERE id = :id";
            $stmt = $this->db->prepare($sql);

            return $stmt->execute([
                ':content' => $content,
                ':id' => $commentId
            ]);

        } catch (PDOException $e) {
            error_log("Error updating comment: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update comment status (for moderation)
     */
    public function updateCommentStatus(int $commentId, string $status, int $moderatorId, ?string $rejectionReason = null): bool
    {
        try {
            $sql = "UPDATE comments 
                    SET status = :status, moderated_by = :moderator_id, moderated_at = NOW(), rejection_reason = :rejection_reason
                    WHERE id = :id";

            $stmt = $this->db->prepare($sql);

            return $stmt->execute([
                ':status' => $status,
                ':moderator_id' => $moderatorId,
                ':rejection_reason' => $rejectionReason,
                ':id' => $commentId
            ]);

        } catch (PDOException $e) {
            error_log("Error updating comment status: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get pending comments for moderation
     */
    public function getPendingComments(int $limit = 50): array
    {
        try {
            // Simplified query without references to potentially non-existent tables
            $sql = "SELECT c.*, u.username as user_username
                    FROM comments c 
                    LEFT JOIN users u ON c.user_id = u.id
                    WHERE c.status = 'pending' 
                    ORDER BY c.created_at DESC 
                    LIMIT :limit";

            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error fetching pending comments: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Count comments by status for a specific item
     */
    public function countCommentsByStatus(string $commentableType, int $commentableId): array
    {
        try {
            $sql = "SELECT status, COUNT(*) as count 
                    FROM comments 
                    WHERE commentable_type = :commentable_type AND commentable_id = :commentable_id
                    GROUP BY status";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':commentable_type' => $commentableType,
                ':commentable_id' => $commentableId
            ]);

            $result = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $result[$row['status']] = (int)$row['count'];
            }

            return $result;

        } catch (PDOException $e) {
            error_log("Error counting comments: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Delete comment (soft delete by marking as rejected)
     */
    public function deleteComment(int $commentId): bool
    {
        try {
            $sql = "UPDATE comments SET status = 'rejected' WHERE id = :id";
            $stmt = $this->db->prepare($sql);

            return $stmt->execute([':id' => $commentId]);

        } catch (PDOException $e) {
            error_log("Error deleting comment: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Calculate thread level for nested comments
     */
    private function calculateThreadLevel(?int $parentId): int
    {
        if (!$parentId) {
            return 0;
        }

        try {
            $sql = "SELECT thread_level FROM comments WHERE id = :parent_id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':parent_id' => $parentId]);

            $parentLevel = $stmt->fetchColumn();
            return $parentLevel !== false ? (int)$parentLevel + 1 : 0;

        } catch (PDOException $e) {
            error_log("Error calculating thread level: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Find comment by ID with user information
     */
    public function findByIdWithUser(int $id): ?array
    {
        try {
            $sql = "SELECT c.*, u.username, u.email as user_email
                    FROM comments c 
                    LEFT JOIN users u ON c.user_id = u.id
                    WHERE c.id = :id";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $id]);

            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;

        } catch (PDOException $e) {
            error_log("Error finding comment by ID with user: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Find comment by ID
     */
    public function findById(int $id): ?array
    {
        try {
            $sql = "SELECT * FROM comments WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $id]);

            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;

        } catch (PDOException $e) {
            error_log("Error finding comment by ID: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Static method to get comment by ID
     */
    public static function getById(DatabaseInterface $database_handler, int $id): ?array
    {
        $instance = new self($database_handler);
        return $instance->findById($id);
    }

    /**
     * Static method to delete comment by ID
     */
    public static function deleteById(DatabaseInterface $database_handler, int $id): bool
    {
        $instance = new self($database_handler);
        return $instance->deleteComment($id);
    }

    /**
     * Check if user can comment on the specified item
     * Simplified version that just checks if the commentable type is valid
     */
    public function canComment(string $commentableType, int $commentableId): bool
    {
        // Basic validation - check if commentable type is supported
        $allowedTypes = [self::TYPE_ARTICLE, self::TYPE_PORTFOLIO];

        if (!in_array($commentableType, $allowedTypes, true)) {
            return false;
        }

        // For now, allow comments on any valid ID > 0
        // This can be enhanced later when we know the exact table structure
        return $commentableId > 0;
    }
}
