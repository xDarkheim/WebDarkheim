<?php

/**
 * Create Support Ticket - Client Portal - PHASE 8
 * Page for creating new support tickets
 */

declare(strict_types=1);

// Use global services from the new DI architecture
global $flashMessageService, $database_handler, $container, $serviceProvider;

use App\Application\Components\AdminNavigation;

// Get AuthenticationService
try {
    $authService = $serviceProvider->getAuth();
} catch (Exception $e) {
    error_log("Critical: Failed to get AuthenticationService instance: " . $e->getMessage());
    die("A critical system error occurred. Please try again later.");
}

// Check authentication
if (!$authService->isAuthenticated()) {
    $flashMessageService->addError('Please log in to create a ticket.');
    header("Location: /index.php?page=login");
    exit();
}

// Get user data
$current_user_id = $authService->getCurrentUserId();
$current_user_role = $authService->getCurrentUserRole();
$currentUser = $authService->getCurrentUser();

// Check if user can access client area
if (!in_array($current_user_role, ['client', 'employee', 'admin'])) {
    header('Location: /index.php?page=home');
    exit;
}

use App\Domain\Models\Ticket;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // CSRF token validation
        $token = $_POST['csrf_token'] ?? '';
        if (empty($token) || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
            throw new Exception('Invalid CSRF token.');
        }

        $subject = trim($_POST['subject'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $priority = $_POST['priority'] ?? 'medium';
        $category = $_POST['category'] ?? 'general';

        // Validation
        $errors = [];
        if (empty($subject)) {
            $errors[] = 'Subject is required.';
        }
        if (empty($description)) {
            $errors[] = 'Description is required.';
        }

        if (empty($errors)) {
            // Create ticket
            $ticketData = [
                'user_id' => $current_user_id,
                'subject' => $subject,
                'description' => $description,
                'priority' => $priority,
                'category' => $category
            ];

            if (Ticket::create($database_handler, $ticketData)) {
                $flashMessageService->addSuccess('Support ticket created successfully. Our team will respond soon.');
                header('Location: /index.php?page=user_tickets');
                exit();
            } else {
                $flashMessageService->addError('Error creating ticket. Please try again.');
            }
        } else {
            foreach ($errors as $error) {
                $flashMessageService->addError($error);
            }
        }
    } catch (Exception $e) {
        error_log("Error creating ticket: " . $e->getMessage());
        $flashMessageService->addError($e->getMessage());
    }
}

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$priorities = Ticket::getPriorities();
$categories = Ticket::getCategories();

// Get flash messages
$flashMessages = $flashMessageService->getAllMessages();

// Create unified navigation
$adminNavigation = new AdminNavigation($authService);
?>

<!-- Admin Dark Theme Styles -->
<link rel="stylesheet" href="/public/assets/css/admin.css">

<!-- Unified Navigation -->
<?= $adminNavigation->render() ?>

<div class="admin-container">
    <!-- Header -->
    <div class="admin-header">
        <div class="admin-header-container">
            <div class="admin-header-content">
                <div class="admin-header-title">
                    <i class="admin-header-icon fas fa-plus"></i>
                    <div class="admin-header-text">
                        <h1>Create Support Ticket</h1>
                        <p>Submit a new support request and get help from our team</p>
                    </div>
                </div>

                <div class="admin-header-actions">
                    <a href="/index.php?page=user_tickets" class="admin-btn admin-btn-secondary">
                        <i class="fas fa-arrow-left"></i>Back to Tickets
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="admin-main">
        <div style="max-width: 800px; margin: 0 auto; padding: 0 1rem;">
            <div class="admin-card">
                <div class="admin-card-header">
                    <h3>New Support Ticket</h3>
                    <p style="color: var(--admin-text-secondary); margin: 0; font-size: 0.875rem;">
                        Please provide detailed information about your request to help us assist you better.
                    </p>
                </div>
                <div class="admin-card-body">
                    <form method="POST" class="admin-form">
                        <div class="admin-form-group">
                            <label for="subject" class="admin-form-label">Subject *</label>
                            <input
                                type="text"
                                id="subject"
                                name="subject"
                                class="admin-form-control"
                                value="<?= htmlspecialchars($_POST['subject'] ?? '') ?>"
                                placeholder="Brief description of your issue"
                                required
                            >
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                            <div class="admin-form-group">
                                <label for="priority" class="admin-form-label">Priority</label>
                                <select id="priority" name="priority" class="admin-form-control">
                                    <?php foreach ($priorities as $key => $label): ?>
                                        <option value="<?= $key ?>" <?= ($_POST['priority'] ?? 'medium') === $key ? 'selected' : '' ?>>
                                            <?= $label ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="admin-form-group">
                                <label for="category" class="admin-form-label">Category</label>
                                <select id="category" name="category" class="admin-form-control">
                                    <?php foreach ($categories as $key => $label): ?>
                                        <option value="<?= $key ?>" <?= ($_POST['category'] ?? 'general') === $key ? 'selected' : '' ?>>
                                            <?= $label ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="admin-form-group">
                            <label for="description" class="admin-form-label">Description *</label>
                            <textarea
                                id="description"
                                name="description"
                                class="admin-form-control"
                                rows="6"
                                placeholder="Please provide detailed information about your request..."
                                required
                            ><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                        </div>

                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

                        <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 2rem;">
                            <a href="/index.php?page=user_tickets" class="admin-btn admin-btn-secondary">
                                <i class="fas fa-times"></i>Cancel
                            </a>
                            <button type="submit" class="admin-btn admin-btn-primary">
                                <i class="fas fa-paper-plane"></i>Submit Ticket
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Help Section -->
            <div class="admin-card" style="margin-top: 1.5rem;">
                <div class="admin-card-header">
                    <h3>Getting Better Support</h3>
                </div>
                <div class="admin-card-body">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem;">
                        <div style="padding: 1rem; border: 1px solid var(--admin-border-color); border-radius: 8px;">
                            <h4 style="margin: 0 0 0.5rem 0; color: var(--admin-primary-light);">
                                <i class="fas fa-info-circle" style="margin-right: 0.5rem;"></i>Be Specific
                            </h4>
                            <p style="margin: 0; color: var(--admin-text-secondary); font-size: 0.875rem;">
                                Include specific error messages, steps to reproduce, and expected behavior.
                            </p>
                        </div>
                        <div style="padding: 1rem; border: 1px solid var(--admin-border-color); border-radius: 8px;">
                            <h4 style="margin: 0 0 0.5rem 0; color: var(--admin-primary-light);">
                                <i class="fas fa-clock" style="margin-right: 0.5rem;"></i>Response Times
                            </h4>
                            <p style="margin: 0; color: var(--admin-text-secondary); font-size: 0.875rem;">
                                Normal: 24-48h, High: 4-8h, Urgent: 1-2h during business hours.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
