#!/bin/bash
# Скрипт установки автоматических бэкапов
# Использование: ./install_backup_cron.sh

PROJECT_PATH="/var/www/darkheim.net"
BACKUP_SCRIPT="$PROJECT_PATH/scripts/auto_backup.php"
USER=$(whoami)

echo "🔧 Установка автоматической системы бэкапов..."
echo "=========================================="

# Проверяем существование скрипта
if [ ! -f "$BACKUP_SCRIPT" ]; then
    echo "❌ Ошибка: скрипт бэкапа не найден: $BACKUP_SCRIPT"
    exit 1
fi

# Проверяем права на выполнение
if [ ! -x "$BACKUP_SCRIPT" ]; then
    echo "🔧 Добавляем права на выполнение..."
    chmod +x "$BACKUP_SCRIPT"
fi

# Создаем временный файл crontab
TEMP_CRON=$(mktemp)

# Сохраняем существующий crontab
crontab -l > "$TEMP_CRON" 2>/dev/null || echo "# New crontab" > "$TEMP_CRON"

# Удаляем старые записи бэкапов если есть
sed -i '/auto_backup\.php/d' "$TEMP_CRON"

# Добавляем новые задачи
echo "" >> "$TEMP_CRON"
echo "# Автоматические бэкапы базы данных Darkheim.net" >> "$TEMP_CRON"
echo "# Ежедневный бэкап в 2:00 ночи" >> "$TEMP_CRON"
echo "0 2 * * * /usr/bin/php $BACKUP_SCRIPT >> /var/log/darkheim_backup.log 2>&1" >> "$TEMP_CRON"
echo "" >> "$TEMP_CRON"
echo "# Еженедельная очистка старых бэкапов (воскресенье в 3:00)" >> "$TEMP_CRON"
echo "0 3 * * 0 find $PROJECT_PATH/storage/backups/ -name '*.sql.gz' -mtime +30 -delete" >> "$TEMP_CRON"

# Устанавливаем новый crontab
crontab "$TEMP_CRON"

# Удаляем временный файл
rm "$TEMP_CRON"

# Создаем лог файл если не существует
LOG_FILE="/var/log/darkheim_backup.log"
if [ ! -f "$LOG_FILE" ]; then
    sudo touch "$LOG_FILE"
    sudo chown $USER:$USER "$LOG_FILE"
    sudo chmod 644 "$LOG_FILE"
fi

# Создаем директорию для бэкапов
BACKUP_DIR="$PROJECT_PATH/storage/backups"
if [ ! -d "$BACKUP_DIR" ]; then
    mkdir -p "$BACKUP_DIR"
    chmod 755 "$BACKUP_DIR"
fi

echo "✅ Автоматические бэкапы установлены успешно!"
echo ""
echo "📋 Расписание:"
echo "   - Ежедневные бэкапы: каждый день в 2:00"
echo "   - Очистка старых: каждое воскресенье в 3:00"
echo ""
echo "📁 Лог файл: $LOG_FILE"
echo "💾 Директория бэкапов: $BACKUP_DIR"
echo ""
echo "🔍 Для проверки текущих задач используйте: crontab -l"
echo "📊 Для просмотра логов: tail -f $LOG_FILE"
echo ""
echo "🧪 Для тестирования запустите: $BACKUP_SCRIPT"
