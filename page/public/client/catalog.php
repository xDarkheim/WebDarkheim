<?php
/**
 * Публичная страница со списком всех портфолио клиентов
 * Каталог всех клиентов с публичными портфолио
 */

// Use global services from the architecture
global $database_handler, $flashMessageService, $container;

use App\Application\Core\ServiceProvider;

// Get ServiceProvider instance
$serviceProvider = ServiceProvider::getInstance($container);

// Pagination settings
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 12;
$offset = ($page - 1) * $perPage;

// Search and filter parameters
$search = trim($_GET['search'] ?? '');
$skill = trim($_GET['skill'] ?? '');
$sort = $_GET['sort'] ?? 'newest';

// Build WHERE conditions
$whereConditions = ["cp.portfolio_visibility = 'public'"];
$params = [];

if (!empty($search)) {
    $whereConditions[] = "(cp.display_name LIKE ? OR cp.professional_title LIKE ? OR cp.bio LIKE ? OR u.username LIKE ?)";
    $searchParam = "%{$search}%";
    $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam]);
}

if (!empty($skill)) {
    $whereConditions[] = "EXISTS (SELECT 1 FROM client_skills cs WHERE cs.client_profile_id = cp.id AND cs.skill_name LIKE ?)";
    $params[] = "%{$skill}%";
}

$whereClause = implode(' AND ', $whereConditions);

// Sort options
$sortOptions = [
    'newest' => 'cp.created_at DESC',
    'name' => 'COALESCE(cp.display_name, u.username) ASC',
    'projects' => 'project_count DESC',
    'views' => 'total_views DESC'
];
$orderBy = $sortOptions[$sort] ?? $sortOptions['newest'];

// Get total count
$countSql = "
    SELECT COUNT(DISTINCT cp.id) 
    FROM client_profiles cp 
    JOIN users u ON cp.user_id = u.id 
    WHERE {$whereClause}
";
$stmt = $database_handler->prepare($countSql);
$stmt->execute($params);
$totalCount = $stmt->fetchColumn();

// Get clients with statistics
$sql = "
    SELECT 
        cp.*,
        u.username,
        u.created_at as user_since,
        COALESCE(cp.display_name, u.username) as display_name,
        COUNT(DISTINCT proj.id) as project_count,
        COALESCE(SUM(proj.view_count), 0) as total_views
    FROM client_profiles cp
    JOIN users u ON cp.user_id = u.id
    LEFT JOIN client_projects proj ON cp.id = proj.client_profile_id 
        AND proj.status = 'published' 
        AND proj.visibility = 'public'
    WHERE {$whereClause}
    GROUP BY cp.id, u.id
    ORDER BY {$orderBy}
    LIMIT {$perPage} OFFSET {$offset}
";

$stmt = $database_handler->prepare($sql);
$stmt->execute($params);
$clients = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate pagination
$totalPages = ceil($totalCount / $perPage);

