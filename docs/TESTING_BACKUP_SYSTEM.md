Д# Тестирование системы бэкапов Darkheim.net

## Обзор

Система тестирования включает комплексную проверку всех компонентов системы бэкапов:
- Unit тесты для `DatabaseBackupController`
- Интеграционные тесты для API эндпоинтов  
- Функциональные тесты для пользовательского интерфейса
- Тесты производительности для оценки быстродействия

## Структура тестов

```
tests/
├── Unit/
│   └── DatabaseBackupControllerTest.php    # Тесты контроллера бэкапов
├── Integration/
│   └── BackupApiTest.php                    # Тесты API эндпоинтов
├── Functional/
│   └── BackupMonitorFunctionalTest.php     # Тесты UI и взаимодействия
└── Performance/
    └── BackupPerformanceTest.php           # Тесты производительности
```

## Запуск тестов

### Все тесты
```bash
./scripts/run_backup_tests.sh
```

### Отдельные группы тестов
```bash
# Только unit тесты
./scripts/run_backup_tests.sh -u

# Только интеграционные тесты  
./scripts/run_backup_tests.sh -i

# Только функциональные тесты
./scripts/run_backup_tests.sh -f

# Только проверки конфигурации
./scripts/run_backup_tests.sh -c
```

### Используя PHPUnit напрямую
```bash
# Все тесты
vendor/bin/phpunit

# Конкретная группа
vendor/bin/phpunit --testsuite "Unit Tests"
vendor/bin/phpunit --testsuite "Integration Tests" 
vendor/bin/phpunit --testsuite "Functional Tests"

# Конкретный тест
vendor/bin/phpunit tests/Unit/DatabaseBackupControllerTest.php
```

## Описание тестов

### Unit тесты (DatabaseBackupControllerTest)

Проверяют бизнес-логику контроллера бэкапов:

- ✅ `testCreateFullBackup()` - создание полных бэкапов
- ✅ `testGetBackupsList()` - получение списка бэкапов
- ✅ `testDeleteBackup()` - удаление файлов бэкапов
- ✅ `testDeleteBackupNonExistentFile()` - обработка ошибок
- ✅ `testCleanupOldBackups()` - очистка старых файлов
- ✅ `testAutoBackup()` - автоматические бэкапы
- ✅ `testPerformBackup()` - выполнение бэкапов
- ✅ `testCreateStructureBackup()` - создание структурных бэкапов

### Интеграционные тесты (BackupApiTest)

Тестируют API эндпоинты системы:

- ✅ `testManualBackupApi()` - API создания ручных бэкапов
- ✅ `testManualBackupApiUnauthorized()` - проверка авторизации
- ✅ `testManualBackupApiWrongMethod()` - валидация HTTP методов
- ✅ `testCleanupOldBackupsApi()` - API очистки старых бэкапов
- ✅ `testDownloadBackupApi()` - скачивание бэкапов
- ✅ `testDownloadBackupApiFileNotFound()` - обработка 404 ошибок
- ✅ `testDownloadBackupApiPathTraversal()` - защита от path traversal
- ✅ `testBackupManagementDeleteApi()` - удаление через API
- ✅ `testApiFlashMessagesIntegration()` - интеграция flash сообщений
- ✅ `testApiErrorHandling()` - обработка ошибок API
- ✅ `testAllApiEndpointsSecurityHeaders()` - проверка безопасности

### Функциональные тесты (BackupMonitorFunctionalTest)

Проверяют пользовательский интерфейс:

- ✅ `testBackupMonitorPageLoads()` - загрузка страницы
- ✅ `testBackupMonitorRequiresAdminAccess()` - контроль доступа
- ✅ `testBackupMonitorDisplaysSystemHealth()` - отображение статуса системы
- ✅ `testBackupMonitorDisplaysStatistics()` - показ статистики
- ✅ `testBackupMonitorDisplaysBackupTable()` - таблица бэкапов
- ✅ `testBackupMonitorJavaScriptFunctions()` - JavaScript функции
- ✅ `testBackupMonitorSidebar()` - боковая панель
- ✅ `testBackupMonitorFlashMessageHandling()` - обработка сообщений
- ✅ `testBackupMonitorResponsiveDesign()` - адаптивный дизайн
- ✅ `testBackupMonitorButtonInteractivity()` - интерактивность кнопок
- ✅ `testBackupMonitorPerformance()` - производительность страницы

