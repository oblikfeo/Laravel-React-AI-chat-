<?php

namespace App\Services\Usage;

use App\Models\UsageRecord;
use App\Models\User;
use Carbon\CarbonImmutable;

/**
 * Сколько бюджета тарифа израсходовал пользователь.
 *
 * Период — месяц. У подписчика он отсчитывается от дня оплаты, у
 * бесплатного тарифа — календарный. Неистраченное на старших тарифах
 * переходит в следующий период, см. rollover в config/plans.php.
 */
class UsageMeter
{
    /** Группы трат, которые видит человек. */
    private const GROUPS = [
        UsageRecord::CATEGORY_CHAT => 'chat',
        UsageRecord::CATEGORY_VOICE => 'chat',
        UsageRecord::CATEGORY_SERVICE => 'chat',
        UsageRecord::CATEGORY_IMAGE => 'images',
        UsageRecord::CATEGORY_EDIT => 'images',
        UsageRecord::CATEGORY_AUDIO => 'audio',
        UsageRecord::CATEGORY_VIDEO => 'video',
    ];

    public function snapshot(User $user, ?CarbonImmutable $at = null): UsageSnapshot
    {
        $now = $at ?? CarbonImmutable::now();
        $plan = $user->planUsage();
        $anchor = $this->anchor($user);
        $index = $anchor ? $this->cycleIndex($anchor, $now) : 0;

        [$start, $end] = $anchor
            ? $this->cycle($anchor, $index)
            : [$now->startOfMonth(), $now->startOfMonth()->addMonth()];

        $carried = $anchor
            ? $this->carried($user, $anchor, $index, $plan['allowance'], $plan['rollover'] - 1)
            : 0.0;

        $byCategory = UsageRecord::query()
            ->where('user_id', $user->id)
            ->between($start, $end)
            ->selectRaw('category, SUM(cost) as total')
            ->groupBy('category')
            ->pluck('total', 'category');

        $byGroup = ['chat' => 0.0, 'images' => 0.0, 'audio' => 0.0, 'video' => 0.0];

        foreach ($byCategory as $category => $total) {
            $byGroup[self::GROUPS[$category] ?? 'chat'] += (float) $total;
        }

        return new UsageSnapshot(
            periodStart: $start,
            periodEnd: $end,
            base: $plan['allowance'],
            carried: $carried,
            spent: array_sum($byGroup),
            dailyLimit: $plan['daily_share'] !== null ? $plan['allowance'] * $plan['daily_share'] : null,
            spentToday: $plan['daily_share'] !== null
                ? $this->spent($user, $now->startOfDay(), $now->startOfDay()->addDay())
                : 0.0,
            byGroup: $byGroup,
        );
    }

    public function allows(User $user): bool
    {
        return $this->denial($user) === null;
    }

    /**
     * Почему нельзя запустить ещё одну трату, или null — можно.
     *
     * $cost — цена запроса, если она известна заранее (работа Студии):
     * запрос дороже остатка не запускается. Цену ответа в чате заранее
     * не узнать, поэтому он проверяется только по остатку.
     */
    public function denial(User $user, float $cost = 0.0): ?string
    {
        $usage = $this->snapshot($user);
        $upgrade = $user->isPromotedPlan() ? ' — or upgrade for more.' : '.';

        if ($usage->exhausted()) {
            return "You've used this month's allowance. It renews on {$usage->periodEnd->format('j M')}{$upgrade}";
        }

        if ($cost > 0 && $usage->spent + $cost > $usage->available()) {
            return "This needs more than what's left of your allowance ({$usage->toArray()['percentLeft']}% left). "
                ."Try a shorter or simpler version, or wait until {$usage->periodEnd->format('j M')}{$upgrade}";
        }

        if ($usage->dailyExhausted()) {
            return "You've used today's allowance. It renews tomorrow"
                .($user->isPromotedPlan() ? ' — or upgrade for more.' : '.');
        }

        return null;
    }

    public function spent(User $user, CarbonImmutable $from, CarbonImmutable $to): float
    {
        return (float) UsageRecord::query()
            ->where('user_id', $user->id)
            ->between($from, $to)
            ->sum('cost');
    }

    /** С какого дня отсчитываются периоды: день оплаты подписки. */
    private function anchor(User $user): ?CarbonImmutable
    {
        $starts = $user->activeSubscription()?->starts_at;

        return $starts ? CarbonImmutable::parse($starts) : null;
    }

    /** Номер текущего месяца подписки, считая от нуля. */
    private function cycleIndex(CarbonImmutable $anchor, CarbonImmutable $now): int
    {
        $index = max(0, (int) floor($anchor->diffInMonths($now)));

        while ($index > 0 && $anchor->addMonthsNoOverflow($index)->greaterThan($now)) {
            $index--;
        }

        while ($anchor->addMonthsNoOverflow($index + 1)->lessThanOrEqualTo($now)) {
            $index++;
        }

        return $index;
    }

    /**
     * Границы месяца подписки. Каждая граница считается от дня оплаты,
     * а не от предыдущей: так 31-е число не сползает к 28-му.
     *
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    private function cycle(CarbonImmutable $anchor, int $index): array
    {
        return [$anchor->addMonthsNoOverflow($index), $anchor->addMonthsNoOverflow($index + 1)];
    }

    /**
     * Остаток, перешедший из прошлых месяцев.
     *
     * Остаток прошлого месяца — это его бюджет (с тем, что перешло в
     * него самого) минус траты. Копится не больше depth месяцев тарифа.
     */
    private function carried(User $user, CarbonImmutable $anchor, int $index, float $base, int $depth): float
    {
        if ($depth <= 0 || $index <= 0) {
            return 0.0;
        }

        [$start, $end] = $this->cycle($anchor, $index - 1);

        $available = $base + $this->carried($user, $anchor, $index - 1, $base, $depth - 1);
        $left = max(0.0, $available - $this->spent($user, $start, $end));

        return min($left, $base * $depth);
    }
}
