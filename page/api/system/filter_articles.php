<?php

/**
 * Filter Articles API
 *
 * This API allows admins to filter articles based on various criteria.
 * It supports sorting, filtering, and pagination.
 * It returns a JSON response containing the filtered articles and pagination information.
 *
 * @author Dmytro Hovenko
 */

// FIXED: Improved error handling and logging
use App\Application\Controllers\NewsController;
use App\Application\Core\ServiceProvider;

error_reporting(E_ALL);
ini_set('display_errors', 0); // Do not show errors in browser for JSON API

// Function for safe exit with JSON response
function apiResponse($success, $data = null, $error = null, $httpCode = 200): void
{
    http_response_code($httpCode);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-cache, must-revalidate');

    $response = ['success' => $success];
    if ($data !== null) {
        $response = array_merge($response, $data);
    }
    if ($error !== null) {
        $response['error'] = $error;
    }

    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

// Check AJAX request
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] !== 'XMLHttpRequest') {
    apiResponse(false, null, 'Only AJAX requests allowed', 400);
}

try {
    // FIXED: Use existing architecture instead of standalone code
    require_once __DIR__ . '/../../includes/bootstrap.php';

    // Get global services from DI container (as in news.php)
    global $container;

    $serviceProvider = ServiceProvider::getInstance($container);
    $newsService = $serviceProvider->getNewsService();
    $authService = $serviceProvider->getAuth();
    $flashService = $serviceProvider->getFlashMessage();
    $logger = $serviceProvider->getLogger();

    // Create a news controller (same as in the main system)
    $newsController = new NewsController(
        $newsService,
        $authService,
        $flashService,
        $logger
    );

    // FIXED: Emulate $_GET parameters for controller
    $originalGet = $_GET;

    // Set parameters for NewsController in the same format
    $_GET = [
        'page' => 'news',
        'category' => $_GET['category'] ?? '',
        'search' => $_GET['search'] ?? '',
        'sort' => $_GET['sort'] ?? 'date_desc',
        'page_num' => (int)($_GET['page_num'] ?? 1)
    ];

    // Log request for debugging
    $logger->info('API Request:', $_GET);

    // Get data via existing controller
    $data = $newsController->handle();

    // Restore original $_GET
    $_GET = $originalGet;

    // FIXED: Check that data is received correctly

    // FIXED: Use the same logic as in list.php for handling no articles
    ob_start();

    if (!empty($data['articles'])) {
        // Include a real _articles_grid.php component with controller data
        include __DIR__ . '/../../resources/views/news/_articles_grid.php';
        $fullArticlesHtml = ob_get_clean();

        // Extract only .articles-grid content without a section and headers
        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        $dom->loadHTML('<?xml encoding="utf-8" ?>' . $fullArticlesHtml, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

        $xpath = new DOMXPath($dom);
        $articlesGridNode = $xpath->query('//div[@class="articles-grid"]')->item(0);

        if ($articlesGridNode) {
            // Get only articles-grid content (articles inside)
            $articlesHtml = '';
            foreach ($articlesGridNode->childNodes as $child) {
                $articlesHtml .= $dom->saveHTML($child);
            }
        } else {
            // Fallback: if parsing failed, take all HTML
            $articlesHtml = $fullArticlesHtml;
        }
    } else {
        // FIXED: Use existing _no_articles.php component
        include __DIR__ . '/../../resources/views/news/_no_articles.php';
        $noArticlesHtml = ob_get_clean();

        // If _no_articles.php is empty or does not exist, use fallback
        if (empty(trim($noArticlesHtml))) {
            $articlesHtml = "
                <div class=\"no-articles-message\">
                    <div class=\"no-articles-icon\">
                        <i class=\"fas fa-newspaper\"></i>
                    </div>
                    <h3>No articles found</h3>
                    <p>Try adjusting your search criteria or browsing all categories.</p>
                    <a href=\"/index.php?page=news\" class=\"btn btn-primary\">View All Articles</a>
                </div>";
        } else {
            $articlesHtml = $noArticlesHtml;
        }
    }

    // Generate pagination using existing component
    ob_start();

    // Include real pagination component
    if (isset($data['pagination']) && $data['pagination']['total_pages'] > 1) {
        include __DIR__ . '/../../resources/views/news/_pagination.php';
    }

    $paginationHtml = ob_get_clean();

    // Form response in the same format
    $responseData = [
        'articles_html' => $articlesHtml,
        'pagination_html' => $paginationHtml,
        'summary' => [
            'total_results' => $data['pagination']['total_articles'] ?? 0,
            'current_page' => $data['pagination']['current_page'] ?? 1,
            'total_pages' => $data['pagination']['total_pages'] ?? 1,
            'showing_from' => (($data['pagination']['current_page'] ?? 1) - 1) * ($data['pagination']['per_page'] ?? 12) + 1,
            'showing_to' => min(
                ($data['pagination']['current_page'] ?? 1) * ($data['pagination']['per_page'] ?? 12),
                $data['pagination']['total_articles'] ?? 0
            ),
            'current_filter' => !empty($_GET['category']) ? ucfirst($_GET['category']) : 'All Categories'
        ],
        'filters' => $data['filters'] ?? [],
        'timestamp' => time()
    ];

    // Log successful response
    $logger->info('API Success:', [
        'total_results' => $responseData['summary']['total_results'],
        'current_page' => $responseData['summary']['current_page']
    ]);

    apiResponse(true, $responseData);

} catch (Exception $e) {
    // Use logger from a system if available
    if (isset($logger)) {
        $logger->error("API Error: " . $e->getMessage(), [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]);
    }

    // FIXED: More informative error messages
    $errorMessage = 'Server error occurred';

    // Show details in development mode
    if (defined('APP_DEBUG') && APP_DEBUG) {
        $errorMessage .= ': ' . $e->getMessage();
    }

    apiResponse(false, null, $errorMessage, 500);
}
