<?php

/**
 * Тарифы. Единственный источник: отсюда их берут и страница тарифов,
 * и меню пользователя, и баннер, и учёт расходов.
 *
 * usage — сколько себестоимости в месяц покрывает тариф:
 *   allowance   — бюджет на месяц в долларах себестоимости провайдеров.
 *                 Человек видит не доллары, а долю: «использовано 37%».
 *   daily_share — какую долю месячного бюджета можно потратить за
 *                 день; null — без дневного ограничения.
 *   rollover    — за сколько месяцев копится неистраченное: 1 — не
 *                 копится, 2 — остаток прошлого месяца переходит в
 *                 текущий, 3 — остатки двух прошлых.
 *
 * video — доступно ли видео: ролик дорогой, на бесплатном его нет.
 */
return [

    // Тариф, который получает новый пользователь.
    'default' => 'free',

    // Кому показывать предложение перейти на платный.
    'promoted' => ['free'],

    'list' => [

        'free' => [
            'name' => 'Free',
            'price_monthly' => 0,
            'price_yearly' => 0,
            'tagline' => 'Explore Uncensia with base models',
            'cta' => 'Current plan',
            'usage' => ['allowance' => 1.0, 'daily_share' => 0.15, 'rollover' => 1],
            'video' => false,
            'features' => [
                'Base models',
                'Chat, images and audio every day',
                'Daily allowance that renews every morning',
                'Private and uncensored',
            ],
        ],

        'pro' => [
            'name' => 'Pro',
            'price_monthly' => 18,
            'price_yearly' => 194,
            'tagline' => 'Your private studio — every model, no limits',
            'cta' => 'Get Pro',
            'popular' => true,
            'usage' => ['allowance' => 6.0, 'daily_share' => null, 'rollover' => 1],
            'video' => true,
            'features' => [
                'All Pro models',
                '6× the monthly allowance of Free',
                'Video generation',
                'Image tools: upscale, remove background, variants',
                'Custom characters',
                'Extended context for longer conversations',
                'Encrypted chat backup',
            ],
        ],

        'pro_plus' => [
            'name' => 'Pro+',
            'price_monthly' => 68,
            'price_yearly' => 734,
            'tagline' => 'Everything in Pro, scaled for serious creators',
            'cta' => 'Get Pro+',
            'usage' => ['allowance' => 24.0, 'daily_share' => null, 'rollover' => 2],
            'video' => true,
            'features' => [
                'Everything in Pro',
                '4× the monthly allowance of Pro',
                'Unused allowance carries over to the next month',
            ],
        ],

        'max' => [
            'name' => 'Max',
            'price_monthly' => 200,
            'price_yearly' => 2160,
            'tagline' => 'For the ultimate access to every model',
            'cta' => 'Get Max',
            'usage' => ['allowance' => 70.0, 'daily_share' => null, 'rollover' => 3],
            'video' => true,
            'features' => [
                'Everything in Pro+',
                '3× the monthly allowance of Pro+',
                'Unused allowance carries over for up to 3 months',
            ],
        ],
    ],
];
