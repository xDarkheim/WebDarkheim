<?php
/**
 * Password Reset Request Email Template
 * Modern design for password reset requests
 */

require_once __DIR__ . '/_base_template.php';

// Email data with multiple fallbacks for better compatibility
$username = $data['username'] ?? 'User';
$resetLink = $data['resetLink'] ?? $data['reset_link'] ?? $data['reset_url'] ?? $data['action_url'] ?? $data['url'] ?? $data['link'] ?? '#';
$siteName = $data['siteName'] ?? 'Darkheim Development Studio';
$siteUrl = $data['siteUrl'] ?? 'https://darkheim.net';
$expiresMinutes = $data['expires_minutes'] ?? 60;

// Build email content
$content = createEmailTitle("Password Reset Request") . "
    
    <p>Hello <strong>{$username}</strong>,</p>
    
    <p>We received a request to reset the password for your {$siteName} account. If you didn't make this request, you can safely ignore this email.</p>
    
    <p>To reset your password, click the button below:</p>
    
    " . createButton('Reset Password', $resetLink, 'primary') . "
    
    " . createLinkFallback($resetLink) . "
    
    " . createWarningBox('This password reset link will expire in 1 hour for security reasons.') . "
    
    " . createInfoBox('If you continue to have problems accessing your account, please contact our support team.') . "
    
    <p>For your security, this request was made from the IP address that was used to access your account.</p>
    
    <p>Best regards,<br>
    <strong>The {$siteName} Team</strong></p>";

// Email settings
$emailData = [
    'title' => 'Password Reset Request - ' . $siteName,
    'content' => $content,
    'siteName' => $siteName,
    'siteUrl' => $siteUrl,
    'footerText' => 'This is an automated message, please do not reply to this email.'
];

// Generate HTML
echo renderEmailTemplate($emailData);
?>
