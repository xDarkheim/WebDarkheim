<?php
/**
 * Add New Portfolio Project
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

$pageTitle = 'Add New Project';
$current_user_id = $authService->getCurrentUserId();

// Get client profile
$stmt = $database_handler->prepare("SELECT * FROM client_profiles WHERE user_id = ?");
$stmt->execute([$current_user_id]);
$profileData = $stmt->fetch(PDO::FETCH_ASSOC);

// Get project categories (simplified - using existing categories table)
$stmt = $database_handler->prepare("SELECT * FROM categories WHERE status = 'active' ORDER BY name");
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/../../../resources/views/_header.php';
?>

<div class="container mt-4">
    <!-- Breadcrumbs -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/index.php?page=dashboard">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="/index.php?page=portfolio">Portfolio</a></li>
            <li class="breadcrumb-item active">Add Project</li>
        </ol>
    </nav>

    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">
            <i class="fas fa-plus text-primary me-2"></i>
            Add New Project
        </h1>
        <a href="/index.php?page=portfolio" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i>
            Back to Portfolio
        </a>
    </div>

    <?php if (!$profileData): ?>
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <strong>Profile Required</strong><br>
            You need to complete your profile before adding projects.
            <a href="/index.php?page=profile" class="btn btn-sm btn-warning mt-2">
                Complete Profile First
            </a>
        </div>
    <?php else: ?>
        <div class="row">
            <div class="col-lg-8">
                <!-- Project Form -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-info-circle me-2"></i>
                            Project Information
                        </h5>
                    </div>
                    <div class="card-body">
                        <form id="projectForm" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="title" class="form-label">Project Title <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="title" name="title" required
                                       placeholder="Enter your project title">
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="4"
                                          placeholder="Describe your project, its goals, and key features"></textarea>
                            </div>

                            <div class="mb-3">
                                <label for="technologies" class="form-label">Technologies Used</label>
                                <input type="text" class="form-control" id="technologies" name="technologies"
                                       placeholder="e.g., React, Node.js, MongoDB, AWS">
                                <small class="form-text text-muted">
                                    List the main technologies, frameworks, and tools used
                                </small>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="project_url" class="form-label">Live Project URL</label>
                                        <input type="url" class="form-control" id="project_url" name="project_url"
                                               placeholder="https://your-project.com">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="github_url" class="form-label">GitHub Repository</label>
                                        <input type="url" class="form-control" id="github_url" name="github_url"
                                               placeholder="https://github.com/username/repo">
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="images" class="form-label">Project Images</label>
                                <input type="file" class="form-control" id="images" name="images[]"
                                       multiple accept="image/*">
                                <small class="form-text text-muted">
                                    Upload screenshots, mockups, or other visuals. Max size: 5MB per file.
                                </small>
                            </div>

                            <div id="imagePreview" class="row mt-3"></div>
                        </form>
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
                                <option value="private">Private - Only visible to you</option>
                                <option value="public">Public - Visible after approval</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Categories</label>
                            <?php foreach ($categories as $category): ?>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox"
                                           name="categories[]" value="<?= $category['id'] ?>"
                                           id="category_<?= $category['id'] ?>">
                                    <label class="form-check-label" for="category_<?= $category['id'] ?>">
                                        <?= htmlspecialchars($category['name']) ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="card">
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <button type="submit" form="projectForm" class="btn btn-primary" data-action="save">
                                <i class="fas fa-save me-1"></i>
                                Save as Draft
                            </button>
                            <button type="submit" form="projectForm" class="btn btn-success" data-action="submit">
                                <i class="fas fa-paper-plane me-1"></i>
                                Save & Submit for Review
                            </button>
                            <a href="/index.php?page=portfolio" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-1"></i>
                                Cancel
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('projectForm');
    const imageInput = document.getElementById('images');
    const imagePreview = document.getElementById('imagePreview');

    // Handle image preview
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

        // Set status based on action
        if (action === 'submit') {
            formData.append('status', 'pending');
        } else {
            formData.append('status', 'draft');
        }

        // Show loading state
        const originalText = submitButton.innerHTML;
        submitButton.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Saving...';
        submitButton.disabled = true;

        fetch('/page/api/portfolio/create_project.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(action === 'submit' ?
                    'Project created and submitted for review!' :
                    'Project saved as draft!'
                );
                window.location.href = '/index.php?page=portfolio';
            } else {
                alert('Error: ' + (data.error || 'Unknown error occurred'));
                submitButton.innerHTML = originalText;
                submitButton.disabled = false;
            }
        })
        .catch(error => {
            alert('An error occurred while saving the project.');
            console.error('Error:', error);
            submitButton.innerHTML = originalText;
            submitButton.disabled = false;
        });
    });
});
</script>

<?php include __DIR__ . '/../../../resources/views/_footer.php'; ?>
