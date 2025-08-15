<?php
/**
 * API Endpoint for Creating Articles
 * Handles article creation with categories and validation
 */

declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/includes/bootstrap.php';

global $serviceProvider, $flashMessageService;

try {
    // Get required services
    $authService = $serviceProvider->getAuth();
    $newsService = $serviceProvider->getNews();
    
    // Check authentication
    if (!$authService->isAuthenticated()) {
        $flashMessageService->addError('Please log in to access this area.');
        header("Location: /index.php?page=login");
        exit();
    }

    // Check permissions - only admin and employee can create articles
    $userRole = $authService->getCurrentUserRole();
    if (!in_array($userRole, ['admin', 'employee'])) {
        $flashMessageService->addError('Access denied. Insufficient permissions.');
        header("Location: /index.php?page=dashboard");
        exit();
    }

    // Validate POST request
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        $flashMessageService->addError('Invalid request method.');
        header("Location: /index.php?page=create_article");
        exit();
    }

    // Validate required fields
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $excerpt = trim($_POST['excerpt'] ?? '');
    $status = $_POST['status'] ?? 'draft';
    $categories = $_POST['categories'] ?? [];
    
    if (empty($title)) {
        $flashMessageService->addError('Article title is required.');
        header("Location: /index.php?page=create_article");
        exit();
    }

    if (empty($content)) {
        $flashMessageService->addError('Article content is required.');
        header("Location: /index.php?page=create_article");
        exit();
    }

    // Generate slug if not provided
    $slug = trim($_POST['slug'] ?? '');
    if (empty($slug)) {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
    }

    // Prepare article data
    $articleData = [
        'title' => $title,
        'short_description' => $excerpt,
        'full_text' => $content,
        'date' => date('Y-m-d'),
        'status' => $status,
        'user_id' => $authService->getCurrentUserId(),
        'slug' => $slug,
        'categories' => is_array($categories) ? $categories : [],
        'featured' => isset($_POST['featured']) ? 1 : 0,
        'allow_comments' => isset($_POST['allow_comments']) ? 1 : 0,
    ];

    // Create the article using NewsService
    try {
        $articleId = $newsService->createArticle($articleData);
        
        if ($articleId) {
            $flashMessageService->addSuccess('Article created successfully!');
            
            // Redirect based on status
            if ($status === 'published') {
                $flashMessageService->addInfo('Article has been published and is now visible to users.');
            } elseif ($status === 'pending_review') {
                $flashMessageService->addInfo('Article has been submitted for review.');
            } else {
                $flashMessageService->addInfo('Article saved as draft.');
            }
            
            header("Location: /index.php?page=edit_article&id=" . $articleId);
            exit();
        } else {
            throw new Exception('Failed to create article');
        }
    } catch (Exception $e) {
        error_log("Article creation failed: " . $e->getMessage());
        $flashMessageService->addError('Failed to create article: ' . $e->getMessage());
        header("Location: /index.php?page=create_article");
        exit();
    }

} catch (Exception $e) {
    error_log("Critical error in create_article API: " . $e->getMessage());
    $flashMessageService->addError('System error occurred. Please try again.');
    header("Location: /index.php?page=create_article");
    exit();
}
