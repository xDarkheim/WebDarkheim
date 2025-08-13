<?php

/**
 * Project Documents - Client Portal - DARK ADMIN THEME
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

    <link rel="stylesheet" href="/public/assets/css/admin.css">

<!-- Navigation -->
<nav class="admin-nav">
    <div class="admin-nav-container">
        <a href="/index.php?page=user_dashboard" class="admin-nav-brand">
            <i class="fas fa-folder"></i>
            Document Portal
        </a>
        <div class="admin-nav-links">
            <a href="/index.php?page=user_dashboard" class="admin-nav-link">
                <i class="fas fa-home"></i> Dashboard
            </a>
            <button class="admin-nav-link" onclick="refreshDocuments()">
                <i class="fas fa-sync-alt"></i> Refresh
            </button>
        </div>
    </div>
</nav>

<!-- Header -->
<header class="admin-header">
    <div class="admin-header-container">
        <div class="admin-header-content">
            <div class="admin-header-title">
                <i class="admin-header-icon fas fa-folder"></i>
                <div class="admin-header-text">
                    <h1>Project Documents</h1>
                    <p>Access project specifications, designs, and deliverables</p>
                </div>
            </div>
            <div class="admin-header-actions">
                <button class="admin-btn admin-btn-secondary" onclick="refreshDocuments()">
                    <i class="fas fa-sync-alt"></i> Refresh
                </button>
            </div>
        </div>
    </div>
</header>

<div class="admin-layout-main">
    <div class="admin-content">
        <!-- Flash Messages -->
        <?php if (!empty($flashMessages)): ?>
            <div class="admin-flash-messages">
                <?php foreach ($flashMessages as $type => $messages): ?>
                    <?php foreach ($messages as $message): ?>
                        <div class="admin-flash-message admin-flash-<?= $type === 'error' ? 'error' : $type ?>">
                            <i class="fas fa-<?= $type === 'error' ? 'exclamation-circle' : ($type === 'success' ? 'check-circle' : 'info-circle') ?>"></i>
                            <div><?= htmlspecialchars($message['text']) ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Documents by Project -->
        <?php if (empty($projectDocuments)): ?>
            <div class="admin-card" style="text-align: center; padding: 3rem 0;">
                <div class="admin-card-body">
                    <i class="fas fa-folder-open fa-3x" style="color: var(--admin-text-muted); margin-bottom: 1rem;"></i>
                    <h5 style="color: var(--admin-text-muted); margin-bottom: 0.5rem;">No documents available</h5>
                    <p style="color: var(--admin-text-muted);">Project documents will appear here as they become available.</p>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($projectDocuments as $projectName => $docs): ?>
                <div class="admin-card" style="margin-bottom: 1.5rem;">
                    <div class="admin-card-header">
                        <h3 class="admin-card-title">
                            <i class="fas fa-project-diagram"></i>
                            <?= htmlspecialchars($projectName) ?>
                        </h3>
                    </div>
                    <div class="admin-card-body">
                        <div class="admin-grid admin-grid-cols-3">
                            <?php foreach ($docs as $doc): ?>
                                <div class="admin-card" style="height: 100%; margin-bottom: 0;">
                                    <div class="admin-card-body" style="text-align: center; padding: 1.5rem;">
                                        <div style="font-size: 2rem; margin-bottom: 1rem; color: var(--admin-<?= getFileIconColor($doc['document_type']) ?>);">
                                            <i class="<?= getFileIcon($doc['document_type']) ?>"></i>
                                        </div>
                                        <h6 style="margin-bottom: 0.75rem; color: var(--admin-text-primary);"><?= htmlspecialchars($doc['document_name']) ?></h6>
                                        <p style="margin-bottom: 1rem;">
                                            <span class="admin-badge admin-badge-gray">
                                                <?= ucfirst(str_replace('_', ' ', $doc['document_type'])) ?>
                                            </span>
                                        </p>
                                        <div style="font-size: 0.8rem; color: var(--admin-text-muted); margin-bottom: 1rem;">
                                            <?= formatFileSize($doc['file_size']) ?>
                                        </div>
                                        <div style="display: grid;">
                                            <a href="/index.php?page=user_documents_download&id=<?= $doc['id'] ?>"
                                               class="admin-btn admin-btn-sm admin-btn-primary" target="_blank">
                                                <i class="fas fa-download"></i> Download
                                            </a>
                                        </div>
                                    </div>
                                    <div class="admin-card-footer" style="text-align: center; background: var(--admin-bg-secondary);">
                                        <small style="color: var(--admin-text-muted);">
                                            Uploaded <?= date('M j, Y', strtotime($doc['uploaded_at'])) ?>
                                            <?php if ($doc['uploaded_by_name']): ?>
                                                by <?= htmlspecialchars($doc['uploaded_by_name']) ?>
                                            <?php endif; ?>
                                        </small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Sidebar -->
    <div class="admin-sidebar">
        <!-- Document Types Legend -->
        <div class="admin-card">
            <div class="admin-card-header">
                <h4 class="admin-card-title">
                    <i class="fas fa-info-circle"></i>
                    Document Types
                </h4>
            </div>
            <div class="admin-card-body">
                <div style="display: grid; gap: 0.75rem;">
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <i class="fas fa-file-alt" style="color: var(--admin-primary);"></i>
                        <div>
                            <strong>Specification</strong>
                            <br><small style="color: var(--admin-text-muted);">Project requirements and scope</small>
                        </div>
                    </div>
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <i class="fas fa-paint-brush" style="color: var(--admin-success);"></i>
                        <div>
                            <strong>Design</strong>
                            <br><small style="color: var(--admin-text-muted);">UI/UX designs and mockups</small>
                        </div>
                    </div>
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <i class="fas fa-file-contract" style="color: var(--admin-warning);"></i>
                        <div>
                            <strong>Contract</strong>
                            <br><small style="color: var(--admin-text-muted);">Legal agreements and terms</small>
                        </div>
                    </div>
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <i class="fas fa-chart-line" style="color: var(--admin-info);"></i>
                        <div>
                            <strong>Report</strong>
                            <br><small style="color: var(--admin-text-muted);">Progress reports and analytics</small>
                        </div>
                    </div>
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <i class="fas fa-file" style="color: var(--admin-text-secondary);"></i>
                        <div>
                            <strong>Other</strong>
                            <br><small style="color: var(--admin-text-muted);">Additional project materials</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Help Section -->
        <div class="admin-card">
            <div class="admin-card-header">
                <h4 class="admin-card-title">
                    <i class="fas fa-question-circle"></i>
                    Need Help?
                </h4>
            </div>
            <div class="admin-card-body">
                <p style="font-size: 0.875rem; margin-bottom: 1rem;">
                    Can't find a document or having issues downloading?
                </p>
                <div style="display: grid; gap: 0.5rem;">
                    <a href="/index.php?page=user_tickets_create" class="admin-btn admin-btn-sm admin-btn-primary">
                        <i class="fas fa-ticket-alt"></i> Create Support Ticket
                    </a>
                    <a href="mailto:support@darkheim.net" class="admin-btn admin-btn-sm admin-btn-secondary">
                        <i class="fas fa-envelope"></i> Contact Support
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="/public/assets/js/admin.js"></script>
<script>
function refreshDocuments() {
    // Show loading state
    const refreshBtns = document.querySelectorAll('[onclick="refreshDocuments()"]');
    const originalTexts = [];

    refreshBtns.forEach((btn, index) => {
        originalTexts[index] = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Refreshing...';
        btn.disabled = true;
    });

    // Reload page after short delay
    setTimeout(() => {
        window.location.reload();
    }, 1000);
}
</script>

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
        default => 'text-secondary'
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
