<?php

/**
 * Middleware to protect client area access
 *
 * @author GitHub Copilot
 */
declare(strict_types=1);

namespace App\Application\Middleware;

use App\Domain\Interfaces\DatabaseInterface;
use App\Domain\Models\User;

class ClientAreaMiddleware {
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

        // Check if user has access to client area (client, employee, or admin)
        $allowedRoles = ['client', 'employee', 'admin'];
        if (!in_array($userData['role'], $allowedRoles)) {
            $this->redirectToAccessDenied();
            return false;
        }

        // Check if account is active
        if (!$userData['is_active']) {
            $this->redirectToInactiveAccount();
            return false;
        }

        return true;
    }

    public function requireOwnResourceOrAdmin(int $resourceUserId): bool {
        if (!$this->handle()) {
            return false;
        }

        $userData = User::findById($this->db_handler, (int)$_SESSION['user_id']);
        
        // Admin can access any resource
        if ($userData['role'] === 'admin') {
            return true;
        }

        // User can only access their own resources
        if ((int)$_SESSION['user_id'] !== $resourceUserId) {
            $this->redirectToAccessDenied();
            return false;
        }

        return true;
    }

    public function requireClientOrHigher(): bool {
        if (!isset($_SESSION['user_id'])) {
            $this->redirectToLogin();
            return false;
        }

        $userData = User::findById($this->db_handler, (int)$_SESSION['user_id']);
        
        if (!$userData) {
            $this->redirectToLogin();
            return false;
        }

        // Check role hierarchy: guest < client < employee < admin
        $roleHierarchy = ['guest' => 0, 'client' => 1, 'employee' => 2, 'admin' => 3];
        $userRoleLevel = $roleHierarchy[$userData['role']] ?? 0;
        $requiredLevel = $roleHierarchy['client'];

        if ($userRoleLevel < $requiredLevel) {
            $this->redirectToAccessDenied();
            return false;
        }

        return true;
    }

    private function redirectToLogin(): void {
        $_SESSION['error_message'] = 'Пожалуйста, войдите в систему для доступа к клиентскому порталу.';
        header('Location: /login');
        exit;
    }

    private function redirectToAccessDenied(): void {
        $_SESSION['error_message'] = 'У вас нет прав доступа к этому разделу.';
        header('Location: /');
        exit;
    }

    private function redirectToInactiveAccount(): void {
        $_SESSION['error_message'] = 'Ваш аккаунт неактивен. Обратитесь к администратору.';
        header('Location: /login');
        exit;
    }
}
