<?php

/**
 * 404 Page
 *
 * This page is displayed when a user attempts to access a page that does not exist.
 * It provides a user-friendly error message and navigation options.
 *
 * @author Dmytro Hovenko
 */

// Get global site settings
global $site_settings_from_db;

$site_name = $site_settings_from_db['general']['site_name']['value'] ?? 'Darkheim Development Studio';
$errorMessage = [
    'error' => ['Unfortunately, the page you requested does not exist.']
];
?>

<!-- Corporate 404 Page -->
    <!-- Strict Corporate Hero Section -->
    <section class="corporate-hero">
        <div class="container">
            <div class="hero-content">
                <div class="hero-badge">
                    <span class="badge-text">ERROR 404</span>
                </div>

                <h1 class="corporate-title">
                    Page Not
                    <span class="title-accent">Found</span>
                </h1>

                <p class="corporate-subtitle">
                    The requested page could not be found on our server.
                    Please check the URL or navigate to one of our main sections.
                </p>

                <div class="hero-actions">
                    <a href="/index.php?page=home" class="button button--primary button--lg">
                        Return to Home
                    </a>
                    <a href="javascript:history.back()" class="button button--secondary button--lg">
                        Go Back
                    </a>
                </div>
            </div>
        </div>
    </section>