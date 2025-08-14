<?php

/**
 * View Support Ticket - Client Portal - PHASE 8
 * Page for viewing and responding to support tickets
 */

declare(strict_types=1);

use App\Application\Components\AdminNavigation;

// Use global services from the new DI architecture
global $flashMessageService, $database_handler, $container, $serviceProvider;

// Get AuthenticationService
try {
    $authService = $serviceProvider->getAuth();
} catch (Exception $e) {
    error_log("Critical: Failed to get AuthenticationService instance: " . $e->getMessage());
    die("A critical system error occurred. Please try again later.");
}

// Check authentication
if (!$authService->isAuthenticated()) {
    $flashMessageService->addError('Please log in to view tickets.');
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

// Create unified navigation
$adminNavigation = new AdminNavigation($authService);

use App\Domain\Models\Ticket;

// Get ticket ID
$ticketId = (int)($_GET['id'] ?? 0);
if (!$ticketId) {
    $flashMessageService->addError('Invalid ticket ID.');
    header('Location: /index.php?page=user_tickets');
    exit();
}

// Get ticket details
$ticket = Ticket::findById($database_handler, $ticketId);
if (!$ticket) {
    $flashMessageService->addError('Ticket not found.');
    header('Location: /index.php?page=user_tickets');
    exit();
}

// Check if user can view this ticket
if ($ticket['user_id'] != $current_user_id && !in_array($current_user_role, ['admin', 'employee'])) {
    $flashMessageService->addError('You do not have permission to view this ticket.');
    header('Location: /index.php?page=user_tickets');
    exit();
}

// Get ticket messages
try {
    $sql = "SELECT tm.*, u.username, u.role 
            FROM ticket_messages tm 
            JOIN users u ON tm.user_id = u.id 
            WHERE tm.ticket_id = ? AND (tm.is_internal = 0 OR ? IN ('admin', 'employee'))
            ORDER BY tm.created_at ASC";
    $stmt = $database_handler->getConnection()->prepare($sql);
    $stmt->execute([$ticketId, $current_user_role]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Error loading ticket messages: " . $e->getMessage());
    $messages = [];
}

$statuses = Ticket::getStatuses();
$priorities = Ticket::getPriorities();
$categories = Ticket::getCategories();

// Get flash messages
$flashMessages = $flashMessageService->getAllMessages();
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
                    <i class="admin-header-icon fas fa-ticket-alt"></i>
                    <div class="admin-header-text">
                        <h1>Ticket #<?= $ticket['id'] ?></h1>
                        <p><?= htmlspecialchars($ticket['subject']) ?></p>
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
        <div style="max-width: 1000px; margin: 0 auto; padding: 0 1rem;">

            <!-- Ticket Info -->
            <div class="admin-card" style="margin-bottom: 1.5rem;">
                <div class="admin-card-body" style="padding: 1rem;">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                        <div>
                            <strong>Status:</strong>
                            <?php
                            $statusColors = [
                                'open' => 'success',
                                'in_progress' => 'info',
                                'waiting_client' => 'warning',
                                'resolved' => 'primary',
                                'closed' => 'secondary'
                            ];
                            $statusColor = $statusColors[$ticket['status']] ?? 'secondary';
                            ?>
                            <span class="admin-badge admin-badge-<?= $statusColor ?>" style="margin-left: 0.5rem;">
                                <?= $statuses[$ticket['status']] ?? $ticket['status'] ?>
                            </span>
                        </div>

                        <div>
                            <strong>Priority:</strong>
                            <?php
                            $priorityColors = [
                                'low' => 'secondary',
                                'medium' => 'info',
                                'high' => 'warning',
                                'urgent' => 'error'
                            ];
                            $priorityColor = $priorityColors[$ticket['priority']] ?? 'secondary';
                            ?>
                            <span class="admin-badge admin-badge-<?= $priorityColor ?>" style="margin-left: 0.5rem;">
                                <?= $priorities[$ticket['priority']] ?? $ticket['priority'] ?>
                            </span>
                        </div>

                        <div>
                            <strong>Category:</strong>
                            <span style="margin-left: 0.5rem;"><?= $categories[$ticket['category']] ?? $ticket['category'] ?></span>
                        </div>

                        <div>
                            <strong>Created:</strong>
                            <span style="margin-left: 0.5rem;"><?= date('M j, Y g:i A', strtotime($ticket['created_at'])) ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Messages -->
            <div class="admin-card">
                <div class="admin-card-header">
                    <h3>Conversation</h3>
                </div>
                <div class="admin-card-body" style="padding: 0;">

                    <!-- Original Ticket Description -->
                    <div style="padding: 1.5rem; border-bottom: 1px solid var(--admin-border-color);">
                        <div style="display: flex; align-items: center; margin-bottom: 1rem;">
                            <div style="width: 40px; height: 40px; border-radius: 50%; background: var(--admin-primary-bg); display: flex; align-items: center; justify-content: center; margin-right: 1rem;">
                                <i class="fas fa-user" style="color: var(--admin-primary-light);"></i>
                            </div>
                            <div>
                                <strong>You</strong>
                                <span style="color: var(--admin-text-secondary); font-size: 0.875rem;">
                                    created this ticket on <?= date('M j, Y g:i A', strtotime($ticket['created_at'])) ?>
                                </span>
                            </div>
                        </div>
                        <div style="margin-left: 56px;">
                            <p style="margin: 0; white-space: pre-wrap;"><?= htmlspecialchars($ticket['description']) ?></p>
                        </div>
                    </div>

                    <!-- Messages -->
                    <?php if (empty($messages)): ?>
                        <div style="padding: 2rem; text-align: center; color: var(--admin-text-secondary);">
                            <i class="fas fa-comments" style="font-size: 2rem; margin-bottom: 1rem; opacity: 0.3;"></i>
                            <p>No responses yet. Our team will reply soon.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($messages as $message): ?>
                            <?php $isStaff = in_array($message['role'], ['admin', 'employee']); ?>
                            <div style="padding: 1.5rem; border-bottom: 1px solid var(--admin-border-color);">
                                <div style="display: flex; align-items: center; margin-bottom: 1rem;">
                                    <div style="width: 40px; height: 40px; border-radius: 50%; background: <?= $isStaff ? 'var(--admin-success-bg)' : 'var(--admin-primary-bg)' ?>; display: flex; align-items: center; justify-content: center; margin-right: 1rem;">
                                        <i class="fas fa-<?= $isStaff ? 'headset' : 'user' ?>" style="color: <?= $isStaff ? 'var(--admin-success-light)' : 'var(--admin-primary-light)' ?>;"></i>
                                    </div>
                                    <div>
                                        <strong><?= $isStaff ? 'Support Team' : htmlspecialchars($message['username']) ?></strong>
                                        <?php if ($isStaff): ?>
                                            <span class="admin-badge admin-badge-success" style="margin-left: 0.5rem; font-size: 0.6rem;">Staff</span>
                                        <?php endif; ?>
                                        <span style="color: var(--admin-text-secondary); font-size: 0.875rem; margin-left: 0.5rem;">
                                            <?= date('M j, Y g:i A', strtotime($message['created_at'])) ?>
                                        </span>
                                    </div>
                                </div>
                                <div style="margin-left: 56px;">
                                    <p style="margin: 0; white-space: pre-wrap;"><?= htmlspecialchars($message['message']) ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <!-- Reply Form (only if ticket is not closed) -->
                    <?php if ($ticket['status'] !== 'closed'): ?>
                        <div style="padding: 1.5rem; background: var(--admin-card-bg);">
                            <h4 style="margin: 0 0 1rem 0;">Add Response</h4>
                            <form method="POST" action="/index.php?page=api_tickets_add_message" class="admin-form">
                                <input type="hidden" name="ticket_id" value="<?= $ticket['id'] ?>">
                                <div class="admin-form-group">
                                    <textarea
                                        name="message"
                                        class="admin-form-control"
                                        rows="4"
                                        placeholder="Type your response..."
                                        required
                                    ></textarea>
                                </div>
                                <div style="display: flex; justify-content: flex-end;">
                                    <button type="submit" class="admin-btn admin-btn-primary">
                                        <i class="fas fa-paper-plane"></i>Send Response
                                    </button>
                                </div>
                            </form>
                        </div>
                    <?php else: ?>
                        <div style="padding: 1.5rem; background: var(--admin-secondary-bg); text-align: center;">
                            <i class="fas fa-lock" style="color: var(--admin-text-secondary); margin-right: 0.5rem;"></i>
                            <span style="color: var(--admin-text-secondary);">This ticket has been closed. Contact support if you need to reopen it.</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
