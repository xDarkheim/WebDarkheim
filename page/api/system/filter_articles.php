<?php
/**
 * Articles Filter API Endpoint
 * Handles AJAX requests for filtering news articles by category, search, and sorting
 *
 * @author Dmytro Hovenko
 * @version 1.0.0
 */

declare(strict_types=1);

// Set content type for JSON response
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

// Include bootstrap
require_once __DIR__ . '/../../../includes/bootstrap.php';

try {
    // Get global services from DI container
    global $container;

    // Get ServiceProvider for accessing services
    $serviceProvider = \App\Application\Core\ServiceProvider::getInstance($container);
    $newsService = $serviceProvider->getNewsService();
    $logger = $serviceProvider->getLogger();

    // Only allow GET requests
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        exit;
    }

    // Get filtering parameters from request
    $filters = [
        'page' => max(1, (int)($_GET['page_num'] ?? $_GET['page'] ?? 1)),
        'per_page' => 12,
        'search' => trim($_GET['search'] ?? ''),
        'sort' => $_GET['sort'] ?? 'date_desc',
        'category' => $_GET['category'] ?? null
    ];

    // Log the request for debugging
    $logger->info('Filter articles API called', [
        'filters' => $filters,
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ]);

    // Get articles data
    $articlesData = $newsService->getArticles($filters);

    // Get categories for filters
    $categories = $newsService->getAllCategories();

    // Prepare response data
    $responseData = [
        'success' => true,
        'data' => [
            'articles' => [],
            'pagination' => [
                'current_page' => $articlesData['current_page'],
                'total_pages' => $articlesData['pages'],
                'total_articles' => $articlesData['total'],
                'per_page' => $filters['per_page']
            ],
            'filters' => $filters,
            'categories' => []
        ]
    ];

    // Convert articles to array format for JSON with all necessary fields
    foreach ($articlesData['articles'] as $article) {
        // Get categories for this article
        $articleCategories = $newsService->getArticleCategories($article->id);
        $categoryName = !empty($articleCategories) ? $articleCategories[0]['name'] : '';

        // Format the short_description using TextEditorComponent
        $textEditorComponent = $serviceProvider->getTextEditorComponent();
        $formattedDescription = '';

        if (!empty($article->short_description)) {
            $formattedPreview = $textEditorComponent->formatContent($article->short_description);

            // Truncate if too long (same logic as in the view)
            $plainText = strip_tags($formattedPreview);
            if (strlen($plainText) > 150) {
                $words = explode(' ', $plainText);
                $truncatedWords = array_slice($words, 0, 25);
                $formattedDescription = implode(' ', $truncatedWords) . '...';
            } else {
                $formattedDescription = $formattedPreview;
            }
        }

        $responseData['data']['articles'][] = [
            'id' => $article->id,
            'title' => $article->title,
            'short_description' => $article->short_description,
            'formatted_description' => $formattedDescription, // Добавляем отформатированное описание
            'full_text' => $article->full_text,
            'date' => $article->date,
            'created_at' => $article->created_at,
            'status' => $article->status,
            'category_name' => $categoryName,
            'image_path' => $article->image_path ?? null,
            'url' => '/index.php?page=news&id=' . $article->id
        ];
    }

    // Convert categories to array format for JSON
    if ($categories) {
        foreach ($categories as $category) {
            $responseData['data']['categories'][] = [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'article_count' => $category->article_count ?? 0
            ];
        }
    }

    // Generate HTML content for AJAX replacement
    ob_start();
    $data = $responseData['data'];
    $data['view_type'] = 'articles_list';

    // Convert article arrays back to objects for compatibility with the view
    $articleObjects = [];
    foreach ($data['articles'] as $articleData) {
        $articleObject = (object) $articleData;
        $articleObjects[] = $articleObject;
    }
    $data['articles'] = $articleObjects;

    include __DIR__ . '/../../../resources/views/news/_articles_grid.php';
    $articlesHtml = ob_get_clean();

    // Generate pagination HTML
    ob_start();
    include __DIR__ . '/../../../resources/views/news/_pagination.php';
    $paginationHtml = ob_get_clean();

    // Add HTML to response in the structure JavaScript expects
    $responseData['html'] = [
        'articles_grid' => $articlesHtml,
        'pagination' => $paginationHtml
    ];

    // Also add data structure that JavaScript expects
    $responseData['data']['html'] = [
        'articles_grid' => $articlesHtml,
        'pagination' => $paginationHtml
    ];

    // Return JSON response
    echo json_encode($responseData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    // Log error
    if (isset($logger)) {
        $logger->error('Articles filter API error: ' . $e->getMessage(), [
            'trace' => $e->getTraceAsString(),
            'filters' => $filters ?? []
        ]);
    }

    // Return error response
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error occurred while filtering articles'
    ], JSON_UNESCAPED_UNICODE);
}
