<?php

/**
 * User model
 *
 * @author Dmytro Hovenko
 */
declare(strict_types=1);

namespace App\Domain\Models;

use App\Domain\Interfaces\DatabaseInterface;
use PDO;
use PDOException;

class User {
    private DatabaseInterface $db_handler;
    private ?int $id = null;
    private ?string $username = null;
    private ?string $email = null;
    private ?string $password_hash = null;
    private ?string $role = null;
    private ?string $created_at = null;
    private ?string $location = null;
    private ?string $user_status = null;
    private ?string $bio = null;
    private ?string $website_url = null;
    private ?string $pending_email_address = null;

    public function __construct(DatabaseInterface $db_handler) {
        $this->db_handler = $db_handler;
    }

    public static function findById(DatabaseInterface $db_handler, int $id): ?array {
        $conn = $db_handler->getConnection();
        $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function findByIdAsObject(int $id): ?self {
        $conn = $this->db_handler->getConnection();
        $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($userData) {
            $user = new self($this->db_handler);
            $user->fillFromArray($userData);
            return $user;
        }

        return null;
    }

    public static function findByUsernameOrEmail(DatabaseInterface $db_handler, string $username = '', string $email = ''): ?array {
        $conn = $db_handler->getConnection();

        if (!empty($username) && !empty($email)) {
            $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
        } elseif (!empty($username)) {
            $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->execute([$username]);
        } elseif (!empty($email)) {
            $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
        } else {
            return null;
        }

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    private function fillFromArray(array $data): void {
        $this->id = $data['id'] ?? null;
        $this->username = $data['username'] ?? null;
        $this->email = $data['email'] ?? null;
        $this->password_hash = $data['password_hash'] ?? null;
        $this->role = $data['role'] ?? null;
        $this->created_at = $data['created_at'] ?? null;
        $this->location = $data['location'] ?? null;
        $this->user_status = $data['user_status'] ?? null;
        $this->bio = $data['bio'] ?? null;
        $this->website_url = $data['website_url'] ?? null;
        $this->pending_email_address = $data['pending_email_address'] ?? null;
    }

    // Getters
    public function getId(): ?int { return $this->id; }
    public function getUsername(): ?string { return $this->username; }
    public function getEmail(): ?string { return $this->email; }
    public function getPasswordHash(): ?string { return $this->password_hash; }
    public function getRole(): ?string { return $this->role; }
    public function getCreatedAt(): ?string { return $this->created_at; }
    public function getLocation(): ?string { return $this->location; }
    public function getUserStatus(): ?string { return $this->user_status; }
    public function getBio(): ?string { return $this->bio; }
    public function getWebsiteUrl(): ?string { return $this->website_url; }
    public function getPendingEmailAddress(): ?string { return $this->pending_email_address; }

    // Setters
    public function setUsername(string $username): void {
        $this->username = $username;
    }

    public function setEmail(string $email): void {
        $this->email = $email;
    }

    public function setPassword(string $password): void {
        $this->password_hash = password_hash($password, PASSWORD_DEFAULT);
    }

    public function setActive(bool $active): void {
        $this->user_status = $active ? 'active' : 'pending';
    }

    public function isActive(): bool {
        return $this->user_status === 'active';
    }

    public function updateDetails(array $details): bool {
        if (empty($details)) {
            return true;
        }

        $conn = $this->db_handler->getConnection();

        $setParts = [];
        $values = [];

        foreach ($details as $field => $value) {
            $setParts[] = "$field = ?";
            $values[] = $value;
        }

        $setParts[] = "updated_at = CURRENT_TIMESTAMP";
        $values[] = $this->id;

        $sql = "UPDATE users SET " . implode(', ', $setParts) . " WHERE id = ?";

        try {
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                error_log("User::updateDetails - Failed to prepare statement: " . json_encode($conn->errorInfo()));
                return false;
            }

            $result = $stmt->execute($values);

            if (!$result) {
                error_log("User::updateDetails - Execute failed: " . json_encode($stmt->errorInfo()));
                return false;
            }

            // Only log if no rows were affected (potential issue)
            $rowCount = $stmt->rowCount();
            if ($rowCount === 0) {
                error_log("User::updateDetails - Warning: No rows affected for user ID $this->id");
            }

            return true;
        } catch (PDOException $e) {
            error_log("User::updateDetails - PDO Exception: " . $e->getMessage());
            return false;
        }
    }

    public function save(): bool {
        $conn = $this->db_handler->getConnection();

        if ($this->id) {
            // Update existing user
            $stmt = $conn->prepare("
                UPDATE users SET 
                    username = ?, email = ?, password_hash = ?, role = ?, 
                    location = ?, user_status = ?, bio = ?, website_url = ?,
                    pending_email_address = ?, updated_at = NOW()
                WHERE id = ?
            ");

            return $stmt->execute([
                $this->username, $this->email, $this->password_hash, $this->role,
                $this->location, $this->user_status, $this->bio, $this->website_url,
                $this->pending_email_address, $this->id
            ]);
        } else {
            // Create a new user
            $stmt = $conn->prepare("
                INSERT INTO users (
                    username, email, password_hash, role, location, user_status, 
                    bio, website_url, pending_email_address, is_active, status, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");

            $result = $stmt->execute([
                $this->username,
                $this->email,
                $this->password_hash,
                $this->role ?? 'user',
                $this->location,
                $this->user_status ?? 'pending',
                $this->bio,
                $this->website_url,
                $this->pending_email_address,
                ($this->user_status === 'active') ? 1 : 0,
                $this->user_status ?? 'pending'
            ]);

            if ($result) {
                $this->id = (int)$conn->lastInsertId();
            }

            return $result;
        }
    }

    public function load(int $id): bool {
        $userData = self::findById($this->db_handler, $id);
        if ($userData) {
            $this->fillFromArray($userData);
            return true;
        }
        return false;
    }

    public function toArray(): array {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'email' => $this->email,
            'role' => $this->role,
            'created_at' => $this->created_at,
            'location' => $this->location,
            'user_status' => $this->user_status,
            'bio' => $this->bio,
            'website_url' => $this->website_url,
            'pending_email_address' => $this->pending_email_address
        ];
    }

    /**
     * Check if a username or email exists for another user (excluding specific ID)
     */
    public static function existsByUsernameOrEmailExcludingId(DatabaseInterface $db_handler, string $username, string $email, int $excludeId): bool {
        $conn = $db_handler->getConnection();

        try {
            $stmt = $conn->prepare("SELECT id FROM users WHERE (username = :username OR email = :email) AND id != :id LIMIT 1");
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':id', $excludeId, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetch() !== false;
        } catch (PDOException $e) {
            error_log("User::existsByUsernameOrEmailExcludingId - PDOException: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update user by ID
     */
    public static function updateById(DatabaseInterface $db_handler, int $id, string $username, string $email, string $role): bool {
        $conn = $db_handler->getConnection();

        try {
            $stmt = $conn->prepare("UPDATE users SET username = :username, email = :email, role = :role, updated_at = NOW() WHERE id = :id");
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':role', $role);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);

            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("User::updateById - PDOException: " . $e->getMessage());
            return false;
        }
    }
}
