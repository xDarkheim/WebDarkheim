<?php
/**
 * Create Support Ticket Page - PHASE 8 - DARK ADMIN THEME
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

    <link rel="stylesheet" href="/public/assets/css/admin.css">

<!-- Navigation -->
<nav class="admin-nav">
    <div class="admin-nav-container">
        <a href="/index.php?page=user_dashboard" class="admin-nav-brand">
            <i class="fas fa-ticket-alt"></i>
            Support Portal
        </a>
        <div class="admin-nav-links">
            <a href="/index.php?page=user_dashboard" class="admin-nav-link">
                <i class="fas fa-home"></i> Dashboard
            </a>
            <a href="/index.php?page=user_tickets" class="admin-nav-link">
                <i class="fas fa-list"></i> All Tickets
            </a>
        </div>
    </div>
</nav>

<!-- Header -->
<header class="admin-header">
    <div class="admin-header-container">
        <div class="admin-header-content">
            <div class="admin-header-title">
                <i class="admin-header-icon fas fa-plus-circle"></i>
                <div class="admin-header-text">
                    <h1>Create Support Ticket</h1>
                    <p>Submit a new support request to our team</p>
                </div>
            </div>
            <div class="admin-header-actions">
                <a href="/index.php?page=user_tickets" class="admin-btn admin-btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Tickets
                </a>
            </div>
        </div>
    </div>
</header>

<div class="admin-layout-main">
    <div class="admin-content">
        <!-- Flash Messages -->
        <?php if (!empty($flashMessages)): ?>
            <div class="admin-flash-messages">
                <?php foreach ($flashMessages as $type => $messages): ?>
                    <?php foreach ($messages as $message): ?>
                        <div class="admin-flash-message admin-flash-<?= $type === 'error' ? 'error' : $type ?>">
                            <i class="fas fa-<?= $type === 'error' ? 'exclamation-circle' : ($type === 'success' ? 'check-circle' : 'info-circle') ?>"></i>
                            <div><?= htmlspecialchars($message['text']) ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Create Ticket Form -->
        <div class="admin-card">
            <div class="admin-card-header">
                <h3 class="admin-card-title">
                    <i class="fas fa-edit"></i>
                    New Support Request
                </h3>
            </div>
            <div class="admin-card-body">
                <div class="admin-flash-message admin-flash-info">
                    <i class="fas fa-info-circle"></i>
                    <div><strong>Need Help?</strong> Please provide as much detail as possible to help us assist you quickly.</div>
                </div>

                <form method="POST" action="/index.php?page=user_tickets_create">
                    <div class="admin-grid admin-grid-cols-2">
                        <div class="admin-form-group">
                            <label for="subject" class="admin-label admin-label-required">Subject</label>
                            <input type="text"
                                   class="admin-input"
                                   id="subject"
                                   name="subject"
                                   value="<?= htmlspecialchars($_POST['subject'] ?? '') ?>"
                                   placeholder="Brief description of your issue"
                                   required>
                            <div class="admin-help-text">Please provide a clear, concise subject line</div>
                        </div>
                        <div class="admin-form-group">
                            <label for="priority" class="admin-label">Priority</label>
                            <select class="admin-input admin-select" id="priority" name="priority">
                                <option value="low" <?= ($_POST['priority'] ?? 'medium') === 'low' ? 'selected' : '' ?>>Low - General inquiry</option>
                                <option value="medium" <?= ($_POST['priority'] ?? 'medium') === 'medium' ? 'selected' : '' ?>>Medium - Standard issue</option>
                                <option value="high" <?= ($_POST['priority'] ?? 'medium') === 'high' ? 'selected' : '' ?>>High - Urgent issue</option>
                                <option value="critical" <?= ($_POST['priority'] ?? 'medium') === 'critical' ? 'selected' : '' ?>>Critical - System down</option>
                            </select>
                        </div>
                    </div>

                    <div class="admin-form-group">
                        <label for="category" class="admin-label">Category</label>
                        <select class="admin-input admin-select" id="category" name="category">
                            <option value="general" <?= ($_POST['category'] ?? 'general') === 'general' ? 'selected' : '' ?>>General Support</option>
                            <option value="technical" <?= ($_POST['category'] ?? 'general') === 'technical' ? 'selected' : '' ?>>Technical Issue</option>
                            <option value="billing" <?= ($_POST['category'] ?? 'general') === 'billing' ? 'selected' : '' ?>>Billing & Payments</option>
                            <option value="project" <?= ($_POST['category'] ?? 'general') === 'project' ? 'selected' : '' ?>>Project Related</option>
                            <option value="feature" <?= ($_POST['category'] ?? 'general') === 'feature' ? 'selected' : '' ?>>Feature Request</option>
                            <option value="bug" <?= ($_POST['category'] ?? 'general') === 'bug' ? 'selected' : '' ?>>Bug Report</option>
                        </select>
                    </div>

                    <div class="admin-form-group">
                        <label for="description" class="admin-label admin-label-required">Description</label>
                        <textarea class="admin-input admin-textarea"
                                  id="description"
                                  name="description"
                                  rows="8"
                                  placeholder="Please describe your issue in detail. Include any error messages, steps to reproduce, or relevant information..."
                                  required><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                        <div class="admin-help-text">
                            <strong>Helpful information to include:</strong> Browser/device used, steps to reproduce, error messages, screenshots (if applicable)
                        </div>
                    </div>

                    <div class="admin-card-footer" style="display: flex; justify-content: space-between; align-items: center;">
                        <a href="/index.php?page=user_tickets" class="admin-btn admin-btn-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                        <button type="submit" class="admin-btn admin-btn-primary">
                            <i class="fas fa-paper-plane"></i> Submit Ticket
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="admin-sidebar">
        <!-- Help Information -->
        <div class="admin-card">
            <div class="admin-card-header">
                <h4 class="admin-card-title">
                    <i class="fas fa-question-circle"></i>
                    Getting Help
                </h4>
            </div>
            <div class="admin-card-body">
                <h6>Response Times</h6>
                <ul style="list-style: none; padding: 0;">
                    <li style="margin-bottom: 0.5rem;"><span class="admin-badge admin-badge-error">Critical</span> - Within 1 hour</li>
                    <li style="margin-bottom: 0.5rem;"><span class="admin-badge admin-badge-warning">High</span> - Within 4 hours</li>
                    <li style="margin-bottom: 0.5rem;"><span class="admin-badge admin-badge-primary">Medium</span> - Within 24 hours</li>
                    <li style="margin-bottom: 0.5rem;"><span class="admin-badge admin-badge-gray">Low</span> - Within 48 hours</li>
                </ul>

                <h6 style="margin-top: 1.5rem;">Before Creating a Ticket</h6>
                <ul style="font-size: 0.875rem;">
                    <li>Check our documentation for common solutions</li>
                    <li>Search existing tickets to see if issue was resolved</li>
                    <li>Try refreshing your browser or clearing cache</li>
                    <li>Note any error messages or unusual behavior</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script src="/public/assets/js/admin.js"></script>
<script>
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
        counter.style.cssText = 'color: var(--admin-text-muted); float: right; margin-top: 0.25rem;';
        this.parentNode.appendChild(counter);
    }

    counter.textContent = `${currentLength} / ${maxLength} characters`;
    if (remaining < 100) {
        counter.style.color = 'var(--admin-warning)';
    } else {
        counter.style.color = 'var(--admin-text-muted)';
    }
});
</script>
