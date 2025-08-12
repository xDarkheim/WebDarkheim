<?php

/**
 * Projects Page
 *
 * This page displays a list of projects, categorized by technology stack.
 * It includes a filter for selecting different categories of projects.
 *
 * @author Dmytro Hovenko
 */

global $site_settings_from_db;

// Get settings from the database
$site_name = $site_settings_from_db['general']['site_name']['value'] ?? 'Darkheim Development Studio';
$site_description = $site_settings_from_db['general']['site_description']['value'] ?? 'Professional software development studio';
$contact_email = $site_settings_from_db['contact']['contact_email']['value'] ?? 'contact@darkheim.net';

// Get category from URL parameters
$category = $_GET['category'] ?? 'all';

// Define category information
$categories = [
    'all' => [
        'title' => 'All Projects',
        'subtitle' => 'Complete portfolio of our development work',
        'description' => 'Explore our comprehensive portfolio showcasing desktop applications, web solutions, and innovative software projects built with modern technologies.',
        'icon' => '🚀'
    ],
    'web' => [
        'title' => 'Web Development Projects',
        'subtitle' => 'Modern web applications and solutions',
        'description' => 'Responsive, scalable web applications built with PHP, JavaScript, and contemporary frameworks. From corporate websites to complex web platforms.',
        'icon' => '🌐'
    ],
    'mobile' => [
        'title' => 'Mobile App Projects',
        'subtitle' => 'Cross-platform mobile solutions',
        'description' => 'Mobile applications designed for iOS and Android platforms, featuring native performance and modern user interfaces.',
        'icon' => '📱'
    ],
    'desktop' => [
        'title' => 'Desktop Software Projects',
        'subtitle' => 'High-performance native applications',
        'description' => 'Cross-platform desktop applications built with C++ and Qt framework, delivering professional-grade performance and functionality.',
        'icon' => '🖥️'
    ],
    'opensource' => [
        'title' => 'Open Source Contributions',
        'subtitle' => 'Community-driven development projects',
        'description' => 'Open source projects and contributions to the developer community, showcasing our commitment to collaborative development.',
        'icon' => '💻'
    ]
];

$current_category = $categories[$category] ?? $categories['all'];

?>

