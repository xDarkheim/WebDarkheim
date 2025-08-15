<?php
/**
 * User Profile View Page - PHASE 8 - DARK ADMIN THEME
 * Shows user profile information (read-only view)
 */

declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/includes/bootstrap.php';
// Include profile completion helper
require_once dirname(__DIR__, 3) . '/includes/profile_completion_helper.php';

global $serviceProvider, $flashMessageService, $database_handler;

use App\Application\Components\AdminNavigation;

try {
    $authService = $serviceProvider->getAuth();
} catch (Exception $e) {
    error_log("Critical: Failed to get AuthenticationService: " . $e->getMessage());
    die("System error occurred.");
}

// Check authentication
if (!$authService->isAuthenticated()) {
    $flashMessageService->addError('Please log in to access this area.');
    header("Location: /index.php?page=login");
    exit();
}

$currentUser = $authService->getCurrentUser();
$userId = $authService->getCurrentUserId();

$pageTitle = 'My Profile';

// Create unified navigation
$adminNavigation = new AdminNavigation($authService);

// Calculate profile completion using unified helper function
$clientProfile = getClientProfileData($database_handler, $userId);
$completion = calculateProfileCompletion($currentUser, $clientProfile);

// Get flash messages
$flashMessages = $flashMessageService->getAllMessages();
?>

    <link rel="stylesheet" href="/public/assets/css/admin.css">

    <!-- Unified Navigation -->
    <?= $adminNavigation->render() ?>

    <!-- Header -->
    <header class="admin-header">
        <div class="admin-header-container">
            <div class="admin-header-content">
                <div class="admin-header-title">
                    <i class="admin-header-icon fas fa-user"></i>
                    <div class="admin-header-text">
                        <h1>My Profile</h1>
                        <p>View and manage your profile information</p>
                    </div>
                </div>
                <div class="admin-header-actions">
                    <a href="/index.php?page=profile_edit" class="admin-btn admin-btn-primary">
                        <i class="fas fa-edit"></i> Edit Profile
                    </a>
                    <a href="/index.php?page=profile_edit" class="admin-btn admin-btn-secondary">
                        <i class="fas fa-cogs"></i> Settings
                    </a>
                </div>
            </div>
        </div>
    </header>

