<?php

declare(strict_types=1);

namespace App\Domain\Models;

use PDO;
use PDOException;
use App\Domain\Interfaces\DatabaseInterface;
use InvalidArgumentException;
use RuntimeException;

/**
 * SupportTicket Model
 *
 * Manages support tickets in the client portal system.
 * Handles ticket creation, status updates, message management and moderation.
 *
 * @author GitHub Copilot
 */
class SupportTicket
{
    private DatabaseInterface $db;

    // Ticket properties
    public ?int $id = null;
    public ?int $client_id = null;
    public ?string $subject = null;
    public ?string $description = null;
    public string $priority = 'medium';
    public string $status = 'open';
    public ?string $category = null;
    public ?int $assigned_to = null;
    public ?string $created_at = null;
    public ?string $updated_at = null;

    // Valid enum values
    private const VALID_PRIORITIES = ['low', 'medium', 'high', 'critical'];
    private const VALID_STATUSES = ['open', 'in_progress', 'waiting_client', 'resolved', 'closed'];
    private const VALID_CATEGORIES = [
        'Technical Support', 'General Inquiry', 'Billing',
        'Feature Request', 'Project Discussion'
    ];

    public function __construct(DatabaseInterface $db)
    {
        $this->db = $db;
    }

    /**
     * Create a new support ticket
     */
    public function create(array $data): bool
    {
        try {
            // Validate required fields
            $this->validateTicketData($data);

            $sql = "INSERT INTO support_tickets 
                    (client_id, subject, description, priority, status, category, assigned_to) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";

            $params = [
                $data['client_id'],
                $data['subject'],
                $data['description'],
                $data['priority'] ?? 'medium',
                $data['status'] ?? 'open',
                $data['category'] ?? null,
                $data['assigned_to'] ?? null
            ];

            $stmt = $this->db->getConnection()->prepare($sql);
            $result = $stmt->execute($params);

            if ($result) {
                $this->id = (int)$this->db->getConnection()->lastInsertId();
                $this->loadFromArray($data);
                return true;
            }

            return false;

        } catch (PDOException $e) {
            error_log("SupportTicket::create() - Database error: " . $e->getMessage());
            throw new RuntimeException("Failed to create support ticket");
        }
    }

