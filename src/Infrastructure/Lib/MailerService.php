<?php

/**
 * Mailer service class
 * Handles sending emails using PHPMailer
 *
 * @author Dmytro Hovenko
 */
namespace App\Infrastructure\Lib;

use App\Domain\Interfaces\MailerInterface;
use PDO;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;
use Exception;

class MailerService implements MailerInterface {
    private PHPMailer $mailer;
    private string $templatePath;
    private string $lastError = '';
    private array $emailSettings;

    public function __construct($emailSettings = []) {
        $this->mailer = new PHPMailer(true);
        $this->emailSettings = $emailSettings;

        // Set the template path from configuration or default
        $this->templatePath = defined('MAIL_TEMPLATE_PATH') ? MAIL_TEMPLATE_PATH : $_SERVER['DOCUMENT_ROOT'] . '/resources/views/emails';

        $this->configureMailer();
    }

    private function configureMailer(): void {
        try {
            $this->mailer->isSMTP();

            // Use settings from a database with safe fallbacks
            $smtpHost = $this->getEmailSetting('smtp_host', 'localhost');
            $smtpPort = (int) $this->getEmailSetting('smtp_port', 587);
            $smtpUsername = $this->getEmailSetting('smtp_username', '');
            $smtpPassword = $this->getEmailSetting('smtp_password', '');
            $smtpEncryption = $this->getEmailSetting('smtp_encryption', 'tls');
            $fromAddress = $this->getEmailSetting('mail_from_address', 'darkheim.universe@gmail.com');
            $fromName = $this->getEmailSetting('mail_from_name', 'Website');

            $this->mailer->Host = $smtpHost;
            $this->mailer->Port = $smtpPort;

            // Only use SMTP auth if a username is provided
            if (!empty($smtpUsername)) {
                $this->mailer->SMTPAuth = true;
                $this->mailer->Username = $smtpUsername;
                $this->mailer->Password = $smtpPassword;
            }

            // Set encryption
            if (!empty($smtpEncryption) && in_array($smtpEncryption, ['tls', 'ssl'])) {
                $this->mailer->SMTPSecure = $smtpEncryption;
            }

            // Set default from address
            $this->mailer->setFrom($fromAddress, $fromName);

            // Additional security settings
            $this->mailer->SMTPOptions = [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                ]
            ];

        } catch (PHPMailerException $e) {
            $this->lastError = "Mailer configuration error: " . $e->getMessage();
            error_log($this->lastError);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function send(string $to, string $subject, string $body, bool $isHtml = false): bool {
        // Check if email is enabled in settings
        if (!$this->isEmailEnabled()) {
            $this->lastError = "Email sending is disabled in settings";
            return false;
        }

        try {
            $this->extracted($to, $subject, $body, $isHtml);

            return $this->mailer->send();

        } catch (PHPMailerException $e) {
            $this->lastError = "Failed to send email: " . $e->getMessage();
            error_log($this->lastError);
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function sendTemplateEmail(string $to, string $subject, string $template, array $data = []): bool
    {
        try {
            $body = $this->renderTemplate($template, $data);

            if (empty($body)) {
                $this->lastError = "Failed to render email template: $template";
                return false;
            }

            return $this->send($to, $subject, $body, true);

        } catch (Exception $e) {
            $this->lastError = "Failed to send template email: " . $e->getMessage();
            error_log($this->lastError);
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function renderTemplate(string $template, array $data = []): string
    {
        $templateFile = $this->templatePath . '/' . $template . '.php';

        if (!file_exists($templateFile)) {
            $this->lastError = "Email template not found: $templateFile";
            error_log($this->lastError);
            return '';
        }

        try {
            // Extract data variables for template
            extract($data, EXTR_SKIP);

            // Start output buffering
            ob_start();

            // Include template file
            include $templateFile;

            // Get rendered content
            $content = ob_get_clean();

            return $content ?: '';

        } catch (Exception $e) {
            // Clean up the output buffer if there was an error
            if (ob_get_level()) {
                ob_end_clean();
            }

            $this->lastError = "Template rendering error: " . $e->getMessage();
            error_log($this->lastError);
            return '';
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setFrom(string $email, string $name = ''): void
    {
        try {
            $this->mailer->setFrom($email, $name);
        } catch (PHPMailerException $e) {
            $this->lastError = "Failed to set sender: " . $e->getMessage();
            error_log($this->lastError);
        }
    }

    /**
     * {}
     */
    public function setPriority(int $priority): void
    {
        // PHPMailer priority: 1 = High, 3 = Normal, 5 = Low
        $this->mailer->Priority = max(1, min(5, $priority));
    }

    /**
     * {}
     */
    public function getLastError(): string
    {
        return $this->lastError;
    }

    /**
     * {@inheritdoc}
     */
    public function addAttachment(string $path, string $name = ''): void
    {
        try {
            $this->mailer->addAttachment($path, $name);
        } catch (PHPMailerException $e) {
            $this->lastError = "Failed to add attachment: " . $e->getMessage();
            error_log($this->lastError);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isHTML(bool $isHTML = true): void
    {
        $this->mailer->isHTML($isHTML);
    }

    private function getEmailSetting(string $key, $default = null) {
        // Сначала проверяем переданные настройки
        $value = $this->emailSettings[$key] ?? null;

        // Если настройка не найдена, пытаемся загрузить напрямую из базы данных
        if ($value === null) {
            try {
                // Прямое подключение к базе данных для получения настроек email
                $pdo = new PDO(
                    "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
                    DB_USER,
                    DB_PASS,
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                    ]
                );

                $stmt = $pdo->prepare("SELECT setting_value FROM site_settings WHERE category = 'email' AND setting_key = ? LIMIT 1");
                $stmt->execute([$key]);
                $result = $stmt->fetch();

                if ($result) {
                    $value = $result['setting_value'];
                } else {
                    $value = $default;
                }
            } catch (Exception $e) {
                error_log("MailerService: Failed to get setting '$key' from database: " . $e->getMessage());
                $value = $default;
            }
        }

        // Если всё равно null, используем default
        if ($value === null) {
            $value = $default;
        }

        // Ensure email-related settings return strings, not arrays
        if (is_array($value)) {
            // If it's an array, try to get the first value or return the default
            $value = !empty($value) ? (string) reset($value) : $default;
        }

        return $value;
    }

    private function isEmailEnabled(): bool {
        return (bool) $this->getEmailSetting('mail_enabled', true);
    }

    /**
     * @param string $to
     * @param string $subject
     * @param string $body
     * @param bool $isHtml
     * @return void
     * @throws PHPMailerException
     */
    public function extracted(string $to, string $subject, string $body, bool $isHtml): void
    {
        $this->mailer->clearAddresses();
        $this->mailer->clearAttachments();

        $this->mailer->addAddress($to);
        $this->mailer->Subject = $subject;
        $this->mailer->Body = $body;
        $this->mailer->AltBody = $isHtml ? strip_tags($body) : '';
        $this->mailer->isHTML($isHtml);
    }
}
