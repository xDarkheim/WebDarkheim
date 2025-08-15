<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

use App\Domain\Interfaces\DatabaseInterface;
use App\Domain\Interfaces\LoggerInterface;
use App\Domain\Models\Article;
use PDO;
use Exception;

/**
 * Article Repository
 * Temporary implementation to fix missing class issues
 * Handles article-related database operations
 */
class ArticleRepository
{
    public function __construct(
        private readonly DatabaseInterface $database,
        private readonly ?LoggerInterface $logger = null
    ) {}

    /**
     * Create new article
     */
    public function create(array $data): ?int
    {
        try {
            $sql = "INSERT INTO articles (title, content, author_id, category_id, status, excerpt, created_at, updated_at) 
                    VALUES (:title, :content, :author_id, :category_id, :status, :excerpt, NOW(), NOW())";

            $stmt = $this->database->getConnection()->prepare($sql);
            $stmt->execute([
                ':title' => $data['title'] ?? '',
                ':content' => $data['content'] ?? '',
                ':author_id' => $data['author_id'] ?? 0,
                ':category_id' => $data['category_id'] ?? null,
                ':status' => $data['status'] ?? 'draft',
                ':excerpt' => $data['excerpt'] ?? ''
            ]);

            $id = $this->database->getConnection()->lastInsertId();

            if ($this->logger) {
                $this->logger->info('Article created', ['article_id' => $id]);
            }

            return (int)$id;
        } catch (Exception $e) {
            if ($this->logger) {
                $this->logger->error('Error creating article', ['error' => $e->getMessage()]);
            }
            return null;
        }
    }

    /**
     * Update existing article
     */
    public function update(int $id, array $data): bool
    {
        try {
            $sql = "UPDATE articles SET 
                    title = :title, 
                    content = :content, 
                    category_id = :category_id, 
                    status = :status, 
                    excerpt = :excerpt,
                    updated_at = NOW()
                    WHERE id = :id";

            $stmt = $this->database->getConnection()->prepare($sql);
            $result = $stmt->execute([
                ':id' => $id,
                ':title' => $data['title'] ?? '',
                ':content' => $data['content'] ?? '',
                ':category_id' => $data['category_id'] ?? null,
                ':status' => $data['status'] ?? 'draft',
                ':excerpt' => $data['excerpt'] ?? ''
            ]);

            if ($this->logger && $result) {
                $this->logger->info('Article updated', ['article_id' => $id]);
            }

            return $result;
        } catch (Exception $e) {
            if ($this->logger) {
                $this->logger->error('Error updating article', ['error' => $e->getMessage(), 'id' => $id]);
            }
            return false;
        }
    }

