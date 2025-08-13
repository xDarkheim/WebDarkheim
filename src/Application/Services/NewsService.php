<?php

/**
 * News Service - Business logic for working with news articles
 * This service provides methods for fetching news articles, categories, and comments.
 *
 * @author Dmytro Hovenko
 */

declare(strict_types=1);

namespace App\Application\Services;

use App\Domain\Interfaces\DatabaseInterface;
use App\Domain\Models\Article;
use App\Domain\Models\Category;
use App\Domain\Models\Comments;
use App\Domain\Repositories\ArticleRepository;
use App\Infrastructure\Lib\Database;
use Exception;
use PDO;


class NewsService
{
    private DatabaseInterface $database;
    private $logger;

    public function __construct(DatabaseInterface $database)
    {
        $this->database = $database;
        // Initialize a simple logger for compatibility
        $this->logger = new class {
            public function error($message): void
            {
                error_log("NewsService Error: " . $message);
            }
        };
    }

    /**
     * Get a specific Database object for compatibility with models
     */
    private function getDatabaseHandler(): Database
    {
        /** @var Database $database */
        $database = $this->database;
        return $database;
    }

    /**
     * Get an article by ID
     * @throws Exception
     */
    public function getArticleById(int $articleId): ?Article
    {
        try {
            $articleRepository = new ArticleRepository($this->database);
            $article = $articleRepository->findById($articleId);

            // Check that the article is published (for public access)
            if ($article && $article->status !== 'published') {
                return null; // Return null for unpublished articles
            }

            return $article;
        } catch (Exception $e) {
            throw new Exception("Error loading article: " . $e->getMessage());
        }
    }

    /**
     * Get a list of articles with pagination and filtering
     * @throws Exception
     */
    public function getArticles(array $filters = []): array
    {
        $currentPage = max(1, $filters['page'] ?? 1);
        $articlesPerPage = $filters['per_page'] ?? 12;
        $searchQuery = trim($filters['search'] ?? '');
        $sortBy = $filters['sort'] ?? 'date_desc';
        $categorySlug = $filters['category'] ?? null;
        $offset = ($currentPage - 1) * $articlesPerPage;

        try {
            $connection = $this->database->getConnection();

            // Build WHERE conditions
            $whereConditions = [];
            $params = [];

            // Filter only published articles
            $whereConditions[] = "a.status = ?";
            $params[] = 'published';

            // Filter by category
            if ($categorySlug) {
                $category = Category::findBySlug($this->getDatabaseHandler(), $categorySlug);
                if ($category) {
                    $whereConditions[] = "EXISTS (SELECT 1 FROM article_categories ac WHERE ac.article_id = a.id AND ac.category_id = ?)";
                    $params[] = $category->id;
                } else {
                    return [
                        'articles' => [],
                        'total' => 0,
                        'pages' => 1,
                        'current_page' => 1
                    ];
                }
            }

            // Search by text
            if (!empty($searchQuery)) {
                $whereConditions[] = "(a.title LIKE ? OR a.short_description LIKE ? OR a.full_text LIKE ?)";
                $searchParam = '%' . $searchQuery . '%';
                $params[] = $searchParam;
                $params[] = $searchParam;
                $params[] = $searchParam;
            }

            $whereClause = "WHERE " . implode(" AND ", $whereConditions);

            // Count total articles
            $countSql = "SELECT COUNT(DISTINCT a.id) FROM articles a $whereClause";
            $countStmt = $connection->prepare($countSql);
            $countStmt->execute($params);
            $totalArticles = (int)$countStmt->fetchColumn();
            $totalPages = max(1, ceil($totalArticles / $articlesPerPage));

            // Adjust current page
            if ($currentPage > $totalPages) {
                $currentPage = $totalPages;
                $offset = ($currentPage - 1) * $articlesPerPage;
            }

            // Determine sorting
            $orderByClause = $this->buildOrderClause($sortBy);

            // Load articles
            $articles = [];
            if ($totalArticles > 0) {
                $sql = "SELECT DISTINCT a.* FROM articles a $whereClause $orderByClause LIMIT ? OFFSET ?";
                $params[] = $articlesPerPage;
                $params[] = $offset;

                $stmt = $connection->prepare($sql);
                $stmt->execute($params);
                $articlesData = $stmt->fetchAll(PDO::FETCH_ASSOC);

                foreach ($articlesData as $articleData) {
                    $articles[] = new Article(
                        (int)$articleData['id'],
                        $articleData['title'],
                        $articleData['short_description'],
                        $articleData['full_text'],
                        $articleData['date'],
                        isset($articleData['user_id']) ? (int)$articleData['user_id'] : null,
                        $articleData['status'] ?? 'published',
                        isset($articleData['reviewed_by']) ? (int)$articleData['reviewed_by'] : null,
                        $articleData['reviewed_at'] ?? null,
                        $articleData['review_notes'] ?? null,
                        $articleData['created_at'],
                        $articleData['updated_at']
                    );
                }
            }

            return [
                'articles' => $articles,
                'total' => $totalArticles,
                'pages' => $totalPages,
                'current_page' => $currentPage
            ];

        } catch (Exception $e) {
            throw new Exception("Error loading articles: " . $e->getMessage());
        }
    }

