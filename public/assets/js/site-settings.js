/**
 * Site Settings Administration Interface
 * JavaScript functionality for managing site settings with mobile optimization
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('Settings page loaded');

    // Инициализация переключения категорий
    initializeCategoryNavigation();

    // Инициализация других функций
    initializeFormHandling();
    initializeAutoResize();
    initializeFlashMessages();

    // Мобильные улучшения
    initializeMobileEnhancements();
    initializeScrollSync();
    initializeTouchSupport();
    initializeResponsiveNavigation();
});

function initializeCategoryNavigation() {
    const categoryButtons = document.querySelectorAll('.settings-category-button');
    const settingsPanels = document.querySelectorAll('.settings-panel');
    const contentTitle = document.getElementById('current-category-title');
    const contentDescription = document.getElementById('current-category-description');
    const contentIcon = document.getElementById('current-category-icon');

    console.log('Found', categoryButtons.length, 'category buttons');
    console.log('Found', settingsPanels.length, 'settings panels');

    categoryButtons.forEach(function(button) {
        button.addEventListener('click', function(e) {
            e.preventDefault();

            const category = this.getAttribute('data-category');
            console.log('Switching to category:', category);

            // Убираем активный класс со всех кнопок
            categoryButtons.forEach(btn => btn.classList.remove('active'));

            // Скрываем все панели
            settingsPanels.forEach(panel => panel.classList.remove('active'));

            // Добавляем активный класс к нажатой кнопке
            this.classList.add('active');

            // Показываем соответствующую панель
            const targetPanel = document.getElementById('settings-panel-' + category);
            if (targetPanel) {
                targetPanel.classList.add('active');
                console.log('Activated panel:', 'settings-panel-' + category);

                // Обновляем заголовок и описание
                const title = targetPanel.getAttribute('data-title');
                const description = targetPanel.getAttribute('data-description');
                const icon = targetPanel.getAttribute('data-icon');

                if (contentTitle && title && icon) {
                    contentTitle.innerHTML =
                        '<i class="fas fa-' + icon + '" id="current-category-icon"></i>' +
                        '<span id="current-category-name">' + title + '</span>';
                }

                if (contentDescription && description) {
                    contentDescription.textContent = description;
                }

                // Прокрутка к контенту на мобильных устройствах
                if (window.innerWidth <= 768) {
                    scrollToSettingsContent();
                }
            } else {
                console.warn('Panel not found for category:', category);
            }
        });
    });

    // Активируем первую категорию по умолчанию, если ни одна не активна
    const activeButton = document.querySelector('.settings-category-button.active');
    if (!activeButton && categoryButtons.length > 0) {
        categoryButtons[0].click();
    }
}

function initializeMobileEnhancements() {
    // Улучшения для мобильных устройств
    if (window.innerWidth <= 768) {
        // Добавляем класс для мобильной версии
        document.body.classList.add('mobile-view');

        // Улучшенная навигация по категориям на мобильных
        setupMobileCategoryNavigation();

        // Оптимизация форм для мобильных
        optimizeFormsForMobile();

        // Улучшение touch-взаимодействий
        enhanceTouchInteractions();
    }
}

function setupMobileCategoryNavigation() {
    const categoryNav = document.querySelector('.settings-category-nav');
    const categoryButtons = document.querySelectorAll('.settings-category-button');

    if (!categoryNav || !categoryButtons.length) return;

    // Для горизонтального скролла на планшетах
    if (window.innerWidth > 480 && window.innerWidth <= 768) {
        let isScrolling = false;
        let startX = 0;
        let scrollLeft = 0;

        categoryNav.addEventListener('mousedown', (e) => {
            isScrolling = true;
            startX = e.pageX - categoryNav.offsetLeft;
            scrollLeft = categoryNav.scrollLeft;
        });

        categoryNav.addEventListener('mouseleave', () => {
            isScrolling = false;
        });

        categoryNav.addEventListener('mouseup', () => {
            isScrolling = false;
        });

        categoryNav.addEventListener('mousemove', (e) => {
            if (!isScrolling) return;
            e.preventDefault();
            const x = e.pageX - categoryNav.offsetLeft;
            const walk = (x - startX) * 2;
            categoryNav.scrollLeft = scrollLeft - walk;
        });
    }

    // Индикатор активной категории для мобильных
    if (window.innerWidth <= 480) {
        addMobileCategoryIndicator();
    }
}

function addMobileCategoryIndicator() {
    const activeButton = document.querySelector('.settings-category-button.active');
    if (!activeButton) return;

    // Создаем индикатор текущей категории
    const indicator = document.createElement('div');
    indicator.className = 'mobile-category-indicator';
    indicator.innerHTML = `
        <span class="current-category-mobile">
            ${activeButton.querySelector('.category-name').textContent}
        </span>
        <i class="fas fa-chevron-down"></i>
    `;

    const settingsContent = document.querySelector('.settings-content');
    if (settingsContent) {
        settingsContent.insertBefore(indicator, settingsContent.firstChild);
    }
}

function optimizeFormsForMobile() {
    const formControls = document.querySelectorAll('.form-control');

    formControls.forEach(control => {
        // Предотвращение зума на iOS при фокусе на input
        if (control.type === 'text' || control.type === 'email' || control.type === 'tel') {
            control.style.fontSize = '16px';
        }

        // Улучшенная клавиатура для разных типов полей
        if (control.type === 'email') {
            control.setAttribute('inputmode', 'email');
        } else if (control.type === 'tel') {
            control.setAttribute('inputmode', 'tel');
        } else if (control.type === 'number') {
            control.setAttribute('inputmode', 'numeric');
        }
    });
}

function enhanceTouchInteractions() {
    // Улучшение переключателей для touch-устройств
    const toggleSwitches = document.querySelectorAll('.toggle-switch');

    toggleSwitches.forEach(toggle => {
        const input = toggle.querySelector('.toggle-input');
        const label = toggle.querySelector('.toggle-label');

        if (input && label) {
            // Добавляем тактильную обратную связь
            label.addEventListener('touchstart', function() {
                if (navigator.vibrate) {
                    navigator.vibrate(50);
                }
            });
        }
    });

    // Улучшение кнопок категорий
    const categoryButtons = document.querySelectorAll('.settings-category-button');

    categoryButtons.forEach(button => {
        button.addEventListener('touchstart', function() {
            this.classList.add('touching');
        });

        button.addEventListener('touchend', function() {
            this.classList.remove('touching');
        });
    });
}

function initializeScrollSync() {
    // Синхронизация скролла для лучшего UX на мобильных
    const categoryNav = document.querySelector('.settings-category-nav');
    const activeButton = document.querySelector('.settings-category-button.active');

    if (categoryNav && activeButton && window.innerWidth <= 768) {
        // Прокрутка к активной категории
        const buttonRect = activeButton.getBoundingClientRect();
        const navRect = categoryNav.getBoundingClientRect();

        if (buttonRect.left < navRect.left || buttonRect.right > navRect.right) {
            activeButton.scrollIntoView({
                behavior: 'smooth',
                block: 'nearest',
                inline: 'center'
            });
        }
    }
}

function initializeTouchSupport() {
    // Поддержка свайпов для переключения категорий на мобильных
    if (window.innerWidth <= 768) {
        let startX = 0;
        let endX = 0;

        const settingsContent = document.querySelector('.settings-content');
        if (!settingsContent) return;

        settingsContent.addEventListener('touchstart', function(e) {
            startX = e.touches[0].clientX;
        });

        settingsContent.addEventListener('touchend', function(e) {
            endX = e.changedTouches[0].clientX;
            handleSwipe();
        });

        function handleSwipe() {
            const threshold = 100; // Минимальное расстояние для свайпа
            const diff = startX - endX;

            if (Math.abs(diff) > threshold) {
                const categoryButtons = Array.from(document.querySelectorAll('.settings-category-button'));
                const activeIndex = categoryButtons.findIndex(btn => btn.classList.contains('active'));

                if (diff > 0 && activeIndex < categoryButtons.length - 1) {
                    // Свайп влево - следующая категория
                    categoryButtons[activeIndex + 1].click();
                } else if (diff < 0 && activeIndex > 0) {
                    // Свайп вправо - предыдущая категория
                    categoryButtons[activeIndex - 1].click();
                }
            }
        }
    }
}

function initializeResponsiveNavigation() {
    // Адаптивная навигация при изменении размера окна
    let resizeTimer;

    window.addEventListener('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
            const isMobile = window.innerWidth <= 768;

            if (isMobile && !document.body.classList.contains('mobile-view')) {
                document.body.classList.add('mobile-view');
                setupMobileCategoryNavigation();
            } else if (!isMobile && document.body.classList.contains('mobile-view')) {
                document.body.classList.remove('mobile-view');
                removeMobileFeatures();
            }
        }, 250);
    });
}

function removeMobileFeatures() {
    // Удаление мобильных элементов при переходе на десктоп
    const mobileIndicator = document.querySelector('.mobile-category-indicator');
    if (mobileIndicator) {
        mobileIndicator.remove();
    }
}

function scrollToSettingsContent() {
    // Плавная прокрутка к контенту настроек на мобильных
    const settingsContent = document.querySelector('.settings-content');
    if (settingsContent) {
        const headerHeight = document.querySelector('.page-header')?.offsetHeight || 0;
        const targetPosition = settingsContent.offsetTop - headerHeight - 20;

        window.scrollTo({
            top: targetPosition,
            behavior: 'smooth'
        });
    }
}

function initializeFormHandling() {
    // Обработка отправки формы с улучшениями для мобильных
    const settingsForm = document.querySelector('.settings-form');
    if (settingsForm) {
        settingsForm.addEventListener('submit', function(e) {
            console.log('Settings form submitted');

            // Добавляем состояние загрузки
            const submitButton = this.querySelector('button[type="submit"]');
            if (submitButton) {
                const originalText = submitButton.innerHTML;
                submitButton.disabled = true;
                submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <span>Saving...</span>';

                // Прокрутка к кнопке на мобильных для лучшего UX
                if (window.innerWidth <= 768) {
                    submitButton.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center'
                    });
                }

                // Восстанавливаем кнопку через 10 секунд на случай ошибки
                setTimeout(() => {
                    submitButton.disabled = false;
                    submitButton.innerHTML = originalText;
                }, 10000);
            }
        });
    }

    // Обработка быстрых действий с улучшениями для мобильных
    const quickActionForms = document.querySelectorAll('.quick-action-item form');
    quickActionForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const button = this.querySelector('button[type="submit"]');
            if (button) {
                const originalText = button.innerHTML;
                button.disabled = true;
                button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';

                // Тактильная обратная связь на мобильных
                if (navigator.vibrate && window.innerWidth <= 768) {
                    navigator.vibrate(100);
                }

                // Восстанавливаем кнопку через 10 секунд
                setTimeout(() => {
                    button.disabled = false;
                    button.innerHTML = originalText;
                }, 10000);
            }
        });
    });
}

function initializeAutoResize() {
    // Автоматическое изменение размера текстовых областей с учетом мобильных
    const textareas = document.querySelectorAll('.textarea-auto-resize');
    textareas.forEach(textarea => {
        // Функция для изменения размера
        function autoResize() {
            textarea.style.height = 'auto';
            const newHeight = Math.max(textarea.scrollHeight, window.innerWidth <= 768 ? 100 : 80);
            textarea.style.height = newHeight + 'px';
        }

        // Обработчик события ввода
        textarea.addEventListener('input', autoResize);

        // Первоначальное изменение размера
        autoResize();
    });
}

function initializeFlashMessages() {
    // Автоматическое скрытие flash-сообщений с улучшениями для мобильных
    setTimeout(function() {
        const messages = document.querySelectorAll('.message');
        messages.forEach(function(message) {
            if (message.parentNode) {
                // Добавляем fade-out класс вместо анимации
                message.style.opacity = '0';
                setTimeout(() => {
                    if (message.parentNode) {
                        message.parentNode.removeChild(message);
                    }
                }, 300);
            }
        });
    }, 5000);
}

// Функции для кнопок действий с улучшениями для мобильных
function resetSettings() {
    // Улучшенное подтверждение для мобильных
    const confirmMessage = window.innerWidth <= 768
        ? 'Reset all changes?\n\nThis will reload original values.'
        : 'Are you sure you want to reset all changes? This will reload the original values.';

    if (confirm(confirmMessage)) {
        // Показываем индикатор загрузки
        showMobileLoadingOverlay('Resetting settings...');

        // Перезагружаем страницу
        setTimeout(() => {
            window.location.reload();
        }, 500);
    }
}

function previewChanges() {
    console.log('Preview changes clicked');

    // Собираем все измененные настройки
    const form = document.querySelector('.settings-form');
    if (!form) {
        showMobileAlert('Settings form not found');
        return;
    }

    const formData = new FormData(form);
    const changes = [];

    for (let [key, value] of formData.entries()) {
        if (key !== 'csrf_token' && key !== 'action') {
            changes.push({
                setting: key,
                value: value
            });
        }
    }

    if (changes.length === 0) {
        showMobileAlert('No changes detected');
        return;
    }

    // Создаем превью для мобильных и десктопа
    showChangesPreview(changes);
}

function exportSettings() {
    console.log('Export settings clicked');

    // Показываем индикатор загрузки
    showMobileLoadingOverlay('Preparing export...');

    // Имитация экспорта (здесь должна быть реальная логика)
    setTimeout(() => {
        hideMobileLoadingOverlay();
        showMobileAlert('Export feature coming soon!');
    }, 1500);
}

function showMobileLoadingOverlay(message) {
    const loadingOverlay = document.createElement('div');
    loadingOverlay.className = 'settings-loading-overlay';
    loadingOverlay.innerHTML = `
        <div class="loading-content">
            <div class="loading-spinner"></div>
            <div class="loading-message">${message}</div>
        </div>
    `;

    // Мобильные стили для overlay
    Object.assign(loadingOverlay.style, {
        position: 'fixed',
        top: '0',
        left: '0',
        width: '100%',
        height: '100%',
        backgroundColor: 'rgba(15, 23, 42, 0.95)',
        zIndex: '9999',
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'center',
        color: '#f8fafc',
        fontSize: window.innerWidth <= 768 ? '16px' : '14px'
    });

    document.body.appendChild(loadingOverlay);
}

function hideMobileLoadingOverlay() {
    const overlay = document.querySelector('.settings-loading-overlay');
    if (overlay) {
        overlay.remove();
    }
}

function showMobileAlert(message) {
    // Простое мобильное уведомление
    if (window.innerWidth <= 768) {
        // Создаем кастомное мобильное уведомление
        const alertDiv = document.createElement('div');
        alertDiv.className = 'mobile-alert';
        alertDiv.textContent = message;

        Object.assign(alertDiv.style, {
            position: 'fixed',
            top: '20px',
            left: '50%',
            transform: 'translateX(-50%)',
            backgroundColor: '#334155',
            color: '#f8fafc',
            padding: '12px 20px',
            borderRadius: '8px',
            zIndex: '10000',
            fontSize: '14px',
            boxShadow: '0 4px 12px rgba(0, 0, 0, 0.3)',
            maxWidth: '90%',
            textAlign: 'center'
        });

        document.body.appendChild(alertDiv);

        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.remove();
            }
        }, 3000);
    } else {
        alert(message);
    }
}

function showChangesPreview(changes) {
    const previewHtml = changes.map(change =>
        `<div class="preview-item">
            <strong>${change.setting.replace(/_/g, ' ').toUpperCase()}:</strong> 
            ${change.value || '(empty)'}
        </div>`
    ).join('');

    if (window.innerWidth <= 768) {
        // Мобильное превью
        const previewDiv = document.createElement('div');
        previewDiv.className = 'mobile-preview';
        previewDiv.innerHTML = `
            <div class="preview-header">
                <h3>Changes Preview</h3>
                <button onclick="this.parentElement.parentElement.remove()" class="close-btn">×</button>
            </div>
            <div class="preview-content">${previewHtml}</div>
        `;

        Object.assign(previewDiv.style, {
            position: 'fixed',
            top: '10%',
            left: '5%',
            right: '5%',
            backgroundColor: '#1e293b',
            border: '1px solid #475569',
            borderRadius: '12px',
            zIndex: '10000',
            maxHeight: '80%',
            overflow: 'auto',
            color: '#f8fafc'
        });

        document.body.appendChild(previewDiv);
    } else {
        // Десктопное превью
        const previewWindow = window.open('', 'preview', 'width=600,height=400');
        previewWindow.document.write(`
            <html>
                <head>
                    <title>Settings Preview</title>
                    <style>
                        body { font-family: Inter, sans-serif; background: #0f172a; color: #f8fafc; padding: 20px; }
                        .preview-item { margin: 10px 0; padding: 10px; background: #1e293b; border-radius: 8px; }
                    </style>
                </head>
                <body>
                    <h2>Settings Changes Preview</h2>
                    ${previewHtml}
                </body>
            </html>
        `);
    }
}

// Функция для обновления расписания резервного копирования
function updateBackupSchedule() {
    const preset = document.getElementById('backup_schedule_preset');
    const input = document.getElementById('backup_schedule');

    if (preset && input && preset.value && preset.value !== 'custom') {
        input.value = preset.value;
    }
}