### Тесты производительности (BackupPerformanceTest)

Оценивают быстродействие системы:

- ⏱️ `testBackupCreationPerformance()` - скорость создания бэкапов (< 30 сек)
- ⏱️ `testBackupListPerformance()` - получение списка (< 2 сек)  
- ⏱️ `testCleanupPerformance()` - очистка файлов (< 10 сек)
- ⏱️ `testConcurrentOperationsPerformance()` - параллельные операции
- 💾 `testLargeBackupHandling()` - обработка больших файлов
- 💾 `testMemoryEfficiency()` - эффективность памяти

## Результаты тестирования

### Автоматические отчеты
Тесты генерируют отчеты в директории `storage/logs/`:
- `testdox.html` - HTML отчет с результатами
- `junit.xml` - XML отчет для CI/CD
- `clover.xml` - отчет о покрытии кода

### Ручные проверки
Скрипт `run_backup_tests.sh` также выполняет:
- Проверку структуры файлов
- Валидацию конфигурации
- Проверку синтаксиса PHP
- Тестирование API endpoints
- Генерацию сводного отчета

## Критерии успеха

### Функциональность
- ✅ Все API возвращают корректные JSON ответы
- ✅ Файлы бэкапов создаются и сжимаются
- ✅ Система корректно обрабатывает ошибки
- ✅ Flash сообщения работают

### Безопасность  
- 🔒 Проверка авторизации для всех операций
- 🔒 Защита от path traversal атак
- 🔒 Валидация HTTP методов
- 🔒 Корректные HTTP коды ответов

### Производительность
- ⚡ Создание бэкапа: < 30 секунд
- ⚡ Получение списка: < 2 секунд
- ⚡ Очистка файлов: < 10 секунд
- ⚡ Использование памяти: < 100MB

### Пользовательский интерфейс
- 🎨 Страница загружается корректно
- 🎨 Статистика и таблицы отображаются
- 🎨 JavaScript функции работают
- 🎨 Адаптивный дизайн

## Устранение неполадок

### Если тесты не проходят:

1. **Проверьте зависимости:**
   ```bash
   composer install
   ```

2. **Проверьте права на директории:**
   ```bash
   chmod -R 755 storage/
   chown -R www-data:www-data storage/
   ```

3. **Проверьте конфигурацию PHP:**
   ```bash
   php -m | grep -E "(pdo|mysql|gzip|json)"
   ```

4. **Проверьте доступность утилит:**
   ```bash
   which mysqldump gzip
   ```

### Общие проблемы:

- **403/401 ошибки API** - проверьте сессию и права пользователя
- **Ошибки создания файлов** - проверьте права на `storage/backups/`
- **Таймауты тестов** - увеличьте лимиты времени выполнения
- **Ошибки памяти** - увеличьте `memory_limit` в PHP

## Интеграция с CI/CD

Для автоматического запуска в CI/CD добавьте в pipeline:

```yaml
test_backup_system:
  script:
    - composer install
    - ./scripts/run_backup_tests.sh
  artifacts:
    reports:
      junit: storage/logs/junit.xml
    paths:
      - storage/logs/
```

## Заключение

Система тестирования обеспечивает:
- 🧪 **Полное покрытие функционала** - все компоненты протестированы
- 🚀 **Автоматизированный запуск** - простые команды для всех типов тестов  
- 📊 **Детальные отчеты** - HTML и XML отчеты для анализа
- 🔧 **Простая отладка** - понятные сообщения об ошибках
- 📈 **Мониторинг производительности** - контроль скорости и памяти

Все API системы бэкапов теперь полностью протестированы и готовы к использованию на продакшене! 🎉
