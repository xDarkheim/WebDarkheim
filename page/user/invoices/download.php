<?php
/**
 * Download Invoice Page - PHASE 8
 * Handles invoice PDF downloads for clients
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

$invoiceId = $_GET['id'] ?? null;
if (!$invoiceId) {
    $flashMessageService->addError('Invoice ID is required.');
    header("Location: /index.php?page=user_invoices");
    exit();
}

// In a real implementation, this would generate and serve the PDF
// For now, just redirect back with a message
$flashMessageService->addInfo('Invoice download functionality is under development (Phase 8).');
header("Location: /index.php?page=user_invoices");
exit();
?>
