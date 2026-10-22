# Spirit Task

Laravel 13 + Vue (Inertia), PHP 8.4, Node.js 24, PostgreSQL 17.
Условия задания находятся в [task/README.md](task/README.md).
Реализованы вебхуки, хранение заказов, API списка/карточки и уведомления покупателям через очередь.
Принятые допущения описаны в [DECISIONS.md](DECISIONS.md).

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

- Админка заказов (без авторизации): http://localhost:8000/orders
- Главная http://localhost:8000 перенаправляет в админку.
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

## Проверка вебхука

Задайте `MARKETPLACE_WEBHOOK_SECRET` в `.env`, затем перезапустите приложение.
В примере замените `your-local-secret` тем же значением:

```bash
docker compose exec app php artisan migrate
docker compose restart app worker
docker compose exec -e MARKETPLACE_WEBHOOK_SECRET=your-local-secret app php task/examples/send-event.php http://app:8000/api/webhooks/marketplace task/examples/order-paid.json
```

Успех и безопасный повтор возвращают HTTP 204. События сохраняются в `marketplace_events`,
заказы — в `orders`, позиции — в `order_items`, история — в `order_status_histories`.
Отдельно запустить тесты вебхуков:

```bash
docker compose exec app php artisan test tests/Feature/Marketplace
```

### Конкурентные доставки на PostgreSQL

Обычные PHPUnit-тесты используют SQLite в памяти. Для проверки блокировок и повторов
на реальной PostgreSQL создайте **отдельную** тестовую базу (команда `createdb` нужна один раз):

```bash
docker compose exec postgres createdb -U spirit spirit_webhooks_test
docker compose exec -e DB_DATABASE=spirit_webhooks_test app php tests/Integration/check-webhook-concurrency.php
```

Скрипт отказывается работать с другой базой, применяет миграции и запускает 16
параллельных подписанных запросов через HTTP kernel в независимых процессах.
Проверяет дедупликацию, текущий снимок и историю. При повторном запуске использует новые ID.

## API заказов

Авторизация не требуется. Контракт: [task/openapi.yaml](task/openapi.yaml).

```bash
curl 'http://localhost:8000/api/orders?status=paid&per_page=20&page=1'
curl 'http://localhost:8000/api/orders?email=anna.smirnova%40example.com'
curl 'http://localhost:8000/api/orders/ord_7Q2KX9M4'
```

Список возвращает `data`, `links`, `meta`; карточка — `data` с позициями и историей.
Email сравнивается целиком, без учёта регистра. Фильтры можно объединять.
Сортировка — время оформления по убыванию, затем ID по убыванию для стабильного порядка.
Размер страницы по умолчанию 20, максимум 100. Некорректные параметры дают JSON 422,
неизвестный ID — JSON 404. Суммы представлены строками с двумя дробными знаками,
даты — ISO 8601 в UTC.

```bash
docker compose exec app php artisan test tests/Feature/Orders
```

## Уведомления

Воркер отправляет текстовые письма об оплате, передаче в доставку и отмене.
Откройте http://localhost:8026 для просмотра. Повтор события не создаёт новое письмо;
устаревшее уведомление подавляется перед отправкой. Каждое письмо использует актуальные
данные заказа. При отмене оплаченного заказа указан срок возврата 10 дней.

После обновления этой ветки:

```bash
docker compose exec app php artisan migrate
docker compose restart worker
```

Проверка состояния и тесты:

```bash
docker compose exec app php artisan orders:notification-status
docker compose exec app php artisan test tests/Feature/OrderNotifications
```

Статусы `pending`, `sending`, `sent`, `suppressed`, `uncertain` хранятся в
`order_notifications`. `sending` означает активную или прерванную попытку;
`uncertain` — ошибку после начала отправки. Проверьте Mailpit и логи, прежде чем
принимать решение о восстановлении. Автоматической повторной SMTP-отправки таких
попыток нет, чтобы не создать дубль. Ограничения описаны в `DECISIONS.md`.
В database-очереди используется та же БД, что и у заказов; `DB_QUEUE_CONNECTION`
не применяется. После изменения кода писем перезапускайте воркер.

Дополнительно проверить восемь конкурентных воркеров и ровно одно письмо в Mailpit
(тестовая БД `spirit_webhooks_test` создаётся командой из предыдущего раздела):

```bash
docker compose exec -e DB_DATABASE=spirit_webhooks_test app php tests/Integration/check-notification-concurrency.php
```

## Админка заказов

Vue + Inertia: список с фильтрами по статусу и полному email (без учёта регистра),
пагинация и карточка заказа с позициями, суммами, доставкой и историей статусов.
Все даты показаны в UTC. Интерфейс только для просмотра; данные поступают через вебхуки.
Фильтры сохраняются в URL, пустая выдача отображается отдельно.
