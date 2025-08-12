<?php

/**
 * Careers Page
 *
 * This page displays a list of open positions and benefits for developers.
 * It includes filtering options for different types of positions.
 *
 * @author Dmytro Hovenko
 */

global $site_settings_from_db;

// Get settings from the database
$site_name = $site_settings_from_db['general']['site_name']['value'] ?? 'Darkheim Development Studio';
$site_description = $site_settings_from_db['general']['site_description']['value'] ?? 'Professional software development studio';
$contact_email = $site_settings_from_db['contact']['contact_email']['value'] ?? 'contact@darkheim.net';

// Get position type from URL parameters
$position_type = $_GET['type'] ?? 'all';

// Define information about position types
$position_types = [
    'all' => [
        'title' => 'Career Opportunities',
        'subtitle' => 'Join our growing development team',
        'description' => 'Explore exciting career opportunities at Darkheim Development Studio. We\'re building the future of software development and looking for passionate developers to join our journey.',
        'icon' => '🚀'
    ],
    'development' => [
        'title' => 'Development Positions',
        'subtitle' => 'Code the future with us',
        'description' => 'Join our development team and work on cutting-edge projects using modern technologies and best practices.',
        'icon' => '💻'
    ],
    'internship' => [
        'title' => 'Internship Programs',
        'subtitle' => 'Start your development career',
        'description' => 'Learn from experienced developers and gain hands-on experience with real-world projects.',
        'icon' => '🎓'
    ]
];

