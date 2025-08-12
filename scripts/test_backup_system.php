<?php

declare(strict_types=1);

/**
 * Comprehensive Backup System Test Script
 * Tests both automatic and manual backup functionality
 * Usage: php scripts/test_backup_system.php [--verbose] [--test=manual|auto|all]
 */

require_once dirname(__DIR__) . '/includes/bootstrap.php';

use App\Application\Controllers\DatabaseBackupController;
use App\Application\Core\ServiceProvider;

class BackupSystemTester
{
    private DatabaseBackupController $backupController;
    private bool $verbose;
    private array $testResults = [];
    private int $totalTests = 0;
    private int $passedTests = 0;

    public function __construct(bool $verbose = false)
    {
        $this->verbose = $verbose;
        
        try {
            $this->backupController = new DatabaseBackupController();
            $this->log("✅ Backup controller initialized successfully", 'success');
        } catch (\Exception $e) {
            $this->log("❌ Failed to initialize backup controller: " . $e->getMessage(), 'error');
            exit(1);
        }
    }

    /**
     * Run all tests
     */
    public function runAllTests(): void
    {
        $this->log("🧪 Starting Comprehensive Backup System Tests", 'header');
        $this->log("=" . str_repeat("=", 60), 'header');

        // Test 1: System Initialization
        $this->testSystemInitialization();

        // Test 2: Database Connection
        $this->testDatabaseConnection();

        // Test 3: Backup Directory
        $this->testBackupDirectory();

        // Test 4: Manual Backup Creation
        $this->testManualBackupCreation();

        // Test 5: Backup List Functionality
        $this->testBackupListFunctionality();

        // Test 6: Backup File Integrity
        $this->testBackupFileIntegrity();

        // Test 7: Backup Deletion
        $this->testBackupDeletion();

        // Test 8: Automatic Backup Simulation
        $this->testAutomaticBackupSimulation();

        // Test 9: API Endpoints
        $this->testApiEndpoints();

        // Test 10: Error Handling
        $this->testErrorHandling();

        // Summary
        $this->printSummary();
    }

    /**
     * Test only manual backup functionality
     */
    public function testManualOnly(): void
    {
        $this->log("🔧 Testing Manual Backup Functionality Only", 'header');
        $this->log("=" . str_repeat("=", 50), 'header');

        $this->testSystemInitialization();
        $this->testManualBackupCreation();
        $this->testBackupListFunctionality();
        $this->testBackupFileIntegrity();
        $this->testApiEndpoints();

        $this->printSummary();
    }

    /**
     * Test only automatic backup functionality
     */
    public function testAutoOnly(): void
    {
        $this->log("⏰ Testing Automatic Backup Functionality Only", 'header');
        $this->log("=" . str_repeat("=", 50), 'header');

        $this->testSystemInitialization();
        $this->testAutomaticBackupSimulation();
        $this->testBackupListFunctionality();

        $this->printSummary();
    }

    /**
     * Test 1: System Initialization
     */
    private function testSystemInitialization(): void
    {
        $this->startTest("System Initialization");

        try {
            // Test if all required services are available
            $services = ServiceProvider::getInstance();
            $database = $services->getDatabase();
            $logger = $services->getLogger();

            $this->assert($database !== null, "Database service available");
            $this->assert($logger !== null, "Logger service available");
            $this->assert($this->backupController !== null, "Backup controller available");

            $this->passTest("System initialization completed successfully");
        } catch (\Exception $e) {
            $this->failTest("System initialization failed: " . $e->getMessage());
        }
    }

    /**
     * Test 2: Database Connection
     */
    private function testDatabaseConnection(): void
    {
        $this->startTest("Database Connection");

        try {
            $services = ServiceProvider::getInstance();
            $database = $services->getDatabase();
            
            // Test basic query
            $stmt = $database->query("SELECT 1 as test");
            $result = $stmt->fetch();
            
            $this->assert($result && $result['test'] == 1, "Database query execution");

            // Test tables existence
            $stmt = $database->query("SHOW TABLES");
            $tables = $stmt->fetchAll();
            
            $this->assert(count($tables) > 0, "Database tables exist");

            $this->passTest("Database connection and basic queries working");
        } catch (\Exception $e) {
            $this->failTest("Database connection failed: " . $e->getMessage());
        }
    }

