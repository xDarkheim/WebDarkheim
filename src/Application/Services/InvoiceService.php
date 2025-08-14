<?php

/**
 * Invoice Service - PHASE 8 Client Portal
 * Handles invoice operations and billing information using Invoice model
 *
 * @author Darkheim Studio
 */

declare(strict_types=1);

namespace App\Application\Services;

use App\Domain\Interfaces\DatabaseInterface;
use App\Domain\Interfaces\LoggerInterface;
use App\Domain\Models\Invoice;
use Exception;

class InvoiceService
{
    private DatabaseInterface $database;
    private LoggerInterface $logger;
    private Invoice $invoiceModel;

    public function __construct(DatabaseInterface $database, LoggerInterface $logger)
    {
        $this->database = $database;
        $this->logger = $logger;
        $this->invoiceModel = new Invoice($database);
    }

    /**
     * Get client invoices with filters (using Invoice model)
     */
    public function getClientInvoices(int $userId, array $filters = []): array
    {
        try {
            $invoices = $this->invoiceModel->getClientInvoices($userId, $filters);

            // Format the data for display
            return array_map([$this, 'formatInvoiceData'], $invoices);

        } catch (Exception $e) {
            $this->logger->error('Failed to get client invoices', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'filters' => $filters
            ]);
            return [];
        }
    }

    /**
     * Get client statistics (using Invoice model)
     */
    public function getClientStatistics(int $userId): array
    {
        try {
            $stats = $this->invoiceModel->getClientStatistics($userId);

            if (empty($stats)) {
                return $this->getEmptyStatistics();
            }

            $totalBilled = (float)($stats['total_billed'] ?? 0);
            $totalPaid = (float)($stats['total_paid'] ?? 0);
            $totalOutstanding = (float)($stats['total_outstanding'] ?? 0);

            return [
                'total_invoices' => (int)($stats['total_invoices'] ?? 0),
                'paid_invoices' => (int)($stats['paid_count'] ?? 0),
                'pending_invoices' => (int)(($stats['sent_count'] ?? 0) + ($stats['viewed_count'] ?? 0)),
                'overdue_invoices' => (int)($stats['overdue_count'] ?? 0),
                'total_paid' => $totalPaid,
                'total_pending' => $totalOutstanding,
                'total_amount' => $totalBilled,
                'total_outstanding' => $totalOutstanding,
                'formatted_total_paid' => '$' . number_format($totalPaid, 2),
                'formatted_total_pending' => '$' . number_format($totalOutstanding, 2),
                'formatted_total_billed' => '$' . number_format($totalBilled, 2),
                'formatted_total_outstanding' => '$' . number_format($totalOutstanding, 2),
                'overdue_count' => (int)($stats['overdue_count'] ?? 0)
            ];

        } catch (Exception $e) {
            $this->logger->error('Failed to get client statistics', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);

            return $this->getEmptyStatistics();
        }
    }

    /**
     * Get status filters for dropdown
     */
    public function getStatusFilters(): array
    {
        return [
            'all' => 'All Invoices',
            'sent' => 'Sent',
            'viewed' => 'Viewed',
            'paid' => 'Paid',
            'overdue' => 'Overdue'
        ];
    }

    /**
     * Get empty statistics structure
     */
    public function getEmptyStatistics(): array
    {
        return [
            'total_invoices' => 0,
            'paid_invoices' => 0,
            'pending_invoices' => 0,
            'overdue_invoices' => 0,
            'total_paid' => 0.0,
            'total_pending' => 0.0,
            'total_amount' => 0.0,
            'total_outstanding' => 0.0,
            'formatted_total_paid' => '$0.00',
            'formatted_total_pending' => '$0.00',
            'formatted_total_billed' => '$0.00',
            'formatted_total_outstanding' => '$0.00',
            'overdue_count' => 0
        ];
    }

    /**
     * Check if invoice system is available (check if tables exist)
     */
    public function isSystemAvailable(): bool
    {
        try {
            $stmt = $this->database->query("SHOW TABLES LIKE 'invoices'");
            return $stmt->rowCount() > 0;
        } catch (Exception $e) {
            $this->logger->warning('Could not check invoice table availability', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get invoice by ID (using Invoice model with user ownership check)
     */
    public function getInvoiceById(int $invoiceId, int $userId): ?array
    {
        try {
            $invoice = $this->invoiceModel->getInvoiceById($invoiceId, $userId);

            if (!$invoice) {
                return null;
            }

            return $this->formatInvoiceData($invoice);

        } catch (Exception $e) {
            $this->logger->error('Failed to get invoice by ID', [
                'invoice_id' => $invoiceId,
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Mark invoice as viewed by client (using Invoice model)
     */
    public function markInvoiceAsViewed(int $invoiceId, int $clientUserId): bool
    {
        try {
            return $this->invoiceModel->markAsViewed($invoiceId, $clientUserId);
        } catch (Exception $e) {
            $this->logger->error('Failed to mark invoice as viewed', [
                'invoice_id' => $invoiceId,
                'user_id' => $clientUserId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Format invoice data for display
     */
    private function formatInvoiceData(array $invoice): array
    {
        $balanceRemaining = (float)($invoice['balance_remaining'] ?? 0);
        $totalAmount = (float)($invoice['total_amount'] ?? 0);
        $totalPaid = (float)($invoice['total_paid'] ?? 0);

        // Calculate days until due
        $dueDate = $invoice['due_date'] ?? null;
        $daysUntilDue = 0;
        $isOverdue = false;

        if ($dueDate) {
            $today = new \DateTime();
            $due = new \DateTime($dueDate);
            $diff = $today->diff($due);
            $daysUntilDue = $diff->invert ? -$diff->days : $diff->days;
            $isOverdue = ($daysUntilDue < 0 && $invoice['status'] !== 'paid');
        }

        // Format the invoice data
        $invoice['formatted_total'] = '$' . number_format($totalAmount, 2);
        $invoice['formatted_balance'] = '$' . number_format($balanceRemaining, 2);
        $invoice['days_until_due'] = $daysUntilDue;
        $invoice['is_overdue'] = $isOverdue;
        $invoice['balance_remaining'] = $balanceRemaining;
        $invoice['total_paid'] = $totalPaid;

        return $invoice;
    }
}
