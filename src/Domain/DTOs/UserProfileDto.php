<?php

/**
 * Data Transfer Object for user profile data
 *
 * @author Dmytro Hovenko
 */

declare(strict_types=1);

namespace App\Domain\DTOs;


class UserProfileDto
{
    private int $id;
    private string $username;
    private string $email;
    private string $role;
    private string $createdAt;
    private ?string $location;
    private ?string $userStatus;
    private ?string $bio;
    private ?string $websiteUrl;

    public function __construct(
        int $id,
        string $username,
        string $email,
        string $role,
        string $createdAt,
        ?string $location = null,
        ?string $userStatus = null,
        ?string $bio = null,
        ?string $websiteUrl = null
    ) {
        $this->id = $id;
        $this->username = $username;
        $this->email = $email;
        $this->role = $role;
        $this->createdAt = $createdAt;
        $this->location = $location;
        $this->userStatus = $userStatus;
        $this->bio = $bio;
        $this->websiteUrl = $websiteUrl;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function getUserStatus(): ?string
    {
        return $this->userStatus;
    }

    public function getBio(): ?string
    {
        return $this->bio;
    }

    public function getWebsiteUrl(): ?string
    {
        return $this->websiteUrl;
    }

    /**
     * Convert to array for compatibility with existing code
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'email' => $this->email,
            'role' => $this->role,
            'created_at' => $this->createdAt,
            'location' => $this->location,
            'user_status' => $this->userStatus,
            'bio' => $this->bio,
            'website_url' => $this->websiteUrl,
        ];
    }

    /**
     * Create from array data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            $data['id'],
            $data['username'],
            $data['email'],
            $data['role'],
            $data['created_at'],
            $data['location'] ?? null,
            $data['user_status'] ?? null,
            $data['bio'] ?? null,
            $data['website_url'] ?? null
        );
    }
}
