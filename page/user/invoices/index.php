<?php

/**
 * Invoices & Billing - Client Portal - DARK ADMIN THEME
 * View and manage invoices and billing information
 *
 * @author GitHub Copilot
 */

declare(strict_types=1);

// Use global services from the DI architecture
global $flashMessageService, $database_handler, $container, $serviceProvider;

try {
    $authService = $serviceProvider->getAuth();
} catch (Exception $e) {
    error_log("Critical: Failed to get AuthenticationService instance: " . $e->getMessage());
    die("A critical system error occurred. Please try again later.");
}

// Check authentication
if (!$authService->isAuthenticated()) {
    $flashMessageService->addError('Please log in to access your invoices.');
    header("Location: /index.php?page=login");
    exit();
}

$currentUser = $authService->getCurrentUser();

// Check if user can access client area
if (!in_array($currentUser['role'], ['client', 'employee', 'admin'])) {
    header('Location: /index.php?page=home');
    exit;
}

// Get invoices for current user
try {
    $sql = "SELECT ci.*, sp.project_name
            FROM client_invoices ci
            LEFT JOIN studio_projects sp ON ci.project_id = sp.id
            WHERE ci.client_id = ?
            ORDER BY ci.created_at DESC";

    $stmt = $database_handler->getConnection()->prepare($sql);
    $stmt->execute([$currentUser['id']]);
    $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate totals
    $totalAmount = 0;
    $paidAmount = 0;
    $pendingAmount = 0;

    foreach ($invoices as $invoice) {
        $totalAmount += $invoice['total_amount'];
        if ($invoice['status'] === 'paid') {
            $paidAmount += $invoice['total_amount'];
        } else {
            $pendingAmount += $invoice['total_amount'];
        }
    }

} catch (Exception $e) {
    error_log("Error getting invoices: " . $e->getMessage());
    $invoices = [];
    $totalAmount = $paidAmount = $pendingAmount = 0;
}

// Get flash messages
$flashMessages = $flashMessageService->getAllMessages();
$pageTitle = 'Invoices & Billing - Client Portal';

?>

    <link rel="stylesheet" href="/public/assets/css/admin.css">

<!-- Navigation -->
<nav class="admin-nav">
    <div class="admin-nav-container">
        <a href="/index.php?page=user_dashboard" class="admin-nav-brand">
            <i class="fas fa-file-invoice-dollar"></i>
            Billing Portal
        </a>
        <div class="admin-nav-links">
            <a href="/index.php?page=user_dashboard" class="admin-nav-link">
                <i class="fas fa-home"></i> Dashboard
            </a>
        </div>
    </div>
</nav>

