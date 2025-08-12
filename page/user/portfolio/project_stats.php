<?php
/**
 * Portfolio Statistics
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

$pageTitle = 'Portfolio Statistics';
$current_user_id = $authService->getCurrentUserId();

// Get client profile and projects
$stmt = $database_handler->prepare("SELECT * FROM client_profiles WHERE user_id = ?");
$stmt->execute([$current_user_id]);
$profileData = $stmt->fetch(PDO::FETCH_ASSOC);

$projects = [];
if ($profileData) {
    $stmt = $database_handler->prepare("SELECT * FROM client_portfolio WHERE client_profile_id = ? ORDER BY created_at DESC");
    $stmt->execute([$profileData['id']]);
    $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Calculate statistics
$stats = [
    'total_projects' => count($projects),
    'published_projects' => count(array_filter($projects, fn($p) => $p['status'] === 'published')),
    'draft_projects' => count(array_filter($projects, fn($p) => $p['status'] === 'draft')),
    'pending_projects' => count(array_filter($projects, fn($p) => $p['status'] === 'pending')),
    'rejected_projects' => count(array_filter($projects, fn($p) => $p['status'] === 'rejected')),
];

// Calculate total views (simplified - would normally come from project_views table)
$totalViews = 0;

// Get monthly data for chart (simplified - last 6 months)
$monthlyViews = [];
for ($i = 5; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $monthlyViews[] = [
        'month' => $month,
        'views' => rand(50, 200) // Placeholder data
    ];
}

include __DIR__ . '/../../../resources/views/_header.php';
?>

<div class="container mt-4">
    <!-- Breadcrumbs -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/page/user/dashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="/page/user/portfolio/">Portfolio</a></li>
            <li class="breadcrumb-item active">Statistics</li>
        </ol>
    </nav>

    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">
            <i class="fas fa-chart-bar text-primary me-2"></i>
            Portfolio Statistics
        </h1>
        <a href="/page/user/portfolio/" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i>
            Back to Portfolio
        </a>
    </div>

    <!-- Overview Stats -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <div class="text-uppercase fw-bold small">Total Views</div>
                            <div class="h4 mb-0"><?= number_format($totalViews) ?></div>
                        </div>
                        <div>
                            <i class="fas fa-eye fa-2x opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <div class="text-uppercase fw-bold small">Published Projects</div>
                            <div class="h4 mb-0"><?= $stats['published_projects'] ?></div>
                        </div>
                        <div>
                            <i class="fas fa-check-circle fa-2x opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <div class="text-uppercase fw-bold small">Avg Views per Project</div>
                            <div class="h4 mb-0">
                                <?= $stats['published_projects'] > 0 ? number_format($totalViews / $stats['published_projects'], 1) : '0' ?>
                            </div>
                        </div>
                        <div>
                            <i class="fas fa-chart-line fa-2x opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <div class="text-uppercase fw-bold small">Portfolio Visibility</div>
                            <div class="h4 mb-0">
                                <?= $profileData && $profileData['portfolio_visibility'] === 'public' ? 'Public' : 'Private' ?>
                            </div>
                        </div>
                        <div>
                            <i class="fas fa-<?= $profileData && $profileData['portfolio_visibility'] === 'public' ? 'globe' : 'lock' ?> fa-2x opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts and Tables -->
    <div class="row">
        <!-- Monthly Views Chart -->
        <div class="col-lg-8 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-chart-line me-2"></i>
                        Monthly Views (Last 6 Months)
                    </h5>
                </div>
                <div class="card-body">
                    <canvas id="monthlyViewsChart" width="400" height="100"></canvas>
                </div>
            </div>
        </div>

        <!-- Project Status Breakdown -->
        <div class="col-lg-4 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-chart-pie me-2"></i>
                        Project Status Breakdown
                    </h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="d-flex justify-content-between">
                            <span>Published</span>
                            <span class="text-success fw-bold"><?= $stats['published_projects'] ?></span>
                        </div>
                        <div class="progress mb-2">
                            <div class="progress-bar bg-success"
                                 style="width: <?= $stats['total_projects'] > 0 ? ($stats['published_projects'] / $stats['total_projects']) * 100 : 0 ?>%"></div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between">
                            <span>Pending Review</span>
                            <span class="text-warning fw-bold"><?= $stats['pending_projects'] ?></span>
                        </div>
                        <div class="progress mb-2">
                            <div class="progress-bar bg-warning"
                                 style="width: <?= $stats['total_projects'] > 0 ? ($stats['pending_projects'] / $stats['total_projects']) * 100 : 0 ?>%"></div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between">
                            <span>Drafts</span>
                            <span class="text-secondary fw-bold"><?= $stats['draft_projects'] ?></span>
                        </div>
                        <div class="progress mb-2">
                            <div class="progress-bar bg-secondary"
                                 style="width: <?= $stats['total_projects'] > 0 ? ($stats['draft_projects'] / $stats['total_projects']) * 100 : 0 ?>%"></div>
                        </div>
                    </div>

                    <?php if ($stats['rejected_projects'] > 0): ?>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between">
                                <span>Rejected</span>
                                <span class="text-danger fw-bold"><?= $stats['rejected_projects'] ?></span>
                            </div>
                            <div class="progress mb-2">
                                <div class="progress-bar bg-danger"
                                     style="width: <?= $stats['total_projects'] > 0 ? ($stats['rejected_projects'] / $stats['total_projects']) * 100 : 0 ?>%"></div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Project Performance Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="fas fa-table me-2"></i>
                Project Performance
            </h5>
        </div>
        <div class="card-body">
            <?php if (empty($projects)): ?>
                <div class="text-center py-4">
                    <i class="fas fa-chart-bar fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">No project data yet</h5>
                    <p class="text-muted">Add some projects to see performance statistics</p>
                    <a href="/page/user/portfolio/add_project.php" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i>
                        Add Your First Project
                    </a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Project</th>
                                <th>Status</th>
                                <th>Views</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($projects as $project): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($project['title']) ?></strong>
                                        <?php if (!empty($project['description'])): ?>
                                            <br><small class="text-muted"><?= htmlspecialchars(substr($project['description'], 0, 50)) ?>...</small>
                                        <?php endif; ?>
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
                                        <span class="h6 mb-0"><?= rand(0, 500) ?></span>
                                        <small class="text-muted">views</small>
                                    </td>
                                    <td>
                                        <small class="text-muted"><?= date('M j, Y', strtotime($project['created_at'])) ?></small>
                                    </td>
                                    <td>
                                        <a href="/page/user/portfolio/edit_project.php?id=<?= $project['id'] ?>"
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="card mt-4">
        <div class="card-body">
            <h5 class="card-title">Quick Actions</h5>
            <div class="row">
                <div class="col-md-3 mb-2">
                    <a href="/page/user/portfolio/add_project.php" class="btn btn-primary w-100">
                        <i class="fas fa-plus me-1"></i> Add New Project
                    </a>
                </div>
                <div class="col-md-3 mb-2">
                    <a href="/page/user/portfolio/my_projects.php" class="btn btn-outline-primary w-100">
                        <i class="fas fa-folder me-1"></i> Manage Projects
                    </a>
                </div>
                <div class="col-md-3 mb-2">
                    <a href="/page/user/portfolio/project_settings.php" class="btn btn-outline-secondary w-100">
                        <i class="fas fa-cog me-1"></i> Portfolio Settings
                    </a>
                </div>
                <div class="col-md-3 mb-2">
                    <a href="/page/user/profile/" class="btn btn-outline-info w-100">
                        <i class="fas fa-user me-1"></i> Edit Profile
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('monthlyViewsChart').getContext('2d');

    const monthlyData = <?= json_encode($monthlyViews) ?>;
    const labels = monthlyData.map(item => {
        const date = new Date(item.month + '-01');
        return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short' });
    });
    const data = monthlyData.map(item => parseInt(item.views));

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Views',
                data: data,
                borderColor: 'rgb(75, 192, 192)',
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                tension: 0.1,
                fill: true
            }]
        },
        options: {
            responsive: true,
            plugins: {
                title: {
                    display: true,
                    text: 'Monthly Portfolio Views'
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });
});
</script>

<?php include __DIR__ . '/../../../resources/views/_footer.php'; ?>
