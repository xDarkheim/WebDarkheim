<?php

/**
 * TinyMCE Text Editor Service
 * Provides rich text editing functionality using TinyMCE
 */

declare(strict_types=1);

namespace App\Application\Services;

use App\Domain\Interfaces\TextEditorInterface;

class TinyMCEEditorService implements TextEditorInterface
{
    private array $defaultConfig;
    private array $allowedTags;

    public function __construct()
    {
        // Allowed HTML tags for sanitization
        $this->allowedTags = [
            'p', 'br', 'strong', 'em', 'u', 's', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
            'ul', 'ol', 'li', 'a', 'img', 'blockquote', 'pre', 'code', 'span',
            'table', 'thead', 'tbody', 'tr', 'th', 'td', 'caption',
            'div', 'hr', 'sub', 'sup', 'mark', 'del', 'ins'
        ];

        // Default TinyMCE configuration
        $this->defaultConfig = [
            'height' => 300,
            'menubar' => false,
            'plugins' => [
                'advlist', 'autolink', 'lists', 'link', 'image', 'charmap',
                'preview', 'anchor', 'searchreplace', 'visualblocks', 'code',
                'fullscreen', 'insertdatetime', 'media', 'table', 'help', 'wordcount',
                'emoticons', 'template', 'paste', 'textcolor', 'colorpicker'
            ],
            'toolbar' => 'undo redo | blocks fontsize | ' .
                        'bold italic underline strikethrough | forecolor backcolor | ' .
                        'alignleft aligncenter alignright alignjustify | ' .
                        'bullist numlist outdent indent | ' .
                        'link image media table | insertdatetime emoticons | ' .
                        'visualblocks code fullscreen | removeformat help',
            'content_style' => 'body { font-family: -apple-system, BlinkMacSystemFont, ' .
                              'San Francisco, Segoe UI, Roboto, Helvetica Neue, sans-serif; font-size: 14px; ' .
                              'line-height: 1.6; color: #333; }',
            'branding' => false,
            'promotion' => false,
            'block_formats' => 'Paragraph=p; Heading 1=h1; Heading 2=h2; Heading 3=h3; Heading 4=h4; Heading 5=h5; Heading 6=h6; Preformatted=pre',
            'fontsize_formats' => '8pt 10pt 12pt 14pt 16pt 18pt 24pt 36pt 48pt',
            'paste_as_text' => true,
            'paste_block_drop' => false,
            'browser_spellcheck' => true,
            'contextmenu' => 'link image table',
            'image_advtab' => true,
            'image_caption' => true,
            'quickbars_selection_toolbar' => 'bold italic | quicklink h2 h3 blockquote',
            'quickbars_insert_toolbar' => 'quickimage quicktable',
            'toolbar_mode' => 'sliding'
        ];
    }

    /**
     * Render the editor HTML for a given element
     */
    public function renderEditor(string $id, string $name, string $value = '', array $config = []): string
    {

        $html = '<div class="text-editor-wrapper">';
        $html .= sprintf(
            '<textarea id="%s" name="%s" class="tinymce-editor">%s</textarea>',
            htmlspecialchars($id),
            htmlspecialchars($name),
            htmlspecialchars($value)
        );
        $html .= '</div>';

        return $html;
    }

    /**
     * Render the editor for a given element ID and content
     */
    public function render(string $elementId, string $content = ''): string
    {

        $html = '<div class="text-editor-wrapper">';
        $html .= sprintf(
            '<textarea id="%s" name="%s" class="tinymce-editor">%s</textarea>',
            htmlspecialchars($elementId),
            htmlspecialchars($elementId),
            htmlspecialchars($content)
        );
        $html .= '</div>';

        return $html;
    }

