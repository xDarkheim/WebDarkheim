<?php

/**
 * Middleware to restrict access to admin users only
 *
 * @author Dmytro Hovenko
 */

declare(strict_types=1);

namespace App\Application\Middleware;

use App\Domain\Interfaces\DatabaseInterface;
use App\Domain\Models\User;

class AdminOnlyMiddleware {
    private DatabaseInterface $db_handler;

    public function __construct(DatabaseInterface $db_handler) {
        $this->db_handler = $db_handler;
    }

    public function handle(): bool {
        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            $this->redirectToLogin();
            return false;
        }

        // Get user data
        $userData = User::findById($this->db_handler, (int)$_SESSION['user_id']);
        
        if (!$userData) {
            $this->redirectToLogin();
            return false;
        }

        // Check if user is admin
        if ($userData['role'] !== 'admin') {
            $this->redirectToAccessDenied();
            return false;
        }

        return true;
    }

    private function redirectToLogin(): void {
        $_SESSION['error_message'] = 'Пожалуйста, войдите в систему для доступа к этой странице.';
        header('Location: /login');
        exit;
    }

    private function redirectToAccessDenied(): void {
        $_SESSION['error_message'] = 'У вас нет прав доступа к этой странице.';
        header('Location: /dashboard');
        exit;
    }
}
