/**
 * JavaScript для страницы создания/редактирования статьи
 * Система черновиков - без предупреждений о несохраненных данных
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM Content Loaded - starting script initialization');

    // Базовые элементы формы
    const form = document.querySelector('.article-creation-form');
    const titleInput = document.getElementById('title');
    const titleCounter = document.getElementById('titleCounter');
    const dateInput = document.getElementById('date');
    const fullTextEditor = document.getElementById('full_text');
    const categorySearch = document.getElementById('categorySearch');
    const categoryOptions = document.querySelectorAll('.ca-category-option');
    const categoryCheckboxes = document.querySelectorAll('.ca-category-checkbox');
    const selectedCategoriesText = document.getElementById('selectedCategoriesText');
    const sidebarWordCount = document.getElementById('sidebarWordCount');
    const sidebarReadTime = document.getElementById('sidebarReadTime');

    console.log('Form elements found:', {
        form: !!form,
        titleInput: !!titleInput,
        dateInput: !!dateInput,
        fullTextEditor: !!fullTextEditor
    });

    // Кнопки действий - расширенная отладка
    const publishButton = document.querySelector('button[name="action"][value="publish"]');
    const saveDraftButton = document.querySelector('button[name="action"][value="save_draft"]');
    const updateButton = document.querySelector('button[name="action"][value="update"]');

    // Дополнительные способы поиска кнопок
    const allButtons = document.querySelectorAll('button[type="submit"]');
    const allActionButtons = document.querySelectorAll('button[name="action"]');

    console.log('Button search results:', {
        publishButton: !!publishButton,
        saveDraftButton: !!saveDraftButton,
        updateButton: !!updateButton,
        totalSubmitButtons: allButtons.length,
        totalActionButtons: allActionButtons.length
    });

    // Логируем все найденные кнопки
    console.log('All submit buttons:', Array.from(allButtons).map(btn => ({
        type: btn.type,
        name: btn.name,
        value: btn.value,
        className: btn.className,
        textContent: btn.textContent.trim()
    })));

    console.log('All action buttons:', Array.from(allActionButtons).map(btn => ({
        type: btn.type,
        name: btn.name,
        value: btn.value,
        className: btn.className,
        textContent: btn.textContent.trim()
    })));

    // Если кнопки не найдены стандартным способом, попробуем альтернативные селекторы
    if (!publishButton && !saveDraftButton && !updateButton) {
        console.warn('No buttons found with standard selectors, trying alternatives...');

        const altPublishButton = document.querySelector('.ca-btn-publish');
        const altSaveDraftButton = document.querySelector('.ca-btn-save-draft');
        const altUpdateButton = document.querySelector('button[value="update"]');

        console.log('Alternative button search:', {
            altPublishButton: !!altPublishButton,
            altSaveDraftButton: !!altSaveDraftButton,
            altUpdateButton: !!altUpdateButton
        });
    }

    // ПОЛНОСТЬЮ ОТКЛЮЧАЕМ ВСЕ ПРЕДУПРЕЖДЕНИЯ О НЕСОХРАНЕННЫХ ДАННЫХ
    window.onbeforeunload = null;
    window.addEventListener('beforeunload', function(e) {
        // НЕ показываем никаких предупреждений для системы черновиков
        delete e['returnValue'];
    }, false);

    // ОЧИЩАЕМ СТАРЫЕ ДАННЫЕ ИЗ localStorage
    if (localStorage.getItem('article_draft')) {
        console.log('Removing old draft data from localStorage - using new draft system');
        localStorage.removeItem('article_draft');
    }

    // Функция для получения содержимого редактора
    function getEditorContent() {
        // Пытаемся получить содержимое из TinyMCE
        if (typeof tinymce !== 'undefined' && tinymce.get('full_text')) {
            const content = tinymce.get('full_text').getContent();
            // Убираем HTML теги для подсчета текста
            return content.replace(/<[^>]*>/g, '').trim();
        }

        // Fallback на обычное текстовое поле
        if (fullTextEditor) {
            return fullTextEditor.value.trim();
        }

        // Пытаемся найти скрытое поле с содержимым
        const hiddenFullText = document.querySelector('input[name="full_text"], textarea[name="full_text"]');
        if (hiddenFullText) {
            return hiddenFullText.value.trim();
        }

        return '';
    }

    // Функция для показа/скрытия ошибок валидации
    function toggleFieldError(field, show, message = '') {
        if (!field) return;

        const fieldContainer = field.closest('.form-section');
        let errorElement = fieldContainer ? fieldContainer.querySelector('.field-error') : null;

        if (show && message) {
            if (!errorElement) {
                errorElement = document.createElement('div');
                errorElement.className = 'field-error';
                fieldContainer.appendChild(errorElement);
            }
            errorElement.textContent = message;
            field.classList.add('error');
        } else {
            if (errorElement) {
                errorElement.remove();
            }
            field.classList.remove('error');
        }
    }

    // Функция валидации в зависимости от действия
    function validateForm(isDraft = false) {
        let isValid = true;

        // Очищаем предыдущие ошибки
        document.querySelectorAll('.field-error').forEach(el => el.remove());
        document.querySelectorAll('.form-control.error').forEach(el => el.classList.remove('error'));

        // Проверяем заголовок (обязателен всегда)
        const title = titleInput ? titleInput.value.trim() : '';
        if (!title) {
            toggleFieldError(titleInput, true, 'Title is required even for drafts.');
            isValid = false;
        }

        // Для публикации нужны дополнительные поля
        if (!isDraft) {
            // Проверяем содержимое статьи
            const content = getEditorContent();
            console.log('Content validation:', content); // Для отладки

            if (!content || content.length < 10) {
                // Более мягкая проверка - минимум 10 символов
                toggleFieldError(fullTextEditor, true, 'Article content is required for publication (minimum 10 characters).');
                isValid = false;
            }

            // Проверяем дату публикации
            const date = dateInput ? dateInput.value.trim() : '';
            if (!date) {
                toggleFieldError(dateInput, true, 'Publication date is required.');
                isValid = false;
            } else {
                const dateRegex = /^\d{4}-\d{2}-\d{2}$/;
                if (!dateRegex.test(date) || !Date.parse(date)) {
                    toggleFieldError(dateInput, true, 'Invalid date format. Please use YYYY-MM-DD.');
                    isValid = false;
                }
            }
        } else {
            // Для черновика автоматически устанавливаем сегодняшнюю дату, если не указана
            if (dateInput && !dateInput.value.trim()) {
                dateInput.value = new Date().toISOString().split('T')[0];
            }
        }

        return isValid;
    }

    // Обработчики кнопок
    if (publishButton) {
        publishButton.addEventListener('click', function(e) {
            console.log('Publish button clicked'); // Для отладки

            // Убираем required атрибуты для проверки
            const requiredFields = form ? form.querySelectorAll('[required]') : [];
            requiredFields.forEach(field => field.removeAttribute('required'));

            // Принудительно синхронизируем TinyMCE с формой
            if (typeof tinymce !== 'undefined' && tinymce.get('full_text')) {
                tinymce.get('full_text').save();
            }

            if (!validateForm(false)) {
                e.preventDefault();
                console.log('Validation failed for publish'); // Для отладки
                // Возвращаем required атрибуты
                requiredFields.forEach(field => field.setAttribute('required', ''));
                return false;
            }

            console.log('Validation passed, submitting form'); // Для отладки
            // Возвращаем required для HTML5 валидации на сервере
            requiredFields.forEach(field => field.setAttribute('required', ''));
        });
    }

    if (saveDraftButton) {
        saveDraftButton.addEventListener('click', function(e) {
            // Убираем все required атрибуты для черновика
            const requiredFields = form ? form.querySelectorAll('[required]') : [];
            requiredFields.forEach(field => field.removeAttribute('required'));

            if (!validateForm(true)) {
                e.preventDefault();
                return false;
            }
        });
    }

    if (updateButton) {
        updateButton.addEventListener('click', function(e) {
            // Проверяем текущий статус статьи
            const currentStatus = document.querySelector('[data-current-status]')?.dataset.currentStatus || 'published';
            const isCurrentlyDraft = currentStatus === 'draft';

            // Убираем required атрибуты для проверки
            const requiredFields = form ? form.querySelectorAll('[required]') : [];
            requiredFields.forEach(field => field.removeAttribute('required'));

            if (!validateForm(isCurrentlyDraft)) {
                e.preventDefault();
                // Возвращаем required атрибуты
                requiredFields.forEach(field => field.setAttribute('required', ''));
                return false;
            }

            // Возвращаем required для валидации на сервере
            requiredFields.forEach(field => field.setAttribute('required', ''));
        });
    }

    // Обработчик отправки формы
    if (form) {
        form.addEventListener('submit', function(e) {
            const clickedButton = document.activeElement;
            const action = clickedButton ? clickedButton.value : 'publish';
            const isDraft = (action === 'save_draft');

            // Убираем required атрибуты для черновиков
            if (isDraft) {
                const requiredFields = form.querySelectorAll('[required]');
                requiredFields.forEach(field => field.removeAttribute('required'));
            }
        });
    }

    // Счетчик символов для заголовка
    if (titleInput && titleCounter) {
        function updateTitleCounter() {
            const length = titleInput.value.length;
            titleCounter.textContent = length;

            // Убираем ошибку при вводе текста
            if (length > 0) {
                toggleFieldError(titleInput, false);
            }
        }

        titleCounter.textContent = titleInput.value.length;
        titleInput.addEventListener('input', updateTitleCounter);
    }

    // Статистика контента
    function updateContentStats() {
        let content = '';

        // Получаем содержимое из редактора
        if (typeof tinymce !== 'undefined' && tinymce.get('full_text')) {
            content = tinymce.get('full_text').getContent({format: 'text'});
        } else if (fullTextEditor) {
            content = fullTextEditor.value;
        }

        const wordCount = content.trim() === '' ? 0 : content.trim().split(/\s+/).length;
        const readTime = Math.max(1, Math.ceil(wordCount / 200));

        if (sidebarWordCount) sidebarWordCount.textContent = wordCount;
        if (sidebarReadTime) sidebarReadTime.textContent = readTime;

        // Убираем ошибку если есть содержимое
        if (content.trim() && fullTextEditor) {
            toggleFieldError(fullTextEditor, false);
        }
    }

    // Обновляем статистику при изменении контента
    if (fullTextEditor) {
        fullTextEditor.addEventListener('input', updateContentStats);
    }

    // Для TinyMCE редактора
    if (typeof tinymce !== 'undefined') {
        tinymce.on('AddEditor', function(e) {
            if (e.editor.id === 'full_text') {
                e.editor.on('input change keyup', updateContentStats);
            }
        });
    }

    // Начальный подсчет
    updateContentStats();

    // Поиск по категориям
    if (categorySearch && categoryOptions.length > 0) {
        categorySearch.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            categoryOptions.forEach(option => {
                const categoryName = option.dataset.category || '';
                const isVisible = categoryName.includes(searchTerm);
                option.style.display = isVisible ? '' : 'none';
            });
        });
    }

    // Обновление выбранных категорий
    function updateSelectedCategories() {
        const selectedCategories = Array.from(categoryCheckboxes)
            .filter(cb => cb.checked)
            .map(cb => cb.closest('.ca-category-option').querySelector('.category-name').textContent);

        if (selectedCategoriesText) {
            selectedCategoriesText.textContent = selectedCategories.length > 0
                ? selectedCategories.join(', ')
                : 'None';
        }
    }

    // Слушатели для чекбоксов категорий
    categoryCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateSelectedCategories);
    });

    // Начальное обновление категорий
    updateSelectedCategories();

    // Прогресс заполнения формы
    function updateProgress() {
        const progressItems = document.querySelectorAll('.ca-progress-item');
        const progressFill = document.querySelector('.ca-progress-fill');
        const progressPercentage = document.getElementById('progressPercentage');

        if (!progressItems.length) return;

        let completedItems = 0;
        const totalItems = progressItems.length;

        progressItems.forEach(item => {
            const target = item.dataset.target;
            let isCompleted = false;

            switch (target) {
                case 'title':
                    isCompleted = titleInput && titleInput.value.trim() !== '';
                    break;
                case 'full_text':
                    isCompleted = getEditorContent() !== '';
                    break;
                case 'short_description':
                    const shortDescEditor = document.getElementById('short_description');
                    if (typeof tinymce !== 'undefined' && tinymce.get('short_description')) {
                        isCompleted = tinymce.get('short_description').getContent({format: 'text'}).trim() !== '';
                    } else if (shortDescEditor) {
                        isCompleted = shortDescEditor.value.trim() !== '';
                    }
                    break;
                case 'categories':
                    isCompleted = Array.from(categoryCheckboxes).some(cb => cb.checked);
                    break;
            }

            const icon = item.querySelector('.ca-progress-icon');
            if (icon) {
                icon.className = isCompleted
                    ? 'fas fa-check-circle ca-progress-icon completed'
                    : 'fas fa-circle ca-progress-icon';
            }

            if (isCompleted) completedItems++;
        });

        const percentage = Math.round((completedItems / totalItems) * 100);

        if (progressFill) {
            progressFill.style.width = percentage + '%';
        }

        if (progressPercentage) {
            progressPercentage.textContent = percentage + '%';
        }
    }

    // Обновляем прогресс при изменениях
    if (titleInput) titleInput.addEventListener('input', updateProgress);
    if (fullTextEditor) fullTextEditor.addEventListener('input', updateProgress);
    categoryCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateProgress);
    });

    // Для TinyMCE редакторов
    if (typeof tinymce !== 'undefined') {
        tinymce.on('AddEditor', function(e) {
            e.editor.on('input change keyup', updateProgress);
        });
    }

    // Начальное обновление прогресса
    updateProgress();

    console.log('Draft system loaded - no unload warnings active');
});
