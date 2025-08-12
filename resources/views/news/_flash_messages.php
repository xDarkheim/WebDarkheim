<?php
/**
 * Flash Messages Component - компонент для отображения системных сообщений
 */
if (empty($flashMessages)) {
    return;
}
?>

<div class="container">
    <div class="flash-messages-container">
        <?php foreach ($flashMessages as $type => $messages): ?>
            <?php foreach ($messages as $message): ?>
                <div class="message message--<?php echo htmlspecialchars($type); ?>">
                    <p><?php echo $message['is_html'] ? $message['text'] : htmlspecialchars($message['text']); ?></p>
                </div>
            <?php endforeach; ?>
        <?php endforeach; ?>
    </div>
</div>
