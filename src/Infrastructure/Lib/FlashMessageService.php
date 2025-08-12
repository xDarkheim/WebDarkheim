<?php

/**
 * Flash message service following Single Responsibility Principle
 * Handles session-based temporary messages with XSS protection
 *
 * @author Dmytro Hovenko
 */

declare(strict_types=1);

namespace App\Infrastructure\Lib;

use App\Domain\Interfaces\FlashMessageInterface;
use InvalidArgumentException;


class FlashMessageService implements FlashMessageInterface
{
    private const SESSION_KEY = 'flash_messages';
    private const ALLOWED_TYPES = ['success', 'error', 'warning', 'info'];

    public function __construct()
    {
        $this->initializeSession();
    }

    /**
     * {@inheritdoc}
     */
    public function addSuccess(string $message, bool $isHtml = false): void
    {
        $this->addMessage('success', $message, $isHtml);
    }

    /**
     * {@inheritdoc}
     */
    public function addError(string $message, bool $isHtml = false): void
    {
        $this->addMessage('error', $message, $isHtml);
    }

    /**
     * {@inheritdoc}
     */
    public function addWarning(string $message, bool $isHtml = false): void
    {
        $this->addMessage('warning', $message, $isHtml);
    }

    /**
     * {@inheritdoc}
     */
    public function addInfo(string $message, bool $isHtml = false): void
    {
        $this->addMessage('info', $message, $isHtml);
    }

    /**
     * {@inheritdoc}
     */
    public function getMessages(string $type = ''): array
    {
        $allMessages = $_SESSION[self::SESSION_KEY] ?? [];

        if (empty($type)) {
            // Возвращаем все сообщения
            $this->clearMessages();
            return $allMessages;
        }

        // Возвращаем сообщения определенного типа
        $typeMessages = $allMessages[$type] ?? [];
        $this->clearMessages($type);
        return $typeMessages;
    }

    /**
     * Get all messages without clearing them
     */
    public function getAllMessages(): array
    {
        return $_SESSION[self::SESSION_KEY] ?? [];
    }

    /**
     * {@inheritdoc}
     */
    public function clearMessages(string $type = ''): void
    {
        if (empty($type)) {
            // Очищаем все сообщения
            unset($_SESSION[self::SESSION_KEY]);
        } else {
            // Очищаем сообщения определенного типа
            if (isset($_SESSION[self::SESSION_KEY][$type])) {
                unset($_SESSION[self::SESSION_KEY][$type]);

                // Удаляем ключ сессии, если больше нет сообщений
                if (empty($_SESSION[self::SESSION_KEY])) {
                    unset($_SESSION[self::SESSION_KEY]);
                }
            }
        }
    }

    /**
     * {}
     */
    public function cleanOldMessages(): void
    {
        if (!isset($_SESSION[self::SESSION_KEY])) {
            return;
        }

        $currentTime = time();
        $maxAge = 3600; // 1 hour

        foreach ($_SESSION[self::SESSION_KEY] as $type => $messages) {
            $_SESSION[self::SESSION_KEY][$type] = array_filter($messages, function($message) use ($currentTime, $maxAge) {
                return ($currentTime - ($message['timestamp'] ?? 0)) <= $maxAge;
            });

            // Remove empty type arrays
            if (empty($_SESSION[self::SESSION_KEY][$type])) {
                unset($_SESSION[self::SESSION_KEY][$type]);
            }
        }

        // Remove session key if no messages left
        if (empty($_SESSION[self::SESSION_KEY])) {
            unset($_SESSION[self::SESSION_KEY]);
        }
    }

    /**
     * Get messages of a specific type without clearing
     */
    public function peekMessages(string $type = ''): array
    {
        $allMessages = $_SESSION[self::SESSION_KEY] ?? [];

        if (empty($type)) {
            return $allMessages;
        }

        return $allMessages[$type] ?? [];
    }

    /**
     * {@inheritdoc}
     */
    public function hasMessages(string $type = ''): bool
    {
        $messages = $this->peekMessages($type);
        return !empty($messages);
    }

    /**
     * {@inheritdoc}
     */
    public function display(string $type = ''): string
    {
        $messages = $this->getMessages($type);

        if (empty($messages)) {
            return '';
        }

        $html = '';

        if (empty($type)) {
            // Отображаем все типы сообщений
            foreach ($messages as $messageType => $typeMessages) {
                foreach ($typeMessages as $message) {
                    $html .= $this->formatMessage($messageType, $message);
                }
            }
        } else {
            // Отображаем сообщения определенного типа
            foreach ($messages as $message) {
                $html .= $this->formatMessage($type, $message);
            }
        }

        return $html;
    }

    /**
     * Format a message for HTML display
     */
    private function formatMessage(string $type, array $message): string
    {
        $cssClass = 'alert alert-' . $this->getBootstrapClass($type);
        $text = $message['text'] ?? '';
        $id = $message['id'] ?? '';

        return sprintf(
            '<div class="%s" role="alert" data-message-id="%s">%s</div>',
            htmlspecialchars($cssClass, ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($id, ENT_QUOTES, 'UTF-8'),
            $text
        );
    }

    /**
     * Get Bootstrap CSS class for a message type
     */
    private function getBootstrapClass(string $type): string
    {
        return match($type) {
            'success' => 'success',
            'error' => 'danger',
            'warning' => 'warning',
            'info' => 'info',
            default => 'secondary'
        };
    }

    /**
     * Add a message to a session
     */
    private function addMessage(string $type, string $message, bool $isHtml = false): void
    {
        if (!in_array($type, self::ALLOWED_TYPES, true)) {
            throw new InvalidArgumentException("Invalid message type: $type");
        }

        if (empty(trim($message))) {
            return;
        }

        $this->initializeSession();

        if (!isset($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = [];
        }

        if (!isset($_SESSION[self::SESSION_KEY][$type])) {
            $_SESSION[self::SESSION_KEY][$type] = [];
        }

        // Sanitize a message if it's not HTML
        $sanitizedMessage = $isHtml ? $message : htmlspecialchars($message, ENT_QUOTES, 'UTF-8');

        $_SESSION[self::SESSION_KEY][$type][] = [
            'text' => $sanitizedMessage,
            'is_html' => $isHtml,
            'timestamp' => time(),
            'id' => uniqid('msg_', true)
        ];

        // Limit messages per type to prevent session bloat
        $this->limitMessages($type);
    }

    /**
     * Initialize a session if not already started
     */
    private function initializeSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Limit number of messages per type
     */
    private function limitMessages(string $type): void
    {
        if (!isset($_SESSION[self::SESSION_KEY][$type])) {
            return;
        }

        $messages = $_SESSION[self::SESSION_KEY][$type];

        if (count($messages) > 10) {
            // Keep only the most recent messages
            $_SESSION[self::SESSION_KEY][$type] = array_slice($messages, -(10));
        }
    }
}