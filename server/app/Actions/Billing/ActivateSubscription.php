<?php

namespace App\Actions\Billing;

use App\Models\Payment;
use App\Models\Subscription;
use Illuminate\Support\Facades\DB;

/**
 * Включает подписку после подтверждённой оплаты.
 *
 * Платёжная система присылает уведомление несколько раз, поэтому
 * повторный вызов для уже оплаченного платежа ничего не меняет:
 * иначе срок подписки продлевался бы за один платёж многократно.
 */
class ActivateSubscription
{
    public function handle(Payment $payment, array $payload = []): void
    {
        if ($payment->status === Payment::STATUS_SUCCEEDED) {
            return;
        }

        DB::transaction(function () use ($payment, $payload) {
            $payment->forceFill([
                'status' => Payment::STATUS_SUCCEEDED,
                'paid_at' => now(),
                'payload' => $payload ?: $payment->payload,
            ])->save();

            $subscription = $payment->subscription;

            if (! $subscription) {
                return;
            }

            $starts = now();
            $ends = $subscription->period === Subscription::PERIOD_YEARLY
                ? $starts->copy()->addYear()
                : $starts->copy()->addMonth();

            $subscription->forceFill([
                'status' => Subscription::STATUS_ACTIVE,
                'starts_at' => $starts,
                'ends_at' => $ends,
            ])->save();

            // Тариф в профиле — то, что видит интерфейс.
            $subscription->user->forceFill([
                'plan' => $subscription->plan,
            ])->save();
        });
    }
}
