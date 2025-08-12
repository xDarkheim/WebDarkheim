<?php
/**
 * Base Email Template for Darkheim Development Studio
 * Minimalist design inspired by GitHub emails
 */

function renderEmailTemplate($data) {
    // Default values
    $siteName = $data['siteName'] ?? 'Darkheim Development Studio';
    $siteUrl = $data['siteUrl'] ?? 'https://darkheim.net';
    $title = $data['title'] ?? 'Email from Darkheim';
    $content = $data['content'] ?? '';
    $footerText = $data['footerText'] ?? 'You\'re receiving this because you have an account on Darkheim.';
    
    // Minimal color scheme like GitHub
    $colors = [
        'primary' => '#0969da',
        'success' => '#1a7f37',
        'danger' => '#d1242f',
        'warning' => '#9a6700',
        'border' => '#d0d7de',
        'bg_canvas' => '#ffffff',
        'bg_subtle' => '#f6f8fa',
        'text_primary' => '#24292f',
        'text_secondary' => '#656d76',
        'text_muted' => '#8c959f'
    ];

    $html = "<!DOCTYPE html>
<html lang=\"en\">
<head>
    <meta charset=\"UTF-8\">
    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
    <meta http-equiv=\"X-UA-Compatible\" content=\"IE=edge\">
    <title>{$title}</title>
    <style type=\"text/css\">
        /* Reset */
        body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
        img { border: 0; height: auto; line-height: 100%; outline: none; text-decoration: none; }
        
        /* Base styles */
        body {
            margin: 0 !important;
            padding: 0 !important;
            background-color: {$colors['bg_subtle']} !important;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Noto Sans', Helvetica, Arial, sans-serif;
            font-size: 14px;
            line-height: 1.5;
            color: {$colors['text_primary']};
        }
        
        table { border-collapse: collapse !important; }
        
        /* Container */
        .email-wrapper {
            width: 100%;
            background-color: {$colors['bg_subtle']};
            padding: 40px 20px;
        }
        
        .email-container {
            max-width: 544px;
            margin: 0 auto;
            background: {$colors['bg_canvas']};
            border: 1px solid {$colors['border']};
            border-radius: 6px;
        }
        
        /* Header */
        .email-header {
            padding: 20px 30px;
            border-bottom: 1px solid {$colors['border']};
            text-align: center;
        }
        
        .logo {
            font-size: 18px;
            font-weight: 600;
            color: {$colors['text_primary']};
            text-decoration: none;
        }
        
        /* Content */
        .email-content {
            padding: 30px;
        }
        
        .email-title {
            font-size: 20px;
            font-weight: 600;
            color: {$colors['text_primary']};
            margin: 0 0 16px 0;
            line-height: 1.25;
        }
        
        .email-content p {
            margin: 0 0 16px 0;
            color: {$colors['text_primary']};
            font-size: 14px;
            line-height: 1.5;
        }
        
        .email-content strong {
            font-weight: 600;
        }
        
        /* Buttons */
        .button-container {
            margin: 24px 0;
        }
        
        .button {
            display: inline-block;
            background: {$colors['primary']};
            color: #ffffff !important;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 6px;
            font-weight: 500;
            font-size: 14px;
            line-height: 20px;
            border: 1px solid rgba(27, 31, 36, 0.15);
        }
        
        .button-success {
            background: {$colors['success']};
            border-color: rgba(27, 31, 36, 0.15);
        }
        
        .button-danger {
            background: {$colors['danger']};
            border-color: rgba(27, 31, 36, 0.15);
        }
        
        /* Info boxes */
        .info-box, .warning-box, .success-box, .danger-box {
            padding: 16px;
            margin: 16px 0;
            border-radius: 6px;
            border-left: 3px solid;
        }
        
        .info-box {
            background: #dbeafe;
            border-left-color: {$colors['primary']};
        }
        
        .warning-box {
            background: #fef3c7;
            border-left-color: {$colors['warning']};
        }
        
        .success-box {
            background: #dcfce7;
            border-left-color: {$colors['success']};
        }
        
        .danger-box {
            background: #fee2e2;
            border-left-color: {$colors['danger']};
        }
        
        .info-box p, .warning-box p, .success-box p, .danger-box p {
            margin: 0;
            font-size: 14px;
            color: {$colors['text_primary']};
        }
        
        /* Code/Link blocks */
        .link-fallback {
            background: {$colors['bg_subtle']};
            border: 1px solid {$colors['border']};
            border-radius: 6px;
            padding: 16px;
            margin: 16px 0;
            font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, monospace;
            font-size: 12px;
            word-break: break-all;
        }
        
        .link-fallback p {
            margin: 0 0 8px 0;
            font-size: 12px;
            color: {$colors['text_secondary']};
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Noto Sans', Helvetica, Arial, sans-serif;
        }
        
        .link-fallback a {
            color: {$colors['primary']};
            text-decoration: none;
        }
        
        /* Footer */
        .email-footer {
            padding: 20px 30px;
            border-top: 1px solid {$colors['border']};
            background: {$colors['bg_subtle']};
        }
        
        .footer-text {
            color: {$colors['text_secondary']};
            font-size: 12px;
            line-height: 1.5;
            margin: 0 0 8px 0;
        }
        
        .footer-links {
            margin: 8px 0 0 0;
        }
        
        .footer-links a {
            color: {$colors['primary']};
            text-decoration: none;
            font-size: 12px;
            margin-right: 16px;
        }
        
        /* Responsive */
        @media only screen and (max-width: 600px) {
            .email-wrapper {
                padding: 20px 10px;
            }
            
            .email-container {
                margin: 0;
            }
            
            .email-header,
            .email-content,
            .email-footer {
                padding: 20px;
            }
            
            .footer-links a {
                display: block;
                margin: 4px 0;
            }
        }
    </style>
</head>
<body>
    <table role=\"presentation\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\" width=\"100%\" class=\"email-wrapper\">
        <tr>
            <td>
                <table role=\"presentation\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\" class=\"email-container\">
                    <!-- Header -->
                    <tr>
                        <td class=\"email-header\">
                            <a href=\"{$siteUrl}\" class=\"logo\">{$siteName}</a>
                        </td>
                    </tr>
                    
                    <!-- Content -->
                    <tr>
                        <td class=\"email-content\">
                            {$content}
                        </td>
                    </tr>
                    
                    <!-- Footer -->
                    <tr>
                        <td class=\"email-footer\">
                            <p class=\"footer-text\">{$footerText}</p>
                            <div class=\"footer-links\">
                                <a href=\"{$siteUrl}\">Darkheim</a>
                                <a href=\"{$siteUrl}/contact\">Contact</a>
                                <a href=\"{$siteUrl}/privacy\">Privacy</a>
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>";

    return $html;
}

/**
 * Helper functions for creating email components
 */

function createButton($text, $url, $type = 'primary') {
    $class = 'button';
    if ($type !== 'primary') {
        $class .= ' button-' . $type;
    }
    
    return "<div class=\"button-container\"><a href=\"{$url}\" class=\"{$class}\">{$text}</a></div>";
}

function createLinkFallback($url) {
    return "<div class=\"link-fallback\"><p>If the button above doesn't work, copy and paste this URL into your browser:</p><a href=\"{$url}\">{$url}</a></div>";
}

function createWarningBox($text) {
    return "<div class=\"warning-box\"><p>{$text}</p></div>";
}

function createInfoBox($text) {
    return "<div class=\"info-box\"><p>{$text}</p></div>";
}

function createSuccessBox($text) {
    return "<div class=\"success-box\"><p>{$text}</p></div>";
}

function createDangerBox($text) {
    return "<div class=\"danger-box\"><p>{$text}</p></div>";
}

function createEmailTitle($title) {
    return "<h1 class=\"email-title\">{$title}</h1>";
}
?>
