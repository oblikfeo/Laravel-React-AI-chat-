<?php

/**
 * Студия: генерация изображений и видео.
 *
 * Названия моделей у провайдера пользователю не показываются — только
 * наши ярлыки, как и в чате (дизайн-система, §12).
 */
return [

    'default_model' => env('STUDIO_DEFAULT_MODEL', 'flash'),

    /*
    |--------------------------------------------------------------------------
    | Модели генерации изображений
    |--------------------------------------------------------------------------
    |
    | Доступ к ним выдаёт провайдер отдельно от текстовых. Пока его нет,
    | интерфейс показывает, что Студия скоро откроется, вместо ошибки.
    |
    */

    'models' => [

        'flash' => [
            'label' => 'Quick',
            'description' => 'Fast drafts and ideas',
            'provider_model' => 'google/gemini-3.1-flash-lite-image',
            'kind' => 'image',
        ],

        'standard' => [
            'label' => 'Standard',
            'description' => 'Balanced quality and speed',
            'provider_model' => 'google/gemini-3.1-flash-image',
            'kind' => 'image',
        ],

        'pro' => [
            'label' => 'Pro',
            'description' => 'Highest detail, slower',
            'provider_model' => 'google/gemini-3-pro-image',
            'kind' => 'image',
            'paid' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Соотношения сторон
    |--------------------------------------------------------------------------
    */

    'aspect_ratios' => [
        '1:1' => ['label' => 'Square', 'width' => 1024, 'height' => 1024],
        '16:9' => ['label' => 'Landscape', 'width' => 1344, 'height' => 768],
        '9:16' => ['label' => 'Portrait', 'width' => 768, 'height' => 1344],
        '4:3' => ['label' => 'Classic', 'width' => 1152, 'height' => 896],
        '3:2' => ['label' => 'Photo', 'width' => 1216, 'height' => 832],
    ],

    /*
    |--------------------------------------------------------------------------
    | Стили
    |--------------------------------------------------------------------------
    |
    | Стиль дописывается к запросу человека: сам он пишет, что хочет
    | увидеть, а не как это должно выглядеть.
    |
    */

    'styles' => [
        'none' => ['label' => 'None', 'suffix' => null],
        'photo' => [
            'label' => 'Photo',
            'suffix' => 'photorealistic, natural lighting, sharp focus, high detail',
        ],
        'cinematic' => [
            'label' => 'Cinematic',
            'suffix' => 'cinematic lighting, film grain, dramatic composition, anamorphic',
        ],
        'anime' => [
            'label' => 'Anime',
            'suffix' => 'anime illustration, clean line art, vivid colors',
        ],
        'art' => [
            'label' => 'Digital art',
            'suffix' => 'digital painting, rich colors, detailed brushwork',
        ],
        'render' => [
            'label' => '3D render',
            'suffix' => '3D render, octane, soft studio lighting, subsurface scattering',
        ],
        'minimal' => [
            'label' => 'Minimal',
            'suffix' => 'minimalist, flat design, clean shapes, generous negative space',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Ограничения
    |--------------------------------------------------------------------------
    */

    'daily_limit_guest' => (int) env('STUDIO_GUEST_DAILY', 3),
    'daily_limit_free' => (int) env('STUDIO_FREE_DAILY', 15),

    'disk' => env('STUDIO_DISK', 'local'),

    'timeout' => (int) env('STUDIO_TIMEOUT', 180),
];
