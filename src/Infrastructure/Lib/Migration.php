<?php

/**
 * Database Migration System
 * Handles database schema changes and versioning
 *
 * @author Dmytro Hovenko
 */

namespace App\Infrastructure\Lib;

use Exception;
use PDO;


class Migration
{
    private Database $db_handler;
    private Logger $logger;
    private string $migrationsPath;

    public function __construct(Database $db_handler)
    {
        $this->db_handler = $db_handler;
        $this->logger = Logger::getInstance();
        $this->migrationsPath = ROOT_PATH . DS . 'database' . DS . 'migrations';
        
        // Create a migrations directory if it doesn't exist
        if (!is_dir($this->migrationsPath)) {
            mkdir($this->migrationsPath, 0755, true);
        }
        
        $this->createMigrationsTable();
    }

    private function createMigrationsTable(): void
    {
        $conn = $this->db_handler->getConnection();

        $sql = "CREATE TABLE IF NOT EXISTS migrations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            migration VARCHAR(255) NOT NULL UNIQUE,
            executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        
        $conn->exec($sql);
    }

    public function run(): array
    {
        $results = [];
        $pendingMigrations = $this->getPendingMigrations();
        
        foreach ($pendingMigrations as $migration) {
            try {
                $this->executeMigration($migration);
                $this->markAsExecuted($migration);
                $results[] = ['migration' => $migration, 'status' => 'success'];
                $this->logger->info("Migration executed successfully", ['migration' => $migration]);
            } catch (Exception $e) {
                $results[] = ['migration' => $migration, 'status' => 'failed', 'error' => $e->getMessage()];
                $this->logger->error("Migration failed", [
                    'migration' => $migration,
                    'error' => $e->getMessage()
                ]);
                break; // Stop on first failure
            }
        }
        
        return $results;
    }

    private function getPendingMigrations(): array
    {
        $conn = $this->db_handler->getConnection();
        $allMigrations = $this->getAllMigrationFiles();
        
        // Get executed migrations
        $stmt = $conn->prepare("SELECT migration FROM migrations");
        $stmt->execute();
        $executed = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Return pending migrations
        return array_diff($allMigrations, $executed);
    }

    private function getAllMigrationFiles(): array
    {
        $files = glob($this->migrationsPath . DS . '*.sql');
        $migrations = [];
        
        foreach ($files as $file) {
            $migrations[] = basename($file, '.sql');
        }
        
        sort($migrations);
        return $migrations;
    }

    /**
     * @throws Exception
     */
    private function executeMigration(string $migration): void
    {
        $filePath = $this->migrationsPath . DS . $migration . '.sql';
        
        if (!file_exists($filePath)) {
            throw new Exception("Migration file not found: $filePath");
        }
        
        $sql = file_get_contents($filePath);
        $conn = $this->db_handler->getConnection();
        
        // Execute each statement separately
        $statements = array_filter(
            array_map('trim', explode(';', $sql)),
            fn($stmt) => !empty($stmt)
        );

        foreach ($statements as $statement) {
            $conn->exec($statement);
        }
    }

    private function markAsExecuted(string $migration): void
    {
        $conn = $this->db_handler->getConnection();
        $stmt = $conn->prepare("INSERT INTO migrations (migration) VALUES (:migration)");
        $stmt->bindValue(':migration', $migration);
        $stmt->execute();
    }

}
