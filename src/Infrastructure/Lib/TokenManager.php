<?php

/**
 * Token management service following Single Responsibility Principle
 * Handles secure token generation, validation, and cleanup
 *
 * @author Dmytro Hovenko
 */

declare(strict_types=1);

namespace App\Infrastructure\Lib;

use App\Domain\Interfaces\TokenManagerInterface;
use App\Domain\Interfaces\DatabaseInterface;
use App\Domain\Interfaces\LoggerInterface;
use InvalidArgumentException;
use PDO;
use PDOException;
use RuntimeException;


class TokenManager implements TokenManagerInterface
{
    public const TYPE_EMAIL_VERIFICATION = 'email_verification';
    public const TYPE_PASSWORD_RESET = 'password_reset';
    public const TYPE_EMAIL_CHANGE = 'email_change';
    public const TYPE_API_ACCESS = 'api_access';
    public const TYPE_REMEMBER_ME = 'remember_me';

    private const TOKEN_LENGTH = 64;

    public function __construct(
        private readonly DatabaseInterface $database,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function generateToken(int $length = 32): string
    {
        if ($length < 16) {
            $length = 16; // Минимальная длина для безопасности
        }

        if ($length > 128) {
            $length = 128; // Максимальная длина
        }

        return $this->createSecureToken($length);
    }

    /**
     * {@inheritdoc}
     */
    public function createVerificationToken(int $userId, string $type, int $expiresInMinutes = 60): string
    {
        $this->validateTokenType($type);

        $token = $this->createSecureToken(self::TOKEN_LENGTH);
        $tokenHash = hash('sha256', $token);
        $expiresAt = date('Y-m-d H:i:s', time() + ($expiresInMinutes * 60));

        $connection = $this->database->getConnection();

        try {
            // First, invalidate any existing tokens of the same type for this user
            $this->revokeUserTokensByType($userId, $type);

            $stmt = $connection->prepare(
                "INSERT INTO user_tokens (user_id, token_hash, token_type, data, expires_at, created_at) 
                 VALUES (?, ?, ?, ?, ?, NOW())"
            );

            $result = $stmt->execute([
                $userId,
                $tokenHash,
                $type,
                json_encode([]),
                $expiresAt
            ]);

            if (!$result) {
                throw new RuntimeException('Failed to store token');
            }

            $this->logger->info('Verification token created', [
                'type' => $type,
                'user_id' => $userId,
                'expires_at' => $expiresAt
            ]);

            return $token;
        } catch (PDOException $e) {
            $this->logger->error('Token generation failed', [
                'type' => $type,
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            throw new RuntimeException('Token generation failed: ' . $e->getMessage());
        }
    }

    /**
     * {@inheritdoc}
     */
    public function verifyToken(string $token, string $type): ?array
    {
        $this->validateTokenType($type);

        $tokenHash = hash('sha256', $token);
        $connection = $this->database->getConnection();

        try {
            $sql = "SELECT * FROM user_tokens 
                    WHERE token_hash = ? AND token_type = ? AND expires_at > NOW() AND revoked_at IS NULL";

            $stmt = $connection->prepare($sql);
            $stmt->execute([$tokenHash, $type]);
            $tokenData = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$tokenData) {
                return null;
            }

            // Automatically revoke the token after successful verification for one-time use tokens
            if (in_array($type, [self::TYPE_EMAIL_VERIFICATION, self::TYPE_PASSWORD_RESET])) {
                $this->revokeToken($token);
            }

            return [
                'user_id' => (int)$tokenData['user_id'],
                'type' => $tokenData['token_type'],
                'data' => json_decode($tokenData['data'], true) ?: [],
                'created_at' => $tokenData['created_at'],
                'expires_at' => $tokenData['expires_at']
            ];
        } catch (PDOException $e) {
            $this->logger->error('Token verification failed', [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function invalidateToken(string $token): bool
    {
        return $this->revokeToken($token);
    }

    /**
     * {}
     */
    public function cleanupExpiredTokens(): int
    {
        $connection = $this->database->getConnection();

        try {
            $stmt = $connection->prepare("DELETE FROM user_tokens WHERE expires_at <= NOW()");
            $stmt->execute();

            $deletedCount = $stmt->rowCount();

            if ($deletedCount > 0) {
                $this->logger->info('Cleaned up expired tokens', [
                    'deleted_count' => $deletedCount
                ]);
            }

            return $deletedCount;
        } catch (PDOException $e) {
            $this->logger->error('Token cleanup failed', [
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }

    /**
     * Clean expired tokens (alias for cleanupExpiredTokens for backward compatibility)
     *
     * @return int Number of deleted tokens
     */
    public function cleanExpiredTokens(): int
    {
        return $this->cleanupExpiredTokens();
    }

    /**
     * {@inheritdoc}
     */
    public function getTokenData(string $token): ?array
    {
        $tokenHash = hash('sha256', $token);
        $connection = $this->database->getConnection();

        try {
            $sql = "SELECT * FROM user_tokens 
                    WHERE token_hash = ? AND expires_at > NOW() AND revoked_at IS NULL";

            $stmt = $connection->prepare($sql);
            $stmt->execute([$tokenHash]);
            $tokenData = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$tokenData) {
                return null;
            }

            return [
                'user_id' => (int)$tokenData['user_id'],
                'type' => $tokenData['token_type'],
                'data' => json_decode($tokenData['data'], true) ?: [],
                'created_at' => $tokenData['created_at'],
                'expires_at' => $tokenData['expires_at']
            ];
        } catch (PDOException $e) {
            $this->logger->error('Token data retrieval failed', [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function revokeUserTokensByType(int $userId, string $type): bool
    {
        $this->validateTokenType($type);

        $connection = $this->database->getConnection();

        try {
            $stmt = $connection->prepare(
                "UPDATE user_tokens 
                 SET revoked_at = NOW() 
                 WHERE user_id = ? AND token_type = ? AND revoked_at IS NULL"
            );

            $result = $stmt->execute([$userId, $type]);

            if ($result) {
                $revokedCount = $stmt->rowCount();
                if ($revokedCount > 0) {
                    $this->logger->info('User tokens revoked', [
                        'user_id' => $userId,
                        'type' => $type,
                        'revoked_count' => $revokedCount
                    ]);
                }
                return true;
            }

            return false;
        } catch (PDOException $e) {
            $this->logger->error('User tokens revocation failed', [
                'user_id' => $userId,
                'type' => $type,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Create secure random token
     */
    private function createSecureToken(int $length = 64): string
    {
        return bin2hex(random_bytes($length / 2));
    }

    /**
     * Validate token type
     */
    private function validateTokenType(string $type): void
    {
        $validTypes = [
            self::TYPE_EMAIL_VERIFICATION,
            self::TYPE_PASSWORD_RESET,
            self::TYPE_EMAIL_CHANGE,
            self::TYPE_API_ACCESS,
            self::TYPE_REMEMBER_ME
        ];

        if (!in_array($type, $validTypes, true)) {
            throw new InvalidArgumentException("Invalid token type: $type");
        }
    }

    /**
     * {@inheritdoc}
     */
    public function validateToken(string $token, string $type = 'default'): bool
    {
        $tokenData = $this->getTokenData($token);
        
        if (!$tokenData) {
            return false;
        }
        
        // Check if the token type matches (if not default)
        if ($type !== 'default' && $tokenData['type'] !== $type) {
            return false;
        }
        
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function revokeToken(string $token): bool
    {
        $tokenHash = hash('sha256', $token);
        $connection = $this->database->getConnection();

        try {
            $stmt = $connection->prepare(
                "UPDATE user_tokens SET revoked_at = NOW() WHERE token_hash = ? AND revoked_at IS NULL"
            );
            
            $result = $stmt->execute([$tokenHash]);
            
            if ($result && $stmt->rowCount() > 0) {
                $this->logger->debug('Token revoked', ['token_hash' => substr($tokenHash, 0, 8) . '...']);
                return true;
            }
            
            return false;
        } catch (PDOException $e) {
            $this->logger->error('Token revocation failed', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function storeToken(string $token, int $userId, string $type = 'default', ?int $expiresAt = null): bool
    {
        $tokenHash = hash('sha256', $token);
        $connection = $this->database->getConnection();

        $expiresAtFormatted = $expiresAt ? date('Y-m-d H:i:s', $expiresAt) : null;

        try {
            $stmt = $connection->prepare(
                "INSERT INTO user_tokens (user_id, token_hash, token_type, data, expires_at, created_at) 
                 VALUES (?, ?, ?, ?, ?, NOW())"
            );

            $result = $stmt->execute([
                $userId,
                $tokenHash,
                $type,
                json_encode([]),
                $expiresAtFormatted
            ]);

            if ($result) {
                $this->logger->debug('Token stored', [
                    'user_id' => $userId,
                    'type' => $type,
                    'expires_at' => $expiresAtFormatted
                ]);
                return true;
            }

            return false;
        } catch (PDOException $e) {
            $this->logger->error('Token storage failed', [
                'user_id' => $userId,
                'type' => $type,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}
