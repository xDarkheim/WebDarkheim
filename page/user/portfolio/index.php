<?php
/**
 * Portfolio Dashboard
 */

require_once __DIR__ . '/../../../includes/bootstrap.php';

// Use global services from the architecture
global $database_handler, $flashMessageService, $container;

use App\Application\Core\ServiceProvider;

// Get ServiceProvider instance
$serviceProvider = ServiceProvider::getInstance($container);
$authService = $serviceProvider->getAuth();

// Check authentication
if (!$authService->isAuthenticated()) {
    $flashMessageService->addError('Please log in to access your portfolio.');
    header("Location: /page/auth/login.php");
    exit();
}

// Check if user is client or higher
$current_user_role = $authService->getCurrentUserRole();
if (!in_array($current_user_role, ['client', 'employee', 'admin'])) {
    $flashMessageService->addError('Access denied. Client account required.');
    header("Location: /page/user/dashboard.php");
    exit();
}

$pageTitle = 'My Portfolio';
$current_user_id = $authService->getCurrentUserId();

// Get client profile
$stmt = $database_handler->prepare("SELECT * FROM client_profiles WHERE user_id = ?");
$stmt->execute([$current_user_id]);
$profileData = $stmt->fetch(PDO::FETCH_ASSOC);

$projects = [];
if ($profileData) {
    // Get projects for this client profile
    $stmt = $database_handler->prepare("SELECT * FROM client_portfolio WHERE client_profile_id = ? ORDER BY created_at DESC");
    $stmt->execute([$profileData['id']]);
    $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Count projects by status
$stats = [
    'total' => count($projects),
    'draft' => count(array_filter($projects, fn($p) => $p['status'] === 'draft')),
    'pending' => count(array_filter($projects, fn($p) => $p['status'] === 'pending')),
    'published' => count(array_filter($projects, fn($p) => $p['status'] === 'published')),
    'rejected' => count(array_filter($projects, fn($p) => $p['status'] === 'rejected'))
];

include __DIR__ . '/../../../resources/views/_header.php';
?>

<div class="container mt-4">
    <!-- Breadcrumbs -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/page/user/dashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item active">Portfolio</li>
        </ol>
    </nav>

    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">
            <i class="fas fa-briefcase text-primary me-2"></i>
            My Portfolio
        </h1>
        <a href="/page/user/portfolio/add_project.php" class="btn btn-primary">
            <i class="fas fa-plus me-1"></i>
            Add Project
        </a>
    </div>

    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <div class="text-uppercase fw-bold small">Total Projects</div>
                            <div class="h4 mb-0"><?= $stats['total'] ?></div>
                        </div>
                        <div>
                            <i class="fas fa-folder fa-2x opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <div class="text-uppercase fw-bold small">Published</div>
                            <div class="h4 mb-0"><?= $stats['published'] ?></div>
                        </div>
                        <div>
                            <i class="fas fa-check-circle fa-2x opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <div class="text-uppercase fw-bold small">Pending Review</div>
                            <div class="h4 mb-0"><?= $stats['pending'] ?></div>
                        </div>
                        <div>
                            <i class="fas fa-clock fa-2x opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card bg-secondary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <div class="text-uppercase fw-bold small">Drafts</div>
                            <div class="h4 mb-0"><?= $stats['draft'] ?></div>
                        </div>
                        <div>
                            <i class="fas fa-edit fa-2x opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Projects -->
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="fas fa-clock me-2"></i>
                    Recent Projects
                </h5>
                <?php if (count($projects) > 5): ?>
                    <a href="/page/user/portfolio/my_projects.php" class="btn btn-outline-primary btn-sm">
                        View All (<?= count($projects) ?>)
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <div class="card-body">
            <?php if (empty($projects)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-folder-open fa-4x text-muted mb-3"></i>
                    <h5 class="text-muted">No projects yet</h5>
                    <p class="text-muted">Start building your portfolio by adding your first project</p>
                    <a href="/page/user/portfolio/add_project.php" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i>
                        Create First Project
                    </a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Project</th>
                                <th>Status</th>
                                <th>Visibility</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_slice($projects, 0, 5) as $project): ?>
                                <tr>
                                    <td>
                                        <div>
                                            <strong><?= htmlspecialchars($project['title']) ?></strong>
                                            <?php if (!empty($project['description'])): ?>
                                                <br><small class="text-muted"><?= htmlspecialchars(substr($project['description'], 0, 60)) ?>...</small>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php
                                        $statusClasses = [
                                            'draft' => 'secondary',
                                            'pending' => 'warning',
                                            'published' => 'success',
                                            'rejected' => 'danger'
                                        ];
                                        $statusText = [
                                            'draft' => 'Draft',
                                            'pending' => 'Pending',
                                            'published' => 'Published',
                                            'rejected' => 'Rejected'
                                        ];
                                        ?>
                                        <span class="badge bg-<?= $statusClasses[$project['status']] ?>">
                                            <?= $statusText[$project['status']] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= $project['visibility'] === 'public' ? 'info' : 'light text-dark' ?>">
                                            <i class="fas fa-<?= $project['visibility'] === 'public' ? 'globe' : 'lock' ?> me-1"></i>
                                            <?= ucfirst($project['visibility']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <small class="text-muted"><?= date('M j, Y', strtotime($project['created_at'])) ?></small>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="/page/user/portfolio/edit_project.php?id=<?= $project['id'] ?>"
                                               class="btn btn-outline-primary" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <?php if ($project['status'] === 'draft'): ?>
                                                <button class="btn btn-outline-success"
                                                        onclick="submitForModeration(<?= $project['id'] ?>)" title="Submit">
                                                    <i class="fas fa-paper-plane"></i>
                                                </button>
                                            <?php endif; ?>
                                            <button class="btn btn-outline-danger"
                                                    onclick="deleteProject(<?= $project['id'] ?>)" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Profile Status -->
    <?php if (!$profileData): ?>
        <div class="card mt-4">
            <div class="card-body">
                <div class="alert alert-warning mb-0">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Profile Setup Required</strong><br>
                    Complete your client profile to start adding projects to your portfolio.
                    <a href="/page/user/profile/" class="btn btn-warning btn-sm mt-2">
                        <i class="fas fa-user-edit me-1"></i>
                        Complete Profile
                    </a>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
function submitForModeration(projectId) {
    if (confirm('Submit this project for review? You won\'t be able to edit it during moderation.')) {
        fetch('/page/api/portfolio/submit_for_moderation.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `project_id=${projectId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Project submitted for review successfully!');
                location.reload();
            } else {
                alert('Error: ' + (data.error || 'Unknown error occurred'));
            }
        })
        .catch(error => {
            alert('An error occurred. Please try again.');
            console.error('Error:', error);
        });
    }
}

function deleteProject(projectId) {
    if (confirm('Are you sure you want to delete this project? This action cannot be undone.')) {
        fetch('/page/api/portfolio/delete_project.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `project_id=${projectId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Project deleted successfully!');
                location.reload();
            } else {
                alert('Error: ' + (data.error || 'Unknown error occurred'));
            }
        })
        .catch(error => {
            alert('An error occurred. Please try again.');
            console.error('Error:', error);
        });
    }
}
</script>

<?php include __DIR__ . '/../../../resources/views/_footer.php'; ?>
