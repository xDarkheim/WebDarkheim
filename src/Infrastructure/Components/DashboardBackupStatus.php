<?php

/**
 * Dashboard Backup Status Component
 * Shows backup information only for administrators
 *
 * @author Dmytro Hovenko
 */

declare(strict_types=1);

namespace App\Infrastructure\Components;

use Exception;


class DashboardBackupStatus
{
    private mixed $authService;
    private mixed $logger;

    public function __construct(mixed $authService, mixed $logger)
    {
        $this->authService = $authService;
        $this->logger = $logger;
    }

    /**
     * Check if the current user is admin
     */
    public function isAdmin(): bool
    {
        return $this->authService->isAuthenticated() && 
               $this->authService->getCurrentUserRole() === 'admin';
    }

    /**
     * Get backup status data for dashboard
     */
    public function getBackupStatusData(): array
    {
        if (!$this->isAdmin()) {
            return [];
        }

        // Используем только fallback метод для стабильности
        return $this->getBackupStatusFromFiles();
    }

    /**
     * Fallback method to get backup status from files directly
     */
    private function getBackupStatusFromFiles(): array
    {
        try {
            $backupDir = '/var/www/darkheim.net/storage/backups/';

            if (!is_dir($backupDir)) {
                return [
                    'totalBackups' => 0,
                    'totalSize' => '0 B',
                    'latestBackup' => null,
                    'daysSinceLastBackup' => 999,
                    'status' => 'error',
                    'statusMessage' => 'Backup directory not found',
                    'statusClass' => 'danger',
                    'hasBackups' => false
                ];
            }

            $files = glob($backupDir . '*.sql.gz');
            $totalBackups = count($files);
            $totalSize = 0;
            $latestBackup = null;

            if ($totalBackups > 0) {
                // Сортируем файлы по времени модификации
                usort($files, function($a, $b) {
                    return filemtime($b) - filemtime($a);
                });

                $latestFile = $files[0];
                $latestBackup = [
                    'created_at' => date('Y-m-d H:i:s', filemtime($latestFile)),
                    'filename' => basename($latestFile)
                ];

                // Подсчитываем общий размер
                foreach ($files as $file) {
                    $totalSize += filesize($file);
                }

                $daysSinceLastBackup = floor((time() - filemtime($latestFile)) / 86400);
            } else {
                $daysSinceLastBackup = 999;
            }

            // Определяем статус
            $status = 'healthy';
            $statusMessage = 'System is operating normally';
            $statusClass = 'success';

            if ($totalBackups === 0) {
                $status = 'error';
                $statusMessage = 'No backups found';
                $statusClass = 'danger';
            } elseif ($daysSinceLastBackup > 7) {
                $status = 'error';
                $statusMessage = "Last backup is $daysSinceLastBackup days old";
                $statusClass = 'danger';
            } elseif ($daysSinceLastBackup > 1) {
                $status = 'warning';
                $statusMessage = "Last backup is $daysSinceLastBackup days old";
                $statusClass = 'warning';
            }

            return [
                'totalBackups' => $totalBackups,
                'totalSize' => $this->formatFileSize($totalSize),
                'latestBackup' => $latestBackup,
                'daysSinceLastBackup' => $daysSinceLastBackup,
                'status' => $status,
                'statusMessage' => $statusMessage,
                'statusClass' => $statusClass,
                'hasBackups' => $totalBackups > 0
            ];

        } catch (Exception $e) {
            $this->logger->error('Failed to get backup status from files', [
                'error' => $e->getMessage()
            ]);
            
            return [
                'totalBackups' => 0,
                'totalSize' => '0 B',
                'latestBackup' => null,
                'daysSinceLastBackup' => 999,
                'status' => 'error',
                'statusMessage' => 'Failed to load backup system',
                'statusClass' => 'danger',
                'hasBackups' => false
            ];
        }
    }

    /**
     * Get recent backup notifications
     */
    public function getRecentNotifications(): array
    {
        return [];

        // Заглушка для уведомлений - NotificationService может быть недоступен
    }

