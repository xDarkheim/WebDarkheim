<?php

/**
 * Support Tickets - Client Portal - PHASE 8 - DARK ADMIN THEME
 * Main page for managing support tickets in client portal
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

    // Mock tickets data for now
    $tickets = [
        [
            'id' => 1,
            'subject' => 'Payment Gateway Integration Issue',
            'description' => 'Having trouble with PayPal integration on the checkout page. The payment process fails at the final step.',
            'status' => 'open',
            'priority' => 'high',
            'category' => 'technical',
            'created_at' => '2024-08-10 14:30:00',
            'updated_at' => '2024-08-12 09:15:00',
            'priority_badge_class' => 'admin-badge-error',
            'status_badge_class' => 'admin-badge-primary'
        ],
        [
            'id' => 2,
            'subject' => 'Feature Request: Dark Mode Toggle',
            'description' => 'Would like to add a dark mode toggle to the user interface for better user experience.',
            'status' => 'in_progress',
            'priority' => 'medium',
            'category' => 'feature',
            'created_at' => '2024-08-08 16:45:00',
            'updated_at' => '2024-08-11 11:20:00',
            'priority_badge_class' => 'admin-badge-primary',
            'status_badge_class' => 'admin-badge-warning'
        ],
        [
            'id' => 3,
            'subject' => 'Database Performance Optimization',
            'description' => 'The application is running slowly, especially on pages with large datasets. Need performance optimization.',
            'status' => 'resolved',
            'priority' => 'medium',
            'category' => 'technical',
            'created_at' => '2024-08-05 10:20:00',
            'updated_at' => '2024-08-09 15:30:00',
            'priority_badge_class' => 'admin-badge-primary',
            'status_badge_class' => 'admin-badge-success'
        ]
    ];

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


    <link rel="stylesheet" href="/public/assets/css/admin.css">
    <style>
        .ticket-card {
            transition: var(--admin-transition);
            overflow: hidden;
        }
        .ticket-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--admin-shadow-lg);
        }
        .priority-critical { border-left: 4px solid var(--admin-error); }
        .priority-high { border-left: 4px solid var(--admin-error); }
        .priority-medium { border-left: 4px solid var(--admin-primary); }
        .priority-low { border-left: 4px solid var(--admin-border); }
        .ticket-meta {
            font-size: 0.75rem;
            color: var(--admin-text-muted);
        }
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--admin-text-muted);
        }
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.3;
        }
        .filter-tabs {
            background: var(--admin-bg-card);
            border-radius: var(--admin-border-radius);
            padding: 1rem;
            margin-bottom: 2rem;
            border: 1px solid var(--admin-border);
        }
    </style>

<div class="admin-container">
    <!-- Navigation -->
    <nav class="admin-nav">
        <div class="admin-nav-container">
            <a href="/index.php?page=dashboard" class="admin-nav-brand">
                <i class="fas fa-ticket-alt"></i>
                Support Center
            </a>
            <div class="admin-nav-links">
                <a href="/index.php?page=dashboard" class="admin-nav-link">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
                <a href="/index.php?page=user_projects" class="admin-nav-link">
                    <i class="fas fa-code"></i> Projects
                </a>
                <a href="/index.php?page=user_documents" class="admin-nav-link">
                    <i class="fas fa-folder"></i> Documents
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
                        <h1>Support Tickets</h1>
                        <p>Manage your support requests and get help from our team</p>
                    </div>
                </div>
                <div class="admin-header-actions">
                    <a href="/index.php?page=user_tickets_create" class="admin-btn admin-btn-primary">
                        <i class="fas fa-plus"></i> New Ticket
                    </a>
                    <button class="admin-btn admin-btn-secondary" onclick="refreshTickets()">
                        <i class="fas fa-sync-alt"></i> Refresh
                    </button>
                </div>
            </div>
        </div>
    </header>

    <!-- Flash Messages -->
    <?php if (!empty($flashMessages)): ?>
        <div class="admin-flash-messages">
            <?php foreach ($flashMessages as $type => $messages): ?>
                <?php foreach ($messages as $message): ?>
                    <div class="admin-flash-message admin-flash-<?= $type === 'error' ? 'error' : $type ?>">
                        <i class="fas fa-<?= $type === 'success' ? 'check-circle' : ($type === 'error' ? 'exclamation-circle' : 'info-circle') ?>"></i>
                        <div><?= htmlspecialchars($message['text']) ?></div>
                    </div>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="admin-layout-main">
        <div class="admin-content">
            <!-- Filter Tabs -->
            <div class="filter-tabs">
                <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                    <button class="admin-btn admin-btn-primary admin-btn-sm filter-btn active" data-filter="all">
                        <i class="fas fa-th-large"></i> All Tickets
                    </button>
                    <button class="admin-btn admin-btn-secondary admin-btn-sm filter-btn" data-filter="open">
                        <i class="fas fa-folder-open"></i> Open <span class="admin-badge admin-badge-primary ms-1" id="open-count">0</span>
                    </button>
                    <button class="admin-btn admin-btn-secondary admin-btn-sm filter-btn" data-filter="in_progress">
                        <i class="fas fa-clock"></i> In Progress <span class="admin-badge admin-badge-warning ms-1" id="progress-count">0</span>
                    </button>
                    <button class="admin-btn admin-btn-secondary admin-btn-sm filter-btn" data-filter="resolved">
                        <i class="fas fa-check-circle"></i> Resolved <span class="admin-badge admin-badge-success ms-1" id="resolved-count">0</span>
                    </button>
                </div>
            </div>

            <!-- Tickets Grid -->
            <?php if (empty($tickets)): ?>
                <div class="admin-card">
                    <div class="admin-card-body">
                        <div class="empty-state">
                            <i class="fas fa-ticket-alt"></i>
                            <h4 style="color: var(--admin-text-primary); margin-bottom: 1rem;">No Support Tickets Yet</h4>
                            <p style="margin-bottom: 2rem;">Create your first support ticket to get help from our team.</p>
                            <a href="/index.php?page=user_tickets_create" class="admin-btn admin-btn-primary">
                                <i class="fas fa-plus"></i> Create Ticket
                            </a>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="admin-grid admin-grid-cols-2" id="ticketsGrid">
                    <?php foreach ($tickets as $ticket): ?>
                        <div class="admin-card ticket-card priority-<?= htmlspecialchars($ticket['priority']) ?>" data-status="<?= $ticket['status'] ?>" data-priority="<?= $ticket['priority'] ?>">
                            <div class="admin-card-header">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <small style="color: var(--admin-text-muted);">#<?= $ticket['id'] ?></small>
                                    <div style="display: flex; gap: 0.5rem;">
                                        <span class="admin-badge <?= $ticket['priority_badge_class'] ?>">
                                            <?= ucfirst($ticket['priority']) ?>
                                        </span>
                                        <span class="admin-badge <?= $ticket['status_badge_class'] ?>">
                                            <?= ucfirst(str_replace('_', ' ', $ticket['status'])) ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="admin-card-body">
                                <h6 style="color: var(--admin-text-primary); margin-bottom: 0.75rem;">
                                    <a href="/index.php?page=user_tickets_view&id=<?= $ticket['id'] ?>"
                                       style="color: var(--admin-text-primary); text-decoration: none;">
                                        <?= htmlspecialchars($ticket['subject']) ?>
                                    </a>
                                </h6>
                                <p style="color: var(--admin-text-muted); font-size: 0.875rem; line-height: 1.4; margin-bottom: 1rem;">
                                    <?= htmlspecialchars(substr($ticket['description'], 0, 120)) ?><?= strlen($ticket['description']) > 120 ? '...' : '' ?>
                                </p>

                                <?php if ($ticket['category']): ?>
                                    <div style="margin-bottom: 1rem;">
                                        <span class="admin-badge admin-badge-gray">
                                            <i class="fas fa-tag"></i> <?= htmlspecialchars($ticket['category']) ?>
                                        </span>
                                    </div>
                                <?php endif; ?>

                                <div class="ticket-meta" style="display: flex; justify-content: space-between; align-items: center;">
                                    <small>
                                        <i class="fas fa-clock"></i>
                                        <?= date('M j, Y', strtotime($ticket['created_at'])) ?>
                                    </small>
                                    <small>
                                        Updated: <?= date('M j', strtotime($ticket['updated_at'])) ?>
                                    </small>
                                </div>
                            </div>
                            <div class="admin-card-footer">
                                <div style="display: flex; gap: 0.5rem;">
                                    <a href="/index.php?page=user_tickets_view&id=<?= $ticket['id'] ?>"
                                       class="admin-btn admin-btn-primary admin-btn-sm" style="flex: 1;">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                    <?php if (in_array($ticket['status'], ['open', 'in_progress'])): ?>
                                        <a href="/index.php?page=user_tickets_view&id=<?= $ticket['id'] ?>"
                                           class="admin-btn admin-btn-secondary admin-btn-sm">
                                            <i class="fas fa-reply"></i> Reply
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Sidebar -->
        <div class="admin-sidebar">
            <!-- Ticket Overview -->
            <div class="admin-card">
                <div class="admin-card-header">
                    <h6 class="admin-card-title" style="font-size: 0.875rem;">Ticket Overview</h6>
                </div>
                <div class="admin-card-body">
                    <div class="admin-stats-grid" style="grid-template-columns: 1fr 1fr;">
                        <div style="text-align: center;">
                            <div style="font-size: 1.5rem; font-weight: 700; color: var(--admin-primary); margin-bottom: 0.25rem;">
                                <?= count($tickets) ?>
                            </div>
                            <small style="color: var(--admin-text-muted);">Total Tickets</small>
                        </div>
                        <div style="text-align: center;">
                            <div style="font-size: 1.5rem; font-weight: 700; color: var(--admin-success); margin-bottom: 0.25rem;">
                                <?php
                                $openTickets = array_filter($tickets, fn($t) => in_array($t['status'], ['open', 'in_progress']));
                                echo count($openTickets);
                                ?>
                            </div>
                            <small style="color: var(--admin-text-muted);">Active</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="admin-card">
                <div class="admin-card-header">
                    <h6 class="admin-card-title" style="font-size: 0.875rem;">Quick Actions</h6>
                </div>
                <div class="admin-card-body">
                    <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                        <a href="/index.php?page=user_tickets_create" class="admin-btn admin-btn-primary admin-btn-sm">
                            <i class="fas fa-plus"></i> New Support Ticket
                        </a>
                        <a href="/index.php?page=user_projects" class="admin-btn admin-btn-secondary admin-btn-sm">
                            <i class="fas fa-code"></i> View Projects
                        </a>
                        <a href="/index.php?page=user_documents" class="admin-btn admin-btn-secondary admin-btn-sm">
                            <i class="fas fa-folder"></i> Documentation
                        </a>
                        <a href="/index.php?page=contact" class="admin-btn admin-btn-secondary admin-btn-sm">
                            <i class="fas fa-envelope"></i> Contact Us
                        </a>
                    </div>
                </div>
            </div>

            <!-- Response Times -->
            <div class="admin-card">
                <div class="admin-card-header">
                    <h6 class="admin-card-title" style="font-size: 0.875rem;">Response Times</h6>
                </div>
                <div class="admin-card-body">
                    <div style="font-size: 0.75rem; line-height: 1.4;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                            <span style="color: var(--admin-text-muted);">Critical</span>
                            <span style="color: var(--admin-error); font-weight: 600;">2 hours</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                            <span style="color: var(--admin-text-muted);">High</span>
                            <span style="color: var(--admin-warning); font-weight: 600;">8 hours</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                            <span style="color: var(--admin-text-muted);">Medium</span>
                            <span style="color: var(--admin-primary); font-weight: 600;">24 hours</span>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span style="color: var(--admin-text-muted);">Low</span>
                            <span style="color: var(--admin-text-secondary); font-weight: 600;">48 hours</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Support Information -->
            <div class="admin-card">
                <div class="admin-card-header">
                    <h6 class="admin-card-title" style="font-size: 0.875rem;">Need Help?</h6>
                </div>
                <div class="admin-card-body">
                    <p style="color: var(--admin-text-muted); font-size: 0.75rem; margin-bottom: 1rem;">
                        Our support team is here to help you with any questions or issues.
                    </p>
                    <div style="background: var(--admin-info-bg); border: 1px solid var(--admin-info); border-radius: var(--admin-border-radius); padding: 0.75rem; margin-bottom: 1rem;">
                        <small style="color: var(--admin-info-light);">
                            <i class="fas fa-lightbulb"></i>
                            <strong>Tip:</strong> Include error messages, screenshots, and steps to reproduce for faster resolution.
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="/public/assets/js/admin.js"></script>
<script>
// Filter functionality
document.addEventListener('DOMContentLoaded', function() {
    const filterBtns = document.querySelectorAll('.filter-btn');
    const ticketCards = document.querySelectorAll('.ticket-card');

    // Update counts
    updateTicketCounts();

    filterBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const filter = this.dataset.filter;

            // Update active button
            filterBtns.forEach(b => {
                b.classList.remove('admin-btn-primary');
                b.classList.add('admin-btn-secondary');
            });
            this.classList.remove('admin-btn-secondary');
            this.classList.add('admin-btn-primary');

            // Filter tickets
            ticketCards.forEach(card => {
                const status = card.dataset.status;
                if (filter === 'all' || status === filter) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    });

    function updateTicketCounts() {
        const tickets = document.querySelectorAll('.ticket-card');
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
});

function refreshTickets() {
    window.location.reload();
}
</script>