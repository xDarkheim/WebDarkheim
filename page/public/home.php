<?php

/**
 * Home page
 *
 * @author Dmytro Hovenko
 */

// Use global variables from the new architecture
global $database_handler, $flashMessageService, $auth, $site_settings_from_db, $container;

// Get text editor component via ServiceProvider
use App\Application\Core\ServiceProvider;

$serviceProvider = ServiceProvider::getInstance($container);
$textEditorComponent = $serviceProvider->getTextEditorComponent();

// Get settings from the database for the home page
$site_name = $site_settings_from_db['general']['site_name']['value'] ?? 'Darkheim Development Studio';
$site_tagline = $site_settings_from_db['general']['site_tagline']['value'] ?? 'Professional Web Development Solutions';
$site_description = $site_settings_from_db['general']['site_description']['value'] ?? 'Professional software development studio';
$contact_email = $site_settings_from_db['contact']['contact_email']['value'] ?? 'contact@darkheim.net';

// Get the latest news for the home page
$latest_news = [];
try {
    // Get NewsService to load articles
    $newsService = $serviceProvider->getNewsService();

    // Get the latest published articles for the home page
    $articlesData = $newsService->getArticles([
        'per_page' => 3,
        'page' => 1
    ]);

    $latest_articles = $articlesData['articles'] ?? [];

    // Transform to the required format for compatibility
    foreach ($latest_articles as $article) {
        // Get categories for each article
        $categories = $newsService->getArticleCategories($article->id);

        $latest_news[] = [
            'id' => $article->id,
            'title' => $article->title,
            'summary' => $article->short_description,
            'created_at' => $article->created_at,
            'slug' => $article->id,
            'categories' => $categories
        ];
    }
} catch (Exception $e) {
    // Log error but do not show to the user
    error_log("Error fetching latest news: " . $e->getMessage());
}
?>
<div class="page-home">
    <!-- Strict corporate Hero section -->
    <section class="corporate-hero">
        <!-- Animated geometric shapes -->
        <div class="hero-animations">
            <!-- Circular shapes -->
            <div class="floating-shape shape-1"></div>
            <div class="floating-shape shape-2"></div>
            <div class="floating-shape shape-3"></div>
            <div class="floating-shape shape-4"></div>

            <!-- Square shapes -->
            <div class="floating-square square-1"></div>
            <div class="floating-square square-2"></div>
            <div class="floating-square square-3"></div>

            <!-- Triangular shapes -->
            <div class="floating-triangle triangle-1"></div>
            <div class="floating-triangle triangle-2"></div>

            <!-- Thin glow effects -->
            <div class="hero-glow"></div>
            <div class="hero-glow-2"></div>
        </div>

        <div class="container">
            <div class="hero-grid">
                <div class="hero-content">
                    <div class="hero-badge">
                        <span class="badge-text">DEVELOPMENT STUDIO</span>
                    </div>

                    <h1 class="corporate-title">
                        Professional Software
                        <span class="title-accent">Development Solutions</span>
                    </h1>

                    <p class="corporate-subtitle">
                        We create robust, scalable applications using cutting-edge technologies.
                        From desktop software to web applications, our solutions drive business efficiency and growth.
                    </p>

                    <div class="hero-stats">
                        <div class="stat-item">
                            <div class="stat-number">2025</div>
                            <div class="stat-label">Founded</div>
                        </div>
                        <div class="stat-divider"></div>
                        <div class="stat-item">
                            <div class="stat-number">10+</div>
                            <div class="stat-label">Projects in Development</div>
                        </div>
                        <div class="stat-divider"></div>
                        <div class="stat-item">
                            <div class="stat-number">100%</div>
                            <div class="stat-label">Modern Technology Stack</div>
                        </div>
                    </div>

                    <div class="hero-actions">
                        <a href="/index.php?page=contact" class="button button--primary button--lg">
                            Start Your Project
                        </a>
                        <a href="/index.php?page=about" class="button button--secondary button--lg">
                            View Our Work
                        </a>
                    </div>
                </div>

                <div class="hero-visual">
                    <div class="tech-showcase">
                        <div class="tech-grid">
                            <div class="tech-item">
                                <div class="tech-icon"><i class="fab fa-cuttlefish"></i></div>
                                <div class="tech-name">C++</div>
                            </div>
                            <div class="tech-item">
                                <div class="tech-icon"><i class="fab fa-php"></i></div>
                                <div class="tech-name">PHP</div>
                            </div>
                            <div class="tech-item">
                                <div class="tech-icon"><i class="fas fa-database"></i></div>
                                <div class="tech-name">MySQL</div>
                            </div>
                            <div class="tech-item">
                                <div class="tech-icon"><i class="fas fa-code"></i></div>
                                <div class="tech-name">Qt</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Corporate services section -->
    <section class="corporate-services">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Core Services</h2>
                <p class="section-description">
                    Comprehensive technology solutions built with industry-leading practices and modern development methodologies
                </p>
            </div>

            <div class="services-grid">
                <div class="service-card card--tech">
                    <div class="service-header">
                        <div class="service-icon">
                            <i class="fas fa-desktop"></i>
                        </div>
                        <h3 class="service-title">Desktop Applications</h3>
                    </div>
                    <p class="service-description">
                        High-performance native applications built with C++ and Qt framework.
                        Cross-platform compatibility with modern, professional interfaces and robust architecture.
                    </p>
                    <div class="service-features">
                        <div class="feature-item">
                            <i class="fas fa-check-circle"></i>
                            Cross-platform deployment (Windows, Linux, macOS)
                        </div>
                        <div class="feature-item">
                            <i class="fas fa-check-circle"></i>
                            Native performance optimization
                        </div>
                        <div class="feature-item">
                            <i class="fas fa-check-circle"></i>
                            Modern Qt-based UI/UX design
                        </div>
                        <div class="feature-item">
                            <i class="fas fa-check-circle"></i>
                            Enterprise database integration
                        </div>
                    </div>
                </div>

                <div class="service-card card--tech">
                    <div class="service-header">
                        <div class="service-icon">
                            <i class="fas fa-globe"></i>
                        </div>
                        <h3 class="service-title">Web Development</h3>
                    </div>
                    <p class="service-description">
                        Scalable web applications using modern PHP frameworks and JavaScript.
                        Responsive design with focus on performance, security, and exceptional user experience.
                    </p>
                    <div class="service-features">
                        <div class="feature-item">
                            <i class="fas fa-check-circle"></i>
                            Progressive Web Applications (PWA)
                        </div>
                        <div class="feature-item">
                            <i class="fas fa-check-circle"></i>
                            Refile API development
                        </div>
                        <div class="feature-item">
                            <i class="fas fa-check-circle"></i>
                            Cloud-ready scalable architecture
                        </div>
                        <div class="feature-item">
                            <i class="fas fa-check-circle"></i>
                            Advanced security implementations
                        </div>
                    </div>
                </div>

                <div class="service-card card--tech">
                    <div class="service-header">
                        <div class="service-icon">
                            <i class="fas fa-database"></i>
                        </div>
                        <h3 class="service-title">Database Solutions</h3>
                    </div>
                    <p class="service-description">
                        Enterprise-grade database design, optimization, and management.
                        Robust data architecture ensuring reliability, scalability, and optimal performance.
                    </p>
                    <div class="service-features">
                        <div class="feature-item">
                            <i class="fas fa-check-circle"></i>
                            Advanced schema design & optimization
                        </div>
                        <div class="feature-item">
                            <i class="fas fa-check-circle"></i>
                            Performance tuning & indexing
                        </div>
                        <div class="feature-item">
                            <i class="fas fa-check-circle"></i>
                            Automated backup and recovery strategies
                        </div>
                        <div class="feature-item">
                            <i class="fas fa-check-circle"></i>
                            Security compliance & data protection
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Latest news section -->
    <section class="corporate-news">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Latest News & Updates</h2>
                <p class="section-description">
                    Stay informed about our latest developments, project milestones, and industry insights
                </p>
            </div>

            <?php if (!empty($latest_news)): ?>
                <div class="news-grid">
                    <?php foreach ($latest_news as $article): ?>
                        <article class="news-card">
                            <div class="news-header">
                                <div class="news-date">
                                    <i class="fas fa-calendar-alt"></i>
                                    <?php echo date('M d, Y', strtotime($article['created_at'])); ?>
                                </div>
                                <div class="news-badge">
                                    <span class="badge-text">NEWS</span>
                                </div>
                            </div>

                            <div class="news-content">
                                <h3 class="news-title">
                                    <a href="/index.php?page=news&id=<?php echo htmlspecialchars($article['slug']); ?>">
                                        <?php echo htmlspecialchars($article['title']); ?>
                                    </a>
                                </h3>

                                <div class="news-excerpt">
                                    <?php
                                    // Use the text editor component for proper Article Preview formatting
                                    $formattedSummary = $textEditorComponent->formatContent($article['summary']);

                                    // Truncate text, considering HTML tags
                                    $plainText = strip_tags($formattedSummary);
                                    if (strlen($plainText) > 150) {
                                        // For long text, show formatted HTML but truncated
                                        $words = explode(' ', $plainText);
                                        $truncatedWords = array_slice($words, 0, 25); // About 150 characters
                                        $truncatedText = implode(' ', $truncatedWords);
                                        echo htmlspecialchars($truncatedText) . '...';
                                    } else {
                                        // Show fully formatted HTML for short previews
                                        echo $formattedSummary;
                                    }
                                    ?>
                                </div>

                                <div class="news-categories">
                                    <?php foreach ($article['categories'] as $category): ?>
                                        <a href="/index.php?page=news&category=<?php echo htmlspecialchars($category['slug']); ?>"
                                           class="category-link">
                                            <?php echo htmlspecialchars($category['name']); ?>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <div class="news-footer">
                                <a href="/index.php?page=news&id=<?php echo htmlspecialchars($article['slug']); ?>"
                                   class="news-link">
                                    Read More
                                    <i class="fas fa-arrow-right"></i>
                                </a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>

                <div class="news-cta">
                    <a href="/index.php?page=news" class="button button--secondary button--lg">
                        View All News
                        <i class="fas fa-newspaper"></i>
                    </a>
                </div>
            <?php else: ?>
                <div class="news-empty">
                    <div class="empty-icon">
                        <i class="fas fa-newspaper"></i>
                    </div>
                    <h3 class="empty-title">No News Available</h3>
                    <p class="empty-description">
                        We're preparing exciting updates. Check back soon for the latest news and developments.
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Corporate CTA block -->
    <section class="corporate-cta">
        <!-- Animated geometric shapes for CTA -->
        <div class="cta-animations">
            <!-- Circular shapes -->
            <div class="floating-shape cta-shape-1"></div>
            <div class="floating-shape cta-shape-2"></div>
            <div class="floating-shape cta-shape-3"></div>
            
            <!-- Square shapes -->
            <div class="floating-square cta-square-1"></div>
            <div class="floating-square cta-square-2"></div>
            
            <!-- Triangular shapes -->
            <div class="floating-triangle cta-triangle-1"></div>
            
            <!-- Thin glow effects -->
            <div class="cta-glow"></div>
        </div>

        <div class="container">
            <div class="cta-content">
                <h2 class="cta-title">Ready to Start Your Project?</h2>
                <p class="cta-description">
                    Let's discuss how our technical expertise can help achieve your business goals.
                    From initial consultation to deployment, we provide comprehensive development services.
                </p>
                <div class="cta-actions">
                    <a href="/index.php?page=contact" class="button button--primary button--lg">
                        Get Started Today
                    </a>
                    <div class="cta-contact">
                        <span class="contact-label">Or email us directly:</span>
                        <a href="mailto:<?php echo htmlspecialchars($contact_email); ?>" class="contact-email">
                            <?php echo htmlspecialchars($contact_email); ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
