<?php
/**
 * User Portfolio Dashboard - PHASE 8
 * Modern portfolio management interface with project overview
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
    $flashMessageService->addError('Access denied. Client account required.');
    header("Location: /index.php?page=dashboard");
    exit();
}

$currentUser = $authService->getCurrentUser();
$userId = $authService->getCurrentUserId();
$pageTitle = 'My Portfolio';

// Get client profile
$clientProfile = null;
try {
    $sql = "SELECT * FROM client_profiles WHERE user_id = ?";
    $stmt = $database_handler->getConnection()->prepare($sql);
    $stmt->execute([$userId]);
    $clientProfile = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Error fetching client profile: " . $e->getMessage());
}

// Get portfolio projects
$projects = [];
$stats = ['total' => 0, 'draft' => 0, 'pending' => 0, 'published' => 0, 'rejected' => 0];

if ($clientProfile) {
    try {
        $sql = "SELECT p.*, 
                       (SELECT COUNT(*) FROM project_views pv WHERE pv.project_id = p.id) as view_count,
                       (SELECT COUNT(*) FROM comments c WHERE c.project_id = p.id AND c.status = 'approved') as comment_count
                FROM client_portfolio p 
                WHERE p.client_profile_id = ? 
                ORDER BY p.updated_at DESC";
        $stmt = $database_handler->getConnection()->prepare($sql);
        $stmt->execute([$clientProfile['id']]);
        $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Calculate stats
        $stats['total'] = count($projects);
        foreach ($projects as $project) {
            $stats[$project['status']]++;
        }
    } catch (Exception $e) {
        error_log("Error fetching portfolio projects: " . $e->getMessage());
    }
}

// Get flash messages
$flashMessages = $flashMessageService->getAllMessages();
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
        .portfolio-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
        }
        .stats-card {
            border-radius: 15px;
            border: none;
            transition: all 0.3s ease;
            overflow: hidden;
        }
        .stats-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        .project-card {
            border-radius: 15px;
            border: none;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            overflow: hidden;
        }
        .project-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        .project-image {
            height: 200px;
            background: linear-gradient(45deg, #f8f9fa, #e9ecef);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6c757d;
        }
        .project-status {
            position: absolute;
            top: 10px;
            right: 10px;
            z-index: 1;
        }
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: #6c757d;
        }
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.3;
        }
        .btn-gradient {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
        }
        .btn-gradient:hover {
            background: linear-gradient(135deg, #5a6fd8 0%, #6a4190 100%);
            color: white;
        }
        .filter-tabs {
            background: white;
            border-radius: 15px;
            padding: 1rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body class="bg-light">

<div class="container py-4">
    <!-- Header Section -->
    <div class="portfolio-header">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1 class="mb-2">
                    <i class="fas fa-briefcase"></i>
                    My Portfolio
                </h1>
                <p class="mb-0 opacity-75">Showcase your projects and track their performance</p>
            </div>
            <div class="col-md-4 text-md-end">
                <div class="d-flex gap-2 justify-content-md-end">
                    <a href="/index.php?page=portfolio_create" class="btn btn-light">
                        <i class="fas fa-plus"></i> Add Project
                    </a>
                    <a href="/index.php?page=user_profile" class="btn btn-outline-light">
                        <i class="fas fa-user"></i> Profile
                    </a>
                </div>
            </div>
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

    <!-- Check if client profile exists -->
    <?php if (!$clientProfile): ?>
        <div class="row">
            <div class="col-12">
                <div class="card border-warning">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-user-plus fa-3x text-warning mb-3"></i>
                        <h4>Complete Your Profile First</h4>
                        <p class="text-muted mb-4">
                            Before you can create portfolio projects, you need to complete your client profile.
                        </p>
                        <a href="/index.php?page=profile_edit" class="btn btn-gradient">
                            <i class="fas fa-edit"></i> Complete Profile
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
            <div class="card stats-card bg-primary text-white">
                <div class="card-body text-center">
                    <i class="fas fa-folder fa-2x mb-2"></i>
                    <h3 class="mb-0"><?= $stats['total'] ?></h3>
                    <small>Total Projects</small>
                </div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
            <div class="card stats-card bg-success text-white">
                <div class="card-body text-center">
                    <i class="fas fa-check-circle fa-2x mb-2"></i>
                    <h3 class="mb-0"><?= $stats['published'] ?></h3>
                    <small>Published</small>
                </div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
            <div class="card stats-card bg-warning text-white">
                <div class="card-body text-center">
                    <i class="fas fa-clock fa-2x mb-2"></i>
                    <h3 class="mb-0"><?= $stats['pending'] ?></h3>
                    <small>Pending</small>
                </div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
            <div class="card stats-card bg-secondary text-white">
                <div class="card-body text-center">
                    <i class="fas fa-edit fa-2x mb-2"></i>
                    <h3 class="mb-0"><?= $stats['draft'] ?></h3>
                    <small>Drafts</small>
                </div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
            <div class="card stats-card bg-danger text-white">
                <div class="card-body text-center">
                    <i class="fas fa-times-circle fa-2x mb-2"></i>
                    <h3 class="mb-0"><?= $stats['rejected'] ?></h3>
                    <small>Rejected</small>
                </div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
            <div class="card stats-card bg-info text-white">
                <div class="card-body text-center">
                    <i class="fas fa-eye fa-2x mb-2"></i>
                    <h3 class="mb-0"><?= array_sum(array_column($projects, 'view_count')) ?></h3>
                    <small>Total Views</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Tabs -->
    <div class="filter-tabs">
        <ul class="nav nav-pills" id="projectFilter" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="all-tab" data-bs-toggle="pill" data-bs-target="#all" type="button">
                    <i class="fas fa-th-large"></i> All Projects
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="published-tab" data-bs-toggle="pill" data-bs-target="#published" type="button">
                    <i class="fas fa-check-circle"></i> Published
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="pending-tab" data-bs-toggle="pill" data-bs-target="#pending" type="button">
                    <i class="fas fa-clock"></i> Pending
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="draft-tab" data-bs-toggle="pill" data-bs-target="#draft" type="button">
                    <i class="fas fa-edit"></i> Drafts
                </button>
            </li>
        </ul>
    </div>

    <!-- Projects Content -->
    <div class="tab-content" id="projectFilterContent">
        <div class="tab-pane fade show active" id="all" role="tabpanel">
            <?php if (empty($projects)): ?>
                <div class="empty-state">
                    <i class="fas fa-folder-open"></i>
                    <h4>No Projects Yet</h4>
                    <p>Start building your portfolio by adding your first project.</p>
                    <a href="/index.php?page=portfolio_create" class="btn btn-gradient">
                        <i class="fas fa-plus"></i> Create Your First Project
                    </a>
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($projects as $project): ?>
                        <div class="col-lg-4 col-md-6 mb-4">
                            <div class="card project-card">
                                <div class="position-relative">
                                    <div class="project-image">
                                        <?php if (!empty($project['images'])): ?>
                                            <?php
                                            $images = json_decode($project['images'], true);
                                            if (!empty($images)): ?>
                                                <img src="<?= htmlspecialchars($images[0]) ?>"
                                                     alt="<?= htmlspecialchars($project['title']) ?>"
                                                     class="img-fluid w-100 h-100"
                                                     style="object-fit: cover;">
                                            <?php else: ?>
                                                <i class="fas fa-image fa-3x"></i>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <i class="fas fa-image fa-3x"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div class="project-status">
                                        <?php
                                        $statusClasses = [
                                            'published' => 'bg-success',
                                            'pending' => 'bg-warning',
                                            'draft' => 'bg-secondary',
                                            'rejected' => 'bg-danger'
                                        ];
                                        $statusClass = $statusClasses[$project['status']] ?? 'bg-secondary';
                                        ?>
                                        <span class="badge <?= $statusClass ?>">
                                            <?= ucfirst($project['status']) ?>
                                        </span>
                                    </div>
                                </div>

                                <div class="card-body">
                                    <h5 class="card-title"><?= htmlspecialchars($project['title']) ?></h5>
                                    <p class="card-text text-muted">
                                        <?= htmlspecialchars(substr($project['description'] ?? '', 0, 100)) ?>
                                        <?= strlen($project['description'] ?? '') > 100 ? '...' : '' ?>
                                    </p>

                                    <?php if (!empty($project['technologies'])): ?>
                                        <div class="mb-2">
                                            <?php
                                            $technologies = explode(',', $project['technologies']);
                                            foreach (array_slice($technologies, 0, 3) as $tech): ?>
                                                <span class="badge bg-light text-dark me-1"><?= htmlspecialchars(trim($tech)) ?></span>
                                            <?php endforeach; ?>
                                            <?php if (count($technologies) > 3): ?>
                                                <span class="badge bg-light text-muted">+<?= count($technologies) - 3 ?> more</span>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>

                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="text-muted">
                                            <i class="fas fa-eye"></i> <?= $project['view_count'] ?? 0 ?> views
                                            <i class="fas fa-comments ms-2"></i> <?= $project['comment_count'] ?? 0 ?> comments
                                        </small>
                                        <small class="text-muted">
                                            <?= date('M j, Y', strtotime($project['updated_at'])) ?>
                                        </small>
                                    </div>
                                </div>

                                <div class="card-footer bg-transparent">
                                    <div class="d-flex gap-2">
                                        <a href="/index.php?page=user_portfolio_edit&id=<?= $project['id'] ?>"
                                           class="btn btn-outline-primary btn-sm flex-fill">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <?php if ($project['status'] === 'published'): ?>
                                            <a href="#" class="btn btn-outline-success btn-sm flex-fill">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                        <?php endif; ?>
                                        <button class="btn btn-outline-danger btn-sm"
                                                onclick="confirmDelete(<?= $project['id'] ?>, '<?= htmlspecialchars($project['title']) ?>')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Other tab panes will show filtered content via JavaScript -->
        <div class="tab-pane fade" id="published" role="tabpanel"></div>
        <div class="tab-pane fade" id="pending" role="tabpanel"></div>
        <div class="tab-pane fade" id="draft" role="tabpanel"></div>
    </div>

    <?php endif; ?>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-triangle text-danger"></i>
                    Delete Project
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete "<span id="deleteProjectTitle"></span>"?</p>
                <div class="alert alert-warning">
                    <strong>Warning:</strong> This action cannot be undone.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" onclick="deleteProject()">
                    <i class="fas fa-trash-alt"></i> Delete Project
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
let projectToDelete = null;

function confirmDelete(projectId, projectTitle) {
    projectToDelete = projectId;
    document.getElementById('deleteProjectTitle').textContent = projectTitle;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}

function deleteProject() {
    if (projectToDelete) {
        fetch('/index.php?page=api_portfolio_delete', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ id: projectToDelete })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error deleting project: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error deleting project');
        });

        bootstrap.Modal.getInstance(document.getElementById('deleteModal')).hide();
    }
}

// Filter functionality
document.addEventListener('DOMContentLoaded', function() {
    const allProjects = document.querySelectorAll('.project-card');
    const tabs = document.querySelectorAll('[data-bs-toggle="pill"]');

    tabs.forEach(tab => {
        tab.addEventListener('shown.bs.tab', function(e) {
            const target = e.target.getAttribute('data-bs-target');
            const status = target.replace('#', '');

            if (status === 'all') {
                // Show all projects in the main tab
                return;
            }

            // Filter and show projects in other tabs
            const targetPane = document.querySelector(target);
            const filteredProjects = Array.from(allProjects).filter(card => {
                const statusBadge = card.querySelector('.project-status .badge');
                return statusBadge && statusBadge.textContent.toLowerCase().trim() === status;
            });

            if (filteredProjects.length === 0) {
                targetPane.innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-folder-open"></i>
                        <h4>No ${status.charAt(0).toUpperCase() + status.slice(1)} Projects</h4>
                        <p>You don't have any ${status} projects yet.</p>
                    </div>
                `;
            } else {
                const row = document.createElement('div');
                row.className = 'row';
                filteredProjects.forEach(project => {
                    const col = document.createElement('div');
                    col.className = 'col-lg-4 col-md-6 mb-4';
                    col.appendChild(project.cloneNode(true));
                    row.appendChild(col);
                });
                targetPane.innerHTML = '';
                targetPane.appendChild(row);
            }
        });
    });

    // Auto-dismiss alerts
    setTimeout(function() {
        var alerts = document.querySelectorAll('.alert');
        alerts.forEach(function(alert) {
            var bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);
});
</script>

</body>
</html>

