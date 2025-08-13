<?php

/**
 * Meetings & Consultations - Client Portal
 * Schedule and manage meetings with the studio team
 *
 * @author GitHub Copilot
 */

declare(strict_types=1);

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

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .meeting-card {
            transition: all 0.3s ease;
            border: none;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .meeting-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        .meeting-upcoming {
            border-left: 4px solid #0d6efd;
        }
        .meeting-past {
            border-left: 4px solid #6c757d;
        }
        .time-badge {
            font-size: 0.9rem;
        }
    </style>
</head>
<body class="bg-light">

<div class="container py-4">
    <!-- Breadcrumb Navigation -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/index.php?page=dashboard">Dashboard</a></li>
            <li class="breadcrumb-item active">Meetings & Consultations</li>
        </ol>
    </nav>

    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0">
                        <i class="fas fa-calendar-alt text-primary"></i>
                        Meetings & Consultations
                    </h1>
                    <p class="text-muted">Schedule and manage your meetings with our team</p>
                </div>
                <a href="/index.php?page=user_meetings_schedule" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Schedule Meeting
                </a>
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

    <!-- Upcoming Meetings -->
    <div class="card meeting-card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="fas fa-clock text-primary"></i>
                Upcoming Meetings (<?= count($upcomingMeetings) ?>)
            </h5>
        </div>
        <div class="card-body">
            <?php if (empty($upcomingMeetings)): ?>
                <div class="text-center py-4">
                    <i class="fas fa-calendar-plus fa-3x text-muted mb-3"></i>
                    <h6 class="text-muted">No upcoming meetings</h6>
                    <p class="text-muted">Schedule a consultation or project review meeting.</p>
                    <a href="/index.php?page=user_meetings_schedule" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Schedule Meeting
                    </a>
                </div>
            <?php else: ?>
                <?php foreach ($upcomingMeetings as $meeting): ?>
                    <div class="card meeting-upcoming mb-3">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-md-8">
                                    <h6 class="card-title mb-1"><?= htmlspecialchars($meeting['title']) ?></h6>
                                    <p class="card-text text-muted mb-2">
                                        <?= htmlspecialchars($meeting['description'] ?? 'No description provided') ?>
                                    </p>
                                    <div class="d-flex flex-wrap gap-2">
                                        <span class="badge bg-primary time-badge">
                                            <i class="fas fa-calendar"></i> <?= date('M j, Y', strtotime($meeting['meeting_date'])) ?>
                                        </span>
                                        <span class="badge bg-info time-badge">
                                            <i class="fas fa-clock"></i> <?= date('g:i A', strtotime($meeting['meeting_date'])) ?>
                                        </span>
                                        <span class="badge bg-secondary time-badge">
                                            <i class="fas fa-hourglass-half"></i> <?= $meeting['duration_minutes'] ?> minutes
                                        </span>
                                        <?php if ($meeting['project_name']): ?>
                                            <span class="badge bg-success time-badge">
                                                <i class="fas fa-project-diagram"></i> <?= htmlspecialchars($meeting['project_name']) ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="col-md-4 text-md-end">
                                    <div class="btn-group btn-group-sm">
                                        <?php if ($meeting['online_link']): ?>
                                            <a href="<?= htmlspecialchars($meeting['online_link']) ?>" 
                                               class="btn btn-primary" target="_blank">
                                                <i class="fas fa-video"></i> Join
                                            </a>
                                        <?php endif; ?>
                                        <button class="btn btn-outline-secondary" onclick="showMeetingDetails(<?= $meeting['id'] ?>)">
                                            <i class="fas fa-info"></i> Details
                                        </button>
                                    </div>
                                    <?php if ($meeting['location']): ?>
                                        <div class="mt-2">
                                            <small class="text-muted">
                                                <i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($meeting['location']) ?>
                                            </small>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Past Meetings -->
    <?php if (!empty($pastMeetings)): ?>
        <div class="card meeting-card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-history text-secondary"></i>
                    Meeting History (<?= count($pastMeetings) ?>)
                </h5>
            </div>
            <div class="card-body">
                <?php foreach (array_slice($pastMeetings, 0, 5) as $meeting): ?>
                    <div class="card meeting-past mb-3">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-md-10">
                                    <h6 class="card-title mb-1"><?= htmlspecialchars($meeting['title']) ?></h6>
                                    <div class="d-flex flex-wrap gap-2">
                                        <span class="badge bg-secondary time-badge">
                                            <i class="fas fa-calendar"></i> <?= date('M j, Y', strtotime($meeting['meeting_date'])) ?>
                                        </span>
                                        <span class="badge <?= getMeetingStatusBadgeClass($meeting['status']) ?> time-badge">
                                            <?= ucfirst($meeting['status']) ?>
                                        </span>
                                        <?php if ($meeting['project_name']): ?>
                                            <span class="badge bg-light text-dark time-badge">
                                                <i class="fas fa-project-diagram"></i> <?= htmlspecialchars($meeting['project_name']) ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="col-md-2 text-md-end">
                                    <button class="btn btn-outline-secondary btn-sm" onclick="showMeetingDetails(<?= $meeting['id'] ?>)">
                                        <i class="fas fa-eye"></i> View
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <?php if (count($pastMeetings) > 5): ?>
                    <div class="text-center">
                        <button class="btn btn-outline-primary" onclick="loadMoreMeetings()">
                            <i class="fas fa-chevron-down"></i> Load More
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Meeting Details Modal -->
<div class="modal fade" id="meetingModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Meeting Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="meetingModalBody">
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function showMeetingDetails(meetingId) {
    const modal = new bootstrap.Modal(document.getElementById('meetingModal'));
    modal.show();
    
    // Here you would load meeting details via AJAX
    // For now, just show a placeholder
    setTimeout(() => {
        document.getElementById('meetingModalBody').innerHTML = `
            <h6>Meeting Details</h6>
            <p>Detailed meeting information for meeting #${meetingId} would be loaded here via AJAX.</p>
        `;
    }, 500);
}

function loadMoreMeetings() {
    // Implementation for loading more historical meetings
    alert('Load more meetings functionality would be implemented here.');
}
</script>

</body>
</html>

<?php
function getMeetingStatusBadgeClass($status): string
{
    return match($status) {
        'scheduled' => 'bg-info',
        'completed' => 'bg-success',
        'cancelled' => 'bg-danger',
        'rescheduled' => 'bg-warning',
        default => 'bg-secondary'
    };
}
?>
