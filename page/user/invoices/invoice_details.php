<?php
/**
 * Invoice Details - Client Portal
 * Detailed view of a single invoice with payment history
 */

require_once '../../../includes/bootstrap.php';

use App\Application\Core\ServiceProvider;
use App\Application\Controllers\ClientInvoiceController;

$services = ServiceProvider::getInstance();
$auth = $services->getAuth();
$user = $auth->getCurrentUser();

// Check if user is authenticated and is a client
if (!$user || !in_array($user['role'], ['client', 'admin'])) {
    header('Location: /page/auth/login.php');
    exit;
}

// Initialize controller and get data
$controller = new ClientInvoiceController($services);
$data = $controller->getInvoiceDetails();

if (!$data['success']) {
    $error = $data['error'];
} else {
    $invoice = $data['invoice'];
    $items = $data['items'];
    $payments = $data['payments'];
    $paymentSummary = $data['payment_summary'];
}

// Page metadata
$pageTitle = isset($invoice) ? 'Invoice #' . $invoice['invoice_number'] . ' - Details' : 'Invoice Details';
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => '/page/user/dashboard.php'],
    ['title' => 'Invoices', 'url' => '/page/user/invoices/index.php'],
    ['title' => isset($invoice) ? 'Invoice #' . $invoice['invoice_number'] : 'Invoice Details', 'url' => '', 'active' => true]
];
?>

