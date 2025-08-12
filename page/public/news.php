<?php

/**
 * News Page
 *
 * This page displays a list of news articles or a single article.
 *
 * @author Dmytro Hovenko
 */

declare(strict_types=1);

// Get global services from DI container
global $container;

try {
    // Get ServiceProvider for accessing services
    $serviceProvider = \App\Application\Core\ServiceProvider::getInstance($container);
    // Get required services via ServiceProvider
    $newsService = $serviceProvider->getNewsService();
    $authService = $serviceProvider->getAuth();
    $flashService = $serviceProvider->getFlashMessage();
    $logger = $serviceProvider->getLogger();

    // Create news controller
    $newsController = new \App\Application\Controllers\NewsController(
        $newsService,
        $authService,
        $flashService,
        $logger
    );

    // Handle request
    $data = $newsController->handle();

    // Get flash messages
    $flashMessages = $newsController->getFlashMessages();

    // Set page title
    $pageTitle = $data['page_title'] ?? 'News Hub';

} catch (Exception $e) {
    // Handle critical errors
    if (isset($logger)) {
        $logger->critical("Critical error in news page: " . $e->getMessage());
    }
    
    $data = [
        'error' => 'System temporarily unavailable. Please try again later.',
        'page_title' => 'News Hub'
    ];
    $flashMessages = [];
    $pageTitle = 'News Hub'; // Default value for PHPStan
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> - Darkheim.net</title>

    <!-- Font Awesome for icons (local version) -->
    <link rel="stylesheet" href="/themes/default/assets/fontawesome/css/all.min.css">

    <!-- Include isolated styles for news -->
    <link rel="stylesheet" href="/themes/default/css/pages/_news-isolated.css">
</head>
<body>

<div class="news-isolated">
    <div class="news-layout">
        <!-- Flash Messages -->
        <?php if (!empty($flashMessages)) : ?>
            <div class="flash-messages-container">
                <?php foreach ($flashMessages as $type => $messages): ?>
                    <?php foreach ($messages as $message): ?>
                        <div class="message message--<?php echo htmlspecialchars($type); ?>">
                            <p><?php echo $message['is_html'] ? $message['text'] : htmlspecialchars($message['text']); ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Error Messages -->
        <?php if (isset($data['error'])) : ?>
            <div class="flash-messages-container">
                <div class="message message--error">
                    <p><?php echo htmlspecialchars($data['error']); ?></p>
                </div>
            </div>
        <?php endif; ?>

        <!-- Main Content -->
        <?php if (!isset($data['error'])) : ?>
            <?php if ($data['view_type'] === 'single_article') : ?>
                <?php include __DIR__ . '/../../resources/views/news/article.php'; ?>
            <?php else : ?>
                <?php include __DIR__ . '/../../resources/views/news/list.php'; ?>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Include text editor scripts for comments -->
<?php include __DIR__ . '/../../resources/views/_editor_scripts.php'; ?>

</body>
</html>
