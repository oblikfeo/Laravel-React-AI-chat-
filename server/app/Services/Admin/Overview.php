<?php

namespace App\Services\Admin;

use App\Models\Payment;
use App\Models\Subscription;
use App\Models\UsageRecord;
use App\Models\User;
use Carbon\CarbonImmutable;

/**
 * Сводка для главной страницы админки.
 *
 * Деньги клиентов — в валюте платежей и в копейках (центах), как они
 * лежат в базе. Расходы на провайдеров — в долларах себестоимости.
 * Это разные валюты, поэтому они не складываются друг с другом.
 */
class Overview
{
    /** Сколько дней показывает график расходов. */
    private const DAYS = 30;

    /** @return array<string, mixed> */
    public function build(?CarbonImmutable $now = null): array
    {
        $now ??= CarbonImmutable::now();
        $month = $now->startOfMonth();

        return [
            'month' => $month->format('F Y'),
            'revenue' => [
                'month' => $this->revenue($month, $month->addMonth()),
                'total' => $this->revenue(null, null),
                'payments' => Payment::query()
                    ->where('status', Payment::STATUS_SUCCEEDED)
                    ->where('paid_at', '>=', $month)
                    ->count(),
            ],
            'subscriptions' => $this->subscriptions(),
            'users' => [
                'total' => User::count(),
                'new' => User::where('created_at', '>=', $now->subDays(self::DAYS))->count(),
                'paying' => Subscription::query()->active()->distinct('user_id')->count('user_id'),
            ],
            'spend' => [
                'month' => round((float) UsageRecord::where('created_at', '>=', $month)->sum('cost'), 4),
                'byCategory' => UsageRecord::query()
                    ->where('created_at', '>=', $month)
                    ->selectRaw('category, SUM(cost) as spend, COUNT(*) as requests')
                    ->groupBy('category')
                    ->orderByDesc('spend')
                    ->get()
                    ->map(fn ($line) => [
                        'category' => $line->category,
                        'spend' => round((float) $line->spend, 4),
                        'requests' => (int) $line->requests,
                    ])
                    ->all(),
                'daily' => $this->daily($now),
                'topUsers' => $this->topUsers($month),
            ],
        ];
    }

    /**
     * Оплачено за период по валютам: [{currency, amount}], amount в
     * копейках (центах).
     *
     * @return array<int, array{currency: string, amount: int}>
     */
    private function revenue(?CarbonImmutable $from, ?CarbonImmutable $to): array
    {
        return Payment::query()
            ->where('status', Payment::STATUS_SUCCEEDED)
            ->when($from, fn ($query) => $query->where('paid_at', '>=', $from)->where('paid_at', '<', $to))
            ->selectRaw('currency, SUM(amount) as amount')
            ->groupBy('currency')
            ->get()
            ->map(fn ($line) => ['currency' => $line->currency, 'amount' => (int) $line->amount])
            ->all();
    }

    /**
     * Действующие подписки по тарифам и месячный доход с них: годовая
     * подписка делится на двенадцать.
     *
     * @return array<string, mixed>
     */
    private function subscriptions(): array
    {
        $active = Subscription::query()->active()->get(['plan', 'period', 'amount', 'currency']);

        return [
            'active' => $active->count(),
            'byPlan' => $active
                ->groupBy('plan')
                ->map(fn ($group, $plan) => [
                    'plan' => config("plans.list.{$plan}.name", $plan),
                    'count' => $group->count(),
                ])
                ->values()
                ->all(),
            'monthly' => $active
                ->groupBy('currency')
                ->map(fn ($group, $currency) => [
                    'currency' => $currency,
                    'amount' => (int) round($group->sum(fn ($subscription) => $subscription->period === Subscription::PERIOD_YEARLY
                        ? $subscription->amount / 12
                        : $subscription->amount)),
                ])
                ->values()
                ->all(),
        ];
    }

    /**
     * Расходы по дням за последние 30 дней, без пропусков: день без
     * трат — ноль, а не дыра в графике.
     *
     * @return array<int, array{date: string, spend: float}>
     */
    private function daily(CarbonImmutable $now): array
    {
        $from = $now->startOfDay()->subDays(self::DAYS - 1);

        $spent = UsageRecord::query()
            ->where('created_at', '>=', $from)
            ->selectRaw('DATE(created_at) as day, SUM(cost) as spend')
            ->groupBy('day')
            ->pluck('spend', 'day');

        return collect(range(0, self::DAYS - 1))
            ->map(function (int $offset) use ($from, $spent) {
                $day = $from->addDays($offset);

                return [
                    'date' => $day->format('j M'),
                    'spend' => round((float) ($spent[$day->toDateString()] ?? 0), 4),
                ];
            })
            ->all();
    }

    /**
     * Кто тратит больше всех в этом месяце.
     *
     * @return array<int, array<string, mixed>>
     */
    private function topUsers(CarbonImmutable $month): array
    {
        $top = UsageRecord::query()
            ->where('created_at', '>=', $month)
            ->whereNotNull('user_id')
            ->selectRaw('user_id, SUM(cost) as spend')
            ->groupBy('user_id')
            ->orderByDesc('spend')
            ->limit(5)
            ->get();

        $users = User::whereIn('id', $top->pluck('user_id'))->get()->keyBy('id');

        return $top
            ->filter(fn ($line) => $users->has($line->user_id))
            ->map(fn ($line) => [
                'id' => $line->user_id,
                'name' => $users[$line->user_id]->name,
                'email' => $users[$line->user_id]->email,
                'plan' => $users[$line->user_id]->planName(),
                'spend' => round((float) $line->spend, 4),
            ])
            ->values()
            ->all();
    }
}
