<?php
/**
 * Project Timeline Page - PHASE 8 - DARK ADMIN THEME
 * Shows timeline and milestones for a studio project
 */

declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/includes/bootstrap.php';

global $serviceProvider, $flashMessageService;

try {
    $authService = $serviceProvider->getAuth();
} catch (Exception $e) {
    error_log("Critical: Failed to get AuthenticationService: " . $e->getMessage());
    die("System error occurred.");
}

if (!$authService->isAuthenticated()) {
    $flashMessageService->addError('Please log in to access this area.');
    header("Location: /index.php?page=login");
    exit();
}

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

// Mock timeline data
$project = [
    'id' => $projectId,
    'project_name' => 'E-commerce Platform',
    'status' => 'development',
    'progress_percentage' => 65
];

$timeline = [
    ['date' => '2024-01-15', 'title' => 'Project Kickoff', 'type' => 'milestone', 'status' => 'completed', 'description' => 'Initial project meeting and requirements gathering'],
    ['date' => '2024-01-20', 'title' => 'Requirements Finalized', 'type' => 'milestone', 'status' => 'completed', 'description' => 'All project requirements documented and approved'],
    ['date' => '2024-01-25', 'title' => 'Database Schema Created', 'type' => 'task', 'status' => 'completed', 'description' => 'Database structure designed and implemented'],
    ['date' => '2024-02-01', 'title' => 'UI/UX Design Phase', 'type' => 'milestone', 'status' => 'completed', 'description' => 'User interface mockups and wireframes completed'],
    ['date' => '2024-02-10', 'title' => 'Backend Development Started', 'type' => 'task', 'status' => 'completed', 'description' => 'Core backend functionality development began'],
    ['date' => '2024-02-15', 'title' => 'Mid-project Review', 'type' => 'meeting', 'status' => 'completed', 'description' => 'Progress review meeting with stakeholders'],
    ['date' => '2024-02-20', 'title' => 'API Development', 'type' => 'task', 'status' => 'in_progress', 'description' => 'REST API endpoints development'],
    ['date' => '2024-03-01', 'title' => 'Frontend Integration', 'type' => 'milestone', 'status' => 'pending', 'description' => 'Frontend components integration with backend'],
    ['date' => '2024-03-15', 'title' => 'Testing Phase', 'type' => 'milestone', 'status' => 'pending', 'description' => 'Comprehensive testing and quality assurance'],
    ['date' => '2024-03-25', 'title' => 'Client Review', 'type' => 'meeting', 'status' => 'pending', 'description' => 'Final client review and feedback session'],
    ['date' => '2024-03-30', 'title' => 'Project Delivery', 'type' => 'milestone', 'status' => 'pending', 'description' => 'Final project delivery and handover']
];

$pageTitle = 'Project Timeline - ' . $project['project_name'];
$flashMessages = $flashMessageService->getAllMessages();
?>

    <link rel="stylesheet" href="/public/assets/css/admin.css">
    <style>
        .timeline-container {
            position: relative;
            padding-left: 2rem;
        }
        .timeline-line {
            position: absolute;
            left: 1rem;
            top: 0;
            bottom: 0;
            width: 2px;
            background: linear-gradient(to bottom, var(--admin-primary), var(--admin-border));
        }
        .timeline-item {
            position: relative;
            margin-bottom: 2rem;
            padding-left: 1rem;
        }
        .timeline-marker {
            position: absolute;
            left: -1.75rem;
            top: 0.5rem;
            width: 2rem;
            height: 2rem;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 0.875rem;
            border: 3px solid var(--admin-bg-primary);
        }
        .timeline-marker.completed { background: var(--admin-success); }
        .timeline-marker.in_progress { background: var(--admin-warning); animation: pulse 2s infinite; }
        .timeline-marker.pending { background: var(--admin-border); color: var(--admin-text-muted); }
        .timeline-content {
            background: var(--admin-bg-card);
            border-radius: var(--admin-border-radius);
            padding: 1.5rem;
            border: 1px solid var(--admin-border);
            transition: var(--admin-transition);
        }
        .timeline-content:hover {
            transform: translateX(5px);
            box-shadow: var(--admin-shadow-lg);
        }
        .timeline-date {
            position: absolute;
            right: 100%;
            top: 0.5rem;
            margin-right: 1rem;
            font-size: 0.75rem;
            color: var(--admin-text-muted);
            white-space: nowrap;
        }
        .type-milestone { border-left: 4px solid var(--admin-primary); }
        .type-task { border-left: 4px solid var(--admin-success); }
        .type-meeting { border-left: 4px solid var(--admin-warning); }
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(245, 158, 11, 0.7); }
            70% { box-shadow: 0 0 0 10px rgba(245, 158, 11, 0); }
            100% { box-shadow: 0 0 0 0 rgba(245, 158, 11, 0); }
        }
        @media (max-width: 768px) {
            .timeline-date {
                position: static;
                margin-right: 0;
                margin-bottom: 0.5rem;
                display: block;
            }
        }
    </style>

