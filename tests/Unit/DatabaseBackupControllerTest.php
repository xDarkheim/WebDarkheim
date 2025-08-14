<?php

/**
 * Unit tests for DatabaseBackupController
 *
 * @author GitHub Copilot
 */

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use App\Application\Controllers\DatabaseBackupController;
use App\Application\Core\ServiceProvider;
use App\Domain\Interfaces\DatabaseInterface;
use App\Domain\Interfaces\LoggerInterface;

class DatabaseBackupControllerTest extends TestCase
{
    private DatabaseBackupController $controller;
    private MockObject $mockDatabase;
    private MockObject $mockLogger;
    private MockObject $mockServiceProvider;
    private string $testBackupDir;

    protected function setUp(): void
    {
        // Create test backup directory
        $this->testBackupDir = sys_get_temp_dir() . '/backup_test_' . uniqid();
        mkdir($this->testBackupDir, 0755, true);

        // Mock dependencies
        $this->mockDatabase = $this->createMock(DatabaseInterface::class);
        $this->mockLogger = $this->createMock(LoggerInterface::class);
        $this->mockServiceProvider = $this->createMock(ServiceProvider::class);

        // Configure mocks
        $this->mockServiceProvider
            ->method('getDatabase')
            ->willReturn($this->mockDatabase);

        $this->mockServiceProvider
            ->method('getLogger')
            ->willReturn($this->mockLogger);

        // Override ServiceProvider singleton for testing
        $reflection = new \ReflectionClass(ServiceProvider::class);
        $instanceProperty = $reflection->getProperty('instance');
        $instanceProperty->setAccessible(true);
        $instanceProperty->setValue($this->mockServiceProvider);

        // Mock database methods
        $this->mockDatabase
            ->method('query')
            ->willReturn($this->createMockStatement());

        $this->controller = new DatabaseBackupController();

        // Set test backup directory via reflection
        $reflection = new \ReflectionClass($this->controller);
        $backupDirProperty = $reflection->getProperty('backupDirectory');
        $backupDirProperty->setAccessible(true);
        $backupDirProperty->setValue($this->controller, $this->testBackupDir);
    }

