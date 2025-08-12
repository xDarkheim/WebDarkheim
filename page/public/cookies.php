<?php

/**
 * Cookie Policy Page
 *
 * This page provides information about the use of cookies on our website.
 *
 * @author Dmytro Hovenko
 */

$pageTitle = 'Cookie Policy';

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
$company_country = $site_settings_from_db['legal']['company_country']['value'] ?? 'International';

// Use the current date for cookie policy
$cookie_policy_last_updated = date('Y-m-d');
$formatted_date = date('F j, Y', strtotime($cookie_policy_last_updated));
?>

    <!-- Corporate Hero -->
    <section class="legal-hero">
        <div class="container">
            <h1 class="legal-hero-title">Cookie Policy</h1>
            <p class="legal-hero-subtitle">How <?php echo htmlspecialchars($company_legal_name); ?> uses cookies and similar technologies to enhance your browsing experience</p>
            <div class="legal-meta">
                <span class="legal-date">Last updated: <?php echo htmlspecialchars($formatted_date); ?></span>
            </div>
        </div>
    </section>

    <!-- Corporate Sections -->
    <div class="legal-content">
        <section class="legal-section">
            <div class="container">
                <h2>What Are Cookies</h2>
                <p>Cookies are small text files that are stored on your device (computer, tablet, or mobile) when you visit our website at <?php echo htmlspecialchars($site_url); ?>. They help us provide you with a better experience by remembering your preferences and understanding how you use our site.</p>

                <p>Cookies contain information transferred to your device's storage. They are widely used to make websites work more efficiently and to provide analytical information to website owners.</p>

                <div class="legal-highlight">
                    <p><strong>Important:</strong> Most web browsers automatically accept cookies, but you can modify your browser settings to decline cookies if you prefer. However, this may prevent you from taking full advantage of our website.</p>
                </div>
            </div>
        </section>

        <section class="legal-section">
            <div class="container">
                <h2>Types of Cookies We Use</h2>
                <p><?php echo htmlspecialchars($company_legal_name); ?> uses different types of cookies for various purposes:</p>

                <h3>Essential Cookies</h3>
                <p>These cookies are necessary for our website to function properly and cannot be switched off in our systems. They are usually only set in response to actions made by you, such as:</p>
                <ul>
                    <li>Logging into your account</li>
                    <li>Setting your privacy preferences</li>
                    <li>Filling in forms and submitting contact requests</li>
                    <li>Maintaining your session while browsing</li>
                    <li>Ensuring website security and preventing fraud</li>
                </ul>

                <h3>Performance and Analytics Cookies</h3>
                <p>These cookies help us understand how visitors interact with our website by collecting and reporting information anonymously. They help us:</p>
                <ul>
                    <li>Count visits and traffic sources</li>
                    <li>Measure and improve website performance</li>
                    <li>Understand which pages are most popular</li>
                    <li>Track user navigation patterns</li>
                    <li>Identify technical issues and optimize the user experience</li>
                </ul>

                <h3>Functional Cookies</h3>
                <p>These cookies enable enhanced functionality and personalization, such as:</p>
                <ul>
                    <li>Remembering your preferences and settings</li>
                    <li>Providing personalized content and recommendations</li>
                    <li>Remembering your login information (if you choose)</li>
                    <li>Maintaining your language and region preferences</li>
                    <li>Customizing your user interface experience</li>
                </ul>

                <h3>Targeting and Advertising Cookies</h3>
                <p>Currently, <?php echo htmlspecialchars($company_legal_name); ?> does not use advertising cookies. However, if we implement such functionality in the future, these cookies would:</p>
                <ul>
                    <li>Track your browsing habits and interests</li>
                    <li>Deliver relevant advertisements based on your preferences</li>
                    <li>Measure the effectiveness of advertising campaigns</li>
                    <li>Limit the number of times you see an advertisement</li>
                </ul>
            </div>
        </section>

        <section class="legal-section">
            <div class="container">
                <h2>Third-Party Cookies</h2>
                <p>Some cookies on our website are placed by third-party services that appear on our pages. We use reputable third-party services to enhance functionality:</p>

                <h3>Content Delivery Networks (CDN)</h3>
                <p>We use CDN services to deliver website assets efficiently:</p>
                <ul>
                    <li><strong>Font Awesome CDN:</strong> For icon fonts and styling</li>
                    <li><strong>Google Fonts:</strong> For web typography and font delivery</li>
                    <li><strong>Other CDN Services:</strong> For optimized content delivery</li>
                </ul>

                <h3>Analytics Services</h3>
                <p>We may use analytics services to understand website usage patterns:</p>
                <ul>
                    <li>Visitor behavior analysis</li>
                    <li>Performance monitoring</li>
                    <li>Technical error tracking</li>
                    <li>User experience optimization</li>
                </ul>

                <div class="legal-highlight">
                    <p><strong>Note:</strong> We carefully select third-party services and ensure they comply with applicable privacy laws and our data protection standards.</p>
                </div>
            </div>
        </section>

        <section class="legal-section">
            <div class="container">
                <h2>Cookie Duration and Storage</h2>
                <p>Cookies have different lifespans depending on their purpose:</p>

                <h3>Session Cookies</h3>
                <p>These temporary cookies are deleted when you close your browser. They help:</p>
                <ul>
                    <li>Maintain your session while browsing</li>
                    <li>Remember items in your temporary preferences</li>
                    <li>Ensure security during your visit</li>
                    <li>Track your navigation within a single session</li>
                </ul>

                <h3>Persistent Cookies</h3>
                <p>These cookies remain on your device for a specified period or until you delete them. They help:</p>
                <ul>
                    <li>Remember your preferences between visits</li>
                    <li>Provide personalized experiences</li>
                    <li>Maintain login sessions (if selected)</li>
                    <li>Analyze long-term usage patterns</li>
                </ul>

                <p><strong>Retention Period:</strong> Our persistent cookies typically expire after 12 months, though some essential cookies may have shorter or longer durations based on their function.</p>
            </div>
        </section>

        <section class="legal-section">
            <div class="container">
                <h2>Managing Your Cookie Preferences</h2>
                <p>You have several options for managing cookies on our website:</p>

                <h3>Browser Settings</h3>
                <p>Most web browsers allow you to control cookies through their settings. You can:</p>
                <ul>
                    <li>View which cookies are stored on your device</li>
                    <li>Delete existing cookies</li>
                    <li>Block cookies from specific websites</li>
                    <li>Block all cookies (not recommended)</li>
                    <li>Set preferences for accepting cookies</li>
                </ul>

                <h3>Browser-Specific Instructions</h3>
                <p>Here's how to manage cookies in popular browsers:</p>
                <ul>
                    <li><strong>Google Chrome:</strong> Settings > Privacy and security > Cookies and other site data</li>
                    <li><strong>Mozilla Firefox:</strong> Settings > Privacy & Security > Cookies and Site Data</li>
                    <li><strong>Microsoft Edge:</strong> Settings > Cookies and site permissions > Cookies and site data</li>
                    <li><strong>Safari:</strong> Preferences > Privacy > Manage Website Data</li>
                </ul>

                <h3>Opt-Out Tools</h3>
                <p>For analytics and advertising cookies, you may use industry opt-out tools:</p>
                <ul>
                    <li>Network Advertising Initiative (NAI) opt-out tool</li>
                    <li>Digital Advertising Alliance (DAA) WebChoices tool</li>
                    <li>European Interactive Digital Advertising Alliance (EDAA) opt-out tool</li>
                    <li>Google Analytics opt-out browser add-on</li>
                </ul>

                <div class="legal-highlight">
                    <p><strong>Important:</strong> Disabling certain cookies may impact website functionality and your user experience. Essential cookies cannot be disabled without affecting basic website operations.</p>
                </div>
            </div>
        </section>

        <section class="legal-section">
            <div class="container">
                <h2>Your Rights and Consent</h2>
                <p>Under applicable data protection laws, including GDPR, you have specific rights regarding cookies:</p>

                <h3>Consent Requirements</h3>
                <ul>
                    <li>We obtain your consent before placing non-essential cookies</li>
                    <li>You can withdraw consent at any time</li>
                    <li>We provide clear information about cookie purposes</li>
                    <li>You can choose which types of cookies to accept</li>
                </ul>

                <h3>Your Cookie Rights</h3>
                <ul>
                    <li><strong>Information:</strong> Right to know what cookies we use and why</li>
                    <li><strong>Access:</strong> Right to access information stored in cookies</li>
                    <li><strong>Control:</strong> Right to manage your cookie preferences</li>
                    <li><strong>Deletion:</strong> Right to delete cookies and related data</li>
                    <li><strong>Objection:</strong> Right to object to certain cookie uses</li>
                </ul>
            </div>
        </section>

        <section class="legal-section">
            <div class="container">
                <h2>Updates to This Cookie Policy</h2>
                <p><?php echo htmlspecialchars($company_legal_name); ?> may update this Cookie Policy from time to time to reflect changes in our practices, technology, or legal requirements. We will notify you of any material changes by:</p>
                <ul>
                    <li>Posting the updated policy on our website</li>
                    <li>Updating the "Last updated" date at the top of this page</li>
                    <li>Providing prominent notice of significant changes</li>
                    <li>Requesting renewed consent where legally required</li>
                </ul>

                <p>We encourage you to review this Cookie Policy periodically to stay informed about how we use cookies and similar technologies.</p>
            </div>
        </section>

        <section class="legal-section">
            <div class="container">
                <h2>Contact Us</h2>
                <p>If you have any questions, concerns, or requests regarding this Cookie Policy or our use of cookies, please contact us:</p>
                <div class="legal-contact-info">
                    <h4><?php echo htmlspecialchars($company_legal_name); ?></h4>
                    <p><strong>Data Protection Officer:</strong> <a href="mailto:<?php echo htmlspecialchars($data_protection_officer); ?>"><?php echo htmlspecialchars($data_protection_officer); ?></a></p>
                    <p><strong>Privacy Questions:</strong> <a href="mailto:<?php echo htmlspecialchars($privacy_email); ?>"><?php echo htmlspecialchars($privacy_email); ?></a></p>
                    <p><strong>General Contact:</strong> <a href="mailto:<?php echo htmlspecialchars($contact_email); ?>"><?php echo htmlspecialchars($contact_email); ?></a></p>
                    <p><strong>Support:</strong> <a href="mailto:<?php echo htmlspecialchars($support_email); ?>"><?php echo htmlspecialchars($support_email); ?></a></p>
                    <p><strong>Contact Form:</strong> <a href="/index.php?page=contact">Contact Us</a></p>
                    <p><strong>Website:</strong> <a href="<?php echo htmlspecialchars($site_url); ?>" target="_blank"><?php echo htmlspecialchars($site_url); ?></a></p>
                </div>

                <div class="legal-highlight">
                    <p><em>By continuing to use our website, you acknowledge that you have read and understood this Cookie Policy and consented to our use of cookies as described herein.</em></p>
                </div>
            </div>
        </section>
    </div>
    <br>
    <!-- Navigation between legal pages -->
    <section class="legal-navigation">
        <div class="container">
            <div class="legal-nav-grid">
                <a href="/index.php?page=privacy" class="legal-nav-item">
                    <h3 class="legal-nav-title">Privacy Policy</h3>
                    <p class="legal-nav-description">Learn how we collect, use, and protect your personal information</p>
                </a>
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
