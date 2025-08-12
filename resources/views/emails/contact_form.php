<?php
/**
 * Contact Form Email Template
 * Modern design for contact form submissions
 */

require_once __DIR__ . '/_base_template.php';

// Email data
$name = $data['name'] ?? 'Visitor';
$email = $data['email'] ?? 'visitor@example.com';
$subject = $data['subject'] ?? 'Contact Form Submission';
$message = $data['message'] ?? 'No message provided';
$siteName = $data['siteName'] ?? 'Darkheim Development Studio';
$siteUrl = $data['siteUrl'] ?? 'https://darkheim.net';
$submissionTime = $data['submissionTime'] ?? date('Y-m-d H:i:s');

// Build email content
$content = createEmailTitle("New Contact Form Submission") . "
    
    <p>You have received a new message through the {$siteName} contact form.</p>
    
    " . createInfoBox('Contact Details:') . "
    
    <p><strong>Name:</strong> {$name}<br>
    <strong>Email:</strong> {$email}<br>
    <strong>Subject:</strong> {$subject}<br>
    <strong>Submitted:</strong> {$submissionTime}</p>
    
    " . createInfoBox('Message:') . "
    
    <p style='background: rgba(15, 23, 42, 0.6); border: 1px solid #475569; border-radius: 8px; padding: 16px; margin: 16px 0; font-style: italic; line-height: 1.6;'>{$message}</p>
    
    " . createButton('Reply to Message', 'mailto:' . $email . '?subject=Re: ' . urlencode($subject), 'primary') . "
    
    " . createInfoBox('You can reply directly to this email address: ' . $email) . "
    
    <p>This message was submitted through the contact form on {$siteName}.</p>";

// Email settings
$emailData = [
    'title' => 'Contact Form: ' . $subject . ' - ' . $siteName,
    'content' => $content,
    'siteName' => $siteName,
    'siteUrl' => $siteUrl,
    'footerText' => 'This is an automated notification from your website contact form.'
];

// Generate HTML
echo renderEmailTemplate($emailData);
?>
