<?php
/**
 * Profile Edit Page - PHASE 8
 * Modern profile editing interface with client profile integration
 */

declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/includes/bootstrap.php';

global $serviceProvider, $flashMessageService, $database_handler;

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
}

// Get flash messages
$flashMessages = $flashMessageService->getAllMessages();

// Calculate profile completion
function calculateProfileCompletion($user, $profile): array {
    $fields = [
        'email' => !empty($user['email']),
        'company' => !empty($profile['company_name'] ?? ''),
        'position' => !empty($profile['position'] ?? ''),
        'bio' => !empty($profile['bio'] ?? ''),
        'location' => !empty($profile['location'] ?? ''),
        'website' => !empty($profile['website'] ?? ''),
        'skills' => !empty($profile['skills'] ?? ''),
    ];

    $completed = count(array_filter($fields));
    $total = count($fields);
    $percentage = round(($completed / $total) * 100);

    return [
        'percentage' => $percentage,
        'completed' => $completed,
        'total' => $total,
        'missing' => array_keys(array_filter($fields, fn($v) => !$v))
    ];
}

$completion = calculateProfileCompletion($currentUser, $clientProfile ?: []);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .profile-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
        }
        .form-section {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border: none;
        }
        .form-section h5 {
            color: #495057;
            border-bottom: 2px solid #e9ecef;
            padding-bottom: 0.5rem;
            margin-bottom: 1.5rem;
        }
        .skill-tag {
            display: inline-block;
            background: #e3f2fd;
            color: #1976d2;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            margin: 0.25rem;
            font-size: 0.875rem;
        }
        .skill-tag .remove-skill {
            margin-left: 0.5rem;
            cursor: pointer;
            color: #f44336;
        }
        .social-input-group {
            position: relative;
        }
        .social-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
            z-index: 10;
        }
        .social-input {
            padding-left: 40px;
        }
        .progress-circle {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: white;
        }
        .btn-gradient {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
        }
        .btn-gradient:hover {
            background: linear-gradient(135deg, #5a6fd8 0%, #6a4190 100%);
            color: white;
        }
    </style>
</head>
<body class="bg-light">

<div class="container py-4">
    <!-- Header Section -->
    <div class="profile-header">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1 class="mb-2">
                    <i class="fas fa-user-edit"></i>
                    Edit Your Profile
                </h1>
                <p class="mb-0 opacity-75">Keep your information up to date to get the most out of our platform</p>
            </div>
            <div class="col-md-4 text-md-end">
                <div class="d-flex align-items-center justify-content-md-end gap-3">
                    <div>
                        <div class="progress-circle" style="background: rgba(255,255,255,0.2);">
                            <?= $completion['percentage'] ?>%
                        </div>
                        <small class="d-block text-center mt-1">Complete</small>
                    </div>
                    <div>
                        <a href="/index.php?page=user_profile" class="btn btn-light btn-sm">
                            <i class="fas fa-eye"></i> View Profile
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Flash Messages -->
    <?php if (!empty($flashMessages)): ?>
        <div class="row mb-4">
            <div class="col-12">
                <?php foreach ($flashMessages as $type => $messages): ?>
                    <?php foreach ($messages as $message): ?>
                        <div class="alert alert-<?= $type === 'error' ? 'danger' : $type ?> alert-dismissible fade show">
                            <?= htmlspecialchars($message['text']) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <form method="POST" action="/index.php?page=profile_edit">
        <div class="row">
            <!-- Left Column -->
            <div class="col-lg-8">
                <!-- Basic Information -->
                <div class="form-section">
                    <h5>
                        <i class="fas fa-user text-primary"></i>
                        Basic Information
                    </h5>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="username" class="form-label">Username <span class="text-danger">*</span></label>
                                <input type="text" 
                                       class="form-control" 
                                       id="username" 
                                       name="username" 
                                       value="<?= htmlspecialchars($currentUser['username']) ?>"
                                       required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                                <input type="email" 
                                       class="form-control" 
                                       id="email" 
                                       name="email" 
                                       value="<?= htmlspecialchars($currentUser['email']) ?>"
                                       required>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Professional Information -->
                <div class="form-section">
                    <h5>
                        <i class="fas fa-briefcase text-primary"></i>
                        Professional Information
                    </h5>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="company_name" class="form-label">Company Name</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="company_name" 
                                       name="company_name" 
                                       value="<?= htmlspecialchars($clientProfile['company_name'] ?? '') ?>"
                                       placeholder="Your company or organization">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="position" class="form-label">Position/Title</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="position" 
                                       name="position" 
                                       value="<?= htmlspecialchars($clientProfile['position'] ?? '') ?>"
                                       placeholder="Your job title or role">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="location" class="form-label">Location</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="location" 
                                       name="location" 
                                       value="<?= htmlspecialchars($clientProfile['location'] ?? '') ?>"
                                       placeholder="City, Country">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="website" class="form-label">Website</label>
                                <input type="url" 
                                       class="form-control" 
                                       id="website" 
                                       name="website" 
                                       value="<?= htmlspecialchars($clientProfile['website'] ?? '') ?>"
                                       placeholder="https://yourwebsite.com">
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="bio" class="form-label">Bio</label>
                        <textarea class="form-control" 
                                  id="bio" 
                                  name="bio" 
                                  rows="4" 
                                  placeholder="Tell us about yourself, your experience, and what you do..."><?= htmlspecialchars($clientProfile['bio'] ?? '') ?></textarea>
                        <div class="form-text">A good bio helps others understand your background and expertise.</div>
                    </div>
                </div>

                <!-- Skills -->
                <div class="form-section">
                    <h5>
                        <i class="fas fa-code text-primary"></i>
                        Skills & Technologies
                    </h5>
                    <div class="mb-3">
                        <label for="skill-input" class="form-label">Add Skills</label>
                        <div class="input-group">
                            <input type="text" 
                                   class="form-control" 
                                   id="skill-input" 
                                   placeholder="Type a skill and press Enter">
                            <button type="button" class="btn btn-outline-primary" id="add-skill-btn">
                                <i class="fas fa-plus"></i> Add
                            </button>
                        </div>
                        <div class="form-text">Add technologies, programming languages, tools you work with</div>
                    </div>
                    
                    <div id="skills-container" class="mb-3">
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

                <!-- Social Links -->
                <div class="form-section">
                    <h5>
                        <i class="fas fa-share-alt text-primary"></i>
                        Social Links
                    </h5>
                    <div class="row">
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
                            <div class="col-md-6 mb-3">
                                <label for="<?= $platform ?>" class="form-label"><?= ucfirst($platform) ?></label>
                                <div class="social-input-group">
                                    <i class="<?= $config['icon'] ?> social-icon"></i>
                                    <input type="url" 
                                           class="form-control social-input" 
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

            <!-- Right Column -->
            <div class="col-lg-4">
                <!-- Profile Settings -->
                <div class="form-section">
                    <h5>
                        <i class="fas fa-cogs text-primary"></i>
                        Privacy Settings
                    </h5>
                    
                    <div class="mb-3">
                        <label for="portfolio_visibility" class="form-label">Portfolio Visibility</label>
                        <select class="form-select" id="portfolio_visibility" name="portfolio_visibility">
                            <option value="private" <?= ($clientProfile['portfolio_visibility'] ?? 'private') === 'private' ? 'selected' : '' ?>>
                                Private - Only I can see
                            </option>
                            <option value="public" <?= ($clientProfile['portfolio_visibility'] ?? 'private') === 'public' ? 'selected' : '' ?>>
                                Public - Visible to everyone
                            </option>
                        </select>
                        <div class="form-text">Control who can see your portfolio projects</div>
                    </div>

                    <div class="form-check">
                        <input class="form-check-input" 
                               type="checkbox" 
                               id="allow_contact" 
                               name="allow_contact" 
                               <?= !empty($clientProfile['allow_contact']) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="allow_contact">
                            Allow others to contact me
                        </label>
                        <div class="form-text">Allow other users to send you messages</div>
                    </div>
                </div>

                <!-- Profile Completion -->
                <div class="form-section">
                    <h5>
                        <i class="fas fa-chart-pie text-primary"></i>
                        Profile Completion
                    </h5>
                    
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span>Progress</span>
                            <span class="badge bg-primary"><?= $completion['percentage'] ?>%</span>
                        </div>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar bg-gradient" 
                                 role="progressbar" 
                                 style="width: <?= $completion['percentage'] ?>%"></div>
                        </div>
                        <small class="text-muted"><?= $completion['completed'] ?> of <?= $completion['total'] ?> fields completed</small>
                    </div>

                    <?php if (!empty($completion['missing'])): ?>
                        <div class="alert alert-info p-2">
                            <small>
                                <strong>To complete your profile, add:</strong><br>
                                <?php foreach (array_slice($completion['missing'], 0, 3) as $field): ?>
                                    • <?= ucfirst(str_replace('_', ' ', $field)) ?><br>
                                <?php endforeach; ?>
                            </small>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Quick Actions -->
                <div class="form-section">
                    <h5>
                        <i class="fas fa-bolt text-primary"></i>
                        Quick Actions
                    </h5>
                    <div class="d-grid gap-2">
                        <a href="/index.php?page=user_profile" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-eye"></i> View Public Profile
                        </a>
                        <a href="/index.php?page=user_portfolio" class="btn btn-outline-success btn-sm">
                            <i class="fas fa-briefcase"></i> Manage Portfolio
                        </a>
                        <a href="/index.php?page=user_profile_settings" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-shield-alt"></i> Security Settings
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Submit Section -->
        <div class="form-section">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="mb-1">Ready to save your changes?</h6>
                    <small class="text-muted">Make sure all information is accurate before saving</small>
                </div>
                <div class="d-flex gap-2">
                    <a href="/index.php?page=user_profile" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                    <button type="submit" class="btn btn-gradient">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
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

    // Auto-dismiss alerts
    setTimeout(function() {
        var alerts = document.querySelectorAll('.alert');
        alerts.forEach(function(alert) {
            var bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);

    // Character counter for bio
    const bioTextarea = document.getElementById('bio');
    bioTextarea.addEventListener('input', function() {
        const maxLength = 500;
        const currentLength = this.value.length;
        const remaining = maxLength - currentLength;

        let counter = document.getElementById('bio-counter');
        if (!counter) {
            counter = document.createElement('small');
            counter.id = 'bio-counter';
            counter.className = 'text-muted float-end';
            this.parentNode.appendChild(counter);
        }

        counter.textContent = `${currentLength} / ${maxLength} characters`;
        counter.className = remaining < 50 ? 'text-warning float-end' : 'text-muted float-end';
    });
});
</script>

</body>
</html>
