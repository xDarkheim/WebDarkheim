<?php

/**
 * Profile Edit Page
 *
 * This page allows users to edit their profile information, including
 * email, location, bio, and website URL.
 *
 * @author Dmytro Hovenko
 */

// Enable output buffering for correct redirect handling
ob_start();

// Set headers to prevent caching
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Ensure the session is started before working with $_SESSION/CSRF
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

use App\Application\Controllers\ProfileController;
use App\Application\Core\SessionManager;
use App\Domain\Interfaces\TokenManagerInterface;

// Use global services from the new DI architecture
global $flashMessageService, $tokenManager, $database_handler, $container, $serviceProvider;

// Get AuthenticationService
try {
    $authService = $serviceProvider->getAuth();
} catch (Exception $e) {
    error_log("Critical: Failed to get AuthenticationService instance: " . $e->getMessage());
    if (ob_get_level() > 0) { ob_end_clean(); }
    die("A critical system error occurred. Please try again later.");
}

// Initialize SessionManager and get GLOBAL CSRF token for middleware
try {
    $configManager = $serviceProvider->getConfigurationManager();
    $logger = $serviceProvider->getLogger();
    $sessionManager = SessionManager::getInstance($logger, [], $configManager);
} catch (Throwable $e) {
    // Fallback – at least create an instance with logger if config is unavailable
    $sessionManager = SessionManager::getInstance($serviceProvider->getLogger());
}
$globalCsrfToken = $sessionManager->getCsrfToken();
$current_user_id = $authService->getCurrentUserId();
$current_user_role = $authService->getCurrentUserRole();
$current_username = $authService->getCurrentUsername();
$userData_from_auth = $authService->getCurrentUser();

// Check required services
if (!isset($flashMessageService) || !isset($tokenManager) || !isset($database_handler) || !isset($container)) {
    error_log("Critical: Required services not available in profile_edit.php");
    if (ob_get_level() > 0) { ob_end_clean(); }
    die("A critical system error occurred. Please try again later.");
}

$userId = $current_user_id;

// Create ProfileController
try {
    $profileController = new ProfileController(
        $database_handler,
        $userId,
        $flashMessageService,
        $container->make(TokenManagerInterface::class)
    );
} catch (Exception $e) {
    error_log("Critical: Failed to create ProfileController: " . $e->getMessage());
    $flashMessageService->addError("Failed to initialize profile system. Please try again later.");
    if (ob_get_level() > 0) { ob_end_clean(); }
    header('Location: /index.php?page=dashboard');
    exit;
}

// Function to calculate account completeness progress
function calculateAccountCompleteness($userData): array
{
    $fields = [
        'email' => !empty($userData['email']) && $userData['email'] !== 'N/A' ? 1 : 0,
        'location' => !empty($userData['location']) ? 1 : 0,
        'bio' => !empty($userData['bio']) ? 1 : 0,
        'user_status' => !empty($userData['user_status']) ? 1 : 0,
        'website_url' => !empty($userData['website_url']) ? 1 : 0
    ];

    $completed = array_sum($fields);
    $total = count($fields);
    $percentage = round(($completed / $total) * 100);

    return [
        'percentage' => $percentage,
        'completed' => $completed,
        'total' => $total,
        'missing_fields' => array_keys(array_filter($fields, function($v) { return $v === 0; }))
    ];
}

// Function to get user statistics
function getUserStats($database_handler, $userId): array
{
    $stats = [
        'articles' => 0,
        'comments' => 0,
        'profile_views' => 0,
        'last_login' => 'Active now',
        'member_since' => 'This year'
    ];

    if ($database_handler && $pdo = $database_handler->getConnection()) {
        try {
            // Article statistics
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM articles WHERE user_id = ?");
            $stmt->execute([$userId]);
            $stats['articles'] = (int)$stmt->fetchColumn();

            // Comment statistics
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM comments WHERE user_id = ?");
            $stmt->execute([$userId]);
            $stats['comments'] = (int)$stmt->fetchColumn();

        } catch (PDOException $e) {
            error_log("User stats error: " . $e->getMessage());
        }
    }

    return $stats;
}

