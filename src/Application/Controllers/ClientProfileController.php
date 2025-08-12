<?php

/**
 * ClientProfileController for managing client profiles
 *
 * @author Dmytro Hovenko
 */
declare(strict_types=1);

namespace App\Application\Controllers;

use App\Domain\Interfaces\DatabaseInterface;
use App\Domain\Models\ClientProfile;
use App\Domain\Models\User;
use App\Application\Middleware\ClientAreaMiddleware;
use Exception;

class ClientProfileController {
    private DatabaseInterface $db_handler;
    private ClientAreaMiddleware $middleware;

    public function __construct(DatabaseInterface $db_handler) {
        $this->db_handler = $db_handler;
        $this->middleware = new ClientAreaMiddleware($db_handler);
    }

    /**
     * API endpoint to update client profile
     */
    public function updateProfile(): array {
        if (!$this->middleware->handle()) {
            return ['success' => false, 'error' => 'Access denied'];
        }

        try {
            // Get or create client profile
            $profileData = ClientProfile::findByUserId($this->db_handler, (int)$_SESSION['user_id']);
            
            if ($profileData) {
                $profile = new ClientProfile($this->db_handler);
                $profile->loadByUserId((int)$_SESSION['user_id']);
            } else {
                $profile = new ClientProfile($this->db_handler);
                $profile->setUserId((int)$_SESSION['user_id']);
            }

            // Update profile fields
            if (isset($_POST['company_name'])) {
                $profile->setCompanyName(trim($_POST['company_name']));
            }
            if (isset($_POST['position'])) {
                $profile->setPosition(trim($_POST['position']));
            }
            if (isset($_POST['bio'])) {
                $profile->setBio(trim($_POST['bio']));
            }
            if (isset($_POST['website'])) {
                $profile->setWebsite(trim($_POST['website']));
            }
            if (isset($_POST['location'])) {
                $profile->setLocation(trim($_POST['location']));
            }
            if (isset($_POST['portfolio_visibility'])) {
                $profile->setPortfolioVisibility($_POST['portfolio_visibility']);
            }
            if (isset($_POST['allow_contact'])) {
                $profile->setAllowContact((bool)$_POST['allow_contact']);
            }

            if ($profile->save()) {
                return [
                    'success' => true,
                    'message' => 'Profile updated successfully'
                ];
            } else {
                return ['success' => false, 'error' => 'Failed to update profile'];
            }

        } catch (Exception $e) {
            error_log("ClientProfileController::updateProfile() - Exception: " . $e->getMessage());
            return ['success' => false, 'error' => 'Server error occurred'];
        }
    }

    /**
     * API endpoint to update client skills
     */
    public function updateSkills(): array {
        if (!$this->middleware->handle()) {
            return ['success' => false, 'error' => 'Access denied'];
        }

        try {
            $skills = $_POST['skills'] ?? [];
            if (!is_array($skills)) {
                $skills = json_decode($skills, true) ?: [];
            }

            // Get or create client profile
            $profileData = ClientProfile::findByUserId($this->db_handler, (int)$_SESSION['user_id']);
            
            if ($profileData) {
                $profile = new ClientProfile($this->db_handler);
                $profile->loadByUserId((int)$_SESSION['user_id']);
            } else {
                $profile = new ClientProfile($this->db_handler);
                $profile->setUserId((int)$_SESSION['user_id']);
                $profile->save();
                $profile->loadByUserId((int)$_SESSION['user_id']);
            }

            // Update skills in JSON format
            $profile->setSkills($skills);

            if ($profile->save()) {
                // Also update individual skills table
                $this->updateSkillsTable($profile->getId(), $skills);

                return [
                    'success' => true,
                    'message' => 'Skills updated successfully'
                ];
            } else {
                return ['success' => false, 'error' => 'Failed to update skills'];
            }

        } catch (Exception $e) {
            error_log("ClientProfileController::updateSkills() - Exception: " . $e->getMessage());
            return ['success' => false, 'error' => 'Server error occurred'];
        }
    }

    /**
     * API endpoint to update social links
     */
    public function updateSocialLinks(): array {
        if (!$this->middleware->handle()) {
            return ['success' => false, 'error' => 'Access denied'];
        }

        try {
            $socialLinks = $_POST['social_links'] ?? [];
            if (!is_array($socialLinks)) {
                $socialLinks = json_decode($socialLinks, true) ?: [];
            }

            // Get or create client profile
            $profileData = ClientProfile::findByUserId($this->db_handler, (int)$_SESSION['user_id']);
            
            if ($profileData) {
                $profile = new ClientProfile($this->db_handler);
                $profile->loadByUserId((int)$_SESSION['user_id']);
            } else {
                $profile = new ClientProfile($this->db_handler);
                $profile->setUserId((int)$_SESSION['user_id']);
                $profile->save();
                $profile->loadByUserId((int)$_SESSION['user_id']);
            }

            // Validate social links
            $validatedLinks = [];
            foreach ($socialLinks as $network => $url) {
                if (!empty($url) && filter_var($url, FILTER_VALIDATE_URL)) {
                    $validatedLinks[$network] = $url;
                }
            }

            $profile->setSocialLinks($validatedLinks);

            if ($profile->save()) {
                // Also update individual social links table
                $this->updateSocialLinksTable($profile->getId(), $validatedLinks);

                return [
                    'success' => true,
                    'message' => 'Social links updated successfully'
                ];
            } else {
                return ['success' => false, 'error' => 'Failed to update social links'];
            }

        } catch (Exception $e) {
            error_log("ClientProfileController::updateSocialLinks() - Exception: " . $e->getMessage());
            return ['success' => false, 'error' => 'Server error occurred'];
        }
    }

