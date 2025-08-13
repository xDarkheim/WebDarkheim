<?php
/**
 * Project Timeline Page - PHASE 8
 * Shows timeline and milestones for a studio project
 */

declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/includes/bootstrap.php';

global $serviceProvider, $flashMessageService;

try {
    $authService = $serviceProvider->getAuth();
} catch (Exception $e) {
    error_log("Critical: Failed to get AuthenticationService: " . $e->getMessage());
    die("System error occurred.");
}

if (!$authService->isAuthenticated()) {
    $flashMessageService->addError('Please log in to access this area.');
    header("Location: /index.php?page=login");
    exit();
}

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

$pageTitle = 'Project Timeline';
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
                    <i class="fas fa-clock text-primary"></i>
                    Project Timeline
                </h1>
                <div class="d-flex gap-2">
                    <a href="/index.php?page=user_projects_details&id=<?= htmlspecialchars($projectId) ?>" class="btn btn-outline-primary">
                        <i class="fas fa-info-circle"></i> Project Details
                    </a>
                    <a href="/index.php?page=user_projects" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Projects
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-timeline text-primary"></i>
                        Project Milestones & Timeline
                    </h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="fas fa-construction"></i>
                        <strong>Phase 8 Development:</strong> Timeline functionality is currently under development.
                    </div>

                    <p>This page will display:</p>
                    <ul>
                        <li>Project milestones and deadlines</li>
                        <li>Completion status for each phase</li>
                        <li>Timeline visualization</li>
                        <li>Progress tracking</li>
                        <li>Upcoming deliverables</li>
                    </ul>

                    <!-- Placeholder timeline -->
                    <div class="timeline mt-4">
                        <div class="timeline-item">
                            <div class="timeline-marker bg-success"></div>
                            <div class="timeline-content">
                                <h6>Project Initiated</h6>
                                <p class="text-muted">Project requirements gathered and approved</p>
                                <small class="text-success">Completed</small>
                            </div>
                        </div>
                        <div class="timeline-item">
                            <div class="timeline-marker bg-warning"></div>
                            <div class="timeline-content">
                                <h6>Development Phase</h6>
                                <p class="text-muted">Active development in progress</p>
                                <small class="text-warning">In Progress</small>
                            </div>
                        </div>
                        <div class="timeline-item">
                            <div class="timeline-marker bg-secondary"></div>
                            <div class="timeline-content">
                                <h6>Testing & Deployment</h6>
                                <p class="text-muted">Quality assurance and final deployment</p>
                                <small class="text-muted">Pending</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.timeline {
    position: relative;
    padding-left: 30px;
}
.timeline::before {
    content: '';
    position: absolute;
    left: 15px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #dee2e6;
}
.timeline-item {
    position: relative;
    margin-bottom: 30px;
}
.timeline-marker {
    position: absolute;
    left: -23px;
    top: 5px;
    width: 16px;
    height: 16px;
    border-radius: 50%;
    border: 3px solid #fff;
    box-shadow: 0 0 0 2px #dee2e6;
}
.timeline-content {
    background: #fff;
    padding: 15px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
</style>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
