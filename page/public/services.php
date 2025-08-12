<?php

/**
 * Services page
 *
 * @author Dmytro Hovenko
 */

global $site_settings_from_db;

// Get settings from the database
$site_name = $site_settings_from_db['general']['site_name']['value'] ?? 'Darkheim Development Studio';
$site_description = $site_settings_from_db['general']['site_description']['value'] ?? 'Professional software development studio';
$contact_email = $site_settings_from_db['contact']['contact_email']['value'] ?? 'contact@darkheim.net';

// Get service type from URL parameters
$service_type = $_GET['type'] ?? 'all';

// Define information about service types
$service_types = [
    'all' => [
        'title' => 'Our Development Services',
        'subtitle' => 'Comprehensive software development solutions',
        'description' => 'End-to-end development services from consultation to deployment, covering desktop applications, web platforms, and technical support.',
        'icon' => '🔧'
    ],
    'consulting' => [
        'title' => 'Technical Consulting',
        'subtitle' => 'Expert guidance for your development projects',
        'description' => 'Strategic technology consulting, architecture planning, and technical decision-making support for your software development initiatives.',
        'icon' => '💡'
    ],
    'development' => [
        'title' => 'Custom Development',
        'subtitle' => 'Tailored software solutions for your business',
        'description' => 'Full-cycle custom software development including desktop applications, web platforms, database design, and system integration.',
        'icon' => '⚙️'
    ],
    'support' => [
        'title' => 'Technical Support & Maintenance',
        'subtitle' => 'Ongoing support for your software systems',
        'description' => 'Comprehensive maintenance, updates, performance optimization, and technical support for existing applications and systems.',
        'icon' => '🛠️'
    ]
];

$current_service = $service_types[$service_type] ?? $service_types['all'];
?>

