<?php

/**
 * View Support Ticket - Client Portal
 * Detailed view of a support ticket with messaging capability
 *
 * @author GitHub Copilot
 */

declare(strict_types=1);

// Include bootstrap
require_once __DIR__ . '/../../../includes/bootstrap.php';

// Get global services
global $container;

try {
    // Get ServiceProvider
    $serviceProvider = \App\Application\Core\ServiceProvider::getInstance($container);

    // Get required services
    $authService = $serviceProvider->getAuth();
    $database = $serviceProvider->getDatabase();
    $flashService = $serviceProvider->getFlashMessage();
    $logger = $serviceProvider->getLogger();

    // Check authentication
    if (!$authService->isAuthenticated()) {
        header('Location: /index.php?page=login');
        exit;
    }

    $currentUser = $authService->getCurrentUser();

    // Check if user can access client area
    if (!in_array($currentUser['role'], ['client', 'employee', 'admin'])) {
        header('Location: /index.php?page=home');
        exit;
    }

    // Get ticket ID from URL
    $ticketId = (int)($_GET['id'] ?? 0);
    if (!$ticketId) {
        header('Location: /index.php?page=user_tickets');
        exit;
    }

    // Create ticket controller
    $ticketController = new \App\Application\Controllers\SupportTicketController(
        $database,
        $authService,
        $flashService,
        $logger
    );

    // Get ticket details
    $_GET['id'] = $ticketId; // Ensure ID is in $_GET for controller
    $ticketResponse = $ticketController->getTicketDetails();

    if (!$ticketResponse['success']) {
        header('Location: /index.php?page=user_tickets');
        exit;
    }

    $ticket = $ticketResponse['ticket'];
    $messages = $ticketResponse['messages'] ?? [];
    $canEdit = $ticketResponse['can_edit'] ?? false;

    // Get flash messages
    $flashMessages = $flashService->getAllMessages();

    // Set page title
    $pageTitle = 'Ticket #' . $ticket['id'] . ' - ' . htmlspecialchars($ticket['subject']);

} catch (Exception $e) {
    // Handle critical errors
    if (isset($logger)) {
        $logger->critical("Critical error in ticket view page: " . $e->getMessage());
    }

    header('Location: /index.php?page=user_tickets');
    exit;
}

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
                <i class="admin-header-icon fas fa-ticket-alt"></i>
                <div class="admin-header-text">
                    <h1>Ticket #<?= $ticket['id'] ?> - <?= htmlspecialchars($ticket['subject']) ?></h1>
                    <p>Created <?= date('M j, Y \a\t g:i A', strtotime($ticket['created_at'])) ?> • Last updated <?= date('M j, Y \a\t g:i A', strtotime($ticket['updated_at'])) ?></p>
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

        <!-- Original Request -->
        <div class="admin-card">
            <div class="admin-card-header">
                <h3 class="admin-card-title">
                    <i class="fas fa-file-alt"></i>
                    Original Request
                </h3>
            </div>
            <div class="admin-card-body">
                <div style="padding: 1rem; background: var(--admin-bg-secondary); border-radius: var(--admin-border-radius); border-left: 3px solid var(--admin-primary);">
                    <p style="margin: 0;"><?= nl2br(htmlspecialchars($ticket['description'])) ?></p>
                </div>
            </div>
        </div>

        <!-- Messages -->
        <div class="admin-card">
            <div class="admin-card-header" style="display: flex; justify-content: space-between; align-items: center;">
                <h3 class="admin-card-title">
                    <i class="fas fa-comments"></i>
                    Conversation (<?= count($messages) ?>)
                </h3>
                <button class="admin-btn admin-btn-sm admin-btn-secondary" onclick="refreshMessages()">
                    <i class="fas fa-sync-alt"></i> Refresh
                </button>
            </div>
            <div class="admin-card-body" id="messagesContainer">
                <?php if (empty($messages)): ?>
                    <div style="text-align: center; padding: 3rem 0; color: var(--admin-text-muted);">
                        <i class="fas fa-comments fa-3x" style="margin-bottom: 1rem;"></i>
                        <p>No messages yet. Start the conversation below.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($messages as $message): ?>
                        <div style="padding: 1rem; margin-bottom: 1rem; border-radius: var(--admin-border-radius); <?= $message['is_internal'] ? 'background: var(--admin-warning-bg); border-left: 3px solid var(--admin-warning);' : ($message['role'] === 'client' ? 'background: var(--admin-primary-bg); border-left: 3px solid var(--admin-primary);' : 'background: var(--admin-success-bg); border-left: 3px solid var(--admin-success);') ?>">
                            <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 0.75rem;">
                                <div style="display: flex; align-items: center;">
                                    <i class="fas fa-user-circle" style="font-size: 1.25rem; margin-right: 0.75rem; color: <?= $message['role'] === 'client' ? 'var(--admin-primary)' : 'var(--admin-success)' ?>;"></i>
                                    <div>
                                        <strong><?= htmlspecialchars($message['username']) ?></strong>
                                        <span class="admin-badge admin-badge-<?= $message['role'] === 'client' ? 'primary' : 'success' ?>" style="margin-left: 0.5rem;">
                                            <?= ucfirst($message['role']) ?>
                                        </span>
                                        <?php if ($message['is_internal']): ?>
                                            <span class="admin-badge admin-badge-warning" style="margin-left: 0.25rem;">Internal</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <small style="color: var(--admin-text-muted);">
                                    <?= date('M j, Y \a\t g:i A', strtotime($message['created_at'])) ?>
                                </small>
                            </div>
                            <div>
                                <?= nl2br(htmlspecialchars($message['message'])) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Reply Form -->
        <?php if (in_array($ticket['status'], ['open', 'in_progress', 'waiting_client'])): ?>
            <div class="admin-card">
                <div class="admin-card-header">
                    <h3 class="admin-card-title">
                        <i class="fas fa-reply"></i>
                        Add Response
                    </h3>
                </div>
                <div class="admin-card-body">
                    <form id="replyForm">
                        <input type="hidden" name="ticket_id" value="<?= $ticket['id'] ?>">

                        <div class="admin-form-group">
                            <label for="message" class="admin-label">Your Message</label>
                            <textarea class="admin-input admin-textarea" id="message" name="message"
                                      placeholder="Type your response here..." required minlength="3" rows="6"></textarea>
                            <div class="admin-help-text">Provide additional information or ask questions about your ticket.</div>
                        </div>

                        <?php if (in_array($currentUser['role'], ['admin', 'employee'])): ?>
                            <div class="admin-form-group">
                                <label class="admin-label" style="display: flex; align-items: center;">
                                    <input type="checkbox" id="is_internal" name="is_internal" style="margin-right: 0.5rem;">
                                    <i class="fas fa-eye-slash" style="color: var(--admin-warning); margin-right: 0.5rem;"></i>
                                    Internal note (not visible to client)
                                </label>
                            </div>
                        <?php endif; ?>

                        <div style="text-align: right;">
                            <button type="submit" class="admin-btn admin-btn-primary" id="replyBtn">
                                <i class="fas fa-paper-plane"></i> Send Response
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        <?php else: ?>
            <div class="admin-card">
                <div class="admin-card-body" style="text-align: center; color: var(--admin-text-muted);">
                    <i class="fas fa-lock fa-2x" style="margin-bottom: 1rem;"></i>
                    <p>This ticket is <?= $ticket['status'] ?>. No new messages can be added.</p>
                    <?php if ($ticket['status'] === 'resolved' && $currentUser['role'] === 'client'): ?>
                        <button class="admin-btn admin-btn-primary" onclick="reopenTicket()">
                            <i class="fas fa-undo"></i> Reopen Ticket
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Sidebar -->
    <div class="admin-sidebar">
        <!-- Ticket Details -->
        <div class="admin-card">
            <div class="admin-card-header">
                <h4 class="admin-card-title">
                    <i class="fas fa-info-circle"></i>
                    Ticket Details
                </h4>
            </div>
            <div class="admin-card-body">
                <div style="display: grid; grid-template-columns: auto 1fr; gap: 0.5rem 1rem; align-items: center;">
                    <strong>Status:</strong>
                    <span class="admin-badge admin-badge-<?= $ticket['status_badge_class'] ?? 'gray' ?>">
                        <?= ucfirst(str_replace('_', ' ', $ticket['status'])) ?>
                    </span>

                    <strong>Priority:</strong>
                    <span class="admin-badge admin-badge-<?= $ticket['priority_badge_class'] ?? 'gray' ?>">
                        <?= ucfirst($ticket['priority']) ?>
                    </span>

                    <?php if ($ticket['category']): ?>
                        <strong>Category:</strong>
                        <span><?= htmlspecialchars($ticket['category']) ?></span>
                    <?php endif; ?>

                    <strong>Created:</strong>
                    <span style="font-size: 0.875rem;"><?= date('M j, Y', strtotime($ticket['created_at'])) ?></span>

                    <strong>Last Update:</strong>
                    <span style="font-size: 0.875rem;"><?= date('M j, Y', strtotime($ticket['updated_at'])) ?></span>
                </div>
            </div>
        </div>

        <!-- Quick Actions (Admin/Employee only) -->
        <?php if (in_array($currentUser['role'], ['admin', 'employee'])): ?>
            <div class="admin-card">
                <div class="admin-card-header">
                    <h4 class="admin-card-title">
                        <i class="fas fa-tools"></i>
                        Quick Actions
                    </h4>
                </div>
                <div class="admin-card-body">
                    <div style="display: grid; gap: 0.5rem;">
                        <?php if ($ticket['status'] !== 'in_progress'): ?>
                            <button class="admin-btn admin-btn-sm admin-btn-warning" onclick="updateStatus('in_progress')">
                                <i class="fas fa-play"></i> Start Working
                            </button>
                        <?php endif; ?>

                        <?php if ($ticket['status'] !== 'waiting_client'): ?>
                            <button class="admin-btn admin-btn-sm admin-btn-secondary" onclick="updateStatus('waiting_client')">
                                <i class="fas fa-clock"></i> Wait for Client
                            </button>
                        <?php endif; ?>

                        <?php if ($ticket['status'] !== 'resolved'): ?>
                            <button class="admin-btn admin-btn-sm admin-btn-success" onclick="updateStatus('resolved')">
                                <i class="fas fa-check"></i> Mark Resolved
                            </button>
                        <?php endif; ?>

                        <?php if ($ticket['status'] !== 'closed'): ?>
                            <button class="admin-btn admin-btn-sm admin-btn-secondary" onclick="updateStatus('closed')">
                                <i class="fas fa-times"></i> Close Ticket
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Help Section -->
        <div class="admin-card">
            <div class="admin-card-header">
                <h4 class="admin-card-title">
                    <i class="fas fa-question-circle"></i>
                    Need Help?
                </h4>
            </div>
            <div class="admin-card-body">
                <p style="font-size: 0.875rem; color: var(--admin-text-muted); margin-bottom: 1rem;">
                    Our support team typically responds within:
                </p>
                <ul style="font-size: 0.875rem; margin-bottom: 1rem;">
                    <li><strong>Critical:</strong> 2 hours</li>
                    <li><strong>High:</strong> 8 hours</li>
                    <li><strong>Medium:</strong> 24-48 hours</li>
                    <li><strong>Low:</strong> 3-5 business days</li>
                </ul>
                <a href="/index.php?page=user_tickets_create" class="admin-btn admin-btn-sm admin-btn-primary" style="width: 100%;">
                    <i class="fas fa-plus"></i> Create New Ticket
                </a>
            </div>
        </div>
    </div>
