<?php
/**
 * Article Engagement Component - лайки, теги и взаимодействие
 */
?>

<div class="article-engagement">
    <div class="engagement-section">
        <h4 class="engagement-title">Was this article helpful?</h4>
        <div class="rating-buttons">
            <button class="rating-btn" data-type="like" title="This was helpful">
                <i class="fas fa-thumbs-up"></i>
                <span id="likeCount"><?php echo rand(12, 85); ?></span>
            </button>
            <button class="rating-btn" data-type="dislike" title="This was not helpful">
                <i class="fas fa-thumbs-down"></i>
                <span id="dislikeCount"><?php echo rand(1, 8); ?></span>
            </button>
        </div>
    </div>

    <!-- Article Tags -->
    <div class="article-tags">
        <h4 class="engagement-title">Tags:</h4>
        <div class="tags-list">
            <?php
            // Generate some example tags based on content
            $sampleTags = ['Web Development', 'Technology', 'Programming', 'News', 'Updates'];
            $randomTags = array_slice($sampleTags, 0, rand(2, 4));
            foreach ($randomTags as $tag) :
            ?>
                <span class="tag-item"><?php echo htmlspecialchars($tag); ?></span>
            <?php endforeach; ?>
        </div>
    </div>
</div>
