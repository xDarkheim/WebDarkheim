/**
 * Custom Visual Text Editor - Визуальный редактор текста с панелью инструментов
 * Имитирует интерфейс Microsoft Word с кнопками форматирования
 */

class CustomTextEditor {
    constructor(containerId, options = {}) {
        this.container = document.getElementById(containerId);
        this.options = {
            height: options.height || '300px',
            placeholder: options.placeholder || 'Начните печатать...',
            showPreview: options.showPreview || false,
            showWordCount: options.showWordCount || true,
            allowedFormats: options.allowedFormats || ['bold', 'italic', 'underline', 'heading', 'list', 'link'],
            ...options
        };
        
        this.textarea = null;
        this.toolbar = null;
        this.preview = null;
        this.statusBar = null;
        this.currentSelection = null;
        
        this.init();
    }

    init() {
        this.createEditor();
        this.bindEvents();
        this.updateUI();
    }

    createEditor() {
        // Создаем основной контейнер редактора
        this.container.innerHTML = `
            <div class="custom-text-editor-container">
                <!-- Панель инструментов -->
                <div class="custom-editor-toolbar">
                    <!-- Группа базового форматирования -->
                    <div class="custom-editor-toolbar-group">
                        <button type="button" class="custom-editor-toolbar-button" data-action="bold" title="Жирный (Ctrl+B)">
                            <i class="fas fa-bold"></i>
                        </button>
                        <button type="button" class="custom-editor-toolbar-button" data-action="italic" title="Курсив (Ctrl+I)">
                            <i class="fas fa-italic"></i>
                        </button>
                        <button type="button" class="custom-editor-toolbar-button" data-action="underline" title="Подчеркнутый (Ctrl+U)">
                            <i class="fas fa-underline"></i>
                        </button>
                        <button type="button" class="custom-editor-toolbar-button" data-action="strikethrough" title="Зачеркнутый">
                            <i class="fas fa-strikethrough"></i>
                        </button>
                    </div>

                    <!-- Группа заголовков -->
                    <div class="custom-editor-toolbar-group">
                        <div class="custom-editor-toolbar-dropdown">
                            <select class="custom-editor-dropdown-select" data-action="heading" title="Стиль заголовка">
                                <option value="">Обычный текст</option>
                                <option value="h1">Заголовок 1</option>
                                <option value="h2">Заголовок 2</option>
                                <option value="h3">Заголовок 3</option>
                                <option value="h4">Заголовок 4</option>
                            </select>
                        </div>
                    </div>

                    <!-- Группа размера шрифта -->
                    <div class="custom-editor-toolbar-group">
                        <div class="custom-editor-toolbar-dropdown">
                            <select class="custom-editor-dropdown-select" data-action="fontSize" title="Размер шрифта">
                                <option value="12px">12px</option>
                                <option value="14px" selected>14px</option>
                                <option value="16px">16px</option>
                                <option value="18px">18px</option>
                                <option value="20px">20px</option>
                                <option value="24px">24px</option>
                                <option value="32px">32px</option>
                            </select>
                        </div>
                    </div>

                    <!-- Группа цветов -->
                    <div class="custom-editor-toolbar-group">
                        <div class="custom-editor-color-picker" title="Цвет текста">
                            <input type="color" class="custom-editor-color-preview" data-action="textColor" value="#000000">
                            <i class="fas fa-font custom-editor-color-icon"></i>
                        </div>
                        <div class="custom-editor-color-picker" title="Цвет фона">
                            <input type="color" class="custom-editor-color-preview" data-action="backgroundColor" value="#ffffff">
                            <i class="fas fa-fill-drip custom-editor-color-icon"></i>
                        </div>
                    </div>

                    <!-- Группа выравнивания -->
                    <div class="custom-editor-toolbar-group">
                        <button type="button" class="custom-editor-toolbar-button" data-action="alignLeft" title="По левому краю">
                            <i class="fas fa-align-left"></i>
                        </button>
                        <button type="button" class="custom-editor-toolbar-button" data-action="alignCenter" title="По центру">
                            <i class="fas fa-align-center"></i>
                        </button>
                        <button type="button" class="custom-editor-toolbar-button" data-action="alignRight" title="По правому краю">
                            <i class="fas fa-align-right"></i>
                        </button>
                        <button type="button" class="custom-editor-toolbar-button" data-action="alignJustify" title="По ширине">
                            <i class="fas fa-align-justify"></i>
                        </button>
                    </div>

                    <!-- Группа списков -->
                    <div class="custom-editor-toolbar-group">
                        <button type="button" class="custom-editor-toolbar-button" data-action="bulletList" title="Маркированный список">
                            <i class="fas fa-list-ul"></i>
                        </button>
                        <button type="button" class="custom-editor-toolbar-button" data-action="numberedList" title="Нумерованный список">
                            <i class="fas fa-list-ol"></i>
                        </button>
                        <button type="button" class="custom-editor-toolbar-button" data-action="outdent" title="Уменьшить отступ">
                            <i class="fas fa-outdent"></i>
                        </button>
                        <button type="button" class="custom-editor-toolbar-button" data-action="indent" title="Увеличить отступ">
                            <i class="fas fa-indent"></i>
                        </button>
                    </div>

                    <!-- Группа дополнительных функций -->
                    <div class="custom-editor-toolbar-group">
                        <button type="button" class="custom-editor-toolbar-button" data-action="link" title="Вставить ссылку">
                            <i class="fas fa-link"></i>
                        </button>
                        <button type="button" class="custom-editor-toolbar-button" data-action="blockquote" title="Цитата">
                            <i class="fas fa-quote-left"></i>
                        </button>
                        <button type="button" class="custom-editor-toolbar-button" data-action="code" title="Код">
                            <i class="fas fa-code"></i>
                        </button>
                    </div>

                    <!-- Группа управления -->
                    <div class="custom-editor-toolbar-group">
                        <button type="button" class="custom-editor-toolbar-button" data-action="undo" title="Отменить (Ctrl+Z)">
                            <i class="fas fa-undo"></i>
                        </button>
                        <button type="button" class="custom-editor-toolbar-button" data-action="redo" title="Повторить (Ctrl+Y)">
                            <i class="fas fa-redo"></i>
                        </button>
                        <button type="button" class="custom-editor-toolbar-button" data-action="clear" title="Очистить форматирование">
                            <i class="fas fa-remove-format"></i>
                        </button>
                    </div>

                    <!-- Группа просмотра -->
                    <div class="custom-editor-toolbar-group">
                        <button type="button" class="custom-editor-toolbar-button" data-action="preview" title="Предварительный просмотр">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button type="button" class="custom-editor-toolbar-button" data-action="fullscreen" title="Полноэкранный режим">
                            <i class="fas fa-expand"></i>
                        </button>
                    </div>
                </div>

                <!-- Область редактирования -->
                <textarea class="custom-editor-textarea" 
                          placeholder="${this.options.placeholder}"
                          style="min-height: ${this.options.height}"></textarea>

                <!-- Предварительный просмотр (скрыт по умолчанию) -->
                <div class="custom-editor-preview" style="display: none;">
                    <h4>Предварительный просмотр:</h4>
                    <div class="preview-content"></div>
                </div>

                <!-- Статус-бар -->
                <div class="custom-editor-status-bar">
                    <div class="custom-editor-word-count">
                        Слов: <span class="word-count">0</span> | Символов: <span class="char-count">0</span>
                    </div>
                    <div class="custom-editor-format-info">
                        <span class="current-format">Обычный текст</span>
                    </div>
                </div>
            </div>
        `;

        // Получаем ссылки на элементы
        this.textarea = this.container.querySelector('.custom-editor-textarea');
        this.toolbar = this.container.querySelector('.custom-editor-toolbar');
        this.preview = this.container.querySelector('.custom-editor-preview');
        this.statusBar = this.container.querySelector('.custom-editor-status-bar');
    }

