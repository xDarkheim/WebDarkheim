<?php
/**
 * Password Changed Notification Email Template
 * Modern design for password change notifications
 */

require_once __DIR__ . '/_base_template.php';

// Email data
$username = $data['username'] ?? 'User';
$siteName = $data['siteName'] ?? 'Darkheim Development Studio';
$siteUrl = $data['siteUrl'] ?? 'https://darkheim.net';
$changeTime = $data['changeTime'] ?? date('Y-m-d H:i:s');
$ipAddress = $data['ipAddress'] ?? 'Unknown';

// Build email content
$content = createEmailTitle("Security Alert: Password Changed") . "
    
    <p>Hello <strong>{$username}</strong>,</p>
    
    <p>We're writing to inform you that your password for {$siteName} was recently changed.</p>
    
    " . createWarningBox('This is a security notification. If you made this change, no action is required.') . "
    
    <p><strong>Change Details:</strong></p>
    <p>• Time: {$changeTime}<br>
    • IP Address: {$ipAddress}<br>
    • Account: {$username}</p>
    
    " . createDangerBox('If you did NOT make this change, your account may be compromised. Please reset your password immediately and contact our support team.') . "
    
    " . createButton('Secure My Account', $siteUrl . '/auth/reset_password', 'danger') . "
    
    " . createInfoBox('Security Tips: Use a unique, strong password and enable two-factor authentication to protect your account.') . "
    
    <p>If you have any questions or concerns about your account security, please contact our support team immediately.</p>
    
    <p>Best regards,<br>
    <strong>The {$siteName} Security Team</strong></p>";

// Email settings
$emailData = [
    'title' => 'Security Alert: Password Changed - ' . $siteName,
    'content' => $content,
    'siteName' => $siteName,
    'siteUrl' => $siteUrl,
    'footerText' => 'This is an automated security alert, please do not reply to this email.'
];

// Generate HTML
echo renderEmailTemplate($emailData);
?>
