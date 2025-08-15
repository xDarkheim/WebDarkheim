<?php
/**
 * Studio Project Details - Client Portal
 * Detailed view of a single studio development project
 */

require_once '../../../includes/bootstrap.php';

use App\Application\Core\ServiceProvider;
use App\Application\Controllers\StudioProjectController;

$services = ServiceProvider::getInstance();
$auth = $services->getAuth();
$user = $auth->getCurrentUser();

// Check if user is authenticated and is a client
if (!$user || !in_array($user['role'], ['client', 'admin'])) {
    header('Location: /page/auth/login.php');
    exit;
}

// Initialize controller and get data
$controller = new StudioProjectController($services);
$data = $controller->getProjectDetails();

if (!$data['success']) {
    $error = $data['error'];
} else {
    $project = $data['project'];
    $milestones = $data['milestones'];
    $documents = $data['documents'];
    $timeline = $data['timeline'];
    $progress = $data['progress'];
}

// Page metadata
$pageTitle = isset($project) ? $project['project_name'] . ' - Project Details' : 'Project Details';
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => '/page/user/dashboard.php'],
    ['title' => 'Studio Projects', 'url' => '/page/user/projects/index.php'],
    ['title' => isset($project) ? $project['project_name'] : 'Project Details', 'url' => '', 'active' => true]
];
?>

