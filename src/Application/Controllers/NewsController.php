<?php

/**
 * News Controller - Controller for managing news
 * This controller handles requests related to news articles and comments.
 * It fetches data from the database, generates page titles, and handles errors.
 * It also provides methods for generating CSRF tokens for comments.
 *
 * @author Dmytro Hovenko
 */

declare(strict_types=1);

namespace App\Application\Controllers;

use App\Application\Services\NewsService;
use App\Domain\Interfaces\AuthenticationInterface;
use App\Domain\Interfaces\FlashMessageInterface;
use App\Domain\Interfaces\LoggerInterface;
use Exception;
use Random\RandomException;


class NewsController
{
    private NewsService $newsService;
    private AuthenticationInterface $authService;
    private FlashMessageInterface $flashService;
    private LoggerInterface $logger;

    public function __construct(
        NewsService $newsService,
        AuthenticationInterface $authService,
        FlashMessageInterface $flashService,
        LoggerInterface $logger
    ) {
        $this->newsService = $newsService;
        $this->authService = $authService;
        $this->flashService = $flashService;
        $this->logger = $logger;
    }

    /**
     * Main method for handling news requests
     */
    public function handle(): array
    {
        try {
            $articleId = isset($_GET['id']) ? (int)$_GET['id'] : null;

            if ($articleId) {
                return $this->handleSingleArticle($articleId);
            } else {
                return $this->handleArticlesList();
            }
        } catch (Exception $e) {
            $this->logger->error('News controller error: ' . $e->getMessage());
            return [
                'error' => 'An error occurred while loading news content.',
                'page_title' => 'News Hub'
            ];
        }
    }

    /**
     * Handle single article view
     */
    private function handleSingleArticle(int $articleId): array
    {
        try {
            $article = $this->newsService->getArticleById($articleId);

            if (!$article) {
                return [
                    'error' => 'Article not found.',
                    'page_title' => 'Article Not Found'
                ];
            }

            // Get comments
            $isAdmin = $this->authService->isAdmin();
            $comments = $this->newsService->getArticleComments($articleId);

            // Get adjacent articles
            $adjacentArticles = $this->newsService->getAdjacentArticles($article);

            // Generate CSRF token for comments
            $csrfToken = $this->generateCommentCsrfToken();

            // Get username for comments
            $authorName = $this->authService->getCurrentUsername() ?? '';

            return [
                'view_type' => 'single_article',
                'article' => $article,
                'comments' => $comments,
                'adjacent_articles' => $adjacentArticles,
                'csrf_token' => $csrfToken,
                'author_name' => $authorName,
                'page_title' => htmlspecialchars($article->title),
                'is_admin' => $isAdmin
            ];

        } catch (Exception $e) {
            $this->logger->error('Error loading single article: ' . $e->getMessage());
            return [
                'error' => 'Article not found.',
                'page_title' => 'Article Not Found'
            ];
        }
    }

    /**
     * Handle articles list
     */
    private function handleArticlesList(): array
    {
        try {
            // Get filtering parameters
            $filters = $this->getFiltersFromRequest();

            // Get articles
            $articlesData = $this->newsService->getArticles($filters);

            // Get categories for filters
            $categories = $this->newsService->getAllCategories();

            // Determine page title
            $pageTitle = $this->buildPageTitle($filters);

            return [
                'view_type' => 'articles_list',
                'articles' => $articlesData['articles'],
                'pagination' => [
                    'current_page' => $articlesData['pagination']['current_page'],
                    'total_pages' => $articlesData['pagination']['total_pages'],
                    'total_articles' => $articlesData['pagination']['total'],
                    'per_page' => $articlesData['pagination']['per_page'],
                    'has_next' => $articlesData['pagination']['has_next'] ?? false,
                    'has_prev' => $articlesData['pagination']['has_prev'] ?? false
                ],
                'filters' => $filters,
                'categories' => $categories,
                'page_title' => $pageTitle,
                'is_admin' => $this->authService->isAdmin()
            ];

        } catch (Exception $e) {
            $this->logger->error('Error loading articles list: ' . $e->getMessage());
            return [
                'error' => 'Error loading articles.',
                'page_title' => 'News Hub'
            ];
        }
    }

    /**
     * Get filtering parameters from request
     */
    private function getFiltersFromRequest(): array
    {
        return [
            'page' => max(1, (int)($_GET['page_num'] ?? 1)),
            'per_page' => 12,
            'search' => trim($_GET['search'] ?? ''),
            'sort' => $_GET['sort'] ?? 'date_desc',
            'category' => $_GET['category'] ?? null
        ];
    }

    /**
     * Build page title based on filters
     */
    private function buildPageTitle(array $filters): string
    {
        if (!empty($filters['search'])) {
            return 'Search Results for "' . htmlspecialchars($filters['search']) . '"';
        }

        if ($filters['category']) {
            return ucfirst(str_replace('-', ' ', $filters['category'])) . ' News';
        }

        return 'News Hub';
    }

    /**
     * Generate CSRF token for comments
     * @throws RandomException
     */
    private function generateCommentCsrfToken(): string
    {
        // Use the standard key that BaseFormController expects
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }

    /**
     * Get flash messages
     */
    public function getFlashMessages(): array
    {
        return $this->flashService->getMessages();
    }
}
