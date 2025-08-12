<?php
/**
 * Email Change Confirmation Template
 * Modern design for email change confirmations
 */

require_once __DIR__ . '/_base_template.php';

// Email data
$username = $data['username'] ?? 'User';
$confirmationLink = $data['confirmationLink'] ?? '#';
$newEmail = $data['newEmail'] ?? 'new@email.com';
$siteName = $data['siteName'] ?? 'Darkheim Development Studio';
$siteUrl = $data['siteUrl'] ?? 'https://darkheim.net';

// Build email content
$content = createEmailTitle("Confirm Email Address Change") . "
    
    <p>Hello <strong>{$username}</strong>,</p>
    
    <p>We received a request to change the email address for your {$siteName} account to:</p>
    
    <p><strong>{$newEmail}</strong></p>
    
    <p>To confirm this change, please click the button below:</p>
    
    " . createButton('Confirm Email Change', $confirmationLink, 'primary') . "
    
    " . createLinkFallback($confirmationLink) . "
    
    " . createWarningBox('This confirmation link will expire in 2 hours for security reasons.') . "
    
    " . createInfoBox('If you did not request this change, please ignore this email. Your current email address will remain unchanged.') . "
    
    <p>After confirmation, you will need to use your new email address to log into your account.</p>
    
    <p>Best regards,<br>
    <strong>The {$siteName} Team</strong></p>";

// Email settings
$emailData = [
    'title' => 'Confirm Email Change - ' . $siteName,
    'content' => $content,
    'siteName' => $siteName,
    'siteUrl' => $siteUrl,
    'footerText' => 'This is an automated message, please do not reply to this email.'
];

// Generate HTML
echo renderEmailTemplate($emailData);
?>
