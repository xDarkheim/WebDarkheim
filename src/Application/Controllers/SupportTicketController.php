<?php

declare(strict_types=1);

namespace App\Application\Controllers;

use App\Domain\Models\SupportTicket;
use App\Domain\Interfaces\DatabaseInterface;
use App\Domain\Interfaces\LoggerInterface;
use App\Application\Services\AuthenticationService;
use App\Infrastructure\Lib\FlashMessageService;
use Exception;
use InvalidArgumentException;

/**
 * SupportTicketController
 * 
 * Handles all support ticket operations for the client portal.
 * Manages ticket creation, status updates, messaging and moderation.
 *
 * @author GitHub Copilot
 */
class SupportTicketController
{
    private DatabaseInterface $db;
    private AuthenticationService $authService;
    private FlashMessageService $flashService;
    private LoggerInterface $logger;

    public function __construct(
        DatabaseInterface $db,
        AuthenticationService $authService,
        FlashMessageService $flashService,
        LoggerInterface $logger
    ) {
        $this->db = $db;
        $this->authService = $authService;
        $this->flashService = $flashService;
        $this->logger = $logger;
    }

    /**
     * Handle ticket creation
     */
    public function createTicket(): array
    {
        try {
            // Check authentication
            if (!$this->authService->isAuthenticated()) {
                throw new Exception("Authentication required");
            }

            $currentUser = $this->authService->getCurrentUser();
            
            // Only clients and above can create tickets
            if (!in_array($currentUser['role'], ['client', 'employee', 'admin'])) {
                throw new Exception("Insufficient permissions");
            }

            // Validate input
            $data = $this->validateTicketInput($_POST);
            $data['client_id'] = $currentUser['id'];
            
            // Create ticket
            $ticket = new SupportTicket($this->db);
            $success = $ticket->create($data);
            
            if ($success) {
                $this->logger->info("Support ticket created", [
                    'ticket_id' => $ticket->id,
                    'client_id' => $currentUser['id'],
                    'subject' => $data['subject']
                ]);
                
                return [
                    'success' => true,
                    'message' => 'Support ticket created successfully',
                    'ticket_id' => $ticket->id,
                    'ticket' => $this->formatTicketForResponse($ticket)
                ];
            }
            
            throw new Exception("Failed to create ticket");
            
        } catch (Exception $e) {
            $this->logger->error("Ticket creation failed", [
                'error' => $e->getMessage(),
                'user_id' => $currentUser['id'] ?? null
            ]);
            
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Get tickets for client dashboard
     */
    public function getClientTickets(): array
    {
        try {
            if (!$this->authService->isAuthenticated()) {
                throw new Exception("Authentication required");
            }

            $currentUser = $this->authService->getCurrentUser();
            $status = $_GET['status'] ?? null;
            $limit = min((int)($_GET['limit'] ?? 10), 50);
            $offset = max((int)($_GET['offset'] ?? 0), 0);
            
            $ticket = new SupportTicket($this->db);
            
            // Admin/employees can see all tickets, clients only their own
            if (in_array($currentUser['role'], ['admin', 'employee'])) {
                $tickets = $ticket->getTicketsForModeration($status, $limit, $offset);
            } else {
                $tickets = $ticket->getClientTickets($currentUser['id'], $status, $limit, $offset);
            }
            
            return [
                'success' => true,
                'tickets' => array_map([$this, 'formatTicketForResponse'], $tickets),
                'pagination' => [
                    'limit' => $limit,
                    'offset' => $offset,
                    'has_more' => count($tickets) === $limit
                ]
            ];
            
        } catch (Exception $e) {
            $this->logger->error("Failed to get client tickets", [
                'error' => $e->getMessage(),
                'user_id' => $currentUser['id'] ?? null
            ]);
            
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Get ticket details with messages
     */
    public function getTicketDetails(): array
    {
        try {
            if (!$this->authService->isAuthenticated()) {
                throw new Exception("Authentication required");
            }

            $currentUser = $this->authService->getCurrentUser();
            $ticketId = (int)($_GET['id'] ?? 0);
            
            if (!$ticketId) {
                throw new InvalidArgumentException("Ticket ID is required");
            }
            
            $ticket = new SupportTicket($this->db);
            $ticketData = $ticket->findById($ticketId);
            
            if (!$ticketData) {
                throw new Exception("Ticket not found");
            }
            
            // Check access rights
            if (!$this->canAccessTicket($ticketData, $currentUser)) {
                throw new Exception("Access denied");
            }
            
            // Get messages (include internal for admin/employee)
            $includeInternal = in_array($currentUser['role'], ['admin', 'employee']);
            $messages = $ticketData->getMessages($includeInternal);
            
            return [
                'success' => true,
                'ticket' => $this->formatTicketForResponse($ticketData),
                'messages' => $this->formatMessagesForResponse($messages),
                'can_edit' => $ticketData->canBeEditedBy($currentUser['id'], $currentUser['role'])
            ];
            
        } catch (Exception $e) {
            $this->logger->error("Failed to get ticket details", [
                'error' => $e->getMessage(),
                'ticket_id' => $ticketId ?? null,
                'user_id' => $currentUser['id'] ?? null
            ]);
            
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Add message to ticket
     */
    public function addMessage(): array
    {
        try {
            if (!$this->authService->isAuthenticated()) {
                throw new Exception("Authentication required");
            }

            $currentUser = $this->authService->getCurrentUser();
            $ticketId = (int)($_POST['ticket_id'] ?? 0);
            $message = trim($_POST['message'] ?? '');
            $isInternal = (bool)($_POST['is_internal'] ?? false);
            
            if (!$ticketId) {
                throw new InvalidArgumentException("Ticket ID is required");
            }
            
            if (strlen($message) < 3) {
                throw new InvalidArgumentException("Message must be at least 3 characters long");
            }
            
            $ticket = new SupportTicket($this->db);
            $ticketData = $ticket->findById($ticketId);
            
            if (!$ticketData) {
                throw new Exception("Ticket not found");
            }
            
            // Check access rights
            if (!$this->canAccessTicket($ticketData, $currentUser)) {
                throw new Exception("Access denied");
            }
            
            // Only admin/employee can create internal messages
            if ($isInternal && !in_array($currentUser['role'], ['admin', 'employee'])) {
                $isInternal = false;
            }
            
            $success = $ticketData->addMessage($currentUser['id'], $message, $isInternal);
            
            if ($success) {
                // Auto-update ticket status based on who replied
                if ($currentUser['role'] === 'client' && $ticketData->status === 'waiting_client') {
                    $ticketData->updateStatus('in_progress');
                } elseif (in_array($currentUser['role'], ['admin', 'employee']) && $ticketData->status === 'open') {
                    $ticketData->updateStatus('in_progress');
                }
                
                $this->logger->info("Message added to ticket", [
                    'ticket_id' => $ticketId,
                    'user_id' => $currentUser['id'],
                    'is_internal' => $isInternal
                ]);
                
                return [
                    'success' => true,
                    'message' => 'Message added successfully'
                ];
            }
            
            throw new Exception("Failed to add message");
            
        } catch (Exception $e) {
            $this->logger->error("Failed to add message", [
                'error' => $e->getMessage(),
                'ticket_id' => $ticketId ?? null,
                'user_id' => $currentUser['id'] ?? null
            ]);
            
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Update ticket status (admin/employee only)
     */
    public function updateTicketStatus(): array
    {
        try {
            if (!$this->authService->isAuthenticated()) {
                throw new Exception("Authentication required");
            }

            $currentUser = $this->authService->getCurrentUser();
            
            // Only admin/employee can update status
            if (!in_array($currentUser['role'], ['admin', 'employee'])) {
                throw new Exception("Insufficient permissions");
            }
            
            $ticketId = (int)($_POST['ticket_id'] ?? 0);
            $status = $_POST['status'] ?? '';
            $assignedTo = !empty($_POST['assigned_to']) ? (int)$_POST['assigned_to'] : null;
            
            if (!$ticketId) {
                throw new InvalidArgumentException("Ticket ID is required");
            }
            
            $ticket = new SupportTicket($this->db);
            $ticketData = $ticket->findById($ticketId);
            
            if (!$ticketData) {
                throw new Exception("Ticket not found");
            }
            
            $success = $ticketData->updateStatus($status, $assignedTo);
            
            if ($success) {
                $this->logger->info("Ticket status updated", [
                    'ticket_id' => $ticketId,
                    'new_status' => $status,
                    'assigned_to' => $assignedTo,
                    'updated_by' => $currentUser['id']
                ]);
                
                return [
                    'success' => true,
                    'message' => 'Ticket status updated successfully'
                ];
            }
            
            throw new Exception("Failed to update ticket status");
            
        } catch (Exception $e) {
            $this->logger->error("Failed to update ticket status", [
                'error' => $e->getMessage(),
                'ticket_id' => $ticketId ?? null,
                'user_id' => $currentUser['id'] ?? null
            ]);
            
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Get ticket statistics
     */
    public function getStatistics(): array
    {
        try {
            if (!$this->authService->isAuthenticated()) {
                throw new Exception("Authentication required");
            }

            $currentUser = $this->authService->getCurrentUser();
            
            // Only admin/employee can view global statistics
            if (!in_array($currentUser['role'], ['admin', 'employee'])) {
                throw new Exception("Insufficient permissions");
            }
            
            $stats = SupportTicket::getStatistics($this->db);
            
            return [
                'success' => true,
                'statistics' => $stats
            ];
            
        } catch (Exception $e) {
            $this->logger->error("Failed to get ticket statistics", [
                'error' => $e->getMessage(),
                'user_id' => $currentUser['id'] ?? null
            ]);
            
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Get flash messages
     */
    public function getFlashMessages(): array
    {
        return $this->flashService->getAllMessages();
    }

    /**
     * Validate ticket input data
     */
    private function validateTicketInput(array $data): array
    {
        $validated = [];
        
        // Subject validation
        $subject = trim($data['subject'] ?? '');
        if (strlen($subject) < 5 || strlen($subject) > 255) {
            throw new InvalidArgumentException("Subject must be between 5 and 255 characters");
        }
        $validated['subject'] = htmlspecialchars($subject, ENT_QUOTES, 'UTF-8');
        
        // Description validation
        $description = trim($data['description'] ?? '');
        if (strlen($description) < 10) {
            throw new InvalidArgumentException("Description must be at least 10 characters long");
        }
        $validated['description'] = htmlspecialchars($description, ENT_QUOTES, 'UTF-8');
        
        // Priority validation
        $priority = $data['priority'] ?? 'medium';
        $validPriorities = ['low', 'medium', 'high', 'critical'];
        if (!in_array($priority, $validPriorities)) {
            $priority = 'medium';
        }
        $validated['priority'] = $priority;
        
        // Category validation
        $category = trim($data['category'] ?? '');
        if (!empty($category)) {
            $validated['category'] = htmlspecialchars($category, ENT_QUOTES, 'UTF-8');
        }
        
        return $validated;
    }

    /**
     * Check if user can access ticket
     */
    private function canAccessTicket(SupportTicket $ticket, array $user): bool
    {
        // Admin/employee can access any ticket
        if (in_array($user['role'], ['admin', 'employee'])) {
            return true;
        }
        
        // Client can only access their own tickets
        return $ticket->client_id === $user['id'];
    }

    /**
     * Format ticket for API response
     */
    private function formatTicketForResponse(SupportTicket $ticket): array
    {
        return [
            'id' => $ticket->id,
            'subject' => $ticket->subject,
            'description' => $ticket->description,
            'priority' => $ticket->priority,
            'status' => $ticket->status,
            'category' => $ticket->category,
            'client_id' => $ticket->client_id,
            'assigned_to' => $ticket->assigned_to,
            'created_at' => $ticket->created_at,
            'updated_at' => $ticket->updated_at,
            'priority_badge_class' => $ticket->getPriorityBadgeClass(),
            'status_badge_class' => $ticket->getStatusBadgeClass()
        ];
    }

    /**
     * Format messages for API response
     */
    private function formatMessagesForResponse(array $messages): array
    {
        return array_map(function($message) {
            return [
                'id' => $message['id'],
                'user_id' => $message['user_id'],
                'username' => $message['username'],
                'role' => $message['role'],
                'message' => $message['message'],
                'is_internal' => (bool)$message['is_internal'],
                'attachments' => $message['attachments'] ? json_decode($message['attachments'], true) : null,
                'created_at' => $message['created_at']
            ];
        }, $messages);
    }
}
