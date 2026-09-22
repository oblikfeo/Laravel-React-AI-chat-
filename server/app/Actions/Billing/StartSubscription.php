<?php

namespace App\Actions\Billing;

use App\Models\Payment;
use App\Models\Subscription;
use App\Models\User;
use App\Services\Billing\PaymentGateway;
use Illuminate\Support\Facades\DB;

/**
 * Начинает оформление подписки.
 *
 * Создаёт ожидающую подписку и платёж, затем отдаёт адрес страницы
 * оплаты. Тариф пользователю не меняется: это произойдёт только когда
 * платёжная система подтвердит оплату.
 */
class StartSubscription
{
    public function __construct(private readonly PaymentGateway $gateway)
    {
    }

    public function handle(User $user, string $plan, string $period, string $returnUrl): ?string
    {
        $amount = $this->amountFor($plan, $period);

        [$subscription, $payment] = DB::transaction(function () use ($user, $plan, $period, $amount) {
            $subscription = $user->subscriptions()->create([
                'plan' => $plan,
                'period' => $period,
                'status' => Subscription::STATUS_PENDING,
                'provider' => config('billing.provider'),
                'amount' => $amount,
                'currency' => config('billing.currency'),
            ]);

            $payment = $user->payments()->create([
                'subscription_id' => $subscription->id,
                'plan' => $plan,
                'period' => $period,
                'status' => Payment::STATUS_PENDING,
                'amount' => $amount,
                'currency' => config('billing.currency'),
                'provider' => config('billing.provider'),
            ]);

            return [$subscription, $payment];
        });

        $intent = $this->gateway->createPayment($payment, $returnUrl);

        $payment->forceFill(['provider_id' => $intent->id])->save();
        $subscription->forceFill(['provider_id' => $intent->id])->save();

        return $intent->confirmationUrl;
    }

    /**
     * Стоимость в копейках.
     *
     * Деньги считаем в целых копейках: дробные значения при расчётах
     * накапливают погрешность.
     */
    private function amountFor(string $plan, string $period): int
    {
        $key = $period === Subscription::PERIOD_YEARLY
            ? 'price_yearly'
            : 'price_monthly';

        return (int) round(config("plans.list.{$plan}.{$key}", 0) * 100);
    }
}