<div class="page-services">
    <!-- Hero Section -->
    <section class="services-hero">
        <div class="container">
            <div class="hero-badge">
                <span class="badge-text">DEVELOPMENT SERVICES</span>
            </div>
            <div class="hero-icon"><?php echo $current_service['icon']; ?></div>
            <h1 class="services-hero-title"><?php echo htmlspecialchars($current_service['title']); ?></h1>
            <p class="services-hero-subtitle"><?php echo htmlspecialchars($current_service['subtitle']); ?></p>
            <p class="services-hero-description"><?php echo htmlspecialchars($current_service['description']); ?></p>
        </div>
    </section>

    <!-- Service Type Filter -->
    <section class="services-filter">
        <div class="container">
            <div class="filter-tabs">
                <a href="/index.php?page=services" 
                   class="filter-tab <?php echo $service_type === 'all' ? 'active' : ''; ?>">
                    🔧 All Services
                </a>
                <a href="/index.php?page=services&type=consulting" 
                   class="filter-tab <?php echo $service_type === 'consulting' ? 'active' : ''; ?>">
                    💡 Consulting
                </a>
                <a href="/index.php?page=services&type=development" 
                   class="filter-tab <?php echo $service_type === 'development' ? 'active' : ''; ?>">
                    ⚙️ Development
                </a>
                <a href="/index.php?page=services&type=support" 
                   class="filter-tab <?php echo $service_type === 'support' ? 'active' : ''; ?>">
                    🛠️ Support
                </a>
            </div>
        </div>
    </section>

    <!-- Services Content -->
    <section class="services-content">
        <div class="container">
            <?php if ($service_type === 'all' || $service_type === 'consulting'): ?>
            <!-- Consulting Services -->
            <div class="service-section">
                <div class="service-header">
                    <div class="service-icon">💡</div>
                    <div class="service-info">
                        <h2>Technical Consulting</h2>
                        <p class="service-tagline">Strategic guidance for informed technology decisions</p>
                    </div>
                </div>

                <div class="service-grid">
                    <div class="service-card">
                        <h3>Architecture Planning</h3>
                        <p>System architecture design and technology stack selection for optimal performance and scalability.</p>
                        <ul class="service-features">
                            <li>Technology stack evaluation</li>
                            <li>System architecture design</li>
                            <li>Performance optimization planning</li>
                            <li>Scalability assessment</li>
                        </ul>
                        <div class="service-price">From $35/hour</div>
                    </div>

                    <div class="service-card">
                        <h3>Code Review & Audit</h3>
                        <p>Comprehensive code quality assessment and security audit for existing applications.</p>
                        <ul class="service-features">
                            <li>Code quality assessment</li>
                            <li>Security vulnerability analysis</li>
                            <li>Performance bottleneck identification</li>
                            <li>Best practices recommendations</li>
                        </ul>
                        <div class="service-price">From $30/hour</div>
                    </div>

                    <div class="service-card">
                        <h3>Technical Strategy</h3>
                        <p>Long-term technology roadmap planning and digital transformation guidance.</p>
                        <ul class="service-features">
                            <li>Technology roadmap development</li>
                            <li>Digital transformation planning</li>
                            <li>Team structure recommendations</li>
                            <li>Process optimization</li>
                        </ul>
                        <div class="service-price">From $40/hour</div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($service_type === 'all' || $service_type === 'development'): ?>
            <!-- Development Services -->
            <div class="service-section">
                <div class="service-header">
                    <div class="service-icon">⚙️</div>
                    <div class="service-info">
                        <h2>Custom Development</h2>
                        <p class="service-tagline">End-to-end software development solutions</p>
                    </div>
                </div>

                <div class="service-grid">
                    <div class="service-card">
                        <h3>Desktop Applications</h3>
                        <p>Cross-platform desktop software built with C++ and Qt framework for professional performance.</p>
                        <ul class="service-features">
                            <li>Windows, macOS, Linux compatibility</li>
                            <li>Native performance optimization</li>
                            <li>Professional UI/UX design</li>
                            <li>Database integration</li>
                        </ul>
                        <div class="service-price">From $2,500</div>
                    </div>

                    <div class="service-card">
                        <h3>Web Applications</h3>
                        <p>Modern web platforms use PHP and JavaScript with responsive design and robust backend.</p>
                        <ul class="service-features">
                            <li>Responsive web design</li>
                            <li>Progressive Web Apps</li>
                            <li>API development</li>
                            <li>Cloud deployment</li>
                        </ul>
                        <div class="service-price">From $1,500</div>
                    </div>

                    <div class="service-card">
                        <h3>Database Solutions</h3>
                        <p>Enterprise-grade database design, optimization, and integration for reliable data management.</p>
                        <ul class="service-features">
                            <li>Schema design & optimization</li>
                            <li>Performance tuning</li>
                            <li>Backup & recovery setup</li>
                            <li>Security implementation</li>
                        </ul>
                        <div class="service-price">From $800</div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($service_type === 'all' || $service_type === 'support'): ?>
            <!-- Support Services -->
            <div class="service-section">
                <div class="service-header">
                    <div class="service-icon">🛠️</div>
                    <div class="service-info">
                        <h2>Technical Support & Maintenance</h2>
                        <p class="service-tagline">Ongoing support for optimal system performance</p>
                    </div>
                </div>

                <div class="service-grid">
                    <div class="service-card">
                        <h3>Application Maintenance</h3>
                        <p>Regular maintenance, updates, and bug fixes to keep your applications running smoothly.</p>
                        <ul class="service-features">
                            <li>Bug fixes and patches</li>
                            <li>Security updates</li>
                            <li>Performance monitoring</li>
                            <li>Compatibility updates</li>
                        </ul>
                        <div class="service-price">From $25/hour</div>
                    </div>

                    <div class="service-card">
                        <h3>System Optimization</h3>
                        <p>Performance tuning and optimization services for existing applications and databases.</p>
                        <ul class="service-features">
                            <li>Performance analysis</li>
                            <li>Code optimization</li>
                            <li>Database tuning</li>
                            <li>Resource optimization</li>
                        </ul>
                        <div class="service-price">From $35/hour</div>
                    </div>

                    <div class="service-card">
                        <h3>Legacy System Support</h3>
                        <p>Maintenance and modernization support for legacy systems and older applications.</p>
                        <ul class="service-features">
                            <li>Legacy code maintenance</li>
                            <li>Modernization planning</li>
                            <li>Migration assistance</li>
                            <li>Documentation updates</li>
                        </ul>
                        <div class="service-price">From $30/hour</div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Process Overview -->
    <section class="services-process">
        <div class="container">
            <div class="section-header">
                <h2>Our Development Process</h2>
                <p class="section-description">
                    Structured approach ensuring quality delivery and client satisfaction
                </p>
            </div>

            <div class="process-steps">
                <div class="process-step">
                    <div class="step-number">1</div>
                    <div class="step-content">
                        <h3>Requirements Analysis</h3>
                        <p>Detailed analysis of your business requirements, technical specifications, and project scope.</p>
                    </div>
                </div>

                <div class="process-step">
                    <div class="step-number">2</div>
                    <div class="step-content">
                        <h3>Planning & Design</h3>
                        <p>System architecture design, technology selection, and project timeline development.</p>
                    </div>
                </div>

                <div class="process-step">
                    <div class="step-number">3</div>
                    <div class="step-content">
                        <h3>Development & Testing</h3>
                        <p>Iterative development with regular testing, code reviews, and quality assurance.</p>
                    </div>
                </div>

                <div class="process-step">
                    <div class="step-number">4</div>
                    <div class="step-content">
                        <h3>Deployment & Support</h3>
                        <p>Production deployment, documentation delivery, and ongoing maintenance support.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Service Packages -->
    <section class="services-packages">
        <div class="container">
            <div class="section-header">
                <h2>Service Packages</h2>
                <p class="section-description">
                    Flexible service packages designed to meet different project needs and budgets
                </p>
            </div>

            <div class="packages-grid">
                <div class="package-card">
                    <div class="package-header">
                        <h3>Starter Package</h3>
                        <div class="package-price">$800 - $2,500</div>
                    </div>
                    <div class="package-features">
                        <ul>
                            <li>Small web applications</li>
                            <li>Basic database design</li>
                            <li>Responsive frontend</li>
                            <li>3-month support</li>
                            <li>Documentation included</li>
                        </ul>
                    </div>
                    <div class="package-action">
                        <a href="/index.php?page=contact" class="button button--secondary">Get Quote</a>
                    </div>
                </div>

                <div class="package-card package-card--featured">
                    <div class="package-badge">Most Popular</div>
                    <div class="package-header">
                        <h3>Professional Package</h3>
                        <div class="package-price">$2,500 - $8,000</div>
                    </div>
                    <div class="package-features">
                        <ul>
                            <li>Desktop or web applications</li>
                            <li>Advanced database integration</li>
                            <li>Custom UI/UX design</li>
                            <li>6-month support</li>
                            <li>Training included</li>
                        </ul>
                    </div>
                    <div class="package-action">
                        <a href="/index.php?page=contact" class="button button--primary">Get Quote</a>
                    </div>
                </div>

                <div class="package-card">
                    <div class="package-header">
                        <h3>Enterprise Package</h3>
                        <div class="package-price">$8,000+</div>
                    </div>
                    <div class="package-features">
                        <ul>
                            <li>Complex enterprise solutions</li>
                            <li>Multi-platform development</li>
                            <li>System integration</li>
                            <li>12-month support</li>
                            <li>Ongoing maintenance</li>
                        </ul>
                    </div>
                    <div class="package-action">
                        <a href="/index.php?page=contact" class="button button--secondary">Contact Us</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Call to Action -->
    <section class="services-cta">
        <div class="container">
            <div class="cta-content">
                <h2>Ready to Discuss Your Project?</h2>
                <p class="cta-description">
                    Let's explore how our development services can help achieve your business goals.
                    Schedule a consultation to discuss your requirements and get a detailed project proposal.
                </p>
                <div class="cta-actions">
                    <a href="/index.php?page=contact" class="button button--primary button--lg">
                        Schedule Consultation
                    </a>
                    <a href="/index.php?page=projects" class="button button--secondary button--lg">
                        View Our Portfolio
                    </a>
                </div>
                <div class="cta-contact">
                    <span class="contact-label">Questions?</span>
                    <a href="mailto:<?php echo htmlspecialchars($contact_email); ?>" class="contact-email">
                        <?php echo htmlspecialchars($contact_email); ?>
                    </a>
                </div>
            </div>
        </div>
    </section>
</div>
