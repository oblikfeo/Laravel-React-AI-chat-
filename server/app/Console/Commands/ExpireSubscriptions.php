<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use Illuminate\Console\Command;

class ExpireSubscriptions extends Command
{
    protected $signature = 'subscriptions:expire';

    protected $description = 'Переводит просроченные подписки на бесплатный тариф';

    /**
     * Подписка с истёкшим сроком перестаёт действовать.
     *
     * Запускается по расписанию: пользоваться платным тарифом после
     * окончания оплаченного периода нельзя.
     */
    public function handle(): int
    {
        $expired = Subscription::query()
            ->with('user')
            ->where('status', Subscription::STATUS_ACTIVE)
            ->whereNotNull('ends_at')
            ->where('ends_at', '<=', now())
            ->get();

        foreach ($expired as $subscription) {
            $subscription->forceFill([
                'status' => Subscription::STATUS_EXPIRED,
            ])->save();

            // Бесплатный тариф возвращаем, только если у человека нет
            // другой действующей подписки.
            $hasAnother = $subscription->user
                ->subscriptions()
                ->active()
                ->whereKeyNot($subscription->id)
                ->exists();

            if (! $hasAnother) {
                $subscription->user->forceFill([
                    'plan' => config('plans.default'),
                ])->save();
            }
        }

        $this->info("Обработано подписок: {$expired->count()}");

        return self::SUCCESS;
    }
}
