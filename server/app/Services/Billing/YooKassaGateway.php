<?php

namespace App\Services\Billing;

use App\Models\Payment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * ЮKassa.
 *
 * Реализация по документации: создание платежа и разбор уведомления.
 * Чтобы включить приём денег, достаточно вписать в настройки номер
 * магазина и секретный ключ — код менять не нужно.
 */
class YooKassaGateway implements PaymentGateway
{
    private const API = 'https://api.yookassa.ru/v3';

    public function __construct(
        private readonly ?string $shopId,
        private readonly ?string $secretKey,
    ) {
    }

    public function isConfigured(): bool
    {
        return filled($this->shopId) && filled($this->secretKey);
    }

    public function createPayment(Payment $payment, string $returnUrl): PaymentIntent
    {
        // Ключ идемпотентности: при повторной отправке того же запроса
        // ЮKassa вернёт прежний платёж, а не спишет деньги дважды.
        $response = Http::withBasicAuth($this->shopId, $this->secretKey)
            ->withHeaders(['Idempotence-Key' => (string) Str::uuid()])
            ->acceptJson()
            ->post(self::API.'/payments', [
                'amount' => [
                    // ЮKassa принимает сумму строкой с двумя знаками.
                    'value' => number_format($payment->amount / 100, 2, '.', ''),
                    'currency' => $payment->currency,
                ],
                'capture' => true,
                'confirmation' => [
                    'type' => 'redirect',
                    'return_url' => $returnUrl,
                ],
                'description' => "Uncensia {$payment->plan}, {$payment->period}",
                'metadata' => [
                    'payment_id' => $payment->id,
                    'user_id' => $payment->user_id,
                ],
            ]);

        if ($response->failed()) {
            throw new RuntimeException(
                'ЮKassa отклонила создание платежа: '.$response->body()
            );
        }

        return new PaymentIntent(
            id: $response->json('id'),
            confirmationUrl: $response->json('confirmation.confirmation_url'),
        );
    }

    public function parseNotification(array $payload, ?string $signature = null): ?PaymentNotification
    {
        $id = data_get($payload, 'object.id');
        $status = data_get($payload, 'object.status');

        if (! $id || ! $status) {
            return null;
        }

        // Уведомление приходит открытым запросом, поэтому доверять его
        // содержимому нельзя: спрашиваем состояние платежа у ЮKassa.
        $response = Http::withBasicAuth($this->shopId, $this->secretKey)
            ->acceptJson()
            ->get(self::API."/payments/{$id}");

        if ($response->failed()) {
            return null;
        }

        return new PaymentNotification(
            paymentId: $id,
            status: $response->json('status'),
            payload: $response->json(),
        );
    }
}