$current_type = $position_types[$position_type] ?? $position_types['all'];
?>

    <!-- Hero Section -->
    <section class="careers-hero">
        <div class="container">
            <div class="hero-badge">
                <span class="badge-text">CAREERS</span>
            </div>
            <div class="hero-icon"><?php echo $current_type['icon']; ?></div>
            <h1 class="careers-hero-title"><?php echo htmlspecialchars($current_type['title']); ?></h1>
            <p class="careers-hero-subtitle"><?php echo htmlspecialchars($current_type['subtitle']); ?></p>
            <p class="careers-hero-description"><?php echo htmlspecialchars($current_type['description']); ?></p>
        </div>
    </section>

    <!-- Position Type Filter -->
    <section class="careers-filter">
        <div class="container">
            <div class="filter-tabs">
                <a href="/index.php?page=careers" 
                   class="filter-tab <?php echo $position_type === 'all' ? 'active' : ''; ?>">
                    🚀 All Positions
                </a>
                <a href="/index.php?page=careers&type=development" 
                   class="filter-tab <?php echo $position_type === 'development' ? 'active' : ''; ?>">
                    💻 Development
                </a>
                <a href="/index.php?page=careers&type=internship" 
                   class="filter-tab <?php echo $position_type === 'internship' ? 'active' : ''; ?>">
                    🎓 Internships
                </a>
            </div>
        </div>
    </section>

    <!-- Open Positions -->
    <section class="careers-content">
        <div class="container">
            <?php if ($position_type === 'all' || $position_type === 'development'): ?>
            <!-- Development Positions -->
            <div class="position-section">
                <div class="section-header">
                    <div class="section-icon">💻</div>
                    <div class="section-info">
                        <h2>Development Positions</h2>
                        <p class="section-tagline">Full-time opportunities for experienced developers</p>
                    </div>
                </div>

                <!-- Currently no open positions -->
                <div class="no-positions">
                    <div class="no-positions-icon">🔍</div>
                    <h3>No Open Positions Currently</h3>
                    <p>
                        We're not actively hiring at the moment, but we're always interested in connecting with talented developers.
                        Feel free to reach out if you're passionate about software development.
                    </p>
                    <a href="/index.php?page=contact" class="button button--secondary">
                        Get in Touch
                    </a>
                </div>

                <!--
                <div class="positions-grid">
                    <div class="position-card">
                        <div class="position-header">
                            <h3>Senior Full-Stack Developer</h3>
                            <div class="position-meta">
                                <span class="position-type">Full-time</span>
                                <span class="position-level">Senior</span>
                            </div>
                        </div>
                        <p class="position-summary">
                            Lead development of complex web applications and desktop software using modern technologies 
                            and best practices. Mentor junior developers and contribute to architectural decisions.
                        </p>
                        <div class="position-requirements">
                            <h4>Key Requirements:</h4>
                            <ul>
                                <li>5+ years of software development experience</li>
                                <li>Strong expertise in PHP, JavaScript, and C++</li>
                                <li>Experience with database design and optimization</li>
                                <li>Knowledge of Qt framework for desktop development</li>
                                <li>Leadership and mentoring experience</li>
                            </ul>
                        </div>
                        <div class="position-footer">
                            <div class="position-salary">$50,000 - $70,000</div>
                            <a href="/index.php?page=contact" class="button button--primary">Apply Now</a>
                        </div>
                    </div>

                    <div class="position-card">
                        <div class="position-header">
                            <h3>Frontend Developer</h3>
                            <div class="position-meta">
                                <span class="position-type">Full-time</span>
                                <span class="position-level">Mid-level</span>
                            </div>
                        </div>
                        <p class="position-summary">
                            Create engaging and responsive user interfaces for web applications. 
                            Work closely with the development team to implement modern frontend solutions.
                        </p>
                        <div class="position-requirements">
                            <h4>Key Requirements:</h4>
                            <ul>
                                <li>3+ years of frontend development experience</li>
                                <li>Proficiency in HTML5, CSS3, and JavaScript</li>
                                <li>Experience with responsive design principles</li>
                                <li>Knowledge of modern frontend frameworks</li>
                                <li>Understanding of web performance optimization</li>
                            </ul>
                        </div>
                        <div class="position-footer">
                            <div class="position-salary">$35,000 - $50,000</div>
                            <a href="/index.php?page=contact" class="button button--primary">Apply Now</a>
                        </div>
                    </div>
                </div>
                -->
            </div>
            <?php endif; ?>

            <?php if ($position_type === 'all' || $position_type === 'internship'): ?>
            <!-- Internship Positions -->
            <div class="position-section">
                <div class="section-header">
                    <div class="section-icon">🎓</div>
                    <div class="section-info">
                        <h2>Internship Programs</h2>
                        <p class="section-tagline">Learning opportunities for aspiring developers</p>
                    </div>
                </div>

                <!-- Currently no open internships -->
                <div class="no-positions">
                    <div class="no-positions-icon">📚</div>
                    <h3>No Internship Programs Currently</h3>
                    <p>
                        We're not offering internship programs at the moment, but we may have opportunities in the future.
                        Students and aspiring developers are welcome to reach out to discuss potential collaboration.
                    </p>
                    <a href="/index.php?page=contact" class="button button--secondary">
                        Contact Us
                    </a>
                </div>

                <!--
                <div class="positions-grid">
                    <div class="position-card">
                        <div class="position-header">
                            <h3>Software Development Intern</h3>
                            <div class="position-meta">
                                <span class="position-type">Internship</span>
                                <span class="position-level">Entry-level</span>
                            </div>
                        </div>
                        <p class="position-summary">
                            Gain hands-on experience in software development while working on real projects. 
                            Learn from experienced developers and contribute to our development process.
                        </p>
                        <div class="position-requirements">
                            <h4>What We're Looking For:</h4>
                            <ul>
                                <li>Computer Science or related field student</li>
                                <li>Basic knowledge of programming languages</li>
                                <li>Eagerness to learn and grow</li>
                                <li>Strong problem-solving skills</li>
                                <li>Good communication abilities</li>
                            </ul>
                        </div>
                        <div class="position-footer">
                            <div class="position-salary">$15 - $20/hour</div>
                            <a href="/index.php?page=contact" class="button button--primary">Apply Now</a>
                        </div>
                    </div>

                    <div class="position-card">
                        <div class="position-header">
                            <h3>Web Development Intern</h3>
                            <div class="position-meta">
                                <span class="position-type">Internship</span>
                                <span class="position-level">Entry-level</span>
                            </div>
                        </div>
                        <p class="position-summary">
                            Focus on web development technologies and modern frontend/backend practices. 
                            Work on client projects under mentorship and guidance.
                        </p>
                        <div class="position-requirements">
                            <h4>What We're Looking For:</h4>
                            <ul>
                                <li>Knowledge of HTML, CSS, and JavaScript</li>
                                <li>Basic understanding of PHP or similar backend language</li>
                                <li>Familiarity with databases</li>
                                <li>Portfolio of personal or academic projects</li>
                                <li>Passion for web development</li>
                            </ul>
                        </div>
                        <div class="position-footer">
                            <div class="position-salary">$12 - $18/hour</div>
                            <a href="/index.php?page=contact" class="button button--primary">Apply Now</a>
                        </div>
                    </div>
                </div>
                -->
            </div>
            <?php endif; ?>

            <!-- Future Opportunities -->
            <div class="position-section">
                <div class="section-header">
                    <div class="section-icon">🔮</div>
                    <div class="section-info">
                        <h2>Future Opportunities</h2>
                        <p class="section-tagline">Positions we're planning to open</p>
                    </div>
                </div>

                <div class="future-positions">
                    <div class="future-position">
                        <h3>DevOps Engineer</h3>
                        <p>Help us build and maintain our development infrastructure and deployment pipelines.</p>
                        <span class="coming-soon">Coming 2026</span>
                    </div>
                    <div class="future-position">
                        <h3>Mobile Developer</h3>
                        <p>Expand our services to include native mobile application development.</p>
                        <span class="coming-soon">Coming 2026</span>
                    </div>
                    <div class="future-position">
                        <h3>UI/UX Designer</h3>
                        <p>Create beautiful and intuitive user experiences for our software products.</p>
                        <span class="coming-soon">Coming 2026</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Benefits -->
    <section class="careers-benefits">
        <div class="container">
            <div class="section-header">
                <h2>Why Work With Us?</h2>
                <p class="section-description">
                    Benefits and perks of joining the Darkheim Development Studio team
                </p>
            </div>

            <div class="benefits-grid">
                <div class="benefit-item">
                    <div class="benefit-icon">💰</div>
                    <h3>Competitive Salary</h3>
                    <p>Fair compensation based on skills and experience with regular performance reviews.</p>
                </div>

                <div class="benefit-item">
                    <div class="benefit-icon">🏠</div>
                    <h3>Remote Friendly</h3>
                    <p>Flexible work arrangements with remote work options and flexible hours.</p>
                </div>

                <div class="benefit-item">
                    <div class="benefit-icon">📚</div>
                    <h3>Learning Budget</h3>
                    <p>Annual budget for courses, books, and conferences to support your professional growth.</p>
                </div>

                <div class="benefit-item">
                    <div class="benefit-icon">🚀</div>
                    <h3>Modern Tech Stack</h3>
                    <p>Work with cutting-edge technologies and tools in a collaborative environment.</p>
                </div>

                <div class="benefit-item">
                    <div class="benefit-icon">⚖️</div>
                    <h3>Work-Life Balance</h3>
                    <p>Reasonable hours and respect for personal time with no crunch culture.</p>
                </div>

                <div class="benefit-item">
                    <div class="benefit-icon">🎯</div>
                    <h3>Career Growth</h3>
                    <p>Clear career progression paths with mentorship and leadership opportunities.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Application Process -->
    <section class="careers-process">
        <div class="container">
            <div class="section-header">
                <h2>Our Hiring Process</h2>
                <p class="section-description">
                    Simple and transparent process designed to find the best fit for both parties
                </p>
            </div>

            <div class="process-steps">
                <div class="process-step">
                    <div class="step-number">1</div>
                    <div class="step-content">
                        <h3>Application</h3>
                        <p>Submit your application through our contact form with your résumé and portfolio.</p>
                    </div>
                </div>

                <div class="process-step">
                    <div class="step-number">2</div>
                    <div class="step-content">
                        <h3>Initial Review</h3>
                        <p>We review your application and get back to you within 3-5 business days.</p>
                    </div>
                </div>

                <div class="process-step">
                    <div class="step-number">3</div>
                    <div class="step-content">
                        <h3>Technical Interview</h3>
                        <p>Discuss your experience and work through technical challenges together.</p>
                    </div>
                </div>

                <div class="process-step">
                    <div class="step-number">4</div>
                    <div class="step-content">
                        <h3>Final Decision</h3>
                        <p>We make our decision and provide feedback regardless of the outcome.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA -->
    <section class="careers-cta">
        <div class="container">
            <div class="cta-content">
                <h2>Ready to Start Your Journey?</h2>
                <p class="cta-description">
                    Don't see the perfect position? We're always interested in talking to talented developers. 
                    Send us your information and let's discuss how you can contribute to our team.
                </p>
                <div class="cta-actions">
                    <a href="/index.php?page=contact" class="button button--primary button--lg">
                        Apply Now
                    </a>
                    <a href="/index.php?page=team" class="button button--secondary button--lg">
                        Meet Our Team
                    </a>
                </div>
                <div class="cta-contact">
                    <span class="contact-label">Questions about careers?</span>
                    <a href="mailto:<?php echo htmlspecialchars($contact_email); ?>" class="contact-email">
                        <?php echo htmlspecialchars($contact_email); ?>
                    </a>
                </div>
            </div>
        </div>
    </section>