// Handle POST requests
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['update_profile_info']) || (isset($_POST['csrf_token_edit_profile_info']) && !isset($_POST['change_password_submit']))) {
        // Validate CSRF token
        if (!isset($_POST['csrf_token_edit_profile_info']) || $_POST['csrf_token_edit_profile_info'] !== $_SESSION['csrf_token_profile']) {
            $flashMessageService->addError('Security error: Invalid CSRF token for profile info. Please refresh and try again.');
        } else {
            // Get current user data for comparison
            $currentUserData = $profileController->getCurrentUserData();

            // Separate email and other profile fields processing
            $newEmail = trim($_POST['email'] ?? '');

            // Check if email has changed
            if (!empty($newEmail) && filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
                if ($currentUserData && strtolower($newEmail) !== strtolower($currentUserData['email'])) {
                    // Email changed – request confirmation
                    try {
                        $profileController->handleEmailChangeRequest();
                    } catch (Exception $e) {
                        error_log("Email change request failed: " . $e->getMessage());
                        $flashMessageService->addError('Failed to initiate email change. Please try again later.');
                    }
                }
            }

            // Process other profile fields
            $otherProfileData = [
                'location' => $_POST['location'] ?? '',
                'user_status' => $_POST['user_status'] ?? '',
                'bio' => $_POST['bio'] ?? '',
                'website_url' => $_POST['website_url'] ?? '',
            ];

            // Update profile fields
            try {
                $profileController->handleUpdateDetailsRequest($otherProfileData);
            } catch (Exception $e) {
                error_log("Profile update failed: " . $e->getMessage());
                $flashMessageService->addError('Failed to update profile. Please try again.');
            }
        }

        // Redirect to prevent form resubmission
        if (ob_get_level() > 0) { ob_end_clean(); }
        header('Location: /index.php?page=profile_edit');
        exit;

    } elseif (isset($_POST['change_password_submit'])) {
        // Validate CSRF token for password change
        if (!isset($_POST['csrf_token_change_password']) || $_POST['csrf_token_change_password'] !== $_SESSION['csrf_token_password']) {
            $flashMessageService->addError('Security error: Invalid CSRF token for password change. Please refresh and try again.');
        } else {
            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';

            try {
                $profileController->handleChangePasswordRequest($currentPassword, $newPassword, $confirmPassword);
            } catch (Exception $e) {
                error_log("Password change request failed: " . $e->getMessage());
                $flashMessageService->addError('Failed to process password change. Please try again later.');
            }
        }

        // Redirect
        if (ob_get_level() > 0) { ob_end_clean(); }
        header('Location: /index.php?page=profile_edit');
        exit;
    }
}

// Load user data and generate tokens for GET requests
$userData = $profileController->getCurrentUserData();

// Generate CSRF tokens
if (!isset($_SESSION['csrf_token_profile'])) {
    $_SESSION['csrf_token_profile'] = bin2hex(random_bytes(32));
}
if (!isset($_SESSION['csrf_token_password'])) {
    $_SESSION['csrf_token_password'] = bin2hex(random_bytes(32));
}

$csrf_token_profile = $_SESSION['csrf_token_profile'];
$csrf_token_password = $_SESSION['csrf_token_password'];

if (!$userData) {
    $userData = [
        'username' => 'N/A', 'email' => 'N/A',
        'location' => '', 'user_status' => '', 'bio' => '', 'website_url' => ''
    ];
    $flashMessageService->addError('Failed to load user data.');
    error_log("Edit Profile Page: Could not load user data for user ID: " . $userId);
}

// Calculate progress and statistics
$accountProgress = calculateAccountCompleteness($userData);
$userStats = getUserStats($database_handler, $userId);

