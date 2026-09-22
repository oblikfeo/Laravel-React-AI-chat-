<?php

namespace App\Services\Billing;

/**
 * Разобранное уведомление от платёжной системы.
 */
class PaymentNotification
{
    public function __construct(
        public readonly string $paymentId,
        public readonly string $status,
        public readonly array $payload = [],
    ) {
    }

    public function isSucceeded(): bool
    {
        return $this->status === 'succeeded';
    }
}
