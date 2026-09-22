<?php

namespace App\Services\Billing;

/**
 * Результат создания платежа: куда отправить человека платить.
 */
class PaymentIntent
{
    public function __construct(
        public readonly string $id,
        public readonly ?string $confirmationUrl = null,
    ) {
    }
}
