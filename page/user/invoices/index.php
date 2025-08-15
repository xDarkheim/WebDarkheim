<?php

/**
 * Invoices & Billing - Client Portal - PHASE 8
 * View and manage invoices and billing information
 * Based on dashboard design and structure
 *
 * @author Darkheim Studio
 */

declare(strict_types=1);

// Use global services from DI container (same pattern as dashboard)
global $flashMessageService, $database_handler, $container, $serviceProvider;

use App\Application\Components\AdminNavigation;

// Ensure all required services are available
if (!isset($serviceProvider)) {
    error_log("Critical: ServiceProvider not available in user_invoices page");
    die("System error: Services not initialized.");
}

try {
    $authService = $serviceProvider->getAuth();
    $invoiceService = $serviceProvider->getInvoiceService();
    $logger = $serviceProvider->getLogger();
} catch (Exception $e) {
    error_log("Critical: Failed to get services: " . $e->getMessage());
    die("A critical system error occurred. Please try again later.");
}

// Check authentication (same pattern as dashboard)
if (!$authService->isAuthenticated()) {
    $flashMessageService->addError('Please log in to access your invoices.');
    header("Location: /index.php?page=login");
    exit();
}

$currentUser = $authService->getCurrentUser();
$current_user_role = $authService->getCurrentUserRole();
$current_user_id = $authService->getCurrentUserId();
$current_username = $authService->getCurrentUsername();

// Check if user can access client area (same pattern as dashboard)
if (!in_array($current_user_role, ['client', 'employee', 'admin'])) {
    header('Location: /index.php?page=home');
    exit;
}

// Get invoices data with error handling (like dashboard)
try {
    // Get filters from request (define this first to ensure it's always available)
    $filters = [
        'status' => $_GET['status'] ?? '',
        'date_from' => $_GET['date_from'] ?? '',
        'date_to' => $_GET['date_to'] ?? '',
        'limit' => 50
    ];

    // Check if invoice system is available
    $isInvoiceSystemAvailable = $invoiceService->isSystemAvailable();

    if (!$isInvoiceSystemAvailable) {
        // System not yet implemented - show development message
        $invoices = [];
        $statistics = $invoiceService->getEmptyStatistics();
        $statusFilters = $invoiceService->getStatusFilters();
        $systemMessage = "Invoice system is currently in development (Phase 8). This feature will be available soon.";
    } else {
        // Get client invoices and statistics using the filters
        $invoices = $invoiceService->getClientInvoices($current_user_id, $filters);
        $statistics = $invoiceService->getClientStatistics($current_user_id);
        $statusFilters = $invoiceService->getStatusFilters();
        $systemMessage = null;
    }

} catch (Exception $e) {
    $logger->error('Error loading invoices: ' . $e->getMessage());

    // Ensure filters is always defined for the form
    if (!isset($filters)) {
        $filters = [
            'status' => $_GET['status'] ?? '',
            'date_from' => $_GET['date_from'] ?? '',
            'date_to' => $_GET['date_to'] ?? '',
            'limit' => 50
        ];
    }

    // Fallback empty data (like dashboard)
    $invoices = [];
    $statistics = $invoiceService->getEmptyStatistics();
    $statusFilters = $invoiceService->getStatusFilters();
    $systemMessage = "There was an error loading the invoice system. Please try again later.";
}

// Get flash messages (same pattern as dashboard)
$flashMessages = $flashMessageService->getAllMessages();

// Set page title (same pattern as dashboard)
$pageTitle = 'My Invoices';

