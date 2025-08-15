<?php
/**
 * Document Details - Client Portal
 * Detailed view of a single document with version history
 */

require_once '../../../includes/bootstrap.php';

use App\Application\Core\ServiceProvider;
use App\Application\Controllers\DocumentWorkflowController;

$services = ServiceProvider::getInstance();
$auth = $services->getAuth();
$user = $auth->getCurrentUser();

// Check if user is authenticated and is a client
if (!$user || !in_array($user['role'], ['client', 'admin'])) {
    header('Location: /page/auth/login.php');
    exit;
}

// Initialize controller and get data
$controller = new DocumentWorkflowController($services);
$data = $controller->getDocumentDetails();

if (!$data['success']) {
    $error = $data['error'];
} else {
    $document = $data['document'];
    $versions = $data['versions'];
}

// Page metadata
$pageTitle = isset($document) ? $document['document_name'] . ' - Document Details' : 'Document Details';
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => '/page/user/dashboard.php'],
    ['title' => 'Documents', 'url' => '/page/user/documents/index.php'],
    ['title' => isset($document) ? $document['document_name'] : 'Document Details', 'url' => '', 'active' => true]
];
?>

<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> - <?= htmlspecialchars(getSiteName()) ?></title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Custom CSS -->
    <link href="/themes/default/css/admin.css" rel="stylesheet">

    <style>
        .document-header {
            background: linear-gradient(135deg, var(--bs-primary), var(--bs-primary-dark));
            border-radius: 15px;
            padding: 2rem;
            color: white;
            margin-bottom: 2rem;
        }
        .document-icon-large {
            width: 80px;
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 15px;
            margin-right: 2rem;
        }
        .version-item {
            border: 1px solid var(--bs-border-color);
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
            transition: all 0.2s ease;
        }
        .version-item:hover {
            border-color: var(--bs-primary);
            background: rgba(var(--bs-primary-rgb), 0.05);
        }
        .metadata-card {
            background: var(--bs-secondary-bg);
            border-radius: 10px;
            padding: 20px;
        }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <?php include '../../../themes/default/components/admin_navigation.php'; ?>

        <div class="admin-content">
            <div class="content-header">
                <div class="container-fluid">
                    <div class="row mb-3">
                        <div class="col-sm-6">
                            <h1 class="m-0">Document Details</h1>
                        </div>
                        <div class="col-sm-6">
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb float-sm-end">
                                    <?php foreach ($breadcrumbs as $crumb): ?>
                                        <li class="breadcrumb-item <?= $crumb['active'] ?? false ? 'active' : '' ?>">
                                            <?php if ($crumb['active'] ?? false): ?>
                                                <?= htmlspecialchars($crumb['title']) ?>
                                            <?php else: ?>
                                                <a href="<?= htmlspecialchars($crumb['url']) ?>"><?= htmlspecialchars($crumb['title']) ?></a>
                                            <?php endif; ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ol>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>

            <div class="content-body">
                <div class="container-fluid">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            <?= htmlspecialchars($error) ?>
                        </div>
                        <div class="text-center">
                            <a href="/page/user/documents/index.php" class="btn btn-primary">
                                <i class="bi bi-arrow-left me-2"></i>Back to Document Library
                            </a>
                        </div>
                    <?php else: ?>

                        <!-- Action Buttons -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <a href="/page/user/documents/index.php" class="btn btn-outline-secondary">
                                            <i class="bi bi-arrow-left me-2"></i>Back to Library
                                        </a>
                                    </div>
                                    <div>
                                        <button class="btn btn-primary" onclick="downloadDocument(<?= $document['id'] ?>)">
                                            <i class="bi bi-download me-2"></i>Download Document
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Document Header -->
                        <div class="document-header">
                            <div class="d-flex align-items-center">
                                <div class="document-icon-large bg-white bg-opacity-25">
                                    <i class="<?= $document['type_icon'] ?> fs-1 text-white"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h2 class="mb-2"><?= htmlspecialchars($document['document_name']) ?></h2>
                                    <div class="mb-3">
                                        <span class="badge <?= $document['type_badge_class'] ?> fs-6 me-2">
                                            <?= ucfirst($document['document_type']) ?>
                                        </span>
                                        <span class="badge bg-light text-dark fs-6">
                                            <?= htmlspecialchars($document['project_name']) ?>
                                        </span>
                                    </div>
                                    <p class="mb-0 fs-5"><?= $document['file_size_formatted'] ?> • Uploaded <?= $document['uploaded_at_formatted'] ?></p>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <!-- Document Information -->
                            <div class="col-lg-8">
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h6 class="card-title mb-0">
                                            <i class="bi bi-info-circle me-2"></i>Document Information
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="metadata-card">
                                                    <h6 class="fw-bold mb-3">Basic Information</h6>
                                                    <div class="row mb-2">
                                                        <div class="col-sm-5"><strong>File Name:</strong></div>
                                                        <div class="col-sm-7"><?= htmlspecialchars($document['document_name']) ?></div>
                                                    </div>
                                                    <div class="row mb-2">
                                                        <div class="col-sm-5"><strong>Type:</strong></div>
                                                        <div class="col-sm-7">
                                                            <span class="badge <?= $document['type_badge_class'] ?>">
                                                                <?= ucfirst($document['document_type']) ?>
                                                            </span>
                                                        </div>
                                                    </div>
                                                    <div class="row mb-2">
                                                        <div class="col-sm-5"><strong>File Size:</strong></div>
                                                        <div class="col-sm-7"><?= $document['file_size_formatted'] ?></div>
                                                    </div>
                                                    <div class="row mb-2">
                                                        <div class="col-sm-5"><strong>Project:</strong></div>
                                                        <div class="col-sm-7"><?= htmlspecialchars($document['project_name']) ?></div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="metadata-card">
                                                    <h6 class="fw-bold mb-3">Upload Details</h6>
                                                    <div class="row mb-2">
                                                        <div class="col-sm-5"><strong>Uploaded:</strong></div>
                                                        <div class="col-sm-7"><?= $document['uploaded_at_formatted'] ?></div>
                                                    </div>
                                                    <?php if ($document['uploaded_by_name']): ?>
                                                        <div class="row mb-2">
                                                            <div class="col-sm-5"><strong>Uploaded By:</strong></div>
                                                            <div class="col-sm-7"><?= htmlspecialchars($document['uploaded_by_name']) ?></div>
                                                        </div>
                                                    <?php endif; ?>
                                                    <div class="row mb-2">
                                                        <div class="col-sm-5"><strong>Client Access:</strong></div>
                                                        <div class="col-sm-7">
                                                            <span class="badge bg-success">
                                                                <i class="bi bi-check-circle me-1"></i>Visible
                                                            </span>
                                                        </div>
                                                    </div>
                                                    <div class="row mb-2">
                                                        <div class="col-sm-5"><strong>Status:</strong></div>
                                                        <div class="col-sm-7">
                                                            <span class="badge bg-primary">Available</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Document Preview/Actions -->
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h6 class="card-title mb-0">
                                            <i class="bi bi-eye me-2"></i>Document Actions
                                        </h6>
                                    </div>
                                    <div class="card-body text-center">
                                        <div class="mb-4">
                                            <i class="<?= $document['type_icon'] ?> text-<?= str_replace('bg-', '', $document['type_badge_class']) ?>" style="font-size: 4rem;"></i>
                                        </div>
                                        <h5 class="mb-3"><?= htmlspecialchars($document['document_name']) ?></h5>
                                        <p class="text-muted mb-4">
                                            This document is ready for download. Click the button below to access the file securely.
                                        </p>
                                        <div class="d-grid gap-2 d-md-block">
                                            <button class="btn btn-primary btn-lg" onclick="downloadDocument(<?= $document['id'] ?>)">
                                                <i class="bi bi-download me-2"></i>Download Document
                                            </button>
                                            <button class="btn btn-outline-secondary btn-lg" onclick="window.print()">
                                                <i class="bi bi-printer me-2"></i>Print Details
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Sidebar -->
                            <div class="col-lg-4">
                                <!-- Quick Stats -->
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h6 class="card-title mb-0">
                                            <i class="bi bi-graph-up me-2"></i>Document Stats
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <span>File Size</span>
                                            <span class="fw-bold"><?= $document['file_size_formatted'] ?></span>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <span>Upload Date</span>
                                            <span class="fw-bold"><?= date('M j, Y', strtotime($document['uploaded_at'])) ?></span>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <span>Document Type</span>
                                            <span class="fw-bold"><?= ucfirst($document['document_type']) ?></span>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span>Project</span>
                                            <span class="fw-bold"><?= htmlspecialchars(strlen($document['project_name']) > 15 ? substr($document['project_name'], 0, 15) . '...' : $document['project_name']) ?></span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Version History -->
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h6 class="card-title mb-0">
                                            <i class="bi bi-clock-history me-2"></i>Version History
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <?php if (count($versions) > 1): ?>
                                            <?php foreach ($versions as $index => $version): ?>
                                                <div class="version-item <?= $index === 0 ? 'border-primary' : '' ?>">
                                                    <div class="d-flex justify-content-between align-items-start">
                                                        <div class="flex-grow-1">
                                                            <h6 class="mb-1">
                                                                Version <?= count($versions) - $index ?>
                                                                <?php if ($index === 0): ?>
                                                                    <span class="badge bg-primary">Current</span>
                                                                <?php endif; ?>
                                                            </h6>
                                                            <div class="small text-muted">
                                                                <div><?= date('M j, Y g:i A', strtotime($version['uploaded_at'])) ?></div>
                                                                <?php if ($version['uploaded_by_name']): ?>
                                                                    <div>by <?= htmlspecialchars($version['uploaded_by_name']) ?></div>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                        <?php if ($index === 0): ?>
                                                            <i class="bi bi-star-fill text-warning"></i>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <div class="version-item border-primary">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <div class="flex-grow-1">
                                                        <h6 class="mb-1">
                                                            Current Version
                                                            <span class="badge bg-primary">Latest</span>
                                                        </h6>
                                                        <div class="small text-muted">
                                                            <div><?= $document['uploaded_at_formatted'] ?></div>
                                                            <?php if ($document['uploaded_by_name']): ?>
                                                                <div>by <?= htmlspecialchars($document['uploaded_by_name']) ?></div>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                    <i class="bi bi-star-fill text-warning"></i>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- Related Documents (Future Enhancement) -->
                                <div class="card">
                                    <div class="card-header">
                                        <h6 class="card-title mb-0">
                                            <i class="bi bi-link-45deg me-2"></i>Related Documents
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <p class="text-muted text-center py-3">
                                            Related documents for this project will appear here.
                                        </p>
                                        <div class="d-grid">
                                            <a href="index.php?project=<?= $document['project_id'] ?>" class="btn btn-outline-primary btn-sm">
                                                <i class="bi bi-folder me-1"></i>View All Project Documents
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        function downloadDocument(documentId) {
            window.open(`/page/api/client/download_document.php?id=${documentId}`, '_blank');
        }
    </script>
</body>
</html>