    /**
     * Get the TinyMCE initialization script for all editors
     */
    public function getEditorScript(array $config = []): string
    {
        $config = array_merge($this->defaultConfig, $config);
        $configJson = json_encode($config, JSON_UNESCAPED_SLASHES);

        return sprintf(
            '<script>
                // Clear any previous TinyMCE initializations
                let tinymce;
                if (typeof tinymce !== "undefined") {
                    tinymce.remove(".tinymce-editor");
                }
                
                document.addEventListener("DOMContentLoaded", function() {
                    console.log("DOM loaded, checking TinyMCE...");
                    
                    if (typeof tinymce !== "undefined") {
                        console.log("TinyMCE library found, initializing...");
                        
                        // Check for editor elements
                        const editors = document.querySelectorAll(".tinymce-editor");
                        console.log("Found " + editors.length + " editor elements");
                        
                        if (editors.length > 0) {
                            tinymce.init(Object.assign(%s, {
                                selector: ".tinymce-editor",
                                init_instance_callback: function(editor) {
                                    console.log("TinyMCE editor initialized successfully:", editor.id);
                                },
                                setup: function(editor) {
                                    editor.on("init", function() {
                                        console.log("Editor " + editor.id + " is ready");
                                    });
                                    editor.on("change", function() {
                                        editor.save();
                                    });
                                }
                            }))
                        } else {
                            console.error("No textarea elements with class .tinymce-editor found");
                        }
                    } else {
                        console.error("TinyMCE library not loaded. Check CDN connection.");
                    }
                });
            </script>',
            $configJson
        );
    }

    /**
     * Get the current TinyMCE configuration
     */
    public function getConfig(): array
    {
        return $this->defaultConfig;
    }

    /**
     * Set TinyMCE configuration
     */
    public function setConfig(array $config): void
    {
        $this->defaultConfig = array_merge($this->defaultConfig, $config);
    }