    /**
     * Test 3: Backup Directory
     */
    private function testBackupDirectory(): void
    {
        $this->startTest("Backup Directory Access");

        try {
            $backups = $this->backupController->getBackupsList();
            
            // Get backup directory from reflection (since it's private)
            $reflection = new \ReflectionClass($this->backupController);
            $property = $reflection->getProperty('backupDirectory');
            $property->setAccessible(true);
            $backupDir = $property->getValue($this->backupController);

            $this->assert(is_dir($backupDir), "Backup directory exists: $backupDir");
            $this->assert(is_writable($backupDir), "Backup directory is writable");
            $this->assert(is_array($backups), "Backup list is accessible");

            $this->passTest("Backup directory access verified");
        } catch (\Exception $e) {
            $this->failTest("Backup directory test failed: " . $e->getMessage());
        }
    }

    /**
     * Test 4: Manual Backup Creation
     */
    private function testManualBackupCreation(): void
    {
        $this->startTest("Manual Backup Creation");

        try {
            $initialBackups = $this->backupController->getBackupsList();
            $initialCount = count($initialBackups);

            // Create manual backup
            $result = $this->backupController->createFullBackup();

            $this->assert($result['success'] === true, "Backup creation returns success");
            $this->assert(isset($result['filename']), "Backup filename provided");
            $this->assert(isset($result['size']) && $result['size'] > 0, "Backup file has valid size");
            $this->assert(isset($result['path']) && file_exists($result['path']), "Backup file exists on filesystem");

            // Verify backup was added to list
            $newBackups = $this->backupController->getBackupsList();
            $newCount = count($newBackups);

            $this->assert($newCount === $initialCount + 1, "Backup count increased by 1");

            // Store for cleanup
            $this->testResults['last_created_backup'] = $result;

            $this->passTest("Manual backup created successfully: " . $result['filename']);
        } catch (\Exception $e) {
            $this->failTest("Manual backup creation failed: " . $e->getMessage());
        }
    }

    /**
     * Test 5: Backup List Functionality
     */
    private function testBackupListFunctionality(): void
    {
        $this->startTest("Backup List Functionality");

        try {
            $backups = $this->backupController->getBackupsList();

            $this->assert(is_array($backups), "Backup list is array");

            if (!empty($backups)) {
                $backup = $backups[0];
                
                $this->assert(isset($backup['filename']), "Backup has filename");
                $this->assert(isset($backup['size']), "Backup has size");
                $this->assert(isset($backup['created_at']), "Backup has creation time");
                $this->assert(isset($backup['path']), "Backup has path");
                $this->assert(file_exists($backup['path']), "Backup file exists");

                // Test sorting (newest first)
                if (count($backups) > 1) {
                    $this->assert($backups[0]['created_at'] >= $backups[1]['created_at'], "Backups sorted by date (newest first)");
                }
            }

            $this->passTest("Backup list functionality verified");
        } catch (\Exception $e) {
            $this->failTest("Backup list test failed: " . $e->getMessage());
        }
    }

    /**
     * Test 6: Backup File Integrity
     */
    private function testBackupFileIntegrity(): void
    {
        $this->startTest("Backup File Integrity");

        try {
            $backups = $this->backupController->getBackupsList();

            if (empty($backups)) {
                $this->skipTest("No backups available for integrity testing");
                return;
            }

            $testBackup = $backups[0];
            $filePath = $testBackup['path'];

            // Test file existence and readability
            $this->assert(file_exists($filePath), "Backup file exists");
            $this->assert(is_readable($filePath), "Backup file is readable");
            $this->assert(filesize($filePath) > 0, "Backup file is not empty");

            // Test gzip compression
            $this->assert(pathinfo($filePath, PATHINFO_EXTENSION) === 'gz', "Backup file is gzipped");

            // Test gzip content can be read
            $compressed = file_get_contents($filePath);
            $decompressed = gzdecode($compressed);
            
            $this->assert($decompressed !== false, "Gzip decompression successful");
            $this->assert(strlen($decompressed) > 0, "Decompressed content not empty");
            $this->assert(strpos($decompressed, 'Database Backup Generated:') !== false, "SQL header present");

            $this->passTest("Backup file integrity verified");
        } catch (\Exception $e) {
            $this->failTest("Backup integrity test failed: " . $e->getMessage());
        }
    }

