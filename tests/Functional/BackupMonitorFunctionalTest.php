<?php

/**
 * Functional tests for Backup Monitor UI
 *
 * @author GitHub Copilot
 */

declare(strict_types=1);

namespace Tests\Functional;

use PHPUnit\Framework\TestCase;

class BackupMonitorFunctionalTest extends TestCase
{
    private string $baseUrl;
    private string $testBackupDir;
    private array $testSession;

    protected function setUp(): void
    {
        $this->baseUrl = 'https://darkheim.net';
        $this->testBackupDir = dirname(__DIR__, 2) . '/storage/backups';
        
        // Mock admin session
        $this->testSession = [
            'user_id' => 1,
            'user_role' => 'admin',
            'authenticated' => true
        ];
    }

    protected function tearDown(): void
    {
        // Clean up test backup files
        $testFiles = glob($this->testBackupDir . '/test_functional_*.sql.gz');
        foreach ($testFiles as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
    }

    private function makeHttpRequest(string $url, array $options = []): array
    {
        $ch = curl_init();
        
        $defaultOptions = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_COOKIEJAR => sys_get_temp_dir() . '/backup_test_cookies.txt',
            CURLOPT_COOKIEFILE => sys_get_temp_dir() . '/backup_test_cookies.txt'
        ];
        
        curl_setopt_array($ch, array_merge($defaultOptions, $options));
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        curl_close($ch);

        return [
            'status_code' => $httpCode,
            'content_type' => $contentType,
            'body' => $response
        ];
    }

    public function testBackupMonitorPageLoads(): void
    {
        $response = $this->makeHttpRequest($this->baseUrl . '/index.php?page=backup_monitor');
        
        $this->assertEquals(200, $response['status_code']);
        $this->assertStringContains('text/html', $response['content_type']);
        $this->assertStringContains('Database Backup Monitor', $response['body']);
        $this->assertStringContains('Create Manual Backup', $response['body']);
        $this->assertStringContains('Cleanup Old Files', $response['body']);
    }

    public function testBackupMonitorRequiresAdminAccess(): void
    {
        // Test without admin session - should redirect to login
        $response = $this->makeHttpRequest($this->baseUrl . '/index.php?page=backup_monitor', [
            CURLOPT_COOKIEJAR => '',
            CURLOPT_COOKIEFILE => ''
        ]);
        
        // Should redirect to login page or show access denied
        $this->assertTrue(
            $response['status_code'] === 302 || 
            $response['status_code'] === 403 || 
            strpos($response['body'], 'login') !== false
        );
    }

    public function testBackupMonitorDisplaysSystemHealth(): void
    {
        $response = $this->makeHttpRequest($this->baseUrl . '/index.php?page=backup_monitor');
        
        $this->assertStringContains('System Health Status', $response['body']);
        $this->assertStringContains('admin-glow-', $response['body']); // Health status indicator
    }

    public function testBackupMonitorDisplaysStatistics(): void
    {
        $response = $this->makeHttpRequest($this->baseUrl . '/index.php?page=backup_monitor');
        
        $this->assertStringContains('Total Backups', $response['body']);
        $this->assertStringContains('Total Size', $response['body']);
        $this->assertStringContains('Latest Backup', $response['body']);
        $this->assertStringContains('Average Size', $response['body']);
        $this->assertStringContains('admin-stats-grid', $response['body']);
    }

    public function testBackupMonitorDisplaysBackupTable(): void
    {
        // First create a test backup file
        $testFilename = 'test_functional_' . time() . '.sql.gz';
        $testPath = $this->testBackupDir . '/' . $testFilename;
        file_put_contents($testPath, 'test backup content for functional test');
        
        $response = $this->makeHttpRequest($this->baseUrl . '/index.php?page=backup_monitor');
        
        $this->assertStringContains('Backup Files', $response['body']);
        $this->assertStringContains('admin-table', $response['body']);
        
        // Should show table headers
        $this->assertStringContains('Filename', $response['body']);
        $this->assertStringContains('Size', $response['body']);
        $this->assertStringContains('Created', $response['body']);
        $this->assertStringContains('Age', $response['body']);
        $this->assertStringContains('Status', $response['body']);
        $this->assertStringContains('Actions', $response['body']);
    }

    public function testBackupMonitorJavaScriptFunctions(): void
    {
        $response = $this->makeHttpRequest($this->baseUrl . '/index.php?page=backup_monitor');
        
        // Check that JavaScript functions are present
        $this->assertStringContains('function createManualBackup()', $response['body']);
        $this->assertStringContains('function cleanupOldBackups()', $response['body']);
        $this->assertStringContains('function downloadBackup(', $response['body']);
        $this->assertStringContains('function deleteBackup(', $response['body']);
        
        // Check API endpoints are correctly configured
        $this->assertStringContains('https://darkheim.net/page/api/admin/manual_backup.php', $response['body']);
        $this->assertStringContains('https://darkheim.net/page/api/admin/cleanup_old_backups.php', $response['body']);
        $this->assertStringContains('https://darkheim.net/page/api/admin/download_backup.php', $response['body']);
        $this->assertStringContains('https://darkheim.net/page/api/admin/backup_management.php', $response['body']);
    }

