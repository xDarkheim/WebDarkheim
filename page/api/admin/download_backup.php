<?php

/**
 * Download Backup API
 *
 * This API allows admins to download database backups.
 * It supports downloading specific backups by filename.
 * It returns a download link for the specified backup file.
 * It also handles security checks to ensure the file is within the backup directory.
 * It also handles errors gracefully and logs any exceptions.
 *
 * @author Dmytro Hovenko
 */

declare(strict_types=1);

use App\Application\Controllers\DatabaseBackupController;

// Start a session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

try {
    // Load bootstrap
    require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';

    // Use global services from bootstrap.php
    global $auth;

    // Check authentication and admin rights
    if (!$auth || !$auth->isAuthenticated() || !$auth->hasRole('admin')) {
        http_response_code(403);
        die('Access denied. Admin privileges required.');
    }

    // Only allow GET requests for downloads
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        die('Method not allowed. Only GET requests are accepted.');
    }

    // Get filename from query parameters - поддерживаем оба параметра для совместимости
    $filename = '';
    if (!empty($_GET['filename'])) {
        $filename = $_GET['filename'];
    } elseif (!empty($_GET['file'])) {
        $filename = $_GET['file']; // Для обратной совместимости
    } else {
        http_response_code(400);
        die('Missing required parameter: filename');
    }

    // Validate filename
    if (!is_string($filename) || str_contains($filename, '..')) {
        http_response_code(400);
        die('Invalid filename provided.');
    }

    // Initialize backup controller
    $backupController = new DatabaseBackupController();
    $backups = $backupController->getBackupsList();

    // Find the backup file
    $backupPath = '';
    $backupFound = false;

    foreach ($backups as $backup) {
        if ($backup['filename'] === $filename) {
            $backupPath = $backup['path'];
            $backupFound = true;
            break;
        }
    }

    if (!$backupFound) {
        http_response_code(404);
        die('Backup file not found in system records.');
    }

    if (!file_exists($backupPath)) {
        http_response_code(404);
        die('Backup file not found on filesystem.');
    }

    // Security check - ensure a file is within a backup directory
    $realPath = realpath($backupPath);
    $backupDir = realpath(dirname($backupPath));

    if (!$realPath || !str_starts_with($realPath, $backupDir)) {
        http_response_code(403);
        die('Access denied. Invalid file path.');
    }

    // Get file info
    $fileSize = filesize($backupPath);
    $mimeType = 'application/gzip';

    // Set headers for download
    header('Content-Type: ' . $mimeType);
    header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
    header('Content-Length: ' . $fileSize);
    header('Cache-Control: no-cache, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');

    // Clear any output buffers
    if (ob_get_level()) {
        ob_end_clean();
    }

    // Read and output the file
    if ($handle = fopen($backupPath, 'rb')) {
        while (!feof($handle)) {
            echo fread($handle, 8192);
            flush();
        }
        fclose($handle);
    } else {
        http_response_code(500);
        die('Error reading backup file.');
    }

    exit();

} catch (Exception $e) {
    error_log("Download backup API error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());

    http_response_code(500);
    die('Internal server error occurred.');
}
