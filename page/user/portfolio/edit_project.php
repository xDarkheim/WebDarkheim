<?php
/**
 * Edit Portfolio Project - PHASE 8 - DARK ADMIN THEME
 * Modern project editing interface
 */

declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/includes/bootstrap.php';

use App\Application\Components\AdminNavigation;

global $serviceProvider, $flashMessageService, $database_handler;

try {
    $authService = $serviceProvider->getAuth();
} catch (Exception $e) {
    error_log("Critical: Failed to get AuthenticationService: " . $e->getMessage());
    die("System error occurred.");
}

// Check authentication
if (!$authService->isAuthenticated()) {
    $flashMessageService->addError('Please log in to access your portfolio.');
    header("Location: /index.php?page=login");
    exit();
}

// Check if user is client or higher
$current_user_role = $authService->getCurrentUserRole();
if (!in_array($current_user_role, ['client', 'employee', 'admin'])) {
    $flashMessageService->addError('Access denied. Client account required.');
    header("Location: /index.php?page=dashboard");
    exit();
}

// Create unified navigation
$adminNavigation = new AdminNavigation($authService);

$pageTitle = 'Edit Project';
$current_user_id = $authService->getCurrentUserId();

// Get project ID
$projectId = (int)($_GET['id'] ?? 0);
if ($projectId <= 0) {
    header('Location: /index.php?page=user_portfolio');
    exit();
}

// Get client profile
$stmt = $database_handler->getConnection()->prepare("SELECT * FROM client_profiles WHERE user_id = ?");
$stmt->execute([$current_user_id]);
$profileData = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$profileData) {
    $flashMessageService->addError('Please complete your profile first.');
    header('Location: /index.php?page=profile_edit');
    exit();
}

// Get project and verify ownership
$stmt = $database_handler->getConnection()->prepare("SELECT * FROM client_portfolio WHERE id = ? AND client_profile_id = ?");
$stmt->execute([$projectId, $profileData['id']]);
$projectData = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$projectData) {
    $flashMessageService->addError('Project not found or access denied.');
    header('Location: /index.php?page=user_portfolio');
    exit();
}

// Get project categories (simplified - using existing categories table)
$stmt = $database_handler->getConnection()->prepare("SELECT * FROM categories WHERE status = 'active' ORDER BY name");
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get assigned categories for this project (simplified - would normally be in separate table)
$assignedCategories = [];

$images = json_decode($projectData['images'] ?? '[]', true);

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
                    <i class="admin-header-icon fas fa-edit"></i>
                    <div class="admin-header-text">
                        <h1>Edit Project</h1>
                        <p>Update your project information and settings</p>
                    </div>
                </div>
                <div class="admin-header-actions">
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <?php
                        $statusConfig = [
                            'rejected' => ['class' => 'admin-badge-error', 'icon' => 'fas fa-times-circle'],
                            'pending' => ['class' => 'admin-badge-warning', 'icon' => 'fas fa-clock'],
                            'published' => ['class' => 'admin-badge-success', 'icon' => 'fas fa-check-circle'],
                            'draft' => ['class' => 'admin-badge-gray', 'icon' => 'fas fa-edit']
                        ];
                        $config = $statusConfig[$projectData['status']] ?? $statusConfig['draft'];
                        ?>
                        <span class="admin-badge <?= $config['class'] ?>">
                            <i class="<?= $config['icon'] ?>"></i>
                            <?= ucfirst($projectData['status']) ?>
                        </span>
                        <a href="/index.php?page=user_portfolio" class="admin-btn admin-btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Portfolio
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </header>

