<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Выдаёт тариф вручную.
 *
 * Нужно для своих аккаунтов и тех, кому доступ даётся без оплаты:
 * команда создаёт настоящую подписку, а не правит поле в базе, чтобы
 * человек видел ровно то же, что увидел бы после платежа.
 */
class GrantPlan extends Command
{
    protected $signature = 'plan:grant
        {email : Почта пользователя}
        {plan=pro : Тариф из config/plans.php}
        {--months=12 : На сколько месяцев}';

    protected $description = 'Выдаёт тариф без оплаты';

    public function handle(): int
    {
        $email = $this->argument('email');
        $plan = $this->argument('plan');
        $months = max((int) $this->option('months'), 1);

        if (! config("plans.list.{$plan}")) {
            $this->error("Нет такого тарифа: {$plan}");
            $this->line('Доступны: '.implode(', ', array_keys(config('plans.list'))));

            return self::FAILURE;
        }

        $user = User::where('email', $email)->first();

        if (! $user) {
            $this->error("Пользователь не найден: {$email}");

            return self::FAILURE;
        }

        DB::transaction(function () use ($user, $plan, $months) {
            // Прежние подписки закрываем: иначе у человека окажется
            // две действующие, и непонятно, какая главная.
            $user->subscriptions()->active()->update([
                'status' => Subscription::STATUS_CANCELLED,
                'cancelled_at' => now(),
            ]);

            $user->subscriptions()->create([
                'plan' => $plan,
                'period' => $months >= 12
                    ? Subscription::PERIOD_YEARLY
                    : Subscription::PERIOD_MONTHLY,
                'status' => Subscription::STATUS_ACTIVE,
                // Выдано вручную, а не платёжной системой: так видно,
                // что денег по этой подписке не было.
                'provider' => 'manual',
                'amount' => 0,
                'currency' => 'RUB',
                'starts_at' => now(),
                'ends_at' => now()->addMonths($months),
            ]);

            $user->forceFill(['plan' => $plan])->save();
        });

        $this->info(sprintf(
            '%s: тариф %s до %s',
            $user->email,
            config("plans.list.{$plan}.name"),
            $user->activeSubscription()->ends_at->format('d.m.Y'),
        ));

        return self::SUCCESS;
    }
}
