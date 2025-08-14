<?php

/**
 * Integration tests for Backup API endpoints
 *
 * @author GitHub Copilot
 */

declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;

class BackupApiTest extends TestCase
{
    private string $baseUrl;
    private array $adminHeaders;
    private string $testBackupDir;

    protected function setUp(): void
    {
        $this->baseUrl = 'https://darkheim.net';
        $this->testBackupDir = dirname(__DIR__, 2) . '/storage/backups';
        
        // Ensure backup directory exists
        if (!is_dir($this->testBackupDir)) {
            mkdir($this->testBackupDir, 0755, true);
        }

        // Mock admin session for testing
        $this->adminHeaders = [
            'Content-Type: application/json',
            'X-Requested-With: XMLHttpRequest',
            'Cookie: PHPSESSID=' . $this->mockAdminSession()
        ];
    }

    protected function tearDown(): void
    {
        // Clean up test backup files
        $this->cleanupTestFiles();
    }

    private function mockAdminSession(): string
    {
        // Start session and mock admin user
        session_start();
        $_SESSION['user_id'] = 1;
        $_SESSION['user_role'] = 'admin';
        $_SESSION['authenticated'] = true;
        return session_id();
    }

    private function cleanupTestFiles(): void
    {
        $testFiles = glob($this->testBackupDir . '/test_backup_*.sql.gz');
        foreach ($testFiles as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
    }

    private function makeApiRequest(string $endpoint, string $method = 'GET', array $data = [], array $headers = []): array
    {
        $url = $this->baseUrl . $endpoint;
        $headers = array_merge($this->adminHeaders, $headers);

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_SSL_VERIFYPEER => false, // For testing only
            CURLOPT_TIMEOUT => 30
        ]);

        if ($method === 'POST' || $method === 'DELETE') {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [
            'status_code' => $httpCode,
            'body' => json_decode($response, true) ?: ['raw' => $response]
        ];
    }

    public function testManualBackupApi(): void
    {
        $response = $this->makeApiRequest('/page/api/admin/manual_backup.php', 'POST');

        $this->assertEquals(200, $response['status_code']);
        $this->assertTrue($response['body']['success']);
        $this->assertArrayHasKey('filename', $response['body']);
        $this->assertArrayHasKey('flash_messages', $response['body']);
        $this->assertStringEndsWith('.sql.gz', $response['body']['filename']);

        // Verify file was actually created
        $backupPath = $this->testBackupDir . '/' . $response['body']['filename'];
        $this->assertTrue(file_exists($backupPath));
        $this->assertGreaterThan(0, filesize($backupPath));
    }

    public function testManualBackupApiUnauthorized(): void
    {
        // Test without admin session
        session_destroy();
        
        $response = $this->makeApiRequest('/page/api/admin/manual_backup.php', 'POST', [], [
            'Cookie: PHPSESSID=invalid_session'
        ]);

        $this->assertEquals(403, $response['status_code']);
        $this->assertFalse($response['body']['success']);
        $this->assertStringContains('Access denied', $response['body']['error']);
    }

    public function testManualBackupApiWrongMethod(): void
    {
        $response = $this->makeApiRequest('/page/api/admin/manual_backup.php', 'GET');

        $this->assertEquals(405, $response['status_code']);
        $this->assertFalse($response['body']['success']);
        $this->assertStringContains('Method not allowed', $response['body']['error']);
    }

    public function testCleanupOldBackupsApi(): void
    {
        // Create test backup files with old timestamps
        $oldFiles = [
            'test_backup_old_1.sql.gz',
            'test_backup_old_2.sql.gz',
            'test_backup_recent.sql.gz'
        ];

        foreach ($oldFiles as $index => $filename) {
            $filepath = $this->testBackupDir . '/' . $filename;
            file_put_contents($filepath, 'test backup content ' . $index);
            
            // Set old timestamp for first two files
            if ($index < 2) {
                touch($filepath, strtotime('-35 days'));
            }
        }

        $response = $this->makeApiRequest('/page/api/admin/cleanup_old_backups.php', 'POST');

        $this->assertEquals(200, $response['status_code']);
        $this->assertTrue($response['body']['success']);
        $this->assertArrayHasKey('files_deleted', $response['body']);
        $this->assertGreaterThan(0, $response['body']['files_deleted']);

        // Verify old files were deleted but recent file remains
        $this->assertFalse(file_exists($this->testBackupDir . '/test_backup_old_1.sql.gz'));
        $this->assertFalse(file_exists($this->testBackupDir . '/test_backup_old_2.sql.gz'));
        $this->assertTrue(file_exists($this->testBackupDir . '/test_backup_recent.sql.gz'));
    }

