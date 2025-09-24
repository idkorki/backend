# Backend (Slim 4)

## Описание
Минималистичный backend на [Slim 4](https://www.slimframework.com/) для управления событиями и подписками.  
Поддерживает аутентификацию, авторизацию и работу с календарём мероприятий.  

Стек:
- PHP 8.2+
- Slim 4
- PHP-DI
- SQLite (по умолчанию, путь в `.env`)
- Composer 2.x

---

## Требования
- PHP >= 8.2  
- Composer >= 2.0  
- SQLite (по умолчанию) или любая другая PDO-совместимая БД  

---

## Установка
```bash
# клонировать репозиторий
git clone https://github.com/<yourname>/backend.git
cd backend

# установить зависимости
composer install

# скопировать конфигурацию
cp .env.example .env
```

---

## Запуск
```bash
composer start
```

По умолчанию сервер поднимется на [http://localhost:8080](http://localhost:8080).

---

## Аутентификация
- Вход: `POST /api/login`  
  Тело запроса:
  ```json
  {
    "email": "admin@local",
    "password": "admin123"
  }
  ```
- Успешный ответ:
  ```json
  {
    "id": 1,
    "email": "admin@local",
    "role": "admin",
    "token": "..."
  }
  ```

Куки `sid` используется для последующих защищённых запросов.  

---

## Работа с событиями
- `GET /api/events` — получить список событий  
- `GET /api/events/{id}` — получить событие по ID  
- `POST /api/events` — создать событие (**только админ**)  
- `PUT /api/events/{id}` — обновить событие (**только админ**)  
- `DELETE /api/events/{id}` — удалить событие (**только админ**)  
- `PUT /api/events/{id}/status` — обновить статус события (**только админ**)  

Пример тела для создания события:
```json
{
  "title": "Конференция",
  "description": "Техническое мероприятие",
  "date": "2025-09-30",
  "startTime": "10:00",
  "endTime": "18:00",
  "status": "draft",
  "stages": [
    {"title": "Регистрация", "time": "10:00"},
    {"title": "Доклады", "time": "11:00"}
  ]
}
```

---

## Подписки
- `POST /api/subscribe`  
  ```json
  {
    "email": "user@example.com"
  }
  ```

---

## Структура проекта
```
app/
  Controller/   # контроллеры (Auth, Events, Subscribe)
  Repository/   # работа с базой (Users, Events, Stages)
  Service/      # бизнес-логика (Auth, Event, Subscribe)

config/
  dependencies.php
  routes.php

public/
  index.php     # точка входа

var/
  app.db        # SQLite база данных

.env            # настройки окружения
```

---

## Тестирование API
Рекомендуется использовать [cURL](https://curl.se/) или [Postman](https://www.postman.com/).

Пример:
```bash
curl -X POST http://localhost:8080/api/login   -H "Content-Type: application/json"   -d '{"email":"admin@local","password":"admin123"}'
```
