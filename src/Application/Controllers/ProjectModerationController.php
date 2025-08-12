<?php

/**
 * ProjectModerationController for moderating client portfolio projects
 *
 * @author Dmytro Hovenko
 */
declare(strict_types=1);

namespace App\Application\Controllers;

use App\Domain\Interfaces\DatabaseInterface;
use App\Domain\Models\ClientProject;
use App\Domain\Models\User;
use App\Application\Middleware\RoleMiddleware;
use Exception;

class ProjectModerationController {
    private DatabaseInterface $db_handler;
    private RoleMiddleware $middleware;

    public function __construct(DatabaseInterface $db_handler) {
        $this->db_handler = $db_handler;
        $this->middleware = new RoleMiddleware($db_handler);
    }

    /**
     * Get projects pending moderation
     */
    public function getPendingProjects(): array {
        if (!$this->middleware->requireRole(['admin', 'employee'])) {
            return ['success' => false, 'error' => 'Access denied'];
        }

        try {
            $projects = ClientProject::getProjectsForModeration($this->db_handler);

            return [
                'success' => true,
                'projects' => $projects
            ];

        } catch (Exception $e) {
            error_log("ProjectModerationController::getPendingProjects() - Exception: " . $e->getMessage());
            return ['success' => false, 'error' => 'Server error occurred'];
        }
    }

    /**
     * Moderate a project (approve or reject)
     */
    public function moderateProject(): array {
        if (!$this->middleware->requireRole(['admin', 'employee'])) {
            return ['success' => false, 'error' => 'Access denied'];
        }

        try {
            $project_id = (int)($_POST['project_id'] ?? 0);
            $action = $_POST['action'] ?? ''; // 'approve' or 'reject'
            $notes = trim($_POST['notes'] ?? '');

            if ($project_id <= 0) {
                return ['success' => false, 'error' => 'Invalid project ID'];
            }

            if (!in_array($action, ['approve', 'reject'])) {
                return ['success' => false, 'error' => 'Invalid action'];
            }

            $project = new ClientProject($this->db_handler);
            if (!$project->loadById($project_id)) {
                return ['success' => false, 'error' => 'Project not found'];
            }

            if ($project->getStatus() !== 'pending') {
                return ['success' => false, 'error' => 'Project is not pending moderation'];
            }

            $status = $action === 'approve' ? 'published' : 'rejected';
            $moderator_id = (int)$_SESSION['user_id'];

            if ($project->moderate($moderator_id, $status, $notes)) {
                // Send notification to client (implement later)
                $this->sendModerationNotification($project, $status, $notes);

                return [
                    'success' => true,
                    'message' => "Project {$action}d successfully"
                ];
            } else {
                return ['success' => false, 'error' => 'Failed to moderate project'];
            }

        } catch (Exception $e) {
            error_log("ProjectModerationController::moderateProject() - Exception: " . $e->getMessage());
            return ['success' => false, 'error' => 'Server error occurred'];
        }
    }

    /**
     * Get moderation history for a project
     */
    public function getModerationHistory(): array {
        if (!$this->middleware->requireRole(['admin', 'employee'])) {
            return ['success' => false, 'error' => 'Access denied'];
        }

        try {
            $project_id = (int)($_GET['project_id'] ?? 0);

            if ($project_id <= 0) {
                return ['success' => false, 'error' => 'Invalid project ID'];
            }

            $conn = $this->db_handler->getConnection();
            $stmt = $conn->prepare("
                SELECT cp.*, u.username as moderator_name
                FROM client_portfolio cp
                LEFT JOIN users u ON cp.moderator_id = u.id
                WHERE cp.id = ?
            ");
            $stmt->execute([$project_id]);
            $project = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$project) {
                return ['success' => false, 'error' => 'Project not found'];
            }

            return [
                'success' => true,
                'project' => $project
            ];

        } catch (Exception $e) {
            error_log("ProjectModerationController::getModerationHistory() - Exception: " . $e->getMessage());
            return ['success' => false, 'error' => 'Server error occurred'];
        }
    }

