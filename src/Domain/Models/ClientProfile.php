<?php

/**
 * ClientProfile model for extended client profile management
 *
 * @author GitHub Copilot
 */
declare(strict_types=1);

namespace App\Domain\Models;

use App\Domain\Interfaces\DatabaseInterface;
use PDO;
use PDOException;

class ClientProfile {
    private DatabaseInterface $db_handler;
    private ?int $id = null;
    private ?int $user_id = null;
    private ?string $company_name = null;
    private ?string $position = null;
    private ?string $bio = null;
    private ?array $skills = null;
    private ?string $portfolio_visibility = 'private';
    private ?bool $allow_contact = false;
    private ?array $social_links = null;
    private ?string $website = null;
    private ?string $location = null;
    private ?string $created_at = null;
    private ?string $updated_at = null;

    public function __construct(DatabaseInterface $db_handler) {
        $this->db_handler = $db_handler;
    }

    // Getters
    public function getId(): ?int { return $this->id; }
    public function getUserId(): ?int { return $this->user_id; }
    public function getCompanyName(): ?string { return $this->company_name; }
    public function getPosition(): ?string { return $this->position; }
    public function getBio(): ?string { return $this->bio; }
    public function getSkills(): ?array { return $this->skills; }
    public function getPortfolioVisibility(): ?string { return $this->portfolio_visibility; }
    public function getAllowContact(): ?bool { return $this->allow_contact; }
    public function getSocialLinks(): ?array { return $this->social_links; }
    public function getWebsite(): ?string { return $this->website; }
    public function getLocation(): ?string { return $this->location; }
    public function getCreatedAt(): ?string { return $this->created_at; }
    public function getUpdatedAt(): ?string { return $this->updated_at; }

    // Setters
    public function setUserId(int $user_id): self {
        $this->user_id = $user_id;
        return $this;
    }

    public function setCompanyName(?string $company_name): self {
        $this->company_name = $company_name;
        return $this;
    }

    public function setPosition(?string $position): self {
        $this->position = $position;
        return $this;
    }

    public function setBio(?string $bio): self {
        $this->bio = $bio;
        return $this;
    }

    public function setSkills(?array $skills): self {
        $this->skills = $skills;
        return $this;
    }

    public function setPortfolioVisibility(string $visibility): self {
        $this->portfolio_visibility = in_array($visibility, ['public', 'private']) ? $visibility : 'private';
        return $this;
    }

    public function setAllowContact(bool $allow_contact): self {
        $this->allow_contact = $allow_contact;
        return $this;
    }

    public function setSocialLinks(?array $social_links): self {
        $this->social_links = $social_links;
        return $this;
    }

    public function setWebsite(?string $website): self {
        $this->website = $website;
        return $this;
    }

    public function setLocation(?string $location): self {
        $this->location = $location;
        return $this;
    }

