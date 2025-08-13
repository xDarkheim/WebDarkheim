<?php
/**
 * Download Document Page - PHASE 8
 * Handles project document downloads for clients
 */

declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/includes/bootstrap.php';

global $serviceProvider, $flashMessageService;

try {
    $authService = $serviceProvider->getAuth();
} catch (Exception $e) {
    error_log("Critical: Failed to get AuthenticationService: " . $e->getMessage());
    die("System error occurred.");
}

if (!$authService->isAuthenticated()) {
    $flashMessageService->addError('Please log in to access this area.');
    header("Location: /index.php?page=login");
    exit();
}

$userRole = $authService->getCurrentUserRole();
if (!in_array($userRole, ['client', 'employee', 'admin'])) {
    header('Location: /index.php?page=home');
    exit;
}

$documentId = $_GET['id'] ?? null;
if (!$documentId) {
    $flashMessageService->addError('Document ID is required.');
    header("Location: /index.php?page=user_documents");
    exit();
}

// In a real implementation, this would serve the document file
// For now, just redirect back with a message
$flashMessageService->addInfo('Document download functionality is under development (Phase 8).');
header("Location: /index.php?page=user_documents");
exit();
?>