<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> - <?= htmlspecialchars(getSiteName()) ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="/themes/default/css/admin.css" rel="stylesheet">
    
    <style>
        .invoice-header {
            background: linear-gradient(135deg, var(--bs-primary), var(--bs-primary-dark));
            border-radius: 10px;
            padding: 2rem;
            color: white;
            margin-bottom: 2rem;
        }
        .payment-progress-large {
            height: 15px;
            border-radius: 7px;
        }
        .invoice-table th {
            background-color: var(--bs-secondary-bg);
        }
        .payment-item {
            border-left: 4px solid var(--bs-success);
            padding: 15px;
            margin-bottom: 10px;
            background: rgba(var(--bs-success-rgb), 0.1);
            border-radius: 5px;
        }
        @media print {
            .no-print {
                display: none !important;
            }
            .invoice-header {
                background: #333 !important;
                -webkit-print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <?php include '../../../themes/default/components/admin_navigation.php'; ?>
        
        <div class="admin-content">
            <div class="content-header no-print">
                <div class="container-fluid">
                    <div class="row mb-3">
                        <div class="col-sm-6">
                            <h1 class="m-0">
                                <?= isset($invoice) ? 'Invoice #' . htmlspecialchars($invoice['invoice_number']) : 'Invoice Details' ?>
                            </h1>
                        </div>
                        <div class="col-sm-6">
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb float-sm-end">
                                    <?php foreach ($breadcrumbs as $crumb): ?>
                                        <li class="breadcrumb-item <?= $crumb['active'] ?? false ? 'active' : '' ?>">
                                            <?php if ($crumb['active'] ?? false): ?>
                                                <?= htmlspecialchars($crumb['title']) ?>
                                            <?php else: ?>
                                                <a href="<?= htmlspecialchars($crumb['url']) ?>"><?= htmlspecialchars($crumb['title']) ?></a>
                                            <?php endif; ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ol>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>

            <div class="content-body">
                <div class="container-fluid">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            <?= htmlspecialchars($error) ?>
                        </div>
                        <div class="text-center">
                            <a href="/page/user/invoices/index.php" class="btn btn-primary">
                                <i class="bi bi-arrow-left me-2"></i>Back to Invoices
                            </a>
                        </div>
                    <?php else: ?>
                        
                        <!-- Action Buttons -->
                        <div class="row mb-4 no-print">
                            <div class="col-12">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <a href="/page/user/invoices/index.php" class="btn btn-outline-secondary">
                                            <i class="bi bi-arrow-left me-2"></i>Back to Invoices
                                        </a>
                                    </div>
                                    <div>
                                        <button class="btn btn-outline-primary me-2" onclick="window.print()">
                                            <i class="bi bi-printer me-2"></i>Print
                                        </button>
                                        <?php if ($invoice['status'] !== 'paid' && $paymentSummary['outstanding_amount'] > 0): ?>
                                            <button class="btn btn-warning" onclick="alert('Payment system integration coming soon!')">
                                                <i class="bi bi-credit-card me-2"></i>Pay <?= $paymentSummary['outstanding_amount_formatted'] ?>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Invoice Header -->
                        <div class="invoice-header">
                            <div class="row">
                                <div class="col-md-6">
                                    <h2 class="mb-3">Invoice #<?= htmlspecialchars($invoice['invoice_number']) ?></h2>
                                    <div class="mb-2">
                                        <span class="badge <?= $invoice['status_badge_class'] ?> fs-6 me-2">
                                            <?= ucfirst($invoice['status']) ?>
                                        </span>
                                        <?php if ($invoice['project_name']): ?>
                                            <span class="badge bg-light text-dark fs-6">
                                                Project: <?= htmlspecialchars($invoice['project_name']) ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <h4 class="mb-3"><?= htmlspecialchars($invoice['title']) ?></h4>
                                </div>
                                <div class="col-md-6 text-md-end">
                                    <div class="mb-3">
                                        <div class="display-4 fw-bold"><?= $invoice['total_amount_formatted'] ?></div>
                                        <?php if ($paymentSummary['outstanding_amount'] > 0): ?>
                                            <div class="fs-5">Outstanding: <?= $paymentSummary['outstanding_amount_formatted'] ?></div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Payment Progress -->
                                    <?php if ($paymentSummary['payment_progress'] > 0): ?>
                                        <div class="mb-3">
                                            <div class="d-flex justify-content-between mb-2">
                                                <span>Payment Progress</span>
                                                <span><?= $paymentSummary['payment_progress'] ?>%</span>
                                            </div>
                                            <div class="progress payment-progress-large">
                                                <div class="progress-bar bg-success" style="width: <?= $paymentSummary['payment_progress'] ?>%"></div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Invoice Details -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h6 class="mb-0">Invoice Information</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row mb-3">
                                            <div class="col-sm-5"><strong>Issue Date:</strong></div>
                                            <div class="col-sm-7"><?= $invoice['issue_date_formatted'] ?></div>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-sm-5"><strong>Due Date:</strong></div>
                                            <div class="col-sm-7"><?= $invoice['due_date_formatted'] ?></div>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-sm-5"><strong>Currency:</strong></div>
                                            <div class="col-sm-7"><?= htmlspecialchars($invoice['currency']) ?></div>
                                        </div>
                                        <?php if ($invoice['payment_date']): ?>
                                            <div class="row mb-3">
                                                <div class="col-sm-5"><strong>Paid Date:</strong></div>
                                                <div class="col-sm-7"><?= $invoice['payment_date_formatted'] ?></div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h6 class="mb-0">Client Information</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row mb-3">
                                            <div class="col-sm-4"><strong>Name:</strong></div>
                                            <div class="col-sm-8"><?= htmlspecialchars($invoice['client_name']) ?></div>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-sm-4"><strong>Email:</strong></div>
                                            <div class="col-sm-8"><?= htmlspecialchars($invoice['client_email']) ?></div>
                                        </div>
                                        <?php if ($invoice['created_by_name']): ?>
                                            <div class="row mb-3">
                                                <div class="col-sm-4"><strong>Created By:</strong></div>
                                                <div class="col-sm-8"><?= htmlspecialchars($invoice['created_by_name']) ?></div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Description -->
                        <?php if ($invoice['description']): ?>
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h6 class="mb-0">Description</h6>
                                </div>
                                <div class="card-body">
                                    <p class="mb-0"><?= nl2br(htmlspecialchars($invoice['description'])) ?></p>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Invoice Items -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h6 class="mb-0">Invoice Items</h6>
                            </div>
                            <div class="card-body">
                                <?php if (empty($items)): ?>
                                    <p class="text-muted text-center py-4">No items found for this invoice.</p>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-striped invoice-table">
                                            <thead>
                                                <tr>
                                                    <th>Description</th>
                                                    <th class="text-center">Quantity</th>
                                                    <th class="text-end">Unit Price</th>
                                                    <th class="text-end">Total</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($items as $item): ?>
                                                    <tr>
                                                        <td>
                                                            <div class="fw-medium"><?= htmlspecialchars($item['description']) ?></div>
                                                            <?php if ($item['details']): ?>
                                                                <div class="small text-muted"><?= htmlspecialchars($item['details']) ?></div>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td class="text-center"><?= $item['quantity'] ?></td>
                                                        <td class="text-end"><?= $item['unit_price_formatted'] ?></td>
                                                        <td class="text-end fw-bold"><?= $item['total_price_formatted'] ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                            <tfoot>
                                                <tr>
                                                    <td colspan="3" class="text-end fw-bold">Subtotal:</td>
                                                    <td class="text-end fw-bold"><?= $invoice['subtotal_formatted'] ?></td>
                                                </tr>
                                                <?php if ($invoice['discount_amount'] > 0): ?>
                                                    <tr>
                                                        <td colspan="3" class="text-end">Discount:</td>
                                                        <td class="text-end text-success">-<?= $invoice['discount_amount_formatted'] ?></td>
                                                    </tr>
                                                <?php endif; ?>
                                                <?php if ($invoice['tax_amount'] > 0): ?>
                                                    <tr>
                                                        <td colspan="3" class="text-end">Tax (<?= $invoice['tax_rate'] ?>%):</td>
                                                        <td class="text-end"><?= $invoice['tax_amount_formatted'] ?></td>
                                                    </tr>
                                                <?php endif; ?>
                                                <tr class="table-dark">
                                                    <td colspan="3" class="text-end fw-bold fs-5">Total:</td>
                                                    <td class="text-end fw-bold fs-5"><?= $invoice['total_amount_formatted'] ?></td>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Payment History -->
                        <?php if (!empty($payments)): ?>
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h6 class="mb-0">
                                        <i class="bi bi-credit-card me-2"></i>Payment History
                                        <span class="badge bg-success ms-2"><?= count($payments) ?> payment(s)</span>
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <?php foreach ($payments as $payment): ?>
                                        <div class="payment-item">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div>
                                                    <h6 class="mb-1">Payment of <?= $payment['amount_formatted'] ?></h6>
                                                    <div class="small text-muted mb-2">
                                                        <i class="bi bi-calendar me-1"></i>
                                                        <?= $payment['payment_date_formatted'] ?>
                                                        <span class="ms-3">
                                                            <i class="bi bi-credit-card me-1"></i>
                                                            <?= $payment['payment_method_display'] ?>
                                                        </span>
                                                        <?php if ($payment['processed_by_name']): ?>
                                                            <span class="ms-3">
                                                                <i class="bi bi-person me-1"></i>
                                                                Processed by: <?= htmlspecialchars($payment['processed_by_name']) ?>
                                                            </span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <?php if ($payment['payment_reference']): ?>
                                                        <div class="small">
                                                            <strong>Reference:</strong> <?= htmlspecialchars($payment['payment_reference']) ?>
                                                        </div>
                                                    <?php endif; ?>
                                                    <?php if ($payment['notes']): ?>
                                                        <div class="small text-muted mt-2">
                                                            <strong>Notes:</strong> <?= htmlspecialchars($payment['notes']) ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="text-success fs-4 fw-bold">
                                                    <i class="bi bi-check-circle"></i>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Payment Terms and Notes -->
                        <?php if ($invoice['payment_terms'] || $invoice['notes']): ?>
                            <div class="row">
                                <?php if ($invoice['payment_terms']): ?>
                                    <div class="col-md-6">
                                        <div class="card">
                                            <div class="card-header">
                                                <h6 class="mb-0">Payment Terms</h6>
                                            </div>
                                            <div class="card-body">
                                                <p class="mb-0"><?= nl2br(htmlspecialchars($invoice['payment_terms'])) ?></p>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($invoice['notes']): ?>
                                    <div class="col-md-6">
                                        <div class="card">
                                            <div class="card-header">
                                                <h6 class="mb-0">Additional Notes</h6>
                                            </div>
                                            <div class="card-body">
                                                <p class="mb-0"><?= nl2br(htmlspecialchars($invoice['notes'])) ?></p>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