<div class="admin-container">
    <!-- Navigation -->
    <nav class="admin-nav">
        <div class="admin-nav-container">
            <a href="/index.php?page=dashboard" class="admin-nav-brand">
                <i class="fas fa-timeline"></i>
                Project Timeline
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
                    <i class="admin-header-icon fas fa-timeline"></i>
                    <div class="admin-header-text">
                        <h1>Project Timeline</h1>
                        <p><?= htmlspecialchars($project['project_name']) ?> - Development Progress</p>
                    </div>
                </div>
                <div class="admin-header-actions">
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <div style="text-align: center;">
                            <div style="font-size: 1.5rem; font-weight: 700; color: var(--admin-primary);">
                                <?= $project['progress_percentage'] ?>%
                            </div>
                            <small style="color: var(--admin-text-muted);">Complete</small>
                        </div>
                        <a href="/index.php?page=user_projects_details&id=<?= htmlspecialchars($projectId) ?>" class="admin-btn admin-btn-secondary">
                            <i class="fas fa-info-circle"></i> Project Details
                        </a>
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
            <!-- Timeline -->
            <div class="admin-card">
                <div class="admin-card-header">
                    <h5 class="admin-card-title">
                        <i class="fas fa-history"></i>
                        Project Timeline & Milestones
                    </h5>
                </div>
                <div class="admin-card-body">
                    <div class="timeline-container">
                        <div class="timeline-line"></div>

                        <?php foreach ($timeline as $item): ?>
                            <div class="timeline-item">
                                <div class="timeline-date">
                                    <?= date('M j, Y', strtotime($item['date'])) ?>
                                </div>

                                <div class="timeline-marker <?= $item['status'] ?>">
                                    <?php if ($item['type'] === 'milestone'): ?>
                                        <i class="fas fa-flag"></i>
                                    <?php elseif ($item['type'] === 'meeting'): ?>
                                        <i class="fas fa-users"></i>
                                    <?php else: ?>
                                        <i class="fas fa-tasks"></i>
                                    <?php endif; ?>
                                </div>

                                <div class="timeline-content type-<?= $item['type'] ?>">
                                    <div style="display: flex; justify-content: between; align-items: flex-start; margin-bottom: 0.5rem;">
                                        <h6 style="color: var(--admin-text-primary); margin: 0; flex: 1;">
                                            <?= htmlspecialchars($item['title']) ?>
                                        </h6>
                                        <div style="display: flex; gap: 0.5rem; margin-left: 1rem;">
                                            <span class="admin-badge admin-badge-<?= getTypeBadgeColor($item['type']) ?>">
                                                <?= ucfirst($item['type']) ?>
                                            </span>
                                            <span class="admin-badge <?= getStatusBadgeClass($item['status']) ?>">
                                                <?= ucfirst(str_replace('_', ' ', $item['status'])) ?>
                                            </span>
                                        </div>
                                    </div>

                                    <p style="color: var(--admin-text-muted); margin: 0; line-height: 1.5;">
                                        <?= htmlspecialchars($item['description']) ?>
                                    </p>

                                    <?php if ($item['status'] === 'in_progress'): ?>
                                        <div style="margin-top: 1rem; padding: 0.75rem; background: var(--admin-warning-bg); border-radius: var(--admin-border-radius); border: 1px solid var(--admin-warning);">
                                            <small style="color: var(--admin-warning-light);">
                                                <i class="fas fa-clock"></i>
                                                <strong>Currently in progress</strong> - Expected completion: <?= date('M j, Y', strtotime($item['date'] . ' +5 days')) ?>
                                            </small>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="admin-sidebar">
            <!-- Timeline Overview -->
            <div class="admin-card">
                <div class="admin-card-header">
                    <h6 class="admin-card-title" style="font-size: 0.875rem;">Timeline Overview</h6>
                </div>
                <div class="admin-card-body">
                    <div class="admin-stats-grid" style="grid-template-columns: 1fr 1fr;">
                        <div style="text-align: center;">
                            <div style="font-size: 1.5rem; font-weight: 700; color: var(--admin-success); margin-bottom: 0.25rem;">
                                <?= count(array_filter($timeline, fn($t) => $t['status'] === 'completed')) ?>
                            </div>
                            <small style="color: var(--admin-text-muted);">Completed</small>
                        </div>
                        <div style="text-align: center;">
                            <div style="font-size: 1.5rem; font-weight: 700; color: var(--admin-warning); margin-bottom: 0.25rem;">
                                <?= count(array_filter($timeline, fn($t) => $t['status'] === 'pending')) ?>
                            </div>
                            <small style="color: var(--admin-text-muted);">Remaining</small>
                        </div>
                    </div>

                    <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--admin-border);">
                        <h6 style="color: var(--admin-text-primary); font-size: 0.875rem; margin-bottom: 0.75rem;">Timeline Types</h6>
                        <div style="display: flex; flex-direction: column; gap: 0.5rem; font-size: 0.75rem;">
                            <div style="display: flex; align-items: center;">
                                <div style="width: 12px; height: 12px; background: var(--admin-primary); border-radius: 50%; margin-right: 0.5rem;"></div>
                                <span style="color: var(--admin-text-muted);">Milestones</span>
                            </div>
                            <div style="display: flex; align-items: center;">
                                <div style="width: 12px; height: 12px; background: var(--admin-success); border-radius: 50%; margin-right: 0.5rem;"></div>
                                <span style="color: var(--admin-text-muted);">Tasks</span>
                            </div>
                            <div style="display: flex; align-items: center;">
                                <div style="width: 12px; height: 12px; background: var(--admin-warning); border-radius: 50%; margin-right: 0.5rem;"></div>
                                <span style="color: var(--admin-text-muted);">Meetings</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Current Status -->
            <div class="admin-card">
                <div class="admin-card-header">
                    <h6 class="admin-card-title" style="font-size: 0.875rem;">Current Status</h6>
                </div>
                <div class="admin-card-body">
                    <?php
                    $currentItem = array_filter($timeline, fn($t) => $t['status'] === 'in_progress');
                    $currentItem = reset($currentItem);
                    ?>
                    <?php if ($currentItem): ?>
                        <div style="text-align: center; margin-bottom: 1rem;">
                            <div style="width: 60px; height: 60px; background: var(--admin-warning); border-radius: 50%; margin: 0 auto 0.75rem auto; display: flex; align-items: center; justify-content: center; color: white;">
                                <i class="fas fa-<?= $currentItem['type'] === 'milestone' ? 'flag' : ($currentItem['type'] === 'meeting' ? 'users' : 'tasks') ?>"></i>
                            </div>
                            <h6 style="color: var(--admin-text-primary); margin-bottom: 0.5rem;">
                                <?= htmlspecialchars($currentItem['title']) ?>
                            </h6>
                            <small style="color: var(--admin-text-muted);">
                                <?= htmlspecialchars($currentItem['description']) ?>
                            </small>
                        </div>
                        <div style="background: var(--admin-warning-bg); border: 1px solid var(--admin-warning); border-radius: var(--admin-border-radius); padding: 0.75rem; text-align: center;">
                            <small style="color: var(--admin-warning-light);">
                                <i class="fas fa-clock"></i>
                                Expected completion: <?= date('M j', strtotime($currentItem['date'] . ' +5 days')) ?>
                            </small>
                        </div>
                    <?php else: ?>
                        <div style="text-align: center; color: var(--admin-text-muted);">
                            <i class="fas fa-check-circle" style="font-size: 2rem; margin-bottom: 0.5rem; color: var(--admin-success);"></i>
                            <p style="margin: 0; font-size: 0.875rem;">All scheduled items completed!</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="admin-card">
                <div class="admin-card-header">
                    <h6 class="admin-card-title" style="font-size: 0.875rem;">Quick Actions</h6>
                </div>
                <div class="admin-card-body">
                    <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                        <a href="/index.php?page=user_projects_details&id=<?= $projectId ?>" class="admin-btn admin-btn-primary admin-btn-sm">
                            <i class="fas fa-info-circle"></i> Project Details
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
        </div>
    </div>
</div>

<script src="/public/assets/js/admin.js"></script>

<?php
function getStatusBadgeClass($status): string
{
    return match($status) {
        'completed' => 'admin-badge-success',
        'in_progress' => 'admin-badge-warning',
        'pending' => 'admin-badge-gray',
        default => 'admin-badge-gray'
    };
}

function getTypeBadgeColor($type): string
{
    return match($type) {
        'milestone' => 'primary',
        'task' => 'success',
        'meeting' => 'warning',
        default => 'gray'
    };
}
?>
