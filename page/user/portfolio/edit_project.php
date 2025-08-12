<?php
/**
 * Edit Portfolio Project
 */

require_once __DIR__ . '/../../../includes/bootstrap.php';

// Use global services from the architecture
global $database_handler, $flashMessageService, $container;

use App\Application\Core\ServiceProvider;

// Get ServiceProvider instance
$serviceProvider = ServiceProvider::getInstance($container);
$authService = $serviceProvider->getAuth();

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

$pageTitle = 'Edit Project';
$current_user_id = $authService->getCurrentUserId();

// Get project ID
$projectId = (int)($_GET['id'] ?? 0);
if ($projectId <= 0) {
    header('Location: /index.php?page=portfolio_manage');
    exit();
}

// Get client profile
$stmt = $database_handler->prepare("SELECT * FROM client_profiles WHERE user_id = ?");
$stmt->execute([$current_user_id]);
$profileData = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$profileData) {
    $flashMessageService->addError('Please complete your profile first.');
    header('Location: /index.php?page=profile_edit');
    exit();
}

// Get project and verify ownership
$stmt = $database_handler->prepare("SELECT * FROM client_portfolio WHERE id = ? AND client_profile_id = ?");
$stmt->execute([$projectId, $profileData['id']]);
$projectData = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$projectData) {
    $flashMessageService->addError('Project not found or access denied.');
    header('Location: /index.php?page=portfolio_manage');
    exit();
}

// Get project categories (simplified - using existing categories table)
$stmt = $database_handler->prepare("SELECT * FROM categories WHERE status = 'active' ORDER BY name");
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get assigned categories for this project (simplified - would normally be in separate table)
$assignedCategories = [];

$images = json_decode($projectData['images'] ?? '[]', true);
?>

