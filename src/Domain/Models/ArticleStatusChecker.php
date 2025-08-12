<?php

/**
 * Article status checker
 * Checks the status of an article
 *
 * @author Dmytro Hovenko
 */

declare(strict_types=1);

namespace App\Domain\Models;

class ArticleStatusChecker
{
    private Article $article;

    public function __construct(Article $article)
    {
        $this->article = $article;
    }

    /**
     * Checks if the article is published
     */
    public function isPublished(): bool
    {
        return $this->article->status === 'published';
    }

    /**
     * Checks if the article is rejected
     */
    public function isRejected(): bool
    {
        return $this->article->status === 'rejected';
    }

    /**
     * Checks if the article is a draft
     */
    public function isDraft(): bool
    {
        return $this->article->status === 'draft';
    }

    /**
     * Checks if the article is pending review
     */
    public function isPendingReview(): bool
    {
        return $this->article->status === 'pending_review';
    }
}