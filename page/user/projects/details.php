<?php
/**
 * Project Details Page - PHASE 8 - DARK ADMIN THEME
 * Shows detailed information about a specific studio project for clients
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
    header('Location: /index.php?page=home');
    exit;
}

$projectId = $_GET['id'] ?? null;
if (!$projectId) {
    $flashMessageService->addError('Project ID is required.');
    header("Location: /index.php?page=user_projects");
    exit();
}

// Mock project data for now - will be replaced with actual database queries
$project = [
    'id' => $projectId,
    'project_name' => 'E-commerce Platform',
    'description' => 'A comprehensive e-commerce solution with advanced features including inventory management, payment processing, and customer analytics.',
    'status' => 'development',
    'priority' => 'high',
    'progress_percentage' => 65,
    'start_date' => '2024-01-15',
    'estimated_completion' => '2024-03-30',
    'project_type' => 'Web Application',
    'budget' => 25000,
    'technologies' => ['PHP', 'Laravel', 'Vue.js', 'MySQL', 'Redis'],
    'team_members' => [
        ['name' => 'John Smith', 'role' => 'Project Manager'],
        ['name' => 'Sarah Johnson', 'role' => 'Lead Developer'],
        ['name' => 'Mike Chen', 'role' => 'Frontend Developer']
    ],
    'milestones' => [
        ['name' => 'Requirements Analysis', 'status' => 'completed', 'date' => '2024-01-20'],
        ['name' => 'Database Design', 'status' => 'completed', 'date' => '2024-02-01'],
        ['name' => 'Core Development', 'status' => 'in_progress', 'date' => '2024-02-15'],
        ['name' => 'Frontend Integration', 'status' => 'pending', 'date' => '2024-03-01'],
        ['name' => 'Testing & QA', 'status' => 'pending', 'date' => '2024-03-15'],
        ['name' => 'Deployment', 'status' => 'pending', 'date' => '2024-03-30']
    ]
];

$pageTitle = 'Project Details - ' . $project['project_name'];
$flashMessages = $flashMessageService->getAllMessages();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="stylesheet" href="/public/assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .progress-bar-custom {
            height: 8px;
            border-radius: 10px;
            background: var(--admin-bg-secondary);
            overflow: hidden;
        }
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--admin-primary) 0%, var(--admin-primary-light) 100%);
            transition: width 0.5s ease;
        }
        .milestone-item {
            position: relative;
            padding-left: 2rem;
            padding-bottom: 1.5rem;
        }
        .milestone-item:last-child {
            padding-bottom: 0;
        }
        .milestone-item::before {
            content: '';
            position: absolute;
            left: 0.6rem;
            top: 0;
            bottom: 0;
            width: 2px;
            background: var(--admin-border);
        }
        .milestone-item:last-child::before {
            display: none;
        }
        .milestone-marker {
            position: absolute;
            left: 0;
            top: 0.5rem;
            width: 1.25rem;
            height: 1.25rem;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 0.7rem;
        }
        .milestone-completed { background: var(--admin-success); }
        .milestone-in-progress { background: var(--admin-warning); }
        .milestone-pending { background: var(--admin-border); }
        .tech-badge {
            background: var(--admin-primary-bg);
            color: var(--admin-primary-light);
            border: 1px solid var(--admin-primary);
        }
    </style>
</head>
<body class="admin-panel">

<div class="admin-container">
    <!-- Navigation -->
    <nav class="admin-nav">
        <div class="admin-nav-container">
            <a href="/index.php?page=dashboard" class="admin-nav-brand">
                <i class="fas fa-project-diagram"></i>
                Project Details
            </a>
            <div class="admin-nav-links">
                <a href="/index.php?page=dashboard" class="admin-nav-link">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
                <a href="/index.php?page=user_projects" class="admin-nav-link">
                    <i class="fas fa-code"></i> Projects
                </a>
                <a href="/index.php?page=user_tickets" class="admin-nav-link">
                    <i class="fas fa-ticket-alt"></i> Support
                </a>
            </div>
        </div>
    </nav>

    <!-- Header -->
    <header class="admin-header">
        <div class="admin-header-container">
            <div class="admin-header-content">
                <div class="admin-header-title">
                    <i class="admin-header-icon fas fa-project-diagram"></i>
                    <div class="admin-header-text">
                        <h1><?= htmlspecialchars($project['project_name']) ?></h1>
                        <p>Detailed project information and progress tracking</p>
                    </div>
                </div>
                <div class="admin-header-actions">
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <span class="admin-badge <?= getStatusBadgeClass($project['status']) ?>">
                            <?= ucfirst(str_replace('_', ' ', $project['status'])) ?>
                        </span>
                        <a href="/index.php?page=user_projects" class="admin-btn admin-btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Projects
                        </a>
                    </div>
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

    <div class="admin-layout-main">
        <div class="admin-content">
            <!-- Project Overview -->
            <div class="admin-card">
                <div class="admin-card-header">
                    <h5 class="admin-card-title">
                        <i class="fas fa-info-circle"></i>
                        Project Overview
                    </h5>
                </div>
                <div class="admin-card-body">
                    <div class="admin-grid admin-grid-cols-2">
                        <div>
                            <p style="color: var(--admin-text-muted); line-height: 1.6;">
                                <?= htmlspecialchars($project['description']) ?>
                            </p>

                            <div style="margin-top: 1.5rem;">
                                <h6 style="color: var(--admin-text-primary); margin-bottom: 1rem;">Technologies Used</h6>
                                <div style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
                                    <?php foreach ($project['technologies'] as $tech): ?>
                                        <span class="admin-badge tech-badge">
                                            <i class="fas fa-code"></i>
                                            <?= htmlspecialchars($tech) ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <div>
                            <!-- Progress Circle -->
                            <div style="text-align: center; margin-bottom: 2rem;">
                                <div style="position: relative; display: inline-block;">
                                    <svg width="120" height="120">
                                        <circle cx="60" cy="60" r="50" stroke="var(--admin-border)" stroke-width="6" fill="transparent"/>
                                        <circle cx="60" cy="60" r="50" stroke="var(--admin-primary)" stroke-width="6" fill="transparent"
                                                stroke-dasharray="<?= 2 * 3.14159 * 50 ?>"
                                                stroke-dashoffset="<?= 2 * 3.14159 * 50 * (1 - $project['progress_percentage'] / 100) ?>"
                                                transform="rotate(-90 60 60)"/>
                                    </svg>
                                    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); text-align: center;">
                                        <div style="font-size: 1.5rem; font-weight: 700; color: var(--admin-text-primary);">
                                            <?= $project['progress_percentage'] ?>%
                                        </div>
                                        <div style="font-size: 0.75rem; color: var(--admin-text-muted);">Complete</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Key Details -->
                            <div style="background: var(--admin-bg-secondary); border-radius: var(--admin-border-radius); padding: 1rem;">
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; font-size: 0.875rem;">
                                    <div>
                                        <div style="color: var(--admin-text-muted);">Start Date</div>
                                        <div style="color: var(--admin-text-primary); font-weight: 500;">
                                            <?= date('M j, Y', strtotime($project['start_date'])) ?>
                                        </div>
                                    </div>
                                    <div>
                                        <div style="color: var(--admin-text-muted);">Est. Completion</div>
                                        <div style="color: var(--admin-text-primary); font-weight: 500;">
                                            <?= date('M j, Y', strtotime($project['estimated_completion'])) ?>
                                        </div>
                                    </div>
                                    <div>
                                        <div style="color: var(--admin-text-muted);">Budget</div>
                                        <div style="color: var(--admin-text-primary); font-weight: 500;">
                                            $<?= number_format($project['budget']) ?>
                                        </div>
                                    </div>
                                    <div>
                                        <div style="color: var(--admin-text-muted);">Type</div>
                                        <div style="color: var(--admin-text-primary); font-weight: 500;">
                                            <?= htmlspecialchars($project['project_type']) ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Project Milestones -->
            <div class="admin-card">
                <div class="admin-card-header">
                    <h5 class="admin-card-title">
                        <i class="fas fa-tasks"></i>
                        Project Milestones
                    </h5>
                </div>
                <div class="admin-card-body">
                    <div class="milestones-timeline">
                        <?php foreach ($project['milestones'] as $milestone): ?>
                            <div class="milestone-item">
                                <div class="milestone-marker milestone-<?= $milestone['status'] ?>">
                                    <?php if ($milestone['status'] === 'completed'): ?>
                                        <i class="fas fa-check"></i>
                                    <?php elseif ($milestone['status'] === 'in_progress'): ?>
                                        <i class="fas fa-play"></i>
                                    <?php else: ?>
                                        <i class="fas fa-clock"></i>
                                    <?php endif; ?>
                                </div>
                                <div style="margin-left: 0.5rem;">
                                    <h6 style="color: var(--admin-text-primary); margin-bottom: 0.25rem;">
                                        <?= htmlspecialchars($milestone['name']) ?>
                                    </h6>
                                    <div style="display: flex; align-items: center; gap: 1rem; font-size: 0.875rem;">
                                        <span class="admin-badge <?= getMilestoneBadgeClass($milestone['status']) ?>">
                                            <?= ucfirst(str_replace('_', ' ', $milestone['status'])) ?>
                                        </span>
                                        <span style="color: var(--admin-text-muted);">
                                            <i class="fas fa-calendar"></i>
                                            <?= date('M j, Y', strtotime($milestone['date'])) ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Team Members -->
            <div class="admin-card">
                <div class="admin-card-header">
                    <h5 class="admin-card-title">
                        <i class="fas fa-users"></i>
                        Project Team
                    </h5>
                </div>
                <div class="admin-card-body">
                    <div class="admin-grid admin-grid-cols-3">
                        <?php foreach ($project['team_members'] as $member): ?>
                            <div style="text-align: center; padding: 1rem; background: var(--admin-bg-secondary); border-radius: var(--admin-border-radius);">
                                <div style="width: 60px; height: 60px; border-radius: 50%; background: var(--admin-primary); margin: 0 auto 1rem auto; display: flex; align-items: center; justify-content: center; color: white; font-size: 1.5rem; font-weight: 700;">
                                    <?= strtoupper(substr($member['name'], 0, 1)) ?>
                                </div>
                                <h6 style="color: var(--admin-text-primary); margin-bottom: 0.25rem;">
                                    <?= htmlspecialchars($member['name']) ?>
                                </h6>
                                <small style="color: var(--admin-text-muted);">
                                    <?= htmlspecialchars($member['role']) ?>
                                </small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
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
                        <a href="/index.php?page=user_projects_timeline&id=<?= $projectId ?>" class="admin-btn admin-btn-primary admin-btn-sm">
                            <i class="fas fa-clock"></i> View Timeline
                        </a>
                        <a href="/index.php?page=user_tickets_create" class="admin-btn admin-btn-secondary admin-btn-sm">
                            <i class="fas fa-ticket-alt"></i> Create Support Ticket
                        </a>
                        <a href="/index.php?page=user_documents" class="admin-btn admin-btn-secondary admin-btn-sm">
                            <i class="fas fa-folder"></i> Project Documents
                        </a>
                        <a href="/index.php?page=user_meetings" class="admin-btn admin-btn-secondary admin-btn-sm">
                            <i class="fas fa-calendar"></i> Schedule Meeting
                        </a>
                    </div>
                </div>
            </div>

            <!-- Project Statistics -->
            <div class="admin-card">
                <div class="admin-card-header">
                    <h6 class="admin-card-title" style="font-size: 0.875rem;">Project Statistics</h6>
                </div>
                <div class="admin-card-body">
                    <div class="admin-stats-grid" style="grid-template-columns: 1fr 1fr;">
                        <div style="text-align: center;">
                            <div style="font-size: 1.5rem; font-weight: 700; color: var(--admin-success); margin-bottom: 0.25rem;">
                                <?= count(array_filter($project['milestones'], fn($m) => $m['status'] === 'completed')) ?>
                            </div>
                            <small style="color: var(--admin-text-muted);">Completed</small>
                        </div>
                        <div style="text-align: center;">
                            <div style="font-size: 1.5rem; font-weight: 700; color: var(--admin-warning); margin-bottom: 0.25rem;">
                                <?= count(array_filter($project['milestones'], fn($m) => $m['status'] === 'pending')) ?>
                            </div>
                            <small style="color: var(--admin-text-muted);">Remaining</small>
                        </div>
                    </div>

                    <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--admin-border);">
                        <div style="margin-bottom: 0.5rem;">
                            <span style="color: var(--admin-text-primary); font-size: 0.875rem;">Progress</span>
                            <span style="float: right; color: var(--admin-text-primary); font-size: 0.875rem; font-weight: 600;">
                                <?= $project['progress_percentage'] ?>%
                            </span>
                        </div>
                        <div class="progress-bar-custom">
                            <div class="progress-fill" style="width: <?= $project['progress_percentage'] ?>%;"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Timeline Preview -->
            <div class="admin-card">
                <div class="admin-card-header">
                    <h6 class="admin-card-title" style="font-size: 0.875rem;">Upcoming Milestones</h6>
                </div>
                <div class="admin-card-body">
                    <?php
                    $upcomingMilestones = array_filter($project['milestones'], fn($m) => $m['status'] === 'pending');
                    $upcomingMilestones = array_slice($upcomingMilestones, 0, 3);
                    ?>
                    <?php if (!empty($upcomingMilestones)): ?>
                        <?php foreach ($upcomingMilestones as $milestone): ?>
                            <div style="padding: 0.75rem 0; border-bottom: 1px solid var(--admin-border); last-child:border-bottom: none;">
                                <div style="font-size: 0.75rem; color: var(--admin-text-primary); margin-bottom: 0.25rem;">
                                    <?= htmlspecialchars($milestone['name']) ?>
                                </div>
                                <div style="font-size: 0.65rem; color: var(--admin-text-muted);">
                                    <i class="fas fa-calendar"></i>
                                    <?= date('M j, Y', strtotime($milestone['date'])) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <div style="margin-top: 1rem;">
                            <a href="/index.php?page=user_projects_timeline&id=<?= $projectId ?>" class="admin-btn admin-btn-secondary admin-btn-sm" style="width: 100%;">
                                <i class="fas fa-clock"></i> View Full Timeline
                            </a>
                        </div>
                    <?php else: ?>
                        <p style="color: var(--admin-text-muted); font-size: 0.75rem; text-align: center; margin: 1rem 0;">
                            All milestones completed!
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="/public/assets/js/admin.js"></script>
</body>
</html>

<?php
function getStatusBadgeClass($status): string
{
    return match($status) {
        'planning' => 'admin-badge-gray',
        'development' => 'admin-badge-primary',
        'testing' => 'admin-badge-warning',
        'deployment' => 'admin-badge-primary',
        'completed' => 'admin-badge-success',
        'on_hold' => 'admin-badge-error',
        default => 'admin-badge-gray'
    };
}

function getMilestoneBadgeClass($status): string
{
    return match($status) {
        'completed' => 'admin-badge-success',
        'in_progress' => 'admin-badge-warning',
        'pending' => 'admin-badge-gray',
        default => 'admin-badge-gray'
    };
}
?>
