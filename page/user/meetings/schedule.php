<?php
/**
 * Schedule Meeting Page - PHASE 8 - DARK ADMIN THEME
 * Allows clients to schedule meetings and consultations
 */

declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/includes/bootstrap.php';

use App\Application\Components\AdminNavigation;

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

// Create unified navigation
$adminNavigation = new AdminNavigation($authService);

$pageTitle = 'Schedule Meeting';
?>

    <link rel="stylesheet" href="/public/assets/css/admin.css">

<!-- Unified Navigation -->
<?= $adminNavigation->render() ?>

<!-- Header -->
<header class="admin-header">
    <div class="admin-header-container">
        <div class="admin-header-content">
            <div class="admin-header-title">
                <i class="admin-header-icon fas fa-calendar-plus"></i>
                <div class="admin-header-text">
                    <h1>Schedule Meeting</h1>
                    <p>Book a consultation or project meeting with our team</p>
                </div>
            </div>
            <div class="admin-header-actions">
                <a href="/index.php?page=user_meetings" class="admin-btn admin-btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Meetings
                </a>
            </div>
        </div>
    </div>
</header>

<div class="admin-layout-main">
    <div class="admin-content">
        <!-- Development Notice -->
        <div class="admin-flash-message admin-flash-info">
            <i class="fas fa-construction"></i>
            <div><strong>Phase 8 Development:</strong> Meeting scheduling functionality is currently under development.</div>
        </div>

        <div class="admin-card">
            <div class="admin-card-header">
                <h3 class="admin-card-title">
                    <i class="fas fa-calendar-check"></i>
                    Schedule New Meeting
                </h3>
            </div>
            <div class="admin-card-body">
                <form method="POST" action="/index.php?page=api_schedule_meeting">
                    <div class="admin-grid admin-grid-cols-2">
                        <div class="admin-form-group">
                            <label for="meeting_type" class="admin-label admin-label-required">Meeting Type</label>
                            <select class="admin-input admin-select" id="meeting_type" name="meeting_type" required>
                                <option value="">Select meeting type</option>
                                <option value="consultation">Initial Consultation</option>
                                <option value="project_review">Project Review</option>
                                <option value="support">Technical Support</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="admin-form-group">
                            <label for="preferred_date" class="admin-label admin-label-required">Preferred Date</label>
                            <input type="date" class="admin-input" id="preferred_date" name="preferred_date" required>
                        </div>
                    </div>

                    <div class="admin-form-group">
                        <label for="description" class="admin-label">Meeting Description</label>
                        <textarea class="admin-input admin-textarea" id="description" name="description" rows="4"
                                  placeholder="Please describe what you'd like to discuss..."></textarea>
                        <div class="admin-help-text">Provide details about the topics you'd like to cover in the meeting</div>
                    </div>

                    <div class="admin-card-footer" style="display: flex; gap: 0.75rem; justify-content: flex-end;">
                        <a href="/index.php?page=user_meetings" class="admin-btn admin-btn-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                        <button type="submit" class="admin-btn admin-btn-primary">
                            <i class="fas fa-calendar-check"></i> Schedule Meeting
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="admin-sidebar">
        <!-- Meeting Guidelines -->
        <div class="admin-card">
            <div class="admin-card-header">
                <h4 class="admin-card-title">
                    <i class="fas fa-info-circle"></i>
                    Meeting Guidelines
                </h4>
            </div>
            <div class="admin-card-body">
                <h6>Meeting Types</h6>
                <ul style="font-size: 0.875rem; margin-bottom: 1.5rem;">
                    <li><strong>Initial Consultation</strong> - Discuss project requirements</li>
                    <li><strong>Project Review</strong> - Review progress and milestones</li>
                    <li><strong>Technical Support</strong> - Resolve technical issues</li>
                    <li><strong>Other</strong> - Custom meeting purpose</li>
                </ul>

                <h6>Availability</h6>
                <ul style="font-size: 0.875rem;">
                    <li>Monday - Friday: 9:00 AM - 6:00 PM</li>
                    <li>Time zone: EST</li>
                    <li>Meetings typically 30-60 minutes</li>
                </ul>
            </div>
        </div>

        <!-- Contact Information -->
        <div class="admin-card">
            <div class="admin-card-header">
                <h4 class="admin-card-title">
                    <i class="fas fa-phone"></i>
                    Need Immediate Help?
                </h4>
            </div>
            <div class="admin-card-body">
                <p style="font-size: 0.875rem; margin-bottom: 1rem;">
                    For urgent matters, you can also:
                </p>
                <div style="display: grid; gap: 0.5rem;">
                    <a href="/index.php?page=user_tickets_create" class="admin-btn admin-btn-sm admin-btn-primary">
                        <i class="fas fa-ticket-alt"></i> Create Support Ticket
                    </a>
                    <a href="mailto:support@darkheim.net" class="admin-btn admin-btn-sm admin-btn-secondary">
                        <i class="fas fa-envelope"></i> Send Email
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="module" src="/public/assets/js/admin.js"></script>
<script>
// Set minimum date to today
document.getElementById('preferred_date').min = new Date().toISOString().split('T')[0];
</script>