    public function testDownloadBackupApi(): void
    {
        // Create test backup file
        $testFilename = 'test_backup_download.sql.gz';
        $testContent = 'test backup content for download';
        $testPath = $this->testBackupDir . '/' . $testFilename;
        file_put_contents($testPath, $testContent);

        $url = $this->baseUrl . '/page/api/admin/download_backup.php?filename=' . urlencode($testFilename);
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $this->adminHeaders,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT => 30
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        curl_close($ch);

        $this->assertEquals(200, $httpCode);
        $this->assertEquals('application/gzip', $contentType);
        $this->assertEquals($testContent, $response);
    }

    public function testDownloadBackupApiFileNotFound(): void
    {
        $response = $this->makeApiRequest('/page/api/admin/download_backup.php?filename=nonexistent.sql.gz');

        $this->assertEquals(404, $response['status_code']);
        $this->assertFalse($response['body']['success']);
        $this->assertStringContains('not found', $response['body']['error']);
    }

    public function testDownloadBackupApiPathTraversal(): void
    {
        // Test security against path traversal attacks
        $maliciousFilename = '../../../etc/passwd';
        $response = $this->makeApiRequest('/page/api/admin/download_backup.php?filename=' . urlencode($maliciousFilename));

        $this->assertEquals(403, $response['status_code']);
        $this->assertFalse($response['body']['success']);
        $this->assertStringContains('Invalid file path', $response['body']['error']);
    }

    public function testBackupManagementDeleteApi(): void
    {
        // Create test backup file
        $testFilename = 'test_backup_delete.sql.gz';
        $testPath = $this->testBackupDir . '/' . $testFilename;
        file_put_contents($testPath, 'test backup content for deletion');

        $this->assertTrue(file_exists($testPath));

        $response = $this->makeApiRequest('/page/api/admin/backup_management.php', 'DELETE', [
            'filename' => $testFilename
        ]);

        $this->assertEquals(200, $response['status_code']);
        $this->assertTrue($response['body']['success']);
        $this->assertEquals($testFilename, $response['body']['filename']);
        
        // Verify file was deleted
        $this->assertFalse(file_exists($testPath));
    }

    public function testBackupManagementDeleteApiFileNotFound(): void
    {
        $response = $this->makeApiRequest('/page/api/admin/backup_management.php', 'DELETE', [
            'filename' => 'nonexistent_file.sql.gz'
        ]);

        $this->assertEquals(500, $response['status_code']);
        $this->assertFalse($response['body']['success']);
    }

    public function testBackupManagementApiInvalidMethod(): void
    {
        $response = $this->makeApiRequest('/page/api/admin/backup_management.php', 'GET');

        $this->assertEquals(405, $response['status_code']);
        $this->assertFalse($response['body']['success']);
        $this->assertStringContains('Method not allowed', $response['body']['error']);
    }

    public function testBackupManagementApiMissingFilename(): void
    {
        $response = $this->makeApiRequest('/page/api/admin/backup_management.php', 'DELETE', []);

        $this->assertEquals(400, $response['status_code']);
        $this->assertFalse($response['body']['success']);
        $this->assertStringContains('Filename required', $response['body']['error']);
    }

    public function testApiFlashMessagesIntegration(): void
    {
        $response = $this->makeApiRequest('/page/api/admin/manual_backup.php', 'POST');

        $this->assertEquals(200, $response['status_code']);
        $this->assertArrayHasKey('flash_messages', $response['body']);
        
        $flashMessages = $response['body']['flash_messages'];
        $this->assertIsArray($flashMessages);
        
        // Should contain success message
        $this->assertArrayHasKey('success', $flashMessages);
        $this->assertNotEmpty($flashMessages['success']);
    }

    public function testApiErrorHandling(): void
    {
        // Mock a scenario that would cause an error
        // Test with invalid JSON in request body for DELETE endpoint
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->baseUrl . '/page/api/admin/backup_management.php',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $this->adminHeaders,
            CURLOPT_CUSTOMREQUEST => 'DELETE',
            CURLOPT_POSTFIELDS => 'invalid json content',
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT => 30
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $decodedResponse = json_decode($response, true);

        $this->assertEquals(400, $httpCode);
        $this->assertFalse($decodedResponse['success']);
        $this->assertArrayHasKey('error', $decodedResponse);
    }

    public function testAllApiEndpointsSecurityHeaders(): void
    {
        $endpoints = [
            '/page/api/admin/manual_backup.php',
            '/page/api/admin/cleanup_old_backups.php',
            '/page/api/admin/backup_management.php'
        ];

        foreach ($endpoints as $endpoint) {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $this->baseUrl . $endpoint,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => $this->adminHeaders,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_TIMEOUT => 10,
                CURLOPT_HEADER => true
            ]);

            $response = curl_exec($ch);
            curl_close($ch);

            // Check that Content-Type header is set to application/json
            $this->assertStringContains('Content-Type: application/json', $response);
        }
    }
}
