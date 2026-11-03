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

    'provider' => env('AI_PROVIDER', 'venice'),

    /*
     * extra — что добавить в каждый запрос: у провайдеров свои
     * параметры сверх общего формата.
     */
    'providers' => [

        // Тот же провайдер, что у Студии: один ключ и один счёт.
        // Свой системный запрос Venice выключен — у нас свой.
        'venice' => [
            'base_url' => env('VENICE_BASE_URL', 'https://api.venice.ai/api/v1'),
            'api_key' => env('VENICE_API_KEY'),
            'model' => env('VENICE_TEXT_MODEL', 'google-gemma-3-27b-it'),
            'extra' => [
                'venice_parameters' => ['include_venice_system_prompt' => false],
            ],
        ],

        // Бесплатные модели, ключ выдаётся без привязки карты.
        // https://openrouter.ai/keys
        'openrouter' => [
            'base_url' => env('OPENROUTER_BASE_URL', 'https://openrouter.ai/api/v1'),
            'api_key' => env('OPENROUTER_API_KEY'),
            'model' => env('OPENROUTER_MODEL', 'liquid/lfm-2.5-2.6b:free'),
            'extra' => [
                // Размышления рассуждающих моделей в чате только мешают.
                'reasoning' => ['exclude' => true],
                // Цена каждого ответа приходит вместе с ним.
                'usage' => ['include' => true],
            ],
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
    | Запретов в запросе нет намеренно: небольшие модели, получив
    | список ограничений, сводят ответ к одной фразе «I am Uncensia».
    |
    | Про язык сказано отдельно: без этого англоязычные модели отвечают
    | по-английски на русский вопрос.
    */
    'system_prompt' => env(
        'AI_SYSTEM_PROMPT',
        'You are Uncensia, a helpful AI assistant. '
        .'Give complete, useful answers and explain your reasoning when it helps. '
        .'Reply only in the language of the latest user message, and only once: '
        .'never add a translation of your answer into English or any other language. '
        .'Do not introduce yourself unless you are asked who you are. '
        .'If a request is too short to understand, ask one brief clarifying question. '
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
    | ReplyWithImage). Без этого указания модель на просьбу поправить
    | фото отвечает «вот изображение», ничего не прикладывая.
    |
    */

    'images_note' => 'Pictures in this chat are drawn and edited by the app itself, not by you: '
        .'when the user plainly asks to draw something or to change a picture, the picture appears without your reply. '
        .'You never attach images yourself, so never write that you have created, edited or attached one. '
        .'If the user wants a picture, suggest asking directly, for example "draw a red fox in the snow".',

    'timeout' => (int) env('AI_TIMEOUT', 60),

    /*
    |--------------------------------------------------------------------------
    | Диспетчер чата
    |--------------------------------------------------------------------------
    |
    | Маленькая модель читает каждое сообщение и решает, что человек
    | хочет: ответ текстом, картинку, правку картинки, звук или видео, —
    | и какая текстовая модель подойдёт, если выбор за нами (Auto).
    | Модель без цензуры: обычная откажется разбирать откровенную
    | просьбу. Цена — около $0.0001 за сообщение: price в долларах за
    | миллион токенов, расход токенов присылает провайдер.
    |
    | Не ответил за timeout — решает запасное правило по словам.
    | Неуверенное решение (ниже min_confidence) инструмент не запускает.
    |
    */

    'router' => [
        'model' => env('AI_ROUTER_MODEL', 'gemma-4-uncensored'),
        'timeout' => (int) env('AI_ROUTER_TIMEOUT', 6),
        'min_confidence' => (float) env('AI_ROUTER_MIN_CONFIDENCE', 0.55),
        'price' => ['input' => 0.1625, 'output' => 0.5],
    ],

    /*
    |--------------------------------------------------------------------------
    | Голосовой ввод
    |--------------------------------------------------------------------------
    |
    | Запись с микрофона превращается в текст в поле ввода. Распознаёт
    | провайдер Студии; цена — cost_per_second за секунду записи.
    |
    */

    'transcription' => [
        'model' => env('AI_TRANSCRIPTION_MODEL', 'openai/whisper-large-v3'),
        'max_seconds' => 120,
        'max_kb' => 10240,
        'cost_per_second' => 0.0001,
    ],

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

    // Сколько истории уходит модели за раз: символов текста (с
    // документами) и картинок. Каждый ответ заново оплачивает всё
    // отправленное, поэтому без предела длинный диалог с файлами
    // стоил бы десятки центов за реплику. Старое отбрасывается первым.
    'context_chars' => (int) env('AI_CONTEXT_CHARS', 60000),
    'context_images' => (int) env('AI_CONTEXT_IMAGES', 4),
];
