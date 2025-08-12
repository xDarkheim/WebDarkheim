<?php
// Форма редактирования профиля клиента
/** @var array $profile */
?>
<form method="post" action="/api/client/profile_update.php">
    <input type="hidden" name="user_id" value="<?= htmlspecialchars($profile['user_id'] ?? '') ?>">
    <div>
        <label>Компания:</label>
        <input type="text" name="company_name" value="<?= htmlspecialchars($profile['company_name'] ?? '') ?>">
    </div>
    <div>
        <label>Должность:</label>
        <input type="text" name="position" value="<?= htmlspecialchars($profile['position'] ?? '') ?>">
    </div>
    <div>
        <label>О себе:</label>
        <textarea name="bio"><?= htmlspecialchars($profile['bio'] ?? '') ?></textarea>
    </div>
    <div>
        <label>Навыки (через запятую):</label>
        <input type="text" name="skills" value="<?= htmlspecialchars(implode(', ', $profile['skills'] ?? [])) ?>">
    </div>
    <div>
        <label>Видимость портфолио:</label>
        <select name="portfolio_visibility">
            <option value="public" <?= ($profile['portfolio_visibility'] ?? '') === 'public' ? 'selected' : '' ?>>Публичное</option>
            <option value="private" <?= ($profile['portfolio_visibility'] ?? '') === 'private' ? 'selected' : '' ?>>Приватное</option>
        </select>
    </div>
    <div>
        <label>Разрешить контакт:</label>
        <input type="checkbox" name="allow_contact" value="1" <?= !empty($profile['allow_contact']) ? 'checked' : '' ?>>
    </div>
    <div>
        <label>Ссылки на соцсети (JSON):</label>
        <input type="text" name="social_links" value="<?= htmlspecialchars(json_encode($profile['social_links'] ?? [])) ?>">
    </div>
    <div>
        <label>Сайт:</label>
        <input type="text" name="website" value="<?= htmlspecialchars($profile['website'] ?? '') ?>">
    </div>
    <div>
        <label>Локация:</label>
        <input type="text" name="location" value="<?= htmlspecialchars($profile['location'] ?? '') ?>">
    </div>
    <button type="submit">Сохранить</button>
</form>