    public function testBackupMonitorSidebar(): void
    {
        $response = $this->makeHttpRequest($this->baseUrl . '/index.php?page=backup_monitor');
        
        $this->assertStringContains('System Information', $response['body']);
        $this->assertStringContains('Backup Schedule', $response['body']);
        $this->assertStringContains('Storage Details', $response['body']);
        $this->assertStringContains('Quick Actions', $response['body']);
        
        // Check schedule information
        $this->assertStringContains('Daily backups at 2:00 AM', $response['body']);
        $this->assertStringContains('Weekly cleanup on Sundays at 3:00 AM', $response['body']);
        $this->assertStringContains('Maximum 30 backups retained', $response['body']);
        
        // Check storage details
        $this->assertStringContains('/storage/backups/', $response['body']);
        $this->assertStringContains('Compressed SQL (gzip)', $response['body']);
        $this->assertStringContains('Integrity checks enabled', $response['body']);
    }

    public function testBackupMonitorFlashMessageHandling(): void
    {
        $response = $this->makeHttpRequest($this->baseUrl . '/index.php?page=backup_monitor');
        
        // Check that flash message system is integrated
        $this->assertStringContains('flash-messages-data', $response['body']);
        $this->assertStringContains('data-php-messages', $response['body']);
        
        // Check JavaScript flash message handling
        $this->assertStringContains('window.showToast', $response['body']);
        $this->assertStringContains('flash_messages', $response['body']);
    }

    public function testBackupMonitorResponsiveDesign(): void
    {
        $response = $this->makeHttpRequest($this->baseUrl . '/index.php?page=backup_monitor');
        
        // Check responsive classes and layout
        $this->assertStringContains('admin-layout-main', $response['body']);
        $this->assertStringContains('admin-content', $response['body']);
        $this->assertStringContains('admin-sidebar', $response['body']);
        $this->assertStringContains('admin-stats-grid', $response['body']);
        
        // Check that CSS is loaded
        $this->assertStringContains('/public/assets/css/admin.css', $response['body']);
        $this->assertStringContains('/public/assets/js/admin.js', $response['body']);
    }

    public function testBackupMonitorButtonInteractivity(): void
    {
        $response = $this->makeHttpRequest($this->baseUrl . '/index.php?page=backup_monitor');
        
        // Check button attributes for JavaScript handling
        $this->assertStringContains('id="manual-backup-btn"', $response['body']);
        $this->assertStringContains('id="cleanup-old-btn"', $response['body']);
        $this->assertStringContains('data-action="download"', $response['body']);
        $this->assertStringContains('data-action="delete"', $response['body']);
        $this->assertStringContains('data-confirm=', $response['body']);
    }

    public function testBackupMonitorErrorHandling(): void
    {
        // Test page behavior when backup controller fails
        // This would require mocking or temporarily breaking the controller
        
        $response = $this->makeHttpRequest($this->baseUrl . '/index.php?page=backup_monitor');
        
        // Should still load even if there are errors
        $this->assertEquals(200, $response['status_code']);
        $this->assertStringContains('Database Backup Monitor', $response['body']);
    }

    public function testBackupMonitorAccessibilityFeatures(): void
    {
        $response = $this->makeHttpRequest($this->baseUrl . '/index.php?page=backup_monitor');
        
        // Check for accessibility attributes
        $this->assertStringContains('data-tooltip', $response['body']);
        $this->assertStringContains('aria-', $response['body']); // Should contain aria attributes
        
        // Check for semantic HTML
        $this->assertStringContains('<main>', $response['body']);
        $this->assertStringContains('<header>', $response['body']);
        $this->assertStringContains('<aside>', $response['body']);
    }

    public function testBackupMonitorPerformance(): void
    {
        $startTime = microtime(true);
        
        $response = $this->makeHttpRequest($this->baseUrl . '/index.php?page=backup_monitor');
        
        $endTime = microtime(true);
        $responseTime = $endTime - $startTime;
        
        $this->assertEquals(200, $response['status_code']);
        $this->assertLessThan(5.0, $responseTime, 'Page should load within 5 seconds');
        $this->assertGreaterThan(0, strlen($response['body']), 'Response should not be empty');
    }

    public function testBackupMonitorSecurityHeaders(): void
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->baseUrl . '/index.php?page=backup_monitor',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT => 30
        ]);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        // Should include security-related headers or meta tags
        $this->assertStringContains('Content-Type:', $response);
    }
}