    bindEvents() {
        // Обработчики для кнопок панели инструментов
        this.toolbar.addEventListener('click', (e) => {
            const button = e.target.closest('[data-action]');
            if (button) {
                e.preventDefault();
                this.executeCommand(button.dataset.action, button);
            }
        });

        // Обработчики для выпадающих списков
        this.toolbar.addEventListener('change', (e) => {
            if (e.target.matches('[data-action]')) {
                this.executeCommand(e.target.dataset.action, e.target);
            }
        });

        // Обработчики клавиатурных сокращений
        this.textarea.addEventListener('keydown', (e) => {
            this.handleKeyboardShortcuts(e);
        });

        // Обновление UI при изменении текста
        this.textarea.addEventListener('input', () => {
            this.updateWordCount();
            this.updatePreview();
        });

        // Обновление UI при изменении позиции курсора
        this.textarea.addEventListener('selectionchange', () => {
            this.updateCurrentFormat();
        });

        // Сохранение позиции курсора
        this.textarea.addEventListener('select', () => {
            this.saveSelection();
        });
    }

    executeCommand(action, element) {
        this.restoreSelection();
        
        switch (action) {
            case 'bold':
                this.wrapSelection('**', '**', 'жирный текст');
                break;
            case 'italic':
                this.wrapSelection('*', '*', 'курсив');
                break;
            case 'underline':
                this.wrapSelection('<u>', '</u>', 'подчеркнутый');
                break;
            case 'strikethrough':
                this.wrapSelection('~~', '~~', 'зачеркнутый');
                break;
            case 'heading':
                this.applyHeading(element.value);
                break;
            case 'fontSize':
                this.applyFontSize(element.value);
                break;
            case 'textColor':
                this.applyTextColor(element.value);
                break;
            case 'backgroundColor':
                this.applyBackgroundColor(element.value);
                break;
            case 'alignLeft':
            case 'alignCenter':
            case 'alignRight':
            case 'alignJustify':
                this.applyAlignment(action);
                break;
            case 'bulletList':
                this.createList('*');
                break;
            case 'numberedList':
                this.createList('1.');
                break;
            case 'indent':
                this.adjustIndent(true);
                break;
            case 'outdent':
                this.adjustIndent(false);
                break;
            case 'link':
                this.insertLink();
                break;
            case 'blockquote':
                this.wrapSelection('\n> ', '', 'цитата');
                break;
            case 'code':
                this.wrapSelection('`', '`', 'код');
                break;
            case 'undo':
                document.execCommand('undo');
                break;
            case 'redo':
                document.execCommand('redo');
                break;
            case 'clear':
                this.clearFormatting();
                break;
            case 'preview':
                this.togglePreview();
                break;
            case 'fullscreen':
                this.toggleFullscreen();
                break;
        }

        this.updateUI();
        this.textarea.focus();
    }

