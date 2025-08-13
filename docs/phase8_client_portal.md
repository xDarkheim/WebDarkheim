# ФАЗА 8: Клиентский портал (В ПРОЦЕССЕ 🔄)

**Дата начала**: 12 августа 2025  
**Статус**: В разработке  
**Цель**: Создание полноценного клиентского портала для управления проектами студии, тикетами поддержки и документооборотом

---

## 📋 ОБЗОР ФАЗЫ

ФАЗА 8 создает комплексный клиентский портал, который трансформирует взаимодействие клиентов со студией. Система включает управление проектами студии, тикеты поддержки, счета, документооборот и планирование встреч.

---

## 🎯 ЦЕЛИ ФАЗЫ

### Основные задачи:
1. **Система тикетов поддержки** - полноценная система обращений клиентов
2. **Управление проектами студии** - клиентский вид активных проектов разработки
3. **Система счетов и платежей** - просмотр инвойсов и финансовой информации
4. **Документооборот** - доступ к проектной документации и файлам
5. **Планирование встреч** - система записи на консультации
6. **Обновленный дашборд** - центральная панель с overview всех активностей

---

## 📁 СОЗДАВАЕМЫЕ ФАЙЛЫ

### 8.1 Система тикетов поддержки
- `page/user/tickets/index.php` - список всех тикетов клиента
- `page/user/tickets/create.php` - создание новых обращений
- `page/user/tickets/view.php` - просмотр и общение по тикету

### 8.2 Управление проектами студии  
- `page/user/projects/index.php` - список проектов клиента в студии
- `page/user/projects/details.php` - детальная информация о проекте
- `page/user/projects/timeline.php` - таймлайн и этапы разработки

### 8.3 Финансы и счета
- `page/user/invoices/index.php` - список счетов и платежей
- `page/user/invoices/download.php` - скачивание PDF инвойсов

### 8.4 Документооборот
- `page/user/documents/index.php` - доступ к проектной документации
- `page/user/documents/download.php` - скачивание файлов

### 8.5 Планирование встреч
- `page/user/meetings/index.php` - запись на консультации
- `page/user/meetings/schedule.php` - планирование встреч

### 8.6 Обновленный дашборд
- `page/user/dashboard.php` - центральная панель клиентского портала

---

## 🗄️ БАЗА ДАННЫХ

### 8.1 Таблицы для тикетов поддержки
```sql
-- Тикеты поддержки
CREATE TABLE support_tickets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    subject VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    priority ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    status ENUM('open', 'in_progress', 'waiting_client', 'resolved', 'closed') DEFAULT 'open',
    category VARCHAR(100),
    assigned_to INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL
);

-- Сообщения в тикетах
CREATE TABLE ticket_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT NOT NULL,
    user_id INT NOT NULL,
    message TEXT NOT NULL,
    is_internal BOOLEAN DEFAULT FALSE,
    attachments JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ticket_id) REFERENCES support_tickets(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

### 8.2 Таблицы для проектов студии
```sql
-- Проекты студии для клиентов
CREATE TABLE studio_projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    project_name VARCHAR(255) NOT NULL,
    project_type ENUM('website', 'mobile_app', 'desktop_app', 'consulting', 'other') NOT NULL,
    description TEXT,
    status ENUM('planning', 'development', 'testing', 'deployment', 'completed', 'on_hold') DEFAULT 'planning',
    start_date DATE,
    estimated_completion DATE,
    actual_completion DATE,
    budget DECIMAL(10,2),
    progress_percentage INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Этапы проектов
CREATE TABLE project_milestones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    due_date DATE,
    completed_at TIMESTAMP NULL,
    status ENUM('pending', 'in_progress', 'completed', 'delayed') DEFAULT 'pending',
    FOREIGN KEY (project_id) REFERENCES studio_projects(id) ON DELETE CASCADE
);
```

### 8.3 Таблицы для финансов
```sql
-- Счета и инвойсы
CREATE TABLE client_invoices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    project_id INT NULL,
    invoice_number VARCHAR(50) UNIQUE NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    tax_amount DECIMAL(10,2) DEFAULT 0,
    total_amount DECIMAL(10,2) NOT NULL,
    due_date DATE NOT NULL,
    status ENUM('draft', 'sent', 'paid', 'overdue', 'cancelled') DEFAULT 'draft',
    payment_date DATE NULL,
    pdf_path VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (project_id) REFERENCES studio_projects(id) ON DELETE SET NULL
);
```

---

## 📊 ПРОГРЕСС ФАЗЫ

### 8.1 Система тикетов поддержки (0%)
- ❌ База данных тикетов
- ❌ Модели и контроллеры  
- ❌ Пользовательские интерфейсы
- ❌ API endpoints

### 8.2 Проекты студии (0%)
- ❌ База данных проектов
- ❌ Система этапов и таймлайна
- ❌ Интерфейсы просмотра

### 8.3 Финансы и счета (0%)
- ❌ База данных инвойсов
- ❌ PDF генерация
- ❌ Интерфейсы управления

### 8.4 Документооборот (0%)
- ❌ Система доступа к файлам
- ❌ Интерфейсы загрузки

### 8.5 Встречи (0%)
- ❌ Система планирования
- ❌ Календарная интеграция

### 8.6 Дашборд (0%)
- ❌ Обновление под новую концепцию

---

## 🔧 АРХИТЕКТУРНЫЕ ПРИНЦИПЫ

### Безопасность
- **ClientAreaMiddleware** - доступ только для аутентифицированных клиентов
- **Проверка владения ресурсами** - клиенты видят только свои данные
- **Разделение проектов** - портфолио клиентов vs проекты студии
- **Логирование активности** - аудит всех действий

### Интеграция
- **Существующая система ролей** - использование client роли
- **ServiceProvider архитектура** - единообразная структура
- **Bootstrap UI** - консистентный дизайн
- **AJAX функциональность** - современный UX

---

## 🚀 СЛЕДУЮЩИЕ ШАГИ

1. **Создать структуру БД** для тикетов и проектов
2. **Разработать систему тикетов** как приоритетную функцию
3. **Создать интерфейсы проектов студии**
4. **Интегрировать с существующей архитектурой**

---

*Документация создана: 12 августа 2025*
