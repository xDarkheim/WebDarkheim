<?php

/**
 * Meetings & Consultations - Client Portal - DARK ADMIN THEME
 * Schedule and manage meetings with the studio team
 *
 * @author GitHub Copilot
 */

declare(strict_types=1);

use App\Application\Components\AdminNavigation;

// Use global services from the DI architecture
global $flashMessageService, $database_handler, $container, $serviceProvider;

try {
    $authService = $serviceProvider->getAuth();
} catch (Exception $e) {
    error_log("Critical: Failed to get AuthenticationService instance: " . $e->getMessage());
    die("A critical system error occurred. Please try again later.");
}

// Check authentication
if (!$authService->isAuthenticated()) {
    $flashMessageService->addError('Please log in to access meetings.');
    header("Location: /index.php?page=login");
    exit();
}

$currentUser = $authService->getCurrentUser();

// Check if user can access client area
if (!in_array($currentUser['role'], ['client', 'employee', 'admin'])) {
    header('Location: /index.php?page=home');
    exit;
}

// Create unified navigation
$adminNavigation = new AdminNavigation($authService);

// Get meetings for current user
try {
    $sql = "SELECT cm.*, sp.project_name
            FROM client_meetings cm
            LEFT JOIN studio_projects sp ON cm.project_id = sp.id
            WHERE cm.client_id = ?
            ORDER BY cm.meeting_date ASC";

    $stmt = $database_handler->getConnection()->prepare($sql);
    $stmt->execute([$currentUser['id']]);
    $meetings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Separate upcoming and past meetings
    $upcomingMeetings = [];
    $pastMeetings = [];
    $now = time();

    foreach ($meetings as $meeting) {
        if (strtotime($meeting['meeting_date']) > $now) {
            $upcomingMeetings[] = $meeting;
        } else {
            $pastMeetings[] = $meeting;
        }
    }

} catch (Exception $e) {
    error_log("Error getting meetings: " . $e->getMessage());
    $meetings = [];
    $upcomingMeetings = [];
    $pastMeetings = [];
}

// Get flash messages
$flashMessages = $flashMessageService->getAllMessages();
$pageTitle = 'Meetings & Consultations - Client Portal';

?>

    <link rel="stylesheet" href="/public/assets/css/admin.css">

<!-- Unified Navigation -->
<?= $adminNavigation->render() ?>

<!-- Header -->
<header class="admin-header">
    <div class="admin-header-container">
        <div class="admin-header-content">
            <div class="admin-header-title">
                <i class="admin-header-icon fas fa-calendar-alt"></i>
                <div class="admin-header-text">
                    <h1>Meetings & Consultations</h1>
                    <p>Schedule and manage your meetings with our team</p>
                </div>
            </div>
            <div class="admin-header-actions">
                <a href="/index.php?page=user_meetings_schedule" class="admin-btn admin-btn-primary">
                    <i class="fas fa-plus"></i> Schedule Meeting
                </a>
            </div>
        </div>
    </div>
</header>

<div class="admin-layout-main">
    <div class="admin-content">
