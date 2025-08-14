<?php

/**
 * Performance tests for Backup System
 *
 * @author GitHub Copilot
 */

declare(strict_types=1);

namespace Tests\Performance;

use PHPUnit\Framework\TestCase;
use App\Application\Controllers\DatabaseBackupController;

class BackupPerformanceTest extends TestCase
{
    private DatabaseBackupController $controller;
    private string $testBackupDir;
    private array $performanceMetrics;

    protected function setUp(): void
    {
        $this->testBackupDir = sys_get_temp_dir() . '/backup_perf_test_' . uniqid();
        mkdir($this->testBackupDir, 0755, true);
        $this->performanceMetrics = [];

        // Initialize controller with test directory
        $this->controller = new DatabaseBackupController();

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
    }

    private function measureExecutionTime(callable $callback): array
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);

        $result = $callback();

        $endTime = microtime(true);
        $endMemory = memory_get_usage(true);

        return [
            'result' => $result,
            'execution_time' => $endTime - $startTime,
            'memory_used' => $endMemory - $startMemory,
            'peak_memory' => memory_get_peak_usage(true)
        ];
    }

    public function testBackupCreationPerformance(): void
    {
        $metrics = $this->measureExecutionTime(function() {
            return $this->controller->createFullBackup();
        });

        $this->performanceMetrics['backup_creation'] = $metrics;

        // Assertions for performance
        $this->assertLessThan(30.0, $metrics['execution_time'], 'Backup creation should complete within 30 seconds');
        $this->assertLessThan(100 * 1024 * 1024, $metrics['memory_used'], 'Memory usage should be under 100MB');
        $this->assertTrue($metrics['result']['success'], 'Backup should be successful');
    }

    public function testBackupListPerformance(): void
    {
        // Create multiple test backup files
        for ($i = 0; $i < 50; $i++) {
            $filename = "perf_test_backup_{$i}_" . time() . ".sql.gz";
            file_put_contents($this->testBackupDir . '/' . $filename, "test backup content {$i}");
        }

        $metrics = $this->measureExecutionTime(function() {
            return $this->controller->getBackupsList();
        });

        $this->performanceMetrics['backup_list'] = $metrics;

        // Assertions
        $this->assertLessThan(2.0, $metrics['execution_time'], 'Getting backup list should complete within 2 seconds');
        $this->assertLessThan(10 * 1024 * 1024, $metrics['memory_used'], 'Memory usage should be under 10MB');
        $this->assertCount(50, $metrics['result'], 'Should return all 50 backup files');
    }

    public function testCleanupPerformance(): void
    {
        // Create many old backup files
        for ($i = 0; $i < 100; $i++) {
            $filename = "old_backup_{$i}_" . time() . ".sql.gz";
            $filepath = $this->testBackupDir . '/' . $filename;
            file_put_contents($filepath, "old backup content {$i}");
            // Set old timestamp
            touch($filepath, strtotime('-35 days'));
        }

        $metrics = $this->measureExecutionTime(function() {
            return $this->controller->cleanupOldBackups();
        });

        $this->performanceMetrics['cleanup'] = $metrics;

        // Assertions
        $this->assertLessThan(10.0, $metrics['execution_time'], 'Cleanup should complete within 10 seconds');
        $this->assertLessThan(20 * 1024 * 1024, $metrics['memory_used'], 'Memory usage should be under 20MB');
        $this->assertTrue($metrics['result']['success'], 'Cleanup should be successful');
        $this->assertEquals(100, $metrics['result']['files_deleted'], 'Should delete all 100 old files');
    }

    public function testConcurrentOperationsPerformance(): void
    {
        // Test multiple operations running sequentially (simulating concurrent load)
        $operations = [];

        // Simulate 5 backup operations
        for ($i = 0; $i < 5; $i++) {
            $metrics = $this->measureExecutionTime(function() {
                return $this->controller->performBackup('test');
            });
            $operations[] = $metrics;
        }

        $totalTime = array_sum(array_column($operations, 'execution_time'));
        $maxMemory = max(array_column($operations, 'peak_memory'));

        $this->assertLessThan(60.0, $totalTime, 'Total time for 5 operations should be under 60 seconds');
        $this->assertLessThan(200 * 1024 * 1024, $maxMemory, 'Peak memory should be under 200MB');

        // All operations should succeed
        foreach ($operations as $operation) {
            $this->assertTrue($operation['result']['success'], 'Each backup operation should succeed');
        }
    }

    public function testLargeBackupHandling(): void
    {
        // Create a test file that simulates a large backup
        $largeTestFile = $this->testBackupDir . '/large_test_backup.sql.gz';

        // Create 10MB of test data
        $testData = str_repeat('test backup data line ' . str_repeat('x', 50) . "\n", 100000);
        file_put_contents($largeTestFile, gzencode($testData));

        $fileSize = filesize($largeTestFile);
        $this->assertGreaterThan(1024 * 1024, $fileSize, 'Test file should be larger than 1MB');

        // Test reading large backup info
        $metrics = $this->measureExecutionTime(function() {
            return $this->controller->getBackupsList();
        });

        $this->assertLessThan(3.0, $metrics['execution_time'], 'Should handle large files within 3 seconds');
        $this->assertNotEmpty($metrics['result'], 'Should return backup list including large file');
    }

    public function testMemoryEfficiency(): void
    {
        $initialMemory = memory_get_usage(true);

        // Perform multiple operations
        $this->controller->getBackupsList();
        $this->controller->performBackup('memory_test');
        $this->controller->getBackupsList();

        $finalMemory = memory_get_usage(true);
        $memoryIncrease = $finalMemory - $initialMemory;

        // Memory increase should be reasonable
        $this->assertLessThan(50 * 1024 * 1024, $memoryIncrease, 'Memory increase should be less than 50MB');
    }

    protected function tearDownAfterClass(): void
    {
        // Output performance summary
        if (!empty(static::$performanceMetrics)) {
            echo "\n=== PERFORMANCE SUMMARY ===\n";
            foreach (static::$performanceMetrics as $test => $metrics) {
                echo sprintf(
                    "%s: %.3fs, %.2fMB\n",
                    str_replace('_', ' ', ucfirst($test)),
                    $metrics['execution_time'],
                    $metrics['memory_used'] / (1024 * 1024)
                );
            }
            echo "============================\n";
        }
    }
}
