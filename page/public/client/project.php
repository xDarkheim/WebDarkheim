<?php
/**
 * Публичная страница отдельного проекта клиента
 * Детальный просмотр проекта с галереей и описанием
 */

// Use global services from the architecture
global $database_handler, $flashMessageService, $container;

use App\Application\Core\ServiceProvider;

// Get ServiceProvider instance
$serviceProvider = ServiceProvider::getInstance($container);

// Get project ID from URL
$project_id = $_GET['id'] ?? null;

if (!$project_id || !is_numeric($project_id)) {
    header('Location: /index.php?page=404');
    exit();
}

// Get project details with client profile
$stmt = $database_handler->prepare("
    SELECT 
        cp.*,
        prof.display_name,
        prof.professional_title,
        prof.bio,
        prof.avatar,
        u.username
    FROM client_projects cp 
    JOIN client_profiles prof ON cp.client_profile_id = prof.id
    JOIN users u ON prof.user_id = u.id 
    WHERE cp.id = ? 
    AND cp.status = 'published' 
    AND cp.visibility = 'public'
");
$stmt->execute([$project_id]);
$project = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$project) {
    header('Location: /index.php?page=404');
    exit();
}

// Increment view count
$stmt = $database_handler->prepare("UPDATE client_projects SET view_count = view_count + 1 WHERE id = ?");
$stmt->execute([$project_id]);