    wrapSelection(before, after, placeholder = '') {
        const start = this.textarea.selectionStart;
        const end = this.textarea.selectionEnd;
        const text = this.textarea.value;
        const selectedText = text.substring(start, end) || placeholder;
        
        const newText = text.substring(0, start) + before + selectedText + after + text.substring(end);
        this.textarea.value = newText;
        
        // Устанавливаем курсор
        const newCursorPos = start + before.length + selectedText.length;
        this.textarea.setSelectionRange(newCursorPos, newCursorPos);
    }

    applyHeading(level) {
        if (!level) return;
        
        const marker = '#'.repeat(parseInt(level.replace('h', ''))) + ' ';
        this.insertAtLineStart(marker);
    }

    applyFontSize(size) {
        this.wrapSelection(`<span style="font-size: ${size}">`, '</span>', 'текст');
    }

    applyTextColor(color) {
        this.wrapSelection(`<span style="color: ${color}">`, '</span>', 'цветной текст');
    }

    applyBackgroundColor(color) {
        this.wrapSelection(`<span style="background-color: ${color}">`, '</span>', 'выделенный текст');
    }

    applyAlignment(alignment) {
        const alignMap = {
            'alignLeft': 'left',
            'alignCenter': 'center', 
            'alignRight': 'right',
            'alignJustify': 'justify'
        };
        
        const align = alignMap[alignment];
        this.wrapSelection(`<div style="text-align: ${align}">`, '</div>', 'выровненный текст');
    }

    createList(marker) {
        const lines = this.getSelectedLines();
        const newLines = lines.map(line => {
            if (line.trim()) {
                return marker + ' ' + line.replace(/^[\*\-\+\d\.]\s*/, '');
            }
            return line;
        });
        
        this.replaceSelectedLines(newLines);
    }

    insertLink() {
        const url = prompt('Введите URL ссылки:');
        if (url) {
            const start = this.textarea.selectionStart;
            const end = this.textarea.selectionEnd;
            const selectedText = this.textarea.value.substring(start, end) || 'ссылка';
            
            this.wrapSelection(`[${selectedText}](`, `${url})`, '');
        }
    }

    insertAtLineStart(marker) {
        const start = this.textarea.selectionStart;
        const text = this.textarea.value;
        const lineStart = text.lastIndexOf('\n', start - 1) + 1;
        
        const newText = text.substring(0, lineStart) + marker + text.substring(lineStart);
        this.textarea.value = newText;
        this.textarea.setSelectionRange(start + marker.length, start + marker.length);
    }

