<?php

namespace App\Providers;

use App\Services\Billing\PaymentGateway;
use App\Services\Billing\UnconfiguredGateway;
use App\Services\Billing\YooKassaGateway;
use Illuminate\Support\ServiceProvider;

class BillingServiceProvider extends ServiceProvider
{
    /**
     * Пока реквизиты не заданы, подставляем заглушку: интерфейс
     * спросит isConfigured() и скажет, что оплата недоступна.
     */
    public function register(): void
    {
        $this->app->singleton(PaymentGateway::class, function () {
            $name = config('billing.provider');
            $settings = config("billing.providers.{$name}", []);

            $gateway = match ($name) {
                'yookassa' => new YooKassaGateway(
                    shopId: $settings['shop_id'] ?? null,
                    secretKey: $settings['secret_key'] ?? null,
                ),
                default => new UnconfiguredGateway(),
            };

            return $gateway->isConfigured() ? $gateway : new UnconfiguredGateway();
        });
    }
}
