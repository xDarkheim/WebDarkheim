# Security & Middleware Guide - Darkheim WebEngine

## Overview

This guide covers security implementation, middleware patterns, and authentication/authorization mechanisms used in the Darkheim WebEngine project.

## Security Architecture

### Authentication Flow

```
User Login → Credentials Validation → Session Creation → CSRF Token → Permission Check → Access Grant
```

### Authorization Layers

1. **Session-based Authentication**: Primary authentication method
2. **Role-based Access Control (RBAC)**: User roles and permissions
3. **Middleware Protection**: Route-level access control
4. **CSRF Protection**: Form submission security
5. **Remember Me Tokens**: Persistent login functionality

## Middleware System

### Available Middleware Classes

```php
// Client area protection
App\Application\Middleware\ClientAreaMiddleware

// Role-based access control
App\Application\Middleware\RoleMiddleware

// Admin-only access
App\Application\Middleware\AdminMiddleware
```

### Middleware Implementation Pattern

```php
declare(strict_types=1);

namespace App\Application\Middleware;

use App\Domain\Interfaces\DatabaseInterface;

class ExampleMiddleware
{
    private DatabaseInterface $db_handler;

    public function __construct(DatabaseInterface $db_handler)
    {
        $this->db_handler = $db_handler;
    }

    public function handle(): bool
    {
        // Check authentication
        if (!$this->isAuthenticated()) {
            return false;
        }

        // Additional checks
        if (!$this->hasPermission()) {
            return false;
        }

        return true;
    }

    private function isAuthenticated(): bool
    {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }

    private function hasPermission(): bool
    {
        // Permission logic here
        return true;
    }
}
```

### ClientAreaMiddleware Usage

```php
class ClientController
{
    private ClientAreaMiddleware $middleware;

    public function __construct(DatabaseInterface $db_handler)
    {
        $this->middleware = new ClientAreaMiddleware($db_handler);
    }

    public function clientAction(): array
    {
        // Always check first
        if (!$this->middleware->handle()) {
            return ['success' => false, 'error' => 'Access denied'];
        }

        // Client-specific logic here
    }
}
```

### RoleMiddleware Usage

```php
class AdminController
{
    private RoleMiddleware $middleware;

    public function __construct(DatabaseInterface $db_handler)
    {
        $this->middleware = new RoleMiddleware($db_handler);
    }

    public function adminAction(): array
    {
        // Check for admin or employee role
        if (!$this->middleware->requireRole(['admin', 'employee'])) {
            return ['success' => false, 'error' => 'Insufficient permissions'];
        }

        // Admin logic here
    }
}
```

## Authentication Implementation

### Session Management

```php
class AuthenticationService
{
    public function authenticate(string $identifier, string $password): AuthResult
    {
        try {
            // Validate credentials
            $user = $this->validateCredentials($identifier, $password);
            
            if ($user) {
                $this->createSession($user);
                return new AuthResult(true, $user);
            }
            
            return new AuthResult(false, null, 'Invalid credentials');
            
        } catch (Exception $e) {
            error_log("Authentication error: " . $e->getMessage());
            return new AuthResult(false, null, 'Authentication failed');
        }
    }

    private function createSession(array $user): void
    {
        // Regenerate session ID for security
        session_regenerate_id(true);
        
        // Set session variables
        $_SESSION['user_id'] = (int)$user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['role'] = $user['role']; // Compatibility
        $_SESSION['is_admin'] = ($user['role'] === 'admin');
        $_SESSION['user_authenticated'] = true;
        
        // Create CSRF token
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        
        // Set current user info
        $_SESSION['current_user'] = [
            'id' => $user['id'],
            'username' => $user['username'],
            'role' => $user['role']
        ];
    }
}
```

### Password Security

