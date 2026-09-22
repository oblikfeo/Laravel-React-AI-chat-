/**
 * Тарифы для интерфейса.
 *
 * Повторяют config/plans.php. Оплата не подключена, данные не зависят
 * от пользователя, поэтому держим их на клиенте и не гоняем в каждом
 * ответе сервера. Когда появится биллинг, список переедет в пропсы.
 */
export const plans = [
    {
        key: 'free',
        name: 'Free',
        priceMonthly: 0,
        priceYearly: 0,
        tagline: 'Explore Uncensia with base models',
        cta: 'Current plan',
        features: [
            'Base models',
            '10 text prompts per day',
            '15 image prompts per day',
            'Private and uncensored',
        ],
    },
    {
        key: 'pro',
        name: 'Pro',
        priceMonthly: 18,
        priceYearly: 194,
        tagline: 'Your private studio — every model, no limits',
        cta: 'Get Pro',
        popular: true,
        features: [
            'All Pro models',
            'Unlimited text prompts',
            '1,000 images per day',
            'Image tools: upscale, remove background, variants',
            'Custom characters',
            'Extended context for longer conversations',
            'Encrypted chat backup',
            '100 credits per month',
        ],
    },
    {
        key: 'pro_plus',
        name: 'Pro+',
        priceMonthly: 68,
        priceYearly: 734,
        tagline: 'Everything in Pro, scaled for serious creators',
        cta: 'Get Pro+',
        features: [
            'Everything in Pro',
            'Higher image generation limits',
            '7,500 credits per month',
            '2-month credit banking — unused credits roll forward',
        ],
    },
    {
        key: 'max',
        name: 'Max',
        priceMonthly: 200,
        priceYearly: 2160,
        tagline: 'For the ultimate access to every model',
        cta: 'Get Max',
        features: [
            'Everything in Pro+',
            'Highest image generation limits',
            '22,500 credits per month',
            '3-month credit banking',
        ],
    },
];
