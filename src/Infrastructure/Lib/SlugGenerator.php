<?php

/**
 * Slug Generator
 * Generates URL-friendly slugs from text
 *
 * @author Dmytro Hovenko
 */

declare(strict_types=1);

namespace App\Infrastructure\Lib;

class SlugGenerator
{
    /**
     * Generate a URL-friendly slug from a text
     */
    public static function generate(string $text): string
    {
        // Remove HTML tags
        $text = strip_tags($text);

        // Convert to lowercase
        $text = mb_strtolower($text, 'UTF-8');

        // Replace non-alphanumeric characters with hyphens
        $text = preg_replace('/[^\p{L}\p{N}]+/u', '-', $text);

        // Transliterate non-ASCII characters
        $text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);

        // Remove any remaining non-alphanumeric characters except hyphens
        $text = preg_replace('/[^a-z0-9\-]/', '', $text);

        // Remove multiple consecutive hyphens
        $text = preg_replace('/-+/', '-', $text);

        // Trim hyphens from start and end
        $text = trim($text, '-');

        // If empty, generate a fallback
        if (empty($text)) {
            return 'item-' . substr(md5(uniqid((string)mt_rand(), true)), 0, 8);
        }

        return $text;
    }

    /**
     * Validate if a slug is properly formatted
     */
    public static function isValid(string $slug): bool
    {
        // Check if the slug matches the pattern: lowercase letters, numbers, and hyphens
        // Cannot start or end with hyphen, no consecutive hyphens
        return preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug) === 1;
    }

}
