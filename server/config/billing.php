<?php

/**
 * Приём платежей.
 *
 * Пока реквизиты не заданы, интерфейс показывает, что оплата временно
 * недоступна. Чтобы включить приём денег, достаточно вписать значения
 * в .env: код менять не нужно.
 */
return [

    'provider' => env('BILLING_PROVIDER', 'yookassa'),

    'currency' => env('BILLING_CURRENCY', 'RUB'),

    'providers' => [

        'yookassa' => [
            'shop_id' => env('YOOKASSA_SHOP_ID'),
            'secret_key' => env('YOOKASSA_SECRET_KEY'),
        ],
    ],
];
