# Spirit Task

Laravel 13 + Vue (Inertia), PHP 8.4, Node.js 24, PostgreSQL 17.
Условия задания находятся в [task/README.md](task/README.md).
Сейчас подготовлены starter kit и Docker-окружение; обработка вебхуков и API заказов ещё не реализованы.

## Запуск с нуля

Нужны Git и запущенный Docker Desktop с Docker Compose. Локальные PHP, Composer и Node.js не требуются.
Выполните из корня клонированного репозитория:

```bash
cp .env.example .env
docker compose build
docker compose run --rm --no-deps app composer install
docker compose run --rm --no-deps app php artisan key:generate
docker compose run --rm --no-deps app npm ci
docker compose run --rm --no-deps app npm run build
docker compose up -d postgres mailpit
docker compose run --rm app php artisan migrate --force
docker compose up -d app worker
```

Если `.env` уже существует, сохраните его. Генерируйте ключ только при пустом `APP_KEY`.
Укажите в `.env` собственный `MARKETPLACE_WEBHOOK_SECRET` для будущего обработчика вебхуков.

- Приложение: http://localhost:8000
- Mailpit (просмотр писем): http://localhost:8026
- PostgreSQL внутри Docker: `postgres:5432`, база `spirit_task`, пользователь `spirit`.
- SMTP внутри Docker: `mailpit:1025`, без авторизации.

Порты приложения и Mailpit задаются `APP_PORT` и `MAILPIT_PORT` в `.env`.
Пароль PostgreSQL задаётся `POSTGRES_PASSWORD` (по умолчанию `local-password`, только для локальной разработки).
Изменение пароля в `.env` не меняет пароль уже созданной базы.
База и SMTP не публикуются на хост; HTTP-порты доступны только на localhost.

Compose задаёт PostgreSQL, SMTP Mailpit и очередь в БД через переменные окружения контейнеров.
Настройки SQLite в `.env.example` остаются для запуска вне Docker и существующего CI.
Зависимости PHP и Node находятся в отдельных Docker volumes: локальные зависимости macOS не используются в Linux.
Исходники подключены из рабочей папки. Используется сервер разработки Laravel; это не конфигурация для production.

## Работа с проектом

```bash
# Статус контейнеров и логи
docker compose ps
docker compose logs --tail=100 app worker

# Пересборка Vue после изменений
docker compose exec app npm run build

# Перезапуск воркера после изменения PHP-кода
docker compose restart worker

# Миграции
docker compose exec app php artisan migrate

# Все проверки, как в CI
docker compose exec app composer ci:check

# Только тесты (SQLite in-memory, PostgreSQL приложения не затрагивается)
docker compose exec app php artisan test

# Остановка с сохранением базы и зависимостей
docker compose down

# Повторный запуск
docker compose up -d
```

После обновления зависимостей выполните `composer install`, `npm ci` и `npm run build` внутри контейнера.
После изменения Dockerfile повторите `docker compose build` и `docker compose up -d`.
Не используйте `docker compose down -v`, если нужно сохранить данные: эта команда удаляет volumes, включая PostgreSQL.
