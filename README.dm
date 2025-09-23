# README — backend (Slim PHP)

## Стек и версии
- **PHP**: 8.2+ (рекомендовано 8.3)
- **Composer**: 2.x
- **БД**: SQLite (файл `data/app.db`)
- **Web-сервер dev**: встроенный PHP сервер `php -S`
- **Фреймворк**: Slim 4
- **DI**: PHP-DI
- **Dotenv**: vlucas/phpdotenv

## Быстрый старт

```bash
# 1) Установить зависимости
composer install

# 2) Создать директории и БД (если нет)
mkdir -p data
touch data/app.db

# 3) Применить миграции (events + stages)
php scripts/migrate.php

# 4) Запустить dev-сервер
php -S localhost:8080 -t public
# API будет на http://localhost:8080/api
```

## Переменные окружения

Создай файл `.env` в корне бэка:

```
APP_ENV=local
DISPLAY_ERROR_DETAILS=true

DB_DSN=sqlite:./data/app.db
DB_USER=
DB_PASS=

# CORS — откуда можно дергать API (Nuxt dev)
CORS_ORIGIN=http://localhost:3000
```

## Маршруты API

Публичные:
- `GET /api/health` — healthcheck
- `POST /api/login` — вход
- `GET /api/events` — список событий
- `GET /api/events/{id}` — одно событие (со stage)
- `POST /api/subscribe` — подписка

Админ (требует Bearer токен и роль `admin`):
- `POST /api/events` — создать событие
- `PUT /api/events/{id}` — обновить событие
- `PUT /api/events/{id}/status` — сменить статус
- `DELETE /api/events/{id}` — удалить

## Статусы
- `draft`
- `review`
- `published`
- `rejected`

## Структура проекта

```
app/
  Controller/
  Http/
  Repository/
  Service/
config/
public/
scripts/
data/
```

## Миграции (SQL)

```sql
CREATE TABLE IF NOT EXISTS events (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  title TEXT NOT NULL,
  description TEXT,
  date TEXT,
  startTime TEXT,
  endTime TEXT,
  status TEXT DEFAULT 'draft'
);

CREATE TABLE IF NOT EXISTS stages (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  event_id INTEGER NOT NULL,
  title TEXT NOT NULL,
  description TEXT,
  startTime TEXT,
  endTime TEXT,
  FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
);
```

## Частые проблемы
- `no such table: stages` — запусти `php scripts/migrate.php`
- `DI settings not found` — проверь `config/dependencies.php`
- `CORS` — настрой `CORS_ORIGIN` в `.env`

