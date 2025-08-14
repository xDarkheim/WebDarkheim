<?php

/**
 * Invoice Model
 * Handles invoice operations and database interactions
 *
 * @author Darkheim Studio
 * @version 1.0.0
 */

declare(strict_types=1);

namespace App\Domain\Models;

use App\Domain\Interfaces\DatabaseInterface;
use PDO;
use Exception;

class Invoice
{
    private DatabaseInterface $database;

    // Invoice status constants
    const STATUS_DRAFT = 'draft';
    const STATUS_SENT = 'sent';
    const STATUS_VIEWED = 'viewed';
    const STATUS_PAID = 'paid';
    const STATUS_OVERDUE = 'overdue';
    const STATUS_CANCELLED = 'cancelled';

    // Payment methods
    const PAYMENT_BANK_TRANSFER = 'bank_transfer';
    const PAYMENT_PAYPAL = 'paypal';
    const PAYMENT_STRIPE = 'stripe';
    const PAYMENT_CRYPTO = 'crypto';
    const PAYMENT_CASH = 'cash';
    const PAYMENT_OTHER = 'other';

    public function __construct(DatabaseInterface $database)
    {
        $this->database = $database;
    }

    /**
     * Create a new invoice
     */
    public function createInvoice(array $data): ?int
    {
        try {
            $conn = $this->database->getConnection();

            // Generate invoice number
            $invoiceNumber = $this->generateInvoiceNumber();

            $sql = "INSERT INTO invoices (
                invoice_number, client_user_id, project_id, title, description, 
                status, issue_date, due_date, subtotal, tax_rate, tax_amount, 
                discount_amount, total_amount, currency, notes, payment_terms, 
                created_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt = $conn->prepare($sql);
            $stmt->execute([
                $invoiceNumber,
                $data['client_user_id'],
                $data['project_id'] ?? null,
                $data['title'],
                $data['description'] ?? null,
                $data['status'] ?? self::STATUS_DRAFT,
                $data['issue_date'],
                $data['due_date'],
                $data['subtotal'] ?? 0.00,
                $data['tax_rate'] ?? 0.00,
                $data['tax_amount'] ?? 0.00,
                $data['discount_amount'] ?? 0.00,
                $data['total_amount'] ?? 0.00,
                $data['currency'] ?? 'USD',
                $data['notes'] ?? null,
                $data['payment_terms'] ?? null,
                $data['created_by']
            ]);

            $invoiceId = (int)$conn->lastInsertId();

            // Log activity
            $this->logActivity($invoiceId, $data['created_by'], 'created', 'Invoice created');

            return $invoiceId;
        } catch (Exception $e) {
            error_log("Error creating invoice: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Add items to invoice
     */
    public function addInvoiceItems(int $invoiceId, array $items): bool
    {
        try {
            $conn = $this->database->getConnection();

            $sql = "INSERT INTO invoice_items (invoice_id, description, quantity, unit_price, line_total, sort_order) 
                   VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);

            foreach ($items as $index => $item) {
                $lineTotal = $item['quantity'] * $item['unit_price'];
                $stmt->execute([
                    $invoiceId,
                    $item['description'],
                    $item['quantity'],
                    $item['unit_price'],
                    $lineTotal,
                    $index
                ]);
            }

            // Recalculate invoice totals
            $this->recalculateInvoiceTotals($invoiceId);

            return true;
        } catch (Exception $e) {
            error_log("Error adding invoice items: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get invoice by ID with client access check
     */
    public function getInvoiceById(int $invoiceId, ?int $clientUserId = null): ?array
    {
        try {
            $conn = $this->database->getConnection();

            $sql = "SELECT i.*, 
                          u.first_name, u.last_name, u.email as client_email,
                          c.first_name as created_by_first_name, c.last_name as created_by_last_name
                   FROM invoices i
                   LEFT JOIN users u ON i.client_user_id = u.id
                   LEFT JOIN users c ON i.created_by = c.id
                   WHERE i.id = ?";

            $params = [$invoiceId];

            // If client user ID provided, filter by it
            if ($clientUserId !== null) {
                $sql .= " AND i.client_user_id = ?";
                $params[] = $clientUserId;
            }

            $stmt = $conn->prepare($sql);
            $stmt->execute($params);

            $invoice = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($invoice) {
                // Get invoice items
                $invoice['items'] = $this->getInvoiceItems($invoiceId);

                // Get payment history
                $invoice['payments'] = $this->getInvoicePayments($invoiceId);

                // Calculate remaining balance
                $totalPaid = array_sum(array_column($invoice['payments'], 'amount'));
                $invoice['balance_remaining'] = $invoice['total_amount'] - $totalPaid;
                $invoice['total_paid'] = $totalPaid;

                return $invoice;
            }

            return null;
        } catch (Exception $e) {
            error_log("Error getting invoice: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get invoices for a client with filtering
     */
    public function getClientInvoices(int $clientUserId, array $filters = []): array
    {
        try {
            $conn = $this->database->getConnection();

            $sql = "SELECT i.*, 
                          COALESCE(SUM(p.amount), 0) as total_paid,
                          (i.total_amount - COALESCE(SUM(p.amount), 0)) as balance_remaining
                   FROM invoices i
                   LEFT JOIN invoice_payments p ON i.id = p.invoice_id
                   WHERE i.client_user_id = ?";

            $params = [$clientUserId];

            // Apply filters
            if (!empty($filters['status'])) {
                $sql .= " AND i.status = ?";
                $params[] = $filters['status'];
            }

            if (!empty($filters['date_from'])) {
                $sql .= " AND i.issue_date >= ?";
                $params[] = $filters['date_from'];
            }

            if (!empty($filters['date_to'])) {
                $sql .= " AND i.issue_date <= ?";
                $params[] = $filters['date_to'];
            }

            $sql .= " GROUP BY i.id ORDER BY i.issue_date DESC, i.created_at DESC";

            if (!empty($filters['limit'])) {
                $sql .= " LIMIT " . (int)$filters['limit'];
            }

            $stmt = $conn->prepare($sql);
            $stmt->execute($params);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting client invoices: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get invoice items
     */
    public function getInvoiceItems(int $invoiceId): array
    {
        try {
            $conn = $this->database->getConnection();

            $sql = "SELECT * FROM invoice_items WHERE invoice_id = ? ORDER BY sort_order ASC, id ASC";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$invoiceId]);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting invoice items: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get invoice payments
     */
    public function getInvoicePayments(int $invoiceId): array
    {
        try {
            $conn = $this->database->getConnection();

            $sql = "SELECT p.*, u.first_name, u.last_name 
                   FROM invoice_payments p
                   LEFT JOIN users u ON p.processed_by = u.id
                   WHERE p.invoice_id = ? 
                   ORDER BY p.payment_date DESC";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$invoiceId]);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting invoice payments: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Update invoice status
     */
    public function updateStatus(int $invoiceId, string $status, int $userId): bool
    {
        try {
            $conn = $this->database->getConnection();

            // Get current status for logging
            $stmt = $conn->prepare("SELECT status FROM invoices WHERE id = ?");
            $stmt->execute([$invoiceId]);
            $oldStatus = $stmt->fetchColumn();

            $sql = "UPDATE invoices SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $result = $stmt->execute([$status, $invoiceId]);

            if ($result) {
                $this->logActivity($invoiceId, $userId, 'status_changed',
                    "Status changed from {$oldStatus} to {$status}", $oldStatus, $status);
            }

            return $result;
        } catch (Exception $e) {
            error_log("Error updating invoice status: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Mark invoice as viewed by client
     */
    public function markAsViewed(int $invoiceId, int $clientUserId): bool
    {
        try {
            $conn = $this->database->getConnection();

            // Only update if current status is 'sent'
            $sql = "UPDATE invoices SET status = ? WHERE id = ? AND client_user_id = ? AND status = ?";
            $stmt = $conn->prepare($sql);
            $result = $stmt->execute([self::STATUS_VIEWED, $invoiceId, $clientUserId, self::STATUS_SENT]);

            if ($result && $stmt->rowCount() > 0) {
                $this->logActivity($invoiceId, $clientUserId, 'viewed', 'Invoice viewed by client');
            }

            return true; // Return true even if no update to avoid errors
        } catch (Exception $e) {
            error_log("Error marking invoice as viewed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get invoice statistics for client
     */
    public function getClientStatistics(int $clientUserId): array
    {
        try {
            $conn = $this->database->getConnection();

            $sql = "SELECT 
                      COUNT(*) as total_invoices,
                      SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft_count,
                      SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent_count,
                      SUM(CASE WHEN status = 'viewed' THEN 1 ELSE 0 END) as viewed_count,
                      SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) as paid_count,
                      SUM(CASE WHEN status = 'overdue' THEN 1 ELSE 0 END) as overdue_count,
                      SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_count,
                      SUM(total_amount) as total_billed,
                      SUM(CASE WHEN status = 'paid' THEN total_amount ELSE 0 END) as total_paid,
                      SUM(CASE WHEN status IN ('sent', 'viewed', 'overdue') THEN total_amount ELSE 0 END) as total_outstanding
                   FROM invoices 
                   WHERE client_user_id = ?";

            $stmt = $conn->prepare($sql);
            $stmt->execute([$clientUserId]);

            return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        } catch (Exception $e) {
            error_log("Error getting client invoice statistics: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Generate next invoice number
     */
    private function generateInvoiceNumber(): string
    {
        try {
            $conn = $this->database->getConnection();

            // Get prefix and next number
            $sql = "SELECT setting_value FROM invoice_settings WHERE setting_key IN ('invoice_prefix', 'next_invoice_number')";
            $stmt = $conn->prepare($sql);
            $stmt->execute();

            $settings = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $settings[] = $row;
            }

            $prefix = 'DH-';
            $nextNumber = 1001;

            foreach ($settings as $setting) {
                if (strpos($setting['setting_key'], 'prefix') !== false) {
                    $prefix = $setting['setting_value'];
                } elseif (strpos($setting['setting_key'], 'next_invoice_number') !== false) {
                    $nextNumber = (int)$setting['setting_value'];
                }
            }

            return $prefix . str_pad((string)$nextNumber, 4, '0', STR_PAD_LEFT);
        } catch (Exception $e) {
            error_log("Error generating invoice number: " . $e->getMessage());
            return 'DH-' . str_pad((string)time(), 4, '0', STR_PAD_LEFT);
        }
    }

    /**
     * Recalculate invoice totals based on items
     */
    private function recalculateInvoiceTotals(int $invoiceId): bool
    {
        try {
            $conn = $this->database->getConnection();

            // Get sum of all line totals
            $sql = "SELECT SUM(line_total) as subtotal FROM invoice_items WHERE invoice_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$invoiceId]);
            $subtotal = $stmt->fetchColumn() ?: 0.00;

            // Get tax rate and discount
            $sql = "SELECT tax_rate, discount_amount FROM invoices WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$invoiceId]);
            $invoice = $stmt->fetch(PDO::FETCH_ASSOC);

            $taxRate = $invoice['tax_rate'] ?? 0.00;
            $discountAmount = $invoice['discount_amount'] ?? 0.00;

            // Calculate totals
            $taxAmount = ($subtotal - $discountAmount) * ($taxRate / 100);
            $totalAmount = $subtotal - $discountAmount + $taxAmount;

            // Update invoice
            $sql = "UPDATE invoices SET subtotal = ?, tax_amount = ?, total_amount = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);

            return $stmt->execute([$subtotal, $taxAmount, $totalAmount, $invoiceId]);
        } catch (Exception $e) {
            error_log("Error recalculating invoice totals: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Log invoice activity
     */
    private function logActivity(int $invoiceId, int $userId, string $action, ?string $description = null, ?string $oldValue = null, ?string $newValue = null): bool
    {
        try {
            $conn = $this->database->getConnection();

            $sql = "INSERT INTO invoice_activities (invoice_id, user_id, action, description, old_value, new_value, ip_address, user_agent) 
                   VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt = $conn->prepare($sql);
            return $stmt->execute([
                $invoiceId,
                $userId,
                $action,
                $description,
                $oldValue,
                $newValue,
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null
            ]);
        } catch (Exception $e) {
            error_log("Error logging invoice activity: " . $e->getMessage());
            return false;
        }
    }
}
