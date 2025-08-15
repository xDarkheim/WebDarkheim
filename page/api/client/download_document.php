<?php
/**
 * Download Document API
 * Allows clients to download project documents
 */

require_once '../../../../includes/bootstrap.php';

use App\Application\Core\ServiceProvider;
use App\Application\Controllers\StudioProjectController;

// Set JSON headers
header('Content-Type: application/json');

try {
    $services = ServiceProvider::getInstance();
    $controller = new StudioProjectController($services);

    // Handle document download
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $result = $controller->downloadDocument();

        if ($result['success']) {
            // Set appropriate headers for file download
            header('Content-Type: ' . $result['content_type']);
            header('Content-Disposition: attachment; filename="' . $result['file_name'] . '"');
            header('Content-Length: ' . filesize($result['file_path']));

            // Output file
            readfile($result['file_path']);
            exit;
        } else {
            http_response_code(404);
            echo json_encode($result);
        }
    } else {
        http_response_code(405);
        echo json_encode([
            'success' => false,
            'error' => 'Method not allowed'
        ]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error'
    ]);

    error_log("Document download error: " . $e->getMessage());
}
?>
