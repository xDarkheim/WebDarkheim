<?php
/**
 * Projects List View for Moderation
 * Displays list of projects awaiting moderation
 */

// Extract data from view context
$projects = $viewData['projects'] ?? [];
$pagination = $viewData['pagination'] ?? [];
$statistics = $viewData['statistics'] ?? [];
$filters = $viewData['filters'] ?? [];
$flashMessages = $viewData['flashMessages'] ?? [];
$currentUser = $viewData['currentUser'] ?? null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($viewData['pageTitle'] ?? 'Projects Moderation') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .status-badge {
            font-size: 0.8rem;
            padding: 0.25rem 0.5rem;
        }
        .project-card {
            transition: all 0.3s ease;
            border: 1px solid #dee2e6;
        }
        .project-card:hover {
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .action-buttons .btn {
            margin: 0 2px;
        }
    </style>
</head>
<body class="bg-light">

<div class="container-fluid py-4">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="h3 mb-0">
                    <i class="fas fa-tasks text-primary"></i>
                    Projects Moderation
                </h1>
                <div class="btn-group">
                    <a href="/index.php?page=admin_moderation_dashboard" class="btn btn-outline-primary">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                    <a href="/index.php?page=admin_moderation_comments" class="btn btn-outline-secondary">
                        <i class="fas fa-comments"></i> Comments
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

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card stats-card">
                <div class="card-body text-center">
                    <h3 class="mb-0"><?= $statistics['pending_projects'] ?? 0 ?></h3>
                    <p class="mb-0">Pending Projects</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <h3 class="mb-0"><?= $statistics['projects']['published'] ?? 0 ?></h3>
                    <p class="mb-0">Published</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-danger text-white">
                <div class="card-body text-center">
                    <h3 class="mb-0"><?= $statistics['projects']['rejected'] ?? 0 ?></h3>
                    <p class="mb-0">Rejected</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body text-center">
                    <h3 class="mb-0"><?= $statistics['recent_projects_week'] ?? 0 ?></h3>
                    <p class="mb-0">This Week</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <input type="hidden" name="page" value="admin_moderation_projects">

                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="all" <?= ($filters['status'] ?? '') === 'all' ? 'selected' : '' ?>>All Statuses</option>
                        <option value="pending" <?= ($filters['status'] ?? '') === 'pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="published" <?= ($filters['status'] ?? '') === 'published' ? 'selected' : '' ?>>Published</option>
                        <option value="rejected" <?= ($filters['status'] ?? '') === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Sort By</label>
                    <select name="sort" class="form-select">
                        <option value="created_desc" <?= ($filters['sort'] ?? '') === 'created_desc' ? 'selected' : '' ?>>Newest First</option>
                        <option value="created_asc" <?= ($filters['sort'] ?? '') === 'created_asc' ? 'selected' : '' ?>>Oldest First</option>
                        <option value="title_asc" <?= ($filters['sort'] ?? '') === 'title_asc' ? 'selected' : '' ?>>Title A-Z</option>
                        <option value="title_desc" <?= ($filters['sort'] ?? '') === 'title_desc' ? 'selected' : '' ?>>Title Z-A</option>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" class="form-control" placeholder="Search projects..."
                           value="<?= htmlspecialchars($filters['search'] ?? '') ?>">
                </div>

                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary d-block w-100">
                        <i class="fas fa-search"></i> Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Projects List -->
    <div class="row">
        <?php if (empty($projects)): ?>
            <div class="col-12">
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                        <h4 class="text-muted">No Projects Found</h4>
                        <p class="text-muted">No projects match your current filters.</p>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($projects as $project): ?>
                <div class="col-lg-6 col-xl-4 mb-4">
                    <div class="card project-card h-100">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <strong class="text-truncate" style="max-width: 200px;">
                                <?= htmlspecialchars($project['title']) ?>
                            </strong>
                            <span class="badge status-badge bg-<?=
                                match($project['status']) {
                                    'pending' => 'warning',
                                    'published' => 'success',
                                    'rejected' => 'danger',
                                    default => 'secondary'
                                }
                            ?>">
                                <?= ucfirst($project['status']) ?>
                            </span>
                        </div>
                        <div class="card-body">
                            <p class="text-muted small mb-2">
                                <i class="fas fa-user"></i>
                                <?= htmlspecialchars($project['client_username'] ?? 'Unknown') ?>
                                <?php if (!empty($project['company_name'])): ?>
                                    <br><i class="fas fa-building"></i>
                                    <?= htmlspecialchars($project['company_name']) ?>
                                <?php endif; ?>
                            </p>

                            <p class="card-text text-truncate" style="max-height: 60px; overflow: hidden;">
                                <?= htmlspecialchars(substr($project['description'] ?? '', 0, 120)) ?>
                                <?= strlen($project['description'] ?? '') > 120 ? '...' : '' ?>
                            </p>

                            <div class="text-muted small mb-3">
                                <i class="fas fa-calendar"></i>
                                <?= date('M j, Y', strtotime($project['created_at'])) ?>
                            </div>

                            <?php if (!empty($project['technologies'])): ?>
                                <?php $techs = json_decode($project['technologies'], true) ?? []; ?>
                                <div class="mb-3">
                                    <?php foreach (array_slice($techs, 0, 3) as $tech): ?>
                                        <span class="badge bg-light text-dark me-1"><?= htmlspecialchars($tech) ?></span>
                                    <?php endforeach; ?>
                                    <?php if (count($techs) > 3): ?>
                                        <span class="badge bg-secondary">+<?= count($techs) - 3 ?> more</span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="card-footer">
                            <div class="action-buttons d-flex justify-content-between">
                                <a href="/index.php?page=admin_moderation_project_details&id=<?= $project['id'] ?>"
                                   class="btn btn-primary btn-sm">
                                    <i class="fas fa-eye"></i> View Details
                                </a>

                                <?php if ($project['status'] === 'pending'): ?>
                                    <div>
                                        <button class="btn btn-success btn-sm"
                                                onclick="moderateProject(<?= $project['id'] ?>, 'published')">
                                            <i class="fas fa-check"></i> Approve
                                        </button>
                                        <button class="btn btn-danger btn-sm"
                                                onclick="moderateProject(<?= $project['id'] ?>, 'rejected')">
                                            <i class="fas fa-times"></i> Reject
                                        </button>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Pagination -->
    <?php if ($pagination['total_pages'] > 1): ?>
        <div class="row">
            <div class="col-12">
                <nav aria-label="Projects pagination">
                    <ul class="pagination justify-content-center">
                        <?php
                        $currentPage = $pagination['current_page'];
                        $totalPages = $pagination['total_pages'];
                        $baseUrl = '/index.php?page=admin_moderation_projects';
                        $queryParams = array_filter([
                            'status' => $filters['status'] ?? null,
                            'search' => $filters['search'] ?? null,
                            'sort' => $filters['sort'] ?? null
                        ]);
                        ?>

                        <!-- Previous Page -->
                        <?php if ($currentPage > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="<?= $baseUrl ?>&<?= http_build_query(array_merge($queryParams, ['page' => $currentPage - 1])) ?>">
                                    <i class="fas fa-chevron-left"></i> Previous
                                </a>
                            </li>
                        <?php endif; ?>

                        <!-- Page Numbers -->
                        <?php for ($i = max(1, $currentPage - 2); $i <= min($totalPages, $currentPage + 2); $i++): ?>
                            <li class="page-item <?= $i === $currentPage ? 'active' : '' ?>">
                                <a class="page-link" href="<?= $baseUrl ?>&<?= http_build_query(array_merge($queryParams, ['page' => $i])) ?>">
                                    <?= $i ?>
                                </a>
                            </li>
                        <?php endfor; ?>

                        <!-- Next Page -->
                        <?php if ($currentPage < $totalPages): ?>
                            <li class="page-item">
                                <a class="page-link" href="<?= $baseUrl ?>&<?= http_build_query(array_merge($queryParams, ['page' => $currentPage + 1])) ?>">
                                    Next <i class="fas fa-chevron-right"></i>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>

                <div class="text-center text-muted">
                    Showing projects <?= ($currentPage - 1) * 20 + 1 ?> to <?= min($currentPage * 20, $pagination['total']) ?>
                    of <?= $pagination['total'] ?> total
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function moderateProject(projectId, action) {
    if (confirm(`Are you sure you want to ${action === 'published' ? 'approve' : 'reject'} this project?`)) {
        fetch('/page/api/moderation/moderate_project.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                project_id: projectId,
                action: action,
                notes: action === 'rejected' ? prompt('Reason for rejection (optional):') : ''
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Network error occurred');
        });
    }
}
</script>

</body>
</html>
