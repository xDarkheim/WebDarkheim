<?php

/**
 * Database connection manager implementing Single Responsibility Principle
 * Handles only database connection and transaction management
 *
 * @author Dmytro Hovenko
 */

declare(strict_types=1);

namespace App\Infrastructure\Lib;

use App\Domain\Interfaces\DatabaseInterface;
use App\Domain\Interfaces\LoggerInterface;
use Exception;
use PDO;
use PDOException;
use PDOStatement;
use RuntimeException;


class Database implements DatabaseInterface
{
    private ?PDO $connection = null;
    private bool $inTransaction = false;
    private LoggerInterface $logger;

    private string $host;
    private string $database;
    private string $username;
    private string $password;
    private string $charset;
    private array $options;

    public function __construct(
        ?LoggerInterface $logger = null,
        ?string $host = null,
        ?string $database = null,
        ?string $username = null,
        ?string $password = null,
        ?string $charset = null
    ) {
        $this->logger = $logger ?? Logger::getInstance();
        $this->host = $host ?? $_ENV['DB_HOST'] ?? DB_HOST;
        $this->database = $database ?? $_ENV['DB_NAME'] ?? DB_NAME;
        $this->username = $username ?? $_ENV['DB_USER'] ?? DB_USER;
        $this->password = $password ?? $_ENV['DB_PASS'] ?? DB_PASS;
        $this->charset = $charset ?? $_ENV['DB_CHARSET'] ?? DB_CHARSET;

        $this->options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES $this->charset",
            PDO::ATTR_PERSISTENT => false,
            PDO::ATTR_TIMEOUT => 30,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getConnection(): PDO
    {
        if ($this->connection === null) {
            $this->connect();
        }

        if ($this->connection === null) {
            throw new RuntimeException('Failed to establish database connection');
        }

        return $this->connection;
    }

    /**
     * {}
     */
    public function isConnected(): bool
    {
        return $this->connection !== null;
    }

    /**
     * {}
     */
    public function close(): void
    {
        if ($this->inTransaction) {
            $this->rollback();
        }

        $this->connection = null;
        $this->logger->debug('Database connection closed');
    }

    /**
     * {@inheritdoc}
     */
    public function beginTransaction(): bool
    {
        $connection = $this->getConnection();

        try {
            $result = $connection->beginTransaction();
            $this->inTransaction = $result;

            if ($result) {
                $this->logger->debug('Database transaction started');
            }

            return $result;
        } catch (PDOException $e) {
            $this->logger->error('Failed to start transaction', [
                'error' => $e->getMessage(),
                'code' => $e->getCode()
            ]);
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function commit(): bool
    {
        if (!$this->inTransaction || $this->connection === null) {
            return false;
        }

        try {
            $result = $this->connection->commit();
            $this->inTransaction = false;

            if ($result) {
                $this->logger->debug('Database transaction committed');
            }

            return $result;
        } catch (PDOException $e) {
            $this->logger->error('Failed to commit transaction', [
                'error' => $e->getMessage(),
                'code' => $e->getCode()
            ]);
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function rollback(): bool
    {
        if (!$this->inTransaction || $this->connection === null) {
            return false;
        }

        try {
            $result = $this->connection->rollBack();
            $this->inTransaction = false;

            if ($result) {
                $this->logger->debug('Database transaction rolled back');
            }

            return $result;
        } catch (PDOException $e) {
            $this->logger->error('Failed to rollback transaction', [
                'error' => $e->getMessage(),
                'code' => $e->getCode()
            ]);
            return false;
        }
    }

    /**
     * Establish database connection
     */
    private function connect(): void
    {
        if (empty($this->database)) {
            $this->logger->critical('Database name is not configured');
            return;
        }

        try {
            $dsn = sprintf(
                'mysql:host=%s;dbname=%s;charset=%s',
                $this->host,
                $this->database,
                $this->charset
            );

            $this->connection = new PDO($dsn, $this->username, $this->password, $this->options);

            $this->logger->info('Database connection established successfully', [
                'host' => $this->host,
                'database' => $this->database,
                'charset' => $this->charset
            ]);

        } catch (PDOException $e) {
            $this->logger->critical('Database connection failed', [
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
                'host' => $this->host,
                'database' => $this->database
            ]);

            // Don't expose sensitive information in production
            if (($_ENV['APP_ENV'] ?? 'production') === 'development') {
                throw $e;
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function prepare(string $sql): PDOStatement
    {
        $connection = $this->getConnection();
        return $connection->prepare($sql);
    }

    /**
     * {@inheritdoc}
     */
    public function query(string $sql, array $params = []): PDOStatement
    {
        $connection = $this->getConnection();

        if (empty($params)) {
            return $connection->query($sql);
        }

        $stmt = $connection->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /**
     * {@inheritdoc}
     */
    public function fetch(string $sql, array $params = []): ?array
    {
        try {
            $stmt = $this->query($sql, $params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result === false ? null : $result;
        } catch (Exception $e) {
            $this->logger->error('Database fetch error', [
                'sql' => $sql,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function fetchAll(string $sql, array $params = []): array
    {
        try {
            $stmt = $this->query($sql, $params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $this->logger->error('Database fetchAll error', [
                'sql' => $sql,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function lastInsertId(): string
    {
        $connection = $this->getConnection();
        return $connection->lastInsertId() ?: '0';
    }

    /**
     * {}
     */
    public function inTransaction(): bool
    {
        $connection = $this->getConnection();
        return $connection->inTransaction();
    }

    /**
     * {}
     */
    public function rowCount(): int
    {
        // This method should be called on a statement, not connection
        // We'll return 0 as a default, but this should be used with statements
        return 0;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(string $sql, array $params = []): bool
    {
        try {
            return true;
        } catch (Exception $e) {
            $this->logger->error('Database execute error', [
                'sql' => $sql,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}
