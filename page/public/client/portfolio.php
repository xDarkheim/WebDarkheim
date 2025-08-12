<?php
/**
 * Публичная страница портфолио клиента
 * Отображает профиль клиента и его проекты для посетителей
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/bootstrap.php';

use App\Domain\Models\ClientProfile;
use App\Domain\Models\ClientProject;

// Получаем ID клиента из URL
$client_id = $_GET['client_id'] ?? null;

if (!$client_id || !is_numeric($client_id)) {
    header('Location: /page/system/404.php');
    exit();
}

// Получаем профиль клиента
$clientProfile = ClientProfile::find($client_id);

if (!$clientProfile || $clientProfile->portfolio_visibility !== 'public') {
    header('Location: /page/system/404.php');
    exit();
}

// Получаем опубликованные проекты
$projects = ClientProject::getPublishedByClientProfileId($client_id);

// Получаем категории проектов для фильтрации
$categories = [];
foreach ($projects as $project) {
    if (!empty($project->category)) {
        $categories[$project->category] = $project->category;
    }
}

$pageTitle = $clientProfile->display_name ?? $clientProfile->getUser()->username;
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> - Портфолио | Darkheim Studio</title>
    <meta name="description" content="<?= htmlspecialchars($clientProfile->bio ?? 'Портфолио ' . $pageTitle) ?>">
    <link rel="stylesheet" href="/public/assets/css/main.css">
    <link rel="stylesheet" href="/public/assets/css/portfolio-public.css">
</head>
<body>
    <?php include $_SERVER['DOCUMENT_ROOT'] . '/resources/views/_main_navigation.php'; ?>

    <div class="portfolio-container">
        <!-- Профиль клиента -->
        <section class="client-profile-section">
            <div class="container">
                <div class="profile-header">
                    <div class="profile-avatar">
                        <?php if ($clientProfile->avatar): ?>
                            <img src="/storage/uploads/avatars/<?= htmlspecialchars($clientProfile->avatar) ?>"
                                 alt="<?= htmlspecialchars($pageTitle) ?>">
                        <?php else: ?>
                            <div class="avatar-placeholder">
                                <i class="icon-user"></i>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="profile-info">
                        <h1><?= htmlspecialchars($pageTitle) ?></h1>

                        <?php if ($clientProfile->professional_title): ?>
                            <p class="professional-title"><?= htmlspecialchars($clientProfile->professional_title) ?></p>
                        <?php endif; ?>

                        <?php if ($clientProfile->bio): ?>
                            <div class="bio">
                                <?= nl2br(htmlspecialchars($clientProfile->bio)) ?>
                            </div>
                        <?php endif; ?>

                        <div class="profile-stats">
                            <div class="stat">
                                <span class="stat-number"><?= count($projects) ?></span>
                                <span class="stat-label">Проектов</span>
                            </div>
                            <div class="stat">
                                <span class="stat-number"><?= $clientProfile->getTotalViews() ?></span>
                                <span class="stat-label">Просмотров</span>
                            </div>
                            <div class="stat">
                                <span class="stat-number"><?= $clientProfile->getExperienceYears() ?></span>
                                <span class="stat-label">Лет опыта</span>
                            </div>
                        </div>

                        <!-- Социальные сети -->
                        <?php $socialLinks = $clientProfile->getSocialLinks(); ?>
                        <?php if (!empty($socialLinks)): ?>
                            <div class="social-links">
                                <?php foreach ($socialLinks as $link): ?>
                                    <a href="<?= htmlspecialchars($link->url) ?>"
                                       target="_blank" rel="noopener"
                                       class="social-link social-<?= htmlspecialchars($link->platform) ?>">
                                        <i class="icon-<?= htmlspecialchars($link->platform) ?>"></i>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <!-- Кнопка связи -->
                        <div class="contact-actions">
                            <a href="/index.php?page=contact&client_id=<?= $client_id ?>"
                               class="btn btn-primary">
                                <i class="icon-mail"></i> Связаться
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Навыки -->
        <?php $skills = $clientProfile->getSkills(); ?>
        <?php if (!empty($skills)): ?>
            <section class="skills-section">
                <div class="container">
                    <h2>Навыки и технологии</h2>
                    <div class="skills-grid">
                        <?php foreach ($skills as $skill): ?>
                            <div class="skill-item">
                                <div class="skill-name"><?= htmlspecialchars($skill->skill_name) ?></div>
                                <div class="skill-level">
                                    <div class="skill-bar">
                                        <div class="skill-progress" style="width: <?= $skill->proficiency_level ?>%"></div>
                                    </div>
                                    <span class="skill-percentage"><?= $skill->proficiency_level ?>%</span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
        <?php endif; ?>

        <!-- Портфолио проектов -->
        <section class="projects-section">
            <div class="container">
                <div class="section-header">
                    <h2>Портфолио проектов</h2>

                    <!-- Фильтры -->
                    <?php if (!empty($categories)): ?>
                        <div class="projects-filters">
                            <button class="filter-btn active" data-category="all">Все проекты</button>
                            <?php foreach ($categories as $category): ?>
                                <button class="filter-btn" data-category="<?= htmlspecialchars($category) ?>">
                                    <?= htmlspecialchars($category) ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if (empty($projects)): ?>
                    <div class="empty-portfolio">
                        <div class="empty-icon">
                            <i class="icon-portfolio"></i>
                        </div>
                        <h3>Проекты пока не добавлены</h3>
                        <p>Скоро здесь появятся интересные работы!</p>
                    </div>
                <?php else: ?>
                    <div class="projects-grid">
                        <?php foreach ($projects as $project): ?>
                            <article class="project-card" data-category="<?= htmlspecialchars($project->category ?? 'other') ?>">
                                <div class="project-image">
                                    <?php if (!empty($project->images)): ?>
                                        <?php $images = json_decode($project->images, true); ?>
                                        <img src="/storage/uploads/portfolio/<?= htmlspecialchars($images[0]) ?>"
                                             alt="<?= htmlspecialchars($project->title) ?>">
                                    <?php else: ?>
                                        <div class="project-placeholder">
                                            <i class="icon-image"></i>
                                        </div>
                                    <?php endif; ?>

                                    <div class="project-overlay">
                                        <a href="/index.php?page=client_project&id=<?= $project->id ?>"
                                           class="project-link">
                                            <i class="icon-eye"></i> Подробнее
                                        </a>
                                    </div>
                                </div>

                                <div class="project-content">
                                    <h3>
                                        <a href="/index.php?page=client_project&id=<?= $project->id ?>">
                                            <?= htmlspecialchars($project->title) ?>
                                        </a>
                                    </h3>

                                    <p><?= htmlspecialchars(substr($project->description, 0, 120)) ?>...</p>

                                    <?php if (!empty($project->technologies)): ?>
                                        <div class="project-tech">
                                            <?php $techs = array_slice(explode(',', $project->technologies), 0, 4); ?>
                                            <?php foreach ($techs as $tech): ?>
                                                <span class="tech-tag"><?= htmlspecialchars(trim($tech)) ?></span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>

                                    <div class="project-meta">
                                        <span class="project-date">
                                            <i class="icon-calendar"></i>
                                            <?= date('M Y', strtotime($project->completion_date ?? $project->created_at)) ?>
                                        </span>

                                        <?php if ($project->project_url): ?>
                                            <a href="<?= htmlspecialchars($project->project_url) ?>"
                                               target="_blank" rel="noopener" class="project-external">
                                                <i class="icon-external-link"></i> Посмотреть проект
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </div>

    <script src="/public/assets/js/portfolio-public.js"></script>
</body>
</html>
