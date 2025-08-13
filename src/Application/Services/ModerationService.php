<?php

/**
 * Moderation Service
 * Business logic for moderation operations
 *
 * @author GitHub Copilot
 */

declare(strict_types=1);

namespace App\Application\Services;

use App\Domain\Interfaces\DatabaseInterface;
use App\Domain\Interfaces\LoggerInterface;
use App\Domain\Models\ClientProject;
use App\Domain\Models\Comments;
use PDO;
use Exception;

class ModerationService
{
    private DatabaseInterface $database;
    private LoggerInterface $logger;

    public function __construct(DatabaseInterface $database, LoggerInterface $logger)
    {
        $this->database = $database;
        $this->logger = $logger;
    }

    /**
     * Get projects for moderation with filtering and pagination
     */
    public function getProjectsForModeration(array $filters): array
    {
        try {
            $connection = $this->database->getConnection();
            
            // Build WHERE conditions
            $whereConditions = [];
            $params = [];

            // Filter by status
            if (!empty($filters['status']) && $filters['status'] !== 'all') {
                $whereConditions[] = "cp.status = ?";
                $params[] = $filters['status'];
            }

            // Search by title or description
            if (!empty($filters['search'])) {
                $whereConditions[] = "(cp.title LIKE ? OR cp.description LIKE ?)";
                $searchParam = '%' . $filters['search'] . '%';
                $params[] = $searchParam;
                $params[] = $searchParam;
            }

            $whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";

            // Count total projects
            $countSql = "SELECT COUNT(*) FROM client_portfolio cp 
                        LEFT JOIN client_profiles prof ON cp.client_profile_id = prof.user_id 
                        $whereClause";
            $countStmt = $connection->prepare($countSql);
            $countStmt->execute($params);
            $totalProjects = (int)$countStmt->fetchColumn();

            // Calculate pagination
            $perPage = $filters['per_page'] ?? 20;
            $currentPage = $filters['page'] ?? 1;
            $totalPages = max(1, ceil($totalProjects / $perPage));
            $offset = ($currentPage - 1) * $perPage;

            // Build ORDER BY clause
            $orderBy = match ($filters['sort'] ?? 'created_desc') {
                'created_asc' => "ORDER BY cp.created_at ASC",
                'title_asc' => "ORDER BY cp.title ASC",
                'title_desc' => "ORDER BY cp.title DESC",
                'status_asc' => "ORDER BY cp.status ASC, cp.created_at DESC",
                default => "ORDER BY cp.created_at DESC"
            };

            // Get projects
            $projectsSql = "SELECT cp.*, 
                                  prof.company_name,
                                  u.username as client_username,
                                  u.email as client_email
                           FROM client_portfolio cp
                           LEFT JOIN client_profiles prof ON cp.client_profile_id = prof.user_id
                           LEFT JOIN users u ON cp.client_profile_id = u.id
                           $whereClause
                           $orderBy
                           LIMIT ? OFFSET ?";

            $params[] = $perPage;
            $params[] = $offset;

            $projectsStmt = $connection->prepare($projectsSql);
            $projectsStmt->execute($params);
            $projects = $projectsStmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'projects' => $projects,
                'total' => $totalProjects,
                'current_page' => $currentPage,
                'total_pages' => $totalPages
            ];

        } catch (Exception $e) {
            $this->logger->error("Error getting projects for moderation: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get single project for moderation
     */
    public function getProjectForModeration(int $projectId): ?array
    {
        try {
            $connection = $this->database->getConnection();

            $sql = "SELECT cp.*, 
                          prof.company_name,
                          prof.bio as client_bio,
                          u.username as client_username,
                          u.email as client_email,
                          mod_u.username as moderated_by_username
                   FROM client_portfolio cp
                   LEFT JOIN client_profiles prof ON cp.client_profile_id = prof.user_id
                   LEFT JOIN users u ON cp.client_profile_id = u.id
                   LEFT JOIN users mod_u ON cp.moderator_id = mod_u.id
                   WHERE cp.id = ?";

            $stmt = $connection->prepare($sql);
            $stmt->execute([$projectId]);
            $project = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($project) {
                // Parse JSON fields
                $project['technologies'] = json_decode($project['technologies'] ?? '[]', true);
                $project['images'] = json_decode($project['images'] ?? '[]', true);
            }

            return $project ?: null;

        } catch (Exception $e) {
            $this->logger->error("Error getting project for moderation: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get moderation statistics
     */
    public function getModerationStatistics(): array
    {
        try {
            $connection = $this->database->getConnection();

            // Projects statistics
            $projectsStatsSQL = "SELECT 
                                status,
                                COUNT(*) as count
                               FROM client_portfolio 
                               GROUP BY status";
            $stmt = $connection->query($projectsStatsSQL);
            $projectsStats = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $projectsStats[$row['status']] = (int)$row['count'];
            }

            // Comments statistics
            $commentsStatsSQL = "SELECT 
                                status,
                                COUNT(*) as count
                               FROM comments 
                               GROUP BY status";
            $stmt = $connection->query($commentsStatsSQL);
            $commentsStats = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $commentsStats[$row['status']] = (int)$row['count'];
            }

            // Recent activity
            $recentActivitySQL = "SELECT COUNT(*) as count
                                 FROM client_portfolio 
                                 WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
            $stmt = $connection->query($recentActivitySQL);
            $recentProjects = (int)$stmt->fetchColumn();

            return [
                'projects' => $projectsStats,
                'comments' => $commentsStats,
                'recent_projects_week' => $recentProjects,
                'pending_projects' => $projectsStats['pending'] ?? 0,
                'pending_comments' => $commentsStats['pending'] ?? 0
            ];

        } catch (Exception $e) {
            $this->logger->error("Error getting moderation statistics: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get dashboard statistics
     */
    public function getDashboardStatistics(): array
    {
        try {
            $connection = $this->database->getConnection();

            $stats = [];

            // Pending projects count
            $stmt = $connection->query("SELECT COUNT(*) FROM client_portfolio WHERE status = 'pending'");
            $stats['pending_projects'] = (int)$stmt->fetchColumn();

            // Pending comments count
            $stmt = $connection->query("SELECT COUNT(*) FROM comments WHERE status = 'pending'");
            $stats['pending_comments'] = (int)$stmt->fetchColumn();

            // Projects moderated today
            $stmt = $connection->query("SELECT COUNT(*) FROM client_portfolio WHERE DATE(moderated_at) = CURDATE()");
            $stats['moderated_today'] = (int)$stmt->fetchColumn();

            // Total published projects
            $stmt = $connection->query("SELECT COUNT(*) FROM client_portfolio WHERE status = 'published'");
            $stats['total_published'] = (int)$stmt->fetchColumn();

            // Projects by status for chart
            $stmt = $connection->query("SELECT status, COUNT(*) as count FROM client_portfolio GROUP BY status");
            $statusData = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $statusData[$row['status']] = (int)$row['count'];
            }
            $stats['projects_by_status'] = $statusData;

            return $stats;

        } catch (Exception $e) {
            $this->logger->error("Error getting dashboard statistics: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get recent projects for moderation (for dashboard)
     */
    public function getRecentProjectsForModeration(int $limit = 5): array
    {
        try {
            $connection = $this->database->getConnection();

            $sql = "SELECT cp.id, cp.title, cp.status, cp.created_at,
                          u.username as client_username
                   FROM client_portfolio cp
                   LEFT JOIN users u ON cp.client_profile_id = u.id
                   WHERE cp.status = 'pending'
                   ORDER BY cp.created_at DESC
                   LIMIT ?";

            $stmt = $connection->prepare($sql);
            $stmt->execute([$limit]);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            $this->logger->error("Error getting recent projects for moderation: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get project moderation history
     */
    public function getProjectModerationHistory(int $projectId): array
    {
        try {
            $connection = $this->database->getConnection();

            // For now, we'll get basic history from the project record
            // This can be expanded with a dedicated moderation_history table
            $sql = "SELECT cp.status, cp.moderated_at, cp.moderation_notes,
                          u.username as moderated_by_username
                   FROM client_portfolio cp
                   LEFT JOIN users u ON cp.moderator_id = u.id
                   WHERE cp.id = ? AND cp.moderated_at IS NOT NULL";

            $stmt = $connection->prepare($sql);
            $stmt->execute([$projectId]);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            $this->logger->error("Error getting project moderation history: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get comments for moderation
     */
    public function getCommentsForModeration(array $filters): array
    {
        try {
            $connection = $this->database->getConnection();

            // Build WHERE conditions
            $whereConditions = [];
            $params = [];

            // Filter by status
            if (!empty($filters['status']) && $filters['status'] !== 'all') {
                $whereConditions[] = "c.status = ?";
                $params[] = $filters['status'];
            }

            // Filter by commentable type
            if (!empty($filters['type']) && $filters['type'] !== 'all') {
                $whereConditions[] = "c.commentable_type = ?";
                $params[] = $filters['type'];
            }

            // Search in content or author
            if (!empty($filters['search'])) {
                $whereConditions[] = "(c.content LIKE ? OR c.author_name LIKE ? OR c.author_email LIKE ?)";
                $searchParam = '%' . $filters['search'] . '%';
                $params[] = $searchParam;
                $params[] = $searchParam;
                $params[] = $searchParam;
            }

            $whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";

            // Count total comments
            $countSql = "SELECT COUNT(*) FROM comments c $whereClause";
            $countStmt = $connection->prepare($countSql);
            $countStmt->execute($params);
            $totalComments = (int)$countStmt->fetchColumn();

            // Calculate pagination
            $perPage = $filters['per_page'] ?? 20;
            $currentPage = $filters['page'] ?? 1;
            $totalPages = max(1, ceil($totalComments / $perPage));
            $offset = ($currentPage - 1) * $perPage;

            // Get comments
            $commentsSql = "SELECT c.*, 
                                  u.username as user_username,
                                  cp.id as project_id,
                                  cp.title as project_title
                           FROM comments c
                           LEFT JOIN users u ON c.user_id = u.id
                           LEFT JOIN client_portfolio cp ON c.commentable_type = 'portfolio_project' AND c.commentable_id = cp.id
                           $whereClause
                           ORDER BY c.created_at DESC
                           LIMIT ? OFFSET ?";

            $params[] = $perPage;
            $params[] = $offset;

            $commentsStmt = $connection->prepare($commentsSql);
            $commentsStmt->execute($params);
            $comments = $commentsStmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'comments' => $comments,
                'total' => $totalComments,
                'current_page' => $currentPage,
                'total_pages' => $totalPages
            ];

        } catch (Exception $e) {
            $this->logger->error("Error getting comments for moderation: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get comments statistics
     */
    public function getCommentsStatistics(): array
    {
        try {
            $connection = $this->database->getConnection();

            $stats = [];

            // Comments by status
            $stmt = $connection->query("SELECT status, COUNT(*) as count FROM comments GROUP BY status");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $stats[$row['status']] = (int)$row['count'];
            }

            // Comments by type
            $stmt = $connection->query("SELECT commentable_type, COUNT(*) as count FROM comments GROUP BY commentable_type");
            $typeStats = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $typeStats[$row['commentable_type']] = (int)$row['count'];
            }
            $stats['by_type'] = $typeStats;

            return $stats;

        } catch (Exception $e) {
            $this->logger->error("Error getting comments statistics: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get moderator notifications
     */
    public function getModeratorNotifications(): array
    {
        try {
            $connection = $this->database->getConnection();

            $notifications = [];

            // New pending projects
            $stmt = $connection->query("SELECT COUNT(*) FROM client_portfolio WHERE status = 'pending' AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)");
            $newProjects = (int)$stmt->fetchColumn();
            
            if ($newProjects > 0) {
                $notifications[] = [
                    'type' => 'new_projects',
                    'message' => "$newProjects new project(s) waiting for moderation",
                    'count' => $newProjects,
                    'url' => '/index.php?page=admin_moderation_projects&status=pending'
                ];
            }

            // New pending comments
            $stmt = $connection->query("SELECT COUNT(*) FROM comments WHERE status = 'pending' AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)");
            $newComments = (int)$stmt->fetchColumn();
            
            if ($newComments > 0) {
                $notifications[] = [
                    'type' => 'new_comments',
                    'message' => "$newComments new comment(s) waiting for moderation",
                    'count' => $newComments,
                    'url' => '/index.php?page=admin_moderation_comments&status=pending'
                ];
            }

            return $notifications;

        } catch (Exception $e) {
            $this->logger->error("Error getting moderator notifications: " . $e->getMessage());
            return [];
        }
    }
}
