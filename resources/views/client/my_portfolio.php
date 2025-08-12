<?php
// Страница портфолио клиента для клиентского портала (личный кабинет)
/** @var array $profile */
/** @var array $projects */
?>
<div class="client-portal-portfolio">
    <h2>Мой профиль</h2>
    <?php include __DIR__ . '/../client/profile_form.php'; ?>
    <h2>Мои проекты</h2>
    <?php include __DIR__ . '/../client/portfolio_grid.php'; ?>
    <a href="/user/portfolio/add_project.php" class="btn">Добавить проект</a>
</div>