```php
class PasswordManager
{
    private const COST = 12; // bcrypt cost factor

    public function hash(string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => self::COST]);
    }

    public function verify(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    public function needsRehash(string $hash): bool
    {
        return password_needs_rehash($hash, PASSWORD_BCRYPT, ['cost' => self::COST]);
    }

    public function validateStrength(string $password): array
    {
        $errors = [];

        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters long';
        }

        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password must contain at least one uppercase letter';
        }

        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Password must contain at least one lowercase letter';
        }

        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Password must contain at least one number';
        }

        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors[] = 'Password must contain at least one special character';
        }

        return $errors;
    }
}
```

## CSRF Protection

### Token Generation

```php
class CSRFProtection
{
    public static function generateToken(): string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $token = bin2hex(random_bytes(32));
        $_SESSION['csrf_token'] = $token;
        
        return $token;
    }

    public static function validateToken(string $token): bool
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return false;
        }

        $sessionToken = $_SESSION['csrf_token'] ?? '';
        
        if (empty($token) || empty($sessionToken)) {
            return false;
        }

        return hash_equals($sessionToken, $token);
    }

    public static function getTokenField(): string
    {
        $token = self::generateToken();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
    }
}
```

### Form Integration

```html
<!-- In forms -->
<form method="POST" action="/api/action">
    <?php echo CSRFProtection::getTokenField(); ?>
    <!-- Other form fields -->
</form>
```

```php
// In controllers
private function validateCSRF(): bool
{
    $token = $_POST['csrf_token'] ?? '';
    $sessionToken = $_SESSION['csrf_token'] ?? '';

    if (empty($token) || empty($sessionToken)) {
        return false;
    }

    return hash_equals($sessionToken, $token);
}
```

## Remember Me System

### Token Management

```php
class TokenManager
{
    public function createVerificationToken(int $userId, string $type, int $expiryMinutes): string
    {
        try {
            $token = bin2hex(random_bytes(32));
            $expiresAt = date('Y-m-d H:i:s', time() + ($expiryMinutes * 60));
            
            $sql = "INSERT INTO verification_tokens (user_id, token, type, expires_at, created_at) 
                    VALUES (?, ?, ?, ?, NOW())";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId, hash('sha256', $token), $type, $expiresAt]);
            
            return $token;
        } catch (Exception $e) {
            error_log("Error creating verification token: " . $e->getMessage());
            throw $e;
        }
    }

    public function validateToken(string $token, string $type): ?int
    {
        try {
            $hashedToken = hash('sha256', $token);
            
            $sql = "SELECT user_id FROM verification_tokens 
                    WHERE token = ? AND type = ? AND expires_at > NOW() AND used_at IS NULL";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$hashedToken, $type]);
            
            $userId = $stmt->fetchColumn();
            
            if ($userId) {
                // Mark token as used
                $this->markTokenAsUsed($hashedToken);
                return (int)$userId;
            }
            
            return null;
        } catch (Exception $e) {
            error_log("Error validating token: " . $e->getMessage());
            return null;
        }
    }

    public function revokeToken(string $token): bool
    {
        try {
            $hashedToken = hash('sha256', $token);
            
            $sql = "UPDATE verification_tokens SET used_at = NOW() WHERE token = ?";
            $stmt = $this->db->prepare($sql);
            
            return $stmt->execute([$hashedToken]);
        } catch (Exception $e) {
            error_log("Error revoking token: " . $e->getMessage());
            return false;
        }
    }
}
```

### Cookie Security

```php
class SecureCookieManager
{
    public function setSecureCookie(string $name, string $value, int $expires): bool
    {
        $options = [
            'expires' => $expires,
            'path' => '/',
            'domain' => '',
            'secure' => !empty($_SERVER['HTTPS']), // HTTPS only
            'httponly' => true, // No JS access
            'samesite' => 'Strict' // CSRF protection
        ];

        return setcookie($name, $value, $options);
    }

    public function deleteSecureCookie(string $name): bool
    {
        return $this->setSecureCookie($name, '', time() - 3600);
    }
}
```

## Input Validation & Sanitization

### Input Sanitizer

