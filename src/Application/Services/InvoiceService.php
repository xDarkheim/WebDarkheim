<?php

/**
 * Invoice Service
 * Business logic for invoice operations
 *
 * @author Darkheim Studio
 * @version 1.0.0
 */

declare(strict_types=1);

namespace App\Application\Services;

use App\Domain\Models\Invoice;
use App\Domain\Interfaces\DatabaseInterface;
use App\Domain\Interfaces\LoggerInterface;
use Exception;

class InvoiceService
{
    private Invoice $invoiceModel;
    private LoggerInterface $logger;

    public function __construct(DatabaseInterface $database, LoggerInterface $logger)
    {
        $this->invoiceModel = new Invoice($database);
        $this->logger = $logger;
    }

    /**
     * Get client invoices with filtering and pagination
     */
    public function getClientInvoices(int $clientUserId, array $filters = []): array
    {
        try {
            $invoices = $this->invoiceModel->getClientInvoices($clientUserId, $filters);

            // Format data for display
            foreach ($invoices as &$invoice) {
                $invoice['status_badge_class'] = $this->getStatusBadgeClass($invoice['status']);
                $invoice['formatted_total'] = $this->formatCurrency($invoice['total_amount'], $invoice['currency']);
                $invoice['formatted_balance'] = $this->formatCurrency($invoice['balance_remaining'], $invoice['currency']);
                $invoice['is_overdue'] = $this->isInvoiceOverdue($invoice);
                $invoice['days_until_due'] = $this->getDaysUntilDue($invoice['due_date']);
            }

            return $invoices;
        } catch (Exception $e) {
            $this->logger->error('Error getting client invoices: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get invoice details for client view
     */
    public function getClientInvoiceDetails(int $invoiceId, int $clientUserId): ?array
    {
        try {
            $invoice = $this->invoiceModel->getInvoiceById($invoiceId, $clientUserId);

            if (!$invoice) {
                return null;
            }

            // Mark as viewed if status is 'sent'
            if ($invoice['status'] === Invoice::STATUS_SENT) {
                $this->invoiceModel->markAsViewed($invoiceId, $clientUserId);
                $invoice['status'] = Invoice::STATUS_VIEWED;
            }

            // Format data for display
            $invoice['status_badge_class'] = $this->getStatusBadgeClass($invoice['status']);
            $invoice['formatted_subtotal'] = $this->formatCurrency($invoice['subtotal'], $invoice['currency']);
            $invoice['formatted_tax_amount'] = $this->formatCurrency($invoice['tax_amount'], $invoice['currency']);
            $invoice['formatted_discount'] = $this->formatCurrency($invoice['discount_amount'], $invoice['currency']);
            $invoice['formatted_total'] = $this->formatCurrency($invoice['total_amount'], $invoice['currency']);
            $invoice['formatted_balance'] = $this->formatCurrency($invoice['balance_remaining'], $invoice['currency']);
            $invoice['formatted_paid'] = $this->formatCurrency($invoice['total_paid'], $invoice['currency']);
            $invoice['is_overdue'] = $this->isInvoiceOverdue($invoice);
            $invoice['days_until_due'] = $this->getDaysUntilDue($invoice['due_date']);
            $invoice['is_fully_paid'] = $invoice['balance_remaining'] <= 0;

            // Format items
            foreach ($invoice['items'] as &$item) {
                $item['formatted_unit_price'] = $this->formatCurrency($item['unit_price'], $invoice['currency']);
                $item['formatted_line_total'] = $this->formatCurrency($item['line_total'], $invoice['currency']);
            }

            // Format payments
            foreach ($invoice['payments'] as &$payment) {
                $payment['formatted_amount'] = $this->formatCurrency($payment['amount'], $invoice['currency']);
                $payment['formatted_date'] = date('M j, Y', strtotime($payment['payment_date']));
            }

            return $invoice;
        } catch (Exception $e) {
            $this->logger->error('Error getting invoice details: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get client invoice statistics
     */
    public function getClientStatistics(int $clientUserId): array
    {
        try {
            $stats = $this->invoiceModel->getClientStatistics($clientUserId);

            if (empty($stats)) {
                return $this->getEmptyStatistics();
            }

            // Format currency values
            $stats['formatted_total_billed'] = $this->formatCurrency($stats['total_billed']);
            $stats['formatted_total_paid'] = $this->formatCurrency($stats['total_paid']);
            $stats['formatted_total_outstanding'] = $this->formatCurrency($stats['total_outstanding']);

            // Calculate percentages
            if ($stats['total_invoices'] > 0) {
                $stats['paid_percentage'] = round(($stats['paid_count'] / $stats['total_invoices']) * 100, 1);
                $stats['outstanding_percentage'] = round((($stats['sent_count'] + $stats['viewed_count'] + $stats['overdue_count']) / $stats['total_invoices']) * 100, 1);
            } else {
                $stats['paid_percentage'] = 0;
                $stats['outstanding_percentage'] = 0;
            }

            return $stats;
        } catch (Exception $e) {
            $this->logger->error('Error getting client statistics: ' . $e->getMessage());
            return $this->getEmptyStatistics();
        }
    }

    /**
     * Get status badge CSS class
     */
    private function getStatusBadgeClass(string $status): string
    {
        return match ($status) {
            Invoice::STATUS_DRAFT => 'badge-secondary',
            Invoice::STATUS_SENT => 'badge-info',
            Invoice::STATUS_VIEWED => 'badge-primary',
            Invoice::STATUS_PAID => 'badge-success',
            Invoice::STATUS_OVERDUE => 'badge-danger',
            Invoice::STATUS_CANCELLED => 'badge-warning',
            default => 'badge-secondary'
        };
    }

    /**
     * Format currency amount
     */
    private function formatCurrency($amount, string $currency = 'USD'): string
    {
        // Convert to float if it's a string
        $amount = is_string($amount) ? (float)$amount : $amount;

        $symbols = [
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'JPY' => '¥'
        ];

        $symbol = $symbols[$currency] ?? '$';

        return $symbol . number_format($amount, 2);
    }

    /**
     * Check if invoice is overdue
     */
    private function isInvoiceOverdue(array $invoice): bool
    {
        if ($invoice['status'] === Invoice::STATUS_PAID || $invoice['status'] === Invoice::STATUS_CANCELLED) {
            return false;
        }

        return strtotime($invoice['due_date']) < time();
    }

    /**
     * Get days until due date
     */
    private function getDaysUntilDue(string $dueDate): int
    {
        $now = new \DateTime();
        $due = new \DateTime($dueDate);
        $interval = $now->diff($due);

        return $interval->invert ? -$interval->days : $interval->days;
    }

    /**
     * Get empty statistics structure
     */
    public function getEmptyStatistics(): array
    {
        return [
            'total_invoices' => 0,
            'draft_count' => 0,
            'sent_count' => 0,
            'viewed_count' => 0,
            'paid_count' => 0,
            'overdue_count' => 0,
            'cancelled_count' => 0,
            'total_billed' => 0,
            'total_paid' => 0,
            'total_outstanding' => 0,
            'formatted_total_billed' => '$0.00',
            'formatted_total_paid' => '$0.00',
            'formatted_total_outstanding' => '$0.00',
            'paid_percentage' => 0,
            'outstanding_percentage' => 0
        ];
    }

    /**
     * Get available status filters for UI
     */
    public function getStatusFilters(): array
    {
        return [
            '' => 'All Invoices',
            Invoice::STATUS_DRAFT => 'Draft',
            Invoice::STATUS_SENT => 'Sent',
            Invoice::STATUS_VIEWED => 'Viewed',
            Invoice::STATUS_PAID => 'Paid',
            Invoice::STATUS_OVERDUE => 'Overdue',
            Invoice::STATUS_CANCELLED => 'Cancelled'
        ];
    }

    /**
     * Get payment method labels
     */
    public function getPaymentMethodLabels(): array
    {
        return [
            Invoice::PAYMENT_BANK_TRANSFER => 'Bank Transfer',
            Invoice::PAYMENT_PAYPAL => 'PayPal',
            Invoice::PAYMENT_STRIPE => 'Credit Card',
            Invoice::PAYMENT_CRYPTO => 'Cryptocurrency',
            Invoice::PAYMENT_CASH => 'Cash',
            Invoice::PAYMENT_OTHER => 'Other'
        ];
    }
}
