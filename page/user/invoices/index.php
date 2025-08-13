<?php

/**
 * Invoices & Billing - Client Portal
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

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .invoice-card {
            transition: all 0.3s ease;
            border: none;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .invoice-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        .status-badge {
            font-size: 0.8rem;
        }
        .amount-large {
            font-size: 1.2rem;
            font-weight: bold;
        }
    </style>
</head>
<body class="bg-light">

<div class="container py-4">
    <!-- Breadcrumb Navigation -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/index.php?page=dashboard">Dashboard</a></li>
            <li class="breadcrumb-item active">Invoices & Billing</li>
        </ol>
    </nav>

    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3 mb-0">
                <i class="fas fa-file-invoice-dollar text-primary"></i>
                Invoices & Billing
            </h1>
            <p class="text-muted">View and manage your invoices and payments</p>
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

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-4 mb-3">
            <div class="card invoice-card bg-primary text-white">
                <div class="card-body text-center">
                    <i class="fas fa-dollar-sign fa-2x mb-2"></i>
                    <h4 class="mb-0">$<?= number_format($totalAmount, 2) ?></h4>
                    <p class="mb-0">Total Invoiced</p>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card invoice-card bg-success text-white">
                <div class="card-body text-center">
                    <i class="fas fa-check-circle fa-2x mb-2"></i>
                    <h4 class="mb-0">$<?= number_format($paidAmount, 2) ?></h4>
                    <p class="mb-0">Paid</p>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card invoice-card bg-warning text-white">
                <div class="card-body text-center">
                    <i class="fas fa-clock fa-2x mb-2"></i>
                    <h4 class="mb-0">$<?= number_format($pendingAmount, 2) ?></h4>
                    <p class="mb-0">Pending</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Invoices List -->
    <div class="card invoice-card">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="fas fa-list text-primary"></i>
                Invoice History
            </h5>
        </div>
        <div class="card-body">
            <?php if (empty($invoices)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-file-invoice fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">No invoices found</h5>
                    <p class="text-muted">You don't have any invoices yet.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Invoice #</th>
                                <th>Project</th>
                                <th>Amount</th>
                                <th>Due Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($invoices as $invoice): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($invoice['invoice_number']) ?></strong>
                                        <br>
                                        <small class="text-muted"><?= date('M j, Y', strtotime($invoice['created_at'])) ?></small>
                                    </td>
                                    <td>
                                        <?= $invoice['project_name'] ? htmlspecialchars($invoice['project_name']) : 'General Service' ?>
                                    </td>
                                    <td>
                                        <span class="amount-large">$<?= number_format($invoice['total_amount'], 2) ?></span>
                                        <?php if ($invoice['tax_amount'] > 0): ?>
                                            <br><small class="text-muted">Tax: $<?= number_format($invoice['tax_amount'], 2) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?= date('M j, Y', strtotime($invoice['due_date'])) ?>
                                        <?php if (strtotime($invoice['due_date']) < time() && $invoice['status'] !== 'paid'): ?>
                                            <br><small class="text-danger">Overdue</small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge <?= getInvoiceStatusBadgeClass($invoice['status']) ?> status-badge">
                                            <?= ucfirst($invoice['status']) ?>
                                        </span>
                                        <?php if ($invoice['payment_date']): ?>
                                            <br><small class="text-muted">Paid: <?= date('M j, Y', strtotime($invoice['payment_date'])) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <?php if ($invoice['pdf_path']): ?>
                                                <a href="/index.php?page=user_invoices_download&id=<?= $invoice['id'] ?>"
                                                   class="btn btn-outline-primary" target="_blank">
                                                    <i class="fas fa-download"></i> Download
                                                </a>
                                            <?php endif; ?>
                                            <button class="btn btn-outline-secondary" onclick="viewInvoiceDetails(<?= $invoice['id'] ?>)">
                                                <i class="fas fa-eye"></i> View
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

<!-- Invoice Details Modal -->
<div class="modal fade" id="invoiceModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Invoice Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="invoiceModalBody">
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function viewInvoiceDetails(invoiceId) {
    const modal = new bootstrap.Modal(document.getElementById('invoiceModal'));
    modal.show();

    // Here you would load invoice details via AJAX
    // For now, just show a placeholder
    setTimeout(() => {
        document.getElementById('invoiceModalBody').innerHTML = `
            <h6>Invoice #INV-${invoiceId}</h6>
            <p>Detailed invoice information would be loaded here via AJAX.</p>
        `;
    }, 500);
}
</script>

</body>
</html>

<?php
function getInvoiceStatusBadgeClass($status): string
{
    return match($status) {
        'draft' => 'bg-secondary',
        'sent' => 'bg-info',
        'paid' => 'bg-success',
        'overdue' => 'bg-danger',
        'cancelled' => 'bg-dark',
        default => 'bg-secondary'
    };
}
?>