    // Static methods
    public static function findByUserId(DatabaseInterface $db_handler, int $user_id): ?array {
        $conn = $db_handler->getConnection();
        $stmt = $conn->prepare("SELECT * FROM client_profiles WHERE user_id = ?");
        $stmt->execute([$user_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function findById(DatabaseInterface $db_handler, int $id): ?array {
        $conn = $db_handler->getConnection();
        $stmt = $conn->prepare("SELECT * FROM client_profiles WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function getPublicProfiles(DatabaseInterface $db_handler, int $limit = 20, int $offset = 0): array {
        $conn = $db_handler->getConnection();
        $stmt = $conn->prepare("
            SELECT cp.*, u.username, u.email 
            FROM client_profiles cp
            INNER JOIN users u ON cp.user_id = u.id
            WHERE cp.portfolio_visibility = 'public' AND u.is_active = 1
            ORDER BY cp.updated_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$limit, $offset]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function loadByUserId(int $user_id): bool {
        $data = self::findByUserId($this->db_handler, $user_id);
        if ($data) {
            $this->fillFromArray($data);
            return true;
        }
        return false;
    }

    public function save(): bool {
        try {
            $conn = $this->db_handler->getConnection();

            $skills_json = $this->skills ? json_encode($this->skills) : null;
            $social_links_json = $this->social_links ? json_encode($this->social_links) : null;

            if ($this->id === null) {
                // Create new profile
                $stmt = $conn->prepare("
                    INSERT INTO client_profiles (
                        user_id, company_name, position, bio, skills, 
                        portfolio_visibility, allow_contact, social_links, website, location
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");

                $result = $stmt->execute([
                    $this->user_id, $this->company_name, $this->position, $this->bio,
                    $skills_json, $this->portfolio_visibility, $this->allow_contact ? 1 : 0,
                    $social_links_json, $this->website, $this->location
                ]);

                if ($result) {
                    $this->id = (int)$conn->lastInsertId();
                }

                return $result;
            } else {
                // Update existing profile
                $stmt = $conn->prepare("
                    UPDATE client_profiles SET 
                        company_name = ?, position = ?, bio = ?, skills = ?, 
                        portfolio_visibility = ?, allow_contact = ?, social_links = ?, 
                        website = ?, location = ?, updated_at = CURRENT_TIMESTAMP
                    WHERE id = ?
                ");

                return $stmt->execute([
                    $this->company_name, $this->position, $this->bio, $skills_json,
                    $this->portfolio_visibility, $this->allow_contact ? 1 : 0,
                    $social_links_json, $this->website, $this->location, $this->id
                ]);
            }
        } catch (PDOException $e) {
            error_log("ClientProfile::save() - PDO Exception: " . $e->getMessage());
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

            // Delete related portfolio projects
            $stmt = $conn->prepare("DELETE FROM client_portfolio WHERE client_profile_id = ?");
            $stmt->execute([$this->id]);

            // Delete skills
            $stmt = $conn->prepare("DELETE FROM client_skills WHERE client_profile_id = ?");
            $stmt->execute([$this->id]);

            // Delete social links
            $stmt = $conn->prepare("DELETE FROM client_social_links WHERE client_profile_id = ?");
            $stmt->execute([$this->id]);

            // Delete profile
            $stmt = $conn->prepare("DELETE FROM client_profiles WHERE id = ?");
            $result = $stmt->execute([$this->id]);

            $conn->commit();
            return $result;
        } catch (PDOException $e) {
            $conn->rollBack();
            error_log("ClientProfile::delete() - PDO Exception: " . $e->getMessage());
            return false;
        }
    }

    public function getPortfolioProjects(): array {
        if ($this->id === null) {
            return [];
        }

        $conn = $this->db_handler->getConnection();
        $stmt = $conn->prepare("
            SELECT * FROM client_portfolio 
            WHERE client_profile_id = ? 
            ORDER BY created_at DESC
        ");
        $stmt->execute([$this->id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPublicPortfolioProjects(): array {
        if ($this->id === null) {
            return [];
        }

        $conn = $this->db_handler->getConnection();
        $stmt = $conn->prepare("
            SELECT * FROM client_portfolio 
            WHERE client_profile_id = ? AND visibility = 'public' AND status = 'published'
            ORDER BY created_at DESC
        ");
        $stmt->execute([$this->id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateVisibility(string $visibility): bool {
        if (!in_array($visibility, ['public', 'private'])) {
            return false;
        }

        try {
            $conn = $this->db_handler->getConnection();
            $stmt = $conn->prepare("
                UPDATE client_profiles 
                SET portfolio_visibility = ?, updated_at = CURRENT_TIMESTAMP 
                WHERE id = ?
            ");

            $result = $stmt->execute([$visibility, $this->id]);
            if ($result) {
                $this->portfolio_visibility = $visibility;
            }

            return $result;
        } catch (PDOException $e) {
            return false;
        }
    }

    public function addSkill(string $skill): bool {
        try {
            $conn = $this->db_handler->getConnection();
            $stmt = $conn->prepare("
                INSERT IGNORE INTO client_skills (client_profile_id, skill) 
                VALUES (?, ?)
            ");
            return $stmt->execute([$this->id, $skill]);
        } catch (PDOException $e) {
            return false;
        }
    }

    public function removeSkill(string $skill): bool {
        try {
            $conn = $this->db_handler->getConnection();
            $stmt = $conn->prepare("
                DELETE FROM client_skills 
                WHERE client_profile_id = ? AND skill = ?
            ");
            return $stmt->execute([$this->id, $skill]);
        } catch (PDOException $e) {
            return false;
        }
    }

    public function getSkillsList(): array {
        if ($this->id === null) {
            return [];
        }

        $conn = $this->db_handler->getConnection();
        $stmt = $conn->prepare("
            SELECT skill FROM client_skills 
            WHERE client_profile_id = ? 
            ORDER BY skill
        ");
        $stmt->execute([$this->id]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    private function fillFromArray(array $data): void {
        $this->id = isset($data['id']) ? (int)$data['id'] : null;
        $this->user_id = isset($data['user_id']) ? (int)$data['user_id'] : null;
        $this->company_name = $data['company_name'] ?? null;
        $this->position = $data['position'] ?? null;
        $this->bio = $data['bio'] ?? null;
        $this->skills = isset($data['skills']) ? json_decode($data['skills'], true) : null;
        $this->portfolio_visibility = $data['portfolio_visibility'] ?? 'private';
        $this->allow_contact = isset($data['allow_contact']) ? (bool)$data['allow_contact'] : false;
        $this->social_links = isset($data['social_links']) ? json_decode($data['social_links'], true) : null;
        $this->website = $data['website'] ?? null;
        $this->location = $data['location'] ?? null;
        $this->created_at = $data['created_at'] ?? null;
        $this->updated_at = $data['updated_at'] ?? null;
    }

    public function toArray(): array {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'company_name' => $this->company_name,
            'position' => $this->position,
            'bio' => $this->bio,
            'skills' => $this->skills,
            'portfolio_visibility' => $this->portfolio_visibility,
            'allow_contact' => $this->allow_contact,
            'social_links' => $this->social_links,
            'website' => $this->website,
            'location' => $this->location,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }
}
