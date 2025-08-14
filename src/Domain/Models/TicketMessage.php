<?php

/**
 * TicketMessage model for managing ticket messages
 * Following the project's architecture patterns
 *
 * @author Dmytro Hovenko
 */
declare(strict_types=1);

namespace App\Domain\Models;

use App\Domain\Interfaces\DatabaseInterface;
use PDO;
use PDOException;

class TicketMessage {
    private DatabaseInterface $db_handler;
    private ?int $id = null;
    private ?int $ticket_id = null;
    private ?int $user_id = null;
    private ?string $message = null;
    private ?bool $is_internal = false;
    private ?string $created_at = null;

    public function __construct(DatabaseInterface $db_handler) {
        $this->db_handler = $db_handler;
    }

    // Getters
    public function getId(): ?int { return $this->id; }
    public function getTicketId(): ?int { return $this->ticket_id; }
    public function getUserId(): ?int { return $this->user_id; }
    public function getMessage(): ?string { return $this->message; }
    public function getIsInternal(): ?bool { return $this->is_internal; }
    public function getCreatedAt(): ?string { return $this->created_at; }

    // Setters
    public function setTicketId(int $ticket_id): self { $this->ticket_id = $ticket_id; return $this; }
    public function setUserId(int $user_id): self { $this->user_id = $user_id; return $this; }
    public function setMessage(string $message): self { $this->message = $message; return $this; }
    public function setIsInternal(bool $is_internal): self { $this->is_internal = $is_internal; return $this; }

    /**
     * Save the message
     */
    public function save(): bool {
        try {
            if ($this->id === null) {
                $sql = "INSERT INTO ticket_messages (ticket_id, user_id, message, is_internal) 
                        VALUES (:ticket_id, :user_id, :message, :is_internal)";

                $stmt = $this->db_handler->prepare($sql);
                $result = $stmt->execute([
                    ':ticket_id' => $this->ticket_id,
                    ':user_id' => $this->user_id,
                    ':message' => $this->message,
                    ':is_internal' => $this->is_internal ? 1 : 0
                ]);

                if ($result) {
                    $this->id = (int)$this->db_handler->lastInsertId();
                }

                return $result;
            }
            return false; // Messages are not updatable
        } catch (PDOException $e) {
            error_log("Error saving ticket message: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Find messages by ticket ID
     */
    public static function findByTicketId(DatabaseInterface $db_handler, int $ticketId, bool $includeInternal = false): array {
        try {
            $sql = "SELECT tm.*, u.username, u.email,
                           CASE 
                               WHEN ur.role_id IN (1, 2) THEN 'staff'
                               ELSE 'client'
                           END as sender_type
                    FROM ticket_messages tm
                    LEFT JOIN users u ON tm.user_id = u.id
                    LEFT JOIN user_roles ur ON u.id = ur.user_id
                    WHERE tm.ticket_id = :ticket_id";

            if (!$includeInternal) {
                $sql .= " AND tm.is_internal = 0";
            }

            $sql .= " ORDER BY tm.created_at ASC";

            $stmt = $db_handler->prepare($sql);
            $stmt->execute([':ticket_id' => $ticketId]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $messages = [];
            foreach ($rows as $row) {
                $message = new self($db_handler);
                $message->loadFromArray($row);
                $messages[] = $message;
            }

            return $messages;
        } catch (PDOException $e) {
            error_log("Error finding ticket messages: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Load data from array
     */
    private function loadFromArray(array $data): void {
        $this->id = $data['id'] ?? null;
        $this->ticket_id = $data['ticket_id'] ?? null;
        $this->user_id = $data['user_id'] ?? null;
        $this->message = $data['message'] ?? null;
        $this->is_internal = (bool)($data['is_internal'] ?? false);
        $this->created_at = $data['created_at'] ?? null;
    }

    /**
     * Convert to array (for API responses)
     */
    public function toArray(): array {
        return [
            'id' => $this->id,
            'ticket_id' => $this->ticket_id,
            'user_id' => $this->user_id,
            'message' => $this->message,
            'is_internal' => $this->is_internal,
            'created_at' => $this->created_at
        ];
    }
}
