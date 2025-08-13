<?php
/**
 * Moderation Dashboard View
 * Central admin panel for moderation overview
 */

// Extract data from view context
$statistics = $viewData['statistics'] ?? [];
$recentProjects = $viewData['recent_projects'] ?? [];
$recentComments = $viewData['recent_comments'] ?? [];
$notifications = $viewData['notifications'] ?? [];
$flashMessages = $viewData['flashMessages'] ?? [];
$currentUser = $viewData['currentUser'] ?? null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($viewData['pageTitle'] ?? 'Moderation Dashboard') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .dashboard-card {
            transition: all 0.3s ease;
            border: none;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .dashboard-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .notification-badge {
            position: relative;
            top: -2px;
        }
        .quick-action-btn {
            width: 100%;
            margin-bottom: 10px;
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
                    <i class="fas fa-tachometer-alt text-primary"></i>
                    Moderation Dashboard
                </h1>
                <div class="btn-group">
                    <a href="/index.php?page=admin_moderation_projects" class="btn btn-outline-primary">
                        <i class="fas fa-tasks"></i> Projects
                        <?php if (($statistics['pending_projects'] ?? 0) > 0): ?>
                            <span class="badge bg-danger notification-badge"><?= $statistics['pending_projects'] ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="/index.php?page=admin_moderation_comments" class="btn btn-outline-secondary">
                        <i class="fas fa-comments"></i> Comments
                        <?php if (($statistics['pending_comments'] ?? 0) > 0): ?>
                            <span class="badge bg-danger notification-badge"><?= $statistics['pending_comments'] ?></span>
                        <?php endif; ?>
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

    <!-- Notifications -->
    <?php if (!empty($notifications)): ?>
        <div class="row mb-4">
            <div class="col-12">
                <div class="alert alert-info">
                    <h5 class="alert-heading"><i class="fas fa-bell"></i> Notifications</h5>
                    <?php foreach ($notifications as $notification): ?>
                        <div class="mb-2">
                            <strong><?= htmlspecialchars($notification['message']) ?></strong>
                            <a href="<?= htmlspecialchars($notification['url']) ?>" class="btn btn-sm btn-outline-primary ms-2">
                                View <i class="fas fa-external-link-alt"></i>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card dashboard-card stat-card">
                <div class="card-body text-center">
                    <i class="fas fa-clock fa-2x mb-2"></i>
                    <h3 class="mb-0"><?= $statistics['pending_projects'] ?? 0 ?></h3>
                    <p class="mb-0">Pending Projects</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card dashboard-card bg-success text-white">
                <div class="card-body text-center">
                    <i class="fas fa-check-circle fa-2x mb-2"></i>
                    <h3 class="mb-0"><?= $statistics['total_published'] ?? 0 ?></h3>
                    <p class="mb-0">Published Projects</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card dashboard-card bg-warning text-white">
                <div class="card-body text-center">
                    <i class="fas fa-comments fa-2x mb-2"></i>
                    <h3 class="mb-0"><?= $statistics['pending_comments'] ?? 0 ?></h3>
                    <p class="mb-0">Pending Comments</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card dashboard-card bg-info text-white">
                <div class="card-body text-center">
                    <i class="fas fa-calendar-day fa-2x mb-2"></i>
                    <h3 class="mb-0"><?= $statistics['moderated_today'] ?? 0 ?></h3>
                    <p class="mb-0">Moderated Today</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Projects Status Chart -->
        <div class="col-lg-6 mb-4">
            <div class="card dashboard-card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-chart-pie"></i> Projects by Status
                    </h5>
                </div>
                <div class="card-body">
                    <canvas id="projectsChart" height="200"></canvas>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="col-lg-6 mb-4">
            <div class="card dashboard-card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-bolt"></i> Quick Actions
                    </h5>
                </div>
                <div class="card-body">
                    <a href="/index.php?page=admin_moderation_projects&status=pending"
                       class="btn btn-primary quick-action-btn">
                        <i class="fas fa-tasks"></i> Review Pending Projects
                        <?php if (($statistics['pending_projects'] ?? 0) > 0): ?>
                            <span class="badge bg-light text-dark ms-2"><?= $statistics['pending_projects'] ?></span>
                        <?php endif; ?>
                    </a>

                    <a href="/index.php?page=admin_moderation_comments&status=pending"
                       class="btn btn-warning quick-action-btn">
                        <i class="fas fa-comments"></i> Review Pending Comments
                        <?php if (($statistics['pending_comments'] ?? 0) > 0): ?>
                            <span class="badge bg-light text-dark ms-2"><?= $statistics['pending_comments'] ?></span>
                        <?php endif; ?>
                    </a>

                    <a href="/index.php?page=admin_moderation_projects"
                       class="btn btn-info quick-action-btn">
                        <i class="fas fa-list"></i> All Projects
                    </a>

                    <a href="/index.php?page=admin_site_settings"
                       class="btn btn-secondary quick-action-btn">
                        <i class="fas fa-cog"></i> Site Settings
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Recent Projects -->
        <div class="col-lg-6 mb-4">
            <div class="card dashboard-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-project-diagram"></i> Recent Projects
                    </h5>
                    <a href="/index.php?page=admin_moderation_projects" class="btn btn-sm btn-outline-primary">
                        View All <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
                <div class="card-body">
                    <?php if (empty($recentProjects)): ?>
                        <p class="text-muted text-center py-3">
                            <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                            No recent projects
                        </p>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($recentProjects as $project): ?>
                                <div class="list-group-item border-0 px-0">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1"><?= htmlspecialchars($project['title']) ?></h6>
                                            <p class="mb-1 text-muted small">
                                                By <?= htmlspecialchars($project['client_username'] ?? 'Unknown') ?>
                                            </p>
                                            <small class="text-muted">
                                                <?= date('M j, Y g:i A', strtotime($project['created_at'])) ?>
                                            </small>
                                        </div>
                                        <div class="text-end">
                                            <span class="badge bg-warning"><?= ucfirst($project['status']) ?></span>
                                            <br>
                                            <a href="/index.php?page=admin_moderation_project_details&id=<?= $project['id'] ?>"
                                               class="btn btn-sm btn-outline-primary mt-1">
                                                View
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Recent Comments -->
        <div class="col-lg-6 mb-4">
            <div class="card dashboard-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-comments"></i> Recent Comments
                    </h5>
                    <a href="/index.php?page=admin_moderation_comments" class="btn btn-sm btn-outline-primary">
                        View All <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
                <div class="card-body">
                    <?php if (empty($recentComments)): ?>
                        <p class="text-muted text-center py-3">
                            <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                            No recent comments
                        </p>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($recentComments as $comment): ?>
                                <div class="list-group-item border-0 px-0">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1"><?= htmlspecialchars($comment['author_name']) ?></h6>
                                            <p class="mb-1 text-truncate" style="max-width: 300px;">
                                                <?= htmlspecialchars(substr($comment['content'], 0, 80)) ?>
                                                <?= strlen($comment['content']) > 80 ? '...' : '' ?>
                                            </p>
                                            <small class="text-muted">
                                                <?= date('M j, Y g:i A', strtotime($comment['created_at'])) ?>
                                            </small>
                                        </div>
                                        <div class="text-end">
                                            <span class="badge bg-warning"><?= ucfirst($comment['status']) ?></span>
                                            <br>
                                            <button class="btn btn-sm btn-outline-success mt-1"
                                                    onclick="moderateComment(<?= $comment['id'] ?>, 'approved')">
                                                <i class="fas fa-check"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger mt-1"
                                                    onclick="moderateComment(<?= $comment['id'] ?>, 'rejected')">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Projects Status Chart
<?php if (!empty($statistics['projects_by_status'])): ?>
const ctx = document.getElementById('projectsChart').getContext('2d');
const projectsChart = new Chart(ctx, {
    type: 'doughnut',
    data: {
        labels: <?= json_encode(array_keys($statistics['projects_by_status'])) ?>,
        datasets: [{
            data: <?= json_encode(array_values($statistics['projects_by_status'])) ?>,
            backgroundColor: [
                '#ffc107', // pending - warning
                '#28a745', // published - success
                '#dc3545', // rejected - danger
                '#6c757d'  // other - secondary
            ],
            borderWidth: 2,
            borderColor: '#fff'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});
<?php endif; ?>

// Comment moderation functions
function moderateComment(commentId, action) {
    if (confirm(`Are you sure you want to ${action === 'approved' ? 'approve' : 'reject'} this comment?`)) {
        fetch('/page/api/moderation/moderate_comment.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                comment_id: commentId,
                action: action
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