    /**
     * Find ticket by ID
     */
    public function findById(int $id): ?self
    {
        try {
            $sql = "SELECT st.*, u.username as client_username, 
                          a.username as assigned_username
                    FROM support_tickets st
                    LEFT JOIN users u ON st.client_id = u.id
                    LEFT JOIN users a ON st.assigned_to = a.id
                    WHERE st.id = ?";

            $stmt = $this->db->getConnection()->prepare($sql);
            $stmt->execute([$id]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($data) {
                $ticket = new self($this->db);
                $ticket->loadFromArray($data);
                return $ticket;
            }

            return null;

        } catch (PDOException $e) {
            error_log("SupportTicket::findById() - Database error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get tickets for specific client
     */
    public function getClientTickets(int $clientId, ?string $status = null, int $limit = 20, int $offset = 0): array
    {
        try {
            $sql = "SELECT st.*, u.username as assigned_username,
                          COUNT(tm.id) as message_count
                    FROM support_tickets st
                    LEFT JOIN users u ON st.assigned_to = u.id
                    LEFT JOIN ticket_messages tm ON st.id = tm.ticket_id
                    WHERE st.client_id = ?";

            $params = [$clientId];

            if ($status) {
                $sql .= " AND st.status = ?";
                $params[] = $status;
            }

            $sql .= " GROUP BY st.id ORDER BY st.updated_at DESC LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;

            $stmt = $this->db->getConnection()->prepare($sql);
            $stmt->execute($params);

            $tickets = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $ticket = new self($this->db);
                $ticket->loadFromArray($row);
                $tickets[] = $ticket;
            }

            return $tickets;

        } catch (PDOException $e) {
            error_log("SupportTicket::getClientTickets() - Database error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get tickets for moderation/admin view
     */
    public function getTicketsForModeration(?string $status = null, int $limit = 50, int $offset = 0): array
    {
        try {
            $sql = "SELECT st.*, u.username as client_username, 
                          a.username as assigned_username,
                          COUNT(tm.id) as message_count
                    FROM support_tickets st
                    LEFT JOIN users u ON st.client_id = u.id
                    LEFT JOIN users a ON st.assigned_to = a.id
                    LEFT JOIN ticket_messages tm ON st.id = tm.ticket_id
                    WHERE 1=1";

            $params = [];

            if ($status) {
                $sql .= " AND st.status = ?";
                $params[] = $status;
            }

            $sql .= " GROUP BY st.id ORDER BY 
                     CASE st.priority 
                         WHEN 'critical' THEN 1
                         WHEN 'high' THEN 2  
                         WHEN 'medium' THEN 3
                         WHEN 'low' THEN 4
                     END,
                     st.created_at ASC 
                     LIMIT ? OFFSET ?";

            $params[] = $limit;
            $params[] = $offset;

            $stmt = $this->db->getConnection()->prepare($sql);
            $stmt->execute($params);

            $tickets = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $ticket = new self($this->db);
                $ticket->loadFromArray($row);
                $tickets[] = $ticket;
            }

            return $tickets;

        } catch (PDOException $e) {
            error_log("SupportTicket::getTicketsForModeration() - Database error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Update ticket status
     */
    public function updateStatus(string $status, ?int $assignedTo = null): bool
    {
        if (!in_array($status, self::VALID_STATUSES)) {
            throw new InvalidArgumentException("Invalid status: $status");
        }

        try {
            $sql = "UPDATE support_tickets 
                    SET status = ?, assigned_to = ?, updated_at = CURRENT_TIMESTAMP 
                    WHERE id = ?";

            $stmt = $this->db->getConnection()->prepare($sql);
            $result = $stmt->execute([$status, $assignedTo, $this->id]);

            if ($result) {
                $this->status = $status;
                $this->assigned_to = $assignedTo;
                $this->updated_at = date('Y-m-d H:i:s');
            }

            return $result;

        } catch (PDOException $e) {
            error_log("SupportTicket::updateStatus() - Database error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Add message to ticket
     */
    public function addMessage(int $userId, string $message, bool $isInternal = false, ?array $attachments = null): bool
    {
        try {
            $sql = "INSERT INTO ticket_messages 
                    (ticket_id, user_id, message, is_internal, attachments) 
                    VALUES (?, ?, ?, ?, ?)";

            $attachmentsJson = $attachments ? json_encode($attachments) : null;

            $stmt = $this->db->getConnection()->prepare($sql);
            $result = $stmt->execute([
                $this->id,
                $userId,
                $message,
                $isInternal ? 1 : 0,
                $attachmentsJson
            ]);

            if ($result) {
                // Update ticket timestamp
                $this->updateTimestamp();
            }

            return $result;

        } catch (PDOException $e) {
            error_log("SupportTicket::addMessage() - Database error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all messages for this ticket
     */
    public function getMessages(bool $includeInternal = false): array
    {
        try {
            $sql = "SELECT tm.*, u.username, u.role
                    FROM ticket_messages tm
                    LEFT JOIN users u ON tm.user_id = u.id
                    WHERE tm.ticket_id = ?";

            $params = [$this->id];

            if (!$includeInternal) {
                $sql .= " AND tm.is_internal = 0";
            }

            $sql .= " ORDER BY tm.created_at ASC";

            $stmt = $this->db->getConnection()->prepare($sql);
            $stmt->execute($params);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("SupportTicket::getMessages() - Database error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get ticket statistics
     */
    public static function getStatistics(DatabaseInterface $db): array
    {
        try {
            $sql = "SELECT 
                        COUNT(*) as total_tickets,
                        COUNT(CASE WHEN status = 'open' THEN 1 END) as open_tickets,
                        COUNT(CASE WHEN status = 'in_progress' THEN 1 END) as in_progress_tickets,
                        COUNT(CASE WHEN status = 'waiting_client' THEN 1 END) as waiting_client_tickets,
                        COUNT(CASE WHEN status = 'resolved' THEN 1 END) as resolved_tickets,
                        COUNT(CASE WHEN status = 'closed' THEN 1 END) as closed_tickets,
                        COUNT(CASE WHEN priority = 'critical' THEN 1 END) as critical_tickets,
                        COUNT(CASE WHEN priority = 'high' THEN 1 END) as high_priority_tickets,
                        COUNT(CASE WHEN created_at >= CURDATE() THEN 1 END) as today_tickets
                    FROM support_tickets";

            $stmt = $db->getConnection()->prepare($sql);
            $stmt->execute();

            return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        } catch (PDOException $e) {
            error_log("SupportTicket::getStatistics() - Database error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Close ticket
     */
    public function close(): bool
    {
        return $this->updateStatus('closed');
    }

    /**
     * Reopen ticket
     */
    public function reopen(): bool
    {
        return $this->updateStatus('open');
    }

    /**
     * Assign ticket to user
     */
    public function assignTo(int $userId): bool
    {
        try {
            $sql = "UPDATE support_tickets 
                    SET assigned_to = ?, status = 'in_progress', updated_at = CURRENT_TIMESTAMP 
                    WHERE id = ?";

            $stmt = $this->db->getConnection()->prepare($sql);
            $result = $stmt->execute([$userId, $this->id]);

            if ($result) {
                $this->assigned_to = $userId;
                $this->status = 'in_progress';
                $this->updated_at = date('Y-m-d H:i:s');
            }

            return $result;

        } catch (PDOException $e) {
            error_log("SupportTicket::assignTo() - Database error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Validate ticket data
     */
    private function validateTicketData(array $data): void
    {
        if (empty($data['client_id'])) {
            throw new InvalidArgumentException("Client ID is required");
        }

        if (empty($data['subject']) || strlen($data['subject']) < 5) {
            throw new InvalidArgumentException("Subject must be at least 5 characters long");
        }

        if (empty($data['description']) || strlen($data['description']) < 10) {
            throw new InvalidArgumentException("Description must be at least 10 characters long");
        }

        if (isset($data['priority']) && !in_array($data['priority'], self::VALID_PRIORITIES)) {
            throw new InvalidArgumentException("Invalid priority level");
        }

        if (isset($data['status']) && !in_array($data['status'], self::VALID_STATUSES)) {
            throw new InvalidArgumentException("Invalid status");
        }
    }

    /**
     * Load data from array
     */
    private function loadFromArray(array $data): void
    {
        $this->id = isset($data['id']) ? (int)$data['id'] : null;
        $this->client_id = isset($data['client_id']) ? (int)$data['client_id'] : null;
        $this->subject = $data['subject'] ?? null;
        $this->description = $data['description'] ?? null;
        $this->priority = $data['priority'] ?? 'medium';
        $this->status = $data['status'] ?? 'open';
        $this->category = $data['category'] ?? null;
        $this->assigned_to = isset($data['assigned_to']) ? (int)$data['assigned_to'] : null;
        $this->created_at = $data['created_at'] ?? null;
        $this->updated_at = $data['updated_at'] ?? null;
    }

    /**
     * Update timestamp
     */
    private function updateTimestamp(): bool
    {
        try {
            $sql = "UPDATE support_tickets SET updated_at = CURRENT_TIMESTAMP WHERE id = ?";
            $stmt = $this->db->getConnection()->prepare($sql);
            return $stmt->execute([$this->id]);

        } catch (PDOException $e) {
            error_log("SupportTicket::updateTimestamp() - Database error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get priority badge class for UI
     */
    public function getPriorityBadgeClass(): string
    {
        return match($this->priority) {
            'critical' => 'bg-danger',
            'high' => 'bg-warning',
            'medium' => 'bg-primary',
            'low' => 'bg-secondary',
            default => 'bg-secondary'
        };
    }

    /**
     * Get status badge class for UI
     */
    public function getStatusBadgeClass(): string
    {
        return match($this->status) {
            'open' => 'bg-info',
            'in_progress' => 'bg-warning',
            'waiting_client' => 'bg-secondary',
            'resolved' => 'bg-success',
            'closed' => 'bg-dark',
            default => 'bg-secondary'
        };
    }

    /**
     * Check if ticket can be edited by user
     */
    public function canBeEditedBy(int $userId, string $userRole): bool
    {
        // Admin and employees can edit any ticket
        if (in_array($userRole, ['admin', 'employee'])) {
            return true;
        }

        // Clients can only edit their own open tickets
        if ($userRole === 'client' && $this->client_id === $userId && $this->status === 'open') {
            return true;
        }

        return false;
    }
}