// End output buffering
if (ob_get_level() > 0) { ob_end_clean(); }
?>
<div class="profile-edit-page-wrapper">
<script>
(function(){
    try {
        const emailInput = document.querySelector('.profile-edit-page-wrapper input[name="email"]') || document.querySelector('.profile-edit-page-wrapper #email');
        const usernameDisplay = document.getElementById('username-display');
        if (emailInput) {
            // Make email non-editable
            emailInput.setAttribute('readonly','readonly');
            emailInput.setAttribute('aria-readonly','true');
            emailInput.setAttribute('tabindex','-1');

            // Reuse the same classes/styles as username-display (no new styles created)
            if (usernameDisplay) {
                emailInput.className = usernameDisplay.className;
            }

            // Add an info note near the email field if not present
            const parent = emailInput.parentNode;
            if (parent && !parent.querySelector('.email-locked-note')) {
                const note = document.createElement('div');
                note.className = 'email-locked-note';
                note.textContent = 'Email change is disabled for security. Please contact support if you need to update it.';
                parent.insertBefore(note, emailInput.nextSibling);
            }
        }
    } catch(e) {}
})();
</script>
<div class="admin-layout">
    <!-- Enhanced Main Header Section -->
    <header class="page-header">
        <div class="page-header-content">
            <div class="page-header-main">
                <h1 class="page-title">
                    <i class="fas fa-user-edit"></i>
                    Edit Profile
                </h1>
                <div class="page-header-description">
                    <p>Manage your account information, public profile, and security settings</p>
                </div>
            </div>
            <div class="page-header-actions">
                <a href="/index.php?page=dashboard" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
                <a href="/index.php?page=profile&user=<?php echo htmlspecialchars($userData['username'] ?? ''); ?>" class="btn btn-outline">
                    <i class="fas fa-eye"></i> View Profile
                </a>
            </div>
        </div>
    </header>

    <!-- Flash Messages -->
    <?php
    if (isset($flashMessageService) && $flashMessageService->hasMessages()) {
        $messages = $flashMessageService->getMessages();
        if (!empty($messages)) {
    ?>
        <div class="flash-messages-container">
            <?php foreach ($messages as $type => $messageList): ?>
                <?php foreach ($messageList as $message): ?>
                    <div class="message message--<?php echo htmlspecialchars($type); ?>">
                        <p><?php echo $message['is_html'] ? $message['text'] : htmlspecialchars($message['text']); ?></p>
                    </div>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </div>
    <?php
        }
    }
    ?>

    <!-- Main Content Layout -->
    <div class="content-layout">
        <!-- Primary Content Area -->
        <main class="main-content" style="max-width: 800px;">
            <!-- Profile Edit Form -->
            <div class="form-wrapper">
                <div class="card card-primary">
                    <div class="card-header">
                        <div class="card-header-content">
                            <h2 class="card-title">
                                <i class="fas fa-user"></i> Profile Information
                            </h2>
                            <div class="card-header-meta">
                                <div class="creation-info">
                                    <small class="creation-date">
                                        <i class="fas fa-calendar-alt"></i>
                                        Last updated: <?php echo date('M j, Y \a\t g:i A'); ?>
                                    </small>
                                    <small class="author-info">
                                        <i class="fas fa-user"></i>
                                        User: <?php echo htmlspecialchars($userData['username'] ?? 'Unknown'); ?>
                                    </small>
                                </div>
                                <div class="article-status">
                                    <span class="status-badge status-profile">
                                        <i class="fas fa-globe"></i>
                                        Public Profile
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="card-header-actions">
                            <button type="button" class="btn-icon btn-toggle-help" onclick="toggleHelp()" title="Toggle Help">
                                <i class="fas fa-question-circle"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <form action="/index.php?page=profile_edit" method="post" class="article-creation-form" id="profileForm">
                            <input type="hidden" name="csrf_token_edit_profile_info" value="<?php echo htmlspecialchars($csrf_token_profile); ?>">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($globalCsrfToken); ?>">

                            <!-- Step 1: Account Information -->
                            <div class="form-section" data-section="1">
                                <div class="section-header">
                                    <h3 class="section-title">
                                        <i class="fas fa-id-card"></i> Account Information
                                    </h3>
                                    <p class="section-description">Update your essential account details and contact information</p>
                                </div>
                                <div class="form-grid">
                                    <div class="form-group form-group-half">
                                        <label for="username-display" class="form-label">
                                            Username
                                            <span class="field-info" title="Your unique identifier - contact support to change">
                                                <i class="fas fa-info-circle"></i>
                                            </span>
                                        </label>
                                        <input type="text" id="username-display" name="username_display"
                                               class="form-control"
                                               value="<?php echo htmlspecialchars($userData['username'] ?? ''); ?>"
                                               disabled>
                                        <div class="form-help-text">
                                            <i class="fas fa-lock"></i>
                                            Your unique identifier. Contact support to change this.
                                        </div>
                                    </div>

                                    <div class="form-group form-group-half">
                                        <label for="email" class="form-label">
                                            Email Address <span class="required-indicator">*</span>
                                        </label>
                                        <input type="text" id="username-display" name="username-display"
                                               class="form-control"
                                               value="<?php echo htmlspecialchars($userData['email'] ?? ''); ?>"
                                               disabled>
                                        <div class="form-help-text">
                                            <i class="fas fa-lock"></i>
                                            Your unique identifier. Contact support to change this.
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Step 2: Public Profile -->
                            <div class="form-section" data-section="2">
                                <div class="section-header">
                                    <h3 class="section-title">
                                        <i class="fas fa-globe"></i> Public Profile
                                    </h3>
                                    <p class="section-description">Information that will be visible to other users on your profile</p>
                                </div>
                                <div class="form-grid">
                                    <div class="form-group form-group-half">
                                        <label for="location" class="form-label">
                                            Location
                                            <span class="optional-indicator">(Optional)</span>
                                        </label>
                                        <div class="input-wrapper">
                                            <input type="text" id="location" name="location"
                                                   class="form-control"
                                                   value="<?php echo htmlspecialchars($userData['location'] ?? ''); ?>"
                                                   placeholder="City, Country"
                                                   maxlength="100">
                                            <div class="character-counter">
                                                <span id="locationCounter"><?php echo strlen($userData['location'] ?? ''); ?></span>/100
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group form-group-half">
                                        <label for="website_url" class="form-label">
                                            Website
                                            <span class="optional-indicator">(Optional)</span>
                                        </label>
                                        <input type="url" id="website_url" name="website_url"
                                               class="form-control"
                                               value="<?php echo htmlspecialchars($userData['website_url'] ?? ''); ?>"
                                               placeholder="https://example.com">
                                    </div>

                                    <div class="form-group form-group-full">
                                        <label for="user_status" class="form-label">
                                            Current Status
                                            <span class="optional-indicator">(Optional)</span>
                                        </label>
                                        <div class="input-wrapper">
                                            <input type="text" id="user_status" name="user_status"
                                                   class="form-control"
                                                   value="<?php echo htmlspecialchars($userData['user_status'] ?? ''); ?>"
                                                   placeholder="What are you working on?"
                                                   maxlength="150">
                                            <div class="character-counter">
                                                <span id="statusCounter"><?php echo strlen($userData['user_status'] ?? ''); ?></span>/150
                                            </div>
                                        </div>
                                        <div class="form-help-text">
                                            <i class="fas fa-comment-dots"></i>
                                            Share what you're currently working on or thinking about
                                        </div>
                                    </div>

                                    <div class="form-group form-group-full">
                                        <label for="bio" class="form-label">
                                            About Me
                                            <span class="optional-indicator">(Optional)</span>
                                        </label>
                                        <div class="textarea-wrapper">
                                            <textarea id="bio" name="bio"
                                                      class="form-control" rows="4"
                                                      placeholder="Tell us about yourself, your interests, and experience..."
                                                      maxlength="1000"><?php echo htmlspecialchars($userData['bio'] ?? ''); ?></textarea>
                                            <div class="character-counter">
                                                <span id="bioCounter"><?php echo strlen($userData['bio'] ?? ''); ?></span>/1000
                                            </div>
                                        </div>
                                        <div class="form-help-text">
                                            <i class="fas fa-user-circle"></i>
                                            Describe yourself in a few sentences
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Step 3: Profile Preview -->
                            <div class="form-section" data-section="3">
                                <div class="section-header">
                                    <h3 class="section-title">
                                        <i class="fas fa-eye"></i> Profile Preview
                                    </h3>
                                    <p class="section-description">See how your profile will appear to other users</p>
                                </div>
                                <div class="article-preview-card">
                                    <div class="preview-header">
                                        <h4>Your Public Profile</h4>
                                        <span class="preview-badge">Live Preview</span>
                                    </div>
                                    <div class="preview-content">
                                        <h5 id="previewUsername"><?php echo htmlspecialchars($userData['username'] ?? 'Username'); ?></h5>
                                        <div class="preview-meta">
                                            <span id="previewLocation"><?php echo htmlspecialchars($userData['location'] ?: 'No location'); ?></span>
                                            <?php if (!empty($userData['website_url'])): ?>
                                                <span class="separator">•</span>
                                                <span id="previewWebsite">Has website</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="preview-status" id="previewStatus">
                                            <?php echo htmlspecialchars($userData['user_status'] ?: 'No current status'); ?>
                                        </div>
                                        <div class="preview-description" id="previewBio">
                                            <?php echo htmlspecialchars($userData['bio'] ?: 'No bio available'); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Enhanced Action Buttons -->
                            <div class="form-actions-redesigned">
                                <div class="form-actions-container">
                                    <button type="submit" name="update_profile_info" class="btn btn-publish">
                                        <i class="fas fa-save"></i>
                                        <span>Save Profile</span>
                                    </button>

                                    <button type="button" class="btn btn-save-draft" onclick="resetForm()">
                                        <i class="fas fa-undo"></i>
                                        <span>Reset Changes</span>
                                    </button>

                                    <a href="/index.php?page=dashboard" class="btn btn-cancel">
                                        <i class="fas fa-times"></i>
                                        <span>Cancel</span>
                                    </a>
                                </div>

                                <div class="form-actions-help">
                                    <div class="keyboard-shortcuts">
                                        <small>
                                            <i class="fas fa-keyboard"></i>
                                            <strong>Tips:</strong>
                                            Complete all fields for a more engaging profile
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </main>

        <!-- Enhanced Compact Sidebar -->
        <aside class="sidebar-content" style="min-width: 280px; max-width: 320px;">
            <!-- Security Settings Card -->
            <div class="card card-compact sidebar-card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-shield-alt"></i> Security
                    </h3>
                </div>
                <div class="card-body">
                    <form action="/index.php?page=profile_edit" method="post" class="security-form" id="securityForm">
                        <input type="hidden" name="csrf_token_change_password" value="<?php echo htmlspecialchars($csrf_token_password); ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($globalCsrfToken); ?>">

                        <div class="form-group">
                            <label for="current_password" class="form-label">
                                Current Password <span class="required-indicator">*</span>
                            </label>
                            <input type="password" id="current_password" name="current_password"
                                   class="form-control" required>
                        </div>

                        <div class="form-group">
                            <label for="new_password" class="form-label">
                                New Password <span class="required-indicator">*</span>
                            </label>
                            <input type="password" id="new_password" name="new_password"
                                   class="form-control" required minlength="8">
                            <div class="password-strength" id="passwordStrength">
                                <div class="strength-bar">
                                    <div class="strength-fill"></div>
                                </div>
                                <small class="strength-text">Password strength</small>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="confirm_password" class="form-label">
                                Confirm Password <span class="required-indicator">*</span>
                            </label>
                            <input type="password" id="confirm_password" name="confirm_password"
                                   class="form-control" required minlength="8">
                            <small class="form-help" id="passwordMatch"></small>
                        </div>

                        <button type="submit" name="change_password_submit" class="btn btn-danger btn-full">
                            <i class="fas fa-key"></i> Update Password
                        </button>
                    </form>
                </div>
            </div>

            <!-- Profile Progress Card -->
            <div class="card card-compact sidebar-card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-tasks"></i> Profile Progress
                    </h3>
                </div>
                <div class="card-body">
                    <div class="progress-overview">
                        <div class="progress-bar-wrapper">
                            <div class="progress-bar" id="profileProgress">
                                <div class="progress-fill" style="width: <?php echo $accountProgress['percentage']; ?>%"></div>
                            </div>
                            <span class="progress-percentage"><?php echo $accountProgress['percentage']; ?>%</span>
                        </div>
                    </div>
                    <div class="progress-checklist">
                        <div class="progress-item <?php echo !empty($userData['email']) ? 'completed' : ''; ?>">
                            <i class="fas fa-circle progress-icon"></i>
                            <span class="progress-text">Email address</span>
                        </div>
                        <div class="progress-item <?php echo !empty($userData['location']) ? 'completed' : ''; ?>">
                            <i class="fas fa-circle progress-icon"></i>
                            <span class="progress-text">Location</span>
                        </div>
                        <div class="progress-item <?php echo !empty($userData['bio']) ? 'completed' : ''; ?>">
                            <i class="fas fa-circle progress-icon"></i>
                            <span class="progress-text">About me</span>
                        </div>
                        <div class="progress-item <?php echo !empty($userData['user_status']) ? 'completed' : ''; ?>">
                            <i class="fas fa-circle progress-icon"></i>
                            <span class="progress-text">Current status</span>
                        </div>
                        <div class="progress-item <?php echo !empty($userData['website_url']) ? 'completed' : ''; ?>">
                            <i class="fas fa-circle progress-icon"></i>
                            <span class="progress-text">Website</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Activity Stats Card -->
            <div class="card card-compact sidebar-card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-chart-line"></i> Your Activity
                    </h3>
                </div>
                <div class="card-body">
                    <div class="writing-stats">
                        <div class="stat-item">
                            <div class="stat-icon">
                                <i class="fas fa-file-alt"></i>
                            </div>
                            <div class="stat-info">
                                <span class="stat-number"><?php echo $userStats['articles']; ?></span>
                                <span class="stat-label">Articles</span>
                            </div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-icon">
                                <i class="fas fa-comments"></i>
                            </div>
                            <div class="stat-info">
                                <span class="stat-number"><?php echo $userStats['comments']; ?></span>
                                <span class="stat-label">Comments</span>
                            </div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-icon">
                                <i class="fas fa-percentage"></i>
                            </div>
                            <div class="stat-info">
                                <span class="stat-number"><?php echo $accountProgress['percentage']; ?>%</span>
                                <span class="stat-label">Complete</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Navigation Card -->
            <div class="card card-compact sidebar-card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-compass"></i> Quick Links
                    </h3>
                </div>
                <div class="card-body">
                    <nav class="quick-nav">
                        <a href="/index.php?page=dashboard" class="quick-nav-link">
                            <i class="fas fa-tachometer-alt"></i>
                            <span>Dashboard</span>
                        </a>
                        <a href="/index.php?page=profile&user=<?php echo htmlspecialchars($userData['username'] ?? ''); ?>" class="quick-nav-link">
                            <i class="fas fa-eye"></i>
                            <span>View Profile</span>
                        </a>
                        <a href="/index.php?page=profile_settings" class="quick-nav-link">
                            <i class="fas fa-cog"></i>
                            <span>Settings</span>
                        </a>
                        <a href="/index.php?page=create_article" class="quick-nav-link">
                            <i class="fas fa-plus"></i>
                            <span>New Article</span>
                        </a>
                    </nav>
                </div>
            </div>
        </aside>
    </div>
