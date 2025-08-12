<?php

/**
 * ClientProject model for managing client portfolio projects
 *
 * @author Dmytro Hovenko
 */
declare(strict_types=1);

namespace App\Domain\Models;

use App\Domain\Interfaces\DatabaseInterface;
use PDO;
use PDOException;

class ClientProject {
    private DatabaseInterface $db_handler;
    private ?int $id = null;
    private ?int $client_profile_id = null;
    private ?string $title = null;
    private ?string $description = null;
    private ?string $technologies = null;
    private ?array $images = null;
    private ?string $project_url = null;
    private ?string $github_url = null;
    private ?string $status = 'draft';
    private ?string $visibility = 'private';
    private ?int $moderator_id = null;
    private ?string $moderated_at = null;
    private ?string $moderation_notes = null;
    private ?string $created_at = null;
    private ?string $updated_at = null;

    public function __construct(DatabaseInterface $db_handler) {
        $this->db_handler = $db_handler;
    }

    // Getters
    public function getId(): ?int { return $this->id; }
    public function getClientProfileId(): ?int { return $this->client_profile_id; }
    public function getTitle(): ?string { return $this->title; }
    public function getDescription(): ?string { return $this->description; }
    public function getTechnologies(): ?string { return $this->technologies; }
    public function getImages(): ?array { return $this->images; }
    public function getProjectUrl(): ?string { return $this->project_url; }
    public function getGithubUrl(): ?string { return $this->github_url; }
    public function getStatus(): ?string { return $this->status; }
    public function getVisibility(): ?string { return $this->visibility; }
    public function getModeratorId(): ?int { return $this->moderator_id; }
    public function getModeratedAt(): ?string { return $this->moderated_at; }
    public function getModerationNotes(): ?string { return $this->moderation_notes; }
    public function getCreatedAt(): ?string { return $this->created_at; }
    public function getUpdatedAt(): ?string { return $this->updated_at; }

    // Setters
    public function setClientProfileId(int $client_profile_id): self {
        $this->client_profile_id = $client_profile_id;
        return $this;
    }

    public function setTitle(string $title): self {
        $this->title = $title;
        return $this;
    }

    public function setDescription(?string $description): self {
        $this->description = $description;
        return $this;
    }

    public function setTechnologies(?string $technologies): self {
        $this->technologies = $technologies;
        return $this;
    }

    public function setImages(?array $images): self {
        $this->images = $images;
        return $this;
    }

    public function setProjectUrl(?string $project_url): self {
        $this->project_url = $project_url;
        return $this;
    }

    public function setGithubUrl(?string $github_url): self {
        $this->github_url = $github_url;
        return $this;
    }

    public function setStatus(string $status): self {
        $validStatuses = ['draft', 'pending', 'published', 'rejected'];
        $this->status = in_array($status, $validStatuses) ? $status : 'draft';
        return $this;
    }

    public function setVisibility(string $visibility): self {
        $this->visibility = in_array($visibility, ['public', 'private']) ? $visibility : 'private';
        return $this;
    }

    public function setModeratorId(?int $moderator_id): self {
        $this->moderator_id = $moderator_id;
        return $this;
    }

    public function setModerationNotes(?string $moderation_notes): self {
        $this->moderation_notes = $moderation_notes;
        return $this;
    }

