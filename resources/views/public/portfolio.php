<?php
// Публичная сетка портфолио клиента
/** @var array $projects */
?>
<div class="portfolio-grid public-portfolio-grid">
    <?php if (empty($projects)): ?>
        <p>Портфолио этого клиента пока пусто.</p>
    <?php else: ?>
        <div class="portfolio-list">
            <?php foreach ($projects as $project): ?>
                <?php if (($project['visibility'] ?? 'private') === 'public' && ($project['status'] ?? 'draft') === 'published'): ?>
                <div class="portfolio-item">
                    <h3><?= htmlspecialchars($project['title'] ?? '') ?></h3>
                    <p><?= htmlspecialchars($project['description'] ?? '') ?></p>
                    <?php if (!empty($project['created_at'])): ?>
                        <div class="portfolio-date">Создано: <?= htmlspecialchars($project['created_at']) ?></div>
                    <?php endif; ?>
                    <?php if (!empty($project['updated_at'])): ?>
                        <div class="portfolio-date">Обновлено: <?= htmlspecialchars($project['updated_at']) ?></div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

