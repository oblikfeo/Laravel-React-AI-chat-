<?php

/**
 * Модели, доступные в чате.
 *
 * Ключ — наш, он уходит в базу и в интерфейс. Название модели у
 * провайдера пользователю не показывается никогда: это внутренняя
 * деталь.
 *
 * vision — понимает ли модель изображения. При отправке картинки
 * запрос уходит модели с таким признаком, иначе она ответит, что
 * не умеет работать с файлами.
 *
 * fallback — чем заменить, если модель не ответила. У бесплатных
 * моделей строгий лимит частоты: без подмены человек получил бы
 * сообщение об ошибке вместо ответа.
 *
 * price — цена у провайдера в долларах за миллион токенов: input —
 * то, что модель читает, output — то, что пишет. По ней и расходу
 * токенов считается цена каждого ответа: Venice свою цену в ответе
 * не присылает. Взята из списка моделей Venice (GET /models).
 */
return [

    'default' => env('AI_DEFAULT_MODEL', 'auto'),

    'list' => [

        'auto' => [
            'label' => 'Auto',
            'description' => 'Balanced everyday model',
            // Версия на 12 млрд отвечала односложно и уходила в
            // английский на русских вопросах. Эта крупнее, держит
            // язык собеседника и не засыпает между запросами.
            'provider_model' => 'google-gemma-3-27b-it',
            'price' => ['input' => 0.12, 'output' => 0.2],
            'vision' => true,
            'free' => false,
            'fallback' => 'fast',
        ],

        'fast' => [
            'label' => 'Fast',
            'description' => 'Quick replies, long context',
            // Рассуждающие модели здесь не годятся: они тратят весь
            // лимит на размышления и отвечают через десятки секунд.
            'provider_model' => 'mistral-small-3-2-24b-instruct',
            'price' => ['input' => 0.09375, 'output' => 0.25],
            'vision' => true,
            'free' => false,
        ],

        'smart' => [
            'label' => 'Smart',
            'description' => 'Deeper reasoning for hard questions',
            'provider_model' => 'qwen3-235b-a22b-instruct-2507',
            'price' => ['input' => 0.15, 'output' => 0.75],
            'vision' => false,
            'free' => false,
        ],

        'uncensored' => [
            'label' => 'Uncensored',
            'description' => 'Fewer refusals, direct answers',
            // Ради этой модели к нам и приходят: выделяем её в списке.
            'signature' => true,
            'provider_model' => 'venice-uncensored-1-2',
            'price' => ['input' => 0.2, 'output' => 0.9],
            'vision' => true,
            'free' => false,
        ],

        'roleplay' => [
            'label' => 'Roleplay',
            'description' => 'Characters, stories, long dialogue',
            'provider_model' => 'venice-uncensored-role-play',
            'price' => ['input' => 0.5, 'output' => 2.0],
            'vision' => true,
            'free' => false,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Модель для изображений
    |--------------------------------------------------------------------------
    |
    | Если к сообщению приложена картинка, а выбранная модель её не
    | понимает, запрос уходит сюда. Так человек не обязан помнить,
    | какая модель что умеет.
    |
    */

    'vision_fallback' => env('AI_VISION_MODEL', 'auto'),
];
