<?php

/**
 * About Page
 *
 * This page provides information about the company and its services.
 *
 * @author Dmytro Hovenko
 */


global $site_settings_from_db;

// Get settings from the database for the About page
$site_name = $site_settings_from_db['general']['site_name']['value'] ?? 'Darkheim Development Studio';
$site_description = $site_settings_from_db['general']['site_description']['value'] ?? 'Professional software development studio';
$site_tagline = $site_settings_from_db['general']['site_tagline']['value'] ?? 'Professional Web Development Solutions';
$contact_email = $site_settings_from_db['contact']['contact_email']['value'] ?? 'contact@darkheim.net';
?>

<div class="page-about">
<!-- Corporate About Page -->
    <!-- Corporate Hero -->
    <div class="about-hero">
        <div class="container">
            <div class="hero-badge">
                <span class="badge-text">ABOUT US</span>
            </div>
            <h1 class="about-hero-title"><?php echo htmlspecialchars(
                $site_name); ?></h1>
            <p class="about-hero-subtitle">Building the future of software development with cutting-edge technology and proven expertise</p>
        </div>
    </div>

    <!-- Main company information -->
    <section class="about-section about-intro">
        <div class="container">
            <div class="about-content-grid">
                <div class="about-text">
                    <h2>Innovative Software Development Studio</h2>
                    <p class="lead">Founded in 2025, we are a fresh, dynamic development studio bringing modern approaches to software creation with cutting-edge technologies and contemporary development practices.</p>
                    <p>As a new player in the software development landscape, we focus on delivering high-quality desktop applications using C++ and Qt framework, alongside responsive web applications built with PHP and JavaScript. Our fresh perspective allows us to implement the latest industry standards and innovative solutions from day one.</p>

                    <div class="about-features">
                        <div class="feature-item">
                            <div class="feature-icon">🚀</div>
                            <div class="feature-content">
                                <h4>Modern Approach</h4>
                                <p>Latest technologies and contemporary development methodologies</p>
                            </div>
                        </div>
                        <div class="feature-item">
                            <div class="feature-icon">💡</div>
                            <div class="feature-content">
                                <h4>Innovation-Focused</h4>
                                <p>Fresh perspectives and creative solutions for complex challenges</p>
                            </div>
                        </div>
                        <div class="feature-item">
                            <div class="feature-icon">🎯</div>
                            <div class="feature-content">
                                <h4>Quality-Driven</h4>
                                <p>Committed to excellence in every line of code we write</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="about-stats">
                    <div class="stat-card">
                        <div class="stat-number">2025</div>
                        <div class="stat-label">Established</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">10+</div>
                        <div class="stat-label">Projects in Development</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">100%</div>
                        <div class="stat-label">Modern Stack</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">24/7</div>
                        <div class="stat-label">Development Focus</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Corporate services -->
    <section class="about-section services-overview">
        <div class="container">
            <div class="section-header">
                <h2>Core Competencies</h2>
                <p class="section-description">Specialized expertise across key technology domains</p>
            </div>

            <div class="expertise-grid">
                <div class="expertise-card card--tech">
                    <div class="service-header">
                        <div class="expertise-icon">🖥️</div>
                        <h3>Desktop Applications</h3>
                    </div>
                    <p>Cross-platform native applications built with C++ and Qt framework. High-performance solutions with modern, intuitive user interfaces designed for professional environments.</p>
                    <div class="service-features">
                        <div class="feature-item">Windows, macOS, Linux compatibility</div>
                        <div class="feature-item">Native performance optimization</div>
                        <div class="feature-item">Professional UI/UX design</div>
                        <div class="feature-item">Database integration capabilities</div>
                    </div>
                </div>

                <div class="expertise-card card--tech">
                    <div class="service-header">
                        <div class="expertise-icon">🌐</div>
                        <h3>Web Development</h3>
                    </div>
                    <p>Modern web applications using PHP backend with JavaScript frontend. Responsive, scalable solutions designed for reliability and performance in enterprise environments.</p>
                    <div class="service-features">
                        <div class="feature-item">Progressive Web Applications</div>
                        <div class="feature-item">API development & integration</div>
                        <div class="feature-item">Cloud-ready architecture</div>
                        <div class="feature-item">Mobile-responsive design</div>
                    </div>
                </div>

                <div class="expertise-card card--tech">
                    <div class="service-header">
                        <div class="expertise-icon">🗄️</div>
                        <h3>Database Solutions</h3>
                    </div>
                    <p>Enterprise-grade database design and optimization for MySQL and Microsoft SQL Server. Robust data architecture ensuring reliability, security, and scalability.</p>
                    <div class="service-features">
                        <div class="feature-item">Schema design & optimization</div>
                        <div class="feature-item">Performance tuning & indexing</div>
                        <div class="feature-item">Backup & recovery strategies</div>
                        <div class="feature-item">Security & compliance</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Technology stack -->
    <section class="about-section tech-stack-section">
        <div class="container">
            <div class="section-header">
                <h2>Technology Stack</h2>
                <p class="section-description">Industry-standard tools and frameworks for professional development</p>
            </div>

            <div class="tech-categories">
                <div class="tech-category">
                    <h3>Programming Languages</h3>
                    <div class="tech-list">
                        <li>C++ - High-performance application development</li>
                        <li>PHP - Server-side web development</li>
                        <li>JavaScript - Frontend interactivity</li>
                        <li>SQL - Database query optimization</li>
                    </div>
                </div>

                <div class="tech-category">
                    <h3>Frameworks & Libraries</h3>
                    <div class="tech-list">
                        <li>Qt Framework - Cross-platform GUI development</li>
                        <li>Modern CSS - Responsive web design</li>
                        <li>RESfile APIs - Service integration</li>
                        <li>Version Control - Git workflows</li>
                    </div>
                </div>

                <div class="tech-category">
                    <h3>Database Systems</h3>
                    <div class="tech-list">
                        <li>MySQL - Open-source database management</li>
                        <li>Microsoft SQL Server - Enterprise database solutions</li>
                        <li>Database optimization - Performance tuning</li>
                        <li>Data modeling - Architectural design</li>
                    </div>
                </div>

                <div class="tech-category">
                    <h3>Development Tools</h3>
                    <div class="tech-list">
                        <li>Professional IDEs - Development environments</li>
                        <li>Testing frameworks - Quality assurance</li>
                        <li>CI/CD pipelines - Automated deployment</li>
                        <li>Documentation tools - Technical documentation</li>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Development approach -->
    <section class="about-section values">
        <div class="container">
            <div class="section-header">
                <h2>Development Methodology</h2>
                <p class="section-description">Proven processes that deliver consistent, high-quality results</p>
            </div>

            <div class="values-grid">
                <div class="value-item card--tech">
                    <div class="value-icon">🎯</div>
                    <h3>Requirements Analysis</h3>
                    <p>Comprehensive analysis of business requirements and technical specifications to ensure optimal solution design and implementation strategy.</p>
                </div>
                <div class="value-item card--tech">
                    <div class="value-icon">⚙️</div>
                    <h3>Agile Development</h3>
                    <p>Iterative development approach with regular client feedback, ensuring alignment with business objectives and rapid adaptation to changing requirements.</p>
                </div>
                <div class="value-item card--tech">
                    <div class="value-icon">🔍</div>
                    <h3>Quality Assurance</h3>
                    <p>Rigorous testing protocols including unit testing, integration testing, and performance optimization to ensure reliable, production-ready software.</p>
                </div>
                <div class="value-item card--tech">
                    <div class="value-icon">📋</div>
                    <h3>Documentation</h3>
                    <p>Comprehensive technical documentation, user guides, and maintenance procedures to ensure long-term sustainability and knowledge transfer.</p>
                </div>
                <div class="value-item card--tech">
                    <div class="value-icon">🛠️</div>
                    <h3>Ongoing Support</h3>
                    <p>Post-deployment support including maintenance, updates, performance monitoring, and feature enhancements as business needs evolve.</p>
                </div>
                <div class="value-item card--tech">
                    <div class="value-icon">🔐</div>
                    <h3>Security Focus</h3>
                    <p>Security-first approach with implementation of industry best practices, secure coding standards, and regular security assessments.</p>
                </div>
            </div>
        </div>
    </section>


    <!-- Corporate CTA -->
    <section class="about-section corporate-cta">
        <div class="container">
            <div class="cta-content">
                <h2>Ready to Start Your Project?</h2>
                <p class="cta-description">
                    Partner with us to transform your business requirements into robust, scalable software solutions.
                    Our experienced team is ready to discuss your technical challenges and deliver results that exceed expectations.
                </p>
                <div class="cta-actions">
                    <a href="/index.php?page=contact" class="button button--primary button--lg">
                        Schedule Consultation
                    </a>
                    <div class="cta-contact">
                        <span class="contact-label">Direct contact:</span>
                        <a href="mailto:<?php echo htmlspecialchars($contact_email); ?>" class="contact-email">
                            <?php echo htmlspecialchars($contact_email); ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