    /**
     * Test 7: Backup Deletion
     */
    private function testBackupDeletion(): void
    {
        $this->startTest("Backup Deletion");

        try {
            // Use the backup we created in the manual backup test
            if (!isset($this->testResults['last_created_backup'])) {
                $this->skipTest("No test backup available for deletion");
                return;
            }

            $backup = $this->testResults['last_created_backup'];
            $filename = $backup['filename'];
            $filePath = $backup['path'];

            // Verify file exists before deletion
            $this->assert(file_exists($filePath), "Test backup file exists before deletion");

            // Delete the backup
            $result = $this->backupController->deleteBackup($filename);

            $this->assert($result === true, "Deletion returns true");
            $this->assert(!file_exists($filePath), "Backup file removed from filesystem");

            $this->passTest("Backup deletion successful");
        } catch (\Exception $e) {
            $this->failTest("Backup deletion failed: " . $e->getMessage());
        }
    }

    /**
     * Test 8: Automatic Backup Simulation
     */
    private function testAutomaticBackupSimulation(): void
    {
        $this->startTest("Automatic Backup Simulation");

        try {
            // Test the autoBackup method
            $result = $this->backupController->autoBackup();

            $this->assert($result['success'] === true, "Auto backup returns success");
            $this->assert($result['backup_type'] === 'automatic', "Backup type is automatic");
            $this->assert(isset($result['filename']), "Auto backup filename provided");

            // Cleanup the test backup
            if (isset($result['filename'])) {
                $this->backupController->deleteBackup($result['filename']);
            }

            $this->passTest("Automatic backup simulation successful");
        } catch (\Exception $e) {
            $this->failTest("Automatic backup simulation failed: " . $e->getMessage());
        }
    }

    /**
     * Test 9: API Endpoints
     */
    private function testApiEndpoints(): void
    {
        $this->startTest("API Endpoints");

        try {
            // Test manual backup API endpoint exists
            $manualBackupApi = dirname(__DIR__) . '/page/api/manual_backup.php';
            $this->assert(file_exists($manualBackupApi), "Manual backup API file exists");

            // Test backup management API endpoint exists
            $backupManagementApi = dirname(__DIR__) . '/page/api/backup_management.php';
            $this->assert(file_exists($backupManagementApi), "Backup management API file exists");

            // Test download API endpoint exists
            $downloadApi = dirname(__DIR__) . '/page/api/download_backup.php';
            $this->assert(file_exists($downloadApi), "Download backup API file exists");

            // Test API files have valid PHP syntax
            $this->assert($this->validatePhpSyntax($manualBackupApi), "Manual backup API has valid syntax");
            $this->assert($this->validatePhpSyntax($backupManagementApi), "Backup management API has valid syntax");
            $this->assert($this->validatePhpSyntax($downloadApi), "Download API has valid syntax");

            $this->passTest("API endpoints validation successful");
        } catch (\Exception $e) {
            $this->failTest("API endpoints test failed: " . $e->getMessage());
        }
    }

    /**
     * Test 10: Error Handling
     */
    private function testErrorHandling(): void
    {
        $this->startTest("Error Handling");

        try {
            // Test deletion of non-existent file
            $result = $this->backupController->deleteBackup('non_existent_file.sql.gz');
            $this->assert($result === false, "Deletion of non-existent file returns false");

            $this->passTest("Error handling tests passed");
        } catch (\Exception $e) {
            $this->failTest("Error handling test failed: " . $e->getMessage());
        }
    }

    /**
     * Validate PHP file syntax
     */
    private function validatePhpSyntax(string $filepath): bool
    {
        $output = [];
        $return = 0;
        exec("php -l " . escapeshellarg($filepath) . " 2>&1", $output, $return);
        return $return === 0;
    }

    /**
     * Helper methods for test management
     */
    private function startTest(string $testName): void
    {
        $this->totalTests++;
        $this->log("\n🔍 Test " . $this->totalTests . ": $testName", 'test');
    }

    private function passTest(string $message): void
    {
        $this->passedTests++;
        $this->log("   ✅ $message", 'success');
    }

    private function failTest(string $message): void
    {
        $this->log("   ❌ $message", 'error');
    }

