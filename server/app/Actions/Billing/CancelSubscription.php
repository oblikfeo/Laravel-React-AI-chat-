<?php

namespace App\Actions\Billing;

use App\Models\Subscription;

/**
 * Отменяет подписку.
 *
 * Оплаченный срок не сгорает: тариф остаётся до конца периода, а потом
 * истекает сам. Деньги за неиспользованные дни не удерживаем.
 */
class CancelSubscription
{
    public function handle(Subscription $subscription): void
    {
        if ($subscription->cancelled_at) {
            return;
        }

        $subscription->forceFill(['cancelled_at' => now()])->save();
    }
}