<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> - <?= htmlspecialchars(getSiteName()) ?></title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Custom CSS -->
    <link href="/themes/default/css/admin.css" rel="stylesheet">

    <style>
        .progress-circle {
            width: 120px;
            height: 120px;
        }
        .milestone-item {
            border-left: 4px solid var(--bs-border-color);
            padding-left: 20px;
            margin-bottom: 25px;
            position: relative;
        }
        .milestone-item.completed {
            border-left-color: var(--bs-success);
        }
        .milestone-item.in_progress {
            border-left-color: var(--bs-primary);
        }
        .milestone-item.delayed {
            border-left-color: var(--bs-danger);
        }
        .milestone-item::before {
            content: '';
            position: absolute;
            left: -8px;
            top: 8px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: var(--bs-border-color);
        }
        .milestone-item.completed::before {
            background: var(--bs-success);
        }
        .milestone-item.in_progress::before {
            background: var(--bs-primary);
        }
        .milestone-item.delayed::before {
            background: var(--bs-danger);
        }
        .timeline-item {
            border-left: 2px solid var(--bs-border-color);
            padding-left: 15px;
            margin-bottom: 20px;
            position: relative;
        }
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -6px;
            top: 10px;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: var(--bs-primary);
        }
        .document-item {
            transition: background-color 0.2s ease;
        }
        .document-item:hover {
            background-color: var(--bs-secondary-bg);
        }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <?php include '../../../themes/default/components/admin_navigation.php'; ?>

        <div class="admin-content">
            <div class="content-header">
                <div class="container-fluid">
                    <div class="row mb-3">
                        <div class="col-sm-6">
                            <h1 class="m-0">
                                <?= isset($project) ? htmlspecialchars($project['project_name']) : 'Project Details' ?>
                            </h1>
                        </div>
                        <div class="col-sm-6">
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb float-sm-end">
                                    <?php foreach ($breadcrumbs as $crumb): ?>
                                        <li class="breadcrumb-item <?= $crumb['active'] ?? false ? 'active' : '' ?>">
                                            <?php if ($crumb['active'] ?? false): ?>
                                                <?= htmlspecialchars($crumb['title']) ?>
                                            <?php else: ?>
                                                <a href="<?= htmlspecialchars($crumb['url']) ?>"><?= htmlspecialchars($crumb['title']) ?></a>
                                            <?php endif; ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ol>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>

            <div class="content-body">
                <div class="container-fluid">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            <?= htmlspecialchars($error) ?>
                        </div>
                        <div class="text-center">
                            <a href="/page/user/projects/index.php" class="btn btn-primary">
                                <i class="bi bi-arrow-left me-2"></i>Back to Projects
                            </a>
                        </div>
                    <?php else: ?>
                        <!-- Project Overview -->
                        <div class="row mb-4">
                            <div class="col-lg-8">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start mb-3">
                                            <div>
                                                <h4 class="mb-2"><?= htmlspecialchars($project['project_name']) ?></h4>
                                                <div class="mb-2">
                                                    <span class="badge bg-<?=
                                                        $project['status'] === 'completed' ? 'success' :
                                                        ($project['status'] === 'development' ? 'primary' :
                                                        ($project['status'] === 'testing' ? 'info' :
                                                        ($project['status'] === 'on_hold' ? 'danger' : 'secondary')))
                                                    ?> me-2">
                                                        <?= ucfirst(str_replace('_', ' ', $project['status'])) ?>
                                                    </span>
                                                    <span class="badge bg-secondary">
                                                        <?= ucfirst(str_replace('_', ' ', $project['project_type'])) ?>
                                                    </span>
                                                </div>
                                            </div>

                                            <!-- Progress Circle -->
                                            <div class="text-center">
                                                <div class="progress-circle mx-auto mb-2">
                                                    <svg width="120" height="120" viewBox="0 0 120 120">
                                                        <circle cx="60" cy="60" r="50" fill="none" stroke="var(--bs-border-color)" stroke-width="6"/>
                                                        <circle cx="60" cy="60" r="50" fill="none" stroke="var(--bs-primary)" stroke-width="6"
                                                            stroke-dasharray="<?= $progress['percentage'] * 3.14 ?> 314"
                                                            stroke-dashoffset="0" stroke-linecap="round"
                                                            transform="rotate(-90 60 60)"/>
                                                        <text x="60" y="60" text-anchor="middle" class="fw-bold fs-4">
                                                            <?= $progress['percentage'] ?>%
                                                        </text>
                                                        <text x="60" y="75" text-anchor="middle" class="small text-muted">
                                                            Complete
                                                        </text>
                                                    </svg>
                                                </div>
                                                <div class="small text-muted">
                                                    <?= $progress['status'] ?>
                                                </div>
                                            </div>
                                        </div>

                                        <?php if ($project['description']): ?>
                                            <div class="mb-3">
                                                <h6>Project Description</h6>
                                                <p class="text-muted"><?= nl2br(htmlspecialchars($project['description'])) ?></p>
                                            </div>
                                        <?php endif; ?>

                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <h6><i class="bi bi-calendar-event me-2"></i>Start Date</h6>
                                                    <p class="mb-0"><?= $project['start_date_formatted'] ?></p>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <h6><i class="bi bi-calendar-check me-2"></i>Estimated Completion</h6>
                                                    <p class="mb-0"><?= $project['estimated_completion_formatted'] ?></p>
                                                </div>
                                            </div>
                                        </div>

                                        <?php if ($project['budget']): ?>
                                            <div class="mb-3">
                                                <h6><i class="bi bi-currency-dollar me-2"></i>Project Budget</h6>
                                                <p class="mb-0 fs-5 text-success"><?= $project['budget_formatted'] ?></p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-4">
                                <!-- Progress Stats -->
                                <div class="card">
                                    <div class="card-header">
                                        <h6 class="card-title mb-0"><i class="bi bi-bar-chart me-2"></i>Progress Stats</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <div class="d-flex justify-content-between">
                                                <span>Total Milestones</span>
                                                <span class="fw-bold"><?= $progress['total_milestones'] ?></span>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <div class="d-flex justify-content-between">
                                                <span>Completed</span>
                                                <span class="fw-bold text-success"><?= $progress['completed_milestones'] ?></span>
                                            </div>
                                        </div>
                                        <?php if ($progress['delayed_milestones'] > 0): ?>
                                            <div class="mb-3">
                                                <div class="d-flex justify-content-between">
                                                    <span>Delayed</span>
                                                    <span class="fw-bold text-danger"><?= $progress['delayed_milestones'] ?></span>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tabs Navigation -->
                        <div class="card">
                            <div class="card-header">
                                <ul class="nav nav-tabs card-header-tabs" id="projectTabs" role="tablist">
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link active" id="milestones-tab" data-bs-toggle="tab" data-bs-target="#milestones" type="button" role="tab">
                                            <i class="bi bi-flag me-2"></i>Milestones
                                        </button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="documents-tab" data-bs-toggle="tab" data-bs-target="#documents" type="button" role="tab">
                                            <i class="bi bi-file-earmark me-2"></i>Documents
                                        </button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="timeline-tab" data-bs-toggle="tab" data-bs-target="#timeline" type="button" role="tab">
                                            <i class="bi bi-clock-history me-2"></i>Timeline
                                        </button>
                                    </li>
                                </ul>
                            </div>
                            <div class="card-body">
                                <div class="tab-content" id="projectTabContent">
                                    <!-- Milestones Tab -->
                                    <div class="tab-pane fade show active" id="milestones" role="tabpanel">
                                        <?php if (empty($milestones)): ?>
                                            <div class="text-center py-4">
                                                <i class="bi bi-flag text-muted fs-1 mb-3"></i>
                                                <h6 class="text-muted">No Milestones Yet</h6>
                                                <p class="text-muted">Milestones will appear here as the project progresses.</p>
                                            </div>
                                        <?php else: ?>
                                            <?php foreach ($milestones as $milestone): ?>
                                                <div class="milestone-item <?= $milestone['status'] ?>">
                                                    <div class="d-flex justify-content-between align-items-start">
                                                        <div class="flex-grow-1">
                                                            <h6 class="mb-1"><?= htmlspecialchars($milestone['title']) ?></h6>
                                                            <?php if ($milestone['description']): ?>
                                                                <p class="text-muted mb-2"><?= htmlspecialchars($milestone['description']) ?></p>
                                                            <?php endif; ?>

                                                            <div class="small text-muted">
                                                                <i class="bi bi-calendar-event me-1"></i>
                                                                Due: <?= $milestone['due_date_formatted'] ?>

                                                                <?php if ($milestone['completed_at']): ?>
                                                                    <span class="ms-3">
                                                                        <i class="bi bi-check-circle me-1"></i>
                                                                        Completed: <?= $milestone['completed_at_formatted'] ?>
                                                                    </span>
                                                                <?php elseif (isset($milestone['days_text'])): ?>
                                                                    <span class="ms-3 text-<?=
                                                                        $milestone['days_status'] === 'overdue' ? 'danger' :
                                                                        ($milestone['days_status'] === 'today' ? 'warning' :
                                                                        ($milestone['days_status'] === 'soon' ? 'warning' : 'muted'))
                                                                    ?>">
                                                                        <i class="bi bi-clock me-1"></i>
                                                                        <?= $milestone['days_text'] ?>
                                                                    </span>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>

                                                        <span class="badge bg-<?=
                                                            $milestone['status'] === 'completed' ? 'success' :
                                                            ($milestone['status'] === 'in_progress' ? 'primary' :
                                                            ($milestone['status'] === 'delayed' ? 'danger' : 'secondary'))
                                                        ?>">
                                                            <?= ucfirst(str_replace('_', ' ', $milestone['status'])) ?>
                                                        </span>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Documents Tab -->
                                    <div class="tab-pane fade" id="documents" role="tabpanel">
                                        <?php if (empty($documents)): ?>
                                            <div class="text-center py-4">
                                                <i class="bi bi-file-earmark text-muted fs-1 mb-3"></i>
                                                <h6 class="text-muted">No Documents Available</h6>
                                                <p class="text-muted">Project documents will appear here when available.</p>
                                            </div>
                                        <?php else: ?>
                                            <?php foreach ($documents as $document): ?>
                                                <div class="document-item p-3 rounded mb-2">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <div class="flex-grow-1">
                                                            <h6 class="mb-1">
                                                                <i class="bi bi-file-earmark-<?=
                                                                    $document['document_type'] === 'specification' ? 'text' :
                                                                    ($document['document_type'] === 'design' ? 'image' :
                                                                    ($document['document_type'] === 'contract' ? 'pdf' : 'fill'))
                                                                ?> me-2"></i>
                                                                <?= htmlspecialchars($document['document_name']) ?>
                                                            </h6>
                                                            <div class="small text-muted">
                                                                <span class="badge bg-secondary me-2">
                                                                    <?= ucfirst($document['document_type']) ?>
                                                                </span>
                                                                Size: <?= $document['file_size_formatted'] ?>
                                                                <span class="ms-3">
                                                                    Uploaded: <?= $document['uploaded_at_formatted'] ?>
                                                                </span>
                                                                <?php if ($document['uploaded_by_name']): ?>
                                                                    <span class="ms-3">
                                                                        By: <?= htmlspecialchars($document['uploaded_by_name']) ?>
                                                                    </span>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>

                                                        <a href="/page/api/client/download_document.php?document_id=<?= $document['id'] ?>"
                                                           class="btn btn-outline-primary btn-sm">
                                                            <i class="bi bi-download me-1"></i>Download
                                                        </a>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Timeline Tab -->
                                    <div class="tab-pane fade" id="timeline" role="tabpanel">
                                        <?php if (empty($timeline)): ?>
                                            <div class="text-center py-4">
                                                <i class="bi bi-clock-history text-muted fs-1 mb-3"></i>
                                                <h6 class="text-muted">No Timeline Events</h6>
                                                <p class="text-muted">Project timeline events will appear here.</p>
                                            </div>
                                        <?php else: ?>
                                            <?php foreach ($timeline as $event): ?>
                                                <div class="timeline-item">
                                                    <div class="d-flex justify-content-between align-items-start">
                                                        <div class="flex-grow-1">
                                                            <h6 class="mb-1"><?= htmlspecialchars($event['event_title']) ?></h6>
                                                            <?php if ($event['event_description']): ?>
                                                                <p class="text-muted mb-2"><?= htmlspecialchars($event['event_description']) ?></p>
                                                            <?php endif; ?>
                                                        </div>
                                                        <div class="small text-muted">
                                                            <?= $event['event_date_formatted'] ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
