# Ephphatha

REST API для обліку членів церкви: персональні дані, церковні ролі (член церкви, диякон, пресвітер) та закріплення за пастором і дияконом.

**Стек:** PHP 8.3, Symfony 7.3, Doctrine ORM, PostgreSQL 16, nginx, Docker.

## Запуск (розробка)

```bash
docker compose up -d --build
docker compose exec app composer install
docker compose exec app php bin/console doctrine:migrations:migrate
```

API доступне на `http://localhost:8080`.

## Продакшн

```bash
APP_SECRET=... DATABASE_URL=... POSTGRES_PASSWORD=... \
  docker compose -f docker-compose.prod.yml up -d --build
```

## Тести

Функціональні тести API на PHPUnit (`app/tests/`). Вони працюють з **локальною** базою `ephphatha_test` у контейнері `db` (див. `app/.env.test`), а не з робочою. Перед кожним тестом схема бази створюється заново.

```bash
# один раз: створити тестову базу
docker compose exec app php bin/console doctrine:database:create --env=test --if-not-exists

# запуск
docker compose exec app php vendor/bin/phpunit
```

---

# API

## Загальне

- **Формат запиту:** тіло POST/PUT передається як форма (`application/x-www-form-urlencoded` або `multipart/form-data`), **не JSON**.
- **Формат відповіді:** JSON.
- **Дати:** `YYYY-MM-DD`.
- **Авторизація:** усі маршрути `/api/*` вимагають заголовок
  ```
  Authorization: Bearer <token>
  ```
  Токен видається через `POST /auth`, діє 1 місяць і продовжується ще на місяць при кожному запиті.
- **Помилки** повертаються у вигляді `{"error": "<опис>"}`.

| Код | Значення |
|-----|----------|
| 401 | Немає токена, токен невірний або прострочений; невірні облікові дані |
| 404 | Запис не знайдено |
| 409 | Конфлікт (член уже зареєстрований) |
| 422 | Помилка валідації |

---

## Автентифікація

### `POST /register`

Встановлює пароль для члена, якого адміністратор попередньо додав до бази з email. Зареєструватися можна лише один раз.

| Поле | Тип | Обов'язкове |
|------|-----|-------------|
| `email` | string | так |
| `password` | string | так |

```bash
curl -X POST http://localhost:8080/register \
  -d email=ivan@example.com -d password=secret123
```

**201 Created**
```json
{
  "id": 1,
  "email": "ivan@example.com",
  "name": "Іван Петренко",
  "birthDate": "1990-05-12",
  "baptDate": "2010-08-01",
  "active": 1,
  "registered": true
}
```

Помилки: `422` — не передано email або пароль; `404` — email відсутній у базі; `409` — член уже зареєстрований.

### `POST /auth`

Вхід. Повертає API-токен.

| Поле | Тип | Обов'язкове |
|------|-----|-------------|
| `email` | string | так |
| `password` | string | так |

```bash
curl -X POST http://localhost:8080/auth \
  -d email=ivan@example.com -d password=secret123
```

**200 OK**
```json
{ "token": "3f9a...c1" }
```

Помилки: `422` — не передано email або пароль; `401` — невірні облікові дані.

---

## Ролі

### `GET /api/roles`

Список допустимих церковних ролей.

**200 OK**
```json
["Член церкви", "Диякон", "Пресвітер"]
```

---

## Члени церкви

### Об'єкт `Member`

| Поле | Тип | Опис |
|------|-----|------|
| `id` | int | Ідентифікатор |
| `name` | string | Ім'я |
| `birth_date` | string | Дата народження |
| `bapt_date` | string \| null | Дата хрещення |
| `active` | int | `1` — активний, `0` — неактивний |
| `email` | string \| null | Email |
| `phone` | string | Телефон у форматі `+{цифри}` |
| `address` | string \| null | Адреса |
| `comment` | string \| null | Коментар |
| `role` | string | Церковна роль (див. `/api/roles`) |
| `pastor` | string \| null | Ім'я закріпленого пастора |
| `diakon` | string \| null | Ім'я закріпленого диякона |
| `last_update` | string | Час останньої зміни, `YYYY-MM-DD HH:MM:SS` |

### `GET /api/members`

Список усіх членів.

```bash
curl http://localhost:8080/api/members -H "Authorization: Bearer $TOKEN"
```

**200 OK** — масив об'єктів `Member`.

### `GET /api/members/{id}`

Один член за ідентифікатором.

**201** — об'єкт `Member`. **404** — не знайдено.

### `POST /api/members/add`

Додає нового члена. Роль за замовчуванням — «Член церкви».

| Поле | Тип | Обов'язкове | Опис |
|------|-----|-------------|------|
| `name` | string | так | |
| `birth_date` | date | так | |
| `bapt_date` | date | ні | |
| `email` | string | ні | Має бути валідним email |
| `phone` | string | так | Нормалізується до `+{цифри}` (див. нижче) |
| `address` | string | ні | |
| `comment` | string | ні | |
| `active` | int | ні | За замовчуванням `1` |
| `pastor_id` | int | ні | ID пастора; неіснуючий ID ігнорується |
| `diakon_id` | int | ні | ID диякона; неіснуючий ID ігнорується |

```bash
curl -X POST http://localhost:8080/api/members/add \
  -H "Authorization: Bearer $TOKEN" \
  -d name="Іван Петренко" -d birth_date=1990-05-12 -d phone=+380501234567 -d email=ivan@example.com
```

**201 Created**
```json
{ "id": 1 }
```

Помилки: `422` — не передано обов'язкові поля (`Required fields are missing: name, phone`), невірний формат дати, email або телефону.

**Телефон.** Пробіли, дефіси, дужки та крапки прибираються, префікс `00` замінюється на `+`, на початку завжди `+`. Після цього номер має містити від 7 до 15 цифр, інакше — `422`. Приклад: `+380 (50) 123-45-67` → `+380501234567`.

### `POST|PUT /api/members/edit/{id}`

Часткове оновлення: змінюються лише передані поля. Порожнє значення очищає необов'язкове поле. Обов'язкові поля (`name`, `birth_date`, `phone`) очистити не можна: `422 Fields cannot be empty: ...`.

| Поле | Тип | Опис |
|------|-----|------|
| `name` | string | Не може бути порожнім |
| `birth_date` | date | Не може бути порожнім |
| `bapt_date` | date | Порожнє значення — очистити |
| `email` | string | Порожнє — очистити; інакше має бути валідним |
| `phone` | string | Не може бути порожнім; нормалізується до `+{цифри}` |
| `address` | string | Порожнє — очистити |
| `comment` | string | Порожнє — очистити |
| `role` | string | Одне зі значень `/api/roles` |
| `active` | int | `0` або `1` |
| `pastor_id` | int | Порожнє — відкріпити пастора |
| `diakon_id` | int | Порожнє — відкріпити диякона |

```bash
curl -X PUT http://localhost:8080/api/members/edit/1 \
  -H "Authorization: Bearer $TOKEN" \
  -d role="Диякон" -d phone=+380501234567
```

**200 OK**
```json
{ "status": "success" }
```

Помилки: `404` — член не знайдений; `422` — порожнє обов'язкове поле, невірна дата, email, телефон або роль.

> Для `PUT` тіло треба надсилати як `application/x-www-form-urlencoded`: `multipart/form-data` для `PUT` PHP не розбирає, і поля будуть проігноровані. З `POST` працюють обидва формати.