    /**
     * Find article by ID
     */
    public function findById(int $id): ?array
    {
        try {
            $sql = "SELECT * FROM articles WHERE id = :id LIMIT 1";
            $stmt = $this->database->getConnection()->prepare($sql);
            $stmt->execute([':id' => $id]);

            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (Exception $e) {
            if ($this->logger) {
                $this->logger->error('Error finding article by ID', ['error' => $e->getMessage(), 'id' => $id]);
            }
            return null;
        }
    }

    /**
     * Get all articles with optional status filter
     */
    public function findAll(?string $status = null): array
    {
        try {
            $sql = "SELECT * FROM articles";
            $params = [];

            if ($status !== null) {
                $sql .= " WHERE status = :status";
                $params[':status'] = $status;
            }

            $sql .= " ORDER BY created_at DESC";

            $stmt = $this->database->getConnection()->prepare($sql);
            $stmt->execute($params);

            $articles = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Convert to Article objects
            return array_map(function($article) {
                return Article::fromArray($article);
            }, $articles);

        } catch (Exception $e) {
            if ($this->logger) {
                $this->logger->error('Error finding all articles', [
                    'status' => $status,
                    'error' => $e->getMessage()
                ]);
            }
            return [];
        }
    }

    /**
     * Find articles by user ID with optional status filter
     */
    public function findByUserId(int $userId, ?string $status = null): array
    {
        try {
            $sql = "SELECT * FROM articles WHERE author_id = :user_id";
            $params = [':user_id' => $userId];

            if ($status !== null) {
                $sql .= " AND status = :status";
                $params[':status'] = $status;
            }

            $sql .= " ORDER BY created_at DESC";

            $stmt = $this->database->getConnection()->prepare($sql);
            $stmt->execute($params);

            $articles = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Convert to Article objects
            return array_map(function($article) {
                return Article::fromArray($article);
            }, $articles);

        } catch (Exception $e) {
            if ($this->logger) {
                $this->logger->error('Error finding articles by user ID', [
                    'user_id' => $userId,
                    'status' => $status,
                    'error' => $e->getMessage()
                ]);
            }
            return [];
        }
    }

    /**
     * Get categories for an article
     */
    public function getCategories(Article $article): array
    {
        try {
            $sql = "SELECT c.* FROM categories c 
                    INNER JOIN article_categories ac ON c.id = ac.category_id 
                    WHERE ac.article_id = :article_id";

            $stmt = $this->database->getConnection()->prepare($sql);
            $stmt->execute([':article_id' => $article->id]);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            if ($this->logger) {
                $this->logger->error('Error getting article categories', [
                    'article_id' => $article->id,
                    'error' => $e->getMessage()
                ]);
            }
            return [];
        }
    }

    /**
     * Update article status
     */
    public function updateStatus(int $id, string $status, ?int $reviewerId = null, ?string $reviewNotes = null): bool
    {
        try {
            $sql = "UPDATE articles SET status = :status, updated_at = NOW()";
            $params = [':id' => $id, ':status' => $status];

            if ($reviewerId !== null) {
                $sql .= ", reviewer_id = :reviewer_id";
                $params[':reviewer_id'] = $reviewerId;
            }

            if ($reviewNotes !== null) {
                $sql .= ", review_notes = :review_notes";
                $params[':review_notes'] = $reviewNotes;
            }

            $sql .= " WHERE id = :id";

            $stmt = $this->database->getConnection()->prepare($sql);
            $result = $stmt->execute($params);

            if ($this->logger && $result) {
                $this->logger->info('Article status updated', [
                    'article_id' => $id,
                    'status' => $status
                ]);
            }

            return $result;
        } catch (Exception $e) {
            if ($this->logger) {
                $this->logger->error('Error updating article status', [
                    'error' => $e->getMessage(),
                    'id' => $id,
                    'status' => $status
                ]);
            }
            return false;
        }
    }

    /**
     * Delete article
     */
    public function delete(int $id): bool
    {
        try {
            $sql = "DELETE FROM articles WHERE id = :id";
            $stmt = $this->database->getConnection()->prepare($sql);
            $result = $stmt->execute([':id' => $id]);

            if ($this->logger && $result) {
                $this->logger->info('Article deleted', ['article_id' => $id]);
            }

            return $result;
        } catch (Exception $e) {
            if ($this->logger) {
                $this->logger->error('Error deleting article', ['error' => $e->getMessage(), 'id' => $id]);
            }
            return false;
        }
    }

    /**
     * Get all articles with pagination
     */
    public function getAllArticles(int $limit = 20, int $offset = 0): array
    {
        try {
            $sql = "SELECT * FROM articles ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
            $stmt = $this->database->getConnection()->prepare($sql);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            if ($this->logger) {
                $this->logger->error('Error getting all articles', ['error' => $e->getMessage()]);
            }
            return [];
        }
    }

    /**
     * Count total articles
     */
    public function count(): int
    {
        try {
            $sql = "SELECT COUNT(*) FROM articles";
            $stmt = $this->database->getConnection()->prepare($sql);
            $stmt->execute();

            return (int)$stmt->fetchColumn();
        } catch (Exception $e) {
            if ($this->logger) {
                $this->logger->error('Error counting articles', ['error' => $e->getMessage()]);
            }
            return 0;
        }
    }
}