    /**
     * Get client profile data
     */
    public function getProfile(): array {
        if (!$this->middleware->handle()) {
            return ['success' => false, 'error' => 'Access denied'];
        }

        try {
            $profileData = ClientProfile::findByUserId($this->db_handler, (int)$_SESSION['user_id']);
            
            if ($profileData) {
                // Remove sensitive data
                unset($profileData['id']);
                
                return [
                    'success' => true,
                    'profile' => $profileData
                ];
            } else {
                return [
                    'success' => true,
                    'profile' => null,
                    'message' => 'No profile found'
                ];
            }

        } catch (Exception $e) {
            error_log("ClientProfileController::getProfile() - Exception: " . $e->getMessage());
            return ['success' => false, 'error' => 'Server error occurred'];
        }
    }

    /**
     * Get client projects
     */
    public function getProjects(): array {
        if (!$this->middleware->handle()) {
            return ['success' => false, 'error' => 'Access denied'];
        }

        try {
            $profileData = ClientProfile::findByUserId($this->db_handler, (int)$_SESSION['user_id']);
            
            if (!$profileData) {
                return [
                    'success' => true,
                    'projects' => []
                ];
            }

            $projects = ClientProject::findByClientProfileId($this->db_handler, (int)$profileData['id']);
            
            return [
                'success' => true,
                'projects' => $projects
            ];

        } catch (Exception $e) {
            error_log("ClientProfileController::getProjects() - Exception: " . $e->getMessage());
            return ['success' => false, 'error' => 'Server error occurred'];
        }
    }

    /**
     * Toggle portfolio visibility
     */
    public function togglePortfolioVisibility(): array {
        if (!$this->middleware->handle()) {
            return ['success' => false, 'error' => 'Access denied'];
        }

        try {
            $profileData = ClientProfile::findByUserId($this->db_handler, (int)$_SESSION['user_id']);
            
            if (!$profileData) {
                return ['success' => false, 'error' => 'Profile not found'];
            }

            $profile = new ClientProfile($this->db_handler);
            $profile->loadByUserId((int)$_SESSION['user_id']);

            $newVisibility = $profile->getPortfolioVisibility() === 'public' ? 'private' : 'public';
            
            if ($profile->updateVisibility($newVisibility)) {
                return [
                    'success' => true,
                    'message' => 'Portfolio visibility updated successfully',
                    'visibility' => $newVisibility
                ];
            } else {
                return ['success' => false, 'error' => 'Failed to update visibility'];
            }

        } catch (Exception $e) {
            error_log("ClientProfileController::togglePortfolioVisibility() - Exception: " . $e->getMessage());
            return ['success' => false, 'error' => 'Server error occurred'];
        }
    }

    /**
     * Update skills in the normalized skills table
     */
    private function updateSkillsTable(int $profileId, array $skills): void {
        try {
            $conn = $this->db_handler->getConnection();
            
            // Clear existing skills
            $stmt = $conn->prepare("DELETE FROM client_skills WHERE client_profile_id = ?");
            $stmt->execute([$profileId]);

            // Insert new skills
            if (!empty($skills)) {
                $stmt = $conn->prepare("
                    INSERT INTO client_skills (client_profile_id, skill, proficiency_level) 
                    VALUES (?, ?, ?)
                ");

                foreach ($skills as $skill) {
                    if (is_array($skill)) {
                        $skillName = $skill['name'] ?? '';
                        $proficiency = $skill['level'] ?? 'intermediate';
                    } else {
                        $skillName = $skill;
                        $proficiency = 'intermediate';
                    }

                    if (!empty($skillName)) {
                        $stmt->execute([$profileId, $skillName, $proficiency]);
                    }
                }
            }
        } catch (Exception $e) {
            error_log("ClientProfileController::updateSkillsTable() - Exception: " . $e->getMessage());
        }
    }

    /**
     * Update social links in the normalized social links table
     */
    private function updateSocialLinksTable(int $profileId, array $socialLinks): void {
        try {
            $conn = $this->db_handler->getConnection();
            
            // Clear existing social links
            $stmt = $conn->prepare("DELETE FROM client_social_links WHERE client_profile_id = ?");
            $stmt->execute([$profileId]);

            // Insert new social links
            if (!empty($socialLinks)) {
                $stmt = $conn->prepare("
                    INSERT INTO client_social_links (client_profile_id, network, url, is_primary) 
                    VALUES (?, ?, ?, ?)
                ");

                $isFirst = true;
                foreach ($socialLinks as $network => $url) {
                    if (!empty($url)) {
                        $stmt->execute([$profileId, $network, $url, $isFirst]);
                        $isFirst = false;
                    }
                }
            }
        } catch (Exception $e) {
            error_log("ClientProfileController::updateSocialLinksTable() - Exception: " . $e->getMessage());
        }
    }
}
