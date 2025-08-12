<?php

/**
 * Error Page
 *
 * This page is used to display error messages and debug information.
 * It is designed to be used in production environments.
 *
 * @author Dmytro Hovenko
 */

// Start with safe default values
$site_name = 'Darkheim Development Studio';
$debug_mode = false;
$environment = 'unknown';
$has_errors = false;
$error_details = [];
$controller_error = null;

// Try to get basic information safely
try {
    // Check isDebugMode function
    if (function_exists('isDebugMode')) {
        $debug_mode = isDebugMode();
    } elseif (defined('APP_DEBUG')) {
        $debug_mode = APP_DEBUG;
    }

    // Get environment
    if (defined('APP_ENV')) {
        $environment = APP_ENV;
    }

    // Check session and errors
    if (session_status() === PHP_SESSION_ACTIVE) {
        $has_errors = isset($_SESSION['error_message']);
        if ($has_errors) {
            $error_details = [
                'message' => $_SESSION['error_message'] ?? 'Unknown error',
                'trace' => $_SESSION['error_trace'] ?? 'No trace available',
                'context' => $_SESSION['error_context'] ?? []
            ];
        }
    }

    // Try to get a site name
    global $site_settings_from_db;
    if (isset($site_settings_from_db['general']['site_name']['value'])) {
        $site_name = $site_settings_from_db['general']['site_name']['value'];
    }

} catch (Exception $e) {
    $controller_error = $e->getMessage();
}

// Clear errors from the session after retrieval
if ($has_errors && session_status() === PHP_SESSION_ACTIVE) {
    unset($_SESSION['error_message']);
    unset($_SESSION['error_trace']);
    unset($_SESSION['error_context']);
}
?>

<?php if (!$debug_mode): ?>
    <!-- APPLICATION ERROR Hero section (production only) -->
    <section class="corporate-hero">
        <div class="container">
            <div class="hero-content">
                <div class="hero-badge">
                    <span class="badge-text">APPLICATION ERROR</span>
                </div>

                <h1 class="corporate-title">
                    System
                    <span class="title-accent">Error</span>
                </h1>

                <p class="corporate-subtitle">
                    An unexpected error occurred while processing your request.
                    Our technical team has been notified and is working to resolve the issue.
                </p>

                <div class="hero-actions">
                    <a href="/index.php?page=home" class="button button--primary button--lg">
                        Return to Home
                    </a>
                </div>
            </div>
        </div>
    </section>
<?php endif; ?>

<?php if ($debug_mode): ?>
    <!-- Debug Hero Header -->
    <section class="corporate-hero debug-hero">
        <div class="container">
            <div class="hero-content">
                <div class="hero-badge">
                    <span class="badge-text">DEBUG MODE</span>
                </div>

                <h1 class="corporate-title">
                    System
                    <span class="title-accent">Debug</span>
                </h1>

                <p class="corporate-subtitle">
                    Detailed error information and system diagnostics for development purposes.
                    This information is only visible in debug mode.
                </p>
            </div>
        </div>
    </section>

    <!-- Debug Information (debug mode only) -->
    <section class="corporate-section">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Debug Information</h2>
                <p class="section-description">
                    Detailed error information for development purposes
                </p>
            </div>

            <div class="debug-container">
                <div class="card card--danger">
                    <div class="card-header card-header-enhanced">
                        <div class="card-header-content">
                            <div class="service-icon">🐛</div>
                            <div>
                                <h3 class="card-title">Error Details</h3>
                                <div class="card-subtitle">Debug Mode Active</div>
                            </div>
                        </div>
                        <div class="card-header-meta">
                            <span class="tech-badge tech-badge--danger">ERROR</span>
                        </div>
                    </div>

                    <div class="card-body">
                        <div class="card-content">
                            <?php if ($has_errors): ?>
                                <div class="error-details">
                                    <!-- Error Message -->
                                    <div class="error-section">
                                        <div class="error-section-header">
                                            <h4 class="error-section-title">
                                                <span class="error-icon">💥</span>
                                                Error Message
                                            </h4>
                                        </div>
                                        <div class="code-block code-block--error">
                                            <?php echo htmlspecialchars($error_details['message']); ?>
                                        </div>
                                    </div>

                                    <?php if (!empty($error_details['trace'])): ?>
                                    <!-- Stack Trace -->
                                    <div class="error-section">
                                        <div class="error-section-header">
                                            <h4 class="error-section-title">
                                                <span class="error-icon">📋</span>
                                                Stack Trace
                                            </h4>
                                        </div>
                                        <div class="code-block code-block--trace">
                                            <?php echo htmlspecialchars($error_details['trace']); ?>
                                        </div>
                                    </div>
                                    <?php endif; ?>

                                    <?php if (!empty($error_details['context'])): ?>
                                    <!-- Context Information -->
                                    <div class="error-section">
                                        <div class="error-section-header">
                                            <h4 class="error-section-title">
                                                <span class="error-icon">📊</span>
                                                Context Information
                                            </h4>
                                        </div>
                                        <div class="code-block code-block--context">
                                            <?php echo htmlspecialchars(print_r($error_details['context'], true)); ?>
                                        </div>
                                    </div>
                                    <?php endif; ?>

                                    <!-- System Status -->
                                    <div class="error-section">
                                        <div class="error-section-header">
                                            <h4 class="error-section-title">
                                                <span class="error-icon">⚙️</span>
                                                System Status
                                            </h4>
                                        </div>
                                        <div class="debug-status">
                                            <div class="status-item">
                                                <span class="status-label">Environment:</span>
                                                <span class="tech-badge tech-badge--info"><?php echo htmlspecialchars($environment); ?></span>
                                            </div>
                                            <div class="status-item">
                                                <span class="status-label">Debug Mode:</span>
                                                <span class="tech-badge tech-badge--success">ON</span>
                                            </div>
                                            <div class="status-item">
                                                <span class="status-label">Timestamp:</span>
                                                <span class="tech-badge tech-badge--secondary"><?php echo date('Y-m-d H:i:s'); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="info-notice">
                                    <div class="notice-header">
                                        <span class="notice-icon">ℹ️</span>
                                        <strong>No Error Data Available</strong>
                                    </div>
                                    <p>Debug mode is enabled, but no error details are available in the session. This typically means you're viewing the error page directly without an actual error occurring.</p>
                                </div>
                            <?php endif; ?>

                            <?php if ($controller_error): ?>
                            <div class="error-notice">
                                <div class="notice-header">
                                    <span class="notice-icon">⚠️</span>
                                    <strong>Controller Error</strong>
                                </div>
                                <p><?php echo htmlspecialchars($controller_error); ?></p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="card-footer">
                        <div class="debug-actions">
                            <a href="/index.php?page=home" class="btn btn-primary">
                                <i class="icon">🏠</i>
                                <span>Return to Home</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