    // Static methods
    public static function findById(DatabaseInterface $db_handler, int $id): ?array {
        $conn = $db_handler->getConnection();
        $stmt = $conn->prepare("SELECT * FROM client_portfolio WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function findByClientProfileId(DatabaseInterface $db_handler, int $client_profile_id): array {
        $conn = $db_handler->getConnection();
        $stmt = $conn->prepare("
            SELECT * FROM client_portfolio 
            WHERE client_profile_id = ? 
            ORDER BY created_at DESC
        ");
        $stmt->execute([$client_profile_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getPublicProjects(DatabaseInterface $db_handler, int $limit = 20, int $offset = 0): array {
        $conn = $db_handler->getConnection();
        $stmt = $conn->prepare("
            SELECT cp.*, cl.company_name, u.username
            FROM client_portfolio cp
            INNER JOIN client_profiles cl ON cp.client_profile_id = cl.id
            INNER JOIN users u ON cl.user_id = u.id
            WHERE cp.status = 'published' AND cp.visibility = 'public'
            ORDER BY cp.created_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$limit, $offset]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getProjectsForModeration(DatabaseInterface $db_handler): array {
        $conn = $db_handler->getConnection();
        $stmt = $conn->prepare("
            SELECT cp.*, cl.company_name, u.username, u.email
            FROM client_portfolio cp
            INNER JOIN client_profiles cl ON cp.client_profile_id = cl.id
            INNER JOIN users u ON cl.user_id = u.id
            WHERE cp.status = 'pending'
            ORDER BY cp.created_at ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function loadById(int $id): bool {
        $data = self::findById($this->db_handler, $id);
        if ($data) {
            $this->fillFromArray($data);
            return true;
        }
        return false;
    }

    public function save(): bool {
        try {
            $conn = $this->db_handler->getConnection();

            $images_json = $this->images ? json_encode($this->images) : null;

            if ($this->id === null) {
                // Create new project
                $stmt = $conn->prepare("
                    INSERT INTO client_portfolio (
                        client_profile_id, title, description, technologies, images,
                        project_url, github_url, status, visibility
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");

                $result = $stmt->execute([
                    $this->client_profile_id, $this->title, $this->description,
                    $this->technologies, $images_json, $this->project_url,
                    $this->github_url, $this->status, $this->visibility
                ]);

                if ($result) {
                    $this->id = (int)$conn->lastInsertId();
                }

                return $result;
            } else {
                // Update existing project
                $stmt = $conn->prepare("
                    UPDATE client_portfolio SET 
                        title = ?, description = ?, technologies = ?, images = ?,
                        project_url = ?, github_url = ?, status = ?, visibility = ?,
                        updated_at = CURRENT_TIMESTAMP
                    WHERE id = ?
                ");

                return $stmt->execute([
                    $this->title, $this->description, $this->technologies, $images_json,
                    $this->project_url, $this->github_url, $this->status, $this->visibility,
                    $this->id
                ]);
            }
        } catch (PDOException $e) {
            error_log("ClientProject::save() - PDO Exception: " . $e->getMessage());
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

            // Delete project views
            $stmt = $conn->prepare("DELETE FROM project_views WHERE project_id = ?");
            $stmt->execute([$this->id]);

            // Delete category assignments
            $stmt = $conn->prepare("DELETE FROM project_category_assignments WHERE project_id = ?");
            $stmt->execute([$this->id]);

            // Delete project
            $stmt = $conn->prepare("DELETE FROM client_portfolio WHERE id = ?");
            $result = $stmt->execute([$this->id]);

            $conn->commit();
            return $result;
        } catch (PDOException $e) {
            $conn->rollBack();
            error_log("ClientProject::delete() - PDO Exception: " . $e->getMessage());
            return false;
        }
    }

    public function moderate(int $moderator_id, string $status, ?string $notes = null): bool {
        if (!in_array($status, ['published', 'rejected'])) {
            return false;
        }

        try {
            $conn = $this->db_handler->getConnection();
            $stmt = $conn->prepare("
                UPDATE client_portfolio 
                SET status = ?, moderator_id = ?, moderated_at = CURRENT_TIMESTAMP, 
                    moderation_notes = ?, updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ");

            $result = $stmt->execute([$status, $moderator_id, $notes, $this->id]);

            if ($result) {
                $this->status = $status;
                $this->moderator_id = $moderator_id;
                $this->moderation_notes = $notes;
            }

            return $result;
        } catch (PDOException $e) {
            error_log("ClientProject::moderate() - PDO Exception: " . $e->getMessage());
            return false;
        }
    }

    public function submitForModeration(): bool {
        if ($this->status !== 'draft') {
            return false;
        }

        try {
            $conn = $this->db_handler->getConnection();
            $stmt = $conn->prepare("
                UPDATE client_portfolio 
                SET status = 'pending', updated_at = CURRENT_TIMESTAMP 
                WHERE id = ?
            ");

            $result = $stmt->execute([$this->id]);

            if ($result) {
                $this->status = 'pending';
            }

            return $result;
        } catch (PDOException $e) {
            return false;
        }
    }

    public function addView(?int $user_id = null, ?string $ip_address = null): bool {
        try {
            $conn = $this->db_handler->getConnection();

            // Check if view already exists for today
            $stmt = $conn->prepare("
                SELECT id FROM project_views 
                WHERE project_id = ? AND user_id = ? AND DATE(last_viewed) = CURDATE()
            ");
            $stmt->execute([$this->id, $user_id]);

            if ($stmt->fetch()) {
                // Update existing view
                $stmt = $conn->prepare("
                    UPDATE project_views 
                    SET view_count = view_count + 1, last_viewed = CURRENT_TIMESTAMP 
                    WHERE project_id = ? AND user_id = ? AND DATE(last_viewed) = CURDATE()
                ");
                return $stmt->execute([$this->id, $user_id]);
            } else {
                // Create new view record
                $stmt = $conn->prepare("
                    INSERT INTO project_views (project_id, user_id, ip_address, view_count, last_viewed) 
                    VALUES (?, ?, ?, 1, CURRENT_TIMESTAMP)
                ");
                return $stmt->execute([$this->id, $user_id, $ip_address]);
            }
        } catch (PDOException $e) {
            return false;
        }
    }

    public function getViewCount(): int {
        $conn = $this->db_handler->getConnection();
        $stmt = $conn->prepare("
            SELECT COALESCE(SUM(view_count), 0) as total_views 
            FROM project_views 
            WHERE project_id = ?
        ");
        $stmt->execute([$this->id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($result['total_views'] ?? 0);
    }

    public function getCategories(): array {
        $conn = $this->db_handler->getConnection();
        $stmt = $conn->prepare("
            SELECT pc.* FROM project_categories pc
            INNER JOIN project_category_assignments pca ON pc.id = pca.category_id
            WHERE pca.project_id = ?
        ");
        $stmt->execute([$this->id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function assignCategory(int $category_id): bool {
        try {
            $conn = $this->db_handler->getConnection();
            $stmt = $conn->prepare("
                INSERT IGNORE INTO project_category_assignments (project_id, category_id) 
                VALUES (?, ?)
            ");
            return $stmt->execute([$this->id, $category_id]);
        } catch (PDOException $e) {
            return false;
        }
    }

    public function removeCategory(int $category_id): bool {
        try {
            $conn = $this->db_handler->getConnection();
            $stmt = $conn->prepare("
                DELETE FROM project_category_assignments 
                WHERE project_id = ? AND category_id = ?
            ");
            return $stmt->execute([$this->id, $category_id]);
        } catch (PDOException $e) {
            return false;
        }
    }

    private function fillFromArray(array $data): void {
        $this->id = isset($data['id']) ? (int)$data['id'] : null;
        $this->client_profile_id = isset($data['client_profile_id']) ? (int)$data['client_profile_id'] : null;
        $this->title = $data['title'] ?? null;
        $this->description = $data['description'] ?? null;
        $this->technologies = $data['technologies'] ?? null;
        $this->images = isset($data['images']) ? json_decode($data['images'], true) : null;
        $this->project_url = $data['project_url'] ?? null;
        $this->github_url = $data['github_url'] ?? null;
        $this->status = $data['status'] ?? 'draft';
        $this->visibility = $data['visibility'] ?? 'private';
        $this->moderator_id = isset($data['moderator_id']) ? (int)$data['moderator_id'] : null;
        $this->moderated_at = $data['moderated_at'] ?? null;
        $this->moderation_notes = $data['moderation_notes'] ?? null;
        $this->created_at = $data['created_at'] ?? null;
        $this->updated_at = $data['updated_at'] ?? null;
    }

    public function toArray(): array {
        return [
            'id' => $this->id,
            'client_profile_id' => $this->client_profile_id,
            'title' => $this->title,
            'description' => $this->description,
            'technologies' => $this->technologies,
            'images' => $this->images,
            'project_url' => $this->project_url,
            'github_url' => $this->github_url,
            'status' => $this->status,
            'visibility' => $this->visibility,
            'moderator_id' => $this->moderator_id,
            'moderated_at' => $this->moderated_at,
            'moderation_notes' => $this->moderation_notes,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }
}