```php
class InputSanitizer
{
    public static function sanitizeString(string $input): string
    {
        return trim(htmlspecialchars($input, ENT_QUOTES, 'UTF-8'));
    }

    public static function sanitizeEmail(string $email): string
    {
        return filter_var(trim($email), FILTER_SANITIZE_EMAIL);
    }

    public static function sanitizeInt(string $input): int
    {
        return (int)filter_var($input, FILTER_SANITIZE_NUMBER_INT);
    }

    public static function sanitizeFloat(string $input): float
    {
        return (float)filter_var($input, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    }

    public static function sanitizeArray(array $input): array
    {
        return array_map(function($item) {
            if (is_string($item)) {
                return self::sanitizeString($item);
            }
            if (is_array($item)) {
                return self::sanitizeArray($item);
            }
            return $item;
        }, $input);
    }
}
```

### Validation Rules

```php
class ValidationRules
{
    public static function validateEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    public static function validateUsername(string $username): array
    {
        $errors = [];

        if (strlen($username) < 3) {
            $errors[] = 'Username must be at least 3 characters long';
        }

        if (strlen($username) > 30) {
            $errors[] = 'Username must not exceed 30 characters';
        }

        if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            $errors[] = 'Username can only contain letters, numbers, and underscores';
        }

        return $errors;
    }

    public static function validateURL(string $url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    public static function validateFileUpload(array $file, array $allowedTypes, int $maxSize): array
    {
        $errors = [];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'File upload failed';
            return $errors;
        }

        if (!in_array($file['type'], $allowedTypes)) {
            $errors[] = 'File type not allowed';
        }

        if ($file['size'] > $maxSize) {
            $errors[] = 'File size exceeds limit';
        }

        // Additional security check
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, $allowedTypes)) {
            $errors[] = 'File content does not match extension';
        }

        return $errors;
    }
}
```

## Rate Limiting

### Simple Rate Limiter

```php
class RateLimiter
{
    private const RATE_LIMIT_ATTEMPTS = 5;
    private const RATE_LIMIT_WINDOW = 300; // 5 minutes

    public function checkRateLimit(string $identifier): bool
    {
        $key = "rate_limit:" . $identifier;
        $attempts = $_SESSION[$key] ?? [];
        $now = time();

        // Clean old attempts
        $attempts = array_filter($attempts, function($timestamp) use ($now) {
            return ($now - $timestamp) < self::RATE_LIMIT_WINDOW;
        });

        // Check if limit exceeded
        if (count($attempts) >= self::RATE_LIMIT_ATTEMPTS) {
            return false;
        }

        // Record this attempt
        $attempts[] = $now;
        $_SESSION[$key] = $attempts;

        return true;
    }

    public function getRemainingAttempts(string $identifier): int
    {
        $key = "rate_limit:" . $identifier;
        $attempts = $_SESSION[$key] ?? [];
        $now = time();

        // Clean old attempts
        $attempts = array_filter($attempts, function($timestamp) use ($now) {
            return ($now - $timestamp) < self::RATE_LIMIT_WINDOW;
        });

        return max(0, self::RATE_LIMIT_ATTEMPTS - count($attempts));
    }

    public function getTimeUntilReset(string $identifier): int
    {
        $key = "rate_limit:" . $identifier;
        $attempts = $_SESSION[$key] ?? [];

        if (empty($attempts)) {
            return 0;
        }

        $oldestAttempt = min($attempts);
        $resetTime = $oldestAttempt + self::RATE_LIMIT_WINDOW;

        return max(0, $resetTime - time());
    }
}
```

## SQL Injection Prevention

### Prepared Statements Pattern

```php
// ✅ SECURE - Always use prepared statements
public function findByEmail(string $email): ?array
{
    $sql = "SELECT * FROM users WHERE email = ?";
    $stmt = $this->db->prepare($sql);
    $stmt->execute([$email]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

// ❌ VULNERABLE - Never use string concatenation
public function findByEmailBad(string $email): ?array
{
    $sql = "SELECT * FROM users WHERE email = '" . $email . "'";
    $stmt = $this->db->query($sql); // DON'T DO THIS
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}
```