    getSelectedLines() {
        const start = this.textarea.selectionStart;
        const end = this.textarea.selectionEnd;
        const text = this.textarea.value;
        
        const lineStart = text.lastIndexOf('\n', start - 1) + 1;
        const lineEnd = text.indexOf('\n', end);
        const selectedText = text.substring(lineStart, lineEnd === -1 ? text.length : lineEnd);
        
        return selectedText.split('\n');
    }

    replaceSelectedLines(newLines) {
        const start = this.textarea.selectionStart;
        const end = this.textarea.selectionEnd;
        const text = this.textarea.value;
        
        const lineStart = text.lastIndexOf('\n', start - 1) + 1;
        const lineEnd = text.indexOf('\n', end);
        const actualLineEnd = lineEnd === -1 ? text.length : lineEnd;
        
        const newText = text.substring(0, lineStart) + newLines.join('\n') + text.substring(actualLineEnd);
        this.textarea.value = newText;
    }

    handleKeyboardShortcuts(e) {
        if (e.ctrlKey || e.metaKey) {
            switch (e.key.toLowerCase()) {
                case 'b':
                    e.preventDefault();
                    this.executeCommand('bold');
                    break;
                case 'i':
                    e.preventDefault();
                    this.executeCommand('italic');
                    break;
                case 'u':
                    e.preventDefault();
                    this.executeCommand('underline');
                    break;
                case 'k':
                    e.preventDefault();
                    this.executeCommand('link');
                    break;
            }
        }
    }

    saveSelection() {
        this.currentSelection = {
            start: this.textarea.selectionStart,
            end: this.textarea.selectionEnd
        };
    }

    restoreSelection() {
        if (this.currentSelection) {
            this.textarea.setSelectionRange(
                this.currentSelection.start,
                this.currentSelection.end
            );
        }
    }

    updateWordCount() {
        const text = this.textarea.value;
        const words = text.trim() ? text.trim().split(/\s+/).length : 0;
        const chars = text.length;
        
        const wordCountEl = this.statusBar.querySelector('.word-count');
        const charCountEl = this.statusBar.querySelector('.char-count');
        
        if (wordCountEl) wordCountEl.textContent = words;
        if (charCountEl) charCountEl.textContent = chars;
    }

    updatePreview() {
        if (this.options.showPreview) {
            const content = this.convertToHTML(this.textarea.value);
            const previewContent = this.preview.querySelector('.preview-content');
            if (previewContent) {
                previewContent.innerHTML = content;
            }
        }
    }

    updateCurrentFormat() {
        // Определяем текущий формат текста в позиции курсора
        const formatEl = this.statusBar.querySelector('.current-format');
        if (formatEl) {
            formatEl.textContent = this.detectCurrentFormat();
        }
        
        // Обновляем состояние кнопок
        this.updateToolbarButtons();
    }

