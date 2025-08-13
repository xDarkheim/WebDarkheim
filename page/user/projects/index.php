<?php

/**
 * Studio Projects - Client Portal
 * View active development projects from the studio
 *
 * @author GitHub Copilot
 */

declare(strict_types=1);

// Use global services from the DI architecture
global $flashMessageService, $database_handler, $container, $serviceProvider;

try {
    $authService = $serviceProvider->getAuth();
} catch (Exception $e) {
    error_log("Critical: Failed to get AuthenticationService instance: " . $e->getMessage());
    die("A critical system error occurred. Please try again later.");
}

// Check authentication
if (!$authService->isAuthenticated()) {
    $flashMessageService->addError('Please log in to access your projects.');
    header("Location: /index.php?page=login");
    exit();
}

$currentUser = $authService->getCurrentUser();

// Check if user can access client area
if (!in_array($currentUser['role'], ['client', 'employee', 'admin'])) {
    header('Location: /index.php?page=home');
    exit;
}

// Get studio projects for current user
try {
    $sql = "SELECT sp.*, 
                   COUNT(pm.id) as milestone_count,
                   COUNT(CASE WHEN pm.status = 'completed' THEN 1 END) as completed_milestones
            FROM studio_projects sp
            LEFT JOIN project_milestones pm ON sp.id = pm.project_id
            WHERE sp.client_id = ?
            GROUP BY sp.id
            ORDER BY sp.created_at DESC";

    $stmt = $database_handler->getConnection()->prepare($sql);
    $stmt->execute([$currentUser['id']]);
    $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    error_log("Error getting studio projects: " . $e->getMessage());
    $projects = [];
}

// Get flash messages
$flashMessages = $flashMessageService->getAllMessages();
$pageTitle = 'Studio Projects - Client Portal';

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .project-card {
            transition: all 0.3s ease;
            border: none;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .project-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        .progress-ring {
            transform: rotate(-90deg);
        }
        .status-badge {
            font-size: 0.8rem;
        }
    </style>
</head>
<body class="bg-light">

<div class="container py-4">
    <!-- Breadcrumb Navigation -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/index.php?page=dashboard">Dashboard</a></li>
            <li class="breadcrumb-item active">Studio Projects</li>
        </ol>
    </nav>

    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3 mb-0">
                <i class="fas fa-code text-primary"></i>
                Studio Projects
            </h1>
            <p class="text-muted">Track your development projects with our studio</p>
        </div>
    </div>

    <!-- Flash Messages -->
    <?php if (!empty($flashMessages)): ?>
        <div class="row mb-4">
            <div class="col-12">
                <?php foreach ($flashMessages as $type => $messages): ?>
                    <?php foreach ($messages as $message): ?>
                        <div class="alert alert-<?= $type === 'error' ? 'danger' : $type ?> alert-dismissible fade show">
                            <?= htmlspecialchars($message['text']) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Projects List -->
    <div class="row">
        <?php if (empty($projects)): ?>
            <div class="col-12">
                <div class="card project-card text-center py-5">
                    <div class="card-body">
                        <i class="fas fa-code fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No active projects</h5>
                        <p class="text-muted">You don't have any active development projects with our studio yet.</p>
                        <a href="/index.php?page=contact" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Start a Project
                        </a>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($projects as $project): ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card project-card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <small class="text-muted"><?= ucfirst($project['project_type']) ?></small>
                            <span class="badge <?= getStatusBadgeClass($project['status']) ?> status-badge">
                                <?= ucfirst(str_replace('_', ' ', $project['status'])) ?>
                            </span>
                        </div>
                        <div class="card-body">
                            <h5 class="card-title mb-3"><?= htmlspecialchars($project['project_name']) ?></h5>

                            <!-- Progress Circle -->
                            <div class="text-center mb-3">
                                <div class="position-relative d-inline-block">
                                    <svg width="80" height="80" class="progress-ring">
                                        <circle cx="40" cy="40" r="30" stroke="#e9ecef" stroke-width="6" fill="transparent"/>
                                        <circle cx="40" cy="40" r="30" stroke="#0d6efd" stroke-width="6" fill="transparent"
                                                stroke-dasharray="<?= 2 * 3.14159 * 30 ?>"
                                                stroke-dashoffset="<?= 2 * 3.14159 * 30 * (1 - ($project['progress_percentage'] ?? 0) / 100) ?>"/>
                                    </svg>
                                    <div class="position-absolute top-50 start-50 translate-middle">
                                        <strong><?= $project['progress_percentage'] ?? 0 ?>%</strong>
                                    </div>
                                </div>
                            </div>

                            <p class="card-text text-muted small mb-3">
                                <?= htmlspecialchars(substr($project['description'] ?? '', 0, 100)) ?><?= strlen($project['description'] ?? '') > 100 ? '...' : '' ?>
                            </p>

                            <!-- Project Details -->
                            <div class="mb-3">
                                <?php if ($project['start_date']): ?>
                                    <small class="text-muted d-block">
                                        <i class="fas fa-play"></i> Started: <?= date('M j, Y', strtotime($project['start_date'])) ?>
                                    </small>
                                <?php endif; ?>
                                <?php if ($project['estimated_completion']): ?>
                                    <small class="text-muted d-block">
                                        <i class="fas fa-flag-checkered"></i> Est. Completion: <?= date('M j, Y', strtotime($project['estimated_completion'])) ?>
                                    </small>
                                <?php endif; ?>
                                <?php if ($project['milestone_count'] > 0): ?>
                                    <small class="text-muted d-block">
                                        <i class="fas fa-tasks"></i> Milestones: <?= $project['completed_milestones'] ?>/<?= $project['milestone_count'] ?>
                                    </small>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="card-footer bg-transparent border-0">
                            <div class="d-flex justify-content-between">
                                <a href="/index.php?page=user_projects_details&id=<?= $project['id'] ?>" class="btn btn-outline-primary btn-sm">
                                    <i class="fas fa-eye"></i> View Details
                                </a>
                                <a href="/index.php?page=user_projects_timeline&id=<?= $project['id'] ?>" class="btn btn-outline-secondary btn-sm">
                                    <i class="fas fa-clock"></i> Timeline
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>

<?php
function getStatusBadgeClass($status): string
{
    return match($status) {
        'planning' => 'bg-secondary',
        'development' => 'bg-primary',
        'testing' => 'bg-warning',
        'deployment' => 'bg-info',
        'completed' => 'bg-success',
        'on_hold' => 'bg-danger',
        default => 'bg-secondary'
    };
}
?>
