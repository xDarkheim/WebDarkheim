<?php

/**
 * Contact page
 *
 * @author Dmytro Hovenko
 */

global $site_settings_from_db;

// Get settings from the database for the contact page

use App\Application\Core\ServiceProvider;
use App\Application\Core\SessionManager;
use App\Application\Helpers\SocialMediaHelper;
use App\Application\Middleware\CSRFMiddleware;

$site_name = $site_settings_from_db['general']['site_name']['value'] ?? 'Darkheim Development Studio';
$contact_email = $site_settings_from_db['contact']['contact_email']['value'] ?? 'contact@darkheim.net';
$support_email = $site_settings_from_db['contact']['support_email']['value'] ?? 'support@darkheim.net';

// Initialize our controllers: ServiceProvider + SessionManager
try {
    $services = ServiceProvider::getInstance();
    $sessionManager = SessionManager::getInstance(
        $services->getLogger(),
        [],
        $services->getConfigurationManager()
    );
    $sessionManager->start();
} catch (Throwable $e) {
    error_log('Contact page: failed to initialize services/session: ' . $e->getMessage());
}

// Pre-fetch CSRF token via our middleware
$csrfToken = CSRFMiddleware::getToken();

// Use new SocialMediaHelper instead of old config file
$socialNetworks = SocialMediaHelper::getAllSocialNetworks();
$hasSocialNetworks = !empty($socialNetworks);
?>

