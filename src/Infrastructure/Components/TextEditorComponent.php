<?php

/**
 * Text Editor Component
 * Provides easy-to-use methods for rendering text editors in templates
 *
 * @author Dmytro Hovenko
 */

declare(strict_types=1);

namespace App\Infrastructure\Components;

use App\Domain\Interfaces\TextEditorInterface;


class TextEditorComponent
{
    private TextEditorInterface $editorService;

    public function __construct(TextEditorInterface $editorService)
    {
        $this->editorService = $editorService;
    }

    /**
     * Render a comment editor (simple textarea without TinyMCE)
     */
    public function renderCommentEditor(string $fieldName = 'comment', string $content = ''): string
    {
        $html = '<div class="comment-editor-wrapper">';
        $html .= sprintf(
            '<textarea id="%s" name="%s" class="comment-textarea no-tinymce" rows="4" placeholder="Write your comment here..." required>%s</textarea>',
            htmlspecialchars($fieldName),
            htmlspecialchars($fieldName),
            htmlspecialchars($content)
        );
        $html .= '</div>';

        return $html;
    }

    /**
     * Render a news article editor
     */
    public function renderNewsEditor(string $fieldName = 'content', string $content = ''): string
    {
        $config = $this->editorService->getPresetConfig('news');
        return $this->editorService->renderEditor($fieldName, $fieldName, $content, $config);
    }

    /**
     * Render a basic editor
     */
    public function renderBasicEditor(string $fieldName = 'content', string $content = ''): string
    {
        $config = $this->editorService->getPresetConfig('basic');
        return $this->editorService->renderEditor($fieldName, $fieldName, $content, $config);
    }

    /**
     * Render an admin editor
     */
    public function renderAdminEditor(string $fieldName = 'content', string $content = ''): string
    {
        $config = $this->editorService->getPresetConfig('admin');
        return $this->editorService->renderEditor($fieldName, $fieldName, $content, $config);
    }

    /**
     * Render a custom editor with a specific config
     */
    public function renderCustomEditor(string $fieldName = 'content', string $content = '', array $customConfig = []): string
    {
        $config = $this->editorService->getPresetConfig();
        $config = array_merge($config, $customConfig);
        return $this->editorService->renderEditor($fieldName, $fieldName, $content, $config);
    }

    /**
     * Format content for safe display
     */
    public function formatContent(string $content): string
    {
        return $this->editorService->formatForDisplay($content);
    }

    /**
     * Sanitize content from editor
     */
    public function sanitizeContent(string $content): string
    {
        return $this->editorService->sanitizeContent($content);
    }

    /**
     * Get editor scripts for inclusion in the page head
     */
    public function getEditorScripts(string $preset = 'basic'): string
    {
        $config = $this->editorService->getPresetConfig($preset);
        $cdn = $this->editorService->getTinyMCECDN();
        
        return sprintf(
            '<script src="%s" referrerpolicy="origin"></script>%s',
            $cdn,
            $this->editorService->getEditorScript($config)
        );
    }
}
