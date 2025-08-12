<?php

/**
 * Role model for advanced role-based access control
 *
 * @author Dmytro Hovenko
 */
declare(strict_types=1);

namespace App\Domain\Models;

use App\Domain\Interfaces\DatabaseInterface;
use PDO;
use PDOException;

class Role {
    private DatabaseInterface $db_handler;
    private ?int $id = null;
    private ?string $name = null;
    private ?string $description = null;
    private ?string $created_at = null;
    private ?string $updated_at = null;

    public function __construct(DatabaseInterface $db_handler) {
        $this->db_handler = $db_handler;
    }

    public function getId(): ?int {
        return $this->id;
    }

    public function getName(): ?string {
        return $this->name;
    }

    public function getDescription(): ?string {
        return $this->description;
    }

    public function setName(string $name): self {
        $this->name = $name;
        return $this;
    }

    public function setDescription(string $description): self {
        $this->description = $description;
        return $this;
    }

    public static function findById(DatabaseInterface $db_handler, int $id): ?array {
        $conn = $db_handler->getConnection();
        $stmt = $conn->prepare("SELECT * FROM roles WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function findByName(DatabaseInterface $db_handler, string $name): ?array {
        $conn = $db_handler->getConnection();
        $stmt = $conn->prepare("SELECT * FROM roles WHERE name = ?");
        $stmt->execute([$name]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function getAllRoles(DatabaseInterface $db_handler): array {
        $conn = $db_handler->getConnection();
        $stmt = $conn->prepare("SELECT * FROM roles ORDER BY name");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPermissions(): array {
        $conn = $this->db_handler->getConnection();
        $stmt = $conn->prepare("
            SELECT p.* FROM permissions p 
            INNER JOIN role_permissions rp ON p.id = rp.permission_id 
            WHERE rp.role_id = ?
        ");
        $stmt->execute([$this->id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function hasPermission(string $resource, string $action): bool {
        $conn = $this->db_handler->getConnection();
        $stmt = $conn->prepare("
            SELECT COUNT(*) FROM permissions p 
            INNER JOIN role_permissions rp ON p.id = rp.permission_id 
            WHERE rp.role_id = ? AND p.resource = ? AND p.action = ?
        ");
        $stmt->execute([$this->id, $resource, $action]);
        return $stmt->fetchColumn() > 0;
    }

    public function assignPermission(int $permission_id): bool {
        try {
            $conn = $this->db_handler->getConnection();
            $stmt = $conn->prepare("INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (?, ?)");
            return $stmt->execute([$this->id, $permission_id]);
        } catch (PDOException $e) {
            return false;
        }
    }

    public function removePermission(int $permission_id): bool {
        try {
            $conn = $this->db_handler->getConnection();
            $stmt = $conn->prepare("DELETE FROM role_permissions WHERE role_id = ? AND permission_id = ?");
            return $stmt->execute([$this->id, $permission_id]);
        } catch (PDOException $e) {
            return false;
        }
    }

    public function save(): bool {
        try {
            $conn = $this->db_handler->getConnection();
            
            if ($this->id === null) {
                $stmt = $conn->prepare("INSERT INTO roles (name, description) VALUES (?, ?)");
                $result = $stmt->execute([$this->name, $this->description]);
                if ($result) {
                    $this->id = (int)$conn->lastInsertId();
                }
                return $result;
            } else {
                $stmt = $conn->prepare("UPDATE roles SET name = ?, description = ? WHERE id = ?");
                return $stmt->execute([$this->name, $this->description, $this->id]);
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

            // Remove role permissions
            $stmt = $conn->prepare("DELETE FROM role_permissions WHERE role_id = ?");
            $stmt->execute([$this->id]);

            // Remove user roles
            $stmt = $conn->prepare("DELETE FROM user_roles WHERE role_id = ?");
            $stmt->execute([$this->id]);

            // Delete role
            $stmt = $conn->prepare("DELETE FROM roles WHERE id = ?");
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
        $this->description = $data['description'] ?? null;
        $this->created_at = $data['created_at'] ?? null;
        $this->updated_at = $data['updated_at'] ?? null;
    }
}
