<?php

/**
 * Студия: изображения, правка и озвучка.
 *
 * Названия моделей у провайдера пользователю не показываются — только
 * наши ярлыки, как и в чате (дизайн-система, §12).
 *
 * Провайдер — Venice AI, разбор его возможностей в docs/STUDIO-API.md.
 */
return [

    'provider' => env('STUDIO_PROVIDER', 'venice'),

    'base_url' => env('VENICE_BASE_URL', 'https://api.venice.ai/api/v1'),
    'api_key' => env('VENICE_API_KEY'),

    'default_model' => env('STUDIO_DEFAULT_MODEL', 'fast'),

    /*
    |--------------------------------------------------------------------------
    | Модели изображений
    |--------------------------------------------------------------------------
    |
    | Линейка повторяет логику чата: от быстрой к подробной, отдельно —
    | модель без ограничений, ради которой к нам и приходят.
    |
    */

    'models' => [

        'fast' => [
            'label' => 'Quick',
            'description' => 'Drafts in seconds',
            'provider_model' => 'z-image-turbo',
            'kind' => 'image',
            'cost' => 0.01,
            // Соотношение сторон принимает не всякая модель: остальным
            // отправляем ширину и высоту, см. VeniceGenerator.
            'aspect_ratio' => false,
            'divisor' => 8,
        ],

        'standard' => [
            'label' => 'Standard',
            'description' => 'Balanced quality and speed',
            'provider_model' => 'venice-sd35',
            'kind' => 'image',
            'cost' => 0.01,
            'aspect_ratio' => false,
            'divisor' => 16,
        ],

        'anime' => [
            'label' => 'Anime',
            'description' => 'Illustration and character art',
            'provider_model' => 'wai-Illustrious',
            'kind' => 'image',
            'cost' => 0.01,
            'aspect_ratio' => false,
            'divisor' => 16,
        ],

        'uncensored' => [
            'label' => 'Uncensored',
            'description' => 'No content restrictions',
            'provider_model' => 'lustify-v8',
            'kind' => 'image',
            'signature' => true,
            'cost' => 0.01,
            'aspect_ratio' => false,
            'divisor' => 8,
        ],

        'pro' => [
            'label' => 'Pro',
            'description' => 'Highest detail, slower',
            'provider_model' => 'flux-2-pro',
            'kind' => 'image',
            'paid' => true,
            'cost' => 0.03,
            'aspect_ratio' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Соотношения сторон
    |--------------------------------------------------------------------------
    |
    | Набор общий для моделей, которые их поддерживают. Для остальных
    | провайдер подбирает размер сам по ширине и высоте.
    |
    */

    'aspect_ratios' => [
        '1:1' => [
            'label' => 'Square',
            'hint' => 'Avatars and posts',
            'width' => 1024,
            'height' => 1024,
        ],
        '3:2' => [
            'label' => 'Landscape',
            'hint' => 'Classic photo',
            'width' => 1216,
            'height' => 832,
        ],
        '16:9' => [
            'label' => 'Cinema',
            'hint' => 'Video and covers',
            'width' => 1280,
            'height' => 720,
        ],
        '21:9' => [
            'label' => 'Widescreen',
            'hint' => 'Panorama',
            'width' => 1280,
            'height' => 544,
        ],
        '9:16' => [
            'label' => 'Tall',
            'hint' => 'Stories and reels',
            'width' => 720,
            'height' => 1280,
        ],
        '2:3' => [
            'label' => 'Portrait',
            'hint' => 'Posters and prints',
            'width' => 832,
            'height' => 1216,
        ],
        '3:4' => [
            'label' => 'Instagram',
            'hint' => 'Feed posts',
            'width' => 896,
            'height' => 1152,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Стили
    |--------------------------------------------------------------------------
    |
    | Справочник держит провайдер (GET /image/styles), поэтому здесь
    | только отобранные: весь список в полсотни пунктов интерфейсу
    | не нужен.
    |
    */

    'styles' => [
        'none' => null,
        'photo' => 'Photographic',
        'cinematic' => 'Cinematic',
        'anime' => 'Anime',
        'art' => 'Digital Art',
        'render' => '3D Model',
        'comic' => 'Comic Book',
        'neon' => 'Neon Punk',
        'pixel' => 'Pixel Art',
        'minimal' => 'Line Art',
    ],

    /*
    |--------------------------------------------------------------------------
    | Правка изображений
    |--------------------------------------------------------------------------
    */

    'edit' => [
        'model' => env('STUDIO_EDIT_MODEL', 'firered-image-edit'),
        'max_combine' => 4,
        'scales' => [2, 4],
    ],

    /*
    |--------------------------------------------------------------------------
    | Озвучка
    |--------------------------------------------------------------------------
    */

    'speech' => [
        // Наш ключ из списка ниже, а не название модели у провайдера:
        // оно наружу не выходит.
        'default_model' => env('STUDIO_SPEECH_MODEL', 'standard'),

        'models' => [
            'standard' => [
                'label' => 'Standard',
                'description' => 'Natural voice, 54 voices',
                'provider_model' => 'tts-kokoro',
                'voice' => 'af_heart',
                'cost' => 0.0001,
            ],
            'premium' => [
                'label' => 'Premium',
                'description' => 'Most lifelike',
                'provider_model' => 'tts-elevenlabs-turbo-v2-5',
                'voice' => 'Aria',
                'paid' => true,
                'cost' => 0.002,
            ],
        ],

        'max_characters' => 4000,
    ],

    /*
    |--------------------------------------------------------------------------
    | Голоса
    |--------------------------------------------------------------------------
    |
    | У провайдера названия служебные: af_heart, am_adam. Первая буква
    | означает язык, вторая — пол. Показываем понятные имена и берём
    | разные по звучанию, а не весь список из полусотни.
    |
    */

    'voices' => [
        'warm' => ['label' => 'Warm', 'description' => 'Female, soft', 'provider' => 'af_heart'],
        'clear' => ['label' => 'Clear', 'description' => 'Female, neutral', 'provider' => 'af_nova'],
        'bright' => ['label' => 'Bright', 'description' => 'Female, lively', 'provider' => 'af_bella'],
        'calm' => ['label' => 'Calm', 'description' => 'Male, steady', 'provider' => 'am_adam'],
        'deep' => ['label' => 'Deep', 'description' => 'Male, low', 'provider' => 'am_onyx'],
        'british_f' => ['label' => 'British', 'description' => 'Female, UK accent', 'provider' => 'bf_emma'],
        'british_m' => ['label' => 'British male', 'description' => 'Male, UK accent', 'provider' => 'bm_george'],
    ],

    'default_voice' => env('STUDIO_VOICE', 'warm'),

    /*
    |--------------------------------------------------------------------------
    | Музыка
    |--------------------------------------------------------------------------
    |
    | Генерация долгая, поэтому идёт через очередь провайдера: запрос
    | ставится в работу, результат забирается отдельно.
    |
    | Дорогие модели намеренно не подключены: ElevenLabs Music стоит
    | $0.69 за минуту — одна песня дороже семидесяти картинок.
    |
    */

    'music' => [
        'default_model' => env('STUDIO_MUSIC_MODEL', 'instrumental'),

        'models' => [
            'instrumental' => [
                'label' => 'Instrumental',
                'description' => 'Music without vocals, up to 10 min',
                'provider_model' => 'sonilo-v1-1-music',
                'cost' => 0.003,
                'lyrics' => false,
                'min_duration' => 10,
                'max_duration' => 600,
                'default_duration' => 60,
            ],
            'song' => [
                'label' => 'Song',
                'description' => 'Your own lyrics, sung',
                'provider_model' => 'ace-step-15',
                'cost' => 0.03,
                'lyrics' => true,
                'lyrics_required' => false,
                // Модель принимает только эти длительности.
                'durations' => [60, 90, 120, 150, 180, 210],
                'default_duration' => 60,
            ],
            'vocal' => [
                'label' => 'Vocal',
                'description' => 'Full song with singing',
                'provider_model' => 'minimax-music-v2',
                'cost' => 0.04,
                'lyrics' => true,
                'lyrics_required' => true,
                'paid' => true,
            ],
        ],

        'max_prompt' => 300,
        'max_lyrics' => 3000,
    ],

    /*
    |--------------------------------------------------------------------------
    | Звуковые эффекты
    |--------------------------------------------------------------------------
    */

    'effects' => [
        'default_model' => 'quick',

        'models' => [
            'quick' => [
                'label' => 'Quick',
                'description' => 'Short effects, up to 30 s',
                'provider_model' => 'mmaudio-v2-text-to-audio',
                'cost' => 0.001,
                'min_duration' => 1,
                'max_duration' => 30,
                'default_duration' => 5,
            ],
            'long' => [
                'label' => 'Extended',
                'description' => 'Up to 3 minutes',
                'provider_model' => 'sonilo-v1-1-sound-effects',
                'cost' => 0.002,
                'min_duration' => 1,
                'max_duration' => 180,
                'default_duration' => 8,
            ],
            'loop' => [
                'label' => 'Seamless',
                'description' => 'Loops without a gap',
                'provider_model' => 'elevenlabs-sound-effects-v2',
                'cost' => 0.002,
                'min_duration' => 1,
                'max_duration' => 22,
                'default_duration' => 7,
                'loop' => true,
            ],
        ],

        'max_prompt' => 450,
    ],

    /*
    |--------------------------------------------------------------------------
    | Смена голоса
    |--------------------------------------------------------------------------
    |
    | Берёт готовую запись и читает её другим голосом. Результат
    | забирается своим эндпоинтом, не общим для звука.
    |
    */

    'voice_changer' => [
        'provider_model' => env('STUDIO_VOICE_CHANGER_MODEL', 'elevenlabs-voice-changer'),
        'cost' => 0.01,
        'max_upload' => 25600,
    ],

    /*
    |--------------------------------------------------------------------------
    | Очередь провайдера
    |--------------------------------------------------------------------------
    */

    'queue' => [
        // Как часто спрашивать готовность и сколько ждать.
        'poll_seconds' => 5,
        'timeout_seconds' => (int) env('STUDIO_QUEUE_TIMEOUT', 600),
    ],

    /*
    |--------------------------------------------------------------------------
    | Ограничения
    |--------------------------------------------------------------------------
    */

    'daily_limit_guest' => (int) env('STUDIO_GUEST_DAILY', 3),
    'daily_limit_free' => (int) env('STUDIO_FREE_DAILY', 15),

    'max_variants' => 4,

    // Больше этого провайдер зерно не принимает.
    'max_seed' => 999_999_999,

    /*
    |--------------------------------------------------------------------------
    | Перевод описания
    |--------------------------------------------------------------------------
    |
    | Модели рисования обучены на английском: русское описание они
    | разбирают плохо. Переводим своей текстовой моделью — быстро и
    | почти бесплатно, см. PromptTranslator.
    |
    | Модель без ограничений выбрана намеренно: переводчик с фильтрами
    | отказался бы переводить часть запросов или смягчил бы их, а это
    | ломает наше главное предложение.
    |
    */

    'translate_model' => env(
        'STUDIO_TRANSLATE_MODEL',
        'cognitivecomputations/dolphin-mistral-24b-venice-edition',
    ),

    // Ниже этого остатка на счёте провайдера пишем предупреждение в лог.
    'low_balance' => (float) env('STUDIO_LOW_BALANCE', 1.0),

    'disk' => env('STUDIO_DISK', 'local'),

    'timeout' => (int) env('STUDIO_TIMEOUT', 180),
];