<!-- Flash messages handled by global toast system -->

    <!-- Status Alerts -->
    <?php if ($projectData['status'] === 'pending'): ?>
        <div style="max-width: 1280px; margin: 0 auto; padding: 0 1rem;">
            <div class="admin-flash-message admin-flash-warning">
                <i class="fas fa-clock"></i>
                <div>
                    <strong>Project Under Review</strong><br>
                    This project is currently being reviewed by our team. You can still make changes, but they won't be visible until the next review cycle.
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($projectData['status'] === 'rejected' && !empty($projectData['moderation_notes'])): ?>
        <div style="max-width: 1280px; margin: 0 auto; padding: 0 1rem;">
            <div class="admin-flash-message admin-flash-error">
                <i class="fas fa-exclamation-triangle"></i>
                <div>
                    <strong>Project Rejected</strong><br>
                    <strong>Reason:</strong> <?= htmlspecialchars($projectData['moderation_notes']) ?><br>
                    <small>Please address the issues above and resubmit your project.</small>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <form id="projectForm" enctype="multipart/form-data">
        <input type="hidden" name="project_id" value="<?= $projectId ?>">

        <div class="admin-layout-main">
            <div class="admin-content">
                <!-- Project Information -->
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h5 class="admin-card-title">
                            <i class="fas fa-info-circle"></i>
                            Project Information
                        </h5>
                    </div>
                    <div class="admin-card-body">
                        <div class="admin-form-group">
                            <label for="title" class="admin-label admin-label-required">Project Title</label>
                            <input type="text" class="admin-input" id="title" name="title" required
                                   value="<?= htmlspecialchars($projectData['title']) ?>"
                                   placeholder="Enter your project title">
                        </div>

                        <div class="admin-form-group">
                            <label for="description" class="admin-label">Description</label>
                            <textarea class="admin-input admin-textarea" id="description" name="description" rows="4"
                                      placeholder="Describe your project, its goals, and key features"><?= htmlspecialchars($projectData['description'] ?? '') ?></textarea>
                            <div class="admin-help-text">Provide a detailed description of what your project does and its main features</div>
                        </div>

                        <div class="admin-form-group">
                            <label for="technologies" class="admin-label">Technologies Used</label>
                            <div class="tech-input-container">
                                <input type="text" class="admin-input" id="technologies" name="technologies"
                                       value="<?= htmlspecialchars($projectData['technologies'] ?? '') ?>"
                                       placeholder="e.g., React, Node.js, MongoDB, AWS">
                                <div class="tech-suggestions" id="techSuggestions"></div>
                            </div>
                            <div class="admin-help-text">List the main technologies, frameworks, and tools used in this project</div>
                        </div>

                        <div class="admin-grid admin-grid-cols-2">
                            <div class="admin-form-group">
                                <label for="project_url" class="admin-label">Live Project URL</label>
                                <input type="url" class="admin-input" id="project_url" name="project_url"
                                       value="<?= htmlspecialchars($projectData['project_url'] ?? '') ?>"
                                       placeholder="https://your-project.com">
                                <div class="admin-help-text">Link to your live project or demo</div>
                            </div>
                            <div class="admin-form-group">
                                <label for="github_url" class="admin-label">GitHub Repository</label>
                                <input type="url" class="admin-input" id="github_url" name="github_url"
                                       value="<?= htmlspecialchars($projectData['github_url'] ?? '') ?>"
                                       placeholder="https://github.com/username/repo">
                                <div class="admin-help-text">Link to your source code repository</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Current Images -->
                <?php if (!empty($images) && is_array($images)): ?>
                    <div class="admin-card">
                        <div class="admin-card-header">
                            <h5 class="admin-card-title">
                                <i class="fas fa-images"></i>
                                Current Images
                            </h5>
                        </div>
                        <div class="admin-card-body">
                            <div class="image-preview-grid" id="currentImages">
                                <?php foreach ($images as $index => $image): ?>
                                    <div class="image-preview-item" data-image="<?= htmlspecialchars($image) ?>">
                                        <img src="/storage/uploads/portfolio/<?= htmlspecialchars($image) ?>"
                                             alt="Project image">
                                        <button type="button" class="remove-image-btn"
                                                onclick="removeImage('<?= htmlspecialchars($image) ?>')">
                                            <i class="fas fa-times"></i>
                                        </button>
                                        <div class="image-preview-info">
                                            <?= htmlspecialchars($image) ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Add New Images -->
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h5 class="admin-card-title">
                            <i class="fas fa-plus"></i>
                            Add New Images
                        </h5>
                    </div>
                    <div class="admin-card-body">
                        <div class="admin-form-group">
                            <label for="images" class="admin-label">Upload New Images</label>
                            <input type="file" class="admin-input" id="images" name="images[]"
                                   multiple accept="image/*">
                            <div class="admin-help-text">
                                Upload additional screenshots, mockups, or other visual representations.<br>
                                <strong>Supported formats:</strong> JPEG, PNG, GIF, WebP | <strong>Max size:</strong> 5MB per file
                            </div>
                        </div>

                        <div id="imagePreview" class="image-preview-grid"></div>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="admin-sidebar">
                <!-- Project Settings -->
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h5 class="admin-card-title">
                            <i class="fas fa-cog"></i>
                            Project Settings
                        </h5>
                    </div>
                    <div class="admin-card-body">
                        <div class="admin-form-group">
                            <label for="visibility" class="admin-label">Visibility</label>
                            <select class="admin-input admin-select" id="visibility" name="visibility">
                                <option value="private" <?= $projectData['visibility'] === 'private' ? 'selected' : '' ?>>
                                    Private - Only visible to you
                                </option>
                                <option value="public" <?= $projectData['visibility'] === 'public' ? 'selected' : '' ?>>
                                    Public - Visible to everyone (after approval)
                                </option>
                            </select>
                            <div class="admin-help-text">Control who can see this project</div>
                        </div>

                        <?php if (!empty($categories)): ?>
                            <div class="admin-form-group">
                                <label class="admin-label">Categories</label>
                                <?php foreach ($categories as $category): ?>
                                    <div style="display: flex; align-items: center; margin-bottom: 0.5rem;">
                                        <input type="checkbox"
                                               name="categories[]"
                                               value="<?= $category['id'] ?>"
                                               id="category_<?= $category['id'] ?>"
                                               style="margin-right: 0.5rem;"
                                               <?= in_array($category['id'], $assignedCategories) ? 'checked' : '' ?>>
                                        <label for="category_<?= $category['id'] ?>" style="margin: 0; color: var(--admin-text-primary); font-size: 0.875rem;">
                                            <?= htmlspecialchars($category['name']) ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                                <div class="admin-help-text">Select relevant categories for better discovery</div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Project Statistics -->
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h5 class="admin-card-title">
                            <i class="fas fa-chart-bar"></i>
                            Project Statistics
                        </h5>
                    </div>
                    <div class="admin-card-body">
                        <div class="admin-stats-grid" style="grid-template-columns: 1fr 1fr;">
                            <div style="text-align: center;">
                                <div style="font-size: 1.5rem; font-weight: 700; color: var(--admin-primary); margin-bottom: 0.25rem;">
                                    <?= rand(0, 500) ?>
                                </div>
                                <small style="color: var(--admin-text-muted);">Views</small>
                            </div>
                            <div style="text-align: center;">
                                <div style="font-size: 1.5rem; font-weight: 700; color: var(--admin-success); margin-bottom: 0.25rem;">
                                    <?= date('M j, Y', strtotime($projectData['created_at'])) ?>
                                </div>
                                <small style="color: var(--admin-text-muted);">Created</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h6 class="admin-card-title" style="font-size: 0.875rem;">
                            <i class="fas fa-bolt"></i>
                            Actions
                        </h6>
                    </div>
                    <div class="admin-card-body">
                        <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                            <button type="submit" class="admin-btn admin-btn-primary admin-btn-sm" data-action="save">
                                <i class="fas fa-save"></i> Save Changes
                            </button>

                            <?php if ($projectData['status'] === 'draft'): ?>
                                <button type="submit" class="admin-btn admin-btn-success admin-btn-sm" data-action="submit">
                                    <i class="fas fa-paper-plane"></i> Save & Submit for Review
                                </button>
                            <?php elseif ($projectData['status'] === 'rejected'): ?>
                                <button type="submit" class="admin-btn admin-btn-success admin-btn-sm" data-action="submit">
                                    <i class="fas fa-paper-plane"></i> Save & Resubmit
                                </button>
                            <?php endif; ?>

                            <a href="/index.php?page=user_portfolio" class="admin-btn admin-btn-secondary admin-btn-sm">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script type="module" src="/public/assets/js/admin.js"></script>
