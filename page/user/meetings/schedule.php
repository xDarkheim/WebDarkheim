<?php
/**
 * Schedule Meeting Page - PHASE 8
 * Allows clients to schedule meetings and consultations
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

$pageTitle = 'Schedule Meeting';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-light">

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">
                        <i class="fas fa-calendar-plus text-primary"></i>
                        Schedule Meeting
                    </h4>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="fas fa-construction"></i>
                        <strong>Phase 8 Development:</strong> Meeting scheduling functionality is currently under development.
                    </div>

                    <form method="POST" action="/index.php?page=api_schedule_meeting">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="meeting_type" class="form-label">Meeting Type</label>
                                    <select class="form-select" id="meeting_type" name="meeting_type" required>
                                        <option value="">Select meeting type</option>
                                        <option value="consultation">Initial Consultation</option>
                                        <option value="project_review">Project Review</option>
                                        <option value="support">Technical Support</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="preferred_date" class="form-label">Preferred Date</label>
                                    <input type="date" class="form-control" id="preferred_date" name="preferred_date" required>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Meeting Description</label>
                            <textarea class="form-control" id="description" name="description" rows="4" placeholder="Please describe what you'd like to discuss..."></textarea>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-calendar-check"></i> Schedule Meeting
                            </button>
                            <a href="/index.php?page=user_meetings" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Back to Meetings
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