<!-- Header -->
<header class="admin-header">
    <div class="admin-header-container">
        <div class="admin-header-content">
            <div class="admin-header-title">
                <i class="admin-header-icon fas fa-file-invoice-dollar"></i>
                <div class="admin-header-text">
                    <h1>Invoices & Billing</h1>
                    <p>View and manage your invoices and payments</p>
                </div>
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

        <!-- Summary Cards -->
        <div class="admin-stats-grid" style="margin-bottom: 2rem;">
            <div class="admin-stat-card">
                <div class="admin-stat-content">
                    <div class="admin-stat-icon admin-stat-icon-primary">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div class="admin-stat-details">
                        <h3>Total Invoiced</h3>
                        <p style="color: var(--admin-text-primary);">$<?= number_format($totalAmount, 2) ?></p>
                    </div>
                </div>
            </div>
            <div class="admin-stat-card">
                <div class="admin-stat-content">
                    <div class="admin-stat-icon admin-stat-icon-success">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="admin-stat-details">
                        <h3>Paid</h3>
                        <p style="color: var(--admin-text-primary);">$<?= number_format($paidAmount, 2) ?></p>
                    </div>
                </div>
            </div>
            <div class="admin-stat-card">
                <div class="admin-stat-content">
                    <div class="admin-stat-icon admin-stat-icon-warning">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="admin-stat-details">
                        <h3>Pending</h3>
                        <p style="color: var(--admin-text-primary);">$<?= number_format($pendingAmount, 2) ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Invoices List -->
        <div class="admin-card">
            <div class="admin-card-header">
                <h3 class="admin-card-title">
                    <i class="fas fa-list"></i>
                    Invoice History
                </h3>
            </div>
            <div class="admin-card-body">
                <?php if (empty($invoices)): ?>
                    <div style="text-align: center; padding: 3rem 0;">
                        <i class="fas fa-file-invoice fa-3x" style="color: var(--admin-text-muted); margin-bottom: 1rem;"></i>
                        <h5 style="color: var(--admin-text-muted); margin-bottom: 0.5rem;">No invoices found</h5>
                        <p style="color: var(--admin-text-muted);">You don't have any invoices yet.</p>
                    </div>
                <?php else: ?>
                    <div class="admin-table-container">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th style="width: 20%;">Invoice #</th>
                                    <th style="width: 25%;">Project</th>
                                    <th style="width: 15%;">Amount</th>
                                    <th style="width: 15%;">Due Date</th>
                                    <th style="width: 15%;">Status</th>
                                    <th style="width: 10%;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($invoices as $invoice): ?>
                                    <tr>
                                        <td>
                                            <strong><?= htmlspecialchars($invoice['invoice_number']) ?></strong>
                                            <br>
                                            <small style="color: var(--admin-text-muted);"><?= date('M j, Y', strtotime($invoice['created_at'])) ?></small>
                                        </td>
                                        <td>
                                            <?= $invoice['project_name'] ? htmlspecialchars($invoice['project_name']) : 'General Service' ?>
                                        </td>
                                        <td>
                                            <span style="font-size: 1.1rem; font-weight: 600;">$<?= number_format($invoice['total_amount'], 2) ?></span>
                                            <?php if ($invoice['tax_amount'] > 0): ?>
                                                <br><small style="color: var(--admin-text-muted);">Tax: $<?= number_format($invoice['tax_amount'], 2) ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?= date('M j, Y', strtotime($invoice['due_date'])) ?>
                                            <?php if (strtotime($invoice['due_date']) < time() && $invoice['status'] !== 'paid'): ?>
                                                <br><small style="color: var(--admin-error);">Overdue</small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="admin-badge admin-badge-<?= getInvoiceStatusBadgeClass($invoice['status']) ?>">
                                                <?= ucfirst($invoice['status']) ?>
                                            </span>
                                            <?php if ($invoice['payment_date']): ?>
                                                <br><small style="color: var(--admin-text-muted);">Paid: <?= date('M j, Y', strtotime($invoice['payment_date'])) ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="admin-table-actions">
                                                <?php if ($invoice['pdf_path']): ?>
                                                    <a href="/index.php?page=user_invoices_download&id=<?= $invoice['id'] ?>"
                                                       class="admin-btn admin-btn-sm admin-btn-primary" target="_blank">
                                                        <i class="fas fa-download"></i> <span>Download</span>
                                                    </a>
                                                <?php endif; ?>
                                                <button class="admin-btn admin-btn-sm admin-btn-secondary" onclick="viewInvoiceDetails(<?= $invoice['id'] ?>)">
                                                    <i class="fas fa-eye"></i> <span>View</span>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="admin-sidebar">
        <!-- Payment Information -->
        <div class="admin-card">
            <div class="admin-card-header">
                <h4 class="admin-card-title">
                    <i class="fas fa-credit-card"></i>
                    Payment Information
                </h4>
            </div>
            <div class="admin-card-body">
                <h6>Payment Methods</h6>
                <ul style="font-size: 0.875rem; margin-bottom: 1.5rem;">
                    <li>Bank Transfer</li>
                    <li>Credit/Debit Card</li>
                    <li>PayPal</li>
                    <li>Cryptocurrency</li>
                </ul>

                <h6>Payment Terms</h6>
                <ul style="font-size: 0.875rem;">
                    <li>Net 30 days from invoice date</li>
                    <li>Late fees may apply after due date</li>
                    <li>Payment confirmations sent via email</li>
                </ul>
            </div>
        </div>

        <!-- Support -->
        <div class="admin-card">
            <div class="admin-card-header">
                <h4 class="admin-card-title">
                    <i class="fas fa-question-circle"></i>
                    Billing Support
                </h4>
            </div>
            <div class="admin-card-body">
                <p style="font-size: 0.875rem; margin-bottom: 1rem;">
                    Questions about your invoices or payments?
                </p>
                <div style="display: grid; gap: 0.5rem;">
                    <a href="/index.php?page=user_tickets_create" class="admin-btn admin-btn-sm admin-btn-primary">
                        <i class="fas fa-ticket-alt"></i> Create Support Ticket
                    </a>
                    <a href="mailto:billing@darkheim.net" class="admin-btn admin-btn-sm admin-btn-secondary">
                        <i class="fas fa-envelope"></i> Email Billing
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Invoice Details Modal -->
<div class="admin-modal admin-hidden" id="invoiceModal">
    <div class="admin-modal-content">
        <div class="admin-modal-header">
            <h5>Invoice Details</h5>
            <button type="button" data-modal-close>
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="admin-modal-body" id="invoiceModalBody">
            <div style="text-align: center; padding: 2rem;">
                <div class="admin-spinner"></div>
                <p style="color: var(--admin-text-muted); margin-top: 1rem;">Loading...</p>
            </div>
        </div>
    </div>
</div>

<script src="/public/assets/js/admin.js"></script>
<script>
function viewInvoiceDetails(invoiceId) {
    const modal = document.getElementById('invoiceModal');
    modal.classList.remove('admin-hidden');
    modal.style.display = 'flex';

    // Here you would load invoice details via AJAX
    // For now, just show a placeholder
    setTimeout(() => {
        document.getElementById('invoiceModalBody').innerHTML = `
            <h6>Invoice #INV-${invoiceId}</h6>
            <p>Detailed invoice information would be loaded here via AJAX.</p>
            <div class="admin-flash-message admin-flash-info">
                <i class="fas fa-construction"></i>
                <div>Full invoice details functionality is under development in Phase 8.</div>
            </div>
        `;
    }, 500);
}
</script>


<?php
function getInvoiceStatusBadgeClass($status): string
{
    return match($status) {
        'sent' => 'info',
        'paid' => 'success',
        'overdue' => 'error',
        default => 'gray'
    };
}
?>
