# Студия: провайдер генерации

## Почему не OpenRouter

Через OpenRouter генерация изображений недоступна: любая модель с выводом
картинки отвечает `403 The request is prohibited due to a violation of
provider Terms Of Service`. Проверено 01.10 на всех одиннадцати моделях
каталога (Google Gemini, OpenAI GPT Image). Текстовые модели тем же ключом
работают, баланс $49.99 на месте.

В ответе `provider_name: null` — отказ выносит сам OpenRouter до выбора
поставщика, то есть это ограничение аккаунта. Параметр
`provider.data_collection=allow` его не снимает. Кодом не обходится.

## Venice AI

Venice — тот самый сервис, который мы берём за образец. У него собственный
API, один ключ закрывает все вкладки Студии.

Каталог (`GET /api/v1/models?type=all`, открыт без ключа):

| Тип | Моделей | Для чего |
|---|---|---|
| image | 42 | генерация, среди них безцензурные Lustify |
| inpaint | 25 | редактирование области |
| upscale | 1 | увеличение 2x/4x |
| tts | 11 | озвучка, включая ElevenLabs |
| music | 18 | музыка |
| asr | 5 | распознавание речи |
| video | 135 | видео (в плане на потом) |

### Нужные эндпоинты

Базовый адрес `https://api.venice.ai/api/v1`, заголовок
`Authorization: Bearer <ключ>`.

**Вкладка «Изображение»** — `POST /image/generate`

Обязательные: `model`, `prompt`. Остальное: `negative_prompt`,
`aspect_ratio` (1:1, 3:2, 16:9, 21:9, 9:16, 2:3, 3:4, 4:5),
`width`/`height` (до 1280), `variants` (1–4 — кнопка количества),
`style_preset`, `style_references` (вкладка Reference, массив
`{image, weight}`), `seed`, `steps`, `cfg_scale`, `safe_mode`,
`hide_watermark`, `format` (jpeg/png/webp), `enhance_prompt`.

Ответ: `{ id, images: [base64], timing }`. Заголовки предупреждают, если
картинка размыта цензурой (`x-venice-is-blurred`) или нарушает правила.

Список стилей отдаёт `GET /image/styles` — свой список держать не нужно.

**Вкладка «Редактирование»**

- `POST /image/edit` — правка по описанию. `image` (base64 или ссылка),
  `prompt`, `aspect_ratio`, `output_format`.
- `POST /image/multi-edit` — кнопка Combine: `images` (массив), `prompt`,
  `quality` (low/medium/high).
- `POST /image/upscale` — `image`, `scale` (2–4), `creativity`.
- `POST /image/background-remove` — `image` или `image_url`.

**Вкладка «Аудио»**

- `POST /audio/speech` — озвучка текста. `input`, `model` (по умолчанию
  `tts-kokoro`), `voice`, `speed` (0.25–4), `response_format`
  (mp3/opus/aac/flac/wav/pcm), `streaming`. Голоса: `GET /audio/voices`.
- `POST /audio/queue` → `/audio/retrieve` — музыка. `model`, `prompt`,
  `lyrics_prompt`, `duration_seconds`, `force_instrumental`. Долгая
  операция, поэтому через очередь. Цена заранее: `/audio/quote`.
- `POST /audio/voice-changer/queue` — смена голоса в записи.
- `POST /audio/transcriptions` — речь в текст (whisper, scribe и другие).

**Прочее**: `GET /billing/balance` — остаток, `GET /api_keys/rate_limits` —
лимиты. Пригодится для счётчика кредитов в интерфейсе.

## Что нужно от проджект-менеджера

Оплаченный ключ Venice AI (https://venice.ai → Settings → API Keys).
Оплата картой или криптовалютой, тариф с пополняемым балансом (не Pro-
подписка: она даёт доступ к сайту, а не к API). Ориентир по ценам из
каталога: генерация 0.01–0.10 $ за картинку, увеличение 0.02–0.08 $.

Положить в `.env` на сервере:

```
STUDIO_PROVIDER=venice
VENICE_API_KEY=<ключ>
STUDIO_ENABLED=true
```