</div>
</div>

<script>
// Character counters for form fields
function updateCounter(inputId, counterId, maxLength) {
    const input = document.getElementById(inputId);
    const counter = document.getElementById(counterId);

    if (input && counter) {
        input.addEventListener('input', function() {
            const count = this.value.length;
            counter.textContent = count;

            const wrapper = counter.closest('.character-counter');
            if (count > maxLength * 0.8) {
                wrapper.classList.add('text-warning');
            } else {
                wrapper.classList.remove('text-warning');
            }
        });
    }
}

// Initialize character counters
updateCounter('location', 'locationCounter', 100);
updateCounter('user_status', 'statusCounter', 150);
updateCounter('bio', 'bioCounter', 1000);

// Live preview updates
function updatePreview() {
    document.getElementById('previewUsername');
    const previewLocation = document.getElementById('previewLocation');
    const previewStatus = document.getElementById('previewStatus');
    const previewBio = document.getElementById('previewBio');

    const location = document.getElementById('location').value;
    const status = document.getElementById('user_status').value;
    const bio = document.getElementById('bio').value;

    if (previewLocation) previewLocation.textContent = location || 'No location';
    if (previewStatus) previewStatus.textContent = status || 'No current status';
    if (previewBio) previewBio.textContent = bio || 'No bio available';
}

