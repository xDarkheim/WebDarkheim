<?php

/**
 * Project Documents - Client Portal
 * Access and download project-related documents
 *
 * @author GitHub Copilot
 */

declare(strict_types=1);

// Use global services from the DI architecture
global $flashMessageService, $database_handler, $container, $serviceProvider;

try {
    $authService = $serviceProvider->getAuth();
} catch (Exception $e) {
    error_log("Critical: Failed to get AuthenticationService instance: " . $e->getMessage());
    die("A critical system error occurred. Please try again later.");
}

// Check authentication
if (!$authService->isAuthenticated()) {
    $flashMessageService->addError('Please log in to access your documents.');
    header("Location: /index.php?page=login");
    exit();
}

$currentUser = $authService->getCurrentUser();

// Check if user can access client area
if (!in_array($currentUser['role'], ['client', 'employee', 'admin'])) {
    header('Location: /index.php?page=home');
    exit;
}

// Get documents for current user's projects
try {
    $sql = "SELECT pd.*, sp.project_name, u.username as uploaded_by_name
            FROM project_documents pd
            JOIN studio_projects sp ON pd.project_id = sp.id
            LEFT JOIN users u ON pd.uploaded_by = u.id
            WHERE sp.client_id = ? AND pd.is_client_visible = 1
            ORDER BY pd.uploaded_at DESC";

    $stmt = $database_handler->getConnection()->prepare($sql);
    $stmt->execute([$currentUser['id']]);
    $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Group documents by project
    $projectDocuments = [];
    foreach ($documents as $doc) {
        $projectDocuments[$doc['project_name']][] = $doc;
    }

} catch (Exception $e) {
    error_log("Error getting documents: " . $e->getMessage());
    $documents = [];
    $projectDocuments = [];
}

// Get flash messages
$flashMessages = $flashMessageService->getAllMessages();
$pageTitle = 'Project Documents - Client Portal';

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
        .document-card {
            transition: all 0.3s ease;
            border: none;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .document-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        .file-icon {
            font-size: 2rem;
            margin-bottom: 1rem;
        }
        .file-size {
            font-size: 0.8rem;
            color: #6c757d;
        }
    </style>
</head>
<body class="bg-light">

<div class="container py-4">
    <!-- Breadcrumb Navigation -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/index.php?page=dashboard">Dashboard</a></li>
            <li class="breadcrumb-item active">Project Documents</li>
        </ol>
    </nav>

    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0">
                        <i class="fas fa-folder text-primary"></i>
                        Project Documents
                    </h1>
                    <p class="text-muted">Access project specifications, designs, and deliverables</p>
                </div>
                <div class="btn-group">
                    <button class="btn btn-outline-secondary" onclick="refreshDocuments()">
                        <i class="fas fa-sync-alt"></i> Refresh
                    </button>
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

    <!-- Documents by Project -->
    <?php if (empty($projectDocuments)): ?>
        <div class="card document-card text-center py-5">
            <div class="card-body">
                <i class="fas fa-folder-open fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No documents available</h5>
                <p class="text-muted">Project documents will appear here as they become available.</p>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($projectDocuments as $projectName => $docs): ?>
            <div class="card document-card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-project-diagram text-primary"></i>
                        <?= htmlspecialchars($projectName) ?>
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php foreach ($docs as $doc): ?>
                            <div class="col-md-6 col-lg-4 mb-3">
                                <div class="card h-100">
                                    <div class="card-body text-center">
                                        <div class="file-icon text-<?= getFileIconColor($doc['document_type']) ?>">
                                            <i class="<?= getFileIcon($doc['document_type']) ?>"></i>
                                        </div>
                                        <h6 class="card-title"><?= htmlspecialchars($doc['document_name']) ?></h6>
                                        <p class="card-text">
                                            <span class="badge bg-light text-dark">
                                                <?= ucfirst(str_replace('_', ' ', $doc['document_type'])) ?>
                                            </span>
                                        </p>
                                        <div class="file-size mb-3">
                                            <?= formatFileSize($doc['file_size']) ?>
                                        </div>
                                        <div class="d-grid">
                                            <a href="/index.php?page=user_documents_download&id=<?= $doc['id'] ?>"
                                               class="btn btn-primary btn-sm" target="_blank">
                                                <i class="fas fa-download"></i> Download
                                            </a>
                                        </div>
                                    </div>
                                    <div class="card-footer bg-transparent text-center">
                                        <small class="text-muted">
                                            Uploaded <?= date('M j, Y', strtotime($doc['uploaded_at'])) ?>
                                            <?php if ($doc['uploaded_by_name']): ?>
                                                by <?= htmlspecialchars($doc['uploaded_by_name']) ?>
                                            <?php endif; ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- Document Types Legend -->
    <div class="card document-card mt-4">
        <div class="card-header">
            <h6 class="card-title mb-0">
                <i class="fas fa-info-circle text-info"></i>
                Document Types
            </h6>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <ul class="list-unstyled">
                        <li><i class="fas fa-file-alt text-primary"></i> <strong>Specification</strong> - Project requirements and scope</li>
                        <li><i class="fas fa-paint-brush text-success"></i> <strong>Design</strong> - UI/UX designs and mockups</li>
                        <li><i class="fas fa-file-contract text-warning"></i> <strong>Contract</strong> - Legal agreements and terms</li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <ul class="list-unstyled">
                        <li><i class="fas fa-chart-line text-info"></i> <strong>Report</strong> - Progress reports and analytics</li>
                        <li><i class="fas fa-file text-secondary"></i> <strong>Other</strong> - Additional project materials</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function refreshDocuments() {
    // Show loading state
    const refreshBtn = document.querySelector('[onclick="refreshDocuments()"]');
    const originalText = refreshBtn.innerHTML;
    refreshBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Refreshing...';
    refreshBtn.disabled = true;

    // Reload page after short delay
    setTimeout(() => {
        window.location.reload();
    }, 1000);
}
</script>

</body>
</html>

<?php
function getFileIcon($type): string
{
    return match($type) {
        'specification' => 'fas fa-file-alt',
        'design' => 'fas fa-paint-brush',
        'contract' => 'fas fa-file-contract',
        'report' => 'fas fa-chart-line',
        default => 'fas fa-file'
    };
}

function getFileIconColor($type): string
{
    return match($type) {
        'specification' => 'primary',
        'design' => 'success',
        'contract' => 'warning',
        'report' => 'info',
        default => 'secondary'
    };
}

function formatFileSize($bytes): string
{
    if ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 1) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 1) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}
?>
