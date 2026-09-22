<?php

/**
 * Тарифы. Единственный источник: отсюда их берут и страница тарифов,
 * и меню пользователя, и баннер.
 *
 * Оплата пока не подключена, поэтому это описание, а не биллинг.
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
            'features' => [
                'Base models',
                '10 text prompts per day',
                '15 image prompts per day',
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
            'features' => [
                'All Pro models',
                'Unlimited text prompts',
                '1,000 images per day',
                'Image tools: upscale, remove background, variants',
                'Custom characters',
                'Extended context for longer conversations',
                'Encrypted chat backup',
                '100 credits per month',
            ],
        ],

        'pro_plus' => [
            'name' => 'Pro+',
            'price_monthly' => 68,
            'price_yearly' => 734,
            'tagline' => 'Everything in Pro, scaled for serious creators',
            'cta' => 'Get Pro+',
            'features' => [
                'Everything in Pro',
                'Higher image generation limits',
                '7,500 credits per month',
                '2-month credit banking — unused credits roll forward',
            ],
        ],

        'max' => [
            'name' => 'Max',
            'price_monthly' => 200,
            'price_yearly' => 2160,
            'tagline' => 'For the ultimate access to every model',
            'cta' => 'Get Max',
            'features' => [
                'Everything in Pro+',
                'Highest image generation limits',
                '22,500 credits per month',
                '3-month credit banking',
            ],
        ],
    ],
];
