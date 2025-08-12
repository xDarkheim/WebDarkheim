<?php

/**
 * Message component for displaying flash messages
 *
 * @author Dmytro Hovenko
 */

declare(strict_types=1);

namespace App\Infrastructure\Components;

use App\Infrastructure\Lib\FlashMessageService;

class MessageComponent {
    private FlashMessageService $flashService;

    public function __construct(FlashMessageService $flashService) {
        $this->flashService = $flashService;
    }

    public function renderFlashMessages(): string {
        $messages = $this->flashService->getAllMessages();
        
        if (empty($messages)) {
            return '';
        }

        $html = '<div class="flash-messages">';

        foreach ($messages as $type => $messageList) {
            foreach ($messageList as $message) {
                $cssClass = $this->getCssClass($type);
                $html .= '<div class="alert ' . $cssClass . ' alert-dismissible">';
                $html .= '<button type="button" class="close" data-dismiss="alert" aria-label="Close">';
                $html .= '<span aria-hidden="true">&times;</span>';
                $html .= '</button>';

                // Fix: Extract text from a message array and handle properly
                if (is_array($message)) {
                    $messageText = $message['text'] ?? '';
                    $isHtml = $message['is_html'] ?? false;

                    if ($isHtml) {
                        $html .= $messageText; // Already sanitized if HTML
                    } else {
                        $html .= htmlspecialchars($messageText, ENT_QUOTES, 'UTF-8');
                    }
                } else {
                    // Fallback for string messages
                    $html .= htmlspecialchars((string)$message, ENT_QUOTES, 'UTF-8');
                }

                $html .= '</div>';
            }
        }

        $html .= '</div>';

        // Clear messages after rendering
        $this->flashService->clearMessages();

        return $html;
    }

    /**
     * Render messages with proper type handling
     * @param string|array $messages Messages to render
     * @param string $additionalClass Additional CSS class
     * @return string Rendered HTML
     */
    public function render(string|array $messages, string $additionalClass = ''): string
    {
        if (empty($messages)) {
            return '';
        }

        $additionalClass = $additionalClass ? ' ' . $additionalClass : '';

        // Handle string messages
        if (is_string($messages)) {
            return '<div class="message-container' . $additionalClass . '">' . htmlspecialchars($messages) . '</div>';
        }

        // Handle array messages - remove redundant is_array check since the type is already constrained
        $html = '<div class="message-container' . $additionalClass . '">';

        foreach ($messages as $type => $messageList) {
            if (is_array($messageList)) {
                foreach ($messageList as $message) {
                    $typeClass = self::getStaticCssClass($type);
                    $html .= '<div class="message message--' . $typeClass . '">';

                    // Обрабатываем сообщение правильно - извлекаем текст
                    if (is_array($message) && isset($message['text'])) {
                        $html .= $message['is_html'] ?? false
                            ? $message['text']
                            : htmlspecialchars($message['text']);
                    } else {
                        $html .= htmlspecialchars((string)$message);
                    }
                    $html .= '</div>';
                }
            } else {
                $typeClass = self::getStaticCssClass($type);
                $html .= '<div class="message message--' . $typeClass . '">';
                $html .= htmlspecialchars($messageList);
                $html .= '</div>';
            }
        }

        $html .= '</div>';
        return $html;
    }

    private static function getStaticCssClass(string $type): string {
        return match ($type) {
            'success' => 'success',
            'error' => 'error',
            'warning' => 'warning',
            default => 'info',
        };
    }

    private function getCssClass(string $type): string {
        return match ($type) {
            'success' => 'alert-success',
            'error' => 'alert-danger',
            'warning' => 'alert-warning',
            default => 'alert-info',
        };
    }


    /**
     * Static method to display flash messages - for compatibility with news.php
     */
    public static function displayFlashMessages($flashMessageService): void {
        if ($flashMessageService && method_exists($flashMessageService, 'getAllMessages')) {
            $messages = $flashMessageService->getAllMessages();

            if (!empty($messages)) {
                echo '<div class="flash-messages">';

                foreach ($messages as $type => $messageList) {
                    foreach ($messageList as $message) {
                        // Extract message text properly
                        $messageText = '';
                        if (is_array($message) && isset($message['text'])) {
                            $messageText = $message['text'];
                        } elseif (is_string($message)) {
                            $messageText = $message;
                        }

                        if (!empty($messageText)) {
                            echo '<div class="alert alert-' . htmlspecialchars($type) . '">';
                            echo '<p>' . htmlspecialchars($messageText) . '</p>';
                            echo '</div>';
                        }
                    }
                }

                echo '</div>';

                // Clear messages after displaying
                if (method_exists($flashMessageService, 'clearMessages')) {
                    $flashMessageService->clearMessages();
                }
            }
        }
    }
}
