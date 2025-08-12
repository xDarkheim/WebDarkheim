<?php

/**
 * Comments model
 *
 * @author Dmytro Hovenko
 */

declare(strict_types=1);

namespace App\Domain\Models;

use App\Domain\Interfaces\DatabaseInterface;
use PDO;
use PDOException;

class Comments
{
    public const string STATUS_PENDING = 'pending';
    public const string STATUS_APPROVED = 'approved';
    public const string STATUS_REJECTED = 'rejected';

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
            // Adapt to the existing comment table structure
            $sql = "INSERT INTO comments (article_id, user_id, author_name, author_email, content, is_approved, created_at) 
                    VALUES (:article_id, :user_id, :author_name, :author_email, :content, 1, NOW())";

            // Get user information for author_name and author_email
            $userStmt = $this->db->prepare("SELECT username, email FROM users WHERE id = :user_id LIMIT 1");
            $userStmt->execute([':user_id' => $data['user_id']]);
            $user = $userStmt->fetch(PDO::FETCH_ASSOC);

            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                ':article_id' => $data['article_id'],
                ':user_id' => $data['user_id'],
                ':author_name' => $user['username'] ?? 'Anonymous',
                ':author_email' => $user['email'] ?? '',
                ':content' => $data['content']
            ]);

            return $result ? (int)$this->db->lastInsertId() : null;
        } catch (PDOException $e) {
            error_log("Error creating comment: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Find comment by ID with user information
     */
    public function findByIdWithUser(int $commentId): ?array
    {
        try {
            $sql = "SELECT c.*, u.username AS author_username 
                    FROM comments c 
                    LEFT JOIN users u ON c.user_id = u.id 
                    WHERE c.id = :id LIMIT 1";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $commentId]);

            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (PDOException $e) {
            error_log("Error finding comment: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Find comment by ID
     */
    public function findById(int $commentId): ?array
    {
        try {
            $sql = "SELECT * FROM comments WHERE id = :id LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $commentId]);

            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (PDOException $e) {
            error_log("Error finding comment: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Find comments by article ID
     * Shows all comments without a status check
     */
    public static function findByArticleId(DatabaseInterface $database_handler, int $article_id): array
    {
        $db = $database_handler->getConnection();

        try {
            $sql = "SELECT c.*, u.username AS author_username 
                    FROM comments c 
                    LEFT JOIN users u ON c.user_id = u.id 
                    WHERE c.article_id = :article_id
                    ORDER BY c.created_at DESC";
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':article_id', $article_id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching comments by article ID $article_id: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get all comments for an article for admin (shows all statuses)
     */
    public static function getAllByArticleIdForAdmin(DatabaseInterface $db_handler, int $article_id): array
    {
        $db = $db_handler->getConnection();

        try {
            $sql = "SELECT c.*, u.username AS author_username
                    FROM comments c
                    LEFT JOIN users u ON c.user_id = u.id
                    WHERE c.article_id = :article_id
                    ORDER BY c.created_at DESC";
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':article_id', $article_id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching all comments for admin for article ID $article_id: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Delete a comment by ID
     */
    public function delete(int $comment_id): bool
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM comments WHERE id = :id");
            $stmt->bindParam(':id', $comment_id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error deleting comment ID $comment_id: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get comment by ID (static version)
     */
    public static function getById(DatabaseInterface $database_handler, int $commentId): ?array
    {
        $db = $database_handler->getConnection();

        try {
            $sql = "SELECT * FROM comments WHERE id = :id LIMIT 1";
            $stmt = $db->prepare($sql);
            $stmt->execute([':id' => $commentId]);

            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (PDOException $e) {
            error_log("Error finding comment: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Delete comment by ID (static version)
     */
    public static function deleteById(DatabaseInterface $database_handler, int $commentId): bool
    {
        $db = $database_handler->getConnection();

        try {
            $stmt = $db->prepare("DELETE FROM comments WHERE id = :id");
            $stmt->bindParam(':id', $commentId, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error deleting comment ID $commentId: " . $e->getMessage());
            return false;
        }
    }
}
