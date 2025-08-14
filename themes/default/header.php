<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <?php
    // Получаем настройки из базы данных
    global $site_settings_from_db;
    $site_name = $site_settings_from_db['general']['site_name']['value'] ?? 'Darkheim Development Studio';
    $site_description = $site_settings_from_db['general']['site_description']['value'] ?? 'Modern web and desktop application development';
    $site_keywords = $site_settings_from_db['general']['site_keywords']['value'] ?? 'web development, php, javascript, darkheim';
    $site_url = $site_settings_from_db['general']['site_url']['value'] ?? 'https://darkheim.net';
    $meta_robots = $site_settings_from_db['seo']['meta_robots']['value'] ?? 'index,follow';
    $google_analytics_id = $site_settings_from_db['seo']['google_analytics_id']['value'] ?? '';
    ?>

    <meta name="description" content="<?php echo htmlspecialchars($site_description); ?>">
    <meta name="keywords" content="<?php echo htmlspecialchars($site_keywords); ?>">
    <meta name="author" content="<?php echo htmlspecialchars($site_name); ?>">
    <meta name="robots" content="<?php echo htmlspecialchars($meta_robots); ?>">

    <!-- User role for JavaScript access control -->
    <meta name="user-role" content="<?php echo htmlspecialchars($_SESSION['user_role'] ?? 'guest'); ?>">

    <!-- Font Awesome Icons - Local Version -->
    <link rel="stylesheet" href="/themes/default/assets/fontawesome/css/all.min.css">

    <!-- Modern CSS -->
    <link rel="stylesheet" href="/themes/default/css/main.css">

    <!-- Enhanced Navigation CSS -->
    <link rel="stylesheet" href="/themes/default/css/navigation-enhanced.css">

    <!-- Sidebar Particles CSS -->
    <link rel="stylesheet" href="/themes/default/css/components/sidebar-particles.css">

    <!-- Debug Panel CSS -->
    <link rel="stylesheet" href="/themes/default/css/components/debug-panel.css">

    <!-- Core JavaScript - Load first -->
    <script src="/themes/default/js/main.js" defer></script>

    <!-- Enhanced Navigation JavaScript -->
    <script src="/themes/default/js/navigation.js" defer></script>

    <!-- User Menu JavaScript -->
    <script src="/themes/default/js/user-menu.js" defer></script>

    <!-- Optimized Header JavaScript -->
    <script src="/themes/default/js/header.js" defer></script>

    <!-- Toast Notification System -->
    <script src="/themes/default/js/toast.js" defer></script>

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/themes/default/img/favicon.ico">

    <title><?php echo htmlspecialchars($template_data['html_page_title'] ?? $page_title ?? $site_name); ?></title>

    <!-- Open Graph tags for social sharing -->
    <meta property="og:title" content="<?php echo htmlspecialchars($template_data['html_page_title'] ?? $page_title ?? $site_name); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($site_description); ?>">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo htmlspecialchars($site_url . ($_SERVER['REQUEST_URI'] ?? '')); ?>">
    <meta property="og:site_name" content="<?php echo htmlspecialchars($site_name); ?>">

    <?php if (!empty($google_analytics_id)): ?>
    <!-- Google Analytics -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo htmlspecialchars($google_analytics_id); ?>"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', '<?php echo htmlspecialchars($google_analytics_id); ?>');
    </script>
    <?php endif; ?>

    <!-- Theme color for mobile browsers -->
    <meta name="theme-color" content="#2563eb">

    <!-- Global state configuration -->
    <meta name="csrf-token" content="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
    <meta name="app-debug" content="<?php echo APP_DEBUG ? 'true' : 'false'; ?>">
    <meta name="app-env" content="<?php echo htmlspecialchars(APP_ENV); ?>">

    <!-- Global State Manager Script -->
    <script src="/themes/default/js/global-state-manager.js" defer></script>
