<?php
/**
 * User Profile View Page - PHASE 8
 * Shows user profile information (read-only view)
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

$pageTitle = 'My Profile';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-light">

<div class="container py-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h2">
                    <i class="fas fa-user text-primary"></i>
                    My Profile
                </h1>
                <div class="d-flex gap-2">
                    <a href="/index.php?page=profile_edit" class="btn btn-primary">
                        <i class="fas fa-edit"></i> Edit Profile
                    </a>
                    <a href="/index.php?page=user_profile_settings" class="btn btn-outline-secondary">
                        <i class="fas fa-cogs"></i> Settings
                    </a>
                    <a href="/index.php?page=dashboard" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Basic Information -->
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-info-circle text-primary"></i>
                        Basic Information
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Username:</strong> <?= htmlspecialchars($currentUser['username']) ?></p>
                            <p><strong>Email:</strong> <?= htmlspecialchars($currentUser['email']) ?></p>
                            <p><strong>Role:</strong> <span class="badge bg-primary"><?= ucfirst($currentUser['role']) ?></span></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Member since:</strong> <?= date('F j, Y', strtotime($currentUser['created_at'])) ?></p>
                            <p><strong>Last updated:</strong> <?= date('F j, Y', strtotime($currentUser['updated_at'])) ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Extended Profile (Client Profile) -->
            <?php if ($clientProfile): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-briefcase text-primary"></i>
                            Professional Information
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <?php if (!empty($clientProfile['company_name'])): ?>
                                    <p><strong>Company:</strong> <?= htmlspecialchars($clientProfile['company_name']) ?></p>
                                <?php endif; ?>
                                <?php if (!empty($clientProfile['position'])): ?>
                                    <p><strong>Position:</strong> <?= htmlspecialchars($clientProfile['position']) ?></p>
                                <?php endif; ?>
                                <?php if (!empty($clientProfile['location'])): ?>
                                    <p><strong>Location:</strong> <?= htmlspecialchars($clientProfile['location']) ?></p>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6">
                                <?php if (!empty($clientProfile['website'])): ?>
                                    <p><strong>Website:</strong> 
                                        <a href="<?= htmlspecialchars($clientProfile['website']) ?>" target="_blank">
                                            <?= htmlspecialchars($clientProfile['website']) ?>
                                        </a>
                                    </p>
                                <?php endif; ?>
                                <p><strong>Portfolio Visibility:</strong> 
                                    <span class="badge bg-<?= $clientProfile['portfolio_visibility'] === 'public' ? 'success' : 'secondary' ?>">
                                        <?= ucfirst($clientProfile['portfolio_visibility']) ?>
                                    </span>
                                </p>
                            </div>
                        </div>
                        
                        <?php if (!empty($clientProfile['bio'])): ?>
                            <div class="mt-3">
                                <h6>Bio:</h6>
                                <p><?= nl2br(htmlspecialchars($clientProfile['bio'])) ?></p>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($clientProfile['skills'])): ?>
                            <div class="mt-3">
                                <h6>Skills:</h6>
                                <div class="d-flex flex-wrap gap-1">
                                    <?php 
                                    $skills = json_decode($clientProfile['skills'], true);
                                    if ($skills && is_array($skills)):
                                        foreach ($skills as $skill): ?>
                                            <span class="badge bg-light text-dark"><?= htmlspecialchars($skill) ?></span>
                                        <?php endforeach;
                                    endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="card mb-4">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-user-plus fa-3x text-muted mb-3"></i>
                        <h5>Complete Your Profile</h5>
                        <p class="text-muted">Add professional information to make your profile complete.</p>
                        <a href="/index.php?page=profile_edit" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Complete Profile
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Profile Actions -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0">Quick Actions</h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="/index.php?page=profile_edit" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-edit"></i> Edit Profile
                        </a>
                        <a href="/index.php?page=user_portfolio" class="btn btn-outline-success btn-sm">
                            <i class="fas fa-briefcase"></i> View Portfolio
                        </a>
                        <a href="/index.php?page=user_profile_settings" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-cogs"></i> Account Settings
                        </a>
                    </div>
                </div>
            </div>

            <!-- Profile Statistics -->
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Profile Statistics</h6>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6">
                            <h4 class="text-primary mb-0">
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
                            </h4>
                            <small class="text-muted">Projects</small>
                        </div>
                        <div class="col-6">
                            <h4 class="text-success mb-0">
                                <?php
                                // Get completion percentage
                                if ($clientProfile) {
                                    $fields = ['company_name', 'position', 'bio', 'location', 'website'];
                                    $completed = 0;
                                    foreach ($fields as $field) {
                                        if (!empty($clientProfile[$field])) $completed++;
                                    }
                                    echo round(($completed / count($fields)) * 100);
                                } else {
                                    echo '0';
                                }
                                ?>%
                            </h4>
                            <small class="text-muted">Complete</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
