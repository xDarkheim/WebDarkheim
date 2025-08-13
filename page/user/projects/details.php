<?php
/**
 * Project Details Page - PHASE 8
 * Shows detailed information about a specific studio project for clients
 */

declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/includes/bootstrap.php';

global $serviceProvider, $flashMessageService, $database_handler;

try {
    $authService = $serviceProvider->getAuth();
} catch (Exception $e) {
    error_log("Critical: Failed to get AuthenticationService: " . $e->getMessage());
    die("System error occurred.");
}

// Check authentication
if (!$authService->isAuthenticated()) {
    $flashMessageService->addError('Please log in to access this area.');
    header("Location: /index.php?page=login");
    exit();
}

// Check if user can access client area
$userRole = $authService->getCurrentUserRole();
if (!in_array($userRole, ['client', 'employee', 'admin'])) {
    header('Location: /index.php?page=home');
    exit;
}

$projectId = $_GET['id'] ?? null;
if (!$projectId) {
    $flashMessageService->addError('Project ID is required.');
    header("Location: /index.php?page=user_projects");
    exit();
}

$pageTitle = 'Project Details';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-light">

<div class="container py-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h2">
                    <i class="fas fa-code text-primary"></i>
                    Project Details
                </h1>
                <a href="/index.php?page=user_projects" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Projects
                </a>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-info-circle text-primary"></i>
                        Project Information
                    </h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="fas fa-construction"></i>
                        <strong>Phase 8 Development:</strong> This page is part of the client portal system currently under development.
                    </div>

                    <p>Project details will be displayed here, including:</p>
                    <ul>
                        <li>Project description and requirements</li>
                        <li>Current development status</li>
                        <li>Timeline and milestones</li>
                        <li>Progress updates</li>
                        <li>Team members assigned</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Quick Actions</h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="/index.php?page=user_projects_timeline&id=<?= htmlspecialchars($projectId) ?>" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-clock"></i> View Timeline
                        </a>
                        <a href="/index.php?page=user_tickets_create" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-ticket-alt"></i> Create Ticket
                        </a>
                        <a href="/index.php?page=user_documents" class="btn btn-outline-info btn-sm">
                            <i class="fas fa-folder"></i> Project Documents
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
