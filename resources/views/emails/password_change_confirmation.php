<?php
/**
 * Password Change Confirmation Email Template
 * Modern design for password change confirmations
 */

require_once __DIR__ . '/_base_template.php';

// Email data
$username = $data['username'] ?? 'User';
$siteName = $data['siteName'] ?? 'Darkheim Development Studio';
$siteUrl = $data['siteUrl'] ?? 'https://darkheim.net';
$changeTime = $data['changeTime'] ?? date('Y-m-d H:i:s');
$ipAddress = $data['ipAddress'] ?? 'Unknown';

// Build email content
$content = createEmailTitle("Password Change Confirmation") . "
    
    <p>Hello <strong>{$username}</strong>,</p>
    
    <p>This email confirms that your password for {$siteName} has been successfully changed.</p>
    
    " . createSuccessBox('Your password has been updated successfully.') . "
    
    <p><strong>Change Details:</strong></p>
    <p>• Time: {$changeTime}<br>
    • IP Address: {$ipAddress}</p>
    
    " . createWarningBox('If you did not make this change, please contact our support team immediately and consider changing your password again.') . "
    
    " . createInfoBox('For your security, we recommend using a strong, unique password and enabling two-factor authentication if available.') . "
    
    <p>If you have any concerns about your account security, please don't hesitate to contact us.</p>
    
    <p>Best regards,<br>
    <strong>The {$siteName} Security Team</strong></p>";

// Email settings
$emailData = [
    'title' => 'Password Changed - ' . $siteName,
    'content' => $content,
    'siteName' => $siteName,
    'siteUrl' => $siteUrl,
    'footerText' => 'This is an automated security notification, please do not reply to this email.'
];

// Generate HTML
echo renderEmailTemplate($emailData);
?>
