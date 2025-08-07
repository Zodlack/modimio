# Инструкция по установке и запуску

## Предварительные требования

1. PHP 7.0 или выше
2. MySQL или SQLite
3. Composer

## Установка

### 1. Настройка базы данных

Отредактируйте файл `config/db.php`:

```php
<?php
return [
    'class' => 'yii\db\Connection',
    'dsn' => 'mysql:host=localhost;dbname=yii2basic', // или 'sqlite:path/to/database.db'
    'username' => 'root',
    'password' => '',
    'charset' => 'utf8',
];
```

### 2. Установка зависимостей

```bash
composer install
```

### 3. Создание таблиц

Запустите миграцию для создания таблиц:

```bash
# Windows
run-migration.bat

# Linux/Mac
php yii migrate
```

### 4. Загрузка данных из логов

Парсинг файла логов:

```bash
# Windows
parse-logs.bat

# Linux/Mac
php yii parse-logs
```

По умолчанию парсится файл `modimio.access.log.1`. 
Для парсинга другого файла используйте:

```bash
php yii parse-logs /path/to/your/logfile.log
```

### 5. Запуск веб-сервера

```bash
# Встроенный сервер PHP
php -S localhost:8080 -t web

# Или настройте Apache/Nginx
```

### 6. Открытие приложения

Откройте браузер и перейдите по адресу:
```
http://localhost:8080
```

## Структура файлов

- `models/LogEntry.php` - модель для работы с данными
- `controllers/SiteController.php` - контроллер для отображения статистики
- `commands/ParseLogsController.php` - команда для парсинга логов
- `views/site/index.php` - представление с графиками и таблицей
- `migrations/create_log_entries_table.php` - миграция БД

## Возможные проблемы

### Ошибка подключения к БД
Проверьте настройки в `config/db.php` и убедитесь, что база данных существует.

### Ошибка при парсинге логов
Убедитесь, что файл логов существует и имеет правильный формат nginx.

### Ошибка при запуске миграции
Проверьте, что PHP доступен в командной строке и установлены все зависимости.

## Поддержка

При возникновении проблем проверьте:
1. Логи ошибок в `runtime/logs/`
2. Настройки PHP и базы данных
3. Права доступа к файлам