</head>
<body class="theme-light" 
      data-debug="<?php echo APP_DEBUG ? 'true' : 'false'; ?>"
      data-env="<?php echo htmlspecialchars(APP_ENV); ?>">
    <!-- Декоративные боковые панели для полноэкранного режима -->
    <div class="fullscreen-sidebars fullscreen-sidebars--left">
        <div class="sidebar-particles">
            <div class="sidebar-particle"></div>
            <div class="sidebar-particle"></div>
            <div class="sidebar-particle"></div>
            <div class="sidebar-particle"></div>
        </div>
    </div>
    <div class="fullscreen-sidebars fullscreen-sidebars--right">
        <div class="sidebar-particles">
            <div class="sidebar-particle"></div>
            <div class="sidebar-particle"></div>
            <div class="sidebar-particle"></div>
            <div class="sidebar-particle"></div>
        </div>
    </div>

    <!-- Modern Header -->
    <header class="site-header" id="header">
        <div class="header-container">
            <!-- Logo Section -->
            <div class="header-brand">
                <div class="logo">
                    <div class="logo-icon">
                        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M12 2L13.09 8.26L20 9L13.09 9.74L12 16L10.91 9.74L4 9L10.91 8.26L12 2Z" fill="currentColor"/>
                            <path d="M19 15L19.74 17.74L22.5 18.5L19.74 19.26L19 22L18.26 19.26L15.5 18.5L18.26 17.74L19 15Z" fill="currentColor"/>
                            <path d="M5 6L5.74 8.74L8.5 9.5L5.74 10.26L5 13L4.26 10.26L1.5 9.5L4.26 8.74L5 6Z" fill="currentColor"/>
                        </svg>
                    </div>
                    <div class="logo-text">
                        <a href="/index.php?page=home" class="logo-link">
                            <span class="logo-primary">Darkheim</span>
                            <span class="logo-secondary">Studio</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Desktop Navigation -->
            <nav class="main-navigation" aria-label="Main navigation">
                <div class="nav-wrapper">
                    <?php echo $template_data['main_navigation_html'] ?? '<ul class="nav-list"><li class="nav-item"><a href="/index.php?page=home" class="nav-link">Home</a></li></ul'; ?>
                </div>

                <!-- Auth Section -->
                <div class="header-auth">
                    <?php if (!isset($_SESSION['user_id'])): ?>
                        <div class="auth-buttons">
                            <a href="/index.php?page=login" class="auth-btn auth-btn--secondary">
                                <span class="auth-btn-icon">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/>
                                        <polyline points="10,17 15,12 10,7"/>
                                        <line x1="15" y1="12" x2="3" y2="12"/>
                                    </svg>
                                </span>
                                Sign In
                            </a>
                            <a href="/index.php?page=register" class="auth-btn auth-btn--primary">
                                <span class="auth-btn-icon">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                                        <circle cx="8.5" cy="7" r="4"/>
                                        <line x1="20" y1="8" x2="20" y2="14"/>
                                        <line x1="23" y1="11" x2="17" y2="11"/>
                                    </svg>
                                </span>
                                Get Started
                            </a>
                        </div>
                    <?php else: ?>
                        <?php
                        // Получаем пользовательскую навигацию из NavigationHelper
                        $userNavigation = \App\Application\Helpers\NavigationHelper::getUserNavigation(true);
                        $userDropdownItems = $userNavigation[0]['dropdown'] ?? [];
                        ?>
                        <div class="user-menu">
                            <button class="user-menu-toggle" aria-expanded="false" aria-haspopup="true">
                                <div class="user-avatar">
                                    <img src="/themes/default/img/default-avatar.png" alt="User Avatar" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                    <div class="avatar-fallback" style="display: none;">
                                        <svg viewBox="0 0 24 24" fill="currentColor">
                                            <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                                        </svg>
                                    </div>
                                </div>
                                <span class="user-name"><?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?></span>
                                <svg class="dropdown-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="6,9 12,15 18,9"/>
                                </svg>
                            </button>

                            <!-- Мобильный overlay для закрытия dropdown -->
                            <div class="mobile-dropdown-overlay"></div>

                            <div class="user-dropdown">
                                <div class="user-dropdown-header">
                                    <div class="user-info">
                                        <div class="user-name-large"><?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?></div>
                                        <div class="user-email"><?php echo htmlspecialchars($_SESSION['email'] ?? 'user@example.com'); ?></div>
                                    </div>
                                </div>
                                <div class="user-dropdown-menu">
                                    <?php foreach ($userDropdownItems as $item): ?>
                                        <?php if ($item['text'] === 'Logout'): ?>
                                            <hr class="dropdown-divider">
                                            <a href="<?php echo htmlspecialchars($item['url']); ?>" class="dropdown-item dropdown-item--danger">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                                                    <polyline points="16,17 21,12 16,7"/>
                                                    <line x1="21" y1="12" x2="9" y2="12"/>
                                                </svg>
                                                <?php echo htmlspecialchars($item['text']); ?>
                                            </a>
                                        <?php elseif (strpos($item['text'], 'Dashboard') !== false): ?>
                                            <a href="<?php echo htmlspecialchars($item['url']); ?>" class="dropdown-item">
                                                <?php if (strpos($item['text'], 'Admin') !== false): ?>
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                                                    </svg>
                                                <?php elseif (strpos($item['text'], 'Employee') !== false): ?>
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                                                        <circle cx="9" cy="7" r="4"/>
                                                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                                                        <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                                                    </svg>
                                                <?php elseif (strpos($item['text'], 'Client') !== false): ?>
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                                                        <circle cx="12" cy="7" r="4"/>
                                                    </svg>
                                                <?php else: ?>
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                        <rect x="3" y="3" width="7" height="7"/>
                                                        <rect x="14" y="3" width="7" height="7"/>
                                                        <rect x="14" y="14" width="7" height="7"/>
                                                        <rect x="3" y="14" width="7" height="7"/>
                                                    </svg>
                                                <?php endif; ?>
                                                <?php echo htmlspecialchars($item['text']); ?>
                                            </a>
                                        <?php elseif ($item['text'] === 'My Profile'): ?>
                                            <a href="<?php echo htmlspecialchars($item['url']); ?>" class="dropdown-item">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                                                    <circle cx="12" cy="7" r="4"/>
                                                </svg>
                                                <?php echo htmlspecialchars($item['text']); ?>
                                            </a>
                                        <?php elseif ($item['text'] === 'My Portfolio'): ?>
                                            <a href="<?php echo htmlspecialchars($item['url']); ?>" class="dropdown-item">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                                                    <circle cx="8.5" cy="8.5" r="1.5"/>
                                                    <polyline points="21,15 16,10 5,21"/>
                                                </svg>
                                                <?php echo htmlspecialchars($item['text']); ?>
                                            </a>
                                        <?php elseif ($item['text'] === 'My Projects'): ?>
                                            <a href="<?php echo htmlspecialchars($item['url']); ?>" class="dropdown-item">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/>
                                                    <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/>
                                                </svg>
                                                <?php echo htmlspecialchars($item['text']); ?>
                                            </a>
                                        <?php elseif ($item['text'] === 'Support Tickets'): ?>
                                            <a href="<?php echo htmlspecialchars($item['url']); ?>" class="dropdown-item">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                                                </svg>
                                                <?php echo htmlspecialchars($item['text']); ?>
                                            </a>
                                        <?php elseif ($item['text'] === 'Manage Articles'): ?>
                                            <a href="<?php echo htmlspecialchars($item['url']); ?>" class="dropdown-item">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                                    <polyline points="14,2 14,8 20,8"/>
                                                    <line x1="16" y1="13" x2="8" y2="13"/>
                                                    <line x1="16" y1="17" x2="8" y2="17"/>
                                                    <polyline points="10,9 9,9 8,9"/>
                                                </svg>
                                                <?php echo htmlspecialchars($item['text']); ?>
                                            </a>
                                        <?php elseif ($item['text'] === 'Manage Categories'): ?>
                                            <a href="<?php echo htmlspecialchars($item['url']); ?>" class="dropdown-item">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/>
                                                    <line x1="7" y1="7" x2="7.01" y2="7"/>
                                                </svg>
                                                <?php echo htmlspecialchars($item['text']); ?>
                                            </a>
                                        <?php elseif ($item['text'] === 'Moderation'): ?>
                                            <a href="<?php echo htmlspecialchars($item['url']); ?>" class="dropdown-item">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                                                    <path d="M9 12l2 2 4-4"/>
                                                </svg>
                                                <?php echo htmlspecialchars($item['text']); ?>
                                            </a>
                                        <?php elseif ($item['text'] === 'Manage Users'): ?>
                                            <a href="<?php echo htmlspecialchars($item['url']); ?>" class="dropdown-item">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                                                    <circle cx="9" cy="7" r="4"/>
                                                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                                                    <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                                                </svg>
                                                <?php echo htmlspecialchars($item['text']); ?>
                                            </a>
                                        <?php elseif ($item['text'] === 'System Monitor'): ?>
                                            <a href="<?php echo htmlspecialchars($item['url']); ?>" class="dropdown-item">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <rect x="2" y="3" width="20" height="14" rx="2" ry="2"/>
                                                    <line x1="8" y1="21" x2="16" y2="21"/>
                                                    <line x1="12" y1="17" x2="12" y2="21"/>
                                                </svg>
                                                <?php echo htmlspecialchars($item['text']); ?>
                                            </a>
                                        <?php elseif ($item['text'] === 'Site Settings'): ?>
                                            <a href="<?php echo htmlspecialchars($item['url']); ?>" class="dropdown-item">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <circle cx="12" cy="12" r="3"/>
                                                    <path d="M12 1v6m0 6v6m11-7h-6m-6 0H1"/>
                                                </svg>
                                                <?php echo htmlspecialchars($item['text']); ?>
                                            </a>
                                        <?php else: ?>
                                            <a href="<?php echo htmlspecialchars($item['url']); ?>" class="dropdown-item">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <circle cx="12" cy="12" r="3"/>
                                                    <path d="M12 1v6m0 6v6m11-7h-6m-6 0H1"/>
                                                </svg>
                                                <?php echo htmlspecialchars($item['text']); ?>
                                            </a>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </nav>

            <!-- Mobile Menu Toggle -->
            <button class="mobile-nav-toggle" id="mobile-nav-toggle" aria-controls="mobile-navigation" aria-expanded="false">
                <span class="sr-only">Toggle Menu</span>
                <div class="hamburger-icon">
                    <span class="hamburger-line"></span>
                    <span class="hamburger-line"></span>
                    <span class="hamburger-line"></span>
                </div>
            </button>
        </div>

        <!-- Mobile Navigation -->
        <nav class="mobile-nav" id="mobile-navigation" aria-label="Mobile navigation">
            <ul class="mobile-nav-list">
                <?php if (isset($navItems) && is_array($navItems)): ?>
                    <?php foreach ($navItems as $item): ?>
                        <li class="mobile-nav-item <?php echo isset($item['dropdown']) ? 'has-dropdown' : ''; ?>">
                            <a href="<?php echo htmlspecialchars($item['url']); ?>"
                               class="mobile-nav-link <?php echo isset($item['dropdown']) ? 'has-dropdown' : ''; ?> <?php echo ($item['is_active'] ? 'active' : ''); ?>">
                                <?php echo htmlspecialchars($item['text']); ?>
                            </a>
                            <?php if (isset($item['dropdown']) && !empty($item['dropdown'])): ?>
                                <ul class="dropdown-menu">
                                    <?php foreach ($item['dropdown'] as $subItem): ?>
                                        <li class="dropdown-item">
                                            <a href="<?php echo htmlspecialchars($subItem['url']); ?>"
                                               class="dropdown-link <?php echo ($subItem['is_active'] ?? false) ? 'active' : ''; ?>">
                                                <?php echo htmlspecialchars($subItem['text']); ?>
                                            </a>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                <?php endif; ?>

                <!-- Компактная секция авторизации для мобильных устройств -->
                <li class="mobile-auth-section">
                    <?php if (!isset($_SESSION['user_id'])): ?>
                        <div class="mobile-nav-item mobile-auth-item">
                            <a href="/index.php?page=login" class="mobile-nav-link mobile-auth-link mobile-auth-link--login">Sign In</a>
                        </div>
                        <div class="mobile-nav-item mobile-auth-item">
                            <a href="/index.php?page=register" class="mobile-nav-link mobile-auth-link mobile-auth-link--register">Get Started</a>
                        </div>
                    <?php else: ?>
                        <?php foreach ($userDropdownItems as $item): ?>
                            <div class="mobile-nav-item mobile-auth-item">
                                <a href="<?php echo htmlspecialchars($item['url']); ?>"
                                   class="mobile-nav-link mobile-auth-link <?php echo $item['text'] === 'Logout' ? 'mobile-auth-link--logout' : 'mobile-auth-link--dashboard'; ?>">
                                    <?php echo htmlspecialchars($item['text']); ?>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </li>
            </ul>
        </nav>
    </header>

    <!-- Site Wrapper -->
    <div class="site-wrapper">
        <!-- Sidebar removed - full width layout -->
        <main class="main-content main-content--full-width" role="main">
