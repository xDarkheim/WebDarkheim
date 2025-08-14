<?php
/**
 * User Portfolio Dashboard - PHASE 8 - DARK ADMIN THEME
 * Modern portfolio management interface with project overview
 */

declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/includes/bootstrap.php';
// Include profile completion helper
require_once dirname(__DIR__, 3) . '/includes/profile_completion_helper.php';

global $serviceProvider, $flashMessageService, $database_handler;

use App\Application\Components\AdminNavigation;

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

// Create unified navigation
$adminNavigation = new AdminNavigation($authService);

// Use unified helper for client profile and completion calculation
$clientProfile = getClientProfileData($database_handler, $userId);
$profileCompletion = calculateProfileCompletion($currentUser, $clientProfile);

// Get portfolio projects
$projects = [];
$stats = ['total' => 0, 'draft' => 0, 'pending' => 0, 'published' => 0, 'rejected' => 0];

if ($clientProfile) {
    try {
        $sql = "SELECT p.*, 
                       (SELECT COUNT(*) FROM project_views pv WHERE pv.project_id = p.id) as view_count,
                       (SELECT COUNT(*) FROM comments c WHERE c.commentable_id = p.id AND c.commentable_type = 'portfolio_project' AND c.status = 'approved') as comment_count
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

<link rel="stylesheet" href="/public/assets/css/admin.css">
    <style>
        .project-card {
            transition: var(--admin-transition);
            overflow: hidden;
        }
        .project-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--admin-shadow-lg);
        }
        .project-image {
            height: 200px;
            background: linear-gradient(135deg, var(--admin-bg-secondary) 0%, var(--admin-bg-tertiary) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--admin-text-muted);
            position: relative;
            overflow: hidden;
        }
        .project-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
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
            color: var(--admin-text-muted);
        }
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.3;
        }
        .filter-tabs {
            background: var(--admin-bg-card);
            border-radius: var(--admin-border-radius);
            padding: 1rem;
            margin-bottom: 2rem;
            border: 1px solid var(--admin-border);
        }
        .tech-badge {
            background: var(--admin-primary-bg);
            color: var(--admin-primary-light);
            border: 1px solid var(--admin-primary);
        }
    </style>
</head>
<body>

