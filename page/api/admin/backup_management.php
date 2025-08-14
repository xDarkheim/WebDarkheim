<?php

/**
 * Backup Management API
 *
 * This API provides interface for managing database backups.
 * It supports deleting individual backup files with security checks.
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

    // Handle DELETE requests for file deletion
    if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        // Get JSON input
        $input = json_decode(file_get_contents('php://input'), true);

        if (!$input || !isset($input['filename'])) {
            $flashMessageService->addError('Invalid request data. Filename required.');
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Invalid request data. Filename required.',
                'flash_messages' => $flashMessageService->getAllMessages()
            ]);
            exit();
        }

        $filename = $input['filename'];

        // Initialize backup controller
        $backupController = new DatabaseBackupController();

        // Delete backup file using existing method
        $result = $backupController->deleteBackup($filename);

        if ($result) {
            $flashMessageService->addSuccess('Backup file deleted successfully: ' . $filename);

            echo json_encode([
                'success' => true,
                'message' => 'Backup file deleted successfully',
                'filename' => $filename,
                'flash_messages' => $flashMessageService->getAllMessages()
            ]);
        } else {
            $flashMessageService->addError('Failed to delete backup file: Unknown error occurred');

            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Unknown error occurred during deletion',
                'flash_messages' => $flashMessageService->getAllMessages()
            ]);
        }

    } else {
        // Method not allowed
        $flashMessageService->addError('Method not allowed. Only DELETE requests supported.');
        http_response_code(405);
        echo json_encode([
            'success' => false,
            'error' => 'Method not allowed. Only DELETE requests supported.',
            'flash_messages' => $flashMessageService->getAllMessages()
        ]);
    }

} catch (Exception $e) {
    error_log("Backup management API error: " . $e->getMessage());
    $flashMessageService->addError('System error occurred: ' . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'System error occurred during backup management',
        'flash_messages' => $flashMessageService->getAllMessages()
    ]);
}
