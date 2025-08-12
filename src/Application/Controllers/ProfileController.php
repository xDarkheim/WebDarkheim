<?php
declare(strict_types=1);

/**
 * Controller for user profile management
 * This controller handles actions related to user profiles in the admin panel.
 * It includes methods for updating user details, changing passwords, and handling email changes.
 * It also provides methods for fetching user data and generating flash messages.
 *
 * @author Dmytro Hovenko
 */

namespace App\Application\Controllers;

use App\Infrastructure\Lib\Database;
use App\Domain\Models\User;
use App\Infrastructure\Lib\FlashMessageService;
use App\Domain\Interfaces\TokenManagerInterface;
use Exception;
use Throwable;

class ProfileController {
    private Database $db_handler;
    private int $userId;
    private FlashMessageService $flashService;
    private TokenManagerInterface $tokenManager;
    private ?User $user = null;

    public function __construct(
        Database $db_handler,
        int $userId,
        FlashMessageService $flashService,
        TokenManagerInterface $tokenManager
    ) {
        $this->db_handler = $db_handler;
        $this->userId = $userId;
        $this->flashService = $flashService;
        $this->tokenManager = $tokenManager;
    }

    private function loadUser(): ?User {
        if ($this->user === null) {
            $this->user = (new User($this->db_handler))->findByIdAsObject($this->userId);
        }
        return $this->user;
    }

    public function getCurrentUserData(): ?array {
        $user = $this->loadUser();
        if (!$user) {
            return null;
        }
        return [
            'id' => $user->getId(),
            'username' => $user->getUsername(),
            'email' => $user->getEmail(),
            'role' => $user->getRole(),
            'created_at' => $user->getCreatedAt(),
            'location' => $user->getLocation(),
            'user_status' => $user->getUserStatus(),
            'bio' => $user->getBio(),
            'website_url' => $user->getWebsiteUrl(),
        ];
    }

    /**
     * Handle password change request (immediate apply, no email confirmation)
     */
    public function handleChangePasswordRequest(string $currentPassword, string $newPassword, string $confirmPassword): void {
        $user = $this->loadUser();
        if (!$user) {
            $this->flashService->addError("User data could not be loaded.");
            return;
        }

        // Validation
        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            $this->flashService->addError("All password fields are required.");
            return;
        }
        if ($newPassword !== $confirmPassword) {
            $this->flashService->addError("New password and confirmation password do not match.");
            return;
        }
        if (strlen($newPassword) < 8) {
            $this->flashService->addError("New password must be at least 8 characters long.");
            return;
        }

        // Verify the current password
        if (!password_verify($currentPassword, $user->getPasswordHash())) {
            $this->flashService->addError("Incorrect current password.");
            return;
        }

        // Apply password immediately
        try {
            $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
            if ($user->updateDetails(['password_hash' => $passwordHash])) {
                $this->flashService->addSuccess("Your password has been changed successfully.");
            } else {
                $this->flashService->addError("Failed to update password. Please try again.");
            }
        } catch (Throwable $e) {
            error_log("Password change failed: " . $e->getMessage());
            $this->flashService->addError("An error occurred while changing your password. Please try again later.");
        }
    }

    /**
     * Handle email change request (disabled)
     */
    public function handleEmailChangeRequest(): void {
        $this->flashService->addError("Email change is disabled.");
    }

    /**
     * Handle updating profile details (excluding email which has separate handling)
     */
    public function handleUpdateDetailsRequest(array $profileData): void {
        $user = $this->loadUser();
        if (!$user) {
            $this->flashService->addError("User data could not be loaded.");
            return;
        }

        // Validate and prepare data for update
        $updateData = [];
        $hasErrors = false;

        // Block email change via profile update
        if (array_key_exists('email', $profileData)) {
            $this->flashService->addError("Changing email is disabled.");
            $hasErrors = true;
        }

        // Process location field
        if (array_key_exists('location', $profileData)) {
            $location = trim($profileData['location']);
            if (strlen($location) > 100) {
                $this->flashService->addError("Location must be 100 characters or less.");
                $hasErrors = true;
            } else {
                $updateData['location'] = $location;
            }
        }

        // Process user_status field
        if (array_key_exists('user_status', $profileData)) {
            $userStatus = trim($profileData['user_status']);
            if (strlen($userStatus) > 150) {
                $this->flashService->addError("Status must be 150 characters or less.");
                $hasErrors = true;
            } else {
                $updateData['user_status'] = $userStatus;
            }
        }

        // Process bio field
        if (array_key_exists('bio', $profileData)) {
            $bio = trim($profileData['bio']);
            if (strlen($bio) > 1000) {
                $this->flashService->addError("Bio must be 1000 characters or less.");
                $hasErrors = true;
            } else {
                $updateData['bio'] = $bio;
            }
        }

        // Process website_url field
        if (array_key_exists('website_url', $profileData)) {
            $websiteUrl = trim($profileData['website_url']);
            if (!empty($websiteUrl) && !filter_var($websiteUrl, FILTER_VALIDATE_URL)) {
                $this->flashService->addError("Please enter a valid website URL.");
                $hasErrors = true;
            } else {
                $updateData['website_url'] = $websiteUrl;
            }
        }

        // Stop if there are validation errors
        if ($hasErrors) {
            return;
        }

        // Attempt to update if we have fields to process
        if (!empty($updateData)) {
            try {
                $updateResult = $user->updateDetails($updateData);

                if ($updateResult) {
                    $this->flashService->addSuccess("Your profile has been updated successfully.");
                    // Clear cached user data to reflect changes
                    $this->user = null;
                } else {
                    $this->flashService->addError("Failed to update profile. Please try again.");
                }
            } catch (Exception $e) {
                error_log("Profile update error: " . $e->getMessage());
                $this->flashService->addError("An error occurred while updating your profile. Please try again.");
            }
        } else {
            $this->flashService->addInfo("No profile fields were provided for update.");
        }
    }

}
