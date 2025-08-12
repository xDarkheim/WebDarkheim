</main>
    </div>

    <!-- Modern Footer -->
    <footer class="site-footer">
        <div class="footer-container">
            <!-- Main Footer Content -->
            <div class="footer-content">
                <?php
                // Получаем настройки из базы данных для футера
                global $site_settings_from_db;
                $site_name = $site_settings_from_db['general']['site_name']['value'] ?? 'Darkheim Studio';
                $site_description = $site_settings_from_db['general']['site_description']['value'] ?? 'Creating modern web solutions and desktop applications using cutting-edge technologies.';
                $contact_email = $site_settings_from_db['contact']['contact_email']['value'] ?? 'contact@darkheim.net';

                // Используем новые Helper классы вместо старых конфигурационных файлов
                $footerSections = \App\Application\Helpers\NavigationHelper::getFooterNavigation();
                $socialNetworks = \App\Application\Helpers\SocialMediaHelper::getAllSocialNetworks();
                ?>

                <div class="footer-section footer-about">
                    <div class="footer-brand">
                        <div class="footer-logo">
                            <div class="footer-logo-icon">
                                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M12 2L13.09 8.26L20 9L13.09 9.74L12 16L10.91 9.74L4 9L10.91 8.26L12 2Z" fill="currentColor"/>
                                    <path d="M19 15L19.74 17.74L22.5 18.5L19.74 19.26L19 22L18.26 19.26L15.5 18.5L18.26 17.74L19 15Z" fill="currentColor"/>
                                    <path d="M5 6L5.74 8.74L8.5 9.5L5.74 10.26L5 13L4.26 10.26L1.5 9.5L4.26 8.74L5 6Z" fill="currentColor"/>
                                </svg>
                            </div>
                            <h3 class="footer-title"><?php echo htmlspecialchars($site_name); ?></h3>
                        </div>
                    </div>
                    <p class="footer-description">
                        <?php echo htmlspecialchars($site_description); ?>
                    </p>

                    <?php if (!empty($socialNetworks)): ?>
                    <div class="footer-social">
                        <span class="social-label">Follow us:</span>
                        <?php echo \App\Application\Helpers\SocialMediaHelper::renderSocialLinks('footer-social-links'); ?>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="footer-section footer-services">
                    <h4 class="footer-subtitle"><?php echo htmlspecialchars($footerSections['services']['title']); ?></h4>
                    <ul class="footer-links">
                        <?php foreach ($footerSections['services']['links'] as $link): ?>
                            <li><a href="<?php echo htmlspecialchars($link['url']); ?>"><?php echo htmlspecialchars($link['text']); ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <div class="footer-section footer-links">
                    <h4 class="footer-subtitle"><?php echo htmlspecialchars($footerSections['company']['title']); ?></h4>
                    <ul class="footer-links">
                        <?php foreach ($footerSections['company']['links'] as $link): ?>
                            <li><a href="<?php echo htmlspecialchars($link['url']); ?>"><?php echo htmlspecialchars($link['text']); ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <div class="footer-section footer-contact">
                    <h4 class="footer-subtitle">Get in Touch</h4>
                    <div class="contact-info">
                        <div class="contact-item">
                            <div class="contact-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="m22 2-7 20-4-9-9-4Z"/>
                                    <path d="M22 2 11 13"/>
                                </svg>
                            </div>
                            <div class="contact-details">
                                <span class="contact-label">Email</span>
                                <a href="mailto:<?php echo htmlspecialchars($contact_email); ?>" class="contact-link">
                                    <?php echo htmlspecialchars($contact_email); ?>
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="footer-cta">
                        <p class="cta-text">Ready to start your project?</p>
                        <a href="/index.php?page=contact" class="cta-button">
                            Let's Talk
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="m9 18 6-6-6-6"/>
                            </svg>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Footer Bottom -->
            <div class="footer-bottom">
                <div class="footer-left">
                    <p class="footer-copyright">
                        © <?php echo date('Y'); ?> <?php echo htmlspecialchars($site_name); ?>. All rights reserved.
                    </p>
                </div>
                
                <!-- Developer Credit - элегантно в нижней части -->
                <div class="footer-center">
                    <div class="developer-credit">
                        <span class="developer-text">Developed by</span>
                        <a href="https://hovenko.com/" target="_blank" rel="noopener noreferrer" class="developer-link">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="developer-icon">
                                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                                <circle cx="9" cy="7" r="4"/>
                            </svg>
                            Dmytro Hovenko
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="external-link-icon">
                                <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>
                                <polyline points="15,3 21,3 21,9"/>
                                <line x1="10" y1="14" x2="21" y2="3"/>
                            </svg>
                        </a>
                    </div>
                </div>
                
                <div class="footer-right">
                    <div class="footer-legal">
                        <?php foreach ($footerSections['legal']['links'] as $index => $link): ?>
                            <?php if ($index > 0): ?><span class="separator">•</span><?php endif; ?>
                            <a href="<?php echo htmlspecialchars($link['url']); ?>"><?php echo htmlspecialchars($link['text']); ?></a>
                        <?php endforeach; ?>
                    </div>
                    <div class="footer-built">
                        <span class="built-text">Built with</span>
                        <span class="heart">❤️</span>
                        <span class="tech-stack">PHP & Modern CSS</span>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <!-- Mobile-friendly scripts loaded at the end -->
    <!-- main.js уже загружен в header.php, убираем дублирование -->

    <!-- Category Filter JavaScript -->
    <script src="/themes/default/js/category-filter.js" defer></script>

    <!-- Article Rating JavaScript -->
    <script src="/themes/default/js/article-rating.js" defer></script>

    <!-- Debug Panel JavaScript -->
    <script src="/themes/default/js/debug-panel.js" defer></script>

    <!-- Form Confirmations JavaScript -->
    <script src="/themes/default/js/form-confirmations.js" defer></script>

    <!-- State synchronization script -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        try {
            if (window.Darkheim && window.Darkheim.GlobalState) {
                // Синхронизация состояния с PHP данными
                const phpState = <?php echo json_encode([
                    'user.authenticated' => isset($_SESSION['user_id']),
                    'user.id' => $_SESSION['user_id'] ?? null,
                    'user.username' => $_SESSION['username'] ?? null,
                    'user.role' => $_SESSION['user_role'] ?? null,
                    'ui.page_messages' => $template_data['page_messages'] ?? [],
                    'site.name' => $template_data['site_name'] ?? 'Darkheim Studio',
                    'request.current_page' => $_GET['page'] ?? 'home',
                    'api.csrf_token' => $_SESSION['csrf_token'] ?? null,
                ]); ?>;

                // Обновляем состояние данными с сервера с обработкой ошибок
                Object.keys(phpState).forEach(key => {
                    try {
                        window.Darkheim.GlobalState.set(key, phpState[key]);
                    } catch (error) {
                        console.error(`[Darkheim] Error setting state for ${key}:`, error);
                    }
                });

                console.log('[Darkheim] State synchronized with server data');

                // Включаем отладку если необходимо
                if (<?php echo APP_DEBUG ? 'true' : 'false'; ?>) {
                    window.Darkheim.GlobalState.set('app.debug', true);
                    console.log('[Darkheim] Debug mode enabled');
                }
            } else {
                console.warn('[Darkheim] GlobalState not available, skipping synchronization');
            }
        } catch (error) {
            console.error('[Darkheim] Error during state synchronization:', error);
        }
    });
    </script>

    <!-- Enhanced Management Pages JavaScript -->
    <?php
    // Load management JavaScript only on relevant pages
    $current_page = $_GET['page'] ?? 'home';
    $management_pages = ['create_article', 'edit_article', 'manage_articles', 'manage_categories', 'edit_category'];
    if (in_array($current_page, $management_pages)): ?>
        <script src="/themes/default/js/manage-pages.js" defer></script>
    <?php endif; ?>

    <!-- Toast Container -->
    <div id="toast-container" class="toast-container" aria-live="polite"></div>

    <!-- Toast Messages Handler -->
    <script src="/themes/default/js/toast-messages.js" defer></script>

    <!-- PHP Messages Data for Toast Handler -->
    <?php if (!empty($template_data['page_messages'])): ?>
    <div data-php-messages='<?php echo json_encode($template_data['page_messages']); ?>' class="hidden"></div>
    <?php endif; ?>
</body>
</html>