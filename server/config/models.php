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
 * то, что модель читает, output — то, что пишет. Провайдер сам
 * сообщает цену каждого ответа; эти значения нужны, только если он
 * её не прислал, и для справки в админке.
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
            'provider_model' => 'google/gemma-3-27b-it',
            'price' => ['input' => 0.09, 'output' => 0.17],
            'vision' => true,
            'free' => false,
            'fallback' => 'fast',
        ],

        'fast' => [
            'label' => 'Fast',
            'description' => 'Quick replies, long context',
            // Рассуждающие модели здесь не годятся: они тратят весь
            // лимит на размышления и отвечают через десятки секунд.
            'provider_model' => 'amazon/nova-lite-v1',
            'price' => ['input' => 0.06, 'output' => 0.24],
            'vision' => true,
            'free' => false,
        ],

        'smart' => [
            'label' => 'Smart',
            'description' => 'Deeper reasoning for hard questions',
            'provider_model' => 'nousresearch/hermes-4-405b',
            'price' => ['input' => 1.0, 'output' => 3.0],
            'vision' => false,
            'free' => false,
        ],

        'uncensored' => [
            'label' => 'Uncensored',
            'description' => 'Fewer refusals, direct answers',
            // Ради этой модели к нам и приходят: выделяем её в списке.
            'signature' => true,
            'provider_model' => 'cognitivecomputations/dolphin-mistral-24b-venice-edition',
            'price' => ['input' => 0.1, 'output' => 0.3],
            'vision' => false,
            'free' => false,
        ],

        'roleplay' => [
            'label' => 'Roleplay',
            'description' => 'Characters, stories, long dialogue',
            'provider_model' => 'sao10k/l3.3-euryale-70b',
            'price' => ['input' => 0.65, 'output' => 0.75],
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
