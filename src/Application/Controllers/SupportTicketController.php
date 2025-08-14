<?php

/**
 * SupportTicketController for managing support tickets
 * Following the project's architecture patterns
 *
 * @author Dmytro Hovenko
 */
declare(strict_types=1);

namespace App\Application\Controllers;

use App\Domain\Interfaces\DatabaseInterface;
use App\Domain\Models\Ticket;
use App\Domain\Models\TicketMessage;
use App\Application\Middleware\ClientAreaMiddleware;
use Exception;

class SupportTicketController {
    private DatabaseInterface $db_handler;
    private ClientAreaMiddleware $middleware;

    public function __construct(DatabaseInterface $db_handler) {
        $this->db_handler = $db_handler;
        $this->middleware = new ClientAreaMiddleware($db_handler);
    }

    /**
     * API endpoint to create a new ticket
     */
    public function createTicket(): array {
        if (!$this->middleware->handle()) {
            return ['success' => false, 'error' => 'Access denied'];
        }

        try {
            // Validate input
            $subject = trim($_POST['subject'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $priority = $_POST['priority'] ?? 'medium';
            $category = $_POST['category'] ?? 'general';

            if (empty($subject)) {
                return ['success' => false, 'error' => 'Subject is required'];
            }

            if (empty($description)) {
                return ['success' => false, 'error' => 'Description is required'];
            }

            // Create new ticket
            $ticket = new Ticket($this->db_handler);
            $ticket->setUserId((int)$_SESSION['user_id'])
                   ->setSubject($subject)
                   ->setDescription($description)
                   ->setPriority($priority)
                   ->setCategory($category);

            if ($ticket->save()) {
                // Create initial message
                $message = new TicketMessage($this->db_handler);
                $message->setTicketId($ticket->getId())
                        ->setUserId((int)$_SESSION['user_id'])
                        ->setMessage($description);
                $message->save();

                return [
                    'success' => true,
                    'message' => 'Ticket created successfully',
                    'ticket_id' => $ticket->getId()
                ];
            }

            return ['success' => false, 'error' => 'Failed to create ticket'];

        } catch (Exception $e) {
            error_log("Error creating ticket: " . $e->getMessage());
            return ['success' => false, 'error' => 'Internal error'];
        }
    }

    /**
     * API endpoint to reply to a ticket
     */
    public function replyToTicket(): array {
        if (!$this->middleware->handle()) {
            return ['success' => false, 'error' => 'Access denied'];
        }

        try {
            $ticketId = (int)($_POST['ticket_id'] ?? 0);
            $messageText = trim($_POST['message'] ?? '');

            if (!$ticketId || empty($messageText)) {
                return ['success' => false, 'error' => 'Missing required fields'];
            }

            // Verify ticket access
            $ticketData = Ticket::findById($this->db_handler, $ticketId);
            if (!$ticketData || $ticketData['user_id'] != (int)$_SESSION['user_id']) {
                return ['success' => false, 'error' => 'Access denied'];
            }

            // Create message
            $message = new TicketMessage($this->db_handler);
            $message->setTicketId($ticketId)
                    ->setUserId((int)$_SESSION['user_id'])
                    ->setMessage($messageText);

            if ($message->save()) {
                // Update ticket status if needed
                if ($ticketData['status'] === 'waiting_client') {
                    $ticket = new Ticket($this->db_handler);
                    $ticket->loadById($ticketId);
                    $ticket->setStatus('open');
                    $ticket->save();
                }

                return ['success' => true, 'message' => 'Reply sent successfully'];
            }

            return ['success' => false, 'error' => 'Failed to send reply'];

        } catch (Exception $e) {
            error_log("Error replying to ticket: " . $e->getMessage());
            return ['success' => false, 'error' => 'Internal error'];
        }
    }

    /**
     * API endpoint to update ticket (for staff only)
     */
    public function updateTicket(): array {
        // Check if user is staff
        $userRole = $_SESSION['user']['role'] ?? '';
        if (!in_array($userRole, ['admin', 'employee'])) {
            return ['success' => false, 'error' => 'Access denied'];
        }

        try {
            $ticketId = (int)($_POST['ticket_id'] ?? 0);
            $status = $_POST['status'] ?? '';
            $priority = $_POST['priority'] ?? '';
            $assignedTo = $_POST['assigned_to'] ?? null;

            if (!$ticketId) {
                return ['success' => false, 'error' => 'Ticket ID required'];
            }

            $ticketData = Ticket::findById($this->db_handler, $ticketId);
            if (!$ticketData) {
                return ['success' => false, 'error' => 'Ticket not found'];
            }

            // Create a ticket object and load the data
            $ticket = new Ticket($this->db_handler);
            $ticket->loadById($ticketId);

            // Update fields
            if (!empty($status)) {
                $ticket->setStatus($status);
            }
            if (!empty($priority)) {
                $ticket->setPriority($priority);
            }
            if ($assignedTo !== null) {
                $ticket->setAssignedTo($assignedTo ? (int)$assignedTo : null);
            }

            if ($ticket->save()) {
                return ['success' => true, 'message' => 'Ticket updated successfully'];
            }

            return ['success' => false, 'error' => 'Failed to update ticket'];

        } catch (Exception $e) {
            error_log("Error updating ticket: " . $e->getMessage());
            return ['success' => false, 'error' => 'Internal error'];
        }
    }
}
