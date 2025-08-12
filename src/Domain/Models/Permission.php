<?php

/**
 * Permission model for granular access control
 *
 * @author Dmytro Hovenko
 */

declare(strict_types=1);

namespace App\Domain\Models;

use App\Domain\Interfaces\DatabaseInterface;
use PDO;
use PDOException;

class Permission {
    private DatabaseInterface $db_handler;
    private ?int $id = null;
    private ?string $name = null;
    private ?string $resource = null;
    private ?string $action = null;
    private ?string $description = null;
    private ?string $created_at = null;

    public function __construct(DatabaseInterface $db_handler) {
        $this->db_handler = $db_handler;
    }

    public function getId(): ?int {
        return $this->id;
    }

    public function getName(): ?string {
        return $this->name;
    }

    public function getResource(): ?string {
        return $this->resource;
    }

    public function getAction(): ?string {
        return $this->action;
    }

    public function getDescription(): ?string {
        return $this->description;
    }

    public function setName(string $name): self {
        $this->name = $name;
        return $this;
    }

    public function setResource(string $resource): self {
        $this->resource = $resource;
        return $this;
    }

    public function setAction(string $action): self {
        $this->action = $action;
        return $this;
    }

    public function setDescription(string $description): self {
        $this->description = $description;
        return $this;
    }

    public static function findById(DatabaseInterface $db_handler, int $id): ?array {
        $conn = $db_handler->getConnection();
        $stmt = $conn->prepare("SELECT * FROM permissions WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function findByResourceAndAction(DatabaseInterface $db_handler, string $resource, string $action): ?array {
        $conn = $db_handler->getConnection();
        $stmt = $conn->prepare("SELECT * FROM permissions WHERE resource = ? AND action = ?");
        $stmt->execute([$resource, $action]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function getAllPermissions(DatabaseInterface $db_handler): array {
        $conn = $db_handler->getConnection();
        $stmt = $conn->prepare("SELECT * FROM permissions ORDER BY resource, action");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getPermissionsByResource(DatabaseInterface $db_handler, string $resource): array {
        $conn = $db_handler->getConnection();
        $stmt = $conn->prepare("SELECT * FROM permissions WHERE resource = ? ORDER BY action");
        $stmt->execute([$resource]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function save(): bool {
        try {
            $conn = $this->db_handler->getConnection();
            
            if ($this->id === null) {
                $stmt = $conn->prepare("INSERT INTO permissions (name, resource, action, description) VALUES (?, ?, ?, ?)");
                $result = $stmt->execute([$this->name, $this->resource, $this->action, $this->description]);
                if ($result) {
                    $this->id = (int)$conn->lastInsertId();
                }
                return $result;
            } else {
                $stmt = $conn->prepare("UPDATE permissions SET name = ?, resource = ?, action = ?, description = ? WHERE id = ?");
                return $stmt->execute([$this->name, $this->resource, $this->action, $this->description, $this->id]);
            }
        } catch (PDOException $e) {
            return false;
        }
    }

    public function delete(): bool {
        if ($this->id === null) {
            return false;
        }

        try {
            $conn = $this->db_handler->getConnection();
            $conn->beginTransaction();

            // Remove from role permissions
            $stmt = $conn->prepare("DELETE FROM role_permissions WHERE permission_id = ?");
            $stmt->execute([$this->id]);

            // Delete permission
            $stmt = $conn->prepare("DELETE FROM permissions WHERE id = ?");
            $result = $stmt->execute([$this->id]);

            $conn->commit();
            return $result;
        } catch (PDOException $e) {
            $conn->rollBack();
            return false;
        }
    }

    private function fillFromArray(array $data): void {
        $this->id = isset($data['id']) ? (int)$data['id'] : null;
        $this->name = $data['name'] ?? null;
        $this->resource = $data['resource'] ?? null;
        $this->action = $data['action'] ?? null;
        $this->description = $data['description'] ?? null;
        $this->created_at = $data['created_at'] ?? null;
    }
}
