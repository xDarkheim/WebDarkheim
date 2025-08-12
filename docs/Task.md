## Полная инструкция для ИИ по переработке системы управления контентом сайта студии разработки

### Контекст проекта
Сайт студии разработки на PHP/Composer с существующей системой управления контентом, которую нужно переориентировать с пользовательского контента на административный, добавив при этом расширенные профили клиентов и опциональную возможность добавления проектов в портфолио.

### Текущая структура проекта
- MVC архитектура (Controllers, Models, Views)
- Система маршрутизации через config/routes_config.php
- Middleware для авторизации в src/Application/Middleware/
- API endpoints в page/api/
- Система модерации (database/add_moderation_system.sql)
- Система черновиков (database/add_draft_system.sql)
- Email-уведомления через PHPMailer
- Система бэкапов (scripts/backup.php)
- Темы в themes/default/

### ФАЗА 1: Система ролей и прав доступа

#### Файлы для создания/изменения:
1. `src/Domain/Models/Role.php` - новая модель
2. `src/Domain/Models/User.php` - добавить поле role
3. `src/Domain/Models/Permission.php` - новая модель
4. `database/migration_roles_permissions.sql` - новая миграция

#### Структура БД:
-- Таблицы для создания:
roles (id, name, description)
permissions (id, name, resource, action)
role_permissions (role_id, permission_id)
user_roles (user_id, role_id)

-- Изменение существующей таблицы:
ALTER TABLE users ADD COLUMN role ENUM('admin', 'employee', 'client', 'guest') DEFAULT 'client';

### ФАЗА 2: Middleware и ограничение доступа

#### Создать в src/Application/Middleware/:
1. `AdminOnlyMiddleware.php` - только для админов
2. `RoleMiddleware.php` - проверка ролей
3. `ClientAreaMiddleware.php` - защита клиентских разделов

#### Изменить:
- `config/routes_config.php` - применить middleware к маршрутам
- Контроллеры в src/Application/Controllers/ - добавить проверки ролей

### ФАЗА 3: Расширенный профиль клиента

#### Новые модели в src/Domain/Models/:
1. `ClientProfile.php` - расширенный профиль
2. `ClientProject.php` - проекты клиентов
3. `ProjectModeration.php` - модерация проектов

#### Поля ClientProfile:
- company_name
- position
- bio
- skills (JSON)
- portfolio_visibility (public/private)
- allow_contact
- social_links (JSON)
- website
- location

#### Новые таблицы БД:
-- client_profiles: расширенные данные профиля
-- client_portfolio: проекты клиентов (опционально)
-- client_skills: навыки/технологии клиента
-- client_social_links: социальные сети клиента
-- project_views: статистика просмотров проектов

### ФАЗА 4: Система портфолио клиентов

#### Создать структуру:
page/user/portfolio/
├── add_project.php - форма добавления проекта
├── edit_project.php - редактирование проекта
├── my_projects.php - список проектов клиента
├── project_stats.php - статистика просмотров
└── project_settings.php - настройки видимости

page/public/client/
├── profile.php - публичный профиль клиента
└── portfolio.php - публичное портфолио

#### Модификация существующих файлов:
- `page/public/projects.php` - добавить фильтр "Проекты студии" / "Проекты сообщества"
- `page/user/dashboard.php` - трансформировать в клиентский портал

### ФАЗА 5: Переработка системы контента

#### Удалить/переименовать:
- page/user/content/ → удалить функционал создания статей
- Убрать формы создания контента из публичной части

#### Изменить:
- `database/add_moderation_system.sql` - адаптировать под модерацию комментариев и проектов клиентов
- `database/add_draft_system.sql` - использовать только для админов и проектов клиентов

### ФАЗА 6: Система комментариев

#### Создать:
1. `src/Domain/Models/Comment.php` - модель комментариев
2. `src/Application/Controllers/CommentController.php` - контроллер
3. `src/Application/Services/CommentService.php` - бизнес-логика
4. `database/create_comments_table.sql` - таблица комментариев

#### API endpoints в page/api/:
comments/
├── create.php
├── update.php
├── delete.php
├── moderate.php
└── get_thread.php

