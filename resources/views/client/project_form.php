<?php
// Форма добавления/редактирования проекта клиента
/** @var array $project */
?>
<form method="post" action="/api/portfolio/create_project.php">
    <input type="hidden" name="client_profile_id" value="<?= htmlspecialchars($project['client_profile_id'] ?? '') ?>">
    <?php if (!empty($project['id'])): ?>
        <input type="hidden" name="project_id" value="<?= htmlspecialchars($project['id']) ?>">
    <?php endif; ?>
    <div>
        <label>Название проекта:</label>
        <input type="text" name="title" value="<?= htmlspecialchars($project['title'] ?? '') ?>" required>
    </div>
    <div>
        <label>Описание:</label>
        <textarea name="description" required><?= htmlspecialchars($project['description'] ?? '') ?></textarea>
    </div>
    <div>
        <label>Статус:</label>
        <select name="status">
            <option value="draft" <?= ($project['status'] ?? '') === 'draft' ? 'selected' : '' ?>>Черновик</option>
            <option value="pending" <?= ($project['status'] ?? '') === 'pending' ? 'selected' : '' ?>>На модерации</option>
            <option value="published" <?= ($project['status'] ?? '') === 'published' ? 'selected' : '' ?>>Опубликован</option>
            <option value="rejected" <?= ($project['status'] ?? '') === 'rejected' ? 'selected' : '' ?>>Отклонён</option>
        </select>
    </div>
    <div>
        <label>Видимость:</label>
        <select name="visibility">
            <option value="public" <?= ($project['visibility'] ?? '') === 'public' ? 'selected' : '' ?>>Публичный</option>
            <option value="private" <?= ($project['visibility'] ?? '') === 'private' ? 'selected' : '' ?>>Приватный</option>
        </select>
    </div>
    <button type="submit">Сохранить проект</button>
</form>