<div class="admin-container">
    <!-- Navigation -->
    <!-- Unified Navigation -->
    <?= $adminNavigation->render() ?>

    <!-- Header -->
    <header class="admin-header">
        <div class="admin-header-container">
            <div class="admin-header-content">
                <div class="admin-header-title">
                    <i class="admin-header-icon fas fa-briefcase"></i>
                    <div class="admin-header-text">
                        <h1>My Portfolio</h1>
                        <p>Showcase your projects and track their performance</p>
                    </div>
                </div>
                <div class="admin-header-actions">
                    <a href="/index.php?page=portfolio_create" class="admin-btn admin-btn-primary">
                        <i class="fas fa-plus"></i> Add Project
                    </a>
                    <a href="/index.php?page=portfolio_settings" class="admin-btn admin-btn-secondary">
                        <i class="fas fa-cog"></i> Settings
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Flash Messages -->
    <?php if (!empty($flashMessages)): ?>
        <div class="admin-flash-messages">
            <?php foreach ($flashMessages as $type => $messages): ?>
                <?php foreach ($messages as $message): ?>
                    <div class="admin-flash-message admin-flash-<?= $type === 'error' ? 'error' : $type ?>">
                        <i class="fas fa-<?= $type === 'success' ? 'check-circle' : ($type === 'error' ? 'exclamation-circle' : 'info-circle') ?>"></i>
                        <div><?= htmlspecialchars($message['text']) ?></div>
                    </div>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Check if client profile exists -->
    <?php if (!$clientProfile): ?>
        <div style="max-width: 1280px; margin: 0 auto; padding: 0 1rem;">
            <div class="admin-card">
                <div class="admin-card-body" style="text-align: center; padding: 4rem 2rem;">
                    <i class="fas fa-user-plus" style="font-size: 4rem; color: var(--admin-warning); margin-bottom: 2rem;"></i>
                    <h3 style="color: var(--admin-text-primary); margin-bottom: 1rem;">Complete Your Profile First</h3>
                    <p style="color: var(--admin-text-muted); margin-bottom: 2rem; max-width: 500px; margin-left: auto; margin-right: auto;">
                        Before you can create portfolio projects, you need to complete your client profile with your professional information.
                    </p>
                    <a href="/index.php?page=profile_edit" class="admin-btn admin-btn-primary">
                        <i class="fas fa-edit"></i> Complete Profile
                    </a>
                </div>
            </div>
        </div>
    <?php else: ?>

    <div class="admin-layout-main">
        <div class="admin-content">
            <!-- Statistics Cards -->
            <div class="admin-stats-grid">
                <div class="admin-stat-card">
                    <div class="admin-stat-content">
                        <div class="admin-stat-icon admin-stat-icon-primary" style="background: var(--admin-primary-bg); color: var(--admin-primary);">
                            <i class="fas fa-folder"></i>
                        </div>
                        <div class="admin-stat-details">
                            <h3>Total Projects</h3>
                            <p style="color: var(--admin-text-primary);"><?= $stats['total'] ?></p>
                            <span>All portfolio projects</span>
                        </div>
                    </div>
                </div>

                <div class="admin-stat-card">
                    <div class="admin-stat-content">
                        <div class="admin-stat-icon admin-stat-icon-success">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="admin-stat-details">
                            <h3>Published</h3>
                            <p style="color: var(--admin-text-primary);"><?= $stats['published'] ?></p>
                            <span>Live projects</span>
                        </div>
                    </div>
                </div>

                <div class="admin-stat-card">
                    <div class="admin-stat-content">
                        <div class="admin-stat-icon admin-stat-icon-warning">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="admin-stat-details">
                            <h3>Pending Review</h3>
                            <p style="color: var(--admin-text-primary);"><?= $stats['pending'] ?></p>
                            <span>Awaiting approval</span>
                        </div>
                    </div>
                </div>

                <div class="admin-stat-card">
                    <div class="admin-stat-content">
                        <div class="admin-stat-icon" style="background: var(--admin-bg-secondary); color: var(--admin-text-secondary);">
                            <i class="fas fa-edit"></i>
                        </div>
                        <div class="admin-stat-details">
                            <h3>Drafts</h3>
                            <p style="color: var(--admin-text-primary);"><?= $stats['draft'] ?></p>
                            <span>Work in progress</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filter Tabs -->
            <div class="filter-tabs">
                <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                    <button class="admin-btn admin-btn-primary admin-btn-sm filter-btn active" data-filter="all">
                        <i class="fas fa-th-large"></i> All Projects
                    </button>
                    <button class="admin-btn admin-btn-secondary admin-btn-sm filter-btn" data-filter="published">
                        <i class="fas fa-check-circle"></i> Published
                    </button>
                    <button class="admin-btn admin-btn-secondary admin-btn-sm filter-btn" data-filter="pending">
                        <i class="fas fa-clock"></i> Pending
                    </button>
                    <button class="admin-btn admin-btn-secondary admin-btn-sm filter-btn" data-filter="draft">
                        <i class="fas fa-edit"></i> Drafts
                    </button>
                    <button class="admin-btn admin-btn-secondary admin-btn-sm filter-btn" data-filter="rejected">
                        <i class="fas fa-times-circle"></i> Rejected
                    </button>
                </div>
            </div>

            <!-- Projects Grid -->
            <?php if (empty($projects)): ?>
                <div class="admin-card">
                    <div class="admin-card-body">
                        <div class="empty-state">
                            <i class="fas fa-folder-open"></i>
                            <h4 style="color: var(--admin-text-primary); margin-bottom: 1rem;">No Projects Yet</h4>
                            <p style="margin-bottom: 2rem;">Start building your portfolio by adding your first project.</p>
                            <a href="/index.php?page=portfolio_create" class="admin-btn admin-btn-primary">
                                <i class="fas fa-plus"></i> Create Your First Project
                            </a>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="admin-grid admin-grid-cols-3" id="projectsGrid">
                    <?php foreach ($projects as $project): ?>
                        <div class="admin-card project-card" data-status="<?= $project['status'] ?>">
                            <div class="project-image">
                                <?php if (!empty($project['images'])): ?>
                                    <?php
                                    $images = json_decode($project['images'], true);
                                    if (!empty($images) && is_array($images)): ?>
                                        <img src="/storage/uploads/portfolio/<?= htmlspecialchars($images[0]) ?>"
                                             alt="<?= htmlspecialchars($project['title']) ?>">
                                    <?php else: ?>
                                        <i class="fas fa-image fa-3x"></i>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <i class="fas fa-image fa-3x"></i>
                                <?php endif; ?>

                                <div class="project-status">
                                    <?php
                                    $statusClasses = [
                                        'published' => 'admin-badge-success',
                                        'pending' => 'admin-badge-warning',
                                        'draft' => 'admin-badge-gray',
                                        'rejected' => 'admin-badge-error'
                                    ];
                                    $statusClass = $statusClasses[$project['status']] ?? 'admin-badge-gray';
                                    ?>
                                    <span class="admin-badge <?= $statusClass ?>">
                                        <?= ucfirst($project['status']) ?>
                                    </span>
                                </div>
                            </div>

                            <div class="admin-card-body">
                                <h5 style="color: var(--admin-text-primary); margin-bottom: 0.5rem;"><?= htmlspecialchars($project['title']) ?></h5>
                                <p style="color: var(--admin-text-muted); font-size: 0.875rem; line-height: 1.4; margin-bottom: 1rem;">
                                    <?= htmlspecialchars(substr($project['description'] ?? '', 0, 100)) ?>
                                    <?= strlen($project['description'] ?? '') > 100 ? '...' : '' ?>
                                </p>

                                <?php if (!empty($project['technologies'])): ?>
                                    <div style="margin-bottom: 1rem;">
                                        <?php
                                        $technologies = explode(',', $project['technologies']);
                                        foreach (array_slice($technologies, 0, 3) as $tech): ?>
                                            <span class="admin-badge tech-badge" style="margin-right: 0.25rem; margin-bottom: 0.25rem; font-size: 0.65rem;">
                                                <?= htmlspecialchars(trim($tech)) ?>
                                            </span>
                                        <?php endforeach; ?>
                                        <?php if (count($technologies) > 3): ?>
                                            <span class="admin-badge admin-badge-gray" style="margin-right: 0.25rem; font-size: 0.65rem;">
                                                +<?= count($technologies) - 3 ?> more
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>

                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; font-size: 0.75rem; color: var(--admin-text-muted);">
                                    <div>
                                        <i class="fas fa-eye"></i> <?= $project['view_count'] ?? 0 ?> views
                                        <i class="fas fa-comments" style="margin-left: 0.5rem;"></i> <?= $project['comment_count'] ?? 0 ?>
                                    </div>
                                    <div>
                                        <?= date('M j, Y', strtotime($project['updated_at'])) ?>
                                    </div>
                                </div>
                            </div>

                            <div class="admin-card-footer">
                                <div style="display: flex; gap: 0.5rem;">
                                    <a href="/index.php?page=user_portfolio_edit&id=<?= $project['id'] ?>"
                                       class="admin-btn admin-btn-primary admin-btn-sm" style="flex: 1;">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <?php if ($project['status'] === 'published'): ?>
                                        <a href="#" class="admin-btn admin-btn-success admin-btn-sm" style="flex: 1;">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                    <?php endif; ?>
                                    <button class="admin-btn admin-btn-danger admin-btn-sm"
                                            onclick="confirmDelete(<?= $project['id'] ?>, '<?= htmlspecialchars($project['title']) ?>')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Sidebar -->
        <div class="admin-sidebar">
            <!-- Quick Actions -->
            <div class="admin-card">
                <div class="admin-card-header">
                    <h6 class="admin-card-title" style="font-size: 0.875rem;">Quick Actions</h6>
                </div>
                <div class="admin-card-body">
                    <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                        <a href="/index.php?page=portfolio_create" class="admin-btn admin-btn-primary admin-btn-sm">
                            <i class="fas fa-plus"></i> Add New Project
                        </a>
                        <a href="/index.php?page=user_profile" class="admin-btn admin-btn-secondary admin-btn-sm">
                            <i class="fas fa-user"></i> Edit Profile
                        </a>
                        <a href="/index.php?page=portfolio_settings" class="admin-btn admin-btn-secondary admin-btn-sm">
                            <i class="fas fa-cog"></i> Portfolio Settings
                        </a>
                    </div>
                </div>
            </div>

            <!-- Portfolio Analytics -->
            <div class="admin-card">
                <div class="admin-card-header">
                    <h6 class="admin-card-title" style="font-size: 0.875rem;">Analytics</h6>
                </div>
                <div class="admin-card-body">
                    <div style="text-align: center;">
                        <div style="font-size: 1.5rem; font-weight: 700; color: var(--admin-primary); margin-bottom: 0.25rem;">
                            <?= array_sum(array_column($projects, 'view_count')) ?>
                        </div>
                        <small style="color: var(--admin-text-muted);">Total Views</small>
                    </div>

                    <!-- Profile Completion using unified helper -->
                    <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--admin-border);">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                            <span style="color: var(--admin-text-primary); font-size: 0.75rem;">Profile Completion</span>
                            <span style="color: var(--admin-text-primary); font-size: 0.75rem;">
                                <?= $profileCompletion['percentage'] ?>%
                            </span>
                        </div>
                        <div style="background: var(--admin-bg-secondary); border-radius: 9999px; height: 6px; overflow: hidden;">
                            <div style="background: var(--admin-success); height: 100%; width: <?= $profileCompletion['percentage'] ?>%; transition: width 0.3s ease;"></div>
                        </div>
                        <small style="color: var(--admin-text-muted); display: block; margin-top: 0.5rem;">
                            <?= $profileCompletion['completed'] ?> of <?= $profileCompletion['total'] ?> fields completed
                        </small>
                    </div>

                    <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--admin-border);">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                            <span style="color: var(--admin-text-primary); font-size: 0.75rem;">Portfolio Completion</span>
                            <span style="color: var(--admin-text-primary); font-size: 0.75rem;">
                                <?= $stats['total'] > 0 ? round(($stats['published'] / $stats['total']) * 100) : 0 ?>%
                            </span>
                        </div>
                        <div style="background: var(--admin-bg-secondary); border-radius: 9999px; height: 6px; overflow: hidden;">
                            <?php
                            $portfolioPercentage = $stats['total'] > 0 ? round(($stats['published'] / $stats['total']) * 100) : 0;
                            ?>
                            <div style="background: var(--admin-primary); height: 100%; width: <?= $portfolioPercentage ?>%; transition: width 0.3s ease;"></div>
                        </div>
                        <small style="color: var(--admin-text-muted); display: block; margin-top: 0.5rem;">
                            <?= $stats['published'] ?> of <?= $stats['total'] ?> projects published
                        </small>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="admin-card">
                <div class="admin-card-header">
                    <h6 class="admin-card-title" style="font-size: 0.875rem;">Recent Activity</h6>
                </div>
                <div class="admin-card-body">
                    <?php if (!empty($projects)): ?>
                        <?php foreach (array_slice($projects, 0, 3) as $project): ?>
                            <div style="display: flex; align-items: center; padding: 0.5rem 0; border-bottom: 1px solid var(--admin-border); last-child:border-bottom: none;">
                                <div style="width: 8px; height: 8px; border-radius: 50%; background: var(--admin-primary); margin-right: 0.75rem; flex-shrink: 0;"></div>
                                <div style="flex: 1; min-width: 0;">
                                    <div style="font-size: 0.75rem; color: var(--admin-text-primary); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                        <?= htmlspecialchars($project['title']) ?>
                                    </div>
                                    <div style="font-size: 0.65rem; color: var(--admin-text-muted);">
                                        Updated <?= date('M j', strtotime($project['updated_at'])) ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="color: var(--admin-text-muted); font-size: 0.75rem; text-align: center; margin: 1rem 0;">
                            No recent activity
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php endif; ?>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="background: var(--admin-bg-card); border: 1px solid var(--admin-border);">
            <div class="modal-header" style="border-bottom: 1px solid var(--admin-border);">
                <h5 class="modal-title" style="color: var(--admin-text-primary);">
                    <i class="fas fa-exclamation-triangle" style="color: var(--admin-error); margin-right: 0.5rem;"></i>
                    Delete Project
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" style="filter: invert(1);"></button>
            </div>
            <div class="modal-body">
                <p style="color: var(--admin-text-primary);">Are you sure you want to delete "<span id="deleteProjectTitle"></span>"?</p>
                <div style="background: var(--admin-error-bg); border: 1px solid var(--admin-error); border-radius: var(--admin-border-radius); padding: 1rem; margin-top: 1rem;">
                    <strong style="color: var(--admin-error-light);">Warning:</strong>
                    <span style="color: var(--admin-error-light);">This action cannot be undone.</span>
                </div>
            </div>
            <div class="modal-footer" style="border-top: 1px solid var(--admin-border);">
                <button type="button" class="admin-btn admin-btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="admin-btn admin-btn-danger" onclick="deleteProject()">
                    <i class="fas fa-trash-alt"></i> Delete Project
                </button>
            </div>
        </div>
    </div>
</div>

<script src="/public/assets/js/admin.js"></script>
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
        fetch('/page/api/portfolio/delete.php', {
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
    const filterBtns = document.querySelectorAll('.filter-btn');
    const projectCards = document.querySelectorAll('.project-card');

    filterBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const filter = this.dataset.filter;

            // Update active button
            filterBtns.forEach(b => {
                b.classList.remove('admin-btn-primary');
                b.classList.add('admin-btn-secondary');
            });
            this.classList.remove('admin-btn-secondary');
            this.classList.add('admin-btn-primary');

            // Filter projects
            projectCards.forEach(card => {
                const status = card.dataset.status;
                if (filter === 'all' || status === filter) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    });

    // Auto-dismiss flash messages
    setTimeout(function() {
        document.querySelectorAll('.admin-flash-message').forEach(function(msg) {
            msg.style.opacity = '0';
            setTimeout(() => msg.remove(), 300);
        });
    }, 5000);
});
</script>
</body>
</html>