<?php endif; ?>

<style>
/* Darkheim error page styles - STATIC VERSION WITHOUT ANIMATIONS */

/* Disable all animations and transitions */
*, *::before, *::after {
    animation-duration: 0s !important;
    animation-delay: 0s !important;
    transition-duration: 0s !important;
    transition-delay: 0s !important;
}

/* Debug container */
.debug-container {
    max-width: 1200px;
    margin: 0 auto;
}

/* Card content styling */
.card-content {
    padding: 0;
}

/* Error sections */
.error-section {
    margin-bottom: var(--spacing-xl);
}

.error-section:last-child {
    margin-bottom: 0;
}

.error-section-header {
    margin-bottom: var(--spacing-md);
}

.error-section-title {
    font-family: var(--font-family-sans), serif;
    font-size: var(--font-size-lg);
    font-weight: var(--font-weight-semibold);
    color: #ffffff !important; /* White text for readability */
    display: flex;
    align-items: center;
    gap: var(--spacing-sm);
    margin: 0;
}

.error-icon {
    font-size: var(--font-size-xl);
    flex-shrink: 0;
}

/* Tech badges with variants - NO ANIMATIONS */
.tech-badge {
    background: #2a2a2a !important;
    color: #ffffff !important;
    padding: var(--spacing-xs) var(--spacing-sm);
    border-radius: var(--radius-lg);
    font-size: var(--font-size-xs);
    font-weight: var(--font-weight-semibold);
    text-transform: uppercase;
    letter-spacing: 0.05em;
    border: 1px solid #444444 !important;
    display: inline-flex;
    align-items: center;
    gap: var(--spacing-xs);
}

.tech-badge--danger {
    background: #dc3545 !important;
    border-color: #dc3545 !important;
    color: #ffffff !important;
}

.tech-badge--success {
    background: #28a745 !important;
    border-color: #28a745 !important;
    color: #ffffff !important;
}

.tech-badge--info {
    background: #17a2b8 !important;
    border-color: #17a2b8 !important;
    color: #ffffff !important;
}

.tech-badge--secondary {
    background: #6c757d !important;
    color: #ffffff !important;
    border-color: #6c757d !important;
}

/* Debug status */
.debug-status {
    display: flex;
    flex-wrap: wrap;
    gap: var(--spacing-md);
    padding: var(--spacing-lg);
    background: #2a2a2a !important;
    border-radius: var(--radius-lg);
    border: 1px solid #444444 !important;
}

.status-item {
    display: flex;
    align-items: center;
    gap: var(--spacing-sm);
}

.status-label {
    font-weight: var(--font-weight-medium);
    color: #ffffff !important; /* White text */
    font-size: var(--font-size-sm);
}

/* Notice components */
.notice-header {
    display: flex;
    align-items: center;
    gap: var(--spacing-sm);
    margin-bottom: var(--spacing-sm);
}

.notice-icon {
    font-size: var(--font-size-lg);
    flex-shrink: 0;
}

.error-notice {
    background: #2a1e1e !important;
    border: 1px solid #dc3545 !important;
    color: #ffffff !important; /* White text */
    padding: var(--spacing-lg);
    border-radius: var(--radius-lg);
    font-size: var(--font-size-sm);
    border-left: 4px solid #dc3545 !important;
}

