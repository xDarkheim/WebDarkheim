#!/usr/bin/env php
<?php
/**
 * Автономный скрипт для автоматических бэкапов
 * Запускается через cron без участия пользователя
 */

declare(strict_types=1);

// Загружаем автозагрузчик и конфигурацию
require_once dirname(__DIR__) . '/includes/bootstrap.php';

use App\Application\Controllers\DatabaseBackupController;
use App\Application\Core\ServiceProvider;

class AutoBackupSystem
{
    private DatabaseBackupController $backupController;
    private array $config;
    private $logger;

    public function __construct()
    {
        // Загружаем конфигурацию
        $this->config = include dirname(__DIR__) . '/config/backup_config.php';

        $services = ServiceProvider::getInstance();
        $this->logger = $services->getLogger();
        $this->backupController = new DatabaseBackupController();

        // Устанавливаем лимиты производительности
        ini_set('memory_limit', $this->config['performance']['memory_limit']);
        set_time_limit($this->config['performance']['timeout']);
    }

    /**
     * Главный метод для автоматического запуска
     */
    public function run(): void
    {
        $this->logger->info('Starting automatic backup system');

        try {
            // Проверяем состояние системы
            $this->checkSystemHealth();

            // Определяем тип бэкапа в зависимости от времени
            $backupType = $this->determineBackupType();

            // Создаем бэкап
            $result = $this->createBackup($backupType);

            if ($result['success']) {
                // Проверяем целостность если включено
                if ($this->config['advanced']['verify_backup']) {
                    $this->verifyBackup($result['filename']);
                }

                // Создаем контрольную сумму если включено
                if ($this->config['advanced']['create_checksum']) {
                    $this->createChecksum($result['filename']);
                }

                // Очищаем старые бэкапы
                $this->cleanupOldBackups();

                $this->logger->info('Automatic backup completed successfully', [
                    'type' => $backupType,
                    'filename' => $result['filename'],
                    'size' => $result['size']
                ]);

                // Отправляем уведомление об успехе если включено
                if ($this->config['notifications']['email_on_success']) {
                    $this->sendNotification('success', $result);
                }

            } else {
                throw new Exception('Backup failed: ' . $result['error']);
            }

        } catch (Exception $e) {
            $this->logger->error('Automatic backup failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Отправляем уведомление об ошибке
            if ($this->config['notifications']['email_on_failure']) {
                $this->sendNotification('failure', ['error' => $e->getMessage()]);
            }

            exit(1);
        }
    }

    /**
     * Проверяет состояние системы перед бэкапом
     */
    private function checkSystemHealth(): void
    {
        // Проверяем место на диске
        $backupDir = ROOT_PATH . DS . 'storage' . DS . 'backups';
        $freeSpace = disk_free_space($backupDir);
        $requiredSpace = 100 * 1024 * 1024; // 100MB минимум

        if ($freeSpace < $requiredSpace) {
            throw new Exception('Insufficient disk space for backup');
        }

        // Проверяем доступность базы данных
        try {
            $services = ServiceProvider::getInstance();
            $database = $services->getDatabase();
            $database->prepare("SELECT 1")->execute();
        } catch (Exception $e) {
            throw new Exception('Database connection failed: ' . $e->getMessage());
        }
    }

    /**
     * Определяет тип бэкапа в зависимости от времени
     */
    private function determineBackupType(): string
    {
        $hour = (int)date('H');
        $dayOfWeek = (int)date('w'); // 0 = Sunday
        $dayOfMonth = (int)date('j');

        // Полный бэкап в воскресенье или в первый день месяца
        if ($dayOfWeek === 0 || $dayOfMonth === 1) {
            return 'full';
        }

        // Структурный бэкап каждые 6 часов если включено
        if ($this->config['schedule']['hourly_structure'] && $hour % 6 === 0) {
            return 'structure';
        }

        // По умолчанию полный бэкап
        return 'full';
    }

    /**
     * Создает бэкап указанного типа
     */
    private function createBackup(string $type): array
    {
        $this->logger->info("Creating {$type} backup");

        if ($type === 'structure') {
            return $this->backupController->createStructureBackup();
        } else {
            return $this->backupController->createFullBackup();
        }
    }

    /**
     * Проверяет целостность созданного бэкапа
     */
    private function verifyBackup(string $filename): void
    {
        $backupPath = ROOT_PATH . DS . 'storage' . DS . 'backups' . DS . $filename;

        // Проверяем, что файл существует и не пустой
        if (!file_exists($backupPath)) {
            throw new Exception("Backup file not found: {$filename}");
        }

        $fileSize = filesize($backupPath);
        if ($fileSize < 1024) { // Минимум 1KB
            throw new Exception("Backup file too small: {$fileSize} bytes");
        }

        // Проверяем, что файл можно прочитать
        $handle = gzopen($backupPath, 'r');
        if (!$handle) {
            throw new Exception("Cannot read backup file: {$filename}");
        }

        $firstLine = gzgets($handle);
        gzclose($handle);

        if (!str_contains($firstLine, 'Database Backup')) {
            throw new Exception("Invalid backup file format: {$filename}");
        }

        $this->logger->info("Backup verification successful", ['filename' => $filename]);
    }

    /**
     * Создает контрольную сумму для бэкапа
     */
    private function createChecksum(string $filename): void
    {
        $backupPath = ROOT_PATH . DS . 'storage' . DS . 'backups' . DS . $filename;
        $checksumPath = $backupPath . '.md5';

        $checksum = md5_file($backupPath);
        file_put_contents($checksumPath, "{$checksum}  {$filename}\n");

        $this->logger->info("Checksum created", [
            'filename' => $filename,
            'checksum' => $checksum
        ]);
    }

    /**
     * Очищает старые бэкапы согласно политике хранения
     */
    private function cleanupOldBackups(): void
    {
        $backups = $this->backupController->getBackupsList();
        $storageConfig = $this->config['storage'];

        $toDelete = [];

        // Удаляем по количеству
        if (count($backups) > $storageConfig['max_files']) {
            $excess = array_slice($backups, $storageConfig['max_files']);
            $toDelete = array_merge($toDelete, $excess);
        }

        // Удаляем по возрасту
        $maxAge = $storageConfig['retention_days'] * 24 * 60 * 60; // в секундах
        foreach ($backups as $backup) {
            if ((time() - $backup['created_at']) > $maxAge) {
                $toDelete[] = $backup;
            }
        }

        // Удаляем дубликаты и выполняем удаление
        $toDelete = array_unique($toDelete, SORT_REGULAR);

        foreach ($toDelete as $backup) {
            if ($this->backupController->deleteBackup($backup['filename'])) {
                // Удаляем также контрольную сумму если есть
                $checksumPath = ROOT_PATH . DS . 'storage' . DS . 'backups' . DS . $backup['filename'] . '.md5';
                if (file_exists($checksumPath)) {
                    unlink($checksumPath);
                }
            }
        }

        if (!empty($toDelete)) {
            $this->logger->info("Cleaned up old backups", [
                'deleted_count' => count($toDelete)
            ]);
        }
    }

    /**
     * Отправляет уведомление о результате бэкапа
     */
    private function sendNotification(string $type, array $data): void
    {
        try {
            $services = ServiceProvider::getInstance();
            $mailer = $services->getMailer();
            $email = $this->config['notifications']['email_address'];

            if ($type === 'success') {
                $subject = "Database Backup Successful - " . date('Y-m-d H:i:s');

                // Подготавливаем данные для шаблона успешного backup
                $templateData = [
                    'backupFile' => $data['filename'] ?? 'unknown_backup.sql.gz',
                    'backupSize' => isset($data['size']) ? $this->formatBytes($data['size']) : 'Unknown',
                    'backupTime' => date('Y-m-d H:i:s'),
                    'backupType' => 'Automatic Full',
                    'siteName' => 'Darkheim Development Studio',
                    'siteUrl' => 'https://darkheim.net'
                ];

                // Используем встроенный метод MailerService для отправки шаблонного email
                $mailer->sendTemplateEmail($email, $subject, 'backup_success', $templateData);

            } else {
                $subject = "Database Backup Failed - " . date('Y-m-d H:i:s');

                // Подготавливаем данные для шаблона неудачного backup
                $templateData = [
                    'errorMessage' => $data['error'] ?? 'Unknown error occurred',
                    'backupTime' => date('Y-m-d H:i:s'),
                    'backupType' => 'Automatic Full',
                    'siteName' => 'Darkheim Development Studio',
                    'siteUrl' => 'https://darkheim.net'
                ];

                // Используем встроенный метод MailerService для отправки шаблонного email
                $mailer->sendTemplateEmail($email, $subject, 'backup_failure', $templateData);
            }

        } catch (Exception $e) {
            $this->logger->error('Failed to send backup notification', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Рендерит email шаблон с данными
     */
    private function renderEmailTemplate(string $template, array $data): string
    {
        $templatePath = ROOT_PATH . DS . 'resources' . DS . 'views' . DS . 'emails' . DS . $template . '.php';

        if (!file_exists($templatePath)) {
            // Fallback к простому тексту если шаблон не найден
            if ($template === 'backup_success') {
                return "Database backup completed successfully.\n\nFilename: {$data['backupFile']}\nSize: {$data['backupSize']}\nCreated: {$data['backupTime']}";
            } else {
                return "Database backup failed.\n\nError: {$data['errorMessage']}\nTime: {$data['backupTime']}";
            }
        }

        // Включаем буферизацию вывода для захвата HTML
        ob_start();

        try {
            // Включаем шаблон с доступными данными
            include $templatePath;
            $html = ob_get_contents();
        } catch (Exception $e) {
            $html = "Error rendering email template: " . $e->getMessage();
        } finally {
            ob_end_clean();
        }

        return $html;
    }

    /**
     * Форматирует размер файла
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        return round($bytes / (1024 ** $pow), 2) . ' ' . $units[$pow];
    }
}

// Запуск автоматической системы
try {
    $autoBackup = new AutoBackupSystem();
    $autoBackup->run();
    echo "Automatic backup completed successfully\n";
} catch (Exception $e) {
    echo "Automatic backup failed: " . $e->getMessage() . "\n";
    exit(1);
}
