# Основа бек-энда для SPA (Symfony 5)

Данный репозиторий содержит следующие эндпоинты:

- регистрация
- аутентификация (получение JWT по email и паролю)
- refresh JWT
- подтверждение email
- сброс пароля
- получение объекта аутентифицированного пользователя по токену
- редактирование ФИО и телефона пользователя
- запрос сброса пароля
- ввод нового пароля

Все эндпоинты описаны в формате OpenApi (Swagger) - /api/v1/

## Минимальные требования:

- PHP 7.4 и выше
- MySQL 5.7 и выше
- Openssl
- Redis

## Установка:

- Склонировать репозиторий
- Установить зависимости:

```bash
composer install
```

- По окончанию установки нужно будет заполнить переменные окружения:
    - `APP_SECRET` - произвольный набор символов
    - `JWT_PASSPHRASE` - произвольный набор символов, будет нужен при генерации ключей для JWT
    - `DATABASE_URL` - настройки для подключения к БД
    - `REDIS_URL` - настройки для подключения к Redis
    - `MAILER_DSN` - настройки для отправки писем
    - `MESSENGER_TRANSPORT_DSN` - настройки асинхронного транспорта
    - `MESSENGER_FAILED_DSN` - настройки транспорта для неуспешных асинхронных задач

## Создать БД и выполнить миграции:

```
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

## Генерация ключей для JWT

```bash
php bin/console lexik:jwt:generate-keypair
```

либо

```bash
openssl genpkey -out config/jwt/private.pem -aes256 -algorithm rsa -pkeyopt rsa_keygen_bits:4096
openssl pkey -in config/jwt/private.pem -out config/jwt/public.pem -pubout
```

В качестве пароля использовать значение переменной `JWT_PASSPHRASE`

## Тестирование

Создать файл `.env.test.local` и прописать в нем переменные окружения:

- `DATABASE_URL` - настройки подключения к тестовой БД MySQL/PG
- `REDIS_URL` - настройки подключения к тестовой БД Redis

```bash
php bin/phpunit
```

## Отправка HTTP-запросов для ручного тестирования

- Создать копии файлов `http-client.env.dist.json` и `http-client.private.env.dist.json`, убрав из названия `.dist`.
- Заполнить значения переменных в этих файлах.
- Запускать запросы, выбрав окружение `dev` или `prod`