<?php
/**
 * Create Support Ticket Page - PHASE 8
 * Allows clients to create new support tickets
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

$currentUser = $authService->getCurrentUser();
$pageTitle = 'Create Support Ticket';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject = trim($_POST['subject'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $priority = $_POST['priority'] ?? 'medium';
    $category = $_POST['category'] ?? 'general';

    $errors = [];

    // Validation
    if (empty($subject)) {
        $errors[] = 'Subject is required.';
    } elseif (strlen($subject) < 3) {
        $errors[] = 'Subject must be at least 3 characters long.';
    }

    if (empty($description)) {
        $errors[] = 'Description is required.';
    } elseif (strlen($description) < 10) {
        $errors[] = 'Description must be at least 10 characters long.';
    }

    if (!in_array($priority, ['low', 'medium', 'high', 'critical'])) {
        $errors[] = 'Invalid priority selected.';
    }

    if (empty($errors)) {
        try {
            // Create support ticket
            $sql = "INSERT INTO support_tickets (client_id, subject, description, priority, category, status, created_at) 
                    VALUES (?, ?, ?, ?, ?, 'open', NOW())";
            $stmt = $database_handler->getConnection()->prepare($sql);
            $stmt->execute([
                $authService->getCurrentUserId(),
                $subject,
                $description,
                $priority,
                $category
            ]);

            $ticketId = $database_handler->getConnection()->lastInsertId();

            $flashMessageService->addSuccess('Support ticket created successfully! Ticket ID: #' . $ticketId);
            header("Location: /index.php?page=user_tickets_view&id=" . $ticketId);
            exit();

        } catch (Exception $e) {
            error_log("Error creating support ticket: " . $e->getMessage());
            $flashMessageService->addError('Failed to create support ticket. Please try again.');
        }
    } else {
        foreach ($errors as $error) {
            $flashMessageService->addError($error);
        }
    }
}

// Get flash messages
$flashMessages = $flashMessageService->getAllMessages();
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
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h2">
                    <i class="fas fa-plus-circle text-primary"></i>
                    Create Support Ticket
                </h1>
                <a href="/index.php?page=user_tickets" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Tickets
                </a>
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

            <!-- Create Ticket Form -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-edit text-primary"></i>
                        New Support Request
                    </h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <strong>Need Help?</strong> Please provide as much detail as possible to help us assist you quickly.
                    </div>

                    <form method="POST" action="/index.php?page=user_tickets_create">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label for="subject" class="form-label">Subject <span class="text-danger">*</span></label>
                                    <input type="text"
                                           class="form-control"
                                           id="subject"
                                           name="subject"
                                           value="<?= htmlspecialchars($_POST['subject'] ?? '') ?>"
                                           placeholder="Brief description of your issue"
                                           required>
                                    <div class="form-text">Please provide a clear, concise subject line</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="priority" class="form-label">Priority</label>
                                    <select class="form-select" id="priority" name="priority">
                                        <option value="low" <?= ($_POST['priority'] ?? 'medium') === 'low' ? 'selected' : '' ?>>Low - General inquiry</option>
                                        <option value="medium" <?= ($_POST['priority'] ?? 'medium') === 'medium' ? 'selected' : '' ?>>Medium - Standard issue</option>
                                        <option value="high" <?= ($_POST['priority'] ?? 'medium') === 'high' ? 'selected' : '' ?>>High - Urgent issue</option>
                                        <option value="critical" <?= ($_POST['priority'] ?? 'medium') === 'critical' ? 'selected' : '' ?>>Critical - System down</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="category" class="form-label">Category</label>
                            <select class="form-select" id="category" name="category">
                                <option value="general" <?= ($_POST['category'] ?? 'general') === 'general' ? 'selected' : '' ?>>General Support</option>
                                <option value="technical" <?= ($_POST['category'] ?? 'general') === 'technical' ? 'selected' : '' ?>>Technical Issue</option>
                                <option value="billing" <?= ($_POST['category'] ?? 'general') === 'billing' ? 'selected' : '' ?>>Billing & Payments</option>
                                <option value="project" <?= ($_POST['category'] ?? 'general') === 'project' ? 'selected' : '' ?>>Project Related</option>
                                <option value="feature" <?= ($_POST['category'] ?? 'general') === 'feature' ? 'selected' : '' ?>>Feature Request</option>
                                <option value="bug" <?= ($_POST['category'] ?? 'general') === 'bug' ? 'selected' : '' ?>>Bug Report</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
                            <textarea class="form-control"
                                      id="description"
                                      name="description"
                                      rows="8"
                                      placeholder="Please describe your issue in detail. Include any error messages, steps to reproduce, or relevant information..."
                                      required><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                            <div class="form-text">
                                <strong>Helpful information to include:</strong> Browser/device used, steps to reproduce, error messages, screenshots (if applicable)
                            </div>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="/index.php?page=user_tickets" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane"></i> Submit Ticket
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Help Information -->
            <div class="card mt-4">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-question-circle text-info"></i>
                        Getting Help
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Response Times</h6>
                            <ul class="list-unstyled">
                                <li><span class="badge bg-danger">Critical</span> - Within 1 hour</li>
                                <li><span class="badge bg-warning">High</span> - Within 4 hours</li>
                                <li><span class="badge bg-primary">Medium</span> - Within 24 hours</li>
                                <li><span class="badge bg-secondary">Low</span> - Within 48 hours</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h6>Before Creating a Ticket</h6>
                            <ul class="small">
                                <li>Check our documentation for common solutions</li>
                                <li>Search existing tickets to see if issue was resolved</li>
                                <li>Try refreshing your browser or clearing cache</li>
                                <li>Note any error messages or unusual behavior</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Auto-dismiss alerts after 5 seconds
setTimeout(function() {
    var alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert) {
        var bsAlert = new bootstrap.Alert(alert);
        bsAlert.close();
    });
}, 5000);

// Character counter for description
document.getElementById('description').addEventListener('input', function() {
    const maxLength = 2000;
    const currentLength = this.value.length;
    const remaining = maxLength - currentLength;

    // Add character counter if it doesn't exist
    let counter = document.getElementById('desc-counter');
    if (!counter) {
        counter = document.createElement('small');
        counter.id = 'desc-counter';
        counter.className = 'text-muted float-end';
        this.parentNode.appendChild(counter);
    }

    counter.textContent = `${currentLength} / ${maxLength} characters`;
    counter.className = remaining < 100 ? 'text-warning float-end' : 'text-muted float-end';
});
</script>

</body>
</html>