<!-- Flash messages handled by global toast system -->

    <!-- Main Content -->
    <div class="admin-layout-main">
        <div class="admin-content">
            <!-- Basic Information -->
            <div class="admin-card">
                <div class="admin-card-header">
                    <h5 class="admin-card-title">
                        <i class="fas fa-info-circle"></i>
                        Basic Information
                    </h5>
                </div>
                <div class="admin-card-body">
                    <div class="admin-grid admin-grid-cols-2">
                        <div>
                            <div class="admin-form-group">
                                <label class="admin-label">Username</label>
                                <div class="admin-input" style="background: var(--admin-bg-secondary); border: none;">
                                    <?= htmlspecialchars($currentUser['username']) ?>
                                </div>
                            </div>
                            <div class="admin-form-group">
                                <label class="admin-label">Email</label>
                                <div class="admin-input" style="background: var(--admin-bg-secondary); border: none;">
                                    <?= htmlspecialchars($currentUser['email']) ?>
                                </div>
                            </div>
                            <div class="admin-form-group">
                                <label class="admin-label">Role</label>
                                <div>
                                    <span class="admin-badge admin-badge-primary">
                                        <i class="fas fa-user"></i>
                                        <?= ucfirst($currentUser['role']) ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div>
                            <div class="admin-form-group">
                                <label class="admin-label">Member Since</label>
                                <div class="admin-input" style="background: var(--admin-bg-secondary); border: none;">
                                    <?= date('F j, Y', strtotime($currentUser['created_at'])) ?>
                                </div>
                            </div>
                            <div class="admin-form-group">
                                <label class="admin-label">Last Updated</label>
                                <div class="admin-input" style="background: var(--admin-bg-secondary); border: none;">
                                    <?= date('F j, Y', strtotime($currentUser['updated_at'])) ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Extended Profile -->
            <?php if ($clientProfile): ?>
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h5 class="admin-card-title">
                            <i class="fas fa-briefcase"></i>
                            Professional Information
                        </h5>
                    </div>
                    <div class="admin-card-body">
                        <div class="admin-grid admin-grid-cols-2">
                            <div>
                                <?php if (!empty($clientProfile['company_name'])): ?>
                                    <div class="admin-form-group">
                                        <label class="admin-label">Company</label>
                                        <div class="admin-input" style="background: var(--admin-bg-secondary); border: none;">
                                            <?= htmlspecialchars($clientProfile['company_name']) ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($clientProfile['position'])): ?>
                                    <div class="admin-form-group">
                                        <label class="admin-label">Position</label>
                                        <div class="admin-input" style="background: var(--admin-bg-secondary); border: none;">
                                            <?= htmlspecialchars($clientProfile['position']) ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($clientProfile['location'])): ?>
                                    <div class="admin-form-group">
                                        <label class="admin-label">Location</label>
                                        <div class="admin-input" style="background: var(--admin-bg-secondary); border: none;">
                                            <?= htmlspecialchars($clientProfile['location']) ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div>
                                <?php if (!empty($clientProfile['website'])): ?>
                                    <div class="admin-form-group">
                                        <label class="admin-label">Website</label>
                                        <div class="admin-input" style="background: var(--admin-bg-secondary); border: none;">
                                            <a href="<?= htmlspecialchars($clientProfile['website']) ?>"
                                               target="_blank"
                                               style="color: var(--admin-primary-light);">
                                                <?= htmlspecialchars($clientProfile['website']) ?>
                                            </a>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                <div class="admin-form-group">
                                    <label class="admin-label">Portfolio Visibility</label>
                                    <div>
                                        <span class="admin-badge <?= $clientProfile['portfolio_visibility'] === 'public' ? 'admin-badge-success' : 'admin-badge-gray' ?>">
                                            <i class="fas fa-<?= $clientProfile['portfolio_visibility'] === 'public' ? 'eye' : 'eye-slash' ?>"></i>
                                            <?= ucfirst($clientProfile['portfolio_visibility']) ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <?php if (!empty($clientProfile['bio'])): ?>
                            <div class="admin-form-group">
                                <label class="admin-label">Bio</label>
                                <div class="admin-input admin-textarea" style="background: var(--admin-bg-secondary); border: none; min-height: auto; padding: 1rem;">
                                    <?= nl2br(htmlspecialchars($clientProfile['bio'])) ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($clientProfile['skills'])): ?>
                            <div class="admin-form-group">
                                <label class="admin-label">Skills</label>
                                <div style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
                                    <?php
                                    $skills = json_decode($clientProfile['skills'], true);
                                    if ($skills && is_array($skills)):
                                        foreach ($skills as $skill): ?>
                                            <span class="admin-badge admin-badge-primary">
                                                <i class="fas fa-code"></i>
                                                <?= htmlspecialchars($skill) ?>
                                            </span>
                                        <?php endforeach;
                                    endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="admin-card">
                    <div class="admin-card-body" style="text-align: center; padding: 3rem;">
                        <i class="fas fa-user-plus" style="font-size: 3rem; color: var(--admin-text-muted); margin-bottom: 1rem;"></i>
                        <h5 style="color: var(--admin-text-primary); margin-bottom: 0.5rem;">Complete Your Profile</h5>
                        <p style="color: var(--admin-text-muted); margin-bottom: 1.5rem;">Add professional information to make your profile complete.</p>
                        <a href="/index.php?page=profile_edit" class="admin-btn admin-btn-primary">
                            <i class="fas fa-plus"></i> Complete Profile
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Sidebar -->
        <div class="admin-sidebar">
            <!-- Profile Actions -->
            <div class="admin-card">
                <div class="admin-card-header">
                    <h6 class="admin-card-title" style="font-size: 0.875rem;">Quick Actions</h6>
                </div>
                <div class="admin-card-body">
                    <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                        <a href="/index.php?page=profile_edit" class="admin-btn admin-btn-primary admin-btn-sm">
                            <i class="fas fa-edit"></i> Edit Profile
                        </a>
                        <a href="/index.php?page=user_portfolio" class="admin-btn admin-btn-success admin-btn-sm">
                            <i class="fas fa-briefcase"></i> View Portfolio
                        </a>
                        <a href="/index.php?page=profile_edit" class="admin-btn admin-btn-secondary admin-btn-sm">
                            <i class="fas fa-cogs"></i> Account Settings
                        </a>
                    </div>
                </div>
            </div>

            <!-- Profile Statistics -->
            <div class="admin-card">
                <div class="admin-card-header">
                    <h6 class="admin-card-title" style="font-size: 0.875rem;">Profile Statistics</h6>
                </div>
                <div class="admin-card-body">
                    <div class="admin-stats-grid" style="grid-template-columns: 1fr 1fr;">
                        <div style="text-align: center;">
                            <div style="font-size: 1.5rem; font-weight: 700; color: var(--admin-primary); margin-bottom: 0.25rem;">
                                <?php
                                // Get portfolio projects count
                                try {
                                    $sql = "SELECT COUNT(*) FROM client_portfolio p 
                                           JOIN client_profiles cp ON p.client_profile_id = cp.id 
                                           WHERE cp.user_id = ?";
                                    $stmt = $database_handler->getConnection()->prepare($sql);
                                    $stmt->execute([$userId]);
                                    echo $stmt->fetchColumn();
                                } catch (Exception $e) {
                                    echo '0';
                                }
                                ?>
                            </div>
                            <small style="color: var(--admin-text-muted);">Projects</small>
                        </div>
                        <div style="text-align: center;">
                            <div style="font-size: 1.5rem; font-weight: 700; color: var(--admin-success); margin-bottom: 0.25rem;">
                                <?= $completion['percentage'] ?>%
                            </div>
                            <small style="color: var(--admin-text-muted);">Complete</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="module" src="/public/assets/js/admin.js"></script>
