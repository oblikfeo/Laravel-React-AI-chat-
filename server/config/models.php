<?php

/**
 * Модели, доступные в чате.
 *
 * Ключ — наш, он уходит в базу и в интерфейс. Название модели у
 * провайдера пользователю не показывается никогда: это внутренняя
 * деталь, см. дизайн-систему, §12.
 *
 * vision — понимает ли модель изображения. При отправке картинки
 * запрос уходит модели с таким признаком, иначе она ответит, что
 * не умеет работать с файлами.
 *
 * fallback — чем заменить, если модель не ответила. У бесплатных
 * моделей строгий лимит частоты: без подмены человек получил бы
 * сообщение об ошибке вместо ответа.
 */
return [

    'default' => env('AI_DEFAULT_MODEL', 'auto'),

    'list' => [

        'auto' => [
            'label' => 'Auto',
            'description' => 'Balanced everyday model',
            // Бесплатная модель стоит в очереди: первый запрос мог
            // ждать 15–25 секунд, следующие полторы. Для модели по
            // умолчанию это неприемлемо, поэтому берём платную —
            // она стоит копейки и отвечает за секунду.
            'provider_model' => 'nex-agi/nex-n2.5-pro',
            'vision' => true,
            'free' => false,
            'fallback' => 'fast',
        ],

        'fast' => [
            'label' => 'Fast',
            'description' => 'Quick replies, long context',
            // Прежняя модель была рассуждающей: тратила минуту на
            // размышления и возвращала пустой ответ. Эта отвечает
            // за 1–2 секунды.
            'provider_model' => 'nex-agi/nex-n2.5-mini',
            'vision' => true,
            'free' => false,
        ],

        'smart' => [
            'label' => 'Smart',
            'description' => 'Deeper reasoning for hard questions',
            'provider_model' => 'nousresearch/hermes-4-405b',
            'vision' => false,
            'free' => false,
        ],

        'uncensored' => [
            'label' => 'Uncensored',
            'description' => 'Fewer refusals, direct answers',
            'provider_model' => 'cognitivecomputations/dolphin-mistral-24b-venice-edition',
            'vision' => false,
            'free' => false,
        ],

        'roleplay' => [
            'label' => 'Roleplay',
            'description' => 'Characters, stories, long dialogue',
            'provider_model' => 'sao10k/l3.3-euryale-70b',
            'vision' => false,
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