    private function skipTest(string $message): void
    {
        $this->log("   ⏭️  SKIPPED: $message", 'warning');
    }

    private function assert(bool $condition, string $message): void
    {
        if ($condition) {
            if ($this->verbose) {
                $this->log("     ✓ $message", 'verbose');
            }
        } else {
            throw new \Exception("Assertion failed: $message");
        }
    }

    private function log(string $message, string $type = 'info'): void
    {
        $colors = [
            'header' => "\033[1;36m",    // Cyan bold
            'success' => "\033[0;32m",   // Green
            'error' => "\033[0;31m",     // Red
            'warning' => "\033[0;33m",   // Yellow
            'test' => "\033[1;34m",      // Blue bold
            'verbose' => "\033[0;37m",   // Light gray
            'info' => "\033[0m"          // Reset
        ];

        $reset = "\033[0m";
        $color = $colors[$type] ?? $colors['info'];

        echo $color . $message . $reset . "\n";
    }

    private function printSummary(): void
    {
        $this->log("\n" . str_repeat("=", 60), 'header');
        $this->log("📊 TEST SUMMARY", 'header');
        $this->log(str_repeat("=", 60), 'header');

        $passed = $this->passedTests;
        $total = $this->totalTests;
        $failed = $total - $passed;
        $percentage = $total > 0 ? round(($passed / $total) * 100, 1) : 0;

        $this->log("Total Tests: $total", 'info');
        $this->log("Passed: $passed", $passed > 0 ? 'success' : 'info');
        $this->log("Failed: $failed", $failed > 0 ? 'error' : 'info');
        $this->log("Success Rate: $percentage%", $percentage >= 80 ? 'success' : ($percentage >= 60 ? 'warning' : 'error'));

        if ($percentage >= 80) {
            $this->log("\n🎉 BACKUP SYSTEM IS HEALTHY!", 'success');
        } elseif ($percentage >= 60) {
            $this->log("\n⚠️  BACKUP SYSTEM HAS SOME ISSUES", 'warning');
        } else {
            $this->log("\n💥 BACKUP SYSTEM HAS CRITICAL ISSUES!", 'error');
        }

        $this->log("\n💡 Recommendations:", 'header');
        if ($failed > 0) {
            $this->log("- Review failed tests above", 'warning');
            $this->log("- Check error logs in storage/logs/", 'warning');
            $this->log("- Verify database connectivity", 'warning');
            $this->log("- Ensure proper file permissions", 'warning');
        } else {
            $this->log("- System is operating normally", 'success');
            $this->log("- Consider setting up automated monitoring", 'info');
            $this->log("- Regular testing recommended", 'info');
        }

        exit($failed > 0 ? 1 : 0);
    }
}

// Main execution
function main($argv): void
{
    $verbose = in_array('--verbose', $argv) || in_array('-v', $argv);
    $testType = 'all';

    // Parse test type
    foreach ($argv as $arg) {
        if (strpos($arg, '--test=') === 0) {
            $testType = substr($arg, 7);
            break;
        }
    }

    $tester = new BackupSystemTester($verbose);

    switch ($testType) {
        case 'manual':
            $tester->testManualOnly();
            break;
        case 'auto':
            $tester->testAutoOnly();
            break;
        case 'all':
        default:
            $tester->runAllTests();
            break;
    }
}

// Help function
function showHelp(): void
{
    echo "Backup System Test Script\n";
    echo "Usage: php scripts/test_backup_system.php [OPTIONS]\n\n";
    echo "Options:\n";
    echo "  --test=TYPE    Test type: manual, auto, or all (default: all)\n";
    echo "  --verbose, -v  Show detailed output\n";
    echo "  --help, -h     Show this help\n\n";
    echo "Examples:\n";
    echo "  php scripts/test_backup_system.php                    # Run all tests\n";
    echo "  php scripts/test_backup_system.php --test=manual     # Test manual backups only\n";
    echo "  php scripts/test_backup_system.php --test=auto       # Test automatic backups only\n";
    echo "  php scripts/test_backup_system.php --verbose         # Detailed output\n";
}

// Handle command line arguments
if (in_array('--help', $argv ?? []) || in_array('-h', $argv ?? [])) {
    showHelp();
    exit(0);
}

// Run tests
main($argv ?? []);
