<?php

declare(strict_types=1);

namespace App\Domain\Models;

use PDO;
use Exception;

class Ticket
{
    public static function findByUserId($database, int $userId, array $filters = []): array
    {
        try {
            $sql = "SELECT * FROM tickets WHERE user_id = ?";
            $params = [$userId];

            // Add filters
            if (!empty($filters['status'])) {
                $sql .= " AND status = ?";
                $params[] = $filters['status'];
            }
            if (!empty($filters['category'])) {
                $sql .= " AND category = ?";
                $params[] = $filters['category'];
            }
            if (!empty($filters['priority'])) {
                $sql .= " AND priority = ?";
                $params[] = $filters['priority'];
            }

            $sql .= " ORDER BY created_at DESC";

            // Add pagination
            if (isset($filters['limit']) && isset($filters['offset'])) {
                $sql .= " LIMIT ? OFFSET ?";
                $params[] = $filters['limit'];
                $params[] = $filters['offset'];
            }

            $stmt = $database->getConnection()->prepare($sql);
            $stmt->execute($params);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error fetching tickets: " . $e->getMessage());
            return [];
        }
    }

    public static function getUserStats($database, int $userId): array
    {
        try {
            $sql = "SELECT 
                        COUNT(*) as total,
                        COUNT(CASE WHEN status = 'open' THEN 1 END) as open,
                        COUNT(CASE WHEN status = 'in_progress' THEN 1 END) as in_progress,
                        COUNT(CASE WHEN status = 'waiting_client' THEN 1 END) as waiting_client,
                        COUNT(CASE WHEN status = 'resolved' THEN 1 END) as resolved,
                        COUNT(CASE WHEN status = 'closed' THEN 1 END) as closed,
                        COUNT(CASE WHEN priority = 'urgent' THEN 1 END) as urgent,
                        COUNT(CASE WHEN created_at >= CURDATE() THEN 1 END) as today
                    FROM tickets WHERE user_id = ?";

            $stmt = $database->getConnection()->prepare($sql);
            $stmt->execute([$userId]);

            return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        } catch (Exception $e) {
            error_log("Error getting ticket stats: " . $e->getMessage());
            return [];
        }
    }

    public static function getStatuses(): array
    {
        return [
            'open' => 'Open',
            'in_progress' => 'In Progress',
            'waiting_client' => 'Waiting for Client',
            'resolved' => 'Resolved',
            'closed' => 'Closed'
        ];
    }

    public static function getPriorities(): array
    {
        return [
            'low' => 'Low',
            'medium' => 'Medium',
            'high' => 'High',
            'urgent' => 'Urgent'
        ];
    }

    public static function getCategories(): array
    {
        return [
            'technical' => 'Technical Support',
            'billing' => 'Billing',
            'general' => 'General Inquiry',
            'feature_request' => 'Feature Request',
            'bug_report' => 'Bug Report'
        ];
    }

    // Instance properties for the model
    private $db_handler;
    private $id;
    private $user_id;
    private $subject;
    private $description;
    private $priority = 'medium';
    private $category = 'general';
    private $status = 'open';
    private $assigned_to;
    private $created_at;

    public function __construct($db_handler)
    {
        $this->db_handler = $db_handler;
    }

    public function setUserId(int $userId): self
    {
        $this->user_id = $userId;
        return $this;
    }

    public function setSubject(string $subject): self
    {
        $this->subject = $subject;
        return $this;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function setPriority(string $priority): self
    {
        $this->priority = $priority;
        return $this;
    }

    public function setCategory(string $category): self
    {
        $this->category = $category;
        return $this;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function setAssignedTo(?int $assignedTo): self
    {
        $this->assigned_to = $assignedTo;
        return $this;
    }

    public function getUserId(): int
    {
        return $this->user_id;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function save(): bool
    {
        try {
            if ($this->id) {
                // Update existing ticket
                $sql = "UPDATE tickets SET 
                        subject = ?, description = ?, priority = ?, category = ?, 
                        status = ?, assigned_to = ?, updated_at = NOW() 
                        WHERE id = ?";
                $params = [
                    $this->subject, $this->description, $this->priority,
                    $this->category, $this->status, $this->assigned_to, $this->id
                ];
            } else {
                // Create new ticket
                $sql = "INSERT INTO tickets 
                        (user_id, subject, description, priority, category, status, created_at) 
                        VALUES (?, ?, ?, ?, ?, ?, NOW())";
                $params = [
                    $this->user_id, $this->subject, $this->description,
                    $this->priority, $this->category, $this->status
                ];
            }

            $stmt = $this->db_handler->getConnection()->prepare($sql);
            $result = $stmt->execute($params);

            if ($result && !$this->id) {
                $this->id = (int)$this->db_handler->getConnection()->lastInsertId();
            }

            return $result;
        } catch (Exception $e) {
            error_log("Error saving ticket: " . $e->getMessage());
            return false;
        }
    }

    public static function findById($db_handler, int $id): ?array
    {
        try {
            $sql = "SELECT * FROM tickets WHERE id = ?";
            $stmt = $db_handler->getConnection()->prepare($sql);
            $stmt->execute([$id]);

            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (Exception $e) {
            error_log("Error finding ticket by ID: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Load ticket data by ID into this instance
     */
    public function loadById(int $id): bool
    {
        try {
            $data = self::findById($this->db_handler, $id);
            if ($data) {
                $this->id = (int)$data['id'];
                $this->user_id = (int)$data['user_id'];
                $this->subject = $data['subject'];
                $this->description = $data['description'];
                $this->priority = $data['priority'];
                $this->category = $data['category'];
                $this->status = $data['status'];
                $this->assigned_to = $data['assigned_to'] ? (int)$data['assigned_to'] : null;
                $this->created_at = $data['created_at'];
                return true;
            }
            return false;
        } catch (Exception $e) {
            error_log("Error loading ticket: " . $e->getMessage());
            return false;
        }
    }
}
