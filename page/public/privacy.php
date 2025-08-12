<?php

/**
 * Privacy Policy Page
 *
 * This page provides information about how we collect, use, and protect your personal information.
 *
 * @author Dmytro Hovenko
 */
$pageTitle = 'Privacy Policy';

// Get settings from the database
global $site_settings_from_db;

// Extract main settings
$site_name = $site_settings_from_db['general']['site_name']['value'] ?? 'Darkheim Development Studio';
$company_legal_name = $site_settings_from_db['legal']['company_legal_name']['value'] ?? $site_name;
$site_description = $site_settings_from_db['general']['site_description']['value'] ?? 'Professional web development studio';
$site_url = $site_settings_from_db['general']['site_url']['value'] ?? 'https://darkheim.net';

// Extract contact settings
$contact_email = $site_settings_from_db['contact']['contact_email']['value'] ?? 'contact@darkheim.net';
$support_email = $site_settings_from_db['contact']['support_email']['value'] ?? 'support@darkheim.net';

// Extract special legal settings
$privacy_email = $site_settings_from_db['legal']['privacy_email']['value'] ?? 'privacy@darkheim.net';
$legal_email = $site_settings_from_db['legal']['legal_email']['value'] ?? 'legal@darkheim.net';
$data_protection_officer = $site_settings_from_db['legal']['data_protection_officer']['value'] ?? $privacy_email;
$privacy_policy_version = $site_settings_from_db['legal']['privacy_policy_version']['value'] ?? '1.0';
$privacy_policy_last_updated = $site_settings_from_db['legal']['privacy_policy_last_updated']['value'] ?? '2025-08-04';
$company_country = $site_settings_from_db['legal']['company_country']['value'] ?? 'International';
$data_retention_period = $site_settings_from_db['legal']['data_retention_period']['value'] ?? '24';

