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

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .ticket-header {
            border-left: 4px solid var(--bs-primary);
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        }
        .priority-critical { border-left-color: #dc3545 !important; }
        .priority-high { border-left-color: #fd7e14 !important; }
        .priority-medium { border-left-color: #0d6efd !important; }
        .priority-low { border-left-color: #6c757d !important; }
        
        .message-item {
            transition: all 0.3s ease;
        }
        .message-item:hover {
            transform: translateX(5px);
        }
        .message-admin {
            border-left: 3px solid #28a745;
        }
        .message-client {
            border-left: 3px solid #0d6efd;
        }
        .message-internal {
            background-color: #fff3cd;
            border-left: 3px solid #ffc107;
        }
        .message-textarea {
            min-height: 120px;
            resize: vertical;
        }
        .status-badge {
            font-size: 0.9rem;
        }
    </style>
</head>
<body class="bg-light">

<div class="container py-4">
    <!-- Breadcrumb Navigation -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/index.php?page=user_dashboard">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="/index.php?page=user_tickets">Support Tickets</a></li>
            <li class="breadcrumb-item active">Ticket #<?= $ticket['id'] ?></li>
        </ol>
    </nav>

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

    <!-- Ticket Header -->
    <div class="card ticket-header priority-<?= htmlspecialchars($ticket['priority']) ?> mb-4">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h2 class="h4 mb-2">
                        <i class="fas fa-ticket-alt text-primary"></i>
                        Ticket #<?= $ticket['id'] ?>
                    </h2>
                    <h3 class="h5 text-muted mb-3"><?= htmlspecialchars($ticket['subject']) ?></h3>
                    <div class="d-flex flex-wrap gap-2">
                        <span class="badge <?= $ticket['status_badge_class'] ?> status-badge">
                            <i class="fas fa-circle-notch"></i> <?= ucfirst(str_replace('_', ' ', $ticket['status'])) ?>
                        </span>
                        <span class="badge <?= $ticket['priority_badge_class'] ?> status-badge">
                            <i class="fas fa-exclamation-triangle"></i> <?= ucfirst($ticket['priority']) ?> Priority
                        </span>
                        <?php if ($ticket['category']): ?>
                            <span class="badge bg-light text-dark status-badge">
                                <i class="fas fa-tag"></i> <?= htmlspecialchars($ticket['category']) ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-md-4 text-md-end">
                    <div class="text-muted small">
                        <div><strong>Created:</strong> <?= date('M j, Y \a\t g:i A', strtotime($ticket['created_at'])) ?></div>
                        <div><strong>Updated:</strong> <?= date('M j, Y \a\t g:i A', strtotime($ticket['updated_at'])) ?></div>
                    </div>
                    <div class="mt-3">
                        <a href="/index.php?page=user_tickets" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Back to Tickets
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Messages Column -->
        <div class="col-lg-8">
            <!-- Original Description -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-file-alt text-primary"></i>
                        Original Request
                    </h5>
                </div>
                <div class="card-body">
                    <div class="message-item message-client p-3 bg-light rounded">
                        <p class="mb-0"><?= nl2br(htmlspecialchars($ticket['description'])) ?></p>
                    </div>
                </div>
            </div>

            <!-- Messages -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-comments text-primary"></i>
                        Conversation (<?= count($messages) ?>)
                    </h5>
                    <button class="btn btn-sm btn-outline-primary" onclick="refreshMessages()">
                        <i class="fas fa-sync-alt"></i> Refresh
                    </button>
                </div>
                <div class="card-body" id="messagesContainer">
                    <?php if (empty($messages)): ?>
                        <div class="text-center py-4 text-muted">
                            <i class="fas fa-comments fa-3x mb-3"></i>
                            <p>No messages yet. Start the conversation below.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($messages as $message): ?>
                            <div class="message-item <?= $message['is_internal'] ? 'message-internal' : ($message['role'] === 'client' ? 'message-client' : 'message-admin') ?> p-3 mb-3 rounded">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-user-circle fa-lg me-2 text-<?= $message['role'] === 'client' ? 'primary' : 'success' ?>"></i>
                                        <div>
                                            <strong><?= htmlspecialchars($message['username']) ?></strong>
                                            <span class="badge bg-<?= $message['role'] === 'client' ? 'primary' : 'success' ?> ms-2">
                                                <?= ucfirst($message['role']) ?>
                                            </span>
                                            <?php if ($message['is_internal']): ?>
                                                <span class="badge bg-warning ms-1">Internal</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <small class="text-muted">
                                        <?= date('M j, Y \a\t g:i A', strtotime($message['created_at'])) ?>
                                    </small>
                                </div>
                                <div class="message-content">
                                    <?= nl2br(htmlspecialchars($message['message'])) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Reply Form -->
            <?php if (in_array($ticket['status'], ['open', 'in_progress', 'waiting_client'])): ?>
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-reply text-primary"></i>
                            Add Response
                        </h5>
                    </div>
                    <div class="card-body">
                        <form id="replyForm">
                            <input type="hidden" name="ticket_id" value="<?= $ticket['id'] ?>">
                            
                            <div class="mb-3">
                                <label for="message" class="form-label">Your Message</label>
                                <textarea class="form-control message-textarea" id="message" name="message" 
                                          placeholder="Type your response here..." required minlength="3"></textarea>
                                <div class="form-text">Provide additional information or ask questions about your ticket.</div>
                            </div>
                            
                            <?php if (in_array($currentUser['role'], ['admin', 'employee'])): ?>
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="is_internal" name="is_internal">
                                        <label class="form-check-label" for="is_internal">
                                            <i class="fas fa-eye-slash text-warning"></i>
                                            Internal note (not visible to client)
                                        </label>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <div class="d-flex justify-content-end">
                                <button type="submit" class="btn btn-primary" id="replyBtn">
                                    <i class="fas fa-paper-plane"></i> Send Response
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php else: ?>
                <div class="card">
                    <div class="card-body text-center text-muted">
                        <i class="fas fa-lock fa-2x mb-3"></i>
                        <p>This ticket is <?= $ticket['status'] ?>. No new messages can be added.</p>
                        <?php if ($ticket['status'] === 'resolved' && $currentUser['role'] === 'client'): ?>
                            <button class="btn btn-outline-primary" onclick="reopenTicket()">
                                <i class="fas fa-undo"></i> Reopen Ticket
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Ticket Details -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-info-circle text-primary"></i>
                        Ticket Details
                    </h6>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-5">Status:</dt>
                        <dd class="col-7">
                            <span class="badge <?= $ticket['status_badge_class'] ?>">
                                <?= ucfirst(str_replace('_', ' ', $ticket['status'])) ?>
                            </span>
                        </dd>
                        
                        <dt class="col-5">Priority:</dt>
                        <dd class="col-7">
                            <span class="badge <?= $ticket['priority_badge_class'] ?>">
                                <?= ucfirst($ticket['priority']) ?>
                            </span>
                        </dd>
                        
                        <?php if ($ticket['category']): ?>
                            <dt class="col-5">Category:</dt>
                            <dd class="col-7"><?= htmlspecialchars($ticket['category']) ?></dd>
                        <?php endif; ?>
                        
                        <dt class="col-5">Created:</dt>
                        <dd class="col-7 small"><?= date('M j, Y', strtotime($ticket['created_at'])) ?></dd>
                        
                        <dt class="col-5">Last Update:</dt>
                        <dd class="col-7 small"><?= date('M j, Y', strtotime($ticket['updated_at'])) ?></dd>
                    </dl>
                </div>
            </div>

            <!-- Quick Actions (Admin/Employee only) -->
            <?php if (in_array($currentUser['role'], ['admin', 'employee'])): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-tools text-primary"></i>
                            Quick Actions
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <?php if ($ticket['status'] !== 'in_progress'): ?>
                                <button class="btn btn-warning btn-sm" onclick="updateStatus('in_progress')">
                                    <i class="fas fa-play"></i> Start Working
                                </button>
                            <?php endif; ?>
                            
                            <?php if ($ticket['status'] !== 'waiting_client'): ?>
                                <button class="btn btn-info btn-sm" onclick="updateStatus('waiting_client')">
                                    <i class="fas fa-clock"></i> Wait for Client
                                </button>
                            <?php endif; ?>
                            
                            <?php if ($ticket['status'] !== 'resolved'): ?>
                                <button class="btn btn-success btn-sm" onclick="updateStatus('resolved')">
                                    <i class="fas fa-check"></i> Mark Resolved
                                </button>
                            <?php endif; ?>
                            
                            <?php if ($ticket['status'] !== 'closed'): ?>
                                <button class="btn btn-secondary btn-sm" onclick="updateStatus('closed')">
                                    <i class="fas fa-times"></i> Close Ticket
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Help Section -->
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-question-circle text-info"></i>
                        Need Help?
                    </h6>
                </div>
                <div class="card-body">
                    <p class="small text-muted mb-3">
                        Our support team typically responds within:
                    </p>
                    <ul class="small mb-3">
                        <li><strong>Critical:</strong> 2 hours</li>
                        <li><strong>High:</strong> 8 hours</li>
                        <li><strong>Medium:</strong> 24-48 hours</li>
                        <li><strong>Low:</strong> 3-5 business days</li>
                    </ul>
                    <a href="/index.php?page=user_tickets_create" class="btn btn-outline-primary btn-sm w-100">
                        <i class="fas fa-plus"></i> Create New Ticket
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
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

</body>
</html>