// Create unified navigation
$adminNavigation = new AdminNavigation($authService);
?>


    <!-- Admin CSS for consistent dark theme -->
    <link rel="stylesheet" href="/public/assets/css/admin.css">

    <!-- Unified Navigation -->
    <?= $adminNavigation->render() ?>

    <!-- Header Section -->
    <header class="admin-header">
        <div class="admin-header-container">
            <div class="admin-header-content">
                <div class="admin-header-title">
                    <i class="admin-header-icon fas fa-file-invoice-dollar"></i>
                    <div class="admin-header-text">
                        <h1>My Invoices</h1>
                        <p>Manage your billing and payment information</p>
                    </div>
                </div>
                <div class="admin-header-actions">
                    <a href="/index.php?page=dashboard" class="admin-btn admin-btn-secondary">
                        <i class="fas fa-arrow-left"></i>
                        Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Flash Messages -->
    <?php if (!empty($flashMessages)): ?>
        <div class="admin-flash-messages">
            <?php foreach ($flashMessages as $type => $messages): ?>
                <?php foreach ($messages as $message): ?>
                    <div class="admin-flash-message admin-flash-<?php echo $type; ?>">
                        <i class="fas fa-<?php echo $type === 'error' ? 'exclamation-triangle' : ($type === 'success' ? 'check-circle' : 'info-circle'); ?>"></i>
                        <div><?php echo htmlspecialchars($message['text']); ?></div>
                    </div>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Main Layout -->
    <div class="admin-layout-main">
        <main class="admin-content">

            <!-- Development Notice -->
            <?php if (isset($systemMessage)): ?>
                <div class="admin-card" style="border-left: 4px solid var(--admin-warning); background: rgba(255, 193, 7, 0.1);">
                    <div class="admin-card-body">
                        <div style="display: flex; align-items: center; gap: 1rem;">
                            <i class="fas fa-construction" style="font-size: 2rem; color: var(--admin-warning);"></i>
                            <div>
                                <h3 style="color: var(--admin-text-primary); margin: 0 0 0.5rem 0;">
                                    System in Development
                                </h3>
                                <p style="color: var(--admin-text-secondary); margin: 0;">
                                    <?php echo htmlspecialchars($systemMessage); ?>
                                </p>
                                <p style="color: var(--admin-text-muted); margin: 0.5rem 0 0 0; font-size: 0.875rem;">
                                    <i class="fas fa-info-circle"></i>
                                    For immediate billing inquiries, please <a href="/index.php?page=contact" style="color: var(--admin-primary);">contact our support team</a>
                                    or <a href="/index.php?page=user_tickets_create" style="color: var(--admin-primary);">create a support ticket</a>.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Statistics Cards -->
            <?php if (!empty($statistics['total_invoices'])): ?>
                <div class="admin-stats-grid">
                    <div class="admin-stat-card">
                        <div class="admin-stat-content">
                            <div class="admin-stat-icon admin-stat-icon-primary">
                                <i class="fas fa-file-invoice"></i>
                            </div>
                            <div class="admin-stat-details">
                                <h3>Total Invoices</h3>
                                <p><?php echo $statistics['total_invoices']; ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="admin-stat-card">
                        <div class="admin-stat-content">
                            <div class="admin-stat-icon admin-stat-icon-primary">
                                <i class="fas fa-dollar-sign"></i>
                            </div>
                            <div class="admin-stat-details">
                                <h3>Total Billed</h3>
                                <p><?php echo $statistics['formatted_total_billed']; ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="admin-stat-card">
                        <div class="admin-stat-content">
                            <div class="admin-stat-icon admin-stat-icon-success">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="admin-stat-details">
                                <h3>Total Paid</h3>
                                <p><?php echo $statistics['formatted_total_paid']; ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="admin-stat-card">
                        <div class="admin-stat-content">
                            <div class="admin-stat-icon <?php echo $statistics['total_outstanding'] > 0 ? 'admin-stat-icon-warning' : 'admin-stat-icon-success'; ?>">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="admin-stat-details">
                                <h3>Outstanding</h3>
                                <p><?php echo $statistics['formatted_total_outstanding']; ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Overdue Warning -->
            <?php if (!empty($statistics['overdue_count'])): ?>
                <div class="overdue-alert">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>Attention:</strong> You have <?php echo $statistics['overdue_count']; ?> overdue invoice(s).
                    Please contact us for payment arrangements.
                </div>
            <?php endif; ?>

            <!-- Filters Card -->
            <div class="admin-card">
                <div class="admin-card-header">
                    <h3 class="admin-card-title">
                        <i class="fas fa-filter"></i>
                        Filter Invoices
                    </h3>
                </div>
                <div class="admin-card-body">
                    <form method="GET" class="filter-form">
                        <input type="hidden" name="page" value="user_invoices">

                        <div class="admin-grid admin-grid-cols-4">
                            <div class="admin-form-group">
                                <label for="status" class="admin-label">Status</label>
                                <select name="status" id="status" class="admin-input admin-select">
                                    <?php foreach ($statusFilters as $value => $label): ?>
                                        <option value="<?php echo htmlspecialchars($value); ?>" <?php echo $filters['status'] === $value ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($label); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="admin-form-group">
                                <label for="date_from" class="admin-label">From Date</label>
                                <input type="date" name="date_from" id="date_from" class="admin-input" value="<?php echo htmlspecialchars($filters['date_from']); ?>">
                            </div>

                            <div class="admin-form-group">
                                <label for="date_to" class="admin-label">To Date</label>
                                <input type="date" name="date_to" id="date_to" class="admin-input" value="<?php echo htmlspecialchars($filters['date_to']); ?>">
                            </div>

                            <div class="form-actions">
                                <button type="submit" class="admin-btn admin-btn-primary">
                                    <i class="fas fa-search"></i>
                                    Filter
                                </button>
                                <a href="/index.php?page=user_invoices" class="admin-btn admin-btn-secondary">
                                    <i class="fas fa-times"></i>
                                    Clear
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Invoices Table -->
            <div class="admin-card">
                <div class="admin-card-header">
                    <h3 class="admin-card-title">
                        <i class="fas fa-list"></i>
                        Invoice List
                    </h3>
                </div>

                <?php if (empty($invoices)): ?>
                    <!-- Empty State -->
                    <div class="admin-card-body" style="text-align: center; padding: 3rem;">
                        <div style="color: var(--admin-text-muted); margin-bottom: 1rem;">
                            <i class="fas fa-file-invoice" style="font-size: 3rem;"></i>
                        </div>
                        <h3 style="color: var(--admin-text-primary); margin: 0 0 0.5rem 0;">No Invoices Found</h3>
                        <p style="color: var(--admin-text-muted); margin: 0;">
                            <?php if (!empty($filters['status']) || !empty($filters['date_from']) || !empty($filters['date_to'])): ?>
                                No invoices match your current filters. Try adjusting your search criteria.
                            <?php else: ?>
                                You don't have any invoices yet. They will appear here once created.
                            <?php endif; ?>
                        </p>
                    </div>
                <?php else: ?>
                    <div class="admin-table-container">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Invoice #</th>
                                    <th>Title</th>
                                    <th>Issue Date</th>
                                    <th>Due Date</th>
                                    <th>Status</th>
                                    <th>Total</th>
                                    <th>Balance</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($invoices as $invoice): ?>
                                    <tr class="<?php echo $invoice['is_overdue'] ? 'row-danger' : ''; ?>">
                                        <td>
                                            <span style="font-family: var(--admin-font-mono); font-weight: 600;">
                                                <?php echo htmlspecialchars($invoice['invoice_number']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div style="font-weight: 500; color: var(--admin-text-primary); margin-bottom: 0.25rem;">
                                                <?php echo htmlspecialchars($invoice['title']); ?>
                                            </div>
                                            <?php if ($invoice['description']): ?>
                                                <small style="color: var(--admin-text-muted);">
                                                    <?php echo htmlspecialchars(substr($invoice['description'], 0, 60)); ?>
                                                    <?php if (strlen($invoice['description']) > 60): ?>...<?php endif; ?>
                                                </small>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo date('M j, Y', strtotime($invoice['issue_date'])); ?></td>
                                        <td>
                                            <?php echo date('M j, Y', strtotime($invoice['due_date'])); ?>
                                            <?php if ($invoice['is_overdue']): ?>
                                                <br><small style="color: var(--admin-error);">
                                                    <i class="fas fa-exclamation-triangle"></i>
                                                    <?php echo abs($invoice['days_until_due']); ?> days overdue
                                                </small>
                                            <?php elseif ($invoice['days_until_due'] <= 7 && $invoice['status'] !== 'paid'): ?>
                                                <br><small style="color: var(--admin-warning);">
                                                    <i class="fas fa-clock"></i>
                                                    Due in <?php echo $invoice['days_until_due']; ?> day(s)
                                                </small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="admin-badge invoice-badge-<?php echo $invoice['status']; ?>">
                                                <?php echo ucfirst(str_replace('_', ' ', $invoice['status'])); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="invoice-amount">
                                                <?php echo $invoice['formatted_total']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="invoice-amount <?php echo $invoice['balance_remaining'] > 0 ? 'amount-negative' : 'amount-zero'; ?>">
                                                <?php echo $invoice['formatted_balance']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="admin-table-actions">
                                                <a href="/index.php?page=user_invoice_view&id=<?php echo $invoice['id']; ?>"
                                                   class="admin-btn admin-btn-sm admin-btn-primary"
                                                   title="View Invoice">
                                                    <i class="fas fa-eye"></i>
                                                    <span>View</span>
                                                </a>
                                                <a href="/page/user/invoices/download.php?id=<?php echo $invoice['id']; ?>"
                                                   class="admin-btn admin-btn-sm admin-btn-secondary"
                                                   title="Download PDF">
                                                    <i class="fas fa-download"></i>
                                                    <span>PDF</span>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

        </main>

        <!-- Sidebar -->
        <aside class="admin-sidebar">
            <!-- Quick Actions -->
            <div class="admin-card">
                <div class="admin-card-header">
                    <h4 class="admin-card-title">
                        <i class="fas fa-bolt"></i>
                        Quick Actions
                    </h4>
                </div>
                <div class="admin-card-body">
                    <a href="/index.php?page=dashboard" class="admin-btn admin-btn-primary" style="width: 100%; margin-bottom: 0.75rem;">
                        <i class="fas fa-tachometer-alt"></i>
                        Dashboard
                    </a>
                    <a href="/index.php?page=user_profile" class="admin-btn admin-btn-secondary" style="width: 100%; margin-bottom: 0.75rem;">
                        <i class="fas fa-user"></i>
                        My Profile
                    </a>
                    <a href="/index.php?page=user_tickets" class="admin-btn admin-btn-secondary" style="width: 100%;">
                        <i class="fas fa-ticket-alt"></i>
                        Support Tickets
                    </a>
                </div>
            </div>

            <!-- Account Summary -->
            <?php if (!empty($statistics['total_invoices'])): ?>
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h4 class="admin-card-title">
                            <i class="fas fa-chart-pie"></i>
                            Account Summary
                    </h4>
                    </div>
                    <div class="admin-card-body">
                        <div style="margin-bottom: 1rem;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                                <span style="color: var(--admin-text-secondary);">Total Invoices:</span>
                                <span style="font-weight: 600;"><?php echo $statistics['total_invoices']; ?></span>
                            </div>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                                <span style="color: var(--admin-text-secondary);">Total Billed:</span>
                                <span style="font-weight: 600;"><?php echo $statistics['formatted_total_billed']; ?></span>
                            </div>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                                <span style="color: var(--admin-text-secondary);">Total Paid:</span>
                                <span style="font-weight: 600; color: var(--admin-success);"><?php echo $statistics['formatted_total_paid']; ?></span>
                            </div>
                            <div style="display: flex; justify-content: space-between;">
                                <span style="color: var(--admin-text-secondary);">Outstanding:</span>
                                <span style="font-weight: 600; color: <?php echo $statistics['total_outstanding'] > 0 ? 'var(--admin-error)' : 'var(--admin-success)'; ?>;">
                                    <?php echo $statistics['formatted_total_outstanding']; ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Help & Support -->
            <div class="admin-card">
                <div class="admin-card-header">
                    <h4 class="admin-card-title">
                        <i class="fas fa-question-circle"></i>
                        Help & Support
                    </h4>
                </div>
                <div class="admin-card-body">
                    <p style="color: var(--admin-text-muted); font-size: 0.875rem; margin-bottom: 1rem;">
                        Need help with your invoices or have billing questions?
                    </p>
                    <a href="/index.php?page=contact" class="admin-btn admin-btn-success" style="width: 100%; margin-bottom: 0.5rem;">
                        <i class="fas fa-envelope"></i>
                        Contact Support
                    </a>
                    <a href="/index.php?page=user_tickets" class="admin-btn admin-btn-secondary" style="width: 100%;">
                        <i class="fas fa-ticket-alt"></i>
                        Create Ticket
                    </a>
                </div>
            </div>
        </aside>
    </div>
</div>

<!-- Admin JavaScript -->
<script type="module" src="/public/assets/js/admin.js"></script>
