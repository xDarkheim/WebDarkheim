<?php

/**
 * Delete Article Page
 *
 * This page allows users to delete an existing article.
 * It checks for the current user's role and permissions before proceeding.
 *
 * @author Dmytro Hovenko
 */

declare(strict_types=1);

// Use global services from the new DI architecture
global $flashMessageService, $database_handler, $container, $serviceProvider;

// Get AuthenticationService instead of direct SessionManager access
try {
    $authService = $serviceProvider->getAuth();
} catch (Exception $e) {
    error_log("Critical: Failed to get AuthenticationService instance: " . $e->getMessage());
    die("A critical system error occurred. Please try again later.");
}

// Check for required services
if (!isset($database_handler)) {
    error_log("Critical: Database handler not available in delete_article.php");
    die("A critical system error occurred. Please try again later.");
}
if (!isset($flashMessageService)) {
    error_log("Critical: FlashMessageService not available in delete_article.php");
    die("A critical system error occurred. Please try again later.");
}
if (!isset($container)) {
    error_log("Critical: Container not available in delete_article.php");
    die("A critical system error occurred. Please try again later.");
}

// Check authorization via new AuthenticationService
if (!$authService->isAuthenticated()) {
    $flashMessageService->addError('Please log in to delete articles.');
    header('Location: /index.php?page=login');
    exit();
}

$db = $database_handler->getConnection();

if (!$db) {
    $flashMessageService->addError('Database connection error.');
    header('Location: /index.php?page=manage_articles');
    exit;
}

// Check request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $flashMessageService->addError('Invalid request method. Articles can only be deleted via POST request.');
    header('Location: /index.php?page=manage_articles');
    exit;
}

// Check CSRF token via global system
use App\Application\Middleware\CSRFMiddleware;

if (!CSRFMiddleware::validateQuick()) {
    $flashMessageService->addError('Security error: Invalid CSRF token. Please try again from the articles list.');
    header('Location: /index.php?page=manage_articles');
    exit;
}

// Check for article ID
if (!isset($_POST['article_id']) || !filter_var($_POST['article_id'], FILTER_VALIDATE_INT)) {
    $flashMessageService->addError('Invalid or missing article ID for deletion.');
    header('Location: /index.php?page=manage_articles');
    exit;
}

$article_id = (int)$_POST['article_id'];

// Get user data via AuthenticationService
$current_user_id = $authService->getCurrentUserId();
$user_role = $authService->getCurrentUserRole();
$currentUser = $authService->getCurrentUser();

try {
    // Get article info for permission check and logging
    $stmt_check = $db->prepare("SELECT id, title, user_id FROM articles WHERE id = ?");
    $stmt_check->execute([$article_id]);
    $article_data = $stmt_check->fetch(PDO::FETCH_ASSOC);

    if (!$article_data) {
        $flashMessageService->addError("Article with ID $article_id not found or already deleted.");
        header('Location: /index.php?page=manage_articles');
        exit;
    }

    $article_title = $article_data['title'];
    $article_owner_id = (int)$article_data['user_id'];

    // Permission check: admin can delete any article, user can delete only their own
    if ($user_role !== 'admin' && $article_owner_id !== $current_user_id) {
        $flashMessageService->addError("Access denied. You do not have permission to delete the article '$article_title'.");
        error_log("Delete attempt denied: User ID $current_user_id tried to delete article ID $article_id owned by user $article_owner_id");
        header('Location: /index.php?page=manage_articles');
        exit;
    }

    // Perform deletion
    $stmt_delete = $db->prepare("DELETE FROM articles WHERE id = ?");
    if ($stmt_delete->execute([$article_id])) {
        if ($stmt_delete->rowCount() > 0) {
            $flashMessageService->addSuccess("Article '$article_title' was successfully deleted.");
            error_log("Article deleted successfully: ID $article_id, Title: '$article_title', Deleted by user ID: $current_user_id");
        } else {
            $flashMessageService->addError("Article not found or already deleted during the deletion process.");
            error_log("Delete operation completed but no rows affected for article ID: $article_id");
        }
    } else {
        $flashMessageService->addError("Failed to delete article '$article_title'. Database error occurred.");
        error_log("Failed to delete article ID: $article_id. PDO Error: " . print_r($stmt_delete->errorInfo(), true));
    }

} catch (PDOException $e) {
    $flashMessageService->addError("Database error while deleting article. Please try again or contact support.");
    error_log("PDOException in delete_article.php for article ID $article_id: " . $e->getMessage());
} catch (Exception $e) {
    $flashMessageService->addError("An unexpected error occurred while deleting the article.");
    error_log("Exception in delete_article.php for article ID $article_id: " . $e->getMessage());
}

// Always redirect back to article management
header('Location: /index.php?page=manage_articles');
exit;