    /**
     * Render backup status widget for dashboard
     */
    public function renderBackupStatusWidget(): string
    {
        if (!$this->isAdmin()) {
            return '';
        }

        $data = $this->getBackupStatusData();
        
        if (empty($data)) {
            return '';
        }

        ob_start();
        ?>
        <div class="dashboard-backup-widget dashboard-profile-compact">
            <div class="profile-header">
                <h3 class="dashboard-section-title">
                    <i class="fas fa-shield-alt"></i> Backup System
                </h3>
                <a href="/index.php?page=backup_monitor" class="overview-card-action">
                    <i class="fas fa-cog"></i> Manage
                </a>
            </div>
            
            <div class="backup-status-indicator backup-status-<?php echo $data['statusClass']; ?>">
                <div class="backup-status-icon">
                    <?php if ($data['status'] === 'healthy'): ?>
                        <i class="fas fa-check-circle"></i>
                    <?php elseif ($data['status'] === 'warning'): ?>
                        <i class="fas fa-exclamation-triangle"></i>
                    <?php else: ?>
                        <i class="fas fa-times-circle"></i>
                    <?php endif; ?>
                </div>
                <div class="backup-status-text">
                    <strong><?php echo htmlspecialchars($data['statusMessage']); ?></strong>
                </div>
            </div>

            <div class="profile-summary-grid">
                <div class="profile-summary-item">
                    <span class="profile-summary-label">Backups</span>
                    <span class="profile-summary-value"><?php echo $data['totalBackups']; ?></span>
                </div>
                <div class="profile-summary-item">
                    <span class="profile-summary-label">Size</span>
                    <span class="profile-summary-value"><?php echo $data['totalSize']; ?></span>
                </div>
                <?php if ($data['latestBackup']): ?>
                <div class="profile-summary-item">
                    <span class="profile-summary-label">Latest</span>
                    <span class="profile-summary-value"><?php echo date('M j, H:i', strtotime($data['latestBackup']['created_at'] ?? 'now')); ?></span>
                </div>
                <?php endif; ?>
            </div>

            <?php if ($data['hasBackups']): ?>
            <div class="profile-completion-actions">
                <button onclick="createManualBackupFromDashboard()" class="completion-link backup-create-btn">
                    <i class="fas fa-plus"></i>
                    Create Backup
                    <small>Generate new system backup</small>
                </button>
            </div>
            <?php endif; ?>
        </div>

        <script>
        function createManualBackupFromDashboard() {
            if (confirm('Create a manual backup now? This may take a few moments.')) {
                const btn = event.target.closest('.backup-create-btn');
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating... <small>Please wait</small>';
                btn.disabled = true;

                fetch('/page/api/manual_backup.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin', // Добавляем для передачи cookie сессии
                    body: JSON.stringify({
                        action: 'create_manual_backup'
                    })
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(_data => {
                    // Не показываем собственные уведомления - FlashMessage покажет их после перезагрузки
                    btn.innerHTML = '<i class="fas fa-check"></i> Success! <small>Reloading...</small>';
                    btn.style.background = 'var(--color-success-bg)';
                    btn.style.borderColor = 'var(--color-success)';
                    btn.style.color = 'var(--color-success)';

                    // Перезагружаем страницу, чтобы показать FlashMessage
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                })
                .catch(error => {
                    console.error('Manual backup error:', error);
                    btn.innerHTML = '<i class="fas fa-times"></i> Error <small>Please try again</small>';
                    btn.style.background = 'var(--color-error-bg)';
                    btn.style.borderColor = 'var(--color-error)';
                    btn.style.color = 'var(--color-error)';

                    // Перезагружаем страницу даже при ошибке, чтобы показать FlashMessage
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                });
            }
        }
        </script>
        <?php
        return ob_get_clean();
    }

    /**
     * Render recent notifications widget for dashboard
     */
    public function renderNotificationsWidget(): string
    {
        if (!$this->isAdmin()) {
            return '';
        }

        $notifications = $this->getRecentNotifications();
        
        if (empty($notifications)) {
            return '';
        }

        ob_start();
        ?>
        <div class="dashboard-notifications-widget">
            <div class="widget-header">
                <h3><i class="fas fa-bell"></i> Recent Backup Activity</h3>
            </div>
            
            <div class="widget-content">
                <?php foreach ($notifications as $notification): ?>
                <div class="notification-item notification-<?php echo $notification['type']; ?>">
                    <div class="notification-icon">
                        <?php if ($notification['type'] === 'backup_success'): ?>
                            <i class="fas fa-check-circle text-success"></i>
                        <?php else: ?>
                            <i class="fas fa-exclamation-circle text-danger"></i>
                        <?php endif; ?>
                    </div>
                    <div class="notification-content">
                        <div class="notification-message">
                            <?php echo htmlspecialchars($notification['message']); ?>
                        </div>
                        <div class="notification-time">
                            <?php echo $notification['timestamp']; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <style>
        .dashboard-notifications-widget {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .notification-item {
            display: flex;
            align-items: flex-start;
            padding: 10px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .notification-item:last-child {
            border-bottom: none;
        }
        
        .notification-icon {
            margin-right: 10px;
            margin-top: 2px;
        }
        
        .notification-content {
            flex: 1;
        }
        
        .notification-message {
            font-size: 13px;
            color: #333;
            margin-bottom: 2px;
        }
        
        .notification-time {
            font-size: 11px;
            color: #666;
        }
        
        .text-success {
            color: #28a745;
        }
        
        .text-danger {
            color: #dc3545;
        }
        </style>
        <?php
        return ob_get_clean();
    }

    /**
     * Format file size in human-readable format
     */
    private function formatFileSize(int $bytes): string
    {
        if ($bytes <= 0) return '0 B';

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = (int)floor(log($bytes) / log(1024));
        $power = min($power, count($units) - 1); // Предотвращаем выход за границы массива

        return round($bytes / pow(1024, $power), 2) . ' ' . $units[$power];
    }

}
