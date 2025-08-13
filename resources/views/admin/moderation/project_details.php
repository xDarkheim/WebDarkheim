<?php
/**
 * Project Details View for Moderation
 * Detailed view and moderation interface for a specific client project
 */

// Extract data from view context
$project = $viewData['project'] ?? null;
$comments = $viewData['comments'] ?? [];
$history = $viewData['history'] ?? [];
$flashMessages = $viewData['flashMessages'] ?? [];
$currentUser = $viewData['currentUser'] ?? null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($viewData['pageTitle'] ?? 'Project Details') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .status-badge {
            font-size: 0.9rem;
            padding: 0.5rem 1rem;
        }
        .project-image {
            max-width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 8px;
        }
        .moderation-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .tech-badge {
            background: #f8f9fa;
            color: #495057;
            font-size: 0.8rem;
        }
    </style>
</head>
<body class="bg-light">

<?php if (!$project): ?>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body text-center">
                        <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                        <h4>Project Not Found</h4>
                        <p class="text-muted">The requested project could not be found or you don't have permission to view it.</p>
                        <a href="/index.php?page=admin_moderation_projects" class="btn btn-primary">
                            <i class="fas fa-arrow-left"></i> Back to Projects
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php else: ?>
<div class="container-fluid py-4">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <a href="/index.php?page=admin_moderation_projects" class="btn btn-outline-secondary btn-sm mb-2">
                        <i class="fas fa-arrow-left"></i> Back to Projects
                    </a>
                    <h1 class="h3 mb-0">
                        <i class="fas fa-eye text-primary"></i>
                        Project Details
                    </h1>
                </div>
                <div class="btn-group">
                    <a href="/index.php?page=admin_moderation_dashboard" class="btn btn-outline-primary">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
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

    <div class="row">
        <!-- Main Project Info -->
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><?= htmlspecialchars($project['title']) ?></h5>
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
                    <!-- Client Info -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h6><i class="fas fa-user"></i> Client Information</h6>
                            <p class="mb-1"><strong>Username:</strong> <?= htmlspecialchars($project['client_username'] ?? 'Unknown') ?></p>
                            <p class="mb-1"><strong>Email:</strong> <?= htmlspecialchars($project['client_email'] ?? 'Unknown') ?></p>
                            <?php if (!empty($project['company_name'])): ?>
                                <p class="mb-1"><strong>Company:</strong> <?= htmlspecialchars($project['company_name']) ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <h6><i class="fas fa-calendar"></i> Project Timeline</h6>
                            <p class="mb-1"><strong>Created:</strong> <?= date('M j, Y g:i A', strtotime($project['created_at'])) ?></p>
                            <p class="mb-1"><strong>Updated:</strong> <?= date('M j, Y g:i A', strtotime($project['updated_at'])) ?></p>
                            <?php if (!empty($project['moderated_at'])): ?>
                                <p class="mb-1"><strong>Moderated:</strong> <?= date('M j, Y g:i A', strtotime($project['moderated_at'])) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Project Description -->
                    <div class="mb-4">
                        <h6><i class="fas fa-align-left"></i> Description</h6>
                        <p><?= nl2br(htmlspecialchars($project['description'])) ?></p>
                    </div>

                    <!-- Technologies -->
                    <?php if (!empty($project['technologies'])): ?>
                        <div class="mb-4">
                            <h6><i class="fas fa-tools"></i> Technologies Used</h6>
                            <div>
                                <?php foreach ($project['technologies'] as $tech): ?>
                                    <span class="badge tech-badge me-2 mb-2"><?= htmlspecialchars($tech) ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Links -->
                    <div class="mb-4">
                        <h6><i class="fas fa-link"></i> Project Links</h6>
                        <?php if (!empty($project['live_url'])): ?>
                            <p class="mb-1">
                                <strong>Live URL:</strong>
                                <a href="<?= htmlspecialchars($project['live_url']) ?>" target="_blank" class="text-decoration-none">
                                    <?= htmlspecialchars($project['live_url']) ?>
                                    <i class="fas fa-external-link-alt"></i>
                                </a>
                            </p>
                        <?php endif; ?>
                        <?php if (!empty($project['github_url'])): ?>
                            <p class="mb-1">
                                <strong>GitHub:</strong>
                                <a href="<?= htmlspecialchars($project['github_url']) ?>" target="_blank" class="text-decoration-none">
                                    <?= htmlspecialchars($project['github_url']) ?>
                                    <i class="fas fa-external-link-alt"></i>
                                </a>
                            </p>
                        <?php endif; ?>
                    </div>

                    <!-- Images -->
                    <?php if (!empty($project['images'])): ?>
                        <div class="mb-4">
                            <h6><i class="fas fa-images"></i> Project Images</h6>
                            <div class="row">
                                <?php foreach ($project['images'] as $image): ?>
                                    <div class="col-md-4 mb-3">
                                        <img src="<?= htmlspecialchars($image) ?>" alt="Project Image" class="project-image">
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Moderation Notes -->
                    <?php if (!empty($project['moderation_notes'])): ?>
                        <div class="mb-4">
                            <h6><i class="fas fa-sticky-note"></i> Moderation Notes</h6>
                            <div class="alert alert-info">
                                <?= nl2br(htmlspecialchars($project['moderation_notes'])) ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Comments Section -->
            <?php if (!empty($comments)): ?>
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-comments"></i> Comments (<?= count($comments) ?>)</h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($comments as $comment): ?>
                            <div class="border-bottom pb-3 mb-3">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1">
                                            <?= htmlspecialchars($comment['author_name']) ?>
                                            <span class="badge bg-<?=
                                                match($comment['status']) {
                                                    'pending' => 'warning',
                                                    'approved' => 'success',
                                                    'rejected' => 'danger',
                                                    default => 'secondary'
                                                }
                                            ?> ms-2">
                                                <?= ucfirst($comment['status']) ?>
                                            </span>
                                        </h6>
                                        <p class="mb-2"><?= nl2br(htmlspecialchars($comment['content'])) ?></p>
                                        <small class="text-muted">
                                            <?= date('M j, Y g:i A', strtotime($comment['created_at'])) ?>
                                        </small>
                                    </div>
                                    <?php if ($comment['status'] === 'pending'): ?>
                                        <div class="btn-group">
                                            <button class="btn btn-success btn-sm" onclick="moderateComment(<?= $comment['id'] ?>, 'approved')">
                                                <i class="fas fa-check"></i>
                                            </button>
                                            <button class="btn btn-danger btn-sm" onclick="moderateComment(<?= $comment['id'] ?>, 'rejected')">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Moderation Sidebar -->
        <div class="col-lg-4">
            <!-- Moderation Actions -->
            <?php if ($project['status'] === 'pending'): ?>
                <div class="card moderation-card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0 text-white"><i class="fas fa-gavel"></i> Moderation Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <button class="btn btn-success btn-lg" onclick="moderateProject(<?= $project['id'] ?>, 'published')">
                                <i class="fas fa-check"></i> Approve Project
                            </button>
                            <button class="btn btn-danger btn-lg" onclick="moderateProject(<?= $project['id'] ?>, 'rejected')">
                                <i class="fas fa-times"></i> Reject Project
                            </button>
                        </div>
                        <hr class="border-light">
                        <small class="text-light">
                            <i class="fas fa-info-circle"></i>
                            Approving will make this project visible in the public portfolio.
                            Rejecting will hide it and notify the client.
                        </small>
                    </div>
                </div>
            <?php else: ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-info-circle"></i> Project Status</h5>
                    </div>
                    <div class="card-body">
                        <p class="mb-2">
                            <strong>Current Status:</strong>
                            <span class="badge bg-<?=
                                match($project['status']) {
                                    'published' => 'success',
                                    'rejected' => 'danger',
                                    default => 'secondary'
                                }
                            ?>">
                                <?= ucfirst($project['status']) ?>
                            </span>
                        </p>
                        <?php if (!empty($project['moderated_by_username'])): ?>
                            <p class="mb-2">
                                <strong>Moderated by:</strong> <?= htmlspecialchars($project['moderated_by_username']) ?>
                            </p>
                        <?php endif; ?>
                        <?php if (!empty($project['moderated_at'])): ?>
                            <p class="mb-0">
                                <strong>Moderated on:</strong> <?= date('M j, Y g:i A', strtotime($project['moderated_at'])) ?>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Project Stats -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-chart-bar"></i> Project Statistics</h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6">
                            <h4 class="text-primary"><?= $project['views'] ?? 0 ?></h4>
                            <small class="text-muted">Views</small>
                        </div>
                        <div class="col-6">
                            <h4 class="text-info"><?= count($comments) ?></h4>
                            <small class="text-muted">Comments</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-bolt"></i> Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="/index.php?page=admin_moderation_projects" class="btn btn-outline-primary">
                            <i class="fas fa-list"></i> All Projects
                        </a>
                        <a href="/index.php?page=admin_moderation_comments&type=portfolio_project" class="btn btn-outline-secondary">
                            <i class="fas fa-comments"></i> Portfolio Comments
                        </a>
                        <?php if (!empty($project['client_id'])): ?>
                            <a href="/index.php?page=admin_moderation_projects&client=<?= $project['client_id'] ?>" class="btn btn-outline-info">
                                <i class="fas fa-user"></i> Client's Projects
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function moderateProject(projectId, action) {
    const notes = action === 'rejected' ? prompt('Please provide a reason for rejection:') : '';

    if (action === 'rejected' && !notes) {
        alert('Please provide a reason for rejection.');
        return;
    }

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
                notes: notes
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
