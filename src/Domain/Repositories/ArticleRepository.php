<?php

/**
 * Article repository
 *
 * @author Dmytro Hovenko
 */

declare(strict_types=1);

namespace App\Domain\Repositories;

use App\Domain\Interfaces\DatabaseInterface;
use App\Domain\Models\Article;
use PDO;
use PDOException;

class ArticleRepository
{
    public DatabaseInterface $database;

    public function __construct(DatabaseInterface $database)
    {
        $this->database = $database;
    }

    public function findById(int $id): ?Article
    {
        $conn = $this->database->getConnection();

        try {
            $sql = "SELECT a.*, u.username AS author_name, r.username AS reviewer_name
                    FROM articles a 
                    LEFT JOIN users u ON a.user_id = u.id 
                    LEFT JOIN users r ON a.reviewed_by = r.id
                    WHERE a.id = :id";
            $stmt = $conn->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result) {
                return new Article(
                    (int)$result['id'],
                    $result['title'],
                    $result['short_description'],
                    $result['full_text'],
                    $result['date'],
                    $result['user_id'] ? (int)$result['user_id'] : null,
                    $result['status'],
                    $result['reviewed_by'] ? (int)$result['reviewed_by'] : null,
                    $result['reviewed_at'],
                    $result['review_notes'],
                    $result['created_at'],
                    $result['updated_at'],
                    $result['author_name'],
                    $result['reviewer_name']
                );
            }
        } catch (PDOException $e) {
            error_log("ArticleRepository::findById - Database error: " . $e->getMessage());
        }

        return null;
    }

    public function findAll(?string $status = null): array
    {
        $conn = $this->database->getConnection();

        try {
            $sql = "SELECT a.*, u.username AS author_name, r.username AS reviewer_name
                    FROM articles a 
                    LEFT JOIN users u ON a.user_id = u.id 
                    LEFT JOIN users r ON a.reviewed_by = r.id";

            if ($status) {
                $sql .= " WHERE a.status = :status";
            }

            $sql .= " ORDER BY a.created_at DESC";

            $stmt = $conn->prepare($sql);

            if ($status) {
                $stmt->bindValue(':status', $status);
            }

            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $articles = [];
            foreach ($results as $result) {
                $articles[] = new Article(
                    (int)$result['id'],
                    $result['title'],
                    $result['short_description'],
                    $result['full_text'],
                    $result['date'],
                    $result['user_id'] ? (int)$result['user_id'] : null,
                    $result['status'],
                    $result['reviewed_by'] ? (int)$result['reviewed_by'] : null,
                    $result['reviewed_at'],
                    $result['review_notes'],
                    $result['created_at'],
                    $result['updated_at'],
                    $result['author_name'],
                    $result['reviewer_name']
                );
            }

            return $articles;
        } catch (PDOException $e) {
            error_log("ArticleRepository::findAll - Database error: " . $e->getMessage());
            return [];
        }
    }

    public function findByUserId(int $user_id, ?string $status = null): array
    {
        $conn = $this->database->getConnection();

        try {
            $sql = "SELECT a.*, u.username AS author_name, r.username AS reviewer_name
                    FROM articles a 
                    LEFT JOIN users u ON a.user_id = u.id 
                    LEFT JOIN users r ON a.reviewed_by = r.id
                    WHERE a.user_id = :user_id";

            if ($status) {
                $sql .= " AND a.status = :status";
            }

            $sql .= " ORDER BY a.created_at DESC";

            $stmt = $conn->prepare($sql);
            $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);

            if ($status) {
                $stmt->bindValue(':status', $status);
            }

            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $articles = [];
            foreach ($results as $result) {
                $articles[] = new Article(
                    (int)$result['id'],
                    $result['title'],
                    $result['short_description'],
                    $result['full_text'],
                    $result['date'],
                    $result['user_id'] ? (int)$result['user_id'] : null,
                    $result['status'],
                    $result['reviewed_by'] ? (int)$result['reviewed_by'] : null,
                    $result['reviewed_at'],
                    $result['review_notes'],
                    $result['created_at'],
                    $result['updated_at'],
                    $result['author_name'],
                    $result['reviewer_name']
                );
            }

            return $articles;
        } catch (PDOException $e) {
            error_log("ArticleRepository::findByUserId - Database error: " . $e->getMessage());
            return [];
        }
    }

    public function delete(Article $article): bool
    {
        $conn = $this->database->getConnection();

        try {
            // First delete article categories
            $sql = "DELETE FROM article_categories WHERE article_id = :id";
            $stmt = $conn->prepare($sql);
            $stmt->bindValue(':id', $article->id, PDO::PARAM_INT);
            $stmt->execute();

            // Then delete the article
            $sql = "DELETE FROM articles WHERE id = :id";
            $stmt = $conn->prepare($sql);
            $stmt->bindValue(':id', $article->id, PDO::PARAM_INT);

            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("ArticleRepository::delete - Database error: " . $e->getMessage());
            return false;
        }
    }

    public function getCategories(Article $article): array
    {
        $conn = $this->database->getConnection();

        try {
            $sql = "SELECT c.* FROM categories c 
                    INNER JOIN article_categories ac ON c.id = ac.category_id 
                    WHERE ac.article_id = :article_id";

            $stmt = $conn->prepare($sql);
            $stmt->bindValue(':article_id', $article->id, PDO::PARAM_INT);
            $stmt->execute();

            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $categories = [];

            foreach ($results as $result) {
                $categories[] = (object)[
                    'id' => (int)$result['id'],
                    'name' => $result['name'],
                    'description' => $result['description']
                ];
            }

            return $categories;
        } catch (PDOException $e) {
            error_log("ArticleRepository::getCategories - Database error: " . $e->getMessage());
            return [];
        }
    }

    public function setCategories(Article $article, array $category_ids): bool
    {
        $conn = $this->database->getConnection();

        try {
            // Start transaction
            $conn->beginTransaction();

            // First, delete existing categories for this article
            $sql = "DELETE FROM article_categories WHERE article_id = :article_id";
            $stmt = $conn->prepare($sql);
            $stmt->bindValue(':article_id', $article->id, PDO::PARAM_INT);
            $stmt->execute();

            // Then add new categories
            if (!empty($category_ids)) {
                $sql = "INSERT INTO article_categories (article_id, category_id) VALUES (:article_id, :category_id)";
                $stmt = $conn->prepare($sql);

                foreach ($category_ids as $category_id) {
                    $stmt->bindValue(':article_id', $article->id, PDO::PARAM_INT);
                    $stmt->bindValue(':category_id', (int)$category_id, PDO::PARAM_INT);
                    $stmt->execute();
                }
            }

            // Commit transaction
            $conn->commit();
            return true;
        } catch (PDOException $e) {
            // Rollback transaction on error
            $conn->rollBack();
            error_log("ArticleRepository::setCategories - Database error: " . $e->getMessage());
            return false;
        }
    }

}
