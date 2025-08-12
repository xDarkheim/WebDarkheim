<?php

/**
 * Middleware for role-based access control
 *
 * @author GitHub Copilot
 */
declare(strict_types=1);

namespace App\Application\Middleware;

use App\Domain\Interfaces\DatabaseInterface;
use App\Domain\Models\User;
use App\Domain\Models\Role;
use App\Domain\Models\Permission;

class RoleMiddleware {
    private DatabaseInterface $db_handler;

    public function __construct(DatabaseInterface $db_handler) {
        $this->db_handler = $db_handler;
    }

    public function requireRole(array $allowedRoles): bool {
        if (!isset($_SESSION['user_id'])) {
            $this->redirectToLogin();
            return false;
        }

        $userData = User::findById($this->db_handler, (int)$_SESSION['user_id']);

        if (!$userData) {
            $this->redirectToLogin();
            return false;
        }

        if (!in_array($userData['role'], $allowedRoles)) {
            $this->redirectToAccessDenied();
            return false;
        }

        return true;
    }

    public function requirePermission(string $resource, string $action): bool {
        if (!isset($_SESSION['user_id'])) {
            $this->redirectToLogin();
            return false;
        }

        $userData = User::findById($this->db_handler, (int)$_SESSION['user_id']);

        if (!$userData) {
            $this->redirectToLogin();
            return false;
        }

        // Admin always has access
        if ($userData['role'] === 'admin') {
            return true;
        }

        // Check if user's role has the required permission
        if ($this->userHasPermission((int)$_SESSION['user_id'], $resource, $action)) {
            return true;
        }

        $this->redirectToAccessDenied();
        return false;
    }

    public function requireMinimumRole(string $minimumRole): bool {
        $roleHierarchy = ['guest', 'client', 'employee', 'admin'];
        $requiredLevel = array_search($minimumRole, $roleHierarchy);

        if ($requiredLevel === false) {
            return false;
        }

        if (!isset($_SESSION['user_id'])) {
            $this->redirectToLogin();
            return false;
        }

        $userData = User::findById($this->db_handler, (int)$_SESSION['user_id']);

        if (!$userData) {
            $this->redirectToLogin();
            return false;
        }

        $userLevel = array_search($userData['role'], $roleHierarchy);

        if ($userLevel === false || $userLevel < $requiredLevel) {
            $this->redirectToAccessDenied();
            return false;
        }

        return true;
    }

    private function userHasPermission(int $userId, string $resource, string $action): bool {
        $conn = $this->db_handler->getConnection();

        // Check through user roles and role permissions
        $stmt = $conn->prepare("
            SELECT COUNT(*) FROM users u
            INNER JOIN user_roles ur ON u.id = ur.user_id
            INNER JOIN roles r ON ur.role_id = r.id
            INNER JOIN role_permissions rp ON r.id = rp.role_id
            INNER JOIN permissions p ON rp.permission_id = p.id
            WHERE u.id = ? AND p.resource = ? AND p.action = ?
        ");

        $stmt->execute([$userId, $resource, $action]);
        $count = $stmt->fetchColumn();

        if ($count > 0) {
            return true;
        }

        // Also check basic role permissions (for backward compatibility)
        $userData = User::findById($this->db_handler, $userId);
        if (!$userData) {
            return false;
        }

        $basicPermissions = [
            'admin' => ['content' => ['create', 'edit', 'delete', 'publish', 'moderate'], 'users' => ['create', 'edit', 'delete', 'view'], 'portfolio' => ['create', 'edit', 'delete', 'moderate'], 'comments' => ['create', 'moderate', 'delete'], 'admin' => ['view'], 'settings' => ['edit'], 'backups' => ['create', 'download']],
            'employee' => ['content' => ['create', 'edit', 'moderate'], 'portfolio' => ['moderate'], 'comments' => ['moderate']],
            'client' => ['portfolio' => ['create', 'edit'], 'comments' => ['create']],
            'guest' => []
        ];

        $userRole = $userData['role'];

        return isset($basicPermissions[$userRole][$resource]) &&
               in_array($action, $basicPermissions[$userRole][$resource]);
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
