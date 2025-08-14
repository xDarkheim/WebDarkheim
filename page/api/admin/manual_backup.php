<?php

/**
 * Manual Backup API
 *
 * This API allows admins to create manual database backups.
 * It supports creating a backup file and returning a success or error message.
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

    // Create manual backup using existing method
    $result = $backupController->createFullBackup();

    if ($result['success']) {
        $flashMessageService->addSuccess('Manual backup created successfully! Backup file: ' . ($result['filename'] ?? 'backup.sql.gz'));

        echo json_encode([
            'success' => true,
            'message' => 'Manual backup created successfully',
            'filename' => $result['filename'] ?? null,
            'size' => $result['size'] ?? null,
            'created_at' => $result['created_at'] ?? time(),
            'flash_messages' => $flashMessageService->getAllMessages()
        ]);
    } else {
        $errorMessage = $result['error'] ?? 'Unknown error occurred during backup creation';
        $flashMessageService->addError('Failed to create manual backup: ' . $errorMessage);

        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $errorMessage,
            'flash_messages' => $flashMessageService->getAllMessages()
        ]);
    }

} catch (Exception $e) {
    error_log("Manual backup API error: " . $e->getMessage());
    $flashMessageService->addError('System error occurred while creating backup: ' . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'System error occurred while creating backup',
        'flash_messages' => $flashMessageService->getAllMessages()
    ]);
}
