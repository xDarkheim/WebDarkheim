<?php
/**
 * Profile Edit Page - PHASE 8 - DARK ADMIN THEME
 * Modern profile editing interface with client profile integration
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
$pageTitle = 'Edit Profile';

// Create unified navigation
$adminNavigation = new AdminNavigation($authService);

// Get client profile data if exists
$clientProfile = null;
try {
    $sql = "SELECT * FROM client_profiles WHERE user_id = ?";
    $stmt = $database_handler->getConnection()->prepare($sql);
    $stmt->execute([$userId]);
    $clientProfile = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Error fetching client profile: " . $e->getMessage());
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // CSRF token validation
        $token = $_POST['csrf_token'] ?? '';
        if (empty($token) || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
            throw new Exception('Invalid CSRF token.');
        }

        $errors = [];

        // Basic user data
        $email = trim($_POST['email'] ?? '');
        $username = trim($_POST['username'] ?? '');

        // Client profile data
        $company_name = trim($_POST['company_name'] ?? '');
        $position = trim($_POST['position'] ?? '');
        $bio = trim($_POST['bio'] ?? '');
        $location = trim($_POST['location'] ?? '');
        $website = trim($_POST['website'] ?? '');
        $portfolio_visibility = $_POST['portfolio_visibility'] ?? 'private';
        $allow_contact = isset($_POST['allow_contact']) ? 1 : 0;
        $skills = $_POST['skills'] ?? [];

        // Social links
        $social_links = [
            'linkedin' => trim($_POST['linkedin'] ?? ''),
            'github' => trim($_POST['github'] ?? ''),
            'twitter' => trim($_POST['twitter'] ?? ''),
            'website' => trim($_POST['website'] ?? ''),
            'behance' => trim($_POST['behance'] ?? ''),
            'dribbble' => trim($_POST['dribbble'] ?? '')
        ];

        // Remove empty social links
        $social_links = array_filter($social_links);

        // Validation
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Valid email is required.';
        }

        if (empty($username) || strlen($username) < 3) {
            $errors[] = 'Username must be at least 3 characters long.';
        }

        if (!empty($website) && !filter_var($website, FILTER_VALIDATE_URL)) {
            $errors[] = 'Please enter a valid website URL.';
        }

        // Validate social links
        foreach ($social_links as $platform => $url) {
            if (!empty($url) && !filter_var($url, FILTER_VALIDATE_URL)) {
                $errors[] = "Please enter a valid {$platform} URL.";
            }
        }

        if (empty($errors)) {
            try {
                $database_handler->getConnection()->beginTransaction();

                // Update basic user data
                $sql = "UPDATE users SET username = ?, email = ?, updated_at = NOW() WHERE id = ?";
                $stmt = $database_handler->getConnection()->prepare($sql);
                $stmt->execute([$username, $email, $userId]);

                // Update or create client profile
                if ($clientProfile) {
                    // Update existing profile
                    $sql = "UPDATE client_profiles SET 
                            company_name = ?, position = ?, bio = ?, location = ?, 
                            website = ?, portfolio_visibility = ?, allow_contact = ?,
                            skills = ?, social_links = ?, updated_at = NOW()
                            WHERE user_id = ?";
                    $stmt = $database_handler->getConnection()->prepare($sql);
                    $stmt->execute([
                        $company_name, $position, $bio, $location, $website,
                        $portfolio_visibility, $allow_contact,
                        json_encode(array_values(array_filter($skills))),
                        json_encode($social_links),
                        $userId
                    ]);
                } else {
                    // Create new profile
                    $sql = "INSERT INTO client_profiles 
                            (user_id, company_name, position, bio, location, website, 
                             portfolio_visibility, allow_contact, skills, social_links, created_at)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
                    $stmt = $database_handler->getConnection()->prepare($sql);
                    $stmt->execute([
                        $userId, $company_name, $position, $bio, $location, $website,
                        $portfolio_visibility, $allow_contact,
                        json_encode(array_values(array_filter($skills))),
                        json_encode($social_links)
                    ]);
                }

                $database_handler->getConnection()->commit();
                $flashMessageService->addSuccess('Profile updated successfully!');

                // Refresh data
                $currentUser = $authService->getCurrentUser();
                $sql = "SELECT * FROM client_profiles WHERE user_id = ?";
                $stmt = $database_handler->getConnection()->prepare($sql);
                $stmt->execute([$userId]);
                $clientProfile = $stmt->fetch(PDO::FETCH_ASSOC);

            } catch (Exception $e) {
                $database_handler->getConnection()->rollBack();
                error_log("Error updating profile: " . $e->getMessage());
                $flashMessageService->addError('Failed to update profile. Please try again.');
            }
        } else {
            foreach ($errors as $error) {
                $flashMessageService->addError($error);
            }
        }
    } catch (Exception $e) {
        $flashMessageService->addError('Error processing request: ' . $e->getMessage());
    }
}

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Get flash messages
$flashMessages = $flashMessageService->getAllMessages();

// Calculate profile completion using unified helper function
$clientProfile = getClientProfileData($database_handler, $userId);
$completion = calculateProfileCompletion($currentUser, $clientProfile);
?>

    <link rel="stylesheet" href="/public/assets/css/admin.css">

    <!-- Unified Navigation -->
    <?= $adminNavigation->render() ?>

    <!-- Header -->
    <header class="admin-header">
        <div class="admin-header-container">
            <div class="admin-header-content">
                <div class="admin-header-title">
                    <i class="admin-header-icon fas fa-user-edit"></i>
                    <div class="admin-header-text">
                        <h1>Edit Your Profile</h1>
                        <p>Keep your information up to date to get the most out of our platform</p>
                    </div>
                </div>
                <div class="admin-header-actions">
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <div class="progress-circle">
                            <?= $completion['percentage'] ?>%
                        </div>
                        <a href="/index.php?page=user_profile" class="admin-btn admin-btn-secondary">
                            <i class="fas fa-eye"></i> View Profile
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </header>

<!-- Flash messages handled by global toast system -->

    <form method="POST" action="/index.php?page=profile_edit">
        <div class="admin-layout-main">
            <div class="admin-content">
                <!-- Basic Information -->
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h5 class="admin-card-title">
                            <i class="fas fa-user"></i>
                            Basic Information
                        </h5>
                    </div>
                    <div class="admin-card-body">
                        <div class="admin-grid admin-grid-cols-2">
                            <div class="admin-form-group">
                                <label for="username" class="admin-label admin-label-required">Username</label>
                                <input type="text"
                                       class="admin-input"
                                       id="username"
                                       name="username" 
                                       value="<?= htmlspecialchars($currentUser['username']) ?>"
                                       required>
                            </div>
                            <div class="admin-form-group">
                                <label for="email" class="admin-label admin-label-required">Email Address</label>
                                <input type="email"
                                       class="admin-input"
                                       id="email"
                                       name="email" 
                                       value="<?= htmlspecialchars($currentUser['email']) ?>"
                                       required>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Professional Information -->
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h5 class="admin-card-title">
                            <i class="fas fa-briefcase"></i>
                            Professional Information
                        </h5>
                    </div>
                    <div class="admin-card-body">
                        <div class="admin-grid admin-grid-cols-2">
                            <div class="admin-form-group">
                                <label for="company_name" class="admin-label">Company Name</label>
                                <input type="text"
                                       class="admin-input"
                                       id="company_name"
                                       name="company_name" 
                                       value="<?= htmlspecialchars($clientProfile['company_name'] ?? '') ?>"
                                       placeholder="Your company or organization">
                            </div>
                            <div class="admin-form-group">
                                <label for="position" class="admin-label">Position/Title</label>
                                <input type="text"
                                       class="admin-input"
                                       id="position"
                                       name="position" 
                                       value="<?= htmlspecialchars($clientProfile['position'] ?? '') ?>"
                                       placeholder="Your job title or role">
                            </div>
                        </div>

                        <div class="admin-grid admin-grid-cols-2">
                            <div class="admin-form-group">
                                <label for="location" class="admin-label">Location</label>
                                <input type="text"
                                       class="admin-input"
                                       id="location"
                                       name="location" 
                                       value="<?= htmlspecialchars($clientProfile['location'] ?? '') ?>"
                                       placeholder="City, Country">
                            </div>
                            <div class="admin-form-group">
                                <label for="website" class="admin-label">Website</label>
                                <input type="url"
                                       class="admin-input"
                                       id="website"
                                       name="website" 
                                       value="<?= htmlspecialchars($clientProfile['website'] ?? '') ?>"
                                       placeholder="https://yourwebsite.com">
                            </div>
                        </div>

                        <div class="admin-form-group">
                            <label for="bio" class="admin-label">Bio</label>
                            <textarea class="admin-input admin-textarea"
                                      id="bio"
                                      name="bio"
                                      rows="4"
                                      placeholder="Tell us about yourself, your experience, and what you do..."><?= htmlspecialchars($clientProfile['bio'] ?? '') ?></textarea>
                            <div class="admin-help-text">A good bio helps others understand your background and expertise.</div>
                        </div>
                    </div>
                </div>

                <!-- Skills -->
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h5 class="admin-card-title">
                            <i class="fas fa-code"></i>
                            Skills & Technologies
                        </h5>
                    </div>
                    <div class="admin-card-body">
                        <div class="admin-form-group">
                            <label for="skill-input" class="admin-label">Add Skills</label>
                            <div style="display: flex; gap: 0.5rem;">
                                <input type="text"
                                       class="admin-input"
                                       id="skill-input"
                                       placeholder="Type a skill and press Enter"
                                       style="flex: 1;">
                                <button type="button" class="admin-btn admin-btn-primary" id="add-skill-btn">
                                    <i class="fas fa-plus"></i> Add
                                </button>
                            </div>
                            <div class="admin-help-text">Add technologies, programming languages, tools you work with</div>
                        </div>

                        <div id="skills-container" style="margin-top: 1rem;">
                            <?php
                            $skills = [];
                            if (!empty($clientProfile['skills'])) {
                                $skills = json_decode($clientProfile['skills'], true) ?: [];
                            }
                            foreach ($skills as $skill): ?>
                                <span class="skill-tag">
                                    <?= htmlspecialchars($skill) ?>
                                    <span class="remove-skill">&times;</span>
                                    <input type="hidden" name="skills[]" value="<?= htmlspecialchars($skill) ?>">
                                </span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Social Links -->
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h5 class="admin-card-title">
                            <i class="fas fa-share-alt"></i>
                            Social Links
                        </h5>
                    </div>
                    <div class="admin-card-body">
                        <div class="admin-grid admin-grid-cols-2">
                            <?php
                            $socialPlatforms = [
                                'linkedin' => ['icon' => 'fab fa-linkedin', 'placeholder' => 'LinkedIn profile URL'],
                                'github' => ['icon' => 'fab fa-github', 'placeholder' => 'GitHub profile URL'],
                                'twitter' => ['icon' => 'fab fa-twitter', 'placeholder' => 'Twitter profile URL'],
                                'behance' => ['icon' => 'fab fa-behance', 'placeholder' => 'Behance profile URL'],
                                'dribbble' => ['icon' => 'fab fa-dribbble', 'placeholder' => 'Dribbble profile URL']
                            ];

                            $existingSocial = [];
                            if (!empty($clientProfile['social_links'])) {
                                $existingSocial = json_decode($clientProfile['social_links'], true) ?: [];
                            }

                            foreach ($socialPlatforms as $platform => $config): ?>
                                <div class="admin-form-group">
                                    <label for="<?= $platform ?>" class="admin-label"><?= ucfirst($platform) ?></label>
                                    <div class="social-input-group">
                                        <i class="<?= $config['icon'] ?> social-icon"></i>
                                        <input type="url"
                                               class="admin-input social-input"
                                               id="<?= $platform ?>"
                                               name="<?= $platform ?>"
                                               value="<?= htmlspecialchars($existingSocial[$platform] ?? '') ?>"
                                               placeholder="<?= $config['placeholder'] ?>">
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="admin-sidebar">
                <!-- Profile Settings -->
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h5 class="admin-card-title">
                            <i class="fas fa-cogs"></i>
                            Privacy Settings
                        </h5>
                    </div>
                    <div class="admin-card-body">
                        <div class="admin-form-group">
                            <label for="portfolio_visibility" class="admin-label">Portfolio Visibility</label>
                            <select class="admin-input admin-select" id="portfolio_visibility" name="portfolio_visibility">
                                <option value="private" <?= ($clientProfile['portfolio_visibility'] ?? 'private') === 'private' ? 'selected' : '' ?>>
                                    Private - Only I can see
                                </option>
                                <option value="public" <?= ($clientProfile['portfolio_visibility'] ?? 'private') === 'public' ? 'selected' : '' ?>>
                                    Public - Visible to everyone
                                </option>
                            </select>
                            <div class="admin-help-text">Control who can see your portfolio projects</div>
                        </div>

                        <div class="admin-form-group">
                            <div style="display: flex; align-items: flex-start; gap: 0.5rem;">
                                <input type="checkbox"
                                       id="allow_contact"
                                       name="allow_contact"
                                       style="margin-top: 0.25rem;"
                                       <?= !empty($clientProfile['allow_contact']) ? 'checked' : '' ?>>
                                <label for="allow_contact" style="margin: 0; color: var(--admin-text-primary);">
                                    Allow others to contact me
                                    <div class="admin-help-text" style="margin-top: 0.25rem;">Allow other users to send you messages</div>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Profile Completion -->
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h5 class="admin-card-title">
                            <i class="fas fa-chart-pie"></i>
                            Profile Completion
                        </h5>
                    </div>
                    <div class="admin-card-body">
                        <div class="admin-form-group">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                                <span style="color: var(--admin-text-primary);">Progress</span>
                                <span class="admin-badge admin-badge-primary"><?= $completion['percentage'] ?>%</span>
                            </div>
                            <div style="background: var(--admin-bg-secondary); border-radius: 9999px; height: 8px; overflow: hidden;">
                                <div style="background: linear-gradient(135deg, var(--admin-primary) 0%, var(--admin-primary-light) 100%);
                                           height: 100%; width: <?= $completion['percentage'] ?>%; transition: width 0.3s ease;"></div>
                            </div>
                            <small style="color: var(--admin-text-muted); display: block; margin-top: 0.5rem;">
                                <?= $completion['completed'] ?> of <?= $completion['total'] ?> fields completed
                            </small>
                        </div>

                        <?php if (!empty($completion['missing'])): ?>
                            <div style="background: var(--admin-info-bg); border: 1px solid var(--admin-info);
                                        border-radius: var(--admin-border-radius); padding: 0.75rem; margin-top: 1rem;">
                                <small style="color: var(--admin-info-light);">
                                    <strong>To complete your profile, add:</strong><br>
                                    <?php foreach (array_slice($completion['missing'], 0, 3) as $field): ?>
                                        • <?= ucfirst(str_replace('_', ' ', $field)) ?><br>
                                    <?php endforeach; ?>
                                </small>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h5 class="admin-card-title">
                            <i class="fas fa-bolt"></i>
                            Quick Actions
                        </h5>
                    </div>
                    <div class="admin-card-body">
                        <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                            <a href="/index.php?page=user_profile" class="admin-btn admin-btn-secondary admin-btn-sm">
                                <i class="fas fa-eye"></i> View Public Profile
                            </a>
                            <a href="/index.php?page=user_portfolio" class="admin-btn admin-btn-success admin-btn-sm">
                                <i class="fas fa-briefcase"></i> Manage Portfolio
                            </a>
                            <a href="/index.php?page=user_profile_settings" class="admin-btn admin-btn-secondary admin-btn-sm">
                                <i class="fas fa-shield-alt"></i> Security Settings
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Submit Section -->
        <div style="max-width: 1280px; margin: 0 auto; padding: 0 1rem 2rem 1rem;">
            <div class="admin-card">
                <div class="admin-card-body">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <h6 style="margin: 0 0 0.25rem 0; color: var(--admin-text-primary);">Ready to save your changes?</h6>
                            <small style="color: var(--admin-text-muted);">Make sure all information is accurate before saving</small>
                        </div>
                        <div style="display: flex; gap: 0.75rem;">
                            <a href="/index.php?page=user_profile" class="admin-btn admin-btn-secondary">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                            <button type="submit" class="admin-btn admin-btn-primary">
                                <i class="fas fa-save"></i> Save Changes
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
    </form>
</div>

<script type="module" src="/public/assets/js/admin.js"></script>
<script>
// Skills management
document.addEventListener('DOMContentLoaded', function() {
    const skillInput = document.getElementById('skill-input');
    const addSkillBtn = document.getElementById('add-skill-btn');
    const skillsContainer = document.getElementById('skills-container');

    function addSkill(skillText) {
        if (!skillText.trim()) return;

        // Check if skill already exists
        const existingSkills = Array.from(skillsContainer.querySelectorAll('input[name="skills[]"]'))
            .map(input => input.value.toLowerCase());

        if (existingSkills.includes(skillText.toLowerCase())) {
            return;
        }

        const skillTag = document.createElement('span');
        skillTag.className = 'skill-tag';
        skillTag.innerHTML = `
            ${skillText}
            <span class="remove-skill">&times;</span>
            <input type="hidden" name="skills[]" value="${skillText}">
        `;

        skillTag.querySelector('.remove-skill').addEventListener('click', function() {
            skillTag.remove();
        });

        skillsContainer.appendChild(skillTag);
        skillInput.value = '';
    }

    addSkillBtn.addEventListener('click', function() {
        addSkill(skillInput.value);
    });

    skillInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            addSkill(this.value);
        }
    });

    // Handle existing skill removal
    document.querySelectorAll('.remove-skill').forEach(function(btn) {
        btn.addEventListener('click', function() {
            this.parentElement.remove();
        });
    });

    // Character counter for bio
    const bioTextarea = document.getElementById('bio');
    bioTextarea.addEventListener('input', function() {
        const maxLength = 500;
        const currentLength = this.value.length;

        let counter = document.getElementById('bio-counter');
        if (!counter) {
            counter = document.createElement('small');
            counter.id = 'bio-counter';
            counter.style.cssText = 'color: var(--admin-text-muted); float: right; margin-top: 0.25rem;';
            this.parentNode.appendChild(counter);
        }

        counter.textContent = `${currentLength} / ${maxLength} characters`;
        counter.style.color = currentLength > maxLength * 0.9 ? 'var(--admin-warning)' : 'var(--admin-text-muted)';
    });
});
</script>
