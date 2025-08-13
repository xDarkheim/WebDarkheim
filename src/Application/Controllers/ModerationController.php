<?php

/**
 * Moderation Controller
 * Handles all moderation-related requests for admin panel
 *
 * @author GitHub Copilot
 */

declare(strict_types=1);

namespace App\Application\Controllers;

use App\Domain\Interfaces\DatabaseInterface;
use App\Domain\Models\ClientProject;
use App\Domain\Models\Comments;
use App\Application\Services\ModerationService;
use App\Infrastructure\Lib\FlashMessageService;
use App\Application\Services\AuthenticationService;
use App\Domain\Interfaces\LoggerInterface;
use Exception;

class ModerationController
{
    private DatabaseInterface $database;
    private AuthenticationService $auth;
    private FlashMessageService $flashMessage;
    private LoggerInterface $logger;
    private ModerationService $moderationService;

    public function __construct(
        DatabaseInterface $database,
        AuthenticationService $auth,
        FlashMessageService $flashMessage,
        LoggerInterface $logger
    ) {
        $this->database = $database;
        $this->auth = $auth;
        $this->flashMessage = $flashMessage;
        $this->logger = $logger;

        // Initialize moderation service
        $this->moderationService = new ModerationService($database, $logger);
    }

    /**
     * Handle projects moderation page
     */
    public function handleProjectsModeration(): array
    {
        try {
            // Check admin permissions
            $this->checkAdminAccess();

            // Get filter parameters
            $filters = [
                'status' => $_GET['status'] ?? 'pending',
                'page' => max(1, (int)($_GET['page'] ?? 1)),
                'per_page' => 20,
                'search' => trim($_GET['search'] ?? ''),
                'sort' => $_GET['sort'] ?? 'created_desc'
            ];

            // Get projects for moderation
            $projectsData = $this->moderationService->getProjectsForModeration($filters);

            // Get statistics
            $statistics = $this->moderationService->getModerationStatistics();

            $this->logger->info("Projects moderation page loaded", [
                'total_projects' => $projectsData['total'],
                'current_page' => $filters['page'],
                'filters' => $filters
            ]);

            return [
                'projects' => $projectsData['projects'],
                'pagination' => [
                    'current_page' => $projectsData['current_page'],
                    'total_pages' => $projectsData['total_pages'],
                    'total' => $projectsData['total']
                ],
                'statistics' => $statistics,
                'filters' => $filters
            ];

        } catch (Exception $e) {
            $this->logger->error("Error in projects moderation: " . $e->getMessage());
            $this->flashMessage->addError('Error loading projects: ' . $e->getMessage());

            return [
                'projects' => [],
                'pagination' => ['current_page' => 1, 'total_pages' => 1, 'total' => 0],
                'statistics' => [],
                'filters' => []
            ];
        }
    }

    /**
     * Handle project details moderation page
     */
    public function handleProjectDetails(int $projectId): array
    {
        try {
            // Check admin permissions
            $this->checkAdminAccess();

            // Get project details
            $project = $this->moderationService->getProjectForModeration($projectId);

            if (!$project) {
                throw new Exception('Project not found');
            }

            // Get project comments
            $commentsModel = new Comments($this->database);
            $comments = $commentsModel->getCommentsByItem('portfolio_project', $projectId, true);

            // Get moderation history
            $history = $this->moderationService->getProjectModerationHistory($projectId);

            $this->logger->info("Project details loaded for moderation", [
                'project_id' => $projectId,
                'project_title' => $project['title'],
                'comments_count' => count($comments)
            ]);

            return [
                'project' => $project,
                'comments' => $comments,
                'history' => $history
            ];

        } catch (Exception $e) {
            $this->logger->error("Error loading project details: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Handle moderation dashboard
     */
    public function handleModerationDashboard(): array
    {
        try {
            // Check admin permissions
            $this->checkAdminAccess();

            // Get dashboard statistics
            $statistics = $this->moderationService->getDashboardStatistics();

            // Get recent projects needing moderation
            $recentProjects = $this->moderationService->getRecentProjectsForModeration(5);

            // Get recent comments needing moderation
            $commentsModel = new Comments($this->database);
            $recentComments = $commentsModel->getPendingComments(5);

            // Get notifications for moderator
            $notifications = $this->moderationService->getModeratorNotifications();

            $this->logger->info("Moderation dashboard loaded", [
                'pending_projects' => $statistics['pending_projects'] ?? 0,
                'pending_comments' => $statistics['pending_comments'] ?? 0,
                'notifications' => count($notifications)
            ]);

            return [
                'statistics' => $statistics,
                'recent_projects' => $recentProjects,
                'recent_comments' => $recentComments,
                'notifications' => $notifications
            ];

        } catch (Exception $e) {
            $this->logger->error("Error loading moderation dashboard: " . $e->getMessage());
            $this->flashMessage->addError('Error loading dashboard: ' . $e->getMessage());

            return [
                'statistics' => [],
                'recent_projects' => [],
                'recent_comments' => [],
                'notifications' => []
            ];
        }
    }

    /**
     * Handle comments moderation page
     */
    public function handleCommentsModeration(): array
    {
        try {
            // Check admin permissions
            $this->checkAdminAccess();

            // Get filter parameters
            $filters = [
                'status' => $_GET['status'] ?? 'pending',
                'page' => max(1, (int)($_GET['page'] ?? 1)),
                'per_page' => 20,
                'type' => $_GET['type'] ?? 'all', // all, article, portfolio_project
                'search' => trim($_GET['search'] ?? '')
            ];

            // Get comments for moderation
            $commentsData = $this->moderationService->getCommentsForModeration($filters);

            // Get comments statistics
            $statistics = $this->moderationService->getCommentsStatistics();

            $this->logger->info("Comments moderation page loaded", [
                'total_comments' => $commentsData['total'],
                'current_page' => $filters['page'],
                'filters' => $filters
            ]);

            return [
                'comments' => $commentsData['comments'],
                'pagination' => [
                    'current_page' => $commentsData['current_page'],
                    'total_pages' => $commentsData['total_pages'],
                    'total' => $commentsData['total']
                ],
                'statistics' => $statistics,
                'filters' => $filters
            ];

        } catch (Exception $e) {
            $this->logger->error("Error in comments moderation: " . $e->getMessage());
            $this->flashMessage->addError('Error loading comments: ' . $e->getMessage());

            return [
                'comments' => [],
                'pagination' => ['current_page' => 1, 'total_pages' => 1, 'total' => 0],
                'statistics' => [],
                'filters' => []
            ];
        }
    }

    /**
     * Get flash messages
     */
    public function getFlashMessages(): array
    {
        return $this->flashMessage->getMessages();
    }

    /**
     * Check if user has admin access
     */
    private function checkAdminAccess(): void
    {
        if (!$this->auth->isAuthenticated()) {
            throw new Exception('Authentication required');
        }

        $user = $this->auth->getCurrentUser();
        if (!$user || !in_array($user['role'] ?? '', ['admin', 'employee'])) {
            throw new Exception('Admin access required');
        }
    }
}
