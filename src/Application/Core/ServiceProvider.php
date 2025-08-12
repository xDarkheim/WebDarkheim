<?php

/**
 * Service Provider for centralized access to services
 * Replaces global variables
 *
 * @author Dmytro Hovenko
 */

declare(strict_types=1);

namespace App\Application\Core;


use App\Application\Services\AuthenticationService;
use App\Application\Services\DatabaseBackupService;
use App\Application\Services\NewsService;
use App\Application\Services\PasswordManager;
use App\Application\Services\SiteSettingsService;
use App\Application\Services\TinyMCEEditorService;
use App\Application\Services\UserRegistrationService;
use App\Infrastructure\Lib\CacheService;
use App\Infrastructure\Lib\Database;
use App\Infrastructure\Lib\FlashMessageService;
use App\Infrastructure\Lib\Logger;
use App\Infrastructure\Lib\MailerService;
use App\Infrastructure\Lib\TokenManager;
use App\Domain\Interfaces\{AuthenticationInterface,
    CacheInterface,
    DatabaseInterface,
    FlashMessageInterface,
    LoggerInterface,
    MailerInterface,
    PasswordManagerInterface,
    TokenManagerInterface,
    UserRegistrationInterface,
    TextEditorInterface};
use App\Infrastructure\Components\TextEditorComponent;
use Exception;
use ReflectionException;
use RuntimeException;


class ServiceProvider
{
    private static ?self $instance = null;
    private Container $container;

    private function __construct(Container $container)
    {
        $this->container = $container;
    }

    public static function getInstance(?Container $container = null): self
    {
        if (self::$instance === null) {
            if ($container === null) {
                throw new RuntimeException('Container must be provided on first call');
            }
            self::$instance = new self($container);
        }

        return self::$instance;
    }

    /**
     * Generic method to get service from a container with type checking
     * @throws ReflectionException
     */
    private function getService(string $interface): object
    {
        return $this->container->make($interface);
    }

    /**
     * @return AuthenticationInterface
     * @throws ReflectionException
     */
    public function getAuth(): AuthenticationInterface
    {
        return $this->getService(AuthenticationInterface::class);
    }

    /**
     * @return CacheInterface
     * @throws ReflectionException
     */
    public function getCache(): CacheInterface
    {
        return $this->getService(CacheInterface::class);
    }

    /**
     * @return DatabaseInterface
     * @throws ReflectionException
     */
    public function getDatabase(): DatabaseInterface
    {
        return $this->getService(DatabaseInterface::class);
    }

    /**
     * @return FlashMessageInterface
     * @throws ReflectionException
     */
    public function getFlashMessage(): FlashMessageInterface
    {
        return $this->getService(FlashMessageInterface::class);
    }

    /**
     * @return LoggerInterface
     * @throws ReflectionException
     */
    public function getLogger(): LoggerInterface
    {
        return $this->getService(LoggerInterface::class);
    }

    /**
     * @return MailerInterface
     * @throws ReflectionException
     */
    public function getMailer(): MailerInterface
    {
        return $this->getService(MailerInterface::class);
    }

    /**
     * @return TokenManagerInterface
     * @throws ReflectionException
     */
    public function getTokenManager(): TokenManagerInterface
    {
        return $this->getService(TokenManagerInterface::class);
    }

    /**
     * @return UserRegistrationInterface
     * @throws ReflectionException
     */
    public function getUserRegistration(): UserRegistrationInterface
    {
        return $this->getService(UserRegistrationInterface::class);
    }

    /**
     * Get SessionManager instance
     * @return SessionManager
     * @throws ReflectionException
     */
    public function getSessionManager(): SessionManager
    {
        return $this->getService(SessionManager::class);
    }

    /**
     * Get SiteSettingsService instance
     * @return SiteSettingsService
     * @throws ReflectionException
     */
    public function getSiteSettingsService(): SiteSettingsService
    {
        return $this->getService(SiteSettingsService::class);
    }

    /**
     * Get NewsService instance
     * @return NewsService
     * @throws ReflectionException
     */
    public function getNewsService(): NewsService
    {
        return $this->getService(NewsService::class);
    }

    /**
     * Get TextEditorComponent instance
     * @return TextEditorComponent
     * @throws ReflectionException
     */
    public function getTextEditorComponent(): TextEditorComponent
    {
        return $this->getService(TextEditorComponent::class);
    }

    /**
     * Get ConfigurationManager instance
     * @return ConfigurationManager
     * @throws ReflectionException
     */
    public function getConfigurationManager(): ConfigurationManager
    {
        return $this->getService(ConfigurationManager::class);
    }