### Dynamic Query Building

```php
public function findWithFilters(array $filters): array
{
    $sql = "SELECT * FROM table WHERE 1=1";
    $params = [];

    // Safe dynamic WHERE conditions
    if (!empty($filters['name'])) {
        $sql .= " AND name = ?";
        $params[] = $filters['name'];
    }

    if (!empty($filters['category'])) {
        $sql .= " AND category = ?";
        $params[] = $filters['category'];
    }

    // Safe ORDER BY with whitelist
    $allowedSort = ['name', 'created_at', 'updated_at'];
    if (!empty($filters['sort']) && in_array($filters['sort'], $allowedSort)) {
        $order = ($filters['order'] === 'desc') ? 'DESC' : 'ASC';
        $sql .= " ORDER BY {$filters['sort']} {$order}";
    }

    $stmt = $this->db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
```

## XSS Prevention

### Output Escaping

```php
class OutputSanitizer
{
    public static function escape(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }

    public static function escapeAttribute(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    public static function escapeJS(string $text): string
    {
        return json_encode($text, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
    }

    public static function stripTags(string $text, string $allowedTags = ''): string
    {
        return strip_tags($text, $allowedTags);
    }
}
```

### Content Security Policy

```php
class CSPManager
{
    public static function setSecurityHeaders(): void
    {
        // Content Security Policy
        header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data:;");
        
        // XSS Protection
        header("X-XSS-Protection: 1; mode=block");
        
        // Content Type Options
        header("X-Content-Type-Options: nosniff");
        
        // Frame Options
        header("X-Frame-Options: SAMEORIGIN");
        
        // HTTPS Redirect
        if (!empty($_SERVER['HTTPS'])) {
            header("Strict-Transport-Security: max-age=31536000; includeSubDomains");
        }
        
        // Referrer Policy
        header("Referrer-Policy: strict-origin-when-cross-origin");
    }
}
```

## Session Security

### Secure Session Configuration

```php
class SessionManager
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            // Secure session configuration
            ini_set('session.cookie_httponly', '1');
            ini_set('session.cookie_secure', !empty($_SERVER['HTTPS']) ? '1' : '0');
            ini_set('session.cookie_samesite', 'Strict');
            ini_set('session.use_strict_mode', '1');
            ini_set('session.cookie_lifetime', '0'); // Session only
            
            session_start();
            
            // Regenerate session ID periodically
            if (!isset($_SESSION['last_regeneration'])) {
                self::regenerateId();
            } elseif (time() - $_SESSION['last_regeneration'] > 300) { // 5 minutes
                self::regenerateId();
            }
        }
    }

    public static function regenerateId(): void
    {
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    }

    public static function destroy(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_unset();
            session_destroy();
            
            // Delete session cookie
            $params = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires' => time() - 3600,
                'path' => $params['path'],
                'domain' => $params['domain'],
                'secure' => $params['secure'],
                'httponly' => $params['httponly'],
                'samesite' => $params['samesite']
            ]);
        }
    }
}
```

## Security Checklist for AI Developers

### ✅ Always Do:
1. Use prepared statements for all database queries
2. Validate and sanitize all input data
3. Implement CSRF protection on all forms
4. Use proper session management
5. Escape output when displaying user data
6. Check permissions before executing actions
7. Log security-related events
8. Use HTTPS in production
9. Implement rate limiting for sensitive operations
10. Follow the middleware pattern for access control

### ❌ Never Do:
1. Build SQL queries with string concatenation
2. Trust user input without validation
3. Skip authentication/authorization checks
4. Store passwords in plain text
5. Use predictable session IDs
6. Allow file uploads without validation
7. Expose sensitive information in error messages
8. Use outdated cryptographic functions
9. Skip CSRF tokens on forms
10. Allow unlimited login attempts

This security and middleware guide ensures that all applications built on the Darkheim WebEngine maintain the highest security standards while providing flexible access control mechanisms.
