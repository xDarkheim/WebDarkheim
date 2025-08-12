<?php

/**
 * Article persistence class
 * Handles database operations for articles
 *
 * @author Dmytro Hovenko
 */
declare(strict_types=1);

namespace App\Domain\Models;

use App\Domain\Repositories\ArticleRepository;
use PDO;
use PDOException;

class ArticlePersistence
{
    private Article $article;

    public function __construct(Article $article)
    {
        $this->article = $article;
    }

    public function updateStatus(string $status, int $reviewer_id, ArticleRepository $articleRepository, ?string $review_notes = null): bool
    {
        $conn = $articleRepository->database->getConnection();

        try {
            $sql = "UPDATE articles 
                    SET status = :status, 
                        reviewed_by = :reviewer_id, 
                        reviewed_at = NOW(), 
                        review_notes = :review_notes,
                        updated_at = NOW()
                    WHERE id = :id";

            $stmt = $conn->prepare($sql);
            $stmt->bindValue(':id', $this->article->id, PDO::PARAM_INT);
            $stmt->bindValue(':status', $status);
            $stmt->bindValue(':reviewer_id', $reviewer_id, PDO::PARAM_INT);
            $stmt->bindValue(':review_notes', $review_notes);

            $result = $stmt->execute();

            if ($result) {
                $this->article->status = $status;
                $this->article->reviewed_by = $reviewer_id;
                $this->article->reviewed_at = date('Y-m-d H:i:s');
                $this->article->review_notes = $review_notes;
            }

            return $result;
        } catch (PDOException $e) {
            error_log("ArticlePersistence::updateStatus - Database error: " . $e->getMessage());
            return false;
        }
    }

    public function save(ArticleRepository $articleRepository): bool
    {
        $conn = $articleRepository->database->getConnection();

        try {
            if ($this->article->id > 0) {
                // Update existing article
                $sql = "UPDATE articles 
                        SET title = :title, 
                            short_description = :short_description, 
                            full_text = :full_text, 
                            date = :date, 
                            status = :status,
                            updated_at = NOW()
                        WHERE id = :id";

                $stmt = $conn->prepare($sql);
                $stmt->bindValue(':id', $this->article->id, PDO::PARAM_INT);
            } else {
                // Create a new article
                $sql = "INSERT INTO articles (title, short_description, full_text, date, user_id, status, created_at, updated_at) 
                        VALUES (:title, :short_description, :full_text, :date, :user_id, :status, NOW(), NOW())";

                $stmt = $conn->prepare($sql);
                $stmt->bindValue(':user_id', $this->article->user_id, PDO::PARAM_INT);
            }

            $stmt->bindValue(':title', $this->article->title);
            $stmt->bindValue(':short_description', $this->article->short_description);
            $stmt->bindValue(':full_text', $this->article->full_text);
            $stmt->bindValue(':date', $this->article->date);
            $stmt->bindValue(':status', $this->article->status);

            $result = $stmt->execute();

            if ($result && $this->article->id === 0) {
                $this->article->setId((int)$conn->lastInsertId());
            }

            return $result;
        } catch (PDOException $e) {
            error_log("ArticlePersistence::save - Database error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Converts the model to an array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->article->id,
            'title' => $this->article->title,
            'short_description' => $this->article->short_description,
            'full_text' => $this->article->full_text,
            'date' => $this->article->date,
            'user_id' => $this->article->user_id,
            'status' => $this->article->status,
            'reviewed_by' => $this->article->reviewed_by,
            'reviewed_at' => $this->article->reviewed_at,
            'review_notes' => $this->article->review_notes,
            'created_at' => $this->article->created_at,
            'updated_at' => $this->article->updated_at,
            'author_name' => $this->article->author_name,
            'reviewer_name' => $this->article->reviewer_name
        ];
    }
}