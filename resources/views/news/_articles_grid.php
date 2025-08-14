<?php
/**
 * Articles Grid Component - Обновлен для официального дизайна
 * ИСПРАВЛЕНО: Убран дублирующийся заголовок для AJAX навигации
 */

// Получаем ServiceProvider для доступа к TextEditorComponent
global $container;
$serviceProvider = \App\Application\Core\ServiceProvider::getInstance($container);

// Проверяем, что переменная $data доступна
if (!isset($data)) {
    $data = [];
}
?>

<div class="articles-grid">
    <?php if (isset($data['articles']) && !empty($data['articles'])) : ?>
        <?php foreach ($data['articles'] as $article) : ?>
            <article class="article-card">
                <!-- Article Image -->
                <div class="article-card-image">
                    <?php if (!empty($article->image_path)) : ?>
                        <img src="<?php echo htmlspecialchars($article->image_path); ?>"
                             alt="<?php echo htmlspecialchars($article->title); ?>"
                             loading="lazy">
                    <?php endif; ?>
                    <!-- Если нет изображения, показывается CSS placeholder с иконкой -->
                </div>

                <div class="article-card-content">
                    <!-- Category Badge -->
                    <?php if (!empty($article->category_name)) : ?>
                        <span class="article-card-category">
                            <?php echo htmlspecialchars($article->category_name); ?>
                        </span>
                    <?php endif; ?>

                    <!-- Article Title -->
                    <h3 class="article-card-title">
                        <a href="/index.php?page=news&id=<?php echo $article->id; ?>">
                            <?php echo htmlspecialchars($article->title); ?>
                        </a>
                    </h3>

                    <!-- Article Excerpt -->
                    <?php if (!empty($article->short_description)) : ?>
                        <div class="article-card-excerpt">
                            <?php
                            // Используем компонент текстового редактора для правильного форматирования Article Preview
                            $formattedPreview = $serviceProvider->getTextEditorComponent()->formatContent($article->short_description);

                            // Обрезаем отформатированный контент если он слишком длинный
                            $plainText = strip_tags($formattedPreview);
                            if (strlen($plainText) > 150) {
                                $words = explode(' ', $plainText);
                                $truncatedWords = array_slice($words, 0, 25);
                                $truncatedText = implode(' ', $truncatedWords);
                                echo htmlspecialchars($truncatedText) . '...';
                            } else {
                                echo $formattedPreview;
                            }
                            ?>
                        </div>
                    <?php endif; ?>

                    <!-- Article Meta Information -->
                    <div class="article-card-meta">
                        <div class="article-card-author">
                            <i class="fas fa-user"></i>
                            <span>Admin</span>
                        </div>
                        <div class="article-card-date">
                            <?php echo htmlspecialchars(date('M j, Y', strtotime($article->date))); ?>
                        </div>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    <?php else: ?>
        <?php include __DIR__ . '/_no_articles.php'; ?>
    <?php endif; ?>
</div>