    /**
     * Get comments for an article
     * @throws Exception
     */
    public function getArticleComments(int $articleId): array
    {
        try {
            // Use the correct method from Comments model
            $commentsModel = new \App\Domain\Models\Comments($this->database);
            return $commentsModel->getCommentsByItem('article', $articleId, false);
        } catch (Exception $e) {
            throw new Exception("Error loading comments: " . $e->getMessage());
        }
    }

    /**
     * Get adjacent articles (previous/next)
     */
    public function getAdjacentArticles(Article $article): array
    {
        try {
            $connection = $this->database->getConnection();

            // First, get categories of the current article
            $articleCategories = $this->getArticleCategories($article->id);
            $categoryIds = array_column($articleCategories, 'id');

            if (!empty($categoryIds)) {
                // If the article has categories, look for adjacent articles in the same categories
                $placeholders = str_repeat('?,', count($categoryIds) - 1) . '?';

                // Previous article from the same categories
                // Uses composite sorting: first by date, then by created_at, then by id
                $prevSql = "SELECT DISTINCT a.id, a.title, a.short_description, a.created_at, a.date
                           FROM articles a
                           INNER JOIN article_categories ac ON a.id = ac.article_id
                           WHERE (a.date < ? OR (a.date = ? AND a.created_at < ?) OR (a.date = ? AND a.created_at = ? AND a.id < ?))
                           AND a.status = 'published' AND a.id != ?
                           AND ac.category_id IN ($placeholders)
                           ORDER BY a.date DESC, a.created_at DESC, a.id DESC
                           LIMIT 1";

                $prevParams = array_merge([
                    $article->date, $article->date, $article->created_at,
                    $article->date, $article->created_at, $article->id,
                    $article->id
                ], $categoryIds);
                $prevStmt = $connection->prepare($prevSql);
                $prevStmt->execute($prevParams);
                $prevArticle = $prevStmt->fetch(PDO::FETCH_ASSOC);

                // Next article from the same categories
                $nextSql = "SELECT DISTINCT a.id, a.title, a.short_description, a.created_at, a.date
                           FROM articles a
                           INNER JOIN article_categories ac ON a.id = ac.article_id
                           WHERE (a.date > ? OR (a.date = ? AND a.created_at > ?) OR (a.date = ? AND a.created_at = ? AND a.id > ?))
                           AND a.status = 'published' AND a.id != ?
                           AND ac.category_id IN ($placeholders)
                           ORDER BY a.date ASC, a.created_at ASC, a.id ASC
                           LIMIT 1";

                $nextParams = array_merge([
                    $article->date, $article->date, $article->created_at,
                    $article->date, $article->created_at, $article->id,
                    $article->id
                ], $categoryIds);
                $nextStmt = $connection->prepare($nextSql);
                $nextStmt->execute($nextParams);
                $nextArticle = $nextStmt->fetch(PDO::FETCH_ASSOC);

                // If not found in categories, look for any adjacent articles
                if (!$prevArticle) {
                    $prevSql = "SELECT id, title, short_description, created_at, date 
                               FROM articles 
                               WHERE (date < ? OR (date = ? AND created_at < ?) OR (date = ? AND created_at = ? AND id < ?))
                               AND status = 'published' AND id != ?
                               ORDER BY date DESC, created_at DESC, id DESC
                               LIMIT 1";
                    $prevStmt = $connection->prepare($prevSql);
                    $prevStmt->execute([
                        $article->date, $article->date, $article->created_at,
                        $article->date, $article->created_at, $article->id,
                        $article->id
                    ]);
                    $prevArticle = $prevStmt->fetch(PDO::FETCH_ASSOC);
                }

                if (!$nextArticle) {
                    $nextSql = "SELECT id, title, short_description, created_at, date 
                               FROM articles 
                               WHERE (date > ? OR (date = ? AND created_at > ?) OR (date = ? AND created_at = ? AND id > ?))
                               AND status = 'published' AND id != ?
                               ORDER BY date ASC, created_at ASC, id ASC
                               LIMIT 1";
                    $nextStmt = $connection->prepare($nextSql);
                    $nextStmt->execute([
                        $article->date, $article->date, $article->created_at,
                        $article->date, $article->created_at, $article->id,
                        $article->id
                    ]);
                    $nextArticle = $nextStmt->fetch(PDO::FETCH_ASSOC);
                }
            } else {
                // If the article has no categories, use simple search with composite sorting
                $prevSql = "SELECT id, title, short_description, created_at, date 
                           FROM articles 
                           WHERE (date < ? OR (date = ? AND created_at < ?) OR (date = ? AND created_at = ? AND id < ?))
                           AND status = 'published' AND id != ?
                           ORDER BY date DESC, created_at DESC, id DESC
                           LIMIT 1";
                $prevStmt = $connection->prepare($prevSql);
                $prevStmt->execute([
                    $article->date, $article->date, $article->created_at,
                    $article->date, $article->created_at, $article->id,
                    $article->id
                ]);
                $prevArticle = $prevStmt->fetch(PDO::FETCH_ASSOC);

                $nextSql = "SELECT id, title, short_description, created_at, date 
                           FROM articles 
                           WHERE (date > ? OR (date = ? AND created_at > ?) OR (date = ? AND created_at = ? AND id > ?))
                           AND status = 'published' AND id != ?
                           ORDER BY date ASC, created_at ASC, id ASC
                           LIMIT 1";
                $nextStmt = $connection->prepare($nextSql);
                $nextStmt->execute([
                    $article->date, $article->date, $article->created_at,
                    $article->date, $article->created_at, $article->id,
                    $article->id
                ]);
                $nextArticle = $nextStmt->fetch(PDO::FETCH_ASSOC);
            }

            return [
                'previous' => $prevArticle ?: null,
                'next' => $nextArticle ?: null
            ];
        } catch (Exception $e) {
            $this->logger->error("Error getting adjacent articles: " . $e->getMessage());
            return ['previous' => null, 'next' => null];
        }
    }

