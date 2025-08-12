<?php
/**
 * Registration Verification Email Template
 * Modern design for registration confirmation
 */

require_once __DIR__ . '/_base_template.php';

// Email data with multiple fallbacks for better compatibility
$username = $data['username'] ?? 'User';
$verificationLink = $data['verificationLink'] ?? $data['verification_link'] ?? $data['action_url'] ?? $data['url'] ?? $data['link'] ?? '#';
$siteName = $data['siteName'] ?? 'Darkheim Development Studio';
$siteUrl = $data['siteUrl'] ?? 'https://darkheim.net';
$expiresHours = $data['expires_hours'] ?? 24;

// Build email content
$content = createEmailTitle("Welcome to {$siteName}!") . "
    
    <p>Hello <strong>{$username}</strong>,</p>
    
    <p>Thank you for joining our development community! We're excited to have you on board. To complete your registration and unlock full access to your account, please verify your email address.</p>
    
    " . createButton('Verify Email Address', $verificationLink, 'success') . "
    
    " . createLinkFallback($verificationLink) . "
    
    " . createWarningBox('This verification link will expire in 24 hours for security reasons.') . "
    
    " . createInfoBox('Once verified, you\'ll have access to create articles, manage your profile, and engage with our community.') . "
    
    <p>If you didn't create an account on our website, please simply ignore this email.</p>
    
    <p>Best regards,<br>
    <strong>The {$siteName} Team</strong></p>";

// Email settings
$emailData = [
    'title' => 'Registration Verification - ' . $siteName,
    'content' => $content,
    'siteName' => $siteName,
    'siteUrl' => $siteUrl,
    'footerText' => 'This is an automated message, please do not reply to this email.'
];

// Generate HTML
echo renderEmailTemplate($emailData);
?>