    detectCurrentFormat() {
        const start = this.textarea.selectionStart;
        const text = this.textarea.value;
        const currentLine = this.getCurrentLine();
        
        if (currentLine.match(/^#{1,6}\s/)) {
            const level = currentLine.match(/^(#{1,6})/)[1].length;
            return `Заголовок ${level}`;
        }
        
        if (currentLine.match(/^\*\s/) || currentLine.match(/^\-\s/) || currentLine.match(/^\+\s/)) {
            return 'Маркированный список';
        }
        
        if (currentLine.match(/^\d+\.\s/)) {
            return 'Нумерованный список';
        }
        
        return 'Обычный текст';
    }

    getCurrentLine() {
        const start = this.textarea.selectionStart;
        const text = this.textarea.value;
        const lineStart = text.lastIndexOf('\n', start - 1) + 1;
        const lineEnd = text.indexOf('\n', start);
        
        return text.substring(lineStart, lineEnd === -1 ? text.length : lineEnd);
    }

    updateToolbarButtons() {
        // Обновляем состояние кнопок на основе текущего форматирования
        const buttons = this.toolbar.querySelectorAll('[data-action]');
        
        buttons.forEach(button => {
            button.classList.remove('active');
        });
        
        // Проверяем активные форматы и обновляем кнопки
        this.checkActiveFormats();
    }

    checkActiveFormats() {
        const selectedText = this.getSelectedText();
        const currentLine = this.getCurrentLine();
        
        // Проверяем жирный текст
        if (selectedText.includes('**') || selectedText.includes('<strong>')) {
            this.setButtonActive('bold');
        }
        
        // Проверяем курсив
        if (selectedText.includes('*') || selectedText.includes('<em>')) {
            this.setButtonActive('italic');
        }
        
        // Проверяем заголовки
        if (currentLine.match(/^#{1,6}\s/)) {
            this.setButtonActive('heading');
        }
    }

    getSelectedText() {
        const start = this.textarea.selectionStart;
        const end = this.textarea.selectionEnd;
        return this.textarea.value.substring(start, end);
    }

    setButtonActive(action) {
        const button = this.toolbar.querySelector(`[data-action="${action}"]`);
        if (button) {
            button.classList.add('active');
        }
    }

    convertToHTML(text) {
        // Простой конвертер Markdown в HTML для предварительного просмотра
        return text
            .replace(/^### (.*$)/gim, '<h3>$1</h3>')
            .replace(/^## (.*$)/gim, '<h2>$1</h2>')
            .replace(/^# (.*$)/gim, '<h1>$1</h1>')
            .replace(/\*\*(.*)\*\*/gim, '<strong>$1</strong>')
            .replace(/\*(.*)\*/gim, '<em>$1</em>')
            .replace(/!\[(.*?)\]\((.*?)\)/gim, '<img alt="$1" src="$2" />')
            .replace(/\[(.*?)\]\((.*?)\)/gim, '<a href="$2">$1</a>')
            .replace(/\n$/gim, '<br />');
    }

    togglePreview() {
        const isVisible = this.preview.style.display !== 'none';
        this.preview.style.display = isVisible ? 'none' : 'block';
        
        const button = this.toolbar.querySelector('[data-action="preview"]');
        if (button) {
            button.classList.toggle('active', !isVisible);
        }
        
        if (!isVisible) {
            this.updatePreview();
        }
    }

    toggleFullscreen() {
        this.container.classList.toggle('fullscreen-editor');
        
        const button = this.toolbar.querySelector('[data-action="fullscreen"]');
        if (button) {
            const icon = button.querySelector('i');
            if (this.container.classList.contains('fullscreen-editor')) {
                icon.className = 'fas fa-compress';
                button.title = 'Выйти из полноэкранного режима';
            } else {
                icon.className = 'fas fa-expand';
                button.title = 'Полноэкранный режим';
            }
        }
    }

    clearFormatting() {
        const start = this.textarea.selectionStart;
        const end = this.textarea.selectionEnd;
        const selectedText = this.textarea.value.substring(start, end);
        
        // Убираем все форматирование
        const cleanText = selectedText
            .replace(/\*\*(.*?)\*\*/g, '$1')
            .replace(/\*(.*?)\*/g, '$1')
            .replace(/<[^>]*>/g, '')
            .replace(/~~(.*?)~~/g, '$1')
            .replace(/`(.*?)`/g, '$1');
        
        this.textarea.value = this.textarea.value.substring(0, start) + cleanText + this.textarea.value.substring(end);
        this.textarea.setSelectionRange(start, start + cleanText.length);
    }

    updateUI() {
        this.updateWordCount();
        this.updateCurrentFormat();
        if (this.preview.style.display !== 'none') {
            this.updatePreview();
        }
    }

    // Публичные методы для внешнего использования
    getValue() {
        return this.textarea.value;
    }

    setValue(value) {
        this.textarea.value = value;
        this.updateUI();
    }

    focus() {
        this.textarea.focus();
    }

    getHTMLContent() {
        return this.convertToHTML(this.textarea.value);
    }
}

// Инициализация редакторов при загрузке страницы
document.addEventListener('DOMContentLoaded', function() {
    // Автоматически инициализируем все элементы с классом custom-text-editor
    const editorContainers = document.querySelectorAll('.custom-text-editor');
    
    editorContainers.forEach((container, index) => {
        const editorId = container.id || `custom-editor-${index}`;
        container.id = editorId;
        
        const options = {
            height: container.dataset.height || '300px',
            placeholder: container.dataset.placeholder || 'Начните печатать...',
            showPreview: container.dataset.showPreview === 'true',
            showWordCount: container.dataset.showWordCount !== 'false'
        };
        
        new CustomTextEditor(editorId, options);
    });
});

// Экспорт для использования в модулях
if (typeof module !== 'undefined' && module.exports) {
    module.exports = CustomTextEditor;
}
