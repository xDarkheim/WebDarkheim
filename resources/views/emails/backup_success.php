<?php
/**
 * Backup Success Email Template
 * Modern design for successful backup notifications
 */

require_once __DIR__ . '/_base_template.php';

// Email data
$backupFile = $data['backupFile'] ?? 'backup_file.sql.gz';
$backupSize = $data['backupSize'] ?? 'Unknown';
$backupTime = $data['backupTime'] ?? date('Y-m-d H:i:s');
$backupType = $data['backupType'] ?? 'Full';
$siteName = $data['siteName'] ?? 'Darkheim Development Studio';
$siteUrl = $data['siteUrl'] ?? 'https://darkheim.net';

// Build email content
$content = createEmailTitle("Backup Completed Successfully") . "
    
    <p>Your {$siteName} backup has been completed successfully.</p>
    
    " . createSuccessBox('Backup operation completed without errors.') . "
    
    <p><strong>Backup Details:</strong></p>
    <p>• <strong>Type:</strong> {$backupType} Backup<br>
    • <strong>File:</strong> {$backupFile}<br>
    • <strong>Size:</strong> {$backupSize}<br>
    • <strong>Completed:</strong> {$backupTime}</p>
    
    " . createInfoBox('Your data has been safely backed up and is available for restoration if needed.') . "
    
    " . createButton('View Backup Monitor', $siteUrl . '/admin/backup_monitor', 'primary') . "
    
    " . createWarningBox('Regular backups are essential for data protection. Ensure you have multiple backup copies stored in different locations.') . "
    
    <p>This backup contains all your website data including database, files, and configurations.</p>
    
    <p>Best regards,<br>
    <strong>The {$siteName} System</strong></p>";

// Email settings
$emailData = [
    'title' => 'Backup Successful - ' . $siteName,
    'content' => $content,
    'siteName' => $siteName,
    'siteUrl' => $siteUrl,
    'footerText' => 'This is an automated system notification, please do not reply to this email.'
];

// Generate HTML
echo renderEmailTemplate($emailData);
?>