// Add event listeners for a live preview
['location', 'user_status', 'bio'].forEach(fieldId => {
    const field = document.getElementById(fieldId);
    if (field) {
        field.addEventListener('input', updatePreview);
    }
});

// Password strength indicator
document.getElementById('new_password').addEventListener('input', function() {
    const password = this.value;
    const strengthElement = document.getElementById('passwordStrength');
    const strengthFill = strengthElement.querySelector('.strength-fill');
    const strengthText = strengthElement.querySelector('.strength-text');

    let strength = 0;
    let strengthLabel;

    if (password.length >= 8) strength++;
    if (/[A-Z]/.test(password)) strength++;
    if (/[a-z]/.test(password)) strength++;
    if (/[0-9]/.test(password)) strength++;
    if (/[^A-Za-z0-9]/.test(password)) strength++;

    const percentage = (strength / 5) * 100;
    strengthFill.style.width = percentage + '%';

    if (strength <= 2) {
        strengthFill.className = 'strength-fill weak';
        strengthLabel = 'Weak';
    } else if (strength <= 3) {
        strengthFill.className = 'strength-fill medium';
        strengthLabel = 'Medium';
    } else {
        strengthFill.className = 'strength-fill strong';
        strengthLabel = 'Strong';
    }

    strengthText.textContent = password.length > 0 ? strengthLabel : 'Password strength';
});

