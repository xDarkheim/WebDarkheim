<?php

/**
 * Database interface for database operations
 *
 * @author Dmytro Hovenko
 */

declare(strict_types=1);

namespace App\Domain\Interfaces;

use PDO;
use PDOStatement;


interface DatabaseInterface
{
    /**
     * Get database connection
     */
    public function getConnection(): PDO;

    /**
     * Execute a query
     */
    public function query(string $sql, array $params = []): PDOStatement;

    /**
     * Execute a prepared statement
     */
    public function execute(string $sql, array $params = []): bool;

    /**
     * Begin transaction
     */
    public function beginTransaction(): bool;

    /**
     * Commit transaction
     */
    public function commit(): bool;

    /**
     * Rollback transaction
     */
    public function rollback(): bool;

    /**
     * Get last insert ID
     */
    public function lastInsertId(): string;

    /**
     * Prepare a statement
     */
    public function prepare(string $sql): PDOStatement;

    /**
     * Fetch a single row from a query
     */
    public function fetch(string $sql, array $params = []): ?array;

    /**
     * Fetch all rows from a query
     */
    public function fetchAll(string $sql, array $params = []): array;
}
