<?php

/**
 * Team Page
 *
 * This page displays a list of team members and their roles.
 *
 * @author Dmytro Hovenko
 */

global $site_settings_from_db;

// Get settings from the database
$site_name = $site_settings_from_db['general']['site_name']['value'] ?? 'Darkheim Development Studio';
$site_description = $site_settings_from_db['general']['site_description']['value'] ?? 'Professional software development studio';
$contact_email = $site_settings_from_db['contact']['contact_email']['value'] ?? 'contact@darkheim.net';
?>

<div class="page-team">
    <!-- Hero Section -->
    <section class="team-hero">
        <div class="container">
            <div class="hero-badge">
                <span class="badge-text">OUR TEAM</span>
            </div>
            <div class="hero-icon">👥</div>
            <h1 class="team-hero-title">Meet Our Development Team</h1>
            <p class="team-hero-subtitle">Passionate developers building the future</p>
            <p class="team-hero-description">
                Our team consists of experienced developers dedicated to delivering high-quality software solutions.
                We combine technical expertise with creative problem-solving to bring your ideas to life.
            </p>
        </div>
    </section>

    <!-- Team Members -->
    <section class="team-content">
        <div class="container">
            <div class="team-grid">
                <!-- Lead Developer -->
                <div class="team-member">
                    <div class="member-photo">
                        <img src="/public/assets/images/team/dhovenko.jpg" alt="Dmytro Hovenko" class="member-photo-img">
                    </div>
                    <div class="member-info">
                        <h3 class="member-name">Dmytro Hovenko</h3>
                        <p class="member-role">Software Developer</p>
                        <p class="member-specialty">Specializing in Desktop Development with C++ & Qt</p>
                        <p class="member-description">
                            Experienced Software Developer specializing in Desktop Development using C++ and the Qt framework.
                            The primary focus is creating cross-platform desktop applications with modern user interfaces.
                            Also experienced with web technologies including PHP, JavaScript, HTML, CSS, and MySQL.
                        </p>
                        <div class="member-skills">
                            <span class="skill-tag skill-tag--advanced">C++</span>
                            <span class="skill-tag skill-tag--advanced">Qt Framework</span>
                            <span class="skill-tag skill-tag--intermediate">PHP</span>
                            <span class="skill-tag skill-tag--intermediate">JavaScript</span>
                            <span class="skill-tag skill-tag--intermediate">MySQL</span>
                            <span class="skill-tag skill-tag--intermediate">MS SQL</span>
                            <span class="skill-tag">Cross-platform Development</span>
                            <span class="skill-tag">Git</span>
                        </div>
                        <div class="member-links">
                            <a href="https://hovenko.com/" target="_blank" rel="noopener" class="member-website">
                                🌐 Personal Website
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Future Team Members Placeholder -->
                <div class="team-member team-member--placeholder">
                    <div class="member-photo">
                        <div class="photo-placeholder">🚀</div>
                    </div>
                    <div class="member-info">
                        <h3 class="member-name">We're Growing!</h3>
                        <p class="member-role">Join Our Team</p>
                        <p class="member-description">
                            We're actively looking for talented developers to join our team.
                            If you're passionate about software development and want to work on exciting projects,
                            we'd love to hear from you.
                        </p>
                        <div class="member-actions">
                            <a href="/index.php?page=careers" class="button button--primary">
                                View Open Positions
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Team Values -->
    <section class="team-values">
        <div class="container">
            <div class="section-header">
                <h2>Our Values & Approach</h2>
                <p class="section-description">
                    The principles that guide our development process and team culture
                </p>
            </div>

            <div class="values-grid">
                <div class="value-item">
                    <div class="value-icon">💡</div>
                    <h3>Innovation</h3>
                    <p>
                        We stay current with the latest technologies and development practices
                        to deliver cutting-edge solutions.
                    </p>
                </div>

                <div class="value-item">
                    <div class="value-icon">🎯</div>
                    <h3>Quality Focus</h3>
                    <p>
                        Every line of code is written with attention to detail,
                        ensuring robust and maintainable software.
                    </p>
                </div>

                <div class="value-item">
                    <div class="value-icon">🤝</div>
                    <h3>Collaboration</h3>
                    <p>
                        We work closely with clients throughout the development process
                        to ensure their vision becomes reality.
                    </p>
                </div>

                <div class="value-item">
                    <div class="value-icon">⚡</div>
                    <h3>Efficiency</h3>
                    <p>
                        Streamlined development processes and agile methodologies
                        ensure timely project delivery.
                    </p>
                </div>

                <div class="value-item">
                    <div class="value-icon">📚</div>
                    <h3>Continuous Learning</h3>
                    <p>
                        We're committed to continuous improvement and staying ahead
                        of industry trends and best practices.
                    </p>
                </div>

                <div class="value-item">
                    <div class="value-icon">🛡️</div>
                    <h3>Reliability</h3>
                    <p>
                        Dependable solutions with comprehensive testing and documentation
                        for long-term stability.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Technologies -->
    <section class="team-technologies">
        <div class="container">
            <div class="section-header">
                <h2>Technologies We Master</h2>
                <p class="section-description">
                    Our team's collective expertise across multiple technology stacks
                </p>
            </div>

            <div class="tech-categories">
                <div class="tech-category">
                    <h3>Desktop Development</h3>
                    <div class="tech-items">
                        <span class="tech-item">C++</span>
                        <span class="tech-item">Qt Framework</span>
                        <span class="tech-item">QML</span>
                        <span class="tech-item">SQLite</span>
                        <span class="tech-item">Cross-platform Development</span>
                    </div>
                </div>

                <div class="tech-category">
                    <h3>Web Development</h3>
                    <div class="tech-items">
                        <span class="tech-item">PHP</span>
                        <span class="tech-item">JavaScript</span>
                        <span class="tech-item">HTML5 & CSS3</span>
                        <span class="tech-item">MySQL</span>
                        <span class="tech-item">RESTful APIs</span>
                    </div>
                </div>

                <div class="tech-category">
                    <h3>Tools & Practices</h3>
                    <div class="tech-items">
                        <span class="tech-item">Git Version Control</span>
                        <span class="tech-item">Agile Development</span>
                        <span class="tech-item">Code Review</span>
                        <span class="tech-item">Testing & QA</span>
                        <span class="tech-item">Documentation</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Join Team CTA -->
    <section class="team-cta">
        <div class="container">
            <div class="cta-content">
                <h2>Want to Join Our Team?</h2>
                <p class="cta-description">
                    We're always looking for talented developers who share our passion for
                    creating exceptional software solutions. Join us in building the future of technology.
                </p>
                <div class="cta-actions">
                    <a href="/index.php?page=careers" class="button button--primary button--lg">
                        View Career Opportunities
                    </a>
                    <a href="/index.php?page=contact" class="button button--secondary button--lg">
                        Get in Touch
                    </a>
                </div>
                <div class="cta-contact">
                    <span class="contact-label">Have questions about working with us?</span>
                    <a href="mailto:<?php echo htmlspecialchars($contact_email); ?>" class="contact-email">
                        <?php echo htmlspecialchars($contact_email); ?>
                    </a>
                </div>
            </div>
        </div>
    </section>
</div>
