<?php
// Страница комментариев к проекту клиента (личный кабинет)
/** @var array $projects */
/** @var array $commentsByProject */
?>
<div class="client-portfolio-comments">
    <h2>Комментарии к проектам</h2>
    <?php if (empty($projects)): ?>
        <p>Нет проектов для отображения комментариев.</p>
    <?php else: ?>
        <?php foreach ($projects as $project): ?>
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
                <form method="post" action="/api/comments/create.php">
                    <input type="hidden" name="project_id" value="<?= htmlspecialchars($project['id']) ?>">
                    <textarea name="text" placeholder="Ваш комментарий" required></textarea>
                    <button type="submit">Добавить комментарий</button>
                </form>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