<!-- Flash messages handled by global toast system -->

        <!-- Upcoming Meetings -->
        <div class="admin-card">
            <div class="admin-card-header">
                <h3 class="admin-card-title">
                    <i class="fas fa-clock"></i>
                    Upcoming Meetings (<?= count($upcomingMeetings) ?>)
                </h3>
            </div>
            <div class="admin-card-body">
                <?php if (empty($upcomingMeetings)): ?>
                    <div style="text-align: center; padding: 3rem 0;">
                        <i class="fas fa-calendar-plus fa-3x" style="color: var(--admin-text-muted); margin-bottom: 1rem;"></i>
                        <h6 style="color: var(--admin-text-muted); margin-bottom: 0.5rem;">No upcoming meetings</h6>
                        <p style="color: var(--admin-text-muted); margin-bottom: 1.5rem;">Schedule a consultation or project review meeting.</p>
                        <a href="/index.php?page=user_meetings_schedule" class="admin-btn admin-btn-primary">
                            <i class="fas fa-plus"></i> Schedule Meeting
                        </a>
                    </div>
                <?php else: ?>
                    <?php foreach ($upcomingMeetings as $meeting): ?>
                        <div class="admin-card" style="margin-bottom: 1rem; border-left: 3px solid var(--admin-primary);">
                            <div class="admin-card-body">
                                <div style="display: flex; justify-content: space-between; align-items: start;">
                                    <div style="flex: 1;">
                                        <h6 style="margin-bottom: 0.5rem; color: var(--admin-text-primary);"><?= htmlspecialchars($meeting['title']) ?></h6>
                                        <p style="color: var(--admin-text-muted); margin-bottom: 1rem;">
                                            <?= htmlspecialchars($meeting['description'] ?? 'No description provided') ?>
                                        </p>
                                        <div style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
                                            <span class="admin-badge admin-badge-primary">
                                                <i class="fas fa-calendar"></i> <?= date('M j, Y', strtotime($meeting['meeting_date'])) ?>
                                            </span>
                                            <span class="admin-badge admin-badge-info">
                                                <i class="fas fa-clock"></i> <?= date('g:i A', strtotime($meeting['meeting_date'])) ?>
                                            </span>
                                            <span class="admin-badge admin-badge-secondary">
                                                <i class="fas fa-hourglass-half"></i> <?= $meeting['duration_minutes'] ?> minutes
                                            </span>
                                            <?php if ($meeting['project_name']): ?>
                                                <span class="admin-badge admin-badge-success">
                                                    <i class="fas fa-project-diagram"></i> <?= htmlspecialchars($meeting['project_name']) ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                                        <?php if ($meeting['online_link']): ?>
                                            <a href="<?= htmlspecialchars($meeting['online_link']) ?>"
                                               class="admin-btn admin-btn-sm admin-btn-primary" target="_blank">
                                                <i class="fas fa-video"></i> Join
                                            </a>
                                        <?php endif; ?>
                                        <button class="admin-btn admin-btn-sm admin-btn-secondary" onclick="showMeetingDetails(<?= $meeting['id'] ?>)">
                                            <i class="fas fa-info"></i> Details
                                        </button>
                                    </div>
                                </div>
                                <?php if ($meeting['location']): ?>
                                    <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--admin-border);">
                                        <small style="color: var(--admin-text-muted);">
                                            <i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($meeting['location']) ?>
                                        </small>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Past Meetings -->
        <?php if (!empty($pastMeetings)): ?>
            <div class="admin-card">
                <div class="admin-card-header">
                    <h3 class="admin-card-title">
                        <i class="fas fa-history"></i>
                        Meeting History (<?= count($pastMeetings) ?>)
                    </h3>
                </div>
                <div class="admin-card-body">
                    <?php foreach (array_slice($pastMeetings, 0, 5) as $meeting): ?>
                        <div class="admin-card" style="margin-bottom: 1rem; border-left: 3px solid var(--admin-border);">
                            <div class="admin-card-body">
                                <div style="display: flex; justify-content: space-between; align-items: start;">
                                    <div style="flex: 1;">
                                        <h6 style="margin-bottom: 0.5rem; color: var(--admin-text-primary);"><?= htmlspecialchars($meeting['title']) ?></h6>
                                        <div style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
                                            <span class="admin-badge admin-badge-gray">
                                                <i class="fas fa-calendar"></i> <?= date('M j, Y', strtotime($meeting['meeting_date'])) ?>
                                            </span>
                                            <span class="admin-badge admin-badge-<?= getMeetingStatusBadgeClass($meeting['status']) ?>">
                                                <?= ucfirst($meeting['status']) ?>
                                            </span>
                                            <?php if ($meeting['project_name']): ?>
                                                <span class="admin-badge admin-badge-gray">
                                                    <i class="fas fa-project-diagram"></i> <?= htmlspecialchars($meeting['project_name']) ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div>
                                        <button class="admin-btn admin-btn-sm admin-btn-secondary" onclick="showMeetingDetails(<?= $meeting['id'] ?>)">
                                            <i class="fas fa-eye"></i> View
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <?php if (count($pastMeetings) > 5): ?>
                        <div style="text-align: center; padding-top: 1rem;">
                            <button class="admin-btn admin-btn-secondary" onclick="loadMoreMeetings()">
                                <i class="fas fa-chevron-down"></i> Load More
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Sidebar -->
    <div class="admin-sidebar">
        <!-- Quick Actions -->
        <div class="admin-card">
            <div class="admin-card-header">
                <h4 class="admin-card-title">
                    <i class="fas fa-bolt"></i>
                    Quick Actions
                </h4>
            </div>
            <div class="admin-card-body">
                <div style="display: grid; gap: 0.75rem;">
                    <a href="/index.php?page=user_meetings_schedule" class="admin-btn admin-btn-sm admin-btn-primary" style="width: 100%;">
                        <i class="fas fa-calendar-plus"></i> Schedule New Meeting
                    </a>
                    <a href="/index.php?page=user_tickets_create" class="admin-btn admin-btn-sm admin-btn-secondary" style="width: 100%;">
                        <i class="fas fa-ticket-alt"></i> Create Support Ticket
                    </a>
                </div>
            </div>
        </div>

        <!-- Meeting Guidelines -->
        <div class="admin-card">
            <div class="admin-card-header">
                <h4 class="admin-card-title">
                    <i class="fas fa-info-circle"></i>
                    Meeting Guidelines
                </h4>
            </div>
            <div class="admin-card-body">
                <h6>Preparation Tips</h6>
                <ul style="font-size: 0.875rem; margin-bottom: 1rem;">
                    <li>Prepare an agenda or list of topics</li>
                    <li>Have relevant documents ready</li>
                    <li>Test your audio/video beforehand</li>
                    <li>Join 5 minutes early</li>
                </ul>

                <h6>Meeting Types</h6>
                <ul style="font-size: 0.875rem;">
                    <li><strong>Consultation:</strong> Project planning</li>
                    <li><strong>Review:</strong> Progress updates</li>
                    <li><strong>Support:</strong> Technical assistance</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Meeting Details Modal -->
