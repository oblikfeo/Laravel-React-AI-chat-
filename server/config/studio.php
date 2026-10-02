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
        '1:1' => ['label' => 'Square', 'width' => 1024, 'height' => 1024],
        '3:2' => ['label' => 'Landscape', 'width' => 1216, 'height' => 832],
        '16:9' => ['label' => 'Cinema', 'width' => 1280, 'height' => 720],
        '21:9' => ['label' => 'Widescreen', 'width' => 1280, 'height' => 544],
        '9:16' => ['label' => 'Tall', 'width' => 720, 'height' => 1280],
        '2:3' => ['label' => 'Portrait', 'width' => 832, 'height' => 1216],
        '3:4' => ['label' => 'Instagram', 'width' => 896, 'height' => 1152],
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
                'description' => 'Natural voice, fast',
                'provider_model' => 'tts-kokoro',
            ],
            'premium' => [
                'label' => 'Premium',
                'description' => 'Most lifelike',
                'provider_model' => 'tts-elevenlabs-turbo-v2-5',
                'paid' => true,
            ],
        ],

        'max_characters' => 4000,
    ],

    /*
    |--------------------------------------------------------------------------
    | Ограничения
    |--------------------------------------------------------------------------
    */

    'daily_limit_guest' => (int) env('STUDIO_GUEST_DAILY', 3),
    'daily_limit_free' => (int) env('STUDIO_FREE_DAILY', 15),

    'max_variants' => 4,

    // Ниже этого остатка на счёте провайдера пишем предупреждение в лог.
    'low_balance' => (float) env('STUDIO_LOW_BALANCE', 1.0),

    'disk' => env('STUDIO_DISK', 'local'),

    'timeout' => (int) env('STUDIO_TIMEOUT', 180),
];