### ФАЗА 7: Клиентский портал
#### Трансформировать page/user/:
dashboard.php - главная страница клиента
projects/ - проекты студии для клиента
├── index.php - список проектов
├── details.php - детали проекта
└── timeline.php - таймлайн проекта
tickets/ - система поддержки
├── index.php - список тикетов
├── create.php - создать тикет
└── view.php - просмотр тикета
invoices/ - финансы
├── index.php - список счетов
└── download.php - скачать PDF
documents/ - документация
meetings/ - планирование встреч

### ФАЗА 8: API модификации

#### Изменить существующие:
- `page/api/filter_articles.php` - только чтение для всех
- Удалить методы создания/редактирования для обычных пользователей

#### Создать новые в page/api/:
portfolio/
├── create_project.php
├── update_project.php
├── upload_images.php
├── get_client_projects.php
└── toggle_visibility.php

client/
├── profile_update.php
├── skills_update.php
└── social_links.php

notifications/
├── get_unread.php
├── mark_read.php
└── preferences.php

### ФАЗА 9: Обновление навигации и UI

#### Изменить resources/views/_main_navigation.php:
- Убрать для клиентов: "Создать статью", "Мои публикации"
- Добавить для клиентов: "Мой профиль", "Портфолио", "Проекты", "Поддержка"
- Добавить для всех: "Сообщество" (проекты клиентов)

#### Создать новые views:
resources/views/
├── client/
│   ├── profile_form.php
│   ├── project_form.php
│   └── portfolio_grid.php
├── moderation/
│   ├── project_review.php
│   └── comment_review.php
└── public/
├── client_profile.php
└── community_projects.php

### ФАЗА 10: Email шаблоны и уведомления

#### Переработать в resources/views/emails/:
- Удалить шаблоны публикации статей пользователей
- Создать новые:
    - project_status_update.php - изменение статуса проекта клиента
    - portfolio_moderation.php - результат модерации портфолио
    - new_comment_notification.php - новый комментарий
    - ticket_response.php - ответ на тикет

### ФАЗА 11: Сервисы и бизнес-логика

#### Создать в src/Application/Services/:
1. `ClientProfileService.php` - управление профилями
2. `PortfolioService.php` - управление портфолио клиентов
3. `ProjectModerationService.php` - модерация проектов
4. `NotificationService.php` - система уведомлений
5. `TicketService.php` - система поддержки
6. `InvoiceService.php` - генерация счетов

### ФАЗА 12: Административная панель

#### Создать в page/admin/:
1. `moderate_projects.php` - модерация проектов клиентов
2. `moderate_comments.php` - модерация комментариев
3. `client_management.php` - управление клиентами
4. `portfolio_settings.php` - настройки системы портфолио

### Последовательность реализации

1. Неделя 1-2: Система ролей и middleware
2. Неделя 3-4: Расширенные профили клиентов
3. Неделя 5-6: Система портфолио с модерацией
4. Неделя 7: Переработка существующего контента
5. Неделя 8-9: Система комментариев
6. Неделя 10-11: Клиентский портал
7. Неделя 12: Финальная интеграция и тестирование

### Критически важные проверки

- [ ] Обычные пользователи НЕ могут создавать новости/статьи
- [ ] Админы имеют полный доступ к управлению контентом
- [ ] Клиенты могут добавлять проекты в портфолио (с модерацией)
- [ ] Публичная страница проектов корректно разделяет типы
- [ ] Система модерации работает для комментариев и проектов
- [ ] Email-уведомления отправляются корректно
- [ ] Профили клиентов имеют настройки приватности
- [ ] API endpoints защищены соответствующими правами

### Что сохранить без изменений
- Система бэкапов (scripts/backup.php)
- Мониторинг (page/admin/system_monitor.php)
- SEO-функционал
- Структура MVC
- Система маршрутизации
- Темы (themes/default/)
- Composer зависимости

### Дополнительные замечания
- Портфолио клиентов - опциональная функция (можно отключить в настройках)
- Все проекты клиентов требуют премодерации перед публикацией
- Сохранить чёткое разделение между официальными проектами студии и портфолио клиентов
- Использовать существующую инфраструктуру максимально эффективно
- Применить существующую систему категорий для тегирования проектов клиентов