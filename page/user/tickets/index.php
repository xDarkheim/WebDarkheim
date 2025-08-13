<?php

/**
 * Support Tickets - Client Portal
 * Main page for managing support tickets in client portal
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

    // Create ticket controller for data
    $ticketController = new \App\Application\Controllers\SupportTicketController(
        $database,
        $authService,
        $flashService,
        $logger
    );

    // Get tickets and statistics
    $ticketsResponse = $ticketController->getClientTickets();
    $tickets = $ticketsResponse['tickets'] ?? [];

    // Get flash messages
    $flashMessages = $flashService->getAllMessages();

    // Set page title
    $pageTitle = 'Support Tickets - Client Portal';

} catch (Exception $e) {
    // Handle critical errors
    if (isset($logger)) {
        $logger->critical("Critical error in tickets page: " . $e->getMessage());
    }

    $tickets = [];
    $flashMessages = [];
    $pageTitle = 'Support Tickets - Client Portal';
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
        .ticket-card {
            transition: all 0.3s ease;
            border: none;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .ticket-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        .priority-critical { border-left: 4px solid #dc3545; }
        .priority-high { border-left: 4px solid #fd7e14; }
        .priority-medium { border-left: 4px solid #0d6efd; }
        .priority-low { border-left: 4px solid #6c757d; }
        .status-badge {
            font-size: 0.8rem;
        }
        .ticket-meta {
            font-size: 0.9rem;
            color: #6c757d;
        }
    </style>
</head>
<body class="bg-light">

<div class="container-fluid py-4">
    <!-- Breadcrumb Navigation -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/index.php?page=user_dashboard">Dashboard</a></li>
            <li class="breadcrumb-item active">Support Tickets</li>
        </ol>
    </nav>

    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="h3 mb-0">
                    <i class="fas fa-ticket-alt text-primary"></i>
                    Support Tickets
                </h1>
                <div class="btn-group">
                    <a href="/index.php?page=user_tickets_create" class="btn btn-primary">
                        <i class="fas fa-plus"></i> New Ticket
                    </a>
                    <button class="btn btn-outline-secondary" onclick="refreshTickets()">
                        <i class="fas fa-sync-alt"></i> Refresh
                    </button>
                </div>
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

    <!-- Filter Tabs -->
    <div class="row mb-4">
        <div class="col-12">
            <ul class="nav nav-tabs" id="ticketTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="all-tab" data-bs-toggle="tab" data-bs-target="#all"
                            type="button" role="tab" onclick="filterTickets('all')">
                        All Tickets
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="open-tab" data-bs-toggle="tab" data-bs-target="#open"
                            type="button" role="tab" onclick="filterTickets('open')">
                        Open <span class="badge bg-primary ms-1" id="open-count">0</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="in_progress-tab" data-bs-toggle="tab" data-bs-target="#in_progress"
                            type="button" role="tab" onclick="filterTickets('in_progress')">
                        In Progress <span class="badge bg-warning ms-1" id="progress-count">0</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="resolved-tab" data-bs-toggle="tab" data-bs-target="#resolved"
                            type="button" role="tab" onclick="filterTickets('resolved')">
                        Resolved <span class="badge bg-success ms-1" id="resolved-count">0</span>
                    </button>
                </li>
            </ul>
        </div>
    </div>

    <!-- Tickets List -->
    <div class="row" id="ticketsContainer">
        <?php if (empty($tickets)): ?>
            <div class="col-12">
                <div class="card ticket-card text-center py-5">
                    <div class="card-body">
                        <i class="fas fa-ticket-alt fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No support tickets yet</h5>
                        <p class="text-muted">Create your first support ticket to get help from our team.</p>
                        <a href="/index.php?page=user_tickets_create" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Create Ticket
                        </a>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($tickets as $ticket): ?>
                <div class="col-md-6 col-lg-4 mb-4 ticket-item" data-status="<?= htmlspecialchars($ticket['status']) ?>" data-priority="<?= htmlspecialchars($ticket['priority']) ?>">
                    <div class="card ticket-card priority-<?= htmlspecialchars($ticket['priority']) ?>">
                        <div class="card-header d-flex justify-content-between align-items-center py-2">
                            <small class="text-muted">#<?= $ticket['id'] ?></small>
                            <div>
                                <span class="badge <?= $ticket['priority_badge_class'] ?> status-badge me-1">
                                    <?= ucfirst($ticket['priority']) ?>
                                </span>
                                <span class="badge <?= $ticket['status_badge_class'] ?> status-badge">
                                    <?= ucfirst(str_replace('_', ' ', $ticket['status'])) ?>
                                </span>
                            </div>
                        </div>
                        <div class="card-body">
                            <h6 class="card-title mb-2">
                                <a href="/index.php?page=user_tickets_view&id=<?= $ticket['id'] ?>"
                                   class="text-decoration-none">
                                    <?= htmlspecialchars($ticket['subject']) ?>
                                </a>
                            </h6>
                            <p class="card-text text-muted small mb-3">
                                <?= htmlspecialchars(substr($ticket['description'], 0, 100)) ?><?= strlen($ticket['description']) > 100 ? '...' : '' ?>
                            </p>
                            <?php if ($ticket['category']): ?>
                                <div class="mb-2">
                                    <span class="badge bg-light text-dark">
                                        <i class="fas fa-tag"></i> <?= htmlspecialchars($ticket['category']) ?>
                                    </span>
                                </div>
                            <?php endif; ?>
                            <div class="ticket-meta d-flex justify-content-between align-items-center">
                                <small>
                                    <i class="fas fa-clock"></i>
                                    <?= date('M j, Y', strtotime($ticket['created_at'])) ?>
                                </small>
                                <small>
                                    Updated: <?= date('M j', strtotime($ticket['updated_at'])) ?>
                                </small>
                            </div>
                        </div>
                        <div class="card-footer bg-transparent border-0 py-2">
                            <div class="d-flex justify-content-end">
                                <a href="/index.php?page=user_tickets_view&id=<?= $ticket['id'] ?>"
                                   class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-eye"></i> View
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Loading indicator -->
    <div class="row d-none" id="loadingIndicator">
        <div class="col-12 text-center py-4">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
let currentFilter = 'all';

// Initialize page
document.addEventListener('DOMContentLoaded', function() {
    updateTicketCounts();
});

// Filter tickets by status
function filterTickets(status) {
    currentFilter = status;
    const tickets = document.querySelectorAll('.ticket-item');

    tickets.forEach(ticket => {
        if (status === 'all' || ticket.dataset.status === status) {
            ticket.style.display = 'block';
        } else {
            ticket.style.display = 'none';
        }
    });
}

// Update ticket counts in tabs
function updateTicketCounts() {
    const tickets = document.querySelectorAll('.ticket-item');
    const counts = {
        open: 0,
        in_progress: 0,
        resolved: 0
    };

    tickets.forEach(ticket => {
        const status = ticket.dataset.status;
        if (counts.hasOwnProperty(status)) {
            counts[status]++;
        }
    });

    document.getElementById('open-count').textContent = counts.open;
    document.getElementById('progress-count').textContent = counts.in_progress;
    document.getElementById('resolved-count').textContent = counts.resolved;
}

// Refresh tickets via AJAX
function refreshTickets() {
    const container = document.getElementById('ticketsContainer');
    const loadingIndicator = document.getElementById('loadingIndicator');

    // Show loading
    container.style.display = 'none';
    loadingIndicator.classList.remove('d-none');

    fetch('/page/api/tickets/get_tickets.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Reload page to show updated tickets
                window.location.reload();
            } else {
                console.error('Failed to refresh tickets:', data.message);
                // Show error toast
                showToast('Failed to refresh tickets', 'error');
            }
        })
        .catch(error => {
            console.error('Error refreshing tickets:', error);
            showToast('Error refreshing tickets', 'error');
        })
        .finally(() => {
            // Hide loading
            container.style.display = 'block';
            loadingIndicator.classList.add('d-none');
        });
}

// Show toast notification
function showToast(message, type = 'info') {
    // Create toast element
    const toastHtml = `
        <div class="toast align-items-center text-white bg-${type === 'error' ? 'danger' : type === 'success' ? 'success' : 'primary'} border-0" role="alert">
            <div class="d-flex">
                <div class="toast-body">${message}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    `;

    // Add to page
    let toastContainer = document.getElementById('toastContainer');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.id = 'toastContainer';
        toastContainer.className = 'toast-container position-fixed top-0 end-0 p-3';
        document.body.appendChild(toastContainer);
    }

    toastContainer.insertAdjacentHTML('beforeend', toastHtml);

    // Show toast
    const toastElement = toastContainer.lastElementChild;
    const toast = new bootstrap.Toast(toastElement);
    toast.show();

    // Remove from DOM after hiding
    toastElement.addEventListener('hidden.bs.toast', () => {
        toastElement.remove();
    });
}
</script>

</body>
</html>
