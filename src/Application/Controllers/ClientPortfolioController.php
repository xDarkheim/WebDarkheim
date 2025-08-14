<?php

/**
 * ClientPortfolioController for managing client portfolio projects
 *
 * @author Dmytro Hovenko
 */
declare(strict_types=1);

namespace App\Application\Controllers;

use App\Domain\Interfaces\DatabaseInterface;
use App\Domain\Models\ClientProject;
use App\Domain\Models\ClientProfile;
use App\Domain\Models\User;
use App\Application\Middleware\ClientAreaMiddleware;
use Exception;

class ClientPortfolioController {
    private DatabaseInterface $db_handler;
    private ClientAreaMiddleware $middleware;

    public function __construct(DatabaseInterface $db_handler) {
        $this->db_handler = $db_handler;
        $this->middleware = new ClientAreaMiddleware($db_handler);
    }

    /**
     * API endpoint to create a new project
     */
    public function create(): array {
        if (!$this->middleware->handle()) {
            return ['success' => false, 'error' => 'Access denied'];
        }

        try {
            // Validate input
            $title = trim($_POST['title'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $technologies = trim($_POST['technologies'] ?? '');
            $project_url = trim($_POST['project_url'] ?? '');
            $github_url = trim($_POST['github_url'] ?? '');
            $visibility = $_POST['visibility'] ?? 'private';

            if (empty($title)) {
                return ['success' => false, 'error' => 'Title is required'];
            }

            // Get or create client profile
            $clientProfile = ClientProfile::findByUserId($this->db_handler, (int)$_SESSION['user_id']);
            if (!$clientProfile) {
                // Create basic profile if doesn't exist
                $profile = new ClientProfile($this->db_handler);
                $profile->setUserId((int)$_SESSION['user_id']);
                $profile->save();
                $clientProfile = ClientProfile::findByUserId($this->db_handler, (int)$_SESSION['user_id']);
            }

            // Create project
            $project = new ClientProject($this->db_handler);
            $project->setClientProfileId((int)$clientProfile['id'])
                   ->setTitle($title)
                   ->setDescription($description)
                   ->setTechnologies($technologies)
                   ->setProjectUrl($project_url)
                   ->setGithubUrl($github_url)
                   ->setVisibility($visibility)
                   ->setStatus('draft');

            // Handle image upload
            if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
                $images = $this->handleImageUpload($_FILES['images']);
                if ($images) {
                    $project->setImages($images);
                }
            }

            if ($project->save()) {
                return [
                    'success' => true,
                    'message' => 'Project created successfully',
                    'project_id' => $project->getId()
                ];
            } else {
                return ['success' => false, 'error' => 'Failed to create project'];
            }

        } catch (Exception $e) {
            error_log("ClientPortfolioController::create() - Exception: " . $e->getMessage());
            return ['success' => false, 'error' => 'Server error occurred'];
        }
    }

    /**
     * API endpoint to update an existing project
     */
    public function update(): array {
        if (!$this->middleware->handle()) {
            return ['success' => false, 'error' => 'Access denied'];
        }

        try {
            $project_id = (int)($_POST['project_id'] ?? 0);
            if ($project_id <= 0) {
                return ['success' => false, 'error' => 'Invalid project ID'];
            }

            $project = new ClientProject($this->db_handler);
            if (!$project->loadById($project_id)) {
                return ['success' => false, 'error' => 'Project not found'];
            }

            // Check ownership
            $clientProfile = ClientProfile::findByUserId($this->db_handler, (int)$_SESSION['user_id']);
            if (!$clientProfile || $project->getClientProfileId() !== (int)$clientProfile['id']) {
                $userData = User::findById($this->db_handler, (int)$_SESSION['user_id']);
                if ($userData['role'] !== 'admin') {
                    return ['success' => false, 'error' => 'Access denied'];
                }
            }

            // Update project fields
            if (isset($_POST['title'])) {
                $project->setTitle(trim($_POST['title']));
            }
            if (isset($_POST['description'])) {
                $project->setDescription(trim($_POST['description']));
            }
            if (isset($_POST['technologies'])) {
                $project->setTechnologies(trim($_POST['technologies']));
            }
            if (isset($_POST['project_url'])) {
                $project->setProjectUrl(trim($_POST['project_url']));
            }
            if (isset($_POST['github_url'])) {
                $project->setGithubUrl(trim($_POST['github_url']));
            }
            if (isset($_POST['visibility'])) {
                $project->setVisibility($_POST['visibility']);
            }

            // Handle new image upload
            if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
                $images = $this->handleImageUpload($_FILES['images']);
                if ($images) {
                    $project->setImages($images);
                }
            }

            if ($project->save()) {
                return [
                    'success' => true,
                    'message' => 'Project updated successfully'
                ];
            } else {
                return ['success' => false, 'error' => 'Failed to update project'];
            }

        } catch (Exception $e) {
            error_log("ClientPortfolioController::update() - Exception: " . $e->getMessage());
            return ['success' => false, 'error' => 'Server error occurred'];
        }
    }

