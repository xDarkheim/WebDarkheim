<?php
/**
 * Add New Portfolio Project - PHASE 8 - DARK ADMIN THEME
 * Modern project creation interface
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

$pageTitle = 'Add New Project';
$current_user_id = $authService->getCurrentUserId();

// Get client profile
$stmt = $database_handler->getConnection()->prepare("SELECT * FROM client_profiles WHERE user_id = ?");
$stmt->execute([$current_user_id]);
$profileData = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$profileData) {
    $flashMessageService->addError('Please complete your profile first.');
    header('Location: /index.php?page=profile_edit');
    exit();
}

// Get project categories (simplified - using existing categories table)
// Temporarily disable categories functionality until table is created
$categories = []; // Empty categories for now

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
                    <i class="admin-header-icon fas fa-plus"></i>
                    <div class="admin-header-text">
                        <h1>Add New Project</h1>
                        <p>Create a new project for your portfolio</p>
                    </div>
                </div>
                <div class="admin-header-actions">
                    <a href="/index.php?page=user_portfolio" class="admin-btn admin-btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Portfolio
                    </a>
                </div>
            </div>
        </div>
    </header>

<!-- Flash messages handled by global toast system -->

    <form id="projectForm" enctype="multipart/form-data">
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
                                   placeholder="Enter your project title">
                        </div>

                        <div class="admin-form-group">
                            <label for="description" class="admin-label">Description</label>
                            <textarea class="admin-input admin-textarea" id="description" name="description" rows="4"
                                      placeholder="Describe your project, its goals, and key features"></textarea>
                            <div class="admin-help-text">Provide a detailed description of what your project does and its main features</div>
                        </div>

                        <div class="admin-form-group">
                            <label for="technologies" class="admin-label">Technologies Used</label>
                            <div class="tech-input-container">
                                <input type="text" class="admin-input" id="technologies" name="technologies"
                                       placeholder="e.g., React, Node.js, MongoDB, AWS">
                                <div class="tech-suggestions" id="techSuggestions"></div>
                            </div>
                            <div class="admin-help-text">List the main technologies, frameworks, and tools used in this project</div>
                        </div>

                        <div class="admin-grid admin-grid-cols-2">
                            <div class="admin-form-group">
                                <label for="project_url" class="admin-label">Live Project URL</label>
                                <input type="url" class="admin-input" id="project_url" name="project_url"
                                       placeholder="https://your-project.com">
                                <div class="admin-help-text">Link to your live project or demo</div>
                            </div>
                            <div class="admin-form-group">
                                <label for="github_url" class="admin-label">GitHub Repository</label>
                                <input type="url" class="admin-input" id="github_url" name="github_url"
                                       placeholder="https://github.com/username/repo">
                                <div class="admin-help-text">Link to your source code repository</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Project Images -->
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h5 class="admin-card-title">
                            <i class="fas fa-images"></i>
                            Project Images
                        </h5>
                    </div>
                    <div class="admin-card-body">
                        <div class="admin-form-group">
                            <label for="images" class="admin-label">Upload Images</label>
                            <input type="file" class="admin-input" id="images" name="images[]"
                                   multiple accept="image/*">
                            <div class="admin-help-text">
                                Upload screenshots, mockups, or other visual representations of your project.<br>
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
                                <option value="private">Private - Only visible to you</option>
                                <option value="public">Public - Visible to everyone (after approval)</option>
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
                                               style="margin-right: 0.5rem;">
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

                <!-- Tips for Success -->
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h6 class="admin-card-title" style="font-size: 0.875rem;">
                            <i class="fas fa-lightbulb"></i>
                            Tips for Success
                        </h6>
                    </div>
                    <div class="admin-card-body">
                        <div style="font-size: 0.75rem; line-height: 1.4;">
                            <div style="display: flex; align-items: flex-start; margin-bottom: 0.75rem;">
                                <i class="fas fa-check-circle" style="color: var(--admin-success); margin-right: 0.5rem; margin-top: 0.125rem; flex-shrink: 0;"></i>
                                <span style="color: var(--admin-text-muted);">Use clear, descriptive project titles</span>
                            </div>
                            <div style="display: flex; align-items: flex-start; margin-bottom: 0.75rem;">
                                <i class="fas fa-check-circle" style="color: var(--admin-success); margin-right: 0.5rem; margin-top: 0.125rem; flex-shrink: 0;"></i>
                                <span style="color: var(--admin-text-muted);">Add multiple high-quality screenshots</span>
                            </div>
                            <div style="display: flex; align-items: flex-start; margin-bottom: 0.75rem;">
                                <i class="fas fa-check-circle" style="color: var(--admin-success); margin-right: 0.5rem; margin-top: 0.125rem; flex-shrink: 0;"></i>
                                <span style="color: var(--admin-text-muted);">Include live demo and source code links</span>
                            </div>
                            <div style="display: flex; align-items: flex-start;">
                                <i class="fas fa-check-circle" style="color: var(--admin-success); margin-right: 0.5rem; margin-top: 0.125rem; flex-shrink: 0;"></i>
                                <span style="color: var(--admin-text-muted);">Select relevant categories for better discovery</span>
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
                            <button type="submit" class="admin-btn admin-btn-secondary admin-btn-sm" data-action="draft">
                                <i class="fas fa-save"></i> Save as Draft
                            </button>
                            <button type="submit" class="admin-btn admin-btn-success admin-btn-sm" data-action="submit">
                                <i class="fas fa-paper-plane"></i> Save & Submit for Review
                            </button>
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
        'Redis', 'Docker', 'Kubernetes', 'AWS', 'Azure', 'Google Cloud', 'Firebase',
        'Git', 'GitHub', 'GitLab', 'Jenkins', 'Webpack', 'Vite', 'Sass', 'Tailwind CSS',
        'Bootstrap', 'Material-UI', 'Ant Design', 'GraphQL', 'REST API', 'WebSocket'
    ];

    // Handle image preview
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
        const value = this.value;
        const lastComma = value.lastIndexOf(',');
        const currentTech = lastComma >= 0 ? value.substring(lastComma + 1).trim() : value.trim();

        if (currentTech.length >= 2) {
            const matches = popularTech.filter(tech =>
                tech.toLowerCase().includes(currentTech.toLowerCase()) &&
                !value.toLowerCase().includes(tech.toLowerCase())
            ).slice(0, 5);

            if (matches.length > 0) {
                techSuggestions.innerHTML = matches.map(tech =>
                    `<div class="tech-suggestion" data-tech="${tech}">${tech}</div>`
                ).join('');
                techSuggestions.style.display = 'block';
            } else {
                techSuggestions.style.display = 'none';
            }
        } else {
            techSuggestions.style.display = 'none';
        }
    });

    // Handle technology suggestion clicks
    techSuggestions.addEventListener('click', function(e) {
        if (e.target.classList.contains('tech-suggestion')) {
            const tech = e.target.dataset.tech;
            const value = techInput.value;
            const lastComma = value.lastIndexOf(',');

            if (lastComma >= 0) {
                techInput.value = value.substring(0, lastComma + 1) + ' ' + tech + ', ';
            } else {
                techInput.value = tech + ', ';
            }

            techSuggestions.style.display = 'none';
            techInput.focus();
        }
    });

    // Hide suggestions when clicking outside
    document.addEventListener('click', function(e) {
        if (!techInput.contains(e.target) && !techSuggestions.contains(e.target)) {
            techSuggestions.style.display = 'none';
        }
    });

    // Handle form submission
    form.addEventListener('submit', function(e) {
        e.preventDefault();

        const submitButton = e.submitter;
        const action = submitButton.getAttribute('data-action');
        const formData = new FormData(this);

        // Set status based on action
        if (action === 'submit') {
            formData.append('status', 'pending');
        } else {
            formData.append('status', 'draft');
        }

        // Show loading state
        const originalText = submitButton.innerHTML;
        submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
        submitButton.disabled = true;

        fetch('/page/api/portfolio/create_project.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.adminPanel.showFlashMessage('success',
                    action === 'submit' ?
                    'Project created and submitted for review!' :
                    'Project saved as draft!'
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
            window.adminPanel.showFlashMessage('error', 'An error occurred while creating the project.');
            console.error('Error:', error);
            // Restore button state
            submitButton.innerHTML = originalText;
            submitButton.disabled = false;
        });
    });

    // Character counter for description
    const descTextarea = document.getElementById('description');
    descTextarea.addEventListener('input', function() {
        const maxLength = 1000;
        const currentLength = this.value.length;

        let counter = document.getElementById('desc-counter');
        if (!counter) {
            counter = document.createElement('small');
            counter.id = 'desc-counter';
            counter.style.cssText = 'color: var(--admin-text-muted); float: right; margin-top: 0.25rem;';
            this.parentNode.appendChild(counter);
        }

        counter.textContent = `${currentLength} / ${maxLength} characters`;
        counter.style.color = currentLength > maxLength * 0.9 ? 'var(--admin-warning)' : 'var(--admin-text-muted)';
    });
});
</script>