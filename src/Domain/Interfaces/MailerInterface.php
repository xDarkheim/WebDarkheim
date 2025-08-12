<?php

/**
 * Mailer interface for email operations
 *
 * @author Dmytro Hovenko
 */

declare(strict_types=1);

namespace App\Domain\Interfaces;


interface MailerInterface
{
    /**
     * Send a simple email
     */
    public function send(string $to, string $subject, string $body): bool;

    /**
     * Send email with template
     */
    public function sendTemplateEmail(string $to, string $subject, string $template, array $data = []): bool;

    /**
     * Render email template
     */
    public function renderTemplate(string $template, array $data = []): string;

    /**
     * Set email sender
     */
    public function setFrom(string $email, string $name = ''): void;

    /**
     * Add attachment to email
     */
    public function addAttachment(string $path, string $name = ''): void;

    /**
     * Set email as HTML
     */
    public function isHTML(bool $isHTML = true): void;
}