<div class="page-contact">
    <!-- Corporate Hero -->
    <div class="contact-hero">
        <div class="container">
            <div class="hero-badge">
                <span class="badge-text">CONTACT US</span>
            </div>
            <h1 class="contact-hero-title">Start Your Development Journey</h1>
            <p class="contact-hero-subtitle">Connect with our innovative development team to explore modern solutions for your software requirements and digital transformation goals</p>
        </div>
    </div>

    <!-- Contact Information -->
    <section class="contact-section contact-info-section">
        <div class="container">
            <div class="section-header">
                <h2>Get In Touch</h2>
                <p class="section-description">Multiple channels for professional communication and project inquiries</p>
            </div>

            <div class="contact-info-grid">
                <div class="contact-info-card card--tech">
                    <div class="contact-icon">📧</div>
                    <h3>Project Inquiries</h3>
                    <p>Discuss new development projects, requirements analysis, and technical specifications</p>
                    <a href="mailto:<?php echo htmlspecialchars($contact_email); ?>" class="contact-link">
                        <?php echo htmlspecialchars($contact_email); ?>
                    </a>
                </div>

                <?php if (!empty($support_email) && $support_email !== $contact_email): ?>
                <div class="contact-info-card card--tech">
                    <div class="contact-icon">🛠️</div>
                    <h3>Technical Support</h3>
                    <p>Maintenance, updates, and technical assistance for existing applications</p>
                    <a href="mailto:<?php echo htmlspecialchars($support_email); ?>" class="contact-link">
                        <?php echo htmlspecialchars($support_email); ?>
                    </a>
                </div>
                <?php endif; ?>

                <div class="contact-info-card card--tech">
                    <div class="contact-icon">💼</div>
                    <h3>Business Development</h3>
                    <p>Partnership opportunities, enterprise solutions, and long-term development contracts</p>
                    <a href="mailto:<?php echo htmlspecialchars($contact_email); ?>?subject=Business%20Partnership" class="contact-link">
                        Business Inquiries
                    </a>
                </div>

                <?php if ($hasSocialNetworks): ?>
                <div class="contact-info-card card--tech">
                    <div class="contact-icon">🌐</div>
                    <h3>Professional Network</h3>
                    <p>Connect with our team on professional platforms and review our open-source contributions</p>
                    <div class="contact-social-wrapper">
                        <span class="social-label">Follow us:</span>
                        <?php echo SocialMediaHelper::renderSocialLinks('contact-social-links'); ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Contact Form -->
    <section class="contact-section contact-form-section">
        <div class="container">
            <div class="contact-form-grid">
                <div class="contact-form-info">
                    <h3>Project Consultation</h3>
                    <p>Submit your project details and technical requirements. Our development team will review your inquiry and respond within 24 hours with a comprehensive analysis and proposed solution approach.</p>

                    <div class="consultation-features">
                        <div class="feature-item">
                            <div class="feature-icon">📋</div>
                            <div class="feature-content">
                                <h4>Requirements Analysis</h4>
                                <p>Detailed assessment of project scope and technical specifications</p>
                            </div>
                        </div>
                        <div class="feature-item">
                            <div class="feature-icon">💰</div>
                            <div class="feature-content">
                                <h4>Cost Estimation</h4>
                                <p>Transparent pricing structure based on project complexity and timeline</p>
                            </div>
                        </div>
                        <div class="feature-item">
                            <div class="feature-icon">📅</div>
                            <div class="feature-content">
                                <h4>Timeline Planning</h4>
                                <p>Realistic project milestones and delivery schedule planning</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="contact-form">
                    <h3>Submit Project Inquiry</h3>
                    <form id="contactForm" action="/index.php?page=form_contact" method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8'); ?>">

                        <div class="form-row">
                            <div class="form-group">
                                <label for="name" class="form-label">Full Name</label>
                                <input type="text" id="name" name="name" class="form-control" required maxlength="100" placeholder="Your full name">
                            </div>
                            <div class="form-group">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" id="email" name="email" class="form-control" required placeholder="professional@company.com">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="subject" class="form-label">Project Type</label>
                            <select id="subject" name="subject" class="form-control" required>
                                <option value="">Select project category</option>
                                <option value="Desktop Application Development">Desktop Application Development</option>
                                <option value="Web Application Development">Web Application Development</option>
                                <option value="Database Design & Optimization">Database Design & Optimization</option>
                                <option value="System Integration">System Integration</option>
                                <option value="Legacy System Modernization">Legacy System Modernization</option>
                                <option value="Technical Consultation">Technical Consultation</option>
                                <option value="Maintenance & Support">Maintenance & Support</option>
                                <option value="Custom Enterprise Solution">Custom Enterprise Solution</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="message" class="form-label">Project Details</label>
                            <textarea id="message" name="message" class="form-control" required maxlength="2000" placeholder="Describe your project requirements, technical specifications, expected timeline, and any specific technologies or constraints..."></textarea>
                        </div>

                        <button type="submit" class="button button--primary button--full" id="submitBtn">
                            <span id="btnText">Submit Inquiry</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Details -->
    <section class="contact-section contact-details">
        <div class="container">
            <div class="section-header">
                <h2>Contact Information</h2>
                <p class="section-description">Professional contact details and response expectations</p>
            </div>

            <div class="contact-details-grid">
                <div class="contact-detail">
                    <div class="contact-detail-icon">📧</div>
                    <div class="contact-detail-label">Primary Email</div>
                    <div class="contact-detail-value">
                        <a href="mailto:<?php echo htmlspecialchars($contact_email); ?>">
                            <?php echo htmlspecialchars($contact_email); ?>
                        </a>
                    </div>
                </div>

                <div class="contact-detail">
                    <div class="contact-detail-icon">⏱️</div>
                    <div class="contact-detail-label">Response Time</div>
                    <div class="contact-detail-value">Within 24 hours</div>
                </div>

                <div class="contact-detail">
                    <div class="contact-detail-icon">🌍</div>
                    <div class="contact-detail-label">Service Areas</div>
                    <div class="contact-detail-value">Global (Remote)</div>
                </div>

                <div class="contact-detail">
                    <div class="contact-detail-icon">🕒</div>
                    <div class="contact-detail-label">Business Hours</div>
                    <div class="contact-detail-value">Monday - Friday, 9:00 - 18:00</div>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    <section class="contact-section faq-section">
        <div class="container">
            <div class="section-header">
                <h2>Frequently Asked Questions</h2>
                <p class="section-description">Common inquiries about our development services and processes</p>
            </div>

            <div class="faq-grid">
                <div class="faq-item">
                    <div class="faq-question">What is your typical project timeline?</div>
                    <div class="faq-answer">Project timelines vary based on complexity and scope. Desktop applications typically require 2-6 months, web applications 1-4 months, and database projects 1-3 months. We provide detailed timeline estimates after requirements analysis.</div>
                </div>

                <div class="faq-item">
                    <div class="faq-question">Do you provide ongoing maintenance and support?</div>
                    <div class="faq-answer">Yes, we offer comprehensive maintenance packages including bug fixes, performance optimization, security updates, and feature enhancements. Support plans are customized based on your specific requirements.</div>
                </div>

                <div class="faq-item">
                    <div class="faq-question">What technologies do you specialize in?</div>
                    <div class="faq-answer">Our core expertise includes C++ with Qt framework for desktop applications, PHP with JavaScript for web development, and MySQL/SQL Server for database solutions. We stay current with industry best practices and emerging technologies.</div>
                </div>

                <div class="faq-item">
                    <div class="faq-question">Can you work with existing systems and legacy code?</div>
                    <div class="faq-answer">Absolutely. We have extensive experience in system integration, legacy modernization, and gradual migration strategies. We can work with your existing infrastructure while implementing modern improvements.</div>
                </div>

                <div class="faq-item">
                    <div class="faq-question">How do you ensure project quality and security?</div>
                    <div class="faq-answer">We follow industry-standard development practices including code reviews, automated testing, security audits, and comprehensive documentation. All projects undergo rigorous quality assurance before deployment.</div>
                </div>

                <div class="faq-item">
                    <div class="faq-question">What information do you need to provide a project estimate?</div>
                    <div class="faq-answer">We need detailed project requirements, expected functionality, target platforms, integration needs, timeline constraints, and any specific technical preferences. The more detailed the information, the more accurate our estimate.</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Corporate CTA -->
    <section class="contact-section corporate-cta">
        <div class="container">
            <div class="cta-content">
                <h2>Start Your Development Project</h2>
                <p class="cta-description">
                    Transform your business requirements into professional software solutions.
                    Our experienced development team is ready to analyze your needs and deliver scalable, reliable applications.
                </p>
                <div class="cta-actions">
                    <a href="mailto:<?php echo htmlspecialchars($contact_email); ?>?subject=Project%20Consultation" class="button button--primary button--lg">
                        Schedule Consultation
                    </a>
                    <div class="cta-contact">
                        <span class="contact-label">Prefer to call?</span>
                        <a href="mailto:<?php echo htmlspecialchars($contact_email); ?>?subject=Phone%20Consultation%20Request" class="contact-email">
                            Request Phone Consultation
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('contactForm');
    const submitBtn = document.getElementById('submitBtn');
    const btnText = document.getElementById('btnText');

    // Form submission
    form.addEventListener('submit', async function(e) {
        e.preventDefault();

        // Show loading state
        submitBtn.disabled = true;
        btnText.textContent = 'Submitting...';

        try {
            const formData = new FormData(form);
            // Include CSRF header and ensure cookies are sent
            const csrf = form.querySelector('input[name="csrf_token"]')?.value
                || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                || '';

            const response = await fetch('/index.php?page=form_contact', {
                method: 'POST',
                body: formData,
                headers: csrf ? { 'X-CSRF-Token': csrf } : {},
                credentials: 'same-origin'
            });

            let result = { success: false, message: 'Submission failed.' };
            try {
                const isJson = (response.headers.get('content-type') || '').includes('application/json');
                if (isJson) {
                    result = await response.json();
                } else {
                    const text = await response.text();
                    result = { success: response.ok, message: text || (response.ok ? 'Your inquiry has been submitted.' : 'Submission failed.') };
                }
            } catch (parseError) {
                console.error('Response parse error:', parseError);
            }

            if (result.success) {
                if (typeof showSuccess === 'function') {
                    showSuccess(result.message);
                }
                form.reset();
            } else {
                if (typeof showError === 'function') {
                    showError(result.message);
                }
            }
        } catch (error) {
            console.error('Form submission error:', error);
            if (typeof showError === 'function') {
                showError('Network error. Please check your connection and try again.');
            }
        } finally {
            submitBtn.disabled = false;
            btnText.textContent = 'Submit Inquiry';
        }
    });

    // Character counter for textarea
    const messageTextarea = document.getElementById('message');
    const maxLength = 2000;

    const charCounter = document.createElement('div');
    charCounter.className = 'char-counter';
    messageTextarea.parentNode.appendChild(charCounter);

    function updateCharCounter() {
        const remaining = maxLength - messageTextarea.value.length;
        charCounter.textContent = `${remaining} characters remaining`;
        charCounter.style.color = remaining < 100 ? 'var(--color-warning)' : 'var(--color-text-muted)';
    }

    messageTextarea.addEventListener('input', updateCharCounter);
    updateCharCounter();
});
</script>
</div>