    /**
     * API endpoint to delete a project
     */
    public function delete(): array {
        if (!$this->middleware->handle()) {
            return ['success' => false, 'error' => 'Access denied'];
        }

        try {
            $project_id = (int)($_POST['project_id'] ?? 0);
            if ($project_id <= 0) {
                return ['success' => false, 'error' => 'Invalid project ID'];
            }

            $project = new ClientProject($this->db_handler);
            if (!$project->loadById($project_id)) {
                return ['success' => false, 'error' => 'Project not found'];
            }

            // Check ownership
            $clientProfile = ClientProfile::findByUserId($this->db_handler, (int)$_SESSION['user_id']);
            if (!$clientProfile || $project->getClientProfileId() !== (int)$clientProfile['id']) {
                $userData = User::findById($this->db_handler, (int)$_SESSION['user_id']);
                if ($userData['role'] !== 'admin') {
                    return ['success' => false, 'error' => 'Access denied'];
                }
            }

            // Delete project images from filesystem
            $images = $project->getImages();
            if ($images) {
                foreach ($images as $image) {
                    $imagePath = $_SERVER['DOCUMENT_ROOT'] . '/storage/uploads/portfolio/' . $image;
                    if (file_exists($imagePath)) {
                        unlink($imagePath);
                    }
                }
            }

            if ($project->delete()) {
                return [
                    'success' => true,
                    'message' => 'Project deleted successfully'
                ];
            } else {
                return ['success' => false, 'error' => 'Failed to delete project'];
            }

        } catch (Exception $e) {
            error_log("ClientPortfolioController::delete() - Exception: " . $e->getMessage());
            return ['success' => false, 'error' => 'Server error occurred'];
        }
    }

    /**
     * API endpoint to upload project images
     */
    public function uploadImages(): array {
        if (!$this->middleware->handle()) {
            return ['success' => false, 'error' => 'Access denied'];
        }

        try {
            if (!isset($_FILES['images']) || empty($_FILES['images']['name'][0])) {
                return ['success' => false, 'error' => 'No images uploaded'];
            }

            $images = $this->handleImageUpload($_FILES['images']);
            if ($images) {
                return [
                    'success' => true,
                    'message' => 'Images uploaded successfully',
                    'images' => $images
                ];
            } else {
                return ['success' => false, 'error' => 'Failed to upload images'];
            }

        } catch (Exception $e) {
            error_log("ClientPortfolioController::uploadImages() - Exception: " . $e->getMessage());
            return ['success' => false, 'error' => 'Server error occurred'];
        }
    }

