<?php

/**
 * Text editor interface for rich text editing
 *
 * @author Dmytro Hovenko
 */

declare(strict_types=1);

namespace App\Domain\Interfaces;


interface TextEditorInterface
{
    /**
     * Get preset configuration for editor
     */
    public function getPresetConfig(string $preset = 'default'): array;

    /**
     * Render the editor HTML
     */
    public function renderEditor(string $id, string $name, string $value = '', array $config = []): string;

    /**
     * Format text for display
     */
    public function formatForDisplay(string $content): string;

    /**
     * Get TinyMCE CDN URL
     */
    public function getTinyMCECDN(): string;

    /**
     * Get editor JavaScript
     */
    public function getEditorScript(array $config = []): string;

    /**
     * Sanitize content
     */
    public function sanitize(string $content): string;

    /**
     * Sanitize content (alias for sanitize)
     */
    public function sanitizeContent(string $content): string;
}