// Get popular skills for filter
$skillsStmt = $database_handler->prepare("
    SELECT skill_name, COUNT(*) as usage_count 
    FROM client_skills cs
    JOIN client_profiles cp ON cs.client_profile_id = cp.id
    WHERE cp.portfolio_visibility = 'public'
    GROUP BY skill_name 
    ORDER BY usage_count DESC 
    LIMIT 20
");
$skillsStmt->execute();
$popularSkills = $skillsStmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Портфолио клиентов';

include $_SERVER['DOCUMENT_ROOT'] . '/resources/views/_breadcrumbs.php';
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> | Darkheim Studio</title>
    <meta name="description" content="Портфолио наших клиентов - разработчиков, дизайнеров и IT-специалистов. Просмотрите проекты и найдите специалиста для вашей задачи.">
    <link rel="stylesheet" href="/public/assets/css/main.css">
    <link rel="stylesheet" href="/public/assets/css/client-catalog.css">
</head>
<body>
    <?php include $_SERVER['DOCUMENT_ROOT'] . '/resources/views/_main_navigation.php'; ?>
    
    <div class="client-catalog-container">
        <!-- Header -->
        <section class="catalog-header">
            <div class="container">
                <h1>Портфолио наших клиентов</h1>
                <p>Познакомьтесь с талантливыми специалистами и их проектами</p>
                
                <div class="catalog-stats">
                    <div class="stat">
                        <span class="stat-number"><?= number_format($totalCount) ?></span>
                        <span class="stat-label">Портфолио</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- Filters -->
        <section class="catalog-filters">
            <div class="container">
                <form method="GET" class="filter-form">
                    <div class="filter-row">
                        <div class="search-group">
                            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" 
                                   placeholder="Поиск по имени или специализации..." class="search-input">
                        </div>
                        
                        <div class="filter-group">
                            <select name="skill" class="filter-select">
                                <option value="">Все навыки</option>
                                <?php foreach ($popularSkills as $skillData): ?>
                                    <option value="<?= htmlspecialchars($skillData['skill_name']) ?>"
                                            <?= $skill === $skillData['skill_name'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($skillData['skill_name']) ?> (<?= $skillData['usage_count'] ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <select name="sort" class="filter-select">
                                <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Новые</option>
                                <option value="name" <?= $sort === 'name' ? 'selected' : '' ?>>По имени</option>
                                <option value="projects" <?= $sort === 'projects' ? 'selected' : '' ?>>По кол-ву проектов</option>
                                <option value="views" <?= $sort === 'views' ? 'selected' : '' ?>>По просмотрам</option>
                            </select>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Найти</button>
                    </div>
                </form>
            </div>
        </section>

        <!-- Results -->
        <section class="catalog-results">
            <div class="container">
                <?php if (empty($clients)): ?>
                    <div class="empty-results">
                        <div class="empty-icon">
                            <i class="icon-users"></i>
                        </div>
                        <h3>Портфолио не найдены</h3>
                        <p>Попробуйте изменить параметры поиска</p>
                    </div>
                <?php else: ?>
                    <div class="results-header">
                        <p>Найдено <?= number_format($totalCount) ?> портфолио</p>
                    </div>
                    
                    <div class="clients-grid">
                        <?php foreach ($clients as $client): ?>
                            <div class="client-card">
                                <div class="client-avatar">
                                    <?php if ($client['avatar']): ?>
                                        <img src="/storage/uploads/avatars/<?= htmlspecialchars($client['avatar']) ?>" 
                                             alt="<?= htmlspecialchars($client['display_name']) ?>">
                                    <?php else: ?>
                                        <div class="avatar-placeholder">
                                            <i class="icon-user"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="client-info">
                                    <h3>
                                        <a href="/index.php?page=public_client_portfolio&client_id=<?= $client['id'] ?>">
                                            <?= htmlspecialchars($client['display_name']) ?>
                                        </a>
                                    </h3>
                                    
                                    <?php if ($client['professional_title']): ?>
                                        <p class="professional-title">
                                            <?= htmlspecialchars($client['professional_title']) ?>
                                        </p>
                                    <?php endif; ?>
                                    
                                    <?php if ($client['bio']): ?>
                                        <p class="bio">
                                            <?= htmlspecialchars(substr($client['bio'], 0, 120)) ?>...
                                        </p>
                                    <?php endif; ?>
                                    
                                    <div class="client-stats">
                                        <span class="stat">
                                            <i class="icon-folder"></i>
                                            <?= $client['project_count'] ?> проектов
                                        </span>
                                        <span class="stat">
                                            <i class="icon-eye"></i>
                                            <?= number_format($client['total_views']) ?> просмотров
                                        </span>
                                    </div>
                                    
                                    <!-- Top skills -->
                                    <?php
                                    $clientSkillsStmt = $database_handler->prepare("
                                        SELECT skill_name 
                                        FROM client_skills 
                                        WHERE client_profile_id = ? 
                                        ORDER BY proficiency_level DESC 
                                        LIMIT 3
                                    ");
                                    $clientSkillsStmt->execute([$client['id']]);
                                    $clientSkills = $clientSkillsStmt->fetchAll(PDO::FETCH_COLUMN);
                                    ?>
                                    
                                    <?php if (!empty($clientSkills)): ?>
                                        <div class="client-skills">
                                            <?php foreach ($clientSkills as $skillName): ?>
                                                <span class="skill-tag"><?= htmlspecialchars($skillName) ?></span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="client-actions">
                                    <a href="/index.php?page=public_client_portfolio&client_id=<?= $client['id'] ?>"
                                       class="btn btn-primary">Смотреть портфолио</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                        <div class="pagination">
                            <?php if ($page > 1): ?>
                                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>" 
                                   class="pagination-link">
                                    <i class="icon-arrow-left"></i> Предыдущая
                                </a>
                            <?php endif; ?>
                            
                            <div class="pagination-numbers">
                                <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>" 
                                       class="pagination-number <?= $i === $page ? 'active' : '' ?>">
                                        <?= $i ?>
                                    </a>
                                <?php endfor; ?>
                            </div>
                            
                            <?php if ($page < $totalPages): ?>
                                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>" 
                                   class="pagination-link">
                                    Следующая <i class="icon-arrow-right"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </section>
    </div>
</body>
</html>