    /**
     * API endpoint to toggle project visibility
     */
    public function toggleVisibility(): array {
        if (!$this->middleware->handle()) {
            return ['success' => false, 'error' => 'Access denied'];
        }

        try {
            $project_id = (int)($_POST['project_id'] ?? 0);
            if ($project_id <= 0) {
                return ['success' => false, 'error' => 'Invalid project ID'];
            }

            $project = new ClientProject($this->db_handler);
            if (!$project->loadById($project_id)) {
                return ['success' => false, 'error' => 'Project not found'];
            }

            // Check ownership
            $clientProfile = ClientProfile::findByUserId($this->db_handler, (int)$_SESSION['user_id']);
            if (!$clientProfile || $project->getClientProfileId() !== (int)$clientProfile['id']) {
                return ['success' => false, 'error' => 'Access denied'];
            }

            // Only published projects can be made public
            if ($project->getStatus() !== 'published' && $_POST['visibility'] === 'public') {
                return ['success' => false, 'error' => 'Only published projects can be made public'];
            }

            $newVisibility = $project->getVisibility() === 'public' ? 'private' : 'public';
            $project->setVisibility($newVisibility);

            if ($project->save()) {
                return [
                    'success' => true,
                    'message' => 'Visibility updated successfully',
                    'visibility' => $newVisibility
                ];
            } else {
                return ['success' => false, 'error' => 'Failed to update visibility'];
            }

        } catch (Exception $e) {
            error_log("ClientPortfolioController::toggleVisibility() - Exception: " . $e->getMessage());
            return ['success' => false, 'error' => 'Server error occurred'];
        }
    }

    /**
     * Submit project for moderation
     */
    public function submitForModeration(): array {
        if (!$this->middleware->handle()) {
            return ['success' => false, 'error' => 'Access denied'];
        }

        try {
            $project_id = (int)($_POST['project_id'] ?? 0);
            if ($project_id <= 0) {
                return ['success' => false, 'error' => 'Invalid project ID'];
            }

            $project = new ClientProject($this->db_handler);
            if (!$project->loadById($project_id)) {
                return ['success' => false, 'error' => 'Project not found'];
            }

            // Check ownership
            $clientProfile = ClientProfile::findByUserId($this->db_handler, (int)$_SESSION['user_id']);
            if (!$clientProfile || $project->getClientProfileId() !== (int)$clientProfile['id']) {
                return ['success' => false, 'error' => 'Access denied'];
            }

            if ($project->submitForModeration()) {
                return [
                    'success' => true,
                    'message' => 'Project submitted for moderation successfully'
                ];
            } else {
                return ['success' => false, 'error' => 'Failed to submit project for moderation'];
            }

        } catch (Exception $e) {
            error_log("ClientPortfolioController::submitForModeration() - Exception: " . $e->getMessage());
            return ['success' => false, 'error' => 'Server error occurred'];
        }
    }

    /**
     * Handle image upload for projects
     */
    private function handleImageUpload(array $files): ?array {
        $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/storage/uploads/portfolio/';

        // Create directory if it doesn't exist
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $maxFileSize = 5 * 1024 * 1024; // 5MB
        $uploadedImages = [];

        $fileCount = is_array($files['name']) ? count($files['name']) : 1;

        for ($i = 0; $i < $fileCount; $i++) {
            $fileName = is_array($files['name']) ? $files['name'][$i] : $files['name'];
            $fileTmpName = is_array($files['tmp_name']) ? $files['tmp_name'][$i] : $files['tmp_name'];
            $fileSize = is_array($files['size']) ? $files['size'][$i] : $files['size'];
            $fileType = is_array($files['type']) ? $files['type'][$i] : $files['type'];

            if (empty($fileName)) continue;

            // Validate file type
            if (!in_array($fileType, $allowedTypes)) {
                continue;
            }

            // Validate file size
            if ($fileSize > $maxFileSize) {
                continue;
            }

            // Generate unique filename
            $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
            $uniqueFileName = uniqid('portfolio_') . '.' . $fileExtension;
            $uploadPath = $uploadDir . $uniqueFileName;

            // Move uploaded file
            if (move_uploaded_file($fileTmpName, $uploadPath)) {
                $uploadedImages[] = $uniqueFileName;
            }
        }

        return !empty($uploadedImages) ? $uploadedImages : null;
    }
}
