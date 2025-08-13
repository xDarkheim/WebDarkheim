<?php

/**
 * Backup Management API
 *
 * This API provides a simple interface for managing database backups.
 * It supports creating, deleting, and downloading backups.
 *
 * @author Dmytro Hovenko
 */

declare(strict_types=1);

use App\Application\Controllers\DatabaseBackupController;
use App\Infrastructure\Lib\FlashMessageService;

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

// Start a session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

try {
    // Load bootstrap
    require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';

    // Use global services from bootstrap.php
    global $auth;

    // Initialize FlashMessageService properly
    $flashService = new FlashMessageService();

    // Check authentication and admin rights
    if (!$auth || !$auth->isAuthenticated() || !$auth->hasRole('admin')) {
        $flashService->addError('Access denied. Admin privileges required.');
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'error' => 'Access denied. Admin privileges required.'
        ]);
        exit();
    }

    // Only allow POST requests
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        $flashService->addError('Method not allowed. Only POST requests are accepted.');
        http_response_code(405);
        echo json_encode([
            'success' => false,
            'error' => 'Method not allowed. Only POST requests are accepted.'
        ]);
        exit();
    }

    // Get and decode JSON input
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        $flashService->addError('Invalid JSON data.');
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Invalid JSON data.'
        ]);
        exit();
    }

    // Validate required fields
    if (!isset($data['action']) || !isset($data['filename'])) {
        $flashService->addError('Missing required fields: action and filename.');
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Missing required fields: action and filename.'
        ]);
        exit();
    }

    $action = $data['action'];
    $filename = $data['filename'];

    // Validate filename
    if (empty($filename) || !is_string($filename)) {
        $flashService->addError('Invalid filename provided.');
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Invalid filename provided.'
        ]);
        exit();
    }

    // Initialize backup controller
    $backupController = new DatabaseBackupController();

    switch ($action) {
        case 'delete':
            $result = $backupController->deleteBackup($filename);

            if ($result) {
                // Set flash message for success using FlashMessageService
                $flashService->addSuccess("Backup file '$filename' has been deleted successfully.");

                echo json_encode([
                    'success' => true,
                    'message' => 'Backup deleted successfully',
                    'filename' => $filename
                ]);
            } else {
                // Set flash message for error using FlashMessageService
                $flashService->addError("Failed to delete backup file '$filename'. File may not exist or is not accessible.");

                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'error' => 'Failed to delete backup file'
                ]);
            }
            break;

        case 'download':
            // For download, we need to handle it differently since it's not a JSON response
            $backups = $backupController->getBackupsList();
            $backupFound = false;
            $backupPath = '';

            foreach ($backups as $backup) {
                if ($backup['filename'] === $filename) {
                    $backupPath = $backup['path'];
                    $backupFound = true;
                    break;
                }
            }

            if (!$backupFound || !file_exists($backupPath)) {
                $flashService->addError('Backup file not found');
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'error' => 'Backup file not found'
                ]);
                break;
            }

            // For download, redirect to a download handler
            echo json_encode([
                'success' => true,
                'action' => 'download',
                'download_url' => '/page/api/download_backup.php?filename=' . urlencode($filename)
            ]);
            break;

        default:
            $flashService->addError('Invalid action specified.');
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Invalid action specified.'
            ]);
            break;
    }

} catch (Exception $e) {
    error_log("Backup management API error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());

    // Set flash message for error using FlashMessageService
    if (isset($flashService)) {
        $flashService->addError('Internal server error occurred during backup operation.');
    }

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error occurred.'
    ]);
}
