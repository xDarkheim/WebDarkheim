<?php
/**
 * Backup Failure Email Template
 * Modern design for backup failure notifications
 */

require_once __DIR__ . '/_base_template.php';

// Email data
$errorMessage = $data['errorMessage'] ?? 'Unknown error occurred';
$backupTime = $data['backupTime'] ?? date('Y-m-d H:i:s');
$backupType = $data['backupType'] ?? 'Full';
$siteName = $data['siteName'] ?? 'Darkheim Development Studio';
$siteUrl = $data['siteUrl'] ?? 'https://darkheim.net';

// Build email content
$content = createEmailTitle("Backup Failed - Action Required") . "
    
    <p>We encountered an issue during the {$siteName} backup process that requires your attention.</p>
    
    " . createDangerBox('Backup operation failed. Your data may not be properly protected.') . "
    
    <p><strong>Failure Details:</strong></p>
    <p>• <strong>Type:</strong> {$backupType} Backup<br>
    • <strong>Failed at:</strong> {$backupTime}<br>
    • <strong>Error:</strong> {$errorMessage}</p>
    
    " . createWarningBox('Important: Your website data is not currently backed up. Please address this issue immediately to ensure data protection.') . "
    
    " . createButton('Check System Status', $siteUrl . '/admin/backup_monitor', 'danger') . "
    
    " . createInfoBox('Common solutions: Check disk space, verify backup directory permissions, ensure database connectivity.') . "
    
    <p><strong>Recommended Actions:</strong></p>
    <p>1. Check available disk space<br>
    2. Verify backup directory permissions<br>
    3. Review system logs for detailed error information<br>
    4. Try running a manual backup to test the system</p>
    
    <p>If you need assistance resolving this issue, please contact technical support with the error details above.</p>
    
    <p>Best regards,<br>
    <strong>The {$siteName} System</strong></p>";

// Email settings
$emailData = [
    'title' => 'URGENT: Backup Failed - ' . $siteName,
    'content' => $content,
    'siteName' => $siteName,
    'siteUrl' => $siteUrl,
    'footerText' => 'This is an automated system alert, please do not reply to this email.'
];

// Generate HTML
echo renderEmailTemplate($emailData);
?>