<div class="admin-modal admin-hidden" id="meetingModal">
    <div class="admin-modal-content">
        <div class="admin-modal-header">
            <h5>Meeting Details</h5>
            <button type="button" data-modal-close>
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="admin-modal-body" id="meetingModalBody">
            <div style="text-align: center; padding: 2rem;">
                <div class="admin-spinner"></div>
                <p style="color: var(--admin-text-muted); margin-top: 1rem;">Loading...</p>
            </div>
        </div>
    </div>
</div>

<script src="/public/assets/js/admin.js"></script>
<script>
function showMeetingDetails(meetingId) {
    const modal = document.getElementById('meetingModal');
    modal.classList.remove('admin-hidden');
    modal.style.display = 'flex';

    // Here you would load meeting details via AJAX
    // For now, just show a placeholder
    setTimeout(() => {
        document.getElementById('meetingModalBody').innerHTML = `
            <h6>Meeting Details</h6>
            <p>Detailed meeting information for meeting #${meetingId} would be loaded here via AJAX.</p>
            <div class="admin-flash-message admin-flash-info">
                <i class="fas fa-construction"></i>
                <div>Full meeting details functionality is under development in Phase 8.</div>
            </div>
        `;
    }, 500);
}

function loadMoreMeetings() {
    // Implementation for loading more historical meetings
    window.adminPanel.showFlashMessage('info', 'Load more meetings functionality would be implemented here.');
}
</script>

<?php
function getMeetingStatusBadgeClass($status): string
{
    return match($status) {
        'scheduled' => 'primary',
        'completed' => 'success',
        'cancelled' => 'error',
        'rescheduled' => 'warning',
        default => 'gray'
    };
}
?>