</div>

<script src="/public/assets/js/admin.js"></script>
<script>
// Handle reply form submission
document.getElementById('replyForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const replyBtn = document.getElementById('replyBtn');
    const originalText = replyBtn.innerHTML;

    // Show loading state
    replyBtn.disabled = true;
    replyBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';

    // Get form data
    const formData = new FormData(this);

    // Submit via AJAX
    fetch('/page/api/tickets/add_message.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Show success message
            showToast('Response sent successfully!', 'success');

            // Clear form
            document.getElementById('message').value = '';
            document.getElementById('is_internal').checked = false;

            // Refresh messages after short delay
            setTimeout(refreshMessages, 1000);
        } else {
            showToast(data.message || 'Failed to send response', 'error');
        }
    })
    .catch(error => {
        console.error('Error sending response:', error);
        showToast('Error sending response. Please try again.', 'error');
    })
    .finally(() => {
        // Reset button
        replyBtn.disabled = false;
        replyBtn.innerHTML = originalText;
    });
});

// Refresh messages
function refreshMessages() {
    const ticketId = <?= $ticket['id'] ?>;

    fetch(`/page/api/tickets/get_details.php?id=${ticketId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.messages) {
                // Update messages container
                updateMessagesDisplay(data.messages);
                showToast('Messages refreshed', 'info');
            }
        })
        .catch(error => {
            console.error('Error refreshing messages:', error);
        });
}

// Update messages display
function updateMessagesDisplay(messages) {
    const container = document.getElementById('messagesContainer');

    if (messages.length === 0) {
        container.innerHTML = `
            <div class="text-center py-4 text-muted">
                <i class="fas fa-comments fa-3x mb-3"></i>
                <p>No messages yet. Start the conversation below.</p>
            </div>
        `;
        return;
    }

    let html = '';
    messages.forEach(message => {
        const messageClass = message.is_internal ? 'message-internal' :
                           (message.role === 'client' ? 'message-client' : 'message-admin');
        const userColor = message.role === 'client' ? 'primary' : 'success';

        html += `
            <div class="message-item ${messageClass} p-3 mb-3 rounded">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-user-circle fa-lg me-2 text-${userColor}"></i>
                        <div>
                            <strong>${escapeHtml(message.username)}</strong>
                            <span class="badge bg-${userColor} ms-2">${message.role.charAt(0).toUpperCase() + message.role.slice(1)}</span>
                            ${message.is_internal ? '<span class="badge bg-warning ms-1">Internal</span>' : ''}
                        </div>
                    </div>
                    <small class="text-muted">
                        ${new Date(message.created_at).toLocaleDateString('en-US', {
                            month: 'short', day: 'numeric', year: 'numeric',
                            hour: 'numeric', minute: '2-digit', hour12: true
                        })}
                    </small>
                </div>
                <div class="message-content">
                    ${escapeHtml(message.message).replace(/\n/g, '<br>')}
                </div>
            </div>
        `;
    });

    container.innerHTML = html;
}

// Update ticket status (admin/employee only)
function updateStatus(newStatus) {
    if (!confirm(`Are you sure you want to change the ticket status to "${newStatus.replace('_', ' ')}"?`)) {
        return;
    }

    const formData = new FormData();
    formData.append('ticket_id', <?= $ticket['id'] ?>);
    formData.append('status', newStatus);

    fetch('/page/api/tickets/update_status.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Ticket status updated successfully!', 'success');
            // Reload page to show updated status
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            showToast(data.message || 'Failed to update status', 'error');
        }
    })
    .catch(error => {
        console.error('Error updating status:', error);
        showToast('Error updating status. Please try again.', 'error');
    });
}

// Reopen ticket (client only)
function reopenTicket() {
    if (!confirm('Are you sure you want to reopen this ticket?')) {
        return;
    }

    updateStatus('open');
}

// Show toast notification
function showToast(message, type = 'info') {
    const toastHtml = `
        <div class="toast align-items-center text-white bg-${type === 'error' ? 'danger' : type === 'success' ? 'success' : 'primary'} border-0" role="alert">
            <div class="d-flex">
                <div class="toast-body">${message}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    `;

    let toastContainer = document.getElementById('toastContainer');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.id = 'toastContainer';
        toastContainer.className = 'toast-container position-fixed top-0 end-0 p-3';
        document.body.appendChild(toastContainer);
    }

    toastContainer.insertAdjacentHTML('beforeend', toastHtml);

    const toastElement = toastContainer.lastElementChild;
    const toast = new bootstrap.Toast(toastElement);
    toast.show();

    toastElement.addEventListener('hidden.bs.toast', () => {
        toastElement.remove();
    });
}

// Escape HTML for security
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>