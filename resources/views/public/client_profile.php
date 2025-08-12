<?php
// Публичный профиль клиента
/** @var array $profile */
?>
<div class="client-public-profile">
    <h2><?= htmlspecialchars($profile['company_name'] ?? '') ?></h2>
    <div><strong>Должность:</strong> <?= htmlspecialchars($profile['position'] ?? '') ?></div>
    <div><strong>О себе:</strong> <?= nl2br(htmlspecialchars($profile['bio'] ?? '')) ?></div>
    <div><strong>Навыки:</strong> <?= htmlspecialchars(implode(', ', $profile['skills'] ?? [])) ?></div>
    <div><strong>Сайт:</strong> <?php if (!empty($profile['website'])): ?><a href="<?= htmlspecialchars($profile['website']) ?>" target="_blank"><?= htmlspecialchars($profile['website']) ?></a><?php endif; ?></div>
    <div><strong>Локация:</strong> <?= htmlspecialchars($profile['location'] ?? '') ?></div>
    <div><strong>Соцсети:</strong>
        <?php if (!empty($profile['social_links'])):
            foreach ($profile['social_links'] as $network => $url): ?>
                <a href="<?= htmlspecialchars($url) ?>" target="_blank"><?= htmlspecialchars($network) ?></a>
            <?php endforeach;
        endif; ?>
    </div>
    <?php if (!empty($profile['allow_contact'])): ?>
        <div class="client-contact">Связаться с клиентом разрешено</div>
    <?php endif; ?>
</div>