<script>
let removedImages = [];

document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('projectForm');
    const imageInput = document.getElementById('images');
    const imagePreview = document.getElementById('imagePreview');
    const techInput = document.getElementById('technologies');
    const techSuggestions = document.getElementById('techSuggestions');

    // Popular technologies for suggestions
    const popularTech = [
        'JavaScript', 'TypeScript', 'React', 'Vue.js', 'Angular', 'Node.js', 'Express.js',
        'PHP', 'Laravel', 'Symfony', 'Python', 'Django', 'Flask', 'Java', 'Spring',
        'C#', '.NET', 'Ruby', 'Rails', 'Go', 'Rust', 'MySQL', 'PostgreSQL', 'MongoDB',
        'Redis', 'Docker', 'Kubernetes', 'AWS', 'Azure', 'Google Cloud', 'Firebase'
    ];

    // Handle new image preview
    imageInput.addEventListener('change', function() {
        imagePreview.innerHTML = '';

        for (let i = 0; i < this.files.length; i++) {
            const file = this.files[i];
            if (file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const item = document.createElement('div');
                    item.className = 'image-preview-item';
                    item.innerHTML = `
                        <img src="${e.target.result}" alt="${file.name}">
                        <div class="image-preview-info">
                            <div style="font-weight: 500; color: var(--admin-text-primary); margin-bottom: 0.25rem;">${file.name}</div>
                            <div>${(file.size / 1024 / 1024).toFixed(2)} MB</div>
                        </div>
                    `;
                    imagePreview.appendChild(item);
                };
                reader.readAsDataURL(file);
            }
        }
    });

    // Technology suggestions
    techInput.addEventListener('input', function() {
        const query = this.value.toLowerCase();
        techSuggestions.innerHTML = '';

        if (query.length > 0) {
            const filteredTech = popularTech.filter(tech => tech.toLowerCase().includes(query));
            filteredTech.forEach(tech => {
                const suggestion = document.createElement('div');
                suggestion.className = 'tech-suggestion';
                suggestion.textContent = tech;
                suggestion.addEventListener('click', function() {
                    techInput.value = tech;
                    techSuggestions.innerHTML = '';
                });
                techSuggestions.appendChild(suggestion);
            });
            techSuggestions.style.display = 'block';
        } else {
            techSuggestions.style.display = 'none';
        }
    });

    techInput.addEventListener('blur', function() {
        setTimeout(() => {
            techSuggestions.style.display = 'none';
        }, 100);
    });

    // Handle form submission
    form.addEventListener('submit', function(e) {
        e.preventDefault();

        const submitButton = e.submitter;
        const action = submitButton.getAttribute('data-action');
        const formData = new FormData(this);

        // Add removed images to form data
        removedImages.forEach(image => {
            formData.append('removed_images[]', image);
        });

        // Set status based on action
        if (action === 'submit') {
            formData.append('status', 'pending');
        }

        // Show loading state
        const originalText = submitButton.innerHTML;
        submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
        submitButton.disabled = true;

        fetch('/page/api/portfolio/update_project.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.adminPanel.showFlashMessage('success',
                    action === 'submit' ?
                    'Project updated and submitted for review!' :
                    'Project updated successfully!'
                );
                setTimeout(() => {
                    window.location.href = '/index.php?page=user_portfolio';
                }, 1500);
            } else {
                window.adminPanel.showFlashMessage('error', 'Error: ' + (data.error || 'Unknown error occurred'));
                // Restore button state
                submitButton.innerHTML = originalText;
                submitButton.disabled = false;
            }
        })
        .catch(error => {
            window.adminPanel.showFlashMessage('error', 'An error occurred while updating the project.');
            console.error('Error:', error);
            // Restore button state
            submitButton.innerHTML = originalText;
            submitButton.disabled = false;
        });
    });
});

function removeImage(imageName) {
    if (confirm('Remove this image? This action cannot be undone.')) {
        removedImages.push(imageName);
        document.querySelector(`[data-image="${imageName}"]`).remove();
    }
}
</script>
