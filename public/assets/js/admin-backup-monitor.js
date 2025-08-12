/**
 * Backup Monitor Admin Page JavaScript
 * Handles backup creation, deletion, and UI interactions
 * Uses FlashMessage system instead of custom toasts
 */

(function() {
    'use strict';

    // Namespace for backup monitor functionality
    const BackupMonitor = {

        // Configuration
        config: {
            csrfTokenSelector: 'meta[name="csrf-token"]',
            pageReloadDelay: 1500,
            animationDuration: 300
        },

        // Initialize the backup monitor
        init: function() {
            this.bindEvents();
            this.addTooltips();
            this.initFlashMessageHandling();
            console.log('Backup Monitor initialized with FlashMessage integration');
        },

        // Initialize flash message handling
        initFlashMessageHandling: function() {
            // Auto-hide flash messages after some time
            const flashMessages = document.querySelectorAll('.flash-messages .flash-message');
            flashMessages.forEach(message => {
                // Add close functionality
                const closeBtn = message.querySelector('.close');
                if (closeBtn) {
                    closeBtn.addEventListener('click', (e) => {
                        e.preventDefault();
                        this.hideFlashMessage(message);
                    });
                }

                // Auto-hide after 8 seconds (except errors)
                const messageType = message.getAttribute('data-type');
                if (messageType !== 'error' && messageType !== 'danger') {
                    setTimeout(() => {
                        if (message.parentNode) {
                            this.hideFlashMessage(message);
                        }
                    }, 8000);
                }
            });
        },

        // Hide flash message with animation
        hideFlashMessage: function(message) {
            message.classList.add('flash-message-hiding');
            setTimeout(() => {
                if (message.parentNode) {
                    message.remove();
                }
            }, this.config.animationDuration);
        },

        // Bind event listeners
        bindEvents: function() {
            const self = this;

            console.log('Binding events...');

            // Manual backup button
            const manualBackupBtn = document.getElementById('manual-backup-btn');
            if (manualBackupBtn) {
                manualBackupBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    console.log('Manual backup button clicked');
                    self.createManualBackup();
                });
                console.log('Manual backup button bound');
            } else {
                console.error('Manual backup button not found');
            }

            // Cleanup old files button
            const cleanupBtn = document.getElementById('cleanup-old-btn');
            if (cleanupBtn) {
                cleanupBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    console.log('Cleanup button clicked');
                    self.showCleanupDialog();
                });
                console.log('Cleanup button bound');
            } else {
                console.error('Cleanup button not found');
            }

            // Привязываем события к кнопкам действий напрямую
            this.bindActionButtons();

            // Также оставляем делегирование событий как резервный вариант
            document.addEventListener('click', (e) => {
                console.log('Document click detected:', e.target);

                let button = e.target;
                while (button && button !== document) {
                    if (button.hasAttribute && button.hasAttribute('data-action')) {
                        console.log('Found button with data-action via delegation:', button);
                        break;
                    }
                    button = button.parentElement;
                }

                if (!button || !button.hasAttribute('data-action')) {
                    return;
                }

                e.preventDefault();
                e.stopPropagation();

                const action = button.getAttribute('data-action');
                const filename = button.getAttribute('data-filename');

                console.log('Delegated click handler - Action:', action, 'Filename:', filename);

                if (!filename) {
                    console.error('No filename found for action:', action);
                    return;
                }

                if (action === 'download') {
                    console.log('Triggering download via delegation for:', filename);
                    self.downloadBackup(filename);
                } else if (action === 'delete') {
                    console.log('Triggering delete via delegation for:', filename);
                    self.showDeleteDialog(filename);
                }
            });
        },

        // Привязываем события к кнопкам действий напрямую
        bindActionButtons: function() {
            const self = this;

            // Ждем чтобы убедиться что DOM загружен
            setTimeout(() => {
                const downloadButtons = document.querySelectorAll('[data-action="download"]');
                const deleteButtons = document.querySelectorAll('[data-action="delete"]');

                console.log('Binding direct events to buttons:');
                console.log('Download buttons found:', downloadButtons.length);
                console.log('Delete buttons found:', deleteButtons.length);

                // Привязываем события к кнопкам скачивания
                downloadButtons.forEach((button, index) => {
                    const filename = button.getAttribute('data-filename');
                    console.log(`Binding download button ${index}:`, filename);

                    button.addEventListener('click', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        console.log('Direct download click for:', filename);
                        self.downloadBackup(filename);
                    });
                });

                // Привязываем события к кнопкам удаления
                deleteButtons.forEach((button, index) => {
                    const filename = button.getAttribute('data-filename');
                    console.log(`Binding delete button ${index}:`, filename);

                    button.addEventListener('click', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        console.log('Direct delete click for:', filename);
                        self.showDeleteDialog(filename);
                    });
                });

                // Проверяем что кнопки действительно существуют
                if (downloadButtons.length === 0 && deleteButtons.length === 0) {
                    console.warn('No action buttons found! Checking table...');
                    const table = document.querySelector('.admin-table');
                    if (table) {
                        console.log('Table exists, checking for buttons inside...');
                        const buttonsInTable = table.querySelectorAll('button[data-action]');
                        console.log('Buttons found in table:', buttonsInTable.length);
                        buttonsInTable.forEach((btn, i) => {
                            console.log(`Table button ${i}:`, {
                                action: btn.getAttribute('data-action'),
                                filename: btn.getAttribute('data-filename'),
                                html: btn.outerHTML
                            });
                        });
                    } else {
                        console.error('Table not found!');
                    }
                }
            }, 500);
        },

        // Create manual backup
        createManualBackup: function() {
            console.log('createManualBackup called');
            const button = document.getElementById('manual-backup-btn');

            if (!button) {
                console.error('Manual backup button not found');
                return;
            }

            if (button.disabled) {
                console.log('Button already disabled, operation in progress');
                return;
            }

            console.log('Starting manual backup...');
            this.toggleButtonLoading(button, true);

            fetch('/page/api/manual_backup.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin', // Добавляем передачу cookie
                body: JSON.stringify({
                    action: 'create_manual_backup'
                })
            })
            .then(response => {
                console.log('Response status:', response.status);
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('Backup API response:', data);
                this.toggleButtonLoading(button, false);

                // Перезагружаем страницу для показа FlashMessage
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            })
            .catch(error => {
                console.error('Manual backup error:', error);
                this.toggleButtonLoading(button, false);

                // Перезагружаем страницу даже при ошибке для показа FlashMessage
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            });
        },

        // Show cleanup confirmation dialog
        showCleanupDialog: function() {
            const confirmed = confirm('Are you sure you want to cleanup old backup files? This action cannot be undone.');
            if (confirmed) {
                this.cleanupOldBackups();
            }
        },

        // Cleanup old backups
        cleanupOldBackups: function() {
            const button = document.getElementById('cleanup-old-btn');

            if (button.disabled) return;

            this.toggleButtonLoading(button, true);

            fetch('/page/api/cleanup_old_backups.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin', // Добавляем передачу cookie
                body: JSON.stringify({
                    action: 'cleanup_old_backups'
                })
            })
            .then(response => response.json())
            .then(data => {
                this.toggleButtonLoading(button, false);

                // Always reload to show flash message (success or error)
                setTimeout(() => {
                    window.location.reload();
                }, this.config.pageReloadDelay);
            })
            .catch(error => {
                console.error('Cleanup error:', error);
                this.toggleButtonLoading(button, false);
                // Reload to show error message
                window.location.reload();
            });
        },

        // Show delete confirmation dialog
        showDeleteDialog: function(filename) {
            console.log('🗑️ Direct delete (no confirmation) for:', filename);
            this.deleteBackup(filename);
        },

        // Delete single backup file
        deleteBackup: function(filename) {
            console.log('🗑️ Starting delete process for:', filename);
            const button = document.querySelector(`[data-action="delete"][data-filename="${filename}"]`);

            if (button && button.disabled) return;

            if (button) {
                button.disabled = true;
                button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            }

            fetch('/page/api/backup_management.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin', // Важно: передаем cookie сессии
                body: JSON.stringify({
                    action: 'delete',
                    filename: filename
                })
            })
            .then(response => {
                console.log('📥 Delete response status:', response.status);
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('✅ Delete response data:', data);
                // Always reload to show flash message and update table
                setTimeout(() => {
                    window.location.reload();
                }, this.config.pageReloadDelay);
            })
            .catch(error => {
                console.error('❌ Delete backup error:', error);
                if (button) {
                    button.disabled = false;
                    button.innerHTML = '<i class="fas fa-trash"></i>';
                }
                // Reload to show error message
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            });
        },

        // Download backup file
        downloadBackup: function(filename) {
            console.log('Download backup called for:', filename);

            // Use the correct parameter name 'filename' instead of 'file'
            const downloadUrl = `/page/api/download_backup.php?filename=${encodeURIComponent(filename)}`;

            console.log('Download URL:', downloadUrl);

            // Create temporary download link
            const link = document.createElement('a');
            link.href = downloadUrl;
            link.download = filename;
            link.style.display = 'none';

            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);

            console.log('Download initiated for:', filename);
        },

        // Toggle button loading state
        toggleButtonLoading: function(button, loading) {
            if (!button) return;

            button.disabled = loading;

            const textSpan = button.querySelector('.btn-text');
            const loadingSpan = button.querySelector('.btn-loading');

            if (textSpan && loadingSpan) {
                if (loading) {
                    textSpan.style.display = 'none';
                    loadingSpan.style.display = 'inline-flex';
                } else {
                    textSpan.style.display = 'inline';
                    loadingSpan.style.display = 'none';
                }
            }
        },

        // Add tooltips to buttons
        addTooltips: function() {
            const tooltipButtons = document.querySelectorAll('[title]');
            tooltipButtons.forEach(button => {
                // Modern browsers handle title attribute automatically
                // Additional tooltip functionality can be added here if needed
            });
        }
    };

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => BackupMonitor.init());
    } else {
        BackupMonitor.init();
    }

    // Expose for debugging in development
    if (typeof window !== 'undefined') {
        window.BackupMonitor = BackupMonitor;
    }

})();
