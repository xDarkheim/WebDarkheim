<?php
// Публичная страница комментариев к проекту клиента
/** @var array $projects */
/** @var array $commentsByProject */
?>
<div class="public-portfolio-comments">
    <h2>Комментарии к проектам клиента</h2>
    <?php if (empty($projects)): ?>
        <p>Нет проектов для отображения комментариев.</p>
    <?php else: ?>
        <?php foreach ($projects as $project): ?>
            <?php if (($project['visibility'] ?? 'private') === 'public' && ($project['status'] ?? 'draft') === 'published'): ?>
            <div class="project-comments-block">
                <h3><?= htmlspecialchars($project['title'] ?? '') ?></h3>
                <?php $comments = $commentsByProject[$project['id']] ?? []; ?>
                <?php if (empty($comments)): ?>
                    <p>Нет комментариев.</p>
                <?php else: ?>
                    <ul class="project-comments-list">
                        <?php foreach ($comments as $comment): ?>
                            <li>
                                <div class="comment-author">Автор: <?= htmlspecialchars($comment['author'] ?? 'Гость') ?></div>
                                <div class="comment-text"><?= nl2br(htmlspecialchars($comment['text'] ?? '')) ?></div>
                                <div class="comment-date"><?= htmlspecialchars($comment['created_at'] ?? '') ?></div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

