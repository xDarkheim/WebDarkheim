<?php

/**
 * Download Backup API
 *
 * This API allows admins to download database backups.
 * It supports downloading specific backups by filename with security checks.
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

try {
    // Get AuthenticationService
    $authService = $serviceProvider->getAuth();

    // Check authentication and admin rights
    if (!$authService->isAuthenticated() || !$authService->hasRole('admin')) {
        header('Content-Type: application/json');
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'error' => 'Access denied. Admin privileges required.'
        ]);
        exit();
    }

    // Check request method and filename parameter
    if ($_SERVER['REQUEST_METHOD'] !== 'GET' || !isset($_GET['filename'])) {
        header('Content-Type: application/json');
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Invalid request. GET method with filename parameter required.'
        ]);
        exit();
    }

    $filename = $_GET['filename'];

    // Initialize backup controller
    $backupController = new DatabaseBackupController();

    // Get backup directory and construct file path
    $backupDir = dirname(__DIR__, 3) . '/storage/backups/';
    $backupPath = $backupDir . $filename;

    if (!file_exists($backupPath)) {
        header('Content-Type: application/json');
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'Backup file not found.'
        ]);
        exit();
    }

    // Security check: ensure file is within backup directory
    $realPath = realpath($backupPath);

    if (!$realPath || strpos($realPath, $backupDir) !== 0) {
        header('Content-Type: application/json');
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'error' => 'Invalid file path.'
        ]);
        exit();
    }

    // Set headers for file download
    $fileSize = filesize($backupPath);
    $mimeType = 'application/gzip';

    header('Content-Type: ' . $mimeType);
    header('Content-Length: ' . $fileSize);
    header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');

    // Clear any output buffer
    if (ob_get_level()) {
        ob_end_clean();
    }

    // Read and output file
    readfile($backupPath);

    // Log successful download
    error_log("Admin downloaded backup file: " . $filename);
    exit();

} catch (Exception $e) {
    error_log("Download backup API error: " . $e->getMessage());

    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'System error occurred during download'
    ]);
}
