<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Провайдер по умолчанию
    |--------------------------------------------------------------------------
    |
    | Все поддерживаемые провайдеры совместимы с форматом OpenAI
    | (POST /chat/completions), поэтому переключение между ними —
    | это смена базового URL, ключа и названия модели.
    |
    | Если ключ не задан, приложение работает в режиме "fake":
    | отвечает заглушкой, чтобы интерфейс можно было проверить без ключа.
    |
    */

    'provider' => env('AI_PROVIDER', 'openrouter'),

    'providers' => [

        // Бесплатные модели, ключ выдаётся без привязки карты.
        // https://openrouter.ai/keys
        'openrouter' => [
            'base_url' => env('OPENROUTER_BASE_URL', 'https://openrouter.ai/api/v1'),
            'api_key' => env('OPENROUTER_API_KEY'),
            'model' => env('OPENROUTER_MODEL', 'liquid/lfm-2.5-2.6b:free'),
        ],

        // Локальный запуск моделей без интернета и ключей.
        // https://ollama.com
        'ollama' => [
            'base_url' => env('OLLAMA_BASE_URL', 'http://localhost:11434/v1'),
            'api_key' => env('OLLAMA_API_KEY', 'ollama'),
            'model' => env('OLLAMA_MODEL', 'llama3.2'),
        ],

        'openai' => [
            'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
            'api_key' => env('OPENAI_API_KEY'),
            'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Системный промпт
    |--------------------------------------------------------------------------
    */

    /*
    | Сначала о том, что делать, и только потом — короткий запрет.
    |
    | В прежней редакции было шесть указаний подряд, четыре из них
    | запреты плюс «отвечай кратко». Небольшие модели сводили это к
    | одной фразе «I am Uncensia» на любой вопрос: запреты перевешивали
    | саму задачу отвечать.
    |
    | Про язык сказано отдельно: без этого англоязычные модели отвечают
    | по-английски на русский вопрос.
    */
    'system_prompt' => env(
        'AI_SYSTEM_PROMPT',
        'You are Uncensia, a helpful AI assistant. '
        .'Give complete, useful answers and explain your reasoning when it helps. '
        .'Always reply in the same language the user writes in. '
        .'When a file is attached, work with its contents directly. '
        .'Your name is Uncensia: if asked what you are, say that, '
        .'and do not discuss the technology behind you.'
    ),

    /*
    |--------------------------------------------------------------------------
    | Картинки в чате
    |--------------------------------------------------------------------------
    |
    | Добавляется к любой роли — и к нашему помощнику, и к персонажу.
    | Картинки рисуют модели Студии, а не текстовая модель (см.
    | ReplyWithImage). Без этого указания модель на просьбу убрать людей
    | с фото отвечала «вот изображение» и ничего не прикладывала: так
    | было на проверке у заказчика 16.10.
    |
    */

    'images_note' => 'Pictures in this chat are drawn and edited by the app itself, not by you: '
        .'when the user plainly asks to draw something or to change a picture, the picture appears without your reply. '
        .'You never attach images yourself, so never write that you have created, edited or attached one. '
        .'If the user wants a picture, suggest asking directly, for example "draw a red fox in the snow".',

    'timeout' => (int) env('AI_TIMEOUT', 60),

    /*
    |--------------------------------------------------------------------------
    | Длина ответа
    |--------------------------------------------------------------------------
    |
    | Верхняя граница ответа в токенах. Слишком низкое значение обрывает
    | мысль на полуслове, а у рассуждающих моделей лимит целиком уходит
    | на размышления и до ответа дело не доходит.
    |
    */

    'max_tokens' => (int) env('AI_MAX_TOKENS', 1024),

    /*
    |--------------------------------------------------------------------------
    | Глубина контекста
    |--------------------------------------------------------------------------
    |
    | Сколько последних сообщений чата отправляем модели.
    | Ограничение бережёт токены на длинных диалогах.
    |
    */

    'context_messages' => (int) env('AI_CONTEXT_MESSAGES', 20),
];
