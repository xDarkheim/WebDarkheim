<?php
/**
 * Text Editor Scripts Component - Production Ready TinyMCE with API Key
 * Uses modular CSS and JS files for better organization
 */

// Debug marker
echo '<!-- PRODUCTION TINYMCE EDITOR SCRIPTS WITH API KEY -->' . PHP_EOL;

// Get TinyMCE API key from configuration
$currentDomain = $_SERVER['HTTP_HOST'] ?? 'localhost';
$isLocalDevelopment = in_array($currentDomain, ['localhost', '127.0.0.1', 'darkheim.local']);

// Use no-api-key for local development or if domain is not approved yet
if ($isLocalDevelopment || APP_ENV === 'development') {
    $tinymceApiKey = 'no-api-key';
    echo '<!-- Using no-api-key for local development -->' . PHP_EOL;
} else {
    $tinymceApiKey = defined('TINYMCE_API_KEY') ? TINYMCE_API_KEY : ($_ENV['TINYMCE_API_KEY'] ?? getenv('TINYMCE_API_KEY') ?? 'no-api-key');
    echo '<!-- Using production API key for domain: ' . htmlspecialchars($currentDomain) . ' -->' . PHP_EOL;
}

// Determine editor preset based on current page
$currentPage = $_GET['page'] ?? 'home';
$editorHeight = 450; // Default for news articles

if (strpos($currentPage, 'create_article') !== false || strpos($currentPage, 'edit_article') !== false) {
    $editorHeight = 450;
    $editorPreset = 'news';
} elseif (strpos($currentPage, 'news') !== false) {
    $editorHeight = 150; // For comments
    $editorPreset = 'comment';
} else {
    $editorPreset = 'basic';
}

echo '<!-- Current page: ' . htmlspecialchars($currentPage) . ', Preset: ' . $editorPreset . ' -->' . PHP_EOL;

// Build proper TinyMCE CDN URL with API key
if ($tinymceApiKey === 'no-api-key') {
    // Use official TinyMCE CDN without API key for development
    $tinymceCDN = "https://cdn.jsdelivr.net/npm/tinymce@6/tinymce.min.js";
    echo '<!-- Using TinyMCE CDN without API key for development -->' . PHP_EOL;
} else {
    // Use TinyMCE Cloud CDN with API key for production
    $tinymceCDN = "https://cdn.tiny.cloud/1/{$tinymceApiKey}/tinymce/6/tinymce.min.js";
    echo '<!-- Using TinyMCE Cloud CDN with API key for production -->' . PHP_EOL;
}
echo '<!-- TinyMCE CDN URL: ' . htmlspecialchars($tinymceCDN) . ' -->' . PHP_EOL;
?>

<!-- TinyMCE Dark Theme CSS -->
<link rel="stylesheet" href="/themes/default/css/components/_tinymce-dark-theme.css?v=<?php echo time(); ?>">

<!-- TinyMCE Library from CDN -->
<script src="<?php echo htmlspecialchars($tinymceCDN); ?>" referrerpolicy="origin"></script>

<!-- TinyMCE Configuration -->
<script src="/themes/default/js/tinymce-init.js"></script>

<!-- TinyMCE Initializer -->
<script src="/themes/default/js/tinymce-initializer.js"></script>

<!-- Comments Management Script -->
<script src="/themes/default/js/comments.js"></script>

<!-- Initialize TinyMCE with current configuration -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (window.tinyMCEInitializer) {
        window.tinyMCEInitializer.init({
            apiKey: '<?php echo addslashes($tinymceApiKey); ?>',
            cdnUrl: '<?php echo addslashes($tinymceCDN); ?>',
            editorHeight: <?php echo $editorHeight; ?>,
            editorPreset: '<?php echo addslashes($editorPreset); ?>'
        });
    }
});
</script>

<!-- End of Production TinyMCE Scripts with API Key -->
<?php echo '<!-- END PRODUCTION TINYMCE EDITOR SCRIPTS WITH API KEY -->' . PHP_EOL; ?>