    /**
     * Get categories for an article
     */
    public function getArticleCategories(int $articleId): array
    {
        try {
            $connection = $this->database->getConnection();

            $sql = "SELECT c.id, c.name, c.slug
                    FROM categories c
                    INNER JOIN article_categories ac ON c.id = ac.category_id
                    WHERE ac.article_id = ?
                    ORDER BY c.name ASC";

            $stmt = $connection->prepare($sql);
            $stmt->execute([$articleId]);

            $categories = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $categories[] = [
                    'id' => (int)$row['id'],
                    'name' => $row['name'],
                    'slug' => $row['slug']
                ];
            }

            return $categories;
        } catch (Exception $e) {
            $this->logger->error("Error getting article categories: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get all categories with article count
     * @throws Exception
     */
    public function getAllCategories(): array
    {
        try {
            $conn = $this->database->getConnection();

            // Get categories with article count
            $sql = "SELECT c.id, c.name, c.slug, c.created_at, c.updated_at,
                           COUNT(DISTINCT ac.article_id) as article_count
                    FROM categories c
                    LEFT JOIN article_categories ac ON c.id = ac.category_id
                    LEFT JOIN articles a ON ac.article_id = a.id AND a.status = 'published'
                    GROUP BY c.id, c.name, c.slug, c.created_at, c.updated_at
                    ORDER BY c.name ASC";

            $stmt = $conn->prepare($sql);
            $stmt->execute();

            $categories = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $category = (object)[
                    'id' => (int)$row['id'],
                    'name' => $row['name'],
                    'slug' => $row['slug'],
                    'created_at' => $row['created_at'],
                    'updated_at' => $row['updated_at'],
                    'article_count' => (int)$row['article_count']
                ];
                $categories[] = $category;
            }

            return $categories;
        } catch (Exception $e) {
            throw new Exception("Error loading categories: " . $e->getMessage());
        }
    }

    /**
     * Build ORDER BY clause
     */
    private function buildOrderClause(string $sortBy): string
    {
        return match ($sortBy) {
            'date_asc' => "ORDER BY a.date ASC, a.created_at ASC",
            'title_asc' => "ORDER BY a.title ASC",
            'title_desc' => "ORDER BY a.title DESC",
            default => "ORDER BY a.date DESC, a.created_at DESC"
        };
    }
}
