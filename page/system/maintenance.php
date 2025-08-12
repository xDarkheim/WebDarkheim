<?php

/**
 * Maintenance Page
 *
 * This page is displayed when the site is under maintenance.
 * It provides a user-friendly message and links to the admin login page.
 *
 * @author Dmytro Hovenko
 */
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Under Maintenance - <?php echo htmlspecialchars($siteName ?? 'Website'); ?></title>
    <link rel="stylesheet" href="/themes/default/css/components/_maintenance.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="maintenance-wrapper">
        <!-- Background Animation -->
        <div class="maintenance-bg">
            <div class="animated-gears">
                <div class="gear gear-1"><i class="fas fa-cog"></i></div>
                <div class="gear gear-2"><i class="fas fa-cog"></i></div>
                <div class="gear gear-3"><i class="fas fa-cog"></i></div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="maintenance-container">
            <div class="maintenance-content">
                <div class="maintenance-icon">
                    <i class="fas fa-tools"></i>
                </div>

                <h1 class="maintenance-title">Site Under Maintenance</h1>

                <p class="maintenance-description">
                    We're currently performing scheduled maintenance to improve your experience.
                    <br>
                    <strong><?php echo htmlspecialchars($siteName ?? 'Our website'); ?></strong> will be back online shortly.
                </p>

                <div class="maintenance-status">
                    <div class="status-item">
                        <i class="fas fa-clock"></i>
                        <span>Expected completion: within a few hours</span>
                    </div>
                    <div class="status-item">
                        <i class="fas fa-envelope"></i>
                        <span>For urgent matters, contact us via email</span>
                    </div>
                </div>

                <div class="maintenance-progress">
                    <div class="progress-bar">
                        <div class="progress-fill"></div>
                    </div>
                    <span class="progress-text">Maintenance in progress...</span>
                </div>
            </div>

            <!-- Admin Login Button -->
            <div class="admin-access">
                <button class="admin-login-btn" onclick="openAdminModal()">
                    <i class="fas fa-user-shield"></i>
                    Administrator Access
                </button>
            </div>
        </div>

        <!-- Social Links -->
        <div class="maintenance-footer">
            <p>Stay updated on our progress:</p>
            <div class="social-links">
                <?php
                // Use SocialMediaHelper to get dynamic links
                use App\Application\Helpers\SocialMediaHelper;

                $socialNetworks = SocialMediaHelper::getAllSocialNetworks();

                if (!empty($socialNetworks)) {
                    foreach ($socialNetworks as $network => $data) {
                        echo '<a href="' . htmlspecialchars($data['url']) . '" class="social-link" target="_blank" rel="opener referrer" title="' . htmlspecialchars($data['name']) . '">';
                        echo '<i class="' . htmlspecialchars($data['icon']) . '"></i>';
                        echo '</a>';
                    }
                } else {
                    // Fallback if no social networks are configured
                    echo '<p style="color: rgba(255,255,255,0.6); font-size: 0.8rem;">Social links will appear here when configured</p>';
                }
                ?>
            </div>
        </div>
    </div>

    <!-- Admin Login Modal -->
    <div id="adminModal" class="modal-overlay">
        <div class="modal-container">
            <div class="modal-header">
                <h3><i class="fas fa-shield-alt"></i> Administrator Login</h3>
                <button class="modal-close" onclick="closeAdminModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="modal-content">
                <form id="adminLoginForm" action="/index.php?page=form_login&maintenance=1" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token ?? ''); ?>">
                    <input type="hidden" name="maintenance_login" value="1">

                    <div class="form-group">
                        <label for="username" class="form-label">
                            <i class="fas fa-user"></i> Username or Email
                        </label>
                        <input
                            type="text"
                            id="username"
                            name="username"
                            class="form-input"
                            placeholder="Enter your credentials"
                            required
                            autocomplete="username"
                        >
                    </div>

                    <div class="form-group">
                        <label for="password" class="form-label">
                            <i class="fas fa-lock"></i> Password
                        </label>
                        <div class="password-wrapper">
                            <input
                                type="password"
                                id="password"
                                name="password"
                                class="form-input"
                                placeholder="Enter your password"
                                required
                                autocomplete="current-password"
                            >
                            <button type="button" class="password-toggle" onclick="togglePassword()">
                                <i class="fas fa-eye" id="passwordIcon"></i>
                            </button>
                        </div>
                    </div>

                    <div class="form-group remember-me">
                        <label class="checkbox-label">
                            <input type="checkbox" name="remember_me" id="remember_me">
                            <span class="checkbox-custom"></span>
                            <span class="checkbox-text">Remember me</span>
                        </label>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary btn-block">
                            <i class="fas fa-sign-in-alt"></i>
                            Login to Admin Panel
                        </button>
                    </div>
                </form>

                <div class="admin-notice">
                    <i class="fas fa-info-circle"></i>
                    <span>Only administrators can access the site during maintenance.</span>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="/themes/default/js/maintenance.js"></script>
</body>
</html>