    /**
     * Registers all core services in the container
     */
    public function registerCoreServices(): void
    {
        // Проверяем, не зарегистрированы ли уже сервисы, чтобы избежать дублирования
        if ($this->container->has(LoggerInterface::class)) {
            return;
        }

        // Logger - must be first
        $this->container->singleton(LoggerInterface::class, function () {
            return Logger::getInstance();
        });

        // Database
        $this->container->singleton(DatabaseInterface::class, function ($container) {
            return new Database($container->make(LoggerInterface::class));
        });

        // Cache
        $this->container->singleton(CacheInterface::class, function () {
            return new CacheService();
        });

        // Flash Messages
        $this->container->singleton(FlashMessageInterface::class, FlashMessageService::class);

        // Token Manager
        $this->container->singleton(TokenManagerInterface::class, function ($container) {
            return new TokenManager(
                $container->make(DatabaseInterface::class),
                $container->make(LoggerInterface::class)
            );
        });

        // Password Manager
        $this->container->singleton(PasswordManagerInterface::class, function () {
            return new PasswordManager();
        });

        // Mailer (requires settings from DB)
        $this->container->singleton(MailerInterface::class, function ($container) {
            // Избегаем циклической зависимости - используем базовые настройки, если SiteSettingsService недоступен
            try {
                $settings = $container->has('site_settings') ? $container->get('site_settings', []) : [];
                $emailSettings = [];
                if (isset($settings['email'])) {
                    foreach ($settings['email'] as $key => $setting) {
                        $emailSettings[$key] = $setting['value'] ?? null;
                    }
                }
            } catch (Exception) {
                // Если возникла ошибка при получении настроек, используем пустой массив
                $emailSettings = [];
            }
            return new MailerService($emailSettings);
        });

        // Authentication Service
        $this->container->singleton(AuthenticationInterface::class, function ($container) {
            return new AuthenticationService(
                $container->make(DatabaseInterface::class),
                $container->make(FlashMessageInterface::class),
                $container->make(PasswordManagerInterface::class),
                $container->make(LoggerInterface::class)
            );
        });

        // User Registration Service
        $this->container->singleton(UserRegistrationInterface::class, function ($container) {
            // Используем lazy loading для SiteSettingsService чтобы избежать циклических зависимостей
            $siteSettingsService = null;
            try {
                $siteSettingsService = $container->make(SiteSettingsService::class);
            } catch (Exception $e) {
                // Если SiteSettingsService недоступен, передаем null
                $container->make(LoggerInterface::class)->warning('SiteSettingsService недоступен при создании UserRegistrationService', ['error' => $e->getMessage()]);
            }

            return new UserRegistrationService(
                $container->make(DatabaseInterface::class),
                $container->make(FlashMessageInterface::class),
                $container->make(MailerInterface::class),
                $container->make(TokenManagerInterface::class),
                $container->make(PasswordManagerInterface::class),
                $container->make(LoggerInterface::class),
                $siteSettingsService
            );
        });

        // Site Settings Service
        $this->container->singleton(SiteSettingsService::class, function ($container) {
            return new SiteSettingsService(
                $container->make(DatabaseInterface::class)
            );
        });

        // News Service
        $this->container->singleton(NewsService::class, function ($container) {
            return new NewsService(
                $container->make(DatabaseInterface::class)
            );
        });

        // Configuration Manager
        $this->container->singleton(ConfigurationManager::class, function ($container) {
            return new ConfigurationManager(
                $container->make(DatabaseInterface::class),
                $container->make(CacheInterface::class),
                $container->make(LoggerInterface::class)
            );
        });

        // Validator
        $this->container->singleton(Validator::class, function () {
            return new Validator();
        });

        // Text Editor Services
        $this->container->singleton(TextEditorInterface::class, function () {
            return new TinyMCEEditorService();
        });

        $this->container->singleton(TextEditorComponent::class, function ($container) {
            return new TextEditorComponent(
                $container->make(TextEditorInterface::class)
            );
        });

        // Database Backup Service
        $this->container->singleton(DatabaseBackupService::class, function ($container) {
            return new DatabaseBackupService(
                $container->make(LoggerInterface::class)
            );
        });

        // Session Manager
        $this->container->singleton(SessionManager::class, function ($container) {
            return SessionManager::getInstance(
                $container->make(LoggerInterface::class),
                [],
                $container->make(ConfigurationManager::class),
                $container->make(TokenManagerInterface::class)
            );
        });
    }
}