// Get related projects from same client
$stmt = $database_handler->prepare("
    SELECT id, title, images, technologies 
    FROM client_projects 
    WHERE client_profile_id = ? 
    AND id != ? 
    AND status = 'published' 
    AND visibility = 'public'
    ORDER BY created_at DESC 
    LIMIT 4
");
$stmt->execute([$project['client_profile_id'], $project_id]);
$relatedProjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = htmlspecialchars($project['title']);
$clientName = htmlspecialchars($project['display_name'] ?? $project['username']);

include $_SERVER['DOCUMENT_ROOT'] . '/resources/views/_breadcrumbs.php';
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> - <?= $clientName ?> | Darkheim Studio</title>
    <meta name="description" content="<?= htmlspecialchars(substr($project['description'], 0, 160)) ?>">
    <link rel="stylesheet" href="/public/assets/css/main.css">
    <link rel="stylesheet" href="/public/assets/css/project-detail.css">
</head>
<body>
    <?php include $_SERVER['DOCUMENT_ROOT'] . '/resources/views/_main_navigation.php'; ?>

    <div class="project-detail-container">
        <!-- Project Header -->
        <section class="project-header">
            <div class="container">
                <div class="project-navigation">
                    <a href="/index.php?page=public_client_portfolio&client_id=<?= $project['client_profile_id'] ?>"
                       class="back-link">
                        <i class="icon-arrow-left"></i> Вернуться к портфолио
                    </a>
                </div>

                <div class="project-title-section">
                    <h1><?= $pageTitle ?></h1>

                    <div class="project-author">
                        <div class="author-avatar">
                            <?php if ($project['avatar']): ?>
                                <img src="/storage/uploads/avatars/<?= htmlspecialchars($project['avatar']) ?>"
                                     alt="<?= $clientName ?>">
                            <?php else: ?>
                                <div class="avatar-placeholder">
                                    <i class="icon-user"></i>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="author-info">
                            <h3>
                                <a href="/index.php?page=public_client_portfolio&client_id=<?= $project['client_profile_id'] ?>">
                                    <?= $clientName ?>
                                </a>
                            </h3>
                            <?php if ($project['professional_title']): ?>
                                <p><?= htmlspecialchars($project['professional_title']) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="project-meta">
                        <div class="meta-item">
                            <i class="icon-calendar"></i>
                            <span><?= date('d.m.Y', strtotime($project['completion_date'] ?? $project['created_at'])) ?></span>
                        </div>
                        <div class="meta-item">
                            <i class="icon-eye"></i>
                            <span><?= number_format($project['view_count']) ?> просмотров</span>
                        </div>
                        <?php if ($project['category']): ?>
                            <div class="meta-item">
                                <i class="icon-tag"></i>
                                <span><?= htmlspecialchars($project['category']) ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </section>

        <!-- Project Gallery -->
        <?php if (!empty($project['images'])): ?>
            <?php $images = json_decode($project['images'], true); ?>
            <section class="project-gallery">
                <div class="container">
                    <div class="gallery-main">
                        <img src="/storage/uploads/portfolio/<?= htmlspecialchars($images[0]) ?>"
                             alt="<?= $pageTitle ?>" class="main-image">
                    </div>

                    <?php if (count($images) > 1): ?>
                        <div class="gallery-thumbnails">
                            <?php foreach ($images as $index => $image): ?>
                                <img src="/storage/uploads/portfolio/<?= htmlspecialchars($image) ?>"
                                     alt="<?= $pageTitle ?> - изображение <?= $index + 1 ?>"
                                     class="thumbnail <?= $index === 0 ? 'active' : '' ?>"
                                     onclick="changeMainImage('<?= htmlspecialchars($image) ?>', this)">
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        <?php endif; ?>

        <!-- Project Content -->
        <section class="project-content">
            <div class="container">
                <div class="content-grid">
                    <div class="main-content">
                        <div class="description">
                            <h2>Описание проекта</h2>
                            <div class="description-text">
                                <?= nl2br(htmlspecialchars($project['description'])) ?>
                            </div>
                        </div>

                        <!-- Technologies -->
                        <?php if (!empty($project['technologies'])): ?>
                            <div class="technologies">
                                <h3>Технологии</h3>
                                <div class="tech-list">
                                    <?php $techs = explode(',', $project['technologies']); ?>
                                    <?php foreach ($techs as $tech): ?>
                                        <span class="tech-tag"><?= htmlspecialchars(trim($tech)) ?></span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="sidebar">
                        <!-- Project Links -->
                        <div class="project-links">
                            <h3>Ссылки на проект</h3>

                            <?php if ($project['project_url']): ?>
                                <a href="<?= htmlspecialchars($project['project_url']) ?>"
                                   target="_blank" rel="noopener" class="project-link">
                                    <i class="icon-external-link"></i>
                                    Посмотреть проект
                                </a>
                            <?php endif; ?>

                            <?php if ($project['github_url']): ?>
                                <a href="<?= htmlspecialchars($project['github_url']) ?>"
                                   target="_blank" rel="noopener" class="project-link">
                                    <i class="icon-github"></i>
                                    Исходный код
                                </a>
                            <?php endif; ?>
                        </div>

                        <!-- Contact Author -->
                        <div class="contact-author">
                            <h3>Связаться с автором</h3>
                            <a href="/index.php?page=contact&client_id=<?= $project['client_profile_id'] ?>"
                               class="btn btn-primary">
                                <i class="icon-mail"></i> Написать сообщение
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Related Projects -->
        <?php if (!empty($relatedProjects)): ?>
            <section class="related-projects">
                <div class="container">
                    <h2>Другие проекты автора</h2>
                    <div class="projects-grid">
                        <?php foreach ($relatedProjects as $related): ?>
                            <div class="project-card">
                                <?php if (!empty($related['images'])): ?>
                                    <?php $relatedImages = json_decode($related['images'], true); ?>
                                    <div class="project-image">
                                        <img src="/storage/uploads/portfolio/<?= htmlspecialchars($relatedImages[0]) ?>"
                                             alt="<?= htmlspecialchars($related['title']) ?>">
                                    </div>
                                <?php endif; ?>

                                <div class="project-info">
                                    <h3>
                                        <a href="/index.php?page=client_project&id=<?= $related['id'] ?>">
                                            <?= htmlspecialchars($related['title']) ?>
                                        </a>
                                    </h3>

                                    <?php if (!empty($related['technologies'])): ?>
                                        <div class="tech-tags">
                                            <?php $relatedTechs = array_slice(explode(',', $related['technologies']), 0, 3); ?>
                                            <?php foreach ($relatedTechs as $tech): ?>
                                                <span class="tech-tag"><?= htmlspecialchars(trim($tech)) ?></span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
        <?php endif; ?>
    </div>

    <script>
        function changeMainImage(imageSrc, thumbnail) {
            document.querySelector('.main-image').src = '/storage/uploads/portfolio/' + imageSrc;
            document.querySelectorAll('.thumbnail').forEach(thumb => thumb.classList.remove('active'));
            thumbnail.classList.add('active');
        }
    </script>
</body>
</html>
