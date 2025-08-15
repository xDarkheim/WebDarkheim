<?php

/**
 * Article model - represents an article in the system
 * This model handles the data and operations related to articles in the system.
 * It includes methods for retrieving, saving, updating, and deleting articles.
 *
 * @author Dmytro Hovenko
 */

declare(strict_types=1);

namespace App\Domain\Models;

use App\Domain\Repositories\ArticleRepository;
use PDO;
use PDOException;


class Article
{
    public function __construct(
        public int $id,
        public readonly string $title,
        public readonly string $short_description,
        public readonly string $full_text,
        public readonly string $date,
        public readonly ?int $user_id,
        public string $status = 'draft',
        public ?int $reviewed_by = null,
        public ?string $reviewed_at = null,
        public ?string $review_notes = null,
        public readonly ?string $created_at = null,
        public ?string $updated_at = null,
        public readonly ?string $author_name = null,
        public readonly ?string $reviewer_name = null
    ) {}

    /**
     * Checks the status of the article
     */
    public function hasStatus(string $status): bool
    {
        return $this->status === $status;
    }

    /**
     * Checks if the article is published
     */
    public function isPublished(): bool
    {
        return $this->hasStatus('published');
    }

    /**
     * Checks if the article is pending review
     */
    public function isPendingReview(): bool
    {
        return $this->hasStatus('pending_review');
    }

    /**
     * Checks if the article is a draft
     */
    public function isDraft(): bool
    {
        return $this->hasStatus('draft');
    }

    /**
     * Checks if the article is rejected
     */
    public function isRejected(): bool
    {
        return $this->hasStatus('rejected');
    }

    /**
     * Checks if the article has been reviewed
     */
    public function isReviewed(): bool
    {
        return $this->reviewed_by !== null && $this->reviewed_at !== null;
    }

    /**
     * Gets the title truncated to the specified length
     */
    public function getTruncatedTitle(int $maxLength = 60): string
    {
        return $this->truncateText($this->title, $maxLength);
    }

    /**
     * Gets the short description truncated to the specified length
     */
    public function getTruncatedDescription(int $maxLength = 150): string
    {
        return $this->truncateText($this->short_description, $maxLength);
    }

    /**
     * Create Article instance from array data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (int)($data['id'] ?? 0),
            title: $data['title'] ?? '',
            short_description: $data['excerpt'] ?? $data['short_description'] ?? '',
            full_text: $data['content'] ?? $data['full_text'] ?? '',
            date: $data['created_at'] ?? $data['date'] ?? date('Y-m-d H:i:s'),
            user_id: isset($data['author_id']) ? (int)$data['author_id'] : (isset($data['user_id']) ? (int)$data['user_id'] : null),
            status: $data['status'] ?? 'draft',
            reviewed_by: isset($data['reviewer_id']) ? (int)$data['reviewer_id'] : (isset($data['reviewed_by']) ? (int)$data['reviewed_by'] : null),
            reviewed_at: $data['reviewed_at'] ?? null,
            review_notes: $data['review_notes'] ?? null,
            created_at: $data['created_at'] ?? null,
            updated_at: $data['updated_at'] ?? null,
            author_name: $data['author_name'] ?? null,
            reviewer_name: $data['reviewer_name'] ?? null
        );
    }

    /**
     * Convert Article to array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'short_description' => $this->short_description,
            'excerpt' => $this->short_description,
            'full_text' => $this->full_text,
            'content' => $this->full_text,
            'date' => $this->date,
            'user_id' => $this->user_id,
            'author_id' => $this->user_id,
            'status' => $this->status,
            'reviewed_by' => $this->reviewed_by,
            'reviewer_id' => $this->reviewed_by,
            'reviewed_at' => $this->reviewed_at,
            'review_notes' => $this->review_notes,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'author_name' => $this->author_name,
            'reviewer_name' => $this->reviewer_name
        ];
    }

    /**
     * Truncates text to the specified length
     */
    private function truncateText(string $text, int $maxLength): string
    {
        if (empty($text) || strlen($text) <= $maxLength) {
            return $text;
        }

        return substr($text, 0, $maxLength) . '...';
    }

    /**
     * Sets the article ID (used after saving to the DB)
     */
    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function save(ArticleRepository $articleRepository): bool
    {
        $conn = $articleRepository->database->getConnection();

        try {
            if ($this->id > 0) {
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
                $stmt->bindValue(':id', $this->id, PDO::PARAM_INT);
            } else {
                // Create a new article
                $sql = "INSERT INTO articles (title, short_description, full_text, date, user_id, status, created_at, updated_at) 
                        VALUES (:title, :short_description, :full_text, :date, :user_id, :status, NOW(), NOW())";

                $stmt = $conn->prepare($sql);
                $stmt->bindValue(':user_id', $this->user_id, PDO::PARAM_INT);
            }

            $stmt->bindValue(':title', $this->title);
            $stmt->bindValue(':short_description', $this->short_description);
            $stmt->bindValue(':full_text', $this->full_text);
            $stmt->bindValue(':date', $this->date);
            $stmt->bindValue(':status', $this->status);

            $result = $stmt->execute();

            if ($result && $this->id === 0) {
                $this->setId((int)$conn->lastInsertId());
            }

            return $result;
        } catch (PDOException $e) {
            error_log("ArticleRepository::save - Database error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Updates the status of the article
     */
    public function updateStatus(string $status, \App\Domain\Repositories\ArticleRepository $current_user_id, ArticleRepository $articleRepository, ?string $review_notes = null): bool
    {
        $conn = $articleRepository->database->getConnection();

        try {
            $sql = "UPDATE articles SET 
                        status = :status, 
                        reviewed_by = :reviewed_by,
                        review_notes = :review_notes,
                        reviewed_at = CURRENT_TIMESTAMP,
                        updated_at = CURRENT_TIMESTAMP 
                    WHERE id = :id";

            $stmt = $conn->prepare($sql);
            $stmt->bindValue(':status', $status);
            $stmt->bindValue(':reviewed_by', $current_user_id, PDO::PARAM_INT);
            $stmt->bindValue(':review_notes', $review_notes);
            $stmt->bindValue(':id', $this->id, PDO::PARAM_INT);

            if ($stmt->execute()) {
                // Update the article object with a new status
                $this->status = $status;
                $this->reviewed_by = $current_user_id;
                $this->review_notes = $review_notes;
                $this->reviewed_at = date('Y-m-d H:i:s');
                $this->updated_at = date('Y-m-d H:i:s');

                return true;
            }

            return false;
        } catch (PDOException $e) {
            error_log("ArticleRepository::updateStatus - Database error: " . $e->getMessage());
            return false;
        }
    }

}
