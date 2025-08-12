<?php
/**
 * API endpoint for updating client profile
 * PUT /page/api/client/profile_update.php
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../includes/bootstrap.php';

use App\Infrastructure\Lib\Database;
use App\Application\Controllers\ClientProfileController;

try {
    if (!in_array($_SERVER['REQUEST_METHOD'], ['PUT', 'POST'])) {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        exit;
    }

    $db = new Database();
    $controller = new ClientProfileController($db);
    $result = $controller->updateProfile();

    http_response_code($result['success'] ? 200 : 400);
    echo json_encode($result);

} catch (Exception $e) {
    error_log("API Error in profile_update.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Internal server error']);
}
