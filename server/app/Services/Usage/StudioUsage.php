<?php

namespace App\Services\Usage;

use App\Models\Guest;
use App\Models\User;

/**
 * Пределы для страницы Студии: остаток каждого раздела и цена работ
 * в процентах.
 *
 * Остаток у всех в одном виде — проценты и когда он обновится, — чтобы
 * формы не разбирались, гость перед ними, бесплатный тариф или
 * подписчик. Без подписки у раздела свой дневной предел, и остаток —
 * меньшее из дневного и бюджета.
 */
class StudioUsage
{
    private const TOOLS = ['image', 'edit', 'audio', 'video'];

    public function __construct(
        private readonly UsageMeter $meter,
        private readonly FreeTier $free,
    ) {}

    /**
     * share — сколько процентов бюджета стоит доллар; null — цены не
     * показываем (гостю: у него проба, а не бюджет).
     *
     * @return array{share: float|null, limits: array<string, array{percentLeft: int, renewsAt: string, exhausted: bool}|null>, caps: array<string, mixed>|null}
     */
    public function for(?User $user, ?Guest $guest): array
    {
        $usage = $user ? $this->meter->snapshot($user) : null;
        $limits = [];

        foreach (self::TOOLS as $tool) {
            $limits[$tool] = $this->limit($user, $guest, $tool, $usage);
        }

        return [
            'share' => $usage ? ($usage->shareOf(1.0) ?: null) : null,
            'limits' => $limits,
            'caps' => $this->free->caps($user, $guest),
        ];
    }

    /** @return array{percentLeft: int, renewsAt: string, exhausted: bool}|null */
    private function limit(?User $user, ?Guest $guest, string $tool, ?UsageSnapshot $usage): ?array
    {
        $daily = $this->free->percentLeft($user, $guest, $tool);

        if (! $usage) {
            return $daily === null ? null : [
                'percentLeft' => $daily,
                'renewsAt' => 'tomorrow',
                'exhausted' => $daily === 0,
            ];
        }

        $budget = 100 - $usage->percentUsed();
        $dailyFirst = $daily !== null && $daily <= $budget;
        $left = $dailyFirst ? $daily : $budget;

        return [
            'percentLeft' => $left,
            'renewsAt' => $dailyFirst ? 'tomorrow' : $usage->periodEnd->format('j M'),
            'exhausted' => $left === 0 || $usage->exhausted(),
        ];
    }
}
