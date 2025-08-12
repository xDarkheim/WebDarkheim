<?php
/**
 * My Portfolio Projects
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
    header("Location: /index.php?page=login");
    exit();
}

// Check if user is client or higher
$current_user_role = $authService->getCurrentUserRole();
if (!in_array($current_user_role, ['client', 'employee', 'admin'])) {
    $flashMessageService->addError('Access denied. Client account required.');
    header("Location: /index.php?page=dashboard");
    exit();
}

$pageTitle = 'My Projects';
$current_user_id = $authService->getCurrentUserId();

// Get client profile
$stmt = $database_handler->prepare("SELECT * FROM client_profiles WHERE user_id = ?");
$stmt->execute([$current_user_id]);
$profileData = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$profileData) {
    $flashMessageService->addError('Please complete your profile first.');
    header('Location: /index.php?page=profile_edit');
    exit();
}

// Get filter parameter
$filter = $_GET['filter'] ?? 'all';
$validFilters = ['all', 'draft', 'pending', 'published', 'rejected'];
if (!in_array($filter, $validFilters)) {
    $filter = 'all';
}

// Get all projects for this client profile
$stmt = $database_handler->prepare("SELECT * FROM client_portfolio WHERE client_profile_id = ? ORDER BY created_at DESC");
$stmt->execute([$profileData['id']]);
$allProjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Filter projects based on selected filter
$projects = [];
if ($filter === 'all') {
    $projects = $allProjects;
} else {
    $projects = array_filter($allProjects, fn($project) => $project['status'] === $filter);
}

// Pagination
$projectsPerPage = 12;
$totalProjects = count($projects);
$totalPages = max(1, ceil($totalProjects / $projectsPerPage));
$currentPage = max(1, min($totalPages, (int)($_GET['page'] ?? 1)));
$offset = ($currentPage - 1) * $projectsPerPage;
$paginatedProjects = array_slice($projects, $offset, $projectsPerPage);

// Count projects by status for filter badges
$statusCounts = [
    'all' => count($allProjects),
    'draft' => count(array_filter($allProjects, fn($p) => $p['status'] === 'draft')),
    'pending' => count(array_filter($allProjects, fn($p) => $p['status'] === 'pending')),
    'published' => count(array_filter($allProjects, fn($p) => $p['status'] === 'published')),
    'rejected' => count(array_filter($allProjects, fn($p) => $p['status'] === 'rejected')),
];
?>

<div class="container mt-4">
    <!-- Breadcrumbs -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/index.php?page=dashboard">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="/index.php?page=client_portfolio">Portfolio</a></li>
            <li class="breadcrumb-item active">My Projects</li>
        </ol>
    </nav>

    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">
            <i class="fas fa-folder text-primary me-2"></i>
            My Projects
        </h1>
        <a href="/index.php?page=portfolio_create" class="btn btn-primary">
            <i class="fas fa-plus me-1"></i>
            Add New Project
        </a>
    </div>

    <!-- Filter Tabs -->
    <div class="card mb-4">
        <div class="card-header">
            <ul class="nav nav-tabs card-header-tabs">
                <li class="nav-item">
                    <a class="nav-link <?= $filter === 'all' ? 'active' : '' ?>"
                       href="/index.php?page=portfolio_manage&filter=all">
                        All Projects
                        <span class="badge bg-secondary ms-1"><?= $statusCounts['all'] ?></span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $filter === 'draft' ? 'active' : '' ?>"
                       href="/index.php?page=portfolio_manage&filter=draft">
                        Drafts
                        <span class="badge bg-secondary ms-1"><?= $statusCounts['draft'] ?></span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $filter === 'pending' ? 'active' : '' ?>"
                       href="/index.php?page=portfolio_manage&filter=pending">
                        Pending Review
                        <span class="badge bg-warning ms-1"><?= $statusCounts['pending'] ?></span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $filter === 'published' ? 'active' : '' ?>"
                       href="/index.php?page=portfolio_manage&filter=published">
                        Published
                        <span class="badge bg-success ms-1"><?= $statusCounts['published'] ?></span>
                    </a>
                </li>
                <?php if ($statusCounts['rejected'] > 0): ?>
                <li class="nav-item">
                    <a class="nav-link <?= $filter === 'rejected' ? 'active' : '' ?>"
                       href="/index.php?page=portfolio_manage&filter=rejected">
                        Rejected
                        <span class="badge bg-danger ms-1"><?= $statusCounts['rejected'] ?></span>
                    </a>
                </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>

    <?php if (empty($paginatedProjects)): ?>
        <!-- Empty State -->
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="fas fa-folder-open fa-4x text-muted mb-3"></i>
                <h5 class="text-muted">
                    <?php if ($filter === 'all'): ?>
                        No projects yet
                    <?php else: ?>
                        No <?= $filter ?> projects
                    <?php endif; ?>
                </h5>
                <p class="text-muted">
                    <?php if ($filter === 'all'): ?>
                        Start building your portfolio by adding your first project
                    <?php else: ?>
                        Switch to "All Projects" to see your complete portfolio
                    <?php endif; ?>
                </p>
                <a href="/index.php?page=portfolio_create" class="btn btn-primary">
                    <i class="fas fa-plus me-1"></i>
                    Add Your First Project
                </a>
            </div>
        </div>
    <?php else: ?>
        <!-- Projects Grid -->
        <div class="row">
            <?php foreach ($paginatedProjects as $project): ?>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card h-100">
                        <!-- Project Image -->
                        <?php
                        $images = json_decode($project['images'] ?? '[]', true);
                        $firstImage = !empty($images) ? $images[0] : null;
                        ?>

                        <?php if ($firstImage): ?>
                            <img src="/storage/uploads/portfolio/<?= htmlspecialchars($firstImage) ?>"
                                 class="card-img-top" style="height: 200px; object-fit: cover;"
                                 alt="<?= htmlspecialchars($project['title']) ?>">
                        <?php else: ?>
                            <div class="card-img-top bg-light d-flex align-items-center justify-content-center"
                                 style="height: 200px;">
                                <i class="fas fa-image fa-3x text-muted"></i>
                            </div>
                        <?php endif; ?>

                        <div class="card-body d-flex flex-column">
                            <!-- Project Title & Status -->
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h5 class="card-title mb-0"><?= htmlspecialchars($project['title']) ?></h5>
                                <span class="badge bg-<?=
                                    $project['status'] === 'published' ? 'success' :
                                    ($project['status'] === 'pending' ? 'warning' :
                                    ($project['status'] === 'rejected' ? 'danger' : 'secondary'))
                                ?>">
                                    <?= ucfirst($project['status']) ?>
                                </span>
                            </div>

                            <!-- Project Description -->
                            <?php if (!empty($project['description'])): ?>
                                <p class="card-text text-muted small">
                                    <?= htmlspecialchars(substr($project['description'], 0, 100)) ?>
                                    <?= strlen($project['description']) > 100 ? '...' : '' ?>
                                </p>
                            <?php endif; ?>

                            <!-- Technologies -->
                            <?php if (!empty($project['technologies'])): ?>
                                <div class="mb-2">
                                    <small class="text-muted">
                                        <i class="fas fa-code me-1"></i>
                                        <?= htmlspecialchars($project['technologies']) ?>
                                    </small>
                                </div>
                            <?php endif; ?>

                            <!-- Project Meta -->
                            <div class="mt-auto">
                                <div class="d-flex justify-content-between align-items-center small text-muted mb-2">
                                    <span>
                                        <i class="fas fa-calendar me-1"></i>
                                        <?= date('M j, Y', strtotime($project['created_at'])) ?>
                                    </span>
                                    <span>
                                        <i class="fas fa-eye me-1"></i>
                                        <?= rand(0, 500) ?> views
                                    </span>
                                </div>

                                <!-- Visibility Badge -->
                                <div class="mb-2">
                                    <span class="badge bg-<?= $project['visibility'] === 'public' ? 'info' : 'light text-dark' ?>">
                                        <i class="fas fa-<?= $project['visibility'] === 'public' ? 'globe' : 'lock' ?> me-1"></i>
                                        <?= ucfirst($project['visibility']) ?>
                                    </span>
                                </div>

                                <!-- Rejection Message -->
                                <?php if ($project['status'] === 'rejected' && !empty($project['moderation_notes'])): ?>
                                    <div class="alert alert-danger p-2 mb-2">
                                        <small>
                                            <strong>Rejected:</strong>
                                            <?= htmlspecialchars($project['moderation_notes']) ?>
                                        </small>
                                    </div>
                                <?php endif; ?>

                                <!-- Actions -->
                                <div class="btn-group w-100" role="group">
                                    <a href="/index.php?page=portfolio_edit&id=<?= $project['id'] ?>"
                                       class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-edit"></i>
                                    </a>

                                    <?php if ($project['status'] === 'draft'): ?>
                                        <button class="btn btn-outline-success btn-sm"
                                                onclick="submitForModeration(<?= $project['id'] ?>)">
                                            <i class="fas fa-paper-plane"></i>
                                        </button>
                                    <?php endif; ?>

                                    <?php if ($project['visibility'] === 'public'): ?>
                                        <button class="btn btn-outline-warning btn-sm"
                                                onclick="toggleVisibility(<?= $project['id'] ?>, 'private')">
                                            <i class="fas fa-eye-slash"></i>
                                        </button>
                                    <?php else: ?>
                                        <button class="btn btn-outline-info btn-sm"
                                                onclick="toggleVisibility(<?= $project['id'] ?>, 'public')">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    <?php endif; ?>

                                    <button class="btn btn-outline-danger btn-sm"
                                            onclick="deleteProject(<?= $project['id'] ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <nav aria-label="Projects pagination">
                <ul class="pagination justify-content-center">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?= $i === $currentPage ? 'active' : '' ?>">
                            <a class="page-link" href="?filter=<?= urlencode($filter) ?>&page=<?= $i ?>">
                                <?= $i ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        <?php endif; ?>
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

function toggleVisibility(projectId, visibility) {
    const action = visibility === 'public' ? 'make public' : 'make private';
    if (confirm(`Are you sure you want to ${action} this project?`)) {
        fetch('/page/api/portfolio/toggle_visibility.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `project_id=${projectId}&visibility=${visibility}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(`Project ${action} successfully!`);
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
