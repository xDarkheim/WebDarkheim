<?php

/**
 * Category model
 *
 * @author Dmytro Hovenko
 */

declare(strict_types=1);

namespace App\Domain\Models;

use App\Infrastructure\Lib\Database;
use PDO;
use PDOException;

class Category {
    public function __construct(
        public int $id,
        public string $name,
        public ?string $slug = null,
        public ?string $created_at = null,
        public ?string $updated_at = null
    ) {}

    public static function findById(Database $db_handler, int $id): ?Category {
        $conn = $db_handler->getConnection();

        try {
            $stmt = $conn->prepare("SELECT id, name, slug, created_at, updated_at FROM categories WHERE id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            $categoryData = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($categoryData) {
                return new self(
                    (int)$categoryData['id'],
                    $categoryData['name'],
                    $categoryData['slug'],
                    $categoryData['created_at'],
                    $categoryData['updated_at']
                );
            }
        } catch (PDOException $e) {
            error_log("Category::findById - PDOException: " . $e->getMessage());
        }
        return null;
    }

    public static function findBySlug(Database $db_handler, string $slug): ?Category {
        $conn = $db_handler->getConnection();

        try {
            $stmt = $conn->prepare("SELECT id, name, slug, created_at, updated_at FROM categories WHERE slug = :slug");
            $stmt->bindParam(':slug', $slug);
            $stmt->execute();
            
            $categoryData = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($categoryData) {
                return new self(
                    (int)$categoryData['id'],
                    $categoryData['name'],
                    $categoryData['slug'],
                    $categoryData['created_at'],
                    $categoryData['updated_at']
                );
            }
        } catch (PDOException $e) {
            error_log("Category::findBySlug - PDOException: " . $e->getMessage());
        }
        return null;
    }

    public static function findAll(Database $db_handler): array { 
        $conn = $db_handler->getConnection();

        $categories = [];
        try {
            // It's good practice to order results, e.g., by name
            $stmt = $conn->query("SELECT id, name, slug, created_at, updated_at FROM categories ORDER BY name ASC");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $categories[] = new self(
                    (int)$row['id'],
                    $row['name'],
                    $row['slug'],
                    $row['created_at'],
                    $row['updated_at']
                );
            }
        } catch (PDOException $e) {
            error_log("Category::findAll - PDOException: " . $e->getMessage());
        }
        return $categories;
    }

    /**
     * Find categories by article ID
     */
    public static function findByArticleId(Database $db_handler, int $article_id): array {
        $conn = $db_handler->getConnection();

        try {
            $stmt = $conn->prepare("
                SELECT c.id, c.name, c.slug, c.created_at, c.updated_at 
                FROM categories c 
                INNER JOIN articles a ON c.id = a.category_id 
                WHERE a.id = :article_id
            ");
            $stmt->bindParam(':article_id', $article_id, PDO::PARAM_INT);
            $stmt->execute();

            $categories = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $categories[] = new self(
                    (int)$row['id'],
                    $row['name'],
                    $row['slug'],
                    $row['created_at'],
                    $row['updated_at']
                );
            }

            return $categories;
        } catch (PDOException $e) {
            error_log("Category::findByArticleId - PDOException: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Check if a category exists by name or slug, excluding specific ID
     */
    public static function existsByNameOrSlugExcludingId(Database $db_handler, string $name, string $slug, int $excludeId): bool {
        $conn = $db_handler->getConnection();

        try {
            $stmt = $conn->prepare("SELECT id FROM categories WHERE (name = :name OR slug = :slug) AND id != :id LIMIT 1");
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':slug', $slug);
            $stmt->bindParam(':id', $excludeId, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetch() !== false;
        } catch (PDOException $e) {
            error_log("Category::existsByNameOrSlugExcludingId - PDOException: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update category by ID
     */
    public static function updateById(Database $db_handler, int $id, string $name, string $slug): bool {
        $conn = $db_handler->getConnection();

        try {
            $stmt = $conn->prepare("UPDATE categories SET name = :name, slug = :slug, updated_at = NOW() WHERE id = :id");
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':slug', $slug);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);

            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Category::updateById - PDOException: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Create a new category
     */
    public static function create(Database $db_handler, string $name, string $slug): ?Category {
        $conn = $db_handler->getConnection();

        try {
            $stmt = $conn->prepare("INSERT INTO categories (name, slug, created_at, updated_at) VALUES (:name, :slug, NOW(), NOW())");
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':slug', $slug);

            if ($stmt->execute()) {
                $newId = (int)$conn->lastInsertId();
                return self::findById($db_handler, $newId);
            }
        } catch (PDOException $e) {
            error_log("Category::create - PDOException: " . $e->getMessage());
        }
        return null;
    }

    /**
     * Delete category by ID
     */
    public static function deleteById(Database $db_handler, int $id): bool {
        $conn = $db_handler->getConnection();

        try {
            $stmt = $conn->prepare("DELETE FROM categories WHERE id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);

            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Category::deleteById - PDOException: " . $e->getMessage());
            return false;
        }
    }
}
