<?php
// Сетка портфолио клиента (public/private)
/** @var array $projects */
?>
<div class="portfolio-grid">
    <?php if (empty($projects)): ?>
        <p>Портфолио пока пусто.</p>
    <?php else: ?>
        <div class="portfolio-list">
            <?php foreach ($projects as $project): ?>
                <div class="portfolio-item">
                    <h3><?= htmlspecialchars($project['title'] ?? '') ?></h3>
                    <p><?= htmlspecialchars($project['description'] ?? '') ?></p>
                    <span class="portfolio-status portfolio-status-<?= htmlspecialchars($project['status'] ?? 'draft') ?>">
                        <?= htmlspecialchars($project['status'] ?? 'draft') ?>
                    </span>
                    <span class="portfolio-visibility portfolio-visibility-<?= htmlspecialchars($project['visibility'] ?? 'private') ?>">
                        <?= htmlspecialchars($project['visibility'] ?? 'private') ?>
                    </span>
                    <?php if (!empty($project['created_at'])): ?>
                        <div class="portfolio-date">Создано: <?= htmlspecialchars($project['created_at']) ?></div>
                    <?php endif; ?>
                    <?php if (!empty($project['updated_at'])): ?>
                        <div class="portfolio-date">Обновлено: <?= htmlspecialchars($project['updated_at']) ?></div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