// Format last-updated date
$formatted_date = date('F j, Y', strtotime($privacy_policy_last_updated));
?>

    <!-- Corporate Hero -->
    <section class="legal-hero">
        <div class="container">
            <h1 class="legal-hero-title">Privacy Policy</h1>
            <p class="legal-hero-subtitle">How <?php echo htmlspecialchars($company_legal_name); ?> collects, uses, and protects your personal information</p>
            <div class="legal-meta">
                <span class="legal-date">Last updated: <?php echo htmlspecialchars($formatted_date); ?> (v<?php echo htmlspecialchars($privacy_policy_version); ?>)</span>
            </div>
        </div>
    </section>

    <!-- Corporate Sections -->
    <div class="legal-content">
        <section class="legal-section">
            <div class="container">
                <h2>Information We Collect</h2>
                <h3>Personal Information</h3>
                <p>We collect information you provide directly to us through <?php echo htmlspecialchars($site_name); ?>, such as when you:</p>
                <ul>
                    <li>Create an account on our website</li>
                    <li>Subscribe to our newsletter or updates</li>
                    <li>Contact us through our contact form</li>
                    <li>Participate in surveys or feedback forms</li>
                    <li>Post comments or content on our website</li>
                    <li>Request information about our services</li>
                    <li>Engage with our professional development services</li>
                </ul>
                
                <p>This information may include:</p>
                <ul>
                    <li>Name and email address</li>
                    <li>Username and password</li>
                    <li>Contact information and preferences</li>
                    <li>Professional information and project requirements</li>
                    <li>Payment information (processed securely through third parties)</li>
                    <li>Any other information you choose to provide</li>
                </ul>

                <h3>Automatically Collected Information</h3>
                <p>When you visit our website at <?php echo htmlspecialchars($site_url); ?>, we automatically collect certain information about your device and usage patterns, including:</p>
                <ul>
                    <li>IP address and browser type</li>
                    <li>Operating system and device information</li>
                    <li>Pages visited and time spent on our site</li>
                    <li>Referring to website and search terms</li>
                    <li>Cookies and similar tracking technologies</li>
                </ul>
            </div>
        </section>

        <section class="legal-section">
            <div class="container">
                <h2>How We Use Your Information</h2>
                <p><?php echo htmlspecialchars($company_legal_name); ?> uses the collected information for the following purposes:</p>
                <ul>
                    <li><strong>Service Provision:</strong> To provide, maintain, and improve our development services</li>
                    <li><strong>Communication:</strong> To respond to your inquiries and send important updates</li>
                    <li><strong>Project Management:</strong> To manage and deliver your software development projects</li>
                    <li><strong>Personalization:</strong> To customize your experience on our website</li>
                    <li><strong>Security:</strong> To protect against fraud, abuse, and security threats</li>
                    <li><strong>Analytics:</strong> To understand how our website is used and improve its performance</li>
                    <li><strong>Legal Compliance:</strong> To comply with applicable laws and regulations</li>
                </ul>
            </div>
        </section>

        <section class="legal-section">
            <div class="container">
                <h2>Data Retention</h2>
                <p><?php echo htmlspecialchars($company_legal_name); ?> retains your personal information for no longer than necessary to fulfill the purposes outlined in this Privacy Policy. Our standard data retention period is <?php echo htmlspecialchars($data_retention_period); ?> months, unless:</p>
                <ul>
                    <li>Law requires a longer retention period</li>
                    <li>You have given explicit consent for longer retention</li>
                    <li>We need to retain data to resolve disputes or enforce our agreements</li>
                    <li>Data is required for ongoing business relationships</li>
                </ul>

                <div class="legal-highlight">
                    <p>You can request deletion of your personal data at any time by contacting our Data Protection Officer at <?php echo htmlspecialchars($data_protection_officer); ?>.</p>
                </div>
            </div>
        </section>

        <section class="legal-section">
            <div class="container">
                <h2>Your Rights and Choices</h2>
                <p>Depending on your location and applicable laws (including GDPR for EU residents), you may have certain rights regarding your personal information:</p>
                <ul>
                    <li><strong>Access:</strong> Request access to the personal information we hold about you</li>
                    <li><strong>Correction:</strong> Request correction of inaccurate or incomplete information</li>
                    <li><strong>Deletion:</strong> Request deletion of your personal information</li>
                    <li><strong>Portability:</strong> Request a copy of your data in a structured format</li>
                    <li><strong>Opt-out:</strong> Unsubscribe from marketing communications</li>
                    <li><strong>Restriction:</strong> Request limitation of processing in certain circumstances</li>
                    <li><strong>Objection:</strong> Object to processing based on legitimate interests</li>
                </ul>
                <p>To exercise these rights, please contact our Data Protection Officer using the information provided below.</p>
            </div>
        </section>

        <section class="legal-section">
            <div class="container">
                <h2>International Data Transfers</h2>
                <p><?php echo htmlspecialchars($company_legal_name); ?> operates internationally from <?php echo htmlspecialchars($company_country); ?>. Your information may be transferred to and processed in countries other than your country of residence. We ensure that such transfers comply with applicable data protection laws and implement appropriate safeguards.</p>
            </div>
        </section>

        <section class="legal-section">
            <div class="container">
                <h2>Contact Us</h2>
                <p>If you have any questions, concerns, or requests regarding this Privacy Policy or our data practices, please contact us:</p>
                <div class="legal-contact-info">
                    <h4><?php echo htmlspecialchars($company_legal_name); ?></h4>
                    <p><strong>Data Protection Officer:</strong> <a href="mailto:<?php echo htmlspecialchars($data_protection_officer); ?>"><?php echo htmlspecialchars($data_protection_officer); ?></a></p>
                    <p><strong>Privacy Questions:</strong> <a href="mailto:<?php echo htmlspecialchars($privacy_email); ?>"><?php echo htmlspecialchars($privacy_email); ?></a></p>
                    <p><strong>General Contact:</strong> <a href="mailto:<?php echo htmlspecialchars($contact_email); ?>"><?php echo htmlspecialchars($contact_email); ?></a></p>
                    <p><strong>Support:</strong> <a href="mailto:<?php echo htmlspecialchars($support_email); ?>"><?php echo htmlspecialchars($support_email); ?></a></p>
                    <p><strong>Contact Form:</strong> <a href="/index.php?page=contact">Contact Us</a></p>
                    <p><strong>Website:</strong> <a href="<?php echo htmlspecialchars($site_url); ?>" target="_blank"><?php echo htmlspecialchars($site_url); ?></a></p>
                </div>
            </div>
        </section>
    </div>
    <br>
    <!-- Navigation between legal pages -->
    <section class="legal-navigation">
        <div class="container">
            <div class="legal-nav-grid">
                <a href="/index.php?page=terms" class="legal-nav-item">
                    <h3 class="legal-nav-title">Terms of Service</h3>
                    <p class="legal-nav-description">Read our terms and conditions governing the use of our services</p>
                </a>
                <a href="/index.php?page=contact" class="legal-nav-item">
                    <h3 class="legal-nav-title">Contact Us</h3>
                    <p class="legal-nav-description">Have questions? Get in touch with our team</p>
                </a>
            </div>
        </div>
    </section>