    protected function tearDown(): void
    {
        // Clean up test directory
        if (is_dir($this->testBackupDir)) {
            $files = glob($this->testBackupDir . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            rmdir($this->testBackupDir);
        }

        // Reset ServiceProvider singleton
        $reflection = new \ReflectionClass(ServiceProvider::class);
        $instanceProperty = $reflection->getProperty('instance');
        $instanceProperty->setAccessible(true);
        $instanceProperty->setValue(null);
    }

    private function createMockStatement(): MockObject
    {
        $mockStatement = $this->createMock(\PDOStatement::class);
        $mockStatement->method('fetchAll')->willReturn([
            ['Tables_in_test' => 'users'],
            ['Tables_in_test' => 'articles'],
            ['Tables_in_test' => 'comments']
        ]);
        return $mockStatement;
    }

    public function testCreateFullBackup(): void
    {
        // Mock database queries for backup
        $this->mockDatabase
            ->expects($this->atLeastOnce())
            ->method('query')
            ->willReturn($this->createMockStatement());

        $this->mockLogger
            ->expects($this->atLeastOnce())
            ->method('info');

        $result = $this->controller->createFullBackup();

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('filename', $result);
        $this->assertArrayHasKey('size', $result);
        $this->assertStringEndsWith('.sql.gz', $result['filename']);
    }

    public function testGetBackupsList(): void
    {
        // Create test backup files
        $testFiles = [
            'backup_20240814_120000.sql.gz',
            'backup_20240813_120000.sql.gz',
            'backup_20240812_120000.sql.gz'
        ];

        foreach ($testFiles as $filename) {
            $filepath = $this->testBackupDir . '/' . $filename;
            file_put_contents($filepath, 'test backup content');
            // Set file modification time for testing
            touch($filepath, strtotime('-' . (array_search($filename, $testFiles)) . ' days'));
        }

        $backups = $this->controller->getBackupsList();

        $this->assertIsArray($backups);
        $this->assertCount(3, $backups);

        // Check that backups are sorted by date (newest first)
        $this->assertEquals('backup_20240814_120000.sql.gz', $backups[0]['filename']);
        $this->assertEquals('backup_20240813_120000.sql.gz', $backups[1]['filename']);
        $this->assertEquals('backup_20240812_120000.sql.gz', $backups[2]['filename']);

        // Check backup structure
        foreach ($backups as $backup) {
            $this->assertArrayHasKey('filename', $backup);
            $this->assertArrayHasKey('size', $backup);
            $this->assertArrayHasKey('created_at', $backup);
            $this->assertArrayHasKey('age_days', $backup);
            $this->assertArrayHasKey('path', $backup);
        }
    }

    public function testDeleteBackup(): void
    {
        // Create test backup file
        $testFilename = 'test_backup_' . time() . '.sql.gz';
        $testPath = $this->testBackupDir . '/' . $testFilename;
        file_put_contents($testPath, 'test backup content');

        $this->assertTrue(file_exists($testPath));

        $this->mockLogger
            ->expects($this->once())
            ->method('info')
            ->with('Backup deleted successfully', ['filename' => $testFilename]);

        $result = $this->controller->deleteBackup($testFilename);

        $this->assertTrue($result);
        $this->assertFalse(file_exists($testPath));
    }

    public function testDeleteBackupNonExistentFile(): void
    {
        $this->mockLogger
            ->expects($this->once())
            ->method('error')
            ->with('Failed to delete backup', $this->callback(function($context) {
                return isset($context['filename']) && isset($context['error']);
            }));

        $result = $this->controller->deleteBackup('non_existent_file.sql.gz');

        $this->assertFalse($result);
    }

    public function testCleanupOldBackups(): void
    {
        // Create test backup files with different ages
        $oldFiles = [
            'backup_old_1.sql.gz' => strtotime('-35 days'),
            'backup_old_2.sql.gz' => strtotime('-40 days'),
            'backup_recent.sql.gz' => strtotime('-5 days'),
        ];

        foreach ($oldFiles as $filename => $timestamp) {
            $filepath = $this->testBackupDir . '/' . $filename;
            file_put_contents($filepath, 'test backup content');
            touch($filepath, $timestamp);
        }

        $this->mockLogger
            ->expects($this->atLeastOnce())
            ->method('info');

        $result = $this->controller->cleanupOldBackups();

        $this->assertTrue($result['success']);
        $this->assertEquals(2, $result['files_deleted']);
        $this->assertGreaterThan(0, $result['total_deleted']);

        // Check that old files were deleted but recent file remains
        $this->assertFalse(file_exists($this->testBackupDir . '/backup_old_1.sql.gz'));
        $this->assertFalse(file_exists($this->testBackupDir . '/backup_old_2.sql.gz'));
        $this->assertTrue(file_exists($this->testBackupDir . '/backup_recent.sql.gz'));
    }

    public function testAutoBackup(): void
    {
        $this->mockDatabase
            ->expects($this->atLeastOnce())
            ->method('query')
            ->willReturn($this->createMockStatement());

        $this->mockLogger
            ->expects($this->atLeastOnce())
            ->method('info');

        $result = $this->controller->autoBackup();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
    }

    public function testPerformBackup(): void
    {
        $this->mockDatabase
            ->expects($this->atLeastOnce())
            ->method('query')
            ->willReturn($this->createMockStatement());

        $this->mockLogger
            ->expects($this->atLeastOnce())
            ->method('info');

        $result = $this->controller->performBackup('manual');

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('filename', $result);
        $this->assertArrayHasKey('tables_count', $result);
        $this->assertArrayHasKey('size', $result);
    }

    public function testCreateStructureBackup(): void
    {
        $this->mockDatabase
            ->expects($this->atLeastOnce())
            ->method('query')
            ->willReturn($this->createMockStatement());

        $this->mockLogger
            ->expects($this->atLeastOnce())
            ->method('info');

        $result = $this->controller->createStructureBackup();

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('filename', $result);
        $this->assertStringContains('structure', $result['filename']);
    }
}
