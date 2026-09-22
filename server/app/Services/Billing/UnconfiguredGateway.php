<?php

namespace App\Services\Billing;

use App\Models\Payment;
use RuntimeException;

/**
 * Пока реквизиты платёжной системы не заданы.
 *
 * Интерфейс спрашивает isConfigured() заранее и показывает, что оплата
 * временно недоступна, поэтому до создания платежа дело не доходит.
 */
class UnconfiguredGateway implements PaymentGateway
{
    public function isConfigured(): bool
    {
        return false;
    }

    public function createPayment(Payment $payment, string $returnUrl): PaymentIntent
    {
        throw new RuntimeException('Реквизиты платёжной системы не заданы');
    }

    public function parseNotification(array $payload, ?string $signature = null): ?PaymentNotification
    {
        return null;
    }
}