<div class="container mt-4">
    <!-- Breadcrumbs -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/index.php?page=dashboard">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="/index.php?page=client_portfolio">Portfolio</a></li>
            <li class="breadcrumb-item"><a href="/index.php?page=portfolio_manage">My Projects</a></li>
            <li class="breadcrumb-item active">Edit Project</li>
        </ol>
    </nav>

    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                <i class="fas fa-edit text-primary me-2"></i>
                Edit Project
            </h1>
            <a href="/index.php?page=portfolio_manage" class="btn btn-outline-secondary me-2">
                <i class="fas fa-arrow-left me-1"></i>
                Back to Projects
            </a>
        </div>
        <div>
            <?php if ($projectData['status'] === 'rejected'): ?>
                <span class="badge bg-danger">Rejected</span>
            <?php elseif ($projectData['status'] === 'pending'): ?>
                <span class="badge bg-warning">Under Review</span>
            <?php elseif ($projectData['status'] === 'published'): ?>
                <span class="badge bg-success">Published</span>
            <?php else: ?>
                <span class="badge bg-secondary">Draft</span>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($projectData['status'] === 'pending'): ?>
        <div class="alert alert-warning">
            <i class="fas fa-clock me-2"></i>
            <strong>Project Under Review</strong><br>
            This project is currently being reviewed by our team. You can still make changes, but they won't be visible until the next review cycle.
        </div>
    <?php endif; ?>

    <?php if ($projectData['status'] === 'rejected' && !empty($projectData['moderation_notes'])): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <strong>Project Rejected</strong><br>
            <strong>Reason:</strong> <?= htmlspecialchars($projectData['moderation_notes']) ?><br>
            <small>Please address the issues above and resubmit your project.</small>
        </div>
    <?php endif; ?>

    <form id="projectForm" enctype="multipart/form-data">
        <input type="hidden" name="project_id" value="<?= $projectId ?>">

        <div class="row">
            <div class="col-lg-8">
                <!-- Project Information -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-info-circle me-2"></i>
                            Project Information
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="title" class="form-label">Project Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="title" name="title" required
                                   value="<?= htmlspecialchars($projectData['title']) ?>"
                                   placeholder="Enter your project title">
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="4"
                                      placeholder="Describe your project, its goals, and key features"><?= htmlspecialchars($projectData['description'] ?? '') ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="technologies" class="form-label">Technologies Used</label>
                            <input type="text" class="form-control" id="technologies" name="technologies"
                                   value="<?= htmlspecialchars($projectData['technologies'] ?? '') ?>"
                                   placeholder="e.g., React, Node.js, MongoDB, AWS">
                            <small class="form-text text-muted">
                                List the main technologies, frameworks, and tools used in this project
                            </small>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="project_url" class="form-label">Live Project URL</label>
                                    <input type="url" class="form-control" id="project_url" name="project_url"
                                           value="<?= htmlspecialchars($projectData['project_url'] ?? '') ?>"
                                           placeholder="https://your-project.com">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="github_url" class="form-label">GitHub Repository</label>
                                    <input type="url" class="form-control" id="github_url" name="github_url"
                                           value="<?= htmlspecialchars($projectData['github_url'] ?? '') ?>"
                                           placeholder="https://github.com/username/repo">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Current Images -->
                <?php if (!empty($images) && is_array($images)): ?>
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-images me-2"></i>
                                Current Images
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row" id="currentImages">
                                <?php foreach ($images as $index => $image): ?>
                                    <div class="col-md-4 mb-3" data-image="<?= htmlspecialchars($image) ?>">
                                        <div class="card">
                                            <img src="/storage/uploads/portfolio/<?= htmlspecialchars($image) ?>"
                                                 class="card-img-top" style="height: 150px; object-fit: cover;"
                                                 alt="Project image">
                                            <div class="card-body p-2">
                                                <button type="button" class="btn btn-sm btn-outline-danger w-100"
                                                        onclick="removeImage('<?= htmlspecialchars($image) ?>')">
                                                    <i class="fas fa-trash me-1"></i> Remove
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Add New Images -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-plus me-2"></i>
                            Add New Images
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="images" class="form-label">Upload New Images</label>
                            <input type="file" class="form-control" id="images" name="images[]"
                                   multiple accept="image/*">
                            <small class="form-text text-muted">
                                Upload additional screenshots, mockups, or other visual representations.
                                Supported formats: JPEG, PNG, GIF, WebP. Max size: 5MB per file.
                            </small>
                        </div>

                        <div id="imagePreview" class="row mt-3"></div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <!-- Project Settings -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-cog me-2"></i>
                            Project Settings
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="visibility" class="form-label">Visibility</label>
                            <select class="form-select" id="visibility" name="visibility">
                                <option value="private" <?= $projectData['visibility'] === 'private' ? 'selected' : '' ?>>
                                    Private - Only visible to you
                                </option>
                                <option value="public" <?= $projectData['visibility'] === 'public' ? 'selected' : '' ?>>
                                    Public - Visible to everyone (after approval)
                                </option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Categories</label>
                            <?php foreach ($categories as $category): ?>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox"
                                           name="categories[]" value="<?= $category['id'] ?>"
                                           id="category_<?= $category['id'] ?>"
                                           <?= in_array($category['id'], $assignedCategories) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="category_<?= $category['id'] ?>">
                                        <?= htmlspecialchars($category['name']) ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Project Stats -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-chart-bar me-2"></i>
                            Project Stats
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-6">
                                <div class="h5 mb-0"><?= rand(0, 500) ?></div>
                                <small class="text-muted">Views</small>
                            </div>
                            <div class="col-6">
                                <div class="h5 mb-0"><?= date('M j, Y', strtotime($projectData['created_at'])) ?></div>
                                <small class="text-muted">Created</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="card">
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary" data-action="save">
                                <i class="fas fa-save me-1"></i> Save Changes
                            </button>

                            <?php if ($projectData['status'] === 'draft'): ?>
                                <button type="submit" class="btn btn-success" data-action="submit">
                                    <i class="fas fa-paper-plane me-1"></i> Save & Submit for Review
                                </button>
                            <?php elseif ($projectData['status'] === 'rejected'): ?>
                                <button type="submit" class="btn btn-success" data-action="submit">
                                    <i class="fas fa-paper-plane me-1"></i> Save & Resubmit
                                </button>
                            <?php endif; ?>

                            <a href="/index.php?page=portfolio_manage" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-1"></i> Cancel
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
let removedImages = [];

document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('projectForm');
    const imageInput = document.getElementById('images');
    const imagePreview = document.getElementById('imagePreview');

    // Handle new image preview
    imageInput.addEventListener('change', function() {
        imagePreview.innerHTML = '';

        for (let i = 0; i < this.files.length; i++) {
            const file = this.files[i];
            if (file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const col = document.createElement('div');
                    col.className = 'col-md-4 mb-3';
                    col.innerHTML = `
                        <div class="card">
                            <img src="${e.target.result}" class="card-img-top" style="height: 150px; object-fit: cover;" alt="${file.name}">
                            <div class="card-body p-2">
                                <small class="text-muted">${file.name}</small>
                            </div>
                        </div>
                    `;
                    imagePreview.appendChild(col);
                };
                reader.readAsDataURL(file);
            }
        }
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
        submitButton.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Saving...';
        submitButton.disabled = true;

        fetch('/page/api/portfolio/update_project.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(action === 'submit' ?
                    'Project updated and submitted for review!' :
                    'Project updated successfully!'
                );
                window.location.href = '/page/user/portfolio/my_projects.php';
            } else {
                alert('Error: ' + (data.error || 'Unknown error occurred'));
                // Restore button state
                submitButton.innerHTML = originalText;
                submitButton.disabled = false;
            }
        })
        .catch(error => {
            alert('An error occurred while updating the project.');
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