.info-notice {
    background: #1e2a2a !important;
    border: 1px solid #17a2b8 !important;
    color: #ffffff !important; /* White text */
    padding: var(--spacing-lg);
    border-radius: var(--radius-lg);
    font-size: var(--font-size-sm);
    border-left: 4px solid #17a2b8 !important;
}

.info-notice p {
    color: #ffffff !important; /* White text in paragraphs */
    margin: 0;
}

/* Code blocks with variants - NO ANIMATIONS */
.code-block {
    background: #1a1a1a !important;
    border: 1px solid #444444 !important;
    border-radius: var(--radius-lg);
    padding: var(--spacing-lg);
    font-size: var(--font-size-sm);
    color: #e6e6e6 !important; /* Light gray text for code */
    overflow-x: auto;
    white-space: pre-wrap;
    word-break: break-word;
    position: relative;
    line-height: var(--line-height-relaxed);
}

.code-block::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: #007bff !important;
    border-radius: var(--radius-lg) var(--radius-lg) 0 0;
}

.code-block--error::before {
    background: #dc3545 !important;
}

.code-block--trace {
    background: #1a1a1a !important;
    border-color: #dc3545 !important;
    color: #ffb3b3 !important; /* Light red for trace */
    max-height: 400px;
    overflow-y: auto;
}

.code-block--trace::before {
    background: #dc3545 !important;
}

.code-block--context {
    background: #1a1a1a !important;
    border-color: #17a2b8 !important;
    color: #b3e6ff !important; /* Light blue for context */
}

.code-block--context::before {
    background: #17a2b8 !important;
}

/* Debug actions - STATIC BUTTONS */
.debug-actions {
    display: flex;
    gap: var(--spacing-md);
    justify-content: flex-start;
    flex-wrap: wrap;
}

.btn {
    display: inline-flex;
    align-items: center;
    gap: var(--spacing-sm);
    padding: var(--spacing-md) var(--spacing-lg);
    font-size: var(--font-size-sm);
    font-weight: var(--font-weight-medium);
    text-decoration: none;
    border: 2px solid transparent;
    border-radius: var(--radius-lg);
    cursor: pointer;
    white-space: nowrap;
    user-select: none;
    position: static; /* Remove relative positioning */
    overflow: visible; /* Remove hidden overflow */
    min-width: 140px;
    justify-content: center;
}

.btn-primary {
    background: #007bff !important;
    color: #ffffff !important;
    border-color: #0056b3 !important;
}

.btn-primary:hover {
    background: #0056b3 !important;
    color: #ffffff !important;
}

.btn .icon {
    font-size: var(--font-size-md);
    flex-shrink: 0;
}

.btn span {
    font-weight: var(--font-weight-medium);
}

/* Service icon in card header */
.service-icon {
    font-size: var(--font-size-2xl);
    margin-right: var(--spacing-sm);
    flex-shrink: 0;
}

/* Card title and subtitle for better readability */
.card-title {
    color: #ffffff !important;
}

.card-subtitle {
    color: #cccccc !important;
}

/* Custom scrollbars for code blocks - STATIC */
.code-block::-webkit-scrollbar {
    width: 8px;
    height: 8px;
}

.code-block::-webkit-scrollbar-track {
    background: #1a1a1a !important;
    border-radius: var(--radius-sm);
}

.code-block::-webkit-scrollbar-thumb {
    background: #007bff !important;
    border-radius: var(--radius-sm);
}

.code-block::-webkit-scrollbar-thumb:hover {
    background: #0056b3 !important;
}

/* Responsive adaptations */
@media (max-width: 768px) {
    .debug-status {
        flex-direction: column;
        gap: var(--spacing-sm);
        padding: var(--spacing-md);
    }

    .debug-actions {
        flex-direction: column;
        gap: var(--spacing-sm);
    }

    .btn {
        width: 100%;
        min-width: auto;
    }

    .code-block {
        padding: var(--spacing-md);
        font-size: var(--font-size-xs);
    }

    .error-section-title {
        font-size: var(--font-size-md);
    }

    .service-icon {
        font-size: var(--font-size-xl);
    }
}

@media (max-width: 480px) {
    .debug-container {
        margin: 0 (--spacing-sm);
    }

    .card {
        border-radius: var(--radius-md);
    }

    .error-section {
        margin-bottom: var(--spacing-lg);
    }

    .notice-header {
        flex-direction: column;
        align-items: flex-start;
        gap: var(--spacing-xs);
    }
}

/* Ensure readability in any theme */
.section-title {
    position: relative;
    color: #ffffff !important;
}

.section-title::after {
    content: '';
    position: absolute;
    bottom: calc(-1 * var(--spacing-sm));
    left: 0;
    width: 60px;
    height: 3px;
    background: #007bff !important;
    border-radius: var(--radius-sm);
}

.section-description {
    color: #cccccc !important;
}

/* Hero section for production */
.hero-badge .badge-text {
    background: #007bff !important;
    color: #ffffff !important;
    font-weight: var(--font-weight-bold);
}

.corporate-title {
    color: #ffffff !important;
}

.title-accent {
    color: #007bff !important;
}

.corporate-subtitle {
    color: #cccccc !important;
}

/* Notice header titles */
.notice-header strong {
    color: #ffffff !important;
}
</style>
