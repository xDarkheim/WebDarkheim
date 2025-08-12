<?php

/**
 * Security Middleware for CSRF protection and rate limiting
 *
 * @author Dmytro Hovenko
 */

namespace App\Infrastructure\Lib;

use App\Domain\Interfaces\LoggerInterface;
use App\Domain\Interfaces\TokenManagerInterface;
use App\Application\Middleware\MiddlewareInterface;

class SecurityMiddleware implements MiddlewareInterface
{
    private LoggerInterface $logger;
    private array $rateLimits = [];
    private ?TokenManagerInterface $tokenManager;
    private bool $csrfEnabled;

    public function __construct(
        LoggerInterface $logger,
        ?TokenManagerInterface $tokenManager = null,
        bool $csrfEnabled = false  // Temporarily disabled for diagnostics
    ) {
        $this->logger = $logger;
        $this->tokenManager = $tokenManager;
        $this->csrfEnabled = $csrfEnabled;
    }

    public function handle(): bool
    {
        // Get request data from global variables
        $request = [
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'GET'
        ];

        // Rate limiting check
        if ($this->isRateLimited()) {
            $this->logger->warning('Rate limit exceeded', [
                'ip' => $request['ip'],
                'uri' => $request['uri']
            ]);
            
            http_response_code(429);
            return false; // Stop execution
        }

        // CSRF protection for POST requests
        if ($this->csrfEnabled && $request['method'] === 'POST') {
            if (!$this->validateCsrfToken()) {
                $this->logger->warning('CSRF token validation failed', [
                    'ip' => $request['ip'],
                    'uri' => $request['uri']
                ]);

                http_response_code(403);
                return false; // Stop execution
            }
        }

        // XSS and SQL injection basic protection
        if (!$this->validateInput($request)) {
            $this->logger->warning('Malicious input detected', [
                'ip' => $request['ip'],
                'uri' => $request['uri']
            ]);

            http_response_code(400);
            return false; // Stop execution
        }

        return true; // Continue execution
    }

    private function isRateLimited(): bool
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $currentTime = time();
        $timeWindow = 60; // 1 minute
        $maxRequests = 60; // Max requests per minute

        if (!isset($this->rateLimits[$ip])) {
            $this->rateLimits[$ip] = [];
        }

        // Clean old requests
        $this->rateLimits[$ip] = array_filter(
            $this->rateLimits[$ip],
            fn($timestamp) => $currentTime - $timestamp < $timeWindow
        );

        // Add current request
        $this->rateLimits[$ip][] = $currentTime;

        return count($this->rateLimits[$ip]) > $maxRequests;
    }

    private function validateCsrfToken(): bool
    {
        // If TokenManager is available, use the new system
        if ($this->tokenManager) {
            // Check various possible CSRF token names
            $possibleTokenNames = [
                'csrf_token',
                'csrf_token_edit_profile_info',
                'csrf_token_change_password',
                // Add other possible token names
            ];
            
            $this->logger->debug('SecurityMiddleware: Checking CSRF tokens', [
                'available_post_keys' => array_keys($_POST),
                'possible_token_names' => $possibleTokenNames,
                'request_uri' => $_SERVER['REQUEST_URI'] ?? 'unknown'
            ]);
            
            foreach ($possibleTokenNames as $tokenName) {
                if (isset($_POST[$tokenName])) {
                    $token = $_POST[$tokenName];
                    
                    // Try to determine action from a token name
                    $action = 'default';
                    if (str_contains($tokenName, 'edit_profile_info')) {
                        $action = 'edit_profile_info';
                    } elseif (str_contains($tokenName, 'change_password')) {
                        $action = 'change_password';
                    } elseif (str_contains($tokenName, 'create_article')) {
                        $action = 'create_article';
                    } elseif (str_contains($tokenName, 'login')) {
                        $action = 'login';
                    } elseif (str_contains($tokenName, 'register')) {
                        $action = 'register';
                    } elseif (str_contains($tokenName, 'forgot_password')) {
                        $action = 'forgot_password';
                    } elseif (str_contains($tokenName, 'reset_password')) {
                        $action = 'reset_password';
                    } elseif (str_contains($tokenName, 'resend_verification')) {
                        $action = 'resend_verification';
                    } else {
                        // Try to determine action from URI
                        $uri = $_SERVER['REQUEST_URI'] ?? '';
                        if (str_contains($uri, 'forgot_password')) {
                            $action = 'forgot_password';
                        } elseif (str_contains($uri, 'reset_password')) {
                            $action = 'reset_password';
                        } elseif (str_contains($uri, 'resend_verification')) {
                            $action = 'resend_verification';
                        } elseif (str_contains($uri, 'edit_profile')) {
                            $action = 'edit_profile_info';
                        } elseif (str_contains($uri, 'login')) {
                            $action = 'login';
                        } elseif (str_contains($uri, 'register')) {
                            $action = 'register';
                        }
                    }
                    
                    $this->logger->debug('SecurityMiddleware: Attempting CSRF validation', [
                        'token_name' => $tokenName,
                        'action' => $action,
                        'token_length' => strlen($token),
                        'session_id' => session_id()
                    ]);
                    
                    // Check token via session
                    if ($token === $_SESSION['csrf_token']) {
                        $this->logger->debug('CSRF token validated successfully in SecurityMiddleware', [
                            'action' => $action,
                            'token_name' => $tokenName
                        ]);
                        return true;
                    } else {
                        $this->logger->warning('CSRF token validation failed in SecurityMiddleware', [
                            'action' => $action,
                            'token_name' => $tokenName,
                            'session_id' => session_id()
                        ]);
                    }
                }
            }
            
            $this->logger->warning('No valid CSRF token found in SecurityMiddleware', [
                'available_tokens' => array_keys(array_filter($_POST, function($key) {
                    return str_contains($key, 'csrf');
                }, ARRAY_FILTER_USE_KEY)),
                'post_keys' => array_keys($_POST),
                'request_uri' => $_SERVER['REQUEST_URI'] ?? 'unknown'
            ]);
            
            return false;
        }
        
        // Fallback to the old system for compatibility
        $token = $_POST['csrf_token'] ?? '';
        $sessionToken = $_SESSION['csrf_token'] ?? '';
        
        return !empty($token) && !empty($sessionToken) && hash_equals($sessionToken, $token);
    }

    private function validateInput(array $request): bool
    {
        // Basic check for dangerous characters in URI
        $dangerousPatterns = [
            '/%3C/', // < encoded as %3C
            '/%3E/', // > encoded as %3E
            '/%27/', // ' encoded as %27
            '/%22/', // " encoded as %22
            '/%3B/', // ; encoded as %3B
            '/%2F/', // / encoded as %2F
            '/%5C/', // \ encoded as %5C
            '/\.\./', // .. (parent directory)
            '/\/\//', // // (double slash)
        ];

        foreach ($dangerousPatterns as $pattern) {
            if (preg_match($pattern, $request['uri'])) {
                return false;
            }
        }

        return true;
    }
}