// Password confirmation check
document.getElementById('confirm_password').addEventListener('input', function() {
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = this.value;
    const matchElement = document.getElementById('passwordMatch');

    if (confirmPassword.length > 0) {
        if (newPassword === confirmPassword) {
            matchElement.innerHTML = '<i class="fas fa-check text-success"></i> Password match';
            matchElement.className = 'form-help text-success';
        } else {
            matchElement.innerHTML = '<i class="fas fa-times text-danger"></i> Passwords do not match';
            matchElement.className = 'form-help text-danger';
        }
    } else {
        matchElement.innerHTML = '';
    }
});

// Reset form function
function resetForm() {
    if (confirm('Are you sure you want to reset all changes?')) {
        document.getElementById('profileForm').reset();
        updatePreview();
    }
}

// Tips carousel functionality
let currentTip = 0;
const tips = document.querySelectorAll('.tip-item');

function showTip(index) {
    tips.forEach((tip, i) => {
        tip.classList.toggle('active', i === index);
    });
}

function nextTip() {
    currentTip = (currentTip + 1) % tips.length;
    showTip(currentTip);
}

function previousTip() {
    currentTip = (currentTip - 1 + tips.length) % tips.length;
    showTip(currentTip);
}

// Auto-rotate tips every 5 seconds
setInterval(nextTip, 5000);

// Helper functions for consistency
function toggleHelp() {
    // Implementation for help toggle
    console.log('Help toggled');
}
</script>