<div class="page-projects">
    <!-- Hero Section -->
    <section class="projects-hero">
        <div class="container">
            <div class="hero-badge">
                <span class="badge-text">PROJECTS PORTFOLIO</span>
            </div>
            <div class="hero-icon"><?php echo $current_category['icon']; ?></div>
            <h1 class="projects-hero-title"><?php echo htmlspecialchars($current_category['title']); ?></h1>
            <p class="projects-hero-subtitle"><?php echo htmlspecialchars($current_category['subtitle']); ?></p>
            <p class="projects-hero-description"><?php echo htmlspecialchars($current_category['description']); ?></p>
        </div>
    </section>

    <!-- Category Filter -->
    <section class="projects-filter">
        <div class="container">
            <div class="filter-tabs">
                <a href="/index.php?page=projects"
                   class="filter-tab <?php echo $category === 'all' ? 'active' : ''; ?>">
                    🚀 All Projects
                </a>
                <a href="/index.php?page=projects&category=web"
                   class="filter-tab <?php echo $category === 'web' ? 'active' : ''; ?>">
                    🌐 Web Development
                </a>
                <a href="/index.php?page=projects&category=mobile"
                   class="filter-tab <?php echo $category === 'mobile' ? 'active' : ''; ?>">
                    📱 Mobile Apps
                </a>
                <a href="/index.php?page=projects&category=desktop"
                   class="filter-tab <?php echo $category === 'desktop' ? 'active' : ''; ?>">
                    🖥️ Desktop Software
                </a>
                <a href="/index.php?page=projects&category=opensource"
                   class="filter-tab <?php echo $category === 'opensource' ? 'active' : ''; ?>">
                    💻 Open Source
                </a>
            </div>
        </div>
    </section>

    <!-- Projects Grid -->
    <section class="projects-content">
        <div class="container">
            <div class="projects-grid">
                <?php if ($category === 'all' || $category === 'web'): ?>
                <!-- Web Development Projects -->
                <div class="project-card">
                    <div class="project-image">
                        <div class="project-placeholder">🌐</div>
                    </div>
                    <div class="project-content">
                        <div class="project-category">Web Development</div>
                        <h3 class="project-title">Corporate Web Platform</h3>
                        <p class="project-description">
                            Full-stack web application built with PHP and JavaScript, featuring responsive design,
                            user authentication, and content management capabilities.
                        </p>
                        <div class="project-tech">
                            <span class="tech-tag">PHP</span>
                            <span class="tech-tag">JavaScript</span>
                            <span class="tech-tag">MySQL</span>
                            <span class="tech-tag">CSS3</span>
                        </div>
                        <div class="project-status" data-status="in-progress">In Development</div>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ($category === 'all' || $category === 'desktop'): ?>
                <!-- Desktop Software Projects -->
                <div class="project-card">
                    <div class="project-image">
                        <div class="project-placeholder">🖥️</div>
                    </div>
                    <div class="project-content">
                        <div class="project-category">Desktop Software</div>
                        <h3 class="project-title">Cross-Platform Application Suite</h3>
                        <p class="project-description">
                            Professional desktop application built with C++ and Qt framework,
                            providing cross-platform compatibility and native performance.
                        </p>
                        <div class="project-tech">
                            <span class="tech-tag">C++</span>
                            <span class="tech-tag">Qt Framework</span>
                            <span class="tech-tag">SQLite</span>
                            <span class="tech-tag">QML</span>
                        </div>
                        <div class="project-status" data-status="planning">Planning Phase</div>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ($category === 'all' || $category === 'mobile'): ?>
                <!-- Mobile App Projects -->
                <div class="project-card">
                    <div class="project-image">
                        <div class="project-placeholder">📱</div>
                    </div>
                    <div class="project-content">
                        <div class="project-category">Mobile Development</div>
                        <h3 class="project-title">Business Mobile Solution</h3>
                        <p class="project-description">
                            Cross-platform mobile application designed for business productivity,
                            featuring modern UI and seamless integration with backend services.
                        </p>
                        <div class="project-tech">
                            <span class="tech-tag">React Native</span>
                            <span class="tech-tag">TypeScript</span>
                            <span class="tech-tag">API Integration</span>
                            <span class="tech-tag">Push Notifications</span>
                        </div>
                        <div class="project-status">Concept Phase</div>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ($category === 'all' || $category === 'opensource'): ?>
                <!-- Open Source Projects -->
                <div class="project-card">
                    <div class="project-image">
                        <div class="project-placeholder">💻</div>
                    </div>
                    <div class="project-content">
                        <div class="project-category">Open Source</div>
                        <h3 class="project-title">Development Tools & Libraries</h3>
                        <p class="project-description">
                            Collection of open-source development tools and libraries
                            to support the developer community and promote collaborative coding.
                        </p>
                        <div class="project-tech">
                            <span class="tech-tag">GitHub</span>
                            <span class="tech-tag">MIT License</span>
                            <span class="tech-tag">Documentation</span>
                            <span class="tech-tag">Community</span>
                        </div>
                        <div class="project-status">Active Development</div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Placeholder for future projects -->
                <div class="project-card project-card--placeholder">
                    <div class="project-content">
                        <div class="placeholder-icon">🚧</div>
                        <h3 class="placeholder-title">More Projects Coming Soon</h3>
                        <p class="placeholder-description">
                            We're actively working on exciting new projects across all categories.
                            Stay tuned for updates on our latest development work.
                        </p>
                        <a href="/index.php?page=contact" class="button button--secondary">
                            Discuss Your Project
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Technology Stack -->
    <section class="projects-tech-stack">
        <div class="container">
            <div class="section-header">
                <h2>Development Technologies</h2>
                <p class="section-description">
                    Modern technology stack powering our project development
                </p>
            </div>

            <div class="tech-grid">
                <div class="tech-category">
                    <h3>Frontend Development</h3>
                    <div class="tech-items">
                        <div class="tech-item">HTML5 & CSS3</div>
                        <div class="tech-item">JavaScript ES6+</div>
                        <div class="tech-item">Responsive Design</div>
                        <div class="tech-item">Progressive Web Apps</div>
                    </div>
                </div>

                <div class="tech-category">
                    <h3>Backend Development</h3>
                    <div class="tech-items">
                        <div class="tech-item">PHP 8+</div>
                        <div class="tech-item">RESTful APIs</div>
                        <div class="tech-item">Database Design</div>
                        <div class="tech-item">Server Architecture</div>
                    </div>
                </div>

                <div class="tech-category">
                    <h3>Desktop Applications</h3>
                    <div class="tech-items">
                        <div class="tech-item">C++ Development</div>
                        <div class="tech-item">Qt Framework</div>
                        <div class="tech-item">Cross-platform Deployment</div>
                        <div class="tech-item">Native Performance</div>
                    </div>
                </div>

                <div class="tech-category">
                    <h3>Database & Tools</h3>
                    <div class="tech-items">
                        <div class="tech-item">MySQL</div>
                        <div class="tech-item">Microsoft SQL Server</div>
                        <div class="tech-item">Git Version Control</div>
                        <div class="tech-item">Testing & Documentation</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Call to Action -->
    <section class="projects-cta">
        <div class="container">
            <div class="cta-content">
                <h2>Ready to Start Your Project?</h2>
                <p class="cta-description">
                    Whether you need a desktop application, web platform, or mobile solution,
                    our development team has the expertise to bring your vision to life.
                </p>
                <div class="cta-actions">
                    <a href="/index.php?page=contact" class="button button--primary button--lg">
                        Discuss Your Project
                    </a>
                    <a href="/index.php?page=services" class="button button--secondary button--lg">
                        View Our Services
                    </a>
                </div>
                <div class="cta-contact">
                    <span class="contact-label">Have questions?</span>
                    <a href="mailto:<?php echo htmlspecialchars($contact_email); ?>" class="contact-email">
                        <?php echo htmlspecialchars($contact_email); ?>
                    </a>
                </div>
            </div>
        </div>
    </section>
</div>
