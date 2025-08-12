<?php

/**
 * Manual Backup API
 *
 * This API allows admins to create manual database backups.
 * It supports creating a backup file and returning a success or error message.
 *
 * @author Dmytro Hovenko
 */

declare(strict_types=1);

use App\Application\Controllers\DatabaseBackupController;
use App\Infrastructure\Lib\FlashMessageService;

// Load global services from bootstrap.php
require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';

// Use global services
global $auth;

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Set JSON response header
header('Content-Type: application/json');

try {
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

    // Check request method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        $flashService->addError('Method not allowed. POST required.');
        http_response_code(405);
        echo json_encode([
            'success' => false,
            'error' => 'Method not allowed. POST required.'
        ]);
        exit();
    }

    // Get request data
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (!$data || !isset($data['action'])) {
        $flashService->addError('Invalid request data.');
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Invalid request data.'
        ]);
        exit();
    }

    if ($data['action'] !== 'create_manual_backup') {
        $flashService->addError('Invalid action.');
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Invalid action.'
        ]);
        exit();
    }

    // Initialize backup controller
    $backupController = new DatabaseBackupController();

    // Create manual backup
    $result = $backupController->performBackup();

    if ($result['success']) {
        // Set flash message for success using FlashMessageService
        $flashService->addSuccess('Manual backup created successfully: ' . ($result['filename'] ?? 'backup file'));

        echo json_encode([
            'success' => true,
            'message' => $result['message'] ?? 'Manual backup created successfully.',
            'filename' => $result['filename'] ?? null,
            'size' => $result['size'] ?? null
        ]);
    } else {
        // Set flash message for error using FlashMessageService
        $flashService->addError('Failed to create manual backup: ' . ($result['error'] ?? 'Unknown error'));

        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $result['error'] ?? 'Failed to create backup.'
        ]);
    }

} catch (Exception $e) {
    error_log("Manual backup API error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());

    // Set flash message for error using FlashMessageService
    if (isset($flashService)) {
        $flashService->addError('Internal server error occurred during manual backup.');
    }

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error occurred.'
    ]);
}
