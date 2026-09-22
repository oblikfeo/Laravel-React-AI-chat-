# Uncensia

Сервис по подписке на нейросети без цензуры.

> The universe has no restrictions. Neither should AI.

## Стек

Laravel 12 и React 19, связанные через Inertia. Оформление на Tailwind, иконки из lucide-react. База PostgreSQL на боевом сервере, SQLite для локальной разработки.

## Структура

| Папка | Что внутри |
|---|---|
| `server/` | Код приложения: Laravel и React |
| `docs/` | Журнал работы, дизайн-система, инструкция по развёртыванию |
| `figma/` | Макеты от дизайнера |
| `assets/` | Прочие материалы от заказчика |

Папка `credentials/` с доступами намеренно не хранится в репозитории.

## Документация

- [Журнал работы](docs/JOURNAL.md) — что и зачем сделано, по датам
- [Дизайн-система](docs/DESIGN-SYSTEM.md) — правила вёрстки для новых страниц
- [Развёртывание](docs/DEPLOY.md) — как выкатывать на боевой сервер

## Запуск на своей машине

```bash
cd server
composer install
npm install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate
npm run build
php artisan serve
```

Приложение откроется на `http://127.0.0.1:8000`.

Для горячей перезагрузки фронтенда нужен Node 20.19 или новее, тогда вместо `npm run build` запускается `npm run dev`.

## Подключение модели

Пока ключ AI-провайдера не задан, приложение отвечает демонстрационной заглушкой, и интерфейс полностью работоспособен.

Чтобы включить настоящие ответы, получите бесплатный ключ на [openrouter.ai](https://openrouter.ai/keys) и добавьте его в `.env`:

```
OPENROUTER_API_KEY=ваш-ключ
```

Формат запросов совместим с OpenAI, поэтому так же подключаются Ollama и OpenAI — меняется только конфигурация в `config/ai.php`.

## Тесты

```bash
cd server
php artisan test
```
