/**
 * TinyMCE Dark Theme Initialization
 * Darkheim Studio - Production Ready Editor
 */

class DarkheimTinyMCE {
    constructor() {
        this.apiKey = null;
        this.cdnUrl = null;
        this.editorHeight = 450;
        this.editorPreset = 'basic';
        this.initialized = false;
    }

    /**
     * Инициализация редактора с переданными параметрами
     */
    init(config = {}) {
        this.apiKey = config.apiKey || 'no-api-key';
        this.cdnUrl = config.cdnUrl || 'https://cdn.jsdelivr.net/npm/tinymce@6/tinymce.min.js';
        this.editorHeight = config.editorHeight || 450;
        this.editorPreset = config.editorPreset || 'basic';

        console.log("🚀 Darkheim TinyMCE initialization started");
        console.log("📡 CDN URL:", this.cdnUrl);
        console.log("🔑 API Key:", this.apiKey);
        console.log("📐 Height:", this.editorHeight);
        console.log("🎨 Preset:", this.editorPreset);

        // Ждем готовности DOM
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.initializeEditor());
        } else {
            this.initializeEditor();
        }
    }

    /**
     * Основная функция инициализации редактора
     */
    initializeEditor() {
        console.log("✅ DOM ready, checking for TinyMCE...");

        // Проверяем загрузку TinyMCE с повторными попытками
        this.waitForTinyMCE();
    }

    /**
     * Ожидание загрузки библиотеки TinyMCE
     */
    waitForTinyMCE() {
        if (typeof tinymce === "undefined") {
            console.log("⏳ TinyMCE not loaded yet, retrying in 200ms...");
            setTimeout(() => this.waitForTinyMCE(), 200);
            return;
        }

        console.log("✅ TinyMCE library found");
        console.log("📋 TinyMCE version:", tinymce.majorVersion + "." + tinymce.minorVersion);

        this.setupEditor();
    }

    /**
     * Настройка и запуск редактора
     */
    setupEditor() {
        // Удаляем существующие редакторы
        try {
            tinymce.remove();
        } catch(e) {
            console.log("ℹ️ No existing editors to remove");
        }

        // Ищем textarea элементы
        const textareas = document.querySelectorAll(".tinymce-editor");
        console.log("📝 Found " + textareas.length + " textarea elements with .tinymce-editor class");

        if (textareas.length === 0) {
            console.warn("⚠️ No .tinymce-editor elements found");
            return;
        }

        // Логируем найденные элементы
        textareas.forEach((textarea, index) => {
            console.log("📝 Textarea " + (index + 1) + ":", {
                id: textarea.id,
                name: textarea.name,
                className: textarea.className
            });
        });

        // Создаем конфигурацию в зависимости от пресета
        const config = this.createConfig();

        console.log("🔧 Final TinyMCE config:", config);

        // Инициализируем TinyMCE
        tinymce.init(config).then((editors) => {
            console.log("🎯 TinyMCE initialization completed successfully!");
            console.log("📊 Initialized editors count:", editors.length);
            console.log("🎁 Premium features available with API key");

            editors.forEach((editor, index) => {
                console.log("✅ Editor " + (index + 1) + " ready:", editor.id);
            });

            this.initialized = true;
            this.setupStatusCheck();

        }).catch((error) => {
            console.error("❌ TinyMCE initialization failed:", error);
        });
    }

    /**
     * Создание конфигурации TinyMCE в зависимости от пресета
     */
    createConfig() {
        // Базовая конфигурация
        const baseConfig = {
            selector: '.tinymce-editor',
            height: this.editorHeight,

            // ТЕМА И ВНЕШНИЙ ВИД
            skin: 'oxide-dark',
            content_css: [
                'dark',
                '/themes/default/css/components/_tinymce-content-sync.css'
            ],

            // СИНХРОНИЗИРОВАННЫЕ СТИЛИ КОНТЕНТА - ТОЧНО КАК В ОТОБРАЖЕНИИ СТАТЕЙ
            content_style: `
                /* Минимальные базовые стили для совместимости */
                body { 
                    font-family: var(--font-family-sans, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif);
                    font-size: var(--font-size-md, 1rem);
                    line-height: var(--line-height-normal, 1.8);
                    color: var(--color-text-primary, #f8fafc);
                    background-color: var(--color-dark-bg, #0f172a);
                    margin: 12px;
                    max-width: none;
                    word-wrap: break-word;
                    overflow-wrap: break-word;
                }
            `,

            // ОСНОВНЫЕ НАСТРОЙКИ
            branding: false,
            menubar: false,
            statusbar: true,
            contextmenu: 'link image table',

            // PLUGINS
            plugins: 'lists link image table code autoresize wordcount fullscreen searchreplace',

            // АВТОРАЗМЕР
            autoresize_min_height: this.editorHeight,
            autoresize_max_height: 800,

            // ПОДСЧЕТ СЛОВ
            wordcount_countregex: /[\w\u2019'-]+/g,

            // НАСТРОЙКИ КОНТЕНТА
            entity_encoding: 'raw',
            convert_urls: false,
            remove_script_host: false,
            relative_urls: false,

            // НАСТРОЙКИ ВСТАВКИ
            paste_auto_cleanup_on_paste: true,
            paste_remove_spans: true,
            paste_remove_styles: true,
            paste_strip_class_attributes: 'all',

            // СОБЫТИЯ
            setup: (editor) => {
                console.log(`🎯 Setting up editor: ${editor.id}`);

                editor.on('init', () => {
                    console.log(`✅ Editor ${editor.id} initialized successfully`);
                });

                editor.on('change', () => {
                    editor.save();
                });

                editor.on('blur', () => {
                    editor.save();
                });
            }
        };

        // Конфигурация в зависимости от пресета
        switch (this.editorPreset) {
            case 'news':
                return {
                    ...baseConfig,
                    toolbar: 'undo redo | formatselect | bold italic underline strikethrough | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image table | code fullscreen',
                    block_formats: 'Paragraph=p; Heading 1=h1; Heading 2=h2; Heading 3=h3; Heading 4=h4; Preformatted=pre; Quote=blockquote',
                    height: 450
                };

            case 'comment':
                return {
                    ...baseConfig,
                    toolbar: 'undo redo | bold italic | bullist numlist | link | code',
                    plugins: 'lists link code autoresize wordcount',
                    height: 150,
                    autoresize_min_height: 120,
                    autoresize_max_height: 300,
                    menubar: false,
                    statusbar: false
                };

            default:
                return {
                    ...baseConfig,
                    toolbar: 'undo redo | formatselect | bold italic | bullist numlist | link | code',
                    height: 300
                };
        }
    }

    /**
     * Callback при инициализации редактора
     */
    onEditorInit(editor) {
        console.log("🎉 TinyMCE editor initialized successfully:", editor.id);
    }

    /**
     * Настройка событий редактора
     */
    setupEditorCallbacks(editor) {
        editor.on("init", () => {
            console.log("⚡ Editor " + editor.id + " is ready!");
        });

        editor.on("change", () => {
            editor.save();
        });
    }

    /**
     * Финальная проверка статуса через 5 секунд
     */
    setupStatusCheck() {
        setTimeout(() => {
            console.log("🔍 === FINAL STATUS CHECK ===");
            console.log("- TinyMCE loaded:", typeof tinymce !== "undefined");
            console.log("- Active editor:", tinymce?.activeEditor?.id || "none");
            console.log("- Total editors:", tinymce?.editors?.length || 0);
            console.log("- API Key Active:", this.apiKey !== "no-api-key");

            if (typeof tinymce !== "undefined" && tinymce.activeEditor) {
                console.log("🎯 SUCCESS: TinyMCE is working perfectly!");
            } else {
                console.error("⚠️ FAILED: TinyMCE not working after 5 seconds");
            }
        }, 5000);
    }

    /**
     * Получить активный редактор
     */
    getActiveEditor() {
        return typeof tinymce !== "undefined" ? tinymce.activeEditor : null;
    }

    /**
     * Получить все редакторы
     */
    getAllEditors() {
        return typeof tinymce !== "undefined" ? tinymce.editors : [];
    }

    /**
     * Проверка готовности
     */
    isReady() {
        return this.initialized && typeof tinymce !== "undefined";
    }
}

// Глобальный экземпляр для использования
window.DarkheimTinyMCE = DarkheimTinyMCE;
