<?php

namespace App\Services\Usage;

use Carbon\CarbonImmutable;

/**
 * Состояние бюджета пользователя в текущем периоде.
 *
 * Доллары остаются внутри: наружу, в интерфейс, уходят только доли —
 * toArray() отдаёт проценты и даты.
 */
final class UsageSnapshot
{
    /**
     * @param  array<string, float>  $byGroup  траты по группам: chat, images, audio, video
     */
    public function __construct(
        public readonly CarbonImmutable $periodStart,
        public readonly CarbonImmutable $periodEnd,
        public readonly float $base,
        public readonly float $carried,
        public readonly float $spent,
        public readonly ?float $dailyLimit,
        public readonly float $spentToday,
        public readonly array $byGroup,
    ) {}

    /** Сколько доступно в этом периоде: тариф и перенесённый остаток. */
    public function available(): float
    {
        return $this->base + $this->carried;
    }

    public function exhausted(): bool
    {
        return $this->spent >= $this->available();
    }

    public function dailyExhausted(): bool
    {
        return $this->dailyLimit !== null && $this->spentToday >= $this->dailyLimit;
    }

    /** Доля бюджета, израсходованная в периоде, 0–100. */
    public function percentUsed(): int
    {
        return self::percent($this->spent, $this->available());
    }

    /** Сколько процентов бюджета займёт трата в долларах. */
    public function shareOf(float $cost): float
    {
        return $this->available() > 0 ? $cost / $this->available() * 100 : 0.0;
    }

    /**
     * Для интерфейса: только проценты и даты.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'percentUsed' => $this->percentUsed(),
            'percentLeft' => 100 - $this->percentUsed(),
            'renewsAt' => $this->periodEnd->format('j M'),
            // Перенесённый остаток — в долях месячного бюджета тарифа.
            'carriedPercent' => $this->base > 0 ? (int) round($this->carried / $this->base * 100) : 0,
            'exhausted' => $this->exhausted(),
            'daily' => $this->dailyLimit === null ? null : [
                'percentUsed' => self::percent($this->spentToday, $this->dailyLimit),
                'exhausted' => $this->dailyExhausted(),
            ],
            'breakdown' => collect($this->byGroup)
                ->map(fn (float $cost) => self::percent($cost, $this->available()))
                ->all(),
        ];
    }

    /**
     * Для админки: те же цифры, но с долларами себестоимости.
     *
     * @return array<string, mixed>
     */
    public function toAdminArray(): array
    {
        return [
            'percentUsed' => $this->percentUsed(),
            'spent' => round($this->spent, 4),
            'available' => round($this->available(), 4),
            'carried' => round($this->carried, 4),
            'periodStart' => $this->periodStart->format('j M Y'),
            'renewsAt' => $this->periodEnd->format('j M Y'),
            'exhausted' => $this->exhausted(),
            'byGroup' => collect($this->byGroup)->map(fn (float $cost) => round($cost, 4))->all(),
        ];
    }

    /**
     * Целый процент. Любая ненулевая трата — хотя бы 1%: иначе человек
     * потратил, а полоска стоит на нуле.
     */
    private static function percent(float $part, float $whole): int
    {
        if ($whole <= 0) {
            return $part > 0 ? 100 : 0;
        }

        if ($part <= 0) {
            return 0;
        }

        return (int) min(100, max(1, floor($part / $whole * 100)));
    }
}
