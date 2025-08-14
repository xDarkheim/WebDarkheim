<?php

/**
 * Cleanup Old Backups API
 *
 * This API allows admins to clean up old backup files.
 * It supports automatic deletion of backups older than a specified threshold.
 *
 * @author GitHub Copilot
 */

declare(strict_types=1);

use App\Application\Controllers\DatabaseBackupController;

// Load global services from bootstrap.php
require_once dirname(__DIR__, 3) . '/includes/bootstrap.php';

// Use global services from bootstrap.php
global $database_handler, $serviceProvider, $flashMessageService;

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Set JSON response header
header('Content-Type: application/json');

try {
    // Get AuthenticationService
    $authService = $serviceProvider->getAuth();

    // Check authentication and admin rights
    if (!$authService->isAuthenticated() || !$authService->hasRole('admin')) {
        $flashMessageService->addError('Access denied. Admin privileges required.');
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'error' => 'Access denied. Admin privileges required.',
            'flash_messages' => $flashMessageService->getAllMessages()
        ]);
        exit();
    }

    // Check request method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        $flashMessageService->addError('Method not allowed. POST required.');
        http_response_code(405);
        echo json_encode([
            'success' => false,
            'error' => 'Method not allowed. POST required.',
            'flash_messages' => $flashMessageService->getAllMessages()
        ]);
        exit();
    }

    // Initialize backup controller
    $backupController = new DatabaseBackupController();

    // Clean up old backups
    $result = $backupController->cleanupOldBackups();

    if ($result['success']) {
        $filesDeleted = $result['files_deleted'] ?? 0;
        $totalDeleted = $result['total_deleted'] ?? 0;

        $flashMessageService->addSuccess("Cleanup completed! Deleted {$filesDeleted} old backup files, freed " .
            ($result['size_freed'] ?? '0 B') . " of disk space.");

        echo json_encode([
            'success' => true,
            'message' => 'Old backups cleaned up successfully',
            'files_deleted' => $filesDeleted,
            'total_deleted' => $totalDeleted,
            'size_freed' => $result['size_freed'] ?? '0 B',
            'flash_messages' => $flashMessageService->getAllMessages()
        ]);
    } else {
        $errorMessage = $result['error'] ?? 'Unknown error occurred during cleanup';
        $flashMessageService->addError('Failed to cleanup old backups: ' . $errorMessage);

        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $errorMessage,
            'flash_messages' => $flashMessageService->getAllMessages()
        ]);
    }

} catch (Exception $e) {
    error_log("Cleanup backups API error: " . $e->getMessage());
    $flashMessageService->addError('System error occurred during cleanup: ' . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'System error occurred during cleanup',
        'flash_messages' => $flashMessageService->getAllMessages()
    ]);
}