    /**
     * Get moderation statistics
     */
    public function getModerationStats(): array {
        if (!$this->middleware->requireRole(['admin', 'employee'])) {
            return ['success' => false, 'error' => 'Access denied'];
        }

        try {
            $conn = $this->db_handler->getConnection();

            // Get overall stats
            $stmt = $conn->prepare("
                SELECT 
                    status,
                    COUNT(*) as count
                FROM client_portfolio 
                GROUP BY status
            ");
            $stmt->execute();
            $statusStats = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Get monthly moderation activity
            $stmt = $conn->prepare("
                SELECT 
                    DATE_FORMAT(moderated_at, '%Y-%m') as month,
                    status,
                    COUNT(*) as count
                FROM client_portfolio 
                WHERE moderated_at IS NOT NULL
                AND moderated_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
                GROUP BY month, status
                ORDER BY month DESC
            ");
            $stmt->execute();
            $monthlyStats = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Get moderator activity
            $stmt = $conn->prepare("
                SELECT 
                    u.username,
                    cp.status,
                    COUNT(*) as count
                FROM client_portfolio cp
                INNER JOIN users u ON cp.moderator_id = u.id
                WHERE cp.moderated_at IS NOT NULL
                AND cp.moderated_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                GROUP BY u.id, u.username, cp.status
                ORDER BY count DESC
            ");
            $stmt->execute();
            $moderatorStats = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'success' => true,
                'stats' => [
                    'status_distribution' => $statusStats,
                    'monthly_activity' => $monthlyStats,
                    'moderator_activity' => $moderatorStats
                ]
            ];

        } catch (Exception $e) {
            error_log("ProjectModerationController::getModerationStats() - Exception: " . $e->getMessage());
            return ['success' => false, 'error' => 'Server error occurred'];
        }
    }

    /**
     * Bulk moderate projects
     */
    public function bulkModerate(): array {
        if (!$this->middleware->requireRole(['admin', 'employee'])) {
            return ['success' => false, 'error' => 'Access denied'];
        }

        try {
            $project_ids = $_POST['project_ids'] ?? [];
            $action = $_POST['action'] ?? ''; // 'approve' or 'reject'
            $notes = trim($_POST['notes'] ?? '');

            if (!is_array($project_ids) || empty($project_ids)) {
                return ['success' => false, 'error' => 'No projects selected'];
            }

            if (!in_array($action, ['approve', 'reject'])) {
                return ['success' => false, 'error' => 'Invalid action'];
            }

            $status = $action === 'approve' ? 'published' : 'rejected';
            $moderator_id = (int)$_SESSION['user_id'];
            $processed = 0;
            $errors = [];

            foreach ($project_ids as $project_id) {
                $project_id = (int)$project_id;

                $project = new ClientProject($this->db_handler);
                if ($project->loadById($project_id)) {
                    if ($project->getStatus() === 'pending') {
                        if ($project->moderate($moderator_id, $status, $notes)) {
                            $processed++;
                            // Send notification
                            $this->sendModerationNotification($project, $status, $notes);
                        } else {
                            $errors[] = "Failed to moderate project ID: {$project_id}";
                        }
                    } else {
                        $errors[] = "Project ID {$project_id} is not pending moderation";
                    }
                } else {
                    $errors[] = "Project ID {$project_id} not found";
                }
            }

            return [
                'success' => true,
                'message' => "Processed {$processed} projects successfully",
                'processed_count' => $processed,
                'errors' => $errors
            ];

        } catch (Exception $e) {
            error_log("ProjectModerationController::bulkModerate() - Exception: " . $e->getMessage());
            return ['success' => false, 'error' => 'Server error occurred'];
        }
    }

    /**
     * Get project details for moderation review
     */
    public function getProjectForReview(): array {
        if (!$this->middleware->requireRole(['admin', 'employee'])) {
            return ['success' => false, 'error' => 'Access denied'];
        }

        try {
            $project_id = (int)($_GET['project_id'] ?? 0);

            if ($project_id <= 0) {
                return ['success' => false, 'error' => 'Invalid project ID'];
            }

            $conn = $this->db_handler->getConnection();
            $stmt = $conn->prepare("
                SELECT 
                    cp.*,
                    cl.company_name,
                    cl.bio as client_bio,
                    cl.website as client_website,
                    u.username,
                    u.email,
                    u.created_at as user_created_at
                FROM client_portfolio cp
                INNER JOIN client_profiles cl ON cp.client_profile_id = cl.id
                INNER JOIN users u ON cl.user_id = u.id
                WHERE cp.id = ?
            ");
            $stmt->execute([$project_id]);
            $project = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$project) {
                return ['success' => false, 'error' => 'Project not found'];
            }

            // Get project categories
            $stmt = $conn->prepare("
                SELECT pc.name, pc.color
                FROM project_categories pc
                INNER JOIN project_category_assignments pca ON pc.id = pca.category_id
                WHERE pca.project_id = ?
            ");
            $stmt->execute([$project_id]);
            $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $project['categories'] = $categories;

            return [
                'success' => true,
                'project' => $project
            ];

        } catch (Exception $e) {
            error_log("ProjectModerationController::getProjectForReview() - Exception: " . $e->getMessage());
            return ['success' => false, 'error' => 'Server error occurred'];
        }
    }

    /**
     * Send moderation notification to client (placeholder for email notification)
     */
    private function sendModerationNotification(ClientProject $project, string $status, ?string $notes): void {
        try {
            // Get client information
            $conn = $this->db_handler->getConnection();
            $stmt = $conn->prepare("
                SELECT u.email, u.username, cl.company_name
                FROM users u
                INNER JOIN client_profiles cl ON u.id = cl.user_id
                WHERE cl.id = ?
            ");
            $stmt->execute([$project->getClientProfileId()]);
            $client = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($client) {
                // Log the notification (implement actual email sending later)
                error_log("Moderation notification: Project '{$project->getTitle()}' {$status} for {$client['email']}");

                // TODO: Implement actual email notification using existing email system
                // $this->sendEmail($client['email'], $subject, $body);
            }
        } catch (Exception $e) {
            error_log("ProjectModerationController::sendModerationNotification() - Exception: " . $e->getMessage());
        }
    }
}