    /**
     * Validate editor content for length and security
     */
    public function validateContent(string $content): bool
    {
        // Check content length (not too long)
        if (strlen($content) > 65000) {
            return false;
        }

        // Check for potentially dangerous content
        $dangerousPatterns = [
            '/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi',
            '/<iframe\b[^<]*(?:(?!<\/iframe>)<[^<]*)*<\/iframe>/mi',
            '/javascript\s*:/i',
            '/on\w+\s*=/i',
            '/<object\b[^<]*(?:(?!<\/object>)<[^<]*)*<\/object>/mi',
            '/<embed\b[^>]*>/i'
        ];

        foreach ($dangerousPatterns as $pattern) {
            if (preg_match($pattern, $content)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get TinyMCE initialization script for a specific element
     */
    public function getInitScript(string $elementId, array $config = []): string
    {
        $mergedConfig = array_merge($this->defaultConfig, $config);
        $configJson = json_encode($mergedConfig, JSON_UNESCAPED_SLASHES);

        return sprintf(
            '<script>
                document.addEventListener("DOMContentLoaded", function() {
                   let tinymce;
                    if (typeof tinymce !== "undefined") {
                        // Remove any existing instance
                        tinymce.remove("#%s");
                        
                        // Initialize TinyMCE for specific element
                        tinymce.init(Object.assign(%s, {
                            selector: "#%s",
                            init_instance_callback: function(editor) {
                                console.log("TinyMCE editor initialized:", editor.id);
                            },
                            setup: function(editor) {
                                editor.on("change", function() {
                                    editor.save();
                                });
                            }
                        }))
                    } else {
                        console.error("TinyMCE library not loaded");
                    }
                });
            </script>',
            $elementId,
            $configJson,
            $elementId
        );
    }

    /**
     * Sanitize editor content by removing dangerous tags and attributes
     */
    public function sanitizeContent(string $content): string
    {
        // Basic HTML sanitization
        $content = strip_tags($content, '<' . implode('><', $this->allowedTags) . '>');

        // Remove potentially dangerous attributes
        $content = preg_replace('/(<[^>]+)(on\w+\s*=\s*["\'][^"\']*["\'])/i', '$1', $content);
        $content = preg_replace('/(<[^>]+)(javascript\s*:)/i', '$1', $content);

        return trim($content);
    }

    /**
     * {@inheritdoc}
     * Sanitize content for safe output
     */
    public function sanitize(string $content): string
    {
        return $this->sanitizeContent($content);
    }

    /**
     * Format content for display (wrap in paragraph if needed)
     */
    public function formatForDisplay(string $content): string
    {
        $content = $this->sanitizeContent($content);

        // Convert line breaks to paragraphs if not present
        if (!str_contains($content, '<p>') && !empty(trim($content))) {
            $content = '<p>' . nl2br($content) . '</p>';
        }

        return $content;
    }

    /**
     * Get allowed HTML tags for sanitization
     */
    public function getAllowedTags(): array
    {
        return $this->allowedTags;
    }

    /**
     * Get preset TinyMCE configurations for different use cases
     */
    public function getPresetConfig(string $preset = 'default'): array
    {
        $presets = [
            'basic' => [
                'height' => 200,
                'toolbar' => 'bold italic underline | bullist numlist | removeformat',
                'plugins' => ['lists']
            ],
            'comment' => [
                'height' => 150,
                'menubar' => false,
                'statusbar' => false,
                'toolbar' => 'bold italic underline | link | bullist numlist | removeformat',
                'plugins' => ['link', 'lists'],
                'content_style' => 'body { font-family: -apple-system, BlinkMacSystemFont, San Francisco, Segoe UI, Roboto, Helvetica Neue, sans-serif; font-size: 14px; line-height: 1.6; color: #333; margin: 8px; }',
                'branding' => false,
                'promotion' => false,
                'paste_as_text' => true,
                'browser_spellcheck' => true,
                'resize' => false,
                'elementpath' => false,
                'setup' => 'function(editor) { editor.on("init", function() { console.log("Comment editor initialized: " + editor.id); }); }'
            ],
            'news' => [
                'height' => 450,
                'plugins' => [
                    'advlist', 'autolink', 'lists', 'link', 'image', 'charmap',
                    'preview', 'anchor', 'searchreplace', 'visualblocks', 'code',
                    'fullscreen', 'insertdatetime', 'media', 'table', 'help', 'wordcount',
                    'emoticons', 'paste', 'textcolor', 'colorpicker'
                ],
                'toolbar' => 'undo redo | blocks fontsize | ' .
                            'bold italic underline strikethrough | forecolor backcolor | ' .
                            'alignleft aligncenter alignright alignjustify | ' .
                            'bullist numlist outdent indent | ' .
                            'link image media table | blockquote | ' .
                            'visualblocks code fullscreen | removeformat help',
                'block_formats' => 'Paragraph=p; Heading 1=h1; Heading 2=h2; Heading 3=h3; Heading 4=h4; Quote=blockquote; Preformatted=pre',
                'fontsize_formats' => '12pt 14pt 16pt 18pt 24pt 36pt',
                'quickbars_selection_toolbar' => 'bold italic | quicklink h2 h3 blockquote',
                'quickbars_insert_toolbar' => 'quickimage quicktable',
                'toolbar_mode' => 'sliding',
                'image_advtab' => true,
                'image_caption' => true,
                'paste_as_text' => false,
                'paste_block_drop' => true
            ],
            'admin' => $this->defaultConfig
        ];

        return $presets[$preset] ?? $this->defaultConfig;
    }

    /**
     * Get TinyMCE CDN URL or fallback to a local version
     */
    public function getTinyMCECDN(): string
    {
        // Use TinyMCE CDN or local version
        $apiKey = defined('TINYMCE_API_KEY') ? TINYMCE_API_KEY : 'no-api-key';

        if ($apiKey !== 'no-api-key') {
            return "https://cdn.tiny.cloud/1/$apiKey/tinymce/7/tinymce.min.js";
        }

        // Fallback to local TinyMCE if available
        return '/themes/default/js/tinymce/tinymce.min.js';
    }
}